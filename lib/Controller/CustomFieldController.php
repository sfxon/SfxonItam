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
        'options',
        'editable',
        'validation',
        'comment'
    ];

    private array $expectedUpdateFields = [
        'name',
        'position',
        'options',
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
    public function delete(int $id): JSONResponse
    {
        try {
            $this->customFieldService->deleteCustomField($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Custom field not found'],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Unexpected error: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        return new JSONResponse(['status' => 'ok']);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/detail')]
    public function detail(): TemplateResponse
    {
        $customFieldId = (int)$this->request->getParam('customFieldId');
        $customFieldGroupId = 0;

        if($customFieldId === 0) {
            $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        } else {
            $customField = $this->customFieldMapper->findById(intval($customFieldId));
            $customFieldGroupId = $customField->getCustomFieldGroupId();
        }

        $customFieldGroup = $this->customFieldGroupMapper->findById(intval($customFieldGroupId));

        if ($customFieldGroup === null) {
            throw new \Exception('Custom field group not found.');
        }

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field/editor',
            ['customFieldGroupId' => $customFieldGroupId, 'customFieldGroup' => $customFieldGroup]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/')]
    public function index(): TemplateResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $customFieldGroup = $this->customFieldGroupMapper->findById(intval($customFieldGroupId));

        if ($customFieldGroup === null) {
            throw new \Exception('Custom field group not found.');
        }

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field/list',
            ['customFieldGroupId' => $customFieldGroupId, 'customFieldGroup' => $customFieldGroup],
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

        try {
            $customField = $this->customFieldService->createCustomField($data);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse([
                'status'  => 'error',
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new DataResponse([
            'status' => 'ok',
            'id'     => $customField->getId(),
        ]);
    }

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

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/custom-field/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $customField = $this->customFieldMapper->findById($id);
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->customFieldService->getDataFromRequest($this->request->getParams(), $this->expectedUpdateFields);

        $result = $this->customFieldService->validateUpdateData($data, $id);

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Update fields.
        $customField->setComment($this->request->getParam('comment') ?? '');
        $customField->setEditable(
            filter_var($this->request->getParam('editable'), FILTER_VALIDATE_BOOLEAN)
        );
        $customField->setPosition((int)$this->request->getParam('position'));
        $customField->setName($this->request->getParam('name'));
        $customField->setValidation(json_encode($this->request->getParam('validation')));
        $updated = $this->customFieldMapper->update($customField);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
}
