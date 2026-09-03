<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\PositionMapper;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\LocationMapper;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\LocationService;

/**
 * @psalm-suppress UnusedClass
 */
class LocationController extends Controller
{
    private array $expectedFields = ['name', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private PositionMapper $positionMapper,
        private LocationMapper $locationMapper,
        private readonly LocationService $locationService,
        private CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/location/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->positionMapper->isEntityValueInUse('location_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still positions assigned to this location.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $location = $this->locationMapper->findById($id)['mainData'];
            $this->locationMapper->delete($location);
        } catch(\Error $error) {
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
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_location');

        return new TemplateResponse(
            Application::APP_ID,
            'location/editor',
            [
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'location/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $data = $this->locationMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->locationMapper->countAll();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(/* $customFields */), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'locations' => $data,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/listall')]
    public function listall(): JSONResponse
    {
        $locations = $this->locationMapper->findAll();

        return new JSONResponse([
            'locations' => array_map(fn($d) => $d->jsonSerialize(), $locations),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/save')]
    public function save(): DataResponse
    {
        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->locationService->validateData($data);
        $location = new Location();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_location');
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
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_location', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->locationMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->locationMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $data = $this->locationMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Location not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_location');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/location/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $location = $this->locationMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Location not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->locationService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_location');
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

        // Felder aktualisieren
        $location = $this->setLocationDataFromRequest($location);
        $updated = $this->locationMapper->update($location);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_location', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function setLocationDataFromRequest($location)
    {
        $location->setName($this->request->getParam('name'));
        $location->setComment($this->request->getParam('comment') ?? '');

        return $location;
    }
}