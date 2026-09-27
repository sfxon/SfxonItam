<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\PositionMapper;
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\PositionService;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;


/**
 * @psalm-suppress UnusedClass
 */
class PositionController extends Controller
{
    private array $expectedFields = ['name', 'location_id', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private PositionMapper $positionMapper,
        private readonly PositionService $positionService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/position/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('position_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this position.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        // @TODO:
        // Change this. Not showing an error, could lead to a user not seeing, that there was a problem and the data still exists.
        try {
            $position = $this->positionMapper->findById($id);
            $this->positionMapper->delete($position['mainData']);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/detail')]
    public function positionDetail(): TemplateResponse
    {
        $entityDefinitions = [
            'deviceStatus' => DeviceStatus::getFieldDefinition(),
            'deviceType' => DeviceType::getFieldDefinition(),
            'itamUser' => ItamUser::getFieldDefinition(),
            'location' => Location::getFieldDefinition(),
            'manufacturer' => Manufacturer::getFieldDefinition(),
            'merchant' => Merchant::getFieldDefinition(),
            'position' => Position::getFieldDefinition(),
            'quantityUnit' => QuantityUnit::getFieldDefinition(),
        ];

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_position');

        return new TemplateResponse(
            Application::APP_ID,
            'position/editor',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/')]
    public function index(): TemplateResponse
    {
        $listId = 'position-list';

        $this->initialState->provideInitialState(
            'listViewColumnOrder-' . $listId,
            $this->listViewSettingsService->getColumnOrder($listId)
        );

        $this->initialState->provideInitialState(
            'listViewUiState-' . $listId,
            $this->listViewSettingsService->getUiState($listId)
        );

        $entityDefinitions = [
            'deviceStatus' => DeviceStatus::getFieldDefinition(),
            'deviceType' => DeviceType::getFieldDefinition(),
            'itamUser' => ItamUser::getFieldDefinition(),
            'location' => Location::getFieldDefinition(),
            'manufacturer' => Manufacturer::getFieldDefinition(),
            'merchant' => Merchant::getFieldDefinition(),
            'position' => Position::getFieldDefinition(),
            'quantityUnit' => QuantityUnit::getFieldDefinition(),
        ];

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device');
        return new TemplateResponse(
            Application::APP_ID,
            'position/list',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 25,
        ?array $filters = null,): JSONResponse
    {
        if($limit != 10 && $limit != 25 && $limit != 50 && $limit != 100 && $limit != 500 && $limit != 1000) {
            $limit = 25;
        }

        $offset = ($page - 1) * $limit;
        $include = $this->getDefaultIncludes();
        $data = $this->positionMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->positionMapper->countAll($filters);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize($customFields), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'positions' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/position/save')]
    public function save(): DataResponse
    {
        $data = $this->positionService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->positionService->validateData($data);
        $position = new Position();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_position');
        $position = $this->setPositionDataFromRequest($position);
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

        $saved = $this->positionMapper->insert($position);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_position', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }
    
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');
        
        if(!is_array($include)) {
            $include = [];
        }

        if(!isset($include['location'])) {
                $include['location'] = [];
        }

            $data = $this->positionMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Position not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_position');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/position/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $position = $this->positionMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Position not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->positionService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->positionService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_position');
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

        $position = $this->setPositionDataFromRequest($position);
        $updated = $this->positionMapper->update($position);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_position', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function getDefaultIncludes(): array {
        return [
            'deviceStatus' => [],
            'deviceType' => [],
            'itamUser' => ['fields' => ['id', 'firstname', 'lastname']],
            'location' => [],
            'merchant' => [],
            'position' => [
                'fields' => ['id', 'name', 'location_id'],
                'with' => [
                    'location' => [
                        'table' => 'sfxon_location',
                        'localKey' => 'location_id',
                        'fields' => ['id', 'name'],
                    ],
                ],
            ],
            'quantityUnit' => [],
        ];
    }

    private function sanitizeForeignKey($foreignKeyValue)
    {
        $foreignKeyValue = intval($foreignKeyValue);

        return ($foreignKeyValue === 0) ? null : $foreignKeyValue;
    }

    private function setPositionDataFromRequest($position)
    {
        $locationId = (int)$this->request->getParam('locationId');
        
        if($locationId === 0) {
            $locationId = null;
        }

        $position->setName($this->request->getParam('name'));
        $position->setLocationId($locationId);
        $position->setComment($this->request->getParam('comment') ?? '');

        return $position;
    }
}
