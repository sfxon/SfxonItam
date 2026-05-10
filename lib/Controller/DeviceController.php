<?php
declare(strict_types=1);

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
use OCA\SfxonItam\Service\DeviceService;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceController extends Controller {
    private array $expectedFields = [
        'assetNumber',
        'deviceStatusId',
        'deviceTypeId',
        'invoiceNumber',
        'itamUserId',
        'merchantId',
        'name',
        'purchaseDate',
        'positionId',
        'serialNumber',
        'serialNumber2'
    ];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private readonly DeviceService $deviceService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device/{id}')]
    public function delete(int $id): JsonResponse {
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
    public function deviceDetail(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'device/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse {
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
        ?array $filters = null
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $devices = $this->deviceMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->deviceMapper->countAll($filters);

        return new JSONResponse([
            'devices' => array_map(fn($d) => $d->jsonSerialize(), $devices),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/save')]
    public function save(): DataResponse {
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
    #[FrontpageRoute(verb: 'GET', url: '/device/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $device = $this->deviceMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($device->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device/{id}')]
    public function update(int $id): DataResponse {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $device = $this->deviceMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceService->validateData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Datensatz aktualisieren
        $device = $this->setDeviceDataFromRequest($device);
        $updated = $this->deviceMapper->update($device);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }

    private function setDeviceDataFromRequest($device) {
        $device->setAssetNumber($this->request->getParam('assetNumber'));

        $deviceStatusId = $this->sanitizeForeignKey($this->request->getParam('deviceStatusId') ?? '');
        $device->setDeviceStatusId($deviceStatusId);

        $deviceTypeId = $this->sanitizeForeignKey($this->request->getParam('deviceTypeId') ?? '');
        $device->setDeviceTypeId($deviceTypeId);

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

        $device->setSerialNumber($this->request->getParam('serialNumber'));

        $device->setSerialNumber2($this->request->getParam('serialNumber2'));

        return $device;
    }

    private function sanitizeForeignKey($foreignKeyValue) {
        return ($foreignKeyValue == 0 || $foreignKeyValue == '0' || $foreignKeyValue == '') ? null : $foreignKeyValue;
    }
}
