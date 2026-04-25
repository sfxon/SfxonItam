<?php
declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCP\AppFramework\Controller;
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
        string        $appName,
        IRequest      $request,
        private DeviceMapper $deviceMapper,
        private readonly DeviceService $deviceService
    ) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'device/index',
        );
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
        $expectedFields = ['name', 'invoiceDate', 'userId'];

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
        $purchaseDateRaw = $this->request->getParam('invoiceDate');

        if ($purchaseDateRaw !== null) {
            $device->setPurchaseDate($purchaseDateRaw); // Format: 'YYYY-MM-DD'
        }

        $saved = $this->deviceMapper->insert($device);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }
}
