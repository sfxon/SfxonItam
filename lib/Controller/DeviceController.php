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
use OCA\SfxonItam\Db\Device;
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeviceService;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceController extends Controller
{
    private array $expectedFields = [
        'assetNumber',
        'deviceStatusId',
        'deviceTypeId',
        'description',
        'imageFileId',
        'invoiceNumber',
        'itamUserId',
        'merchantId',
        'name',
        'purchaseDate',
        'positionId',
        'quantity',
        'quantityUnitId',
        'serialNumber',
        'serialNumber2'
    ];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private readonly DeviceService $deviceService,
        private CustomFieldGroupMapper $customFieldGroupMapper,
        private CustomFieldMapper $customFieldMapper,
        private CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $device = $this->deviceMapper->findById($id);
            $this->deviceMapper->delete($device);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device/detail')]
    public function deviceDetail(): TemplateResponse
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

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup();

        return new TemplateResponse(
            Application::APP_ID,
            'device/editor',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'device/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,
        ?array $filters = null,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $include = $this->getDefaultIncludes();
        $data = $this->deviceMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->deviceMapper->countAll($filters);

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(/* $customFields */), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'devices' => $data,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/save')]
    public function save(): DataResponse
    {
        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $device = new Device();
        $device = $this->setDeviceDataFromRequest($device);

        $saved = $this->deviceMapper->insert($device);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');

            $data = $this->deviceMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup();
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $device = $this->deviceMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceService->validateData($data, $id);

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup();
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

        // Update.
        $device = $this->setDeviceDataFromRequest($device);
        $updated = $this->deviceMapper->update($device);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_device', $updated->getId(), $customFieldData);

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

    private function setDeviceDataFromRequest($device)
    {
        $device->setAssetNumber($this->request->getParam('assetNumber'));

        $deviceStatusId = $this->sanitizeForeignKey($this->request->getParam('deviceStatusId') ?? '');
        $device->setDeviceStatusId($deviceStatusId);

        $deviceTypeId = $this->sanitizeForeignKey($this->request->getParam('deviceTypeId') ?? '');
        $device->setDeviceTypeId($deviceTypeId);

        $device->setDescription($this->request->getParam('description'));

        $imageFileId = $this->sanitizeForeignKey($this->request->getParam('imageFileId') ?? '');
        $device->setImageFileId($imageFileId);

        $device->setInvoiceNumber($this->request->getParam('invoiceNumber'));

        $itamUserId = $this->sanitizeForeignKey($this->request->getParam('itamUserId') ?? '');
        $device->setItamUserId($itamUserId);

        $merchantId = $this->sanitizeForeignKey($this->request->getParam('merchantId') ?? '');
        $device->setMerchantId($merchantId);

        $device->setName($this->request->getParam('name'));

        $positionId = $this->sanitizeForeignKey($this->request->getParam('positionId') ?? '');
        $device->setPositionId($positionId);
        
        $purchaseDateRaw = $this->request->getParam('purchaseDate');
        $device->setPurchaseDate($purchaseDateRaw);

        $quantity = $this->request->getParam('quantity');
        $quantity = is_numeric($quantity) ? (float)$quantity : null;
        $device->setQuantity($quantity);

        $quantityUnitId = $this->sanitizeForeignKey($this->request->getParam('quantityUnitId') ?? '');
        $device->setQuantityUnitId($quantityUnitId);

        $device->setSerialNumber($this->request->getParam('serialNumber'));

        $device->setSerialNumber2($this->request->getParam('serialNumber2'));

        return $device;
    }
}
