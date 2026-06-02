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
use OCA\SfxonItam\Db\DeviceTypeMapper;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\ManufacturerMapper;
use OCA\SfxonItam\Service\ManufacturerService;

/**
 * @psalm-suppress UnusedClass
 */
class ManufacturerController extends Controller {
    private array $expectedFields = ['name', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceTypeMapper $deviceTypeMapper,
        private ManufacturerMapper $manufacturerMapper,
        private readonly ManufacturerService $manufacturerService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/manufacturer/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceTypeMapper->isEntityValueInUse('manufacturer_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still deviceTypes assigned to this manufacturer.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $manufacturer = $this->manufacturerMapper->findById($id);
            $this->manufacturerMapper->delete($manufacturer);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/manufacturer/detail')]
    public function manufacturerDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'manufacturer/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/manufacturer/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'manufacturer/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/manufacturer/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $manufacturers = $this->manufacturerMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->manufacturerMapper->countAll();

        return new JSONResponse([
            'manufacturers' => array_map(fn($d) => $d->jsonSerialize(), $manufacturers),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/manufacturer/listall')]
    public function listall(): JSONResponse
    {
        $manufacturers = $this->manufacturerMapper->findAll();

        return new JSONResponse([
            'manufacturers' => array_map(fn($d) => $d->jsonSerialize(), $manufacturers),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/manufacturer/save')]
    public function save(): DataResponse
    {
        $data = $this->manufacturerService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->manufacturerService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $manufacturer = new Manufacturer();
        $manufacturer->setName($this->request->getParam('name'));
        $manufacturer->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->manufacturerMapper->insert($manufacturer);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/manufacturer/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $manufacturer = $this->manufacturerMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Manufacturer not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($manufacturer->jsonSerialize());
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/manufacturer/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->manufacturerMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->manufacturerMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/manufacturer/{id}')]
    public function update(int $id): DataResponse
    {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $manufacturer = $this->manufacturerMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Manufacturer not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->manufacturerService->getDataFromRequest($this->request->getParams(), $this->expectedFields);

        $result = $this->manufacturerService->validateData($data, $id);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $manufacturer->setName($this->request->getParam('name'));
        $manufacturer->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->manufacturerMapper->update($manufacturer);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
