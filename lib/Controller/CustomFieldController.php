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
use OCA\SfxonItam\Db\CustomField;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\Service\CustomFieldService;

/**
 * @psalm-suppress UnusedClass
 */
class CustomFieldController extends Controller {
    private array $expectedFields = [
        'customFieldGroupId',
        'technicalName',
        'name',
        'type',
        'position',
        //'options',
        'editable',
        'validation',
        'comment'
    ];

    public function __construct(
        string $appName,
        IRequest $request,
        private CustomFieldGroupMapper $customFieldGroupMapper,
        private CustomFieldMapper $customFieldMapper,
        private readonly CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/custom-field/{id}')]
    public function delete(int $id): JsonResponse
    {
        die('delete custom field');

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        
        /*
        try {
            $position = $this->positionMapper->findById($id);
            $this->positionMapper->delete($position);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
        */
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/detail')]
    public function detail(): TemplateResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $existing = $this->customFieldGroupMapper->findById(intval($customFieldGroupId));

        if ($existing === null) {
            throw new \Exception('Custom field group not found.');
        }

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field/editor',
            ['customFieldGroupId' => $customFieldGroupId]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/')]
    public function index(): TemplateResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $existing = $this->customFieldGroupMapper->findById(intval($customFieldGroupId));

        if ($existing === null) {
            throw new \Exception('Custom field group not found.');
        }

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field/list',
            ['customFieldGroupId' => $customFieldGroupId]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20): JSONResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $existing = $this->customFieldGroupMapper->findById(intval($customFieldGroupId));

        if ($existing === null) {
            throw new \Exception('Custom field group not found.');
        }

        $offset = ($page - 1) * $limit;
        $result = $this->customFieldMapper->searchPaged($customFieldGroupId, $orderBy, $direction, $limit, $offset);
        $total   = $this->customFieldMapper->countAll($customFieldGroupId);

        return new JSONResponse([
            'result' => $result,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/custom-field/save')]
    public function save(): DataResponse
    {
        $data = $this->customFieldService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->customFieldService->validateData($data);

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }



        die('test');


        /*

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
        */
    }

    /*
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/custom-field/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,
    ): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $include = $this->request->getParam('include');
        $result = $this->customFieldMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->customFieldMapper->countAll($filters);

        return new JSONResponse([
            'result' => $result,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }
    */

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/{id}')]
    public function show(int $id): JSONResponse {
        try {
            $customField = $this->customFieldMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Custom field not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse($customField->jsonSerialize());
    }
}
