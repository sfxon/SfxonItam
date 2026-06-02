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
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\ItamUserMapper;
use OCA\SfxonItam\Service\ItamUserService;

/**
 * @psalm-suppress UnusedClass
 */
class ItamUserController extends Controller
{
    private array $expectedFields = ['firstname', 'lastname', 'email', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private ItamUserMapper $itamUserMapper,
        private readonly ItamUserService $itamUserService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/itam-user/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('itam_user_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this itamUser.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $itamUser = $this->itamUserMapper->findById($id);
            $this->itamUserMapper->delete($itamUser);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/detail')]
    public function itamUserDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'itam-user/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'itam-user/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $itamUsers = $this->itamUserMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->itamUserMapper->countAll();

        return new JSONResponse([
            'itamUsers' => array_map(fn($d) => $d->jsonSerialize(), $itamUsers),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/listall')]
    public function listall(): JSONResponse
    {
        $itamUsers = $this->itamUserMapper->findAll();

        return new JSONResponse([
            'itamUsers' => array_map(fn($d) => $d->jsonSerialize(), $itamUsers),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/itam-user/save')]
    public function save(): DataResponse
    {
        $data = $this->itamUserService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->itamUserService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $itamUser = new ItamUser();
        $itamUser = $this->setDeviceDataFromRequest($itamUser);
        $saved = $this->itamUserMapper->insert($itamUser);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/itam-user/search')]
    public function search(
        string $orderBy = 'firstname',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->itamUserMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->itamUserMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $itamUser = $this->itamUserMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'ItamUser not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($itamUser->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/itam-user/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entity is not found.
        try {
            $itamUser = $this->itamUserMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'ItamUser not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->itamUserService->getDataFromRequest($this->request->getParams(), $this->expectedFields);

        $result = $this->itamUserService->validateData($data, $id);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $itamUser = $this->setDeviceDataFromRequest($itamUser);
        $updated = $this->itamUserMapper->update($itamUser);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }

    private function setDeviceDataFromRequest($itamUser) {
        $itamUser->setFirstname($this->request->getParam('firstname'));
        $itamUser->setLastname($this->request->getParam('lastname'));
        $itamUser->setEmail($this->request->getParam('email'));
        $itamUser->setComment($this->request->getParam('comment'));
        return $itamUser;
    }
}
