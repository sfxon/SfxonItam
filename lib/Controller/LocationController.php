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
// use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\LocationMapper;
use OCA\SfxonItam\Service\LocationService;

/**
 * @psalm-suppress UnusedClass
 */
class LocationController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        // private DeviceMapper $deviceMapper,
        private LocationMapper $locationMapper,
        private readonly LocationService $locationService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/location/{id}')]
    public function delete(int $id): JsonResponse {
        // Only allow delete, if the deviceStatus is still used by another entity.
        /*
        $hasEntries = $this->deviceMapper->isEntityValueInUse('device_status_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this status.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }
        */

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $location = $this->locationMapper->findById($id);
            $this->locationMapper->delete($location);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/detail')]
    public function locationDetail(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'location/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/')]
    public function index(): TemplateResponse {
        return new TemplateResponse(
            Application::APP_ID,
            'location/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20
    ): JSONResponse {
        $offset = ($page - 1) * $limit;
        $locations = $this->locationMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->locationMapper->countAll();

        return new JSONResponse([
            'locations' => array_map(fn($d) => $d->jsonSerialize(), $locations),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/listall')]
    public function listall(): JSONResponse {
        $locations = $this->locationMapper->findAll();

        return new JSONResponse([
            'locations' => array_map(fn($d) => $d->jsonSerialize(), $locations),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/save')]
    public function save(): DataResponse {
        $expectedFields = ['name', 'comment'];
        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $expectedFields);
        $result = $this->locationService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $location = new Location();
        $location->setName($this->request->getParam('name'));
        $location->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->locationMapper->insert($location);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $location = $this->locationMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Location not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($location->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/location/{id}')]
    public function update(int $id): DataResponse {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $location = $this->locationMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Location not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $expectedFields = ['name', 'comment'];

        $data = $this->locationService->getDataFromRequest($this->request->getParams(), $expectedFields);

        $result = $this->locationService->validateData($data);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $location->setName($this->request->getParam('name'));
        $location->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->locationMapper->update($location);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
