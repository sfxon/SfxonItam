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
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\MerchantMapper;
use OCA\SfxonItam\Service\MerchantService;

/**
 * @psalm-suppress UnusedClass
 */
class MerchantController extends Controller {
    private array $expectedFields = ['name', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private MerchantMapper $merchantMapper,
        private readonly MerchantService $merchantService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/merchant/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('merchant_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this merchant.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $merchant = $this->merchantMapper->findById($id);
            $this->merchantMapper->delete($merchant);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/merchant/detail')]
    public function merchantDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'merchant/editor',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/merchant/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'merchant/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/merchant/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $merchants = $this->merchantMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->merchantMapper->countAll();

        return new JSONResponse([
            'merchants' => array_map(fn($d) => $d->jsonSerialize(), $merchants),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/merchant/listall')]
    public function listall(): JSONResponse
    {
        $merchants = $this->merchantMapper->findAll();

        return new JSONResponse([
            'merchants' => array_map(fn($d) => $d->jsonSerialize(), $merchants),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/merchant/save')]
    public function save(): DataResponse
    {
        $data = $this->merchantService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->merchantService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $merchant = new Merchant();
        $merchant->setName($this->request->getParam('name'));
        $merchant->setComment($this->request->getParam('comment') ?? '');
        $saved = $this->merchantMapper->insert($merchant);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/merchant/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->merchantMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->merchantMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/merchant/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $merchant = $this->merchantMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Merchant not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($merchant->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/merchant/{id}')]
    public function update(int $id): DataResponse
    {
        // Gerät laden – 404 wenn nicht vorhanden
        try {
            $merchant = $this->merchantMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Merchant not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->merchantService->getDataFromRequest($this->request->getParams(), $this->expectedFields);

        $result = $this->merchantService->validateData($data, $id);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Felder aktualisieren
        $merchant->setName($this->request->getParam('name'));
        $merchant->setComment($this->request->getParam('comment') ?? '');

        $updated = $this->merchantMapper->update($merchant);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
