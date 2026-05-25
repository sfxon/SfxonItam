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
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\DeviceTypeMapper;
use OCA\SfxonItam\Service\DeviceTypeService;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceTypeController extends Controller {
    private array $expectedFields = ['name', 'manufacturer_id', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private DeviceTypeMapper $deviceTypeMapper,
        private readonly DeviceTypeService $deviceTypeService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device-type/{id}')]
    public function delete(int $id): JsonResponse {
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
            $deviceType = $this->deviceTypeMapper->findById($id);
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
    public function deviceTypeDetail(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'device-type/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/')]
    public function index(): TemplateResponse {
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
        int $limit = 20
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $deviceTypes = $this->deviceTypeMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->deviceTypeMapper->countAll();

        return new JSONResponse([
            'deviceTypes' => array_map(fn($d) => $d->jsonSerialize(), $deviceTypes),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/listall')]
    public function listall(): JSONResponse {
        $deviceTypes = $this->deviceTypeMapper->findAll();

        return new JSONResponse([
            'deviceTypes' => array_map(fn($d) => $d->jsonSerialize(), $deviceTypes),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device-type/save')]
    public function save(): DataResponse {
        $data = $this->deviceTypeService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceTypeService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $manufacturerId = (int)$this->request->getParam('manufacturerId');
        
        if($manufacturerId === 0) {
            $manufacturerId = null;
        }

        $deviceType = new DeviceType();
        $deviceType->setName($this->request->getParam('name'));
        $deviceType->setManufacturerId($manufacturerId);
        $deviceType->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->deviceTypeMapper->insert($deviceType);

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
        int $limit = 20,
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->deviceTypeMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->deviceTypeMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-type/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $deviceType = $this->deviceTypeMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'DeviceType not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($deviceType->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device-type/{id}')]
    public function update(int $id): DataResponse {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $deviceType = $this->deviceTypeMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'DeviceType not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceTypeService->getDataFromRequest($this->request->getParams(), $this->expectedFields);

        $result = $this->deviceTypeService->validateData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $manufacturerId = (int)$this->request->getParam('manufacturerId');
        
        if($manufacturerId === 0) {
            $manufacturerId = null;
        }

        // Felder aktualisieren
        $deviceType->setName($this->request->getParam('name'));
        $deviceType->setManufacturerId($manufacturerId);
        $deviceType->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->deviceTypeMapper->update($deviceType);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
