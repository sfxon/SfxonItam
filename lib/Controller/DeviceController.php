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
        $device = $this->deviceMapper->findById($id);

        if($device !== null) {
            $this->deviceMapper->delete($device);
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
        int $limit = 20
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $devices = $this->deviceMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->deviceMapper->countAll();

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
        $expectedFields = ['name', 'purchaseDate', 'userId'];

        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $expectedFields);
        $result = $this->deviceService->validateDeviceData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $device = new Device();
        $device->setName($this->request->getParam('name'));
        $device->setUserId($this->request->getParam('userId') ?? '');
        $purchaseDateRaw = $this->request->getParam('purchaseDate');

        if ($purchaseDateRaw !== null) {
            $device->setPurchaseDate($purchaseDateRaw); // Format: 'YYYY-MM-DD'
        }

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

        $expectedFields = ['name', 'purchaseDate', 'userId'];
        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $expectedFields);
        $result = $this->deviceService->validateDeviceData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $device->setName($this->request->getParam('name'));
        $device->setUserId($this->request->getParam('userId') ?? '');
        $purchaseDateRaw = $this->request->getParam('purchaseDate');
        $device->setPurchaseDate($purchaseDateRaw);

        $updated = $this->deviceMapper->update($device);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
