<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\LocationMapper;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Definition\LocationDefinition;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\LocationService;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;


/**
 * @psalm-suppress UnusedClass
 */
class LocationController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private LocationMapper $locationMapper,
        private readonly LocationService $locationService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,
        private readonly LocationDefinition $definition,
        private EntityRegistry $entityRegistry,
        private DeleteGuardService $deleteGuardService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/location/{id}')]
    public function delete(int $id): JsonResponse
    {
        $violation = $this->deleteGuardService->findViolation($this->definition, $id);

        if($violation !== null) {
            return new JSONResponse([
                'status' => 'error',
                'errors' => [$violation]
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        try {
            $location = $this->locationMapper->findById($id);
            $this->locationMapper->delete($location['mainData']);
        } catch(DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/detail')]
    public function locationDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            $this->definition->templateDir() . '/editor',
            $this->getTemplateParameters()
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/')]
    public function index(): TemplateResponse
    {
        $listId = $this->definition->listId();

        $this->initialState->provideInitialState(
            'listViewColumnOrder-' . $listId,
            $this->listViewSettingsService->getColumnOrder($listId)
        );

        $this->initialState->provideInitialState(
            'listViewUiState-' . $listId,
            $this->listViewSettingsService->getUiState($listId)
        );

        return new TemplateResponse(
            Application::APP_ID,
            $this->definition->templateDir() . '/list',
            $this->getTemplateParameters()
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/list')]
    public function list(
        ?string $orderBy = null,
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 25,
        ?array $filters = null,): JSONResponse
    {
        $orderBy ??= $this->definition->defaultOrderBy;

        if($limit != 10 && $limit != 25 && $limit != 50 && $limit != 100 && $limit != 500 && $limit != 1000) {
            $limit = 25;
        }

        $offset = ($page - 1) * $limit;
        $data = $this->locationMapper->findAllPaged(
            $orderBy,
            $direction,
            $limit,
            $offset,
            $filters,
            $this->definition->listIncludes
        );
        $total   = $this->locationMapper->countAll($filters);
        $customFields = $this->getCustomFields();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize($customFields), $data['mainData']);

        return new JSONResponse([
            'locations' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/save')]
    public function save(): DataResponse
    {
        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $this->definition->expectedFields);
        $result = $this->locationService->validateData($data);
        $location = new Location();
        $customFields = $this->getCustomFields();
        $location = $this->setLocationDataFromRequest($location);
        $customFieldData = $this->customFieldService->getCustomFieldDataFromRequest($customFields, $this->request->getParams());
        $customFieldErrors = $this->customFieldService->validateCustomFieldData($customFields, $customFieldData);

        if(count($customFieldErrors) > 0) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $customFieldErrors);
        }

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $saved = $this->locationMapper->insert($location);
        $this->customFieldService->updateCustomFieldsForEntity($this->definition->customFieldGroup, $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');
            $data = $this->locationMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data['mainData'] = $data['mainData']->jsonSerialize($this->getCustomFields());

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/location/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $location = $this->locationMapper->findById($id)['mainData'];
        } catch (DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $this->definition->expectedFields);
        $result = $this->locationService->validateData($data, $id);
        $customFields = $this->getCustomFields();
        $customFieldData = $this->customFieldService->getCustomFieldDataFromRequest($customFields, $this->request->getParams());
        $customFieldErrors = $this->customFieldService->validateCustomFieldData($customFields, $customFieldData);

        if(count($customFieldErrors) > 0) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $customFieldErrors);
        }

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $location = $this->setLocationDataFromRequest($location);
        $updated = $this->locationMapper->update($location);
        $this->customFieldService->updateCustomFieldsForEntity($this->definition->customFieldGroup, $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function getCustomFields()
    {
        return $this->customFieldService->getCustomFieldsDefinitionByGroup($this->definition->customFieldGroup);
    }

    private function getTemplateParameters(): array
    {
        return [
            'entityDefinitions' => $this->entityRegistry->fieldDefinitions(),
            'customFields' => $this->getCustomFields(),
        ];
    }

    private function setLocationDataFromRequest($location)
    {
        $location->setName($this->request->getParam('name'));
        $location->setComment($this->request->getParam('comment') ?? '');

        return $location;
    }
}