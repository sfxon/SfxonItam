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
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Db\QuantityUnitMapper;
use OCA\SfxonItam\Service\QuantityUnitService;

/**
 * @psalm-suppress UnusedClass
 */
class QuantityUnitController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private QuantityUnitMapper $quantityUnitMapper,
        private readonly QuantityUnitService $quantityUnitService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/quantity-unit/{id}')]
    public function delete(int $id): JsonResponse {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('quantity_unit_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this quantityUnit.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $quantityUnit = $this->quantityUnitMapper->findById($id);
            $this->quantityUnitMapper->delete($quantityUnit);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/detail')]
    public function quantityUnitDetail(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'quantity-unit/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/')]
    public function index(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'quantity-unit/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $quantityUnits = $this->quantityUnitMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->quantityUnitMapper->countAll();

        return new JSONResponse([
            'quantityUnits' => array_map(fn($d) => $d->jsonSerialize(), $quantityUnits),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/listall')]
    public function listall(): JSONResponse {
        $quantityUnits = $this->quantityUnitMapper->findAll();

        return new JSONResponse([
            'quantityUnits' => array_map(fn($d) => $d->jsonSerialize(), $quantityUnits),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/quantity-unit/save')]
    public function save(): DataResponse {
        $expectedFields = ['name', 'comment'];
        $data = $this->quantityUnitService->getDataFromRequest($this->request->getParams(), $expectedFields);
        $result = $this->quantityUnitService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $quantityUnit = new QuantityUnit();
        $quantityUnit->setName($this->request->getParam('name'));
        $quantityUnit->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->quantityUnitMapper->insert($quantityUnit);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $quantityUnit = $this->quantityUnitMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'QuantityUnit not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($quantityUnit->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/quantity-unit/{id}')]
    public function update(int $id): DataResponse {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $quantityUnit = $this->quantityUnitMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'QuantityUnit not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $expectedFields = ['name', 'comment'];

        $data = $this->quantityUnitService->getDataFromRequest($this->request->getParams(), $expectedFields);

        $result = $this->quantityUnitService->validateData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $quantityUnit->setName($this->request->getParam('name'));
        $quantityUnit->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->quantityUnitMapper->update($quantityUnit);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
