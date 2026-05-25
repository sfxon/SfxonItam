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
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\PositionMapper;
use OCA\SfxonItam\Service\PositionService;

/**
 * @psalm-suppress UnusedClass
 */
class PositionController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private PositionMapper $positionMapper,
        private readonly PositionService $positionService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/position/{id}')]
    public function delete(int $id): JsonResponse {
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
        try {
            $position = $this->positionMapper->findById($id);
            $this->positionMapper->delete($position);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/detail')]
    public function positionDetail(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'position/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/')]
    public function index(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'position/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $positions = $this->positionMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->positionMapper->countAll();

        return new JSONResponse([
            'positions' => array_map(fn($d) => $d->jsonSerialize(), $positions),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/listall')]
    public function listall(): JSONResponse {
        $positions = $this->positionMapper->findAll();

        return new JSONResponse([
            'positions' => array_map(fn($d) => $d->jsonSerialize(), $positions),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/position/save')]
    public function save(): DataResponse {
        $expectedFields = ['name', 'comment'];
        $data = $this->positionService->getDataFromRequest($this->request->getParams(), $expectedFields);
        $result = $this->positionService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $locationId = (int)$this->request->getParam('locationId');
        
        if($locationId === 0) {
            $locationId = null;
        }

        $position = new Position();
        $position->setName($this->request->getParam('name'));
        $position->setLocationId($locationId);
        $position->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->positionMapper->insert($position);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/position/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $include = $this->request->getParam('include');
        $result = $this->positionMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->positionMapper->countAll($filters);

        return new JSONResponse([
            'result' => $result,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/position/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $position = $this->positionMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Position not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($position->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/position/{id}')]
    public function update(int $id): DataResponse {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $position = $this->positionMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Position not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $expectedFields = ['name', 'comment'];

        $data = $this->positionService->getDataFromRequest($this->request->getParams(), $expectedFields);

        $result = $this->positionService->validateData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $locationId = (int)$this->request->getParam('locationId');
        
        if($locationId === 0) {
            $locationId = null;
        }

        // Felder aktualisieren
        $position->setName($this->request->getParam('name'));
        $position->setLocationId($locationId);
        $position->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->positionMapper->update($position);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
