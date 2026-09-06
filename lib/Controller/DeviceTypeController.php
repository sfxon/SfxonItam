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
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\DeviceTypeMapper;
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeviceTypeService;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceTypeController extends Controller
{
    private array $expectedFields = ['name', 'manufacturer_id', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private DeviceTypeMapper $deviceTypeMapper,
        private readonly DeviceTypeService $deviceTypeService,
        private CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device-type/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('device_type_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this deviceType.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $deviceType = $this->deviceTypeMapper->findById($id)['mainData'];
            $this->deviceTypeMapper->delete($deviceType);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/detail')]
    public function deviceTypeDetail(): TemplateResponse
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

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_type');

        return new TemplateResponse(
            Application::APP_ID,
            'device-type/editor',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'device-type/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $data = $this->deviceTypeMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->deviceTypeMapper->countAll();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(/* $customFields */), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'deviceTypes' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/listall')]
    public function listall(): JSONResponse
    {
        $deviceTypes = $this->deviceTypeMapper->findAll();

        return new JSONResponse([
            'deviceTypes' => array_map(fn($d) => $d->jsonSerialize(), $deviceTypes),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device-type/save')]
    public function save(): DataResponse
    {
        $data = $this->deviceTypeService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceTypeService->validateData($data);
        $deviceType = new DeviceType();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_type');
        $deviceType = $this->setDeviceTypeDataFromRequest($deviceType);
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

        $saved = $this->deviceTypeMapper->insert($deviceType);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_device_type', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device-type/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');

        $data = $this->deviceTypeMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters);
        $total = $this->deviceTypeMapper->countAll($filters);

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(), $data['mainData']);

        return new JSONResponse([
            'mainData' => $data['mainData'],
            'relations' => $data['relations'],
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = ['manufacturer' => []];
            $data = $this->deviceTypeMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'DeviceType not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_type');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];
        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device-type/{id}')]
    public function update(int $id): DataResponse {
        // Return 404 if entry was not found.
        try {
            $deviceType = $this->deviceTypeMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'DeviceType not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceTypeService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceTypeService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_type');
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

        $deviceType = $this->setDeviceTypeDataFromRequest($deviceType);
        $updated = $this->deviceTypeMapper->update($deviceType);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_device_type', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function setDeviceTypeDataFromRequest($deviceType)
    {
        $deviceType->setName($this->request->getParam('name'));

        $manufacturerId = (int)$this->request->getParam('manufacturerId');
        
        if($manufacturerId === 0) {
            $manufacturerId = null;
        }

        $deviceType->setManufacturerId($manufacturerId);
        $deviceType->setComment($this->request->getParam('comment') ?? '');

        return $deviceType;
    }
}
