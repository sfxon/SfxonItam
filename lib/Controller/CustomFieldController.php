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
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;
use OCA\SfxonItam\Db\CustomFieldMapper;
use OCA\SfxonItam\ForeignKey\ForeignKeyRegistry;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\ListViewSettingsService;

/**
 * @psalm-suppress UnusedClass
 */
class CustomFieldController extends Controller {
    private const LIST_ID = 'custom-field-list';
    private const ALLOWED_LIMITS = [10, 25, 50, 100, 500, 1000];
    private const DEFAULT_LIMIT = 25;

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
        private readonly CustomFieldService $customFieldService,
        private readonly IInitialState $initialState,
        private readonly ListViewSettingsService $listViewSettingsService,)
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
            return $this->jsonError('Custom field not found', Http::STATUS_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->jsonError('Unexpected error: ' . $e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse(['status' => 'ok']);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/detail')]
    public function detail(): TemplateResponse
    {
        $customFieldId = (int)$this->request->getParam('customFieldId');

        $customFieldGroupId = $customFieldId === 0
            ? (int)$this->request->getParam('customFieldGroupId')
            : (int)$this->customFieldMapper->findById($customFieldId)->getCustomFieldGroupId();

        $customFieldGroup = $this->requireGroup($customFieldGroupId);
        $foreignKeyTargets = [];

        foreach (ForeignKeyRegistry::getTargets() as $key => $target) {
            $foreignKeyTargets[] = [
                'id' => $key,
                'label' => $target['label'],
                'labelFields' => $target['labelFields'],
            ];
        }

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field/editor',
            [
                'customFieldGroupId' => $customFieldGroupId,
                'customFieldGroup' => $customFieldGroup,
                'foreignKeyTargets' => $foreignKeyTargets,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/')]
    public function index(): TemplateResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $customFieldGroup = $this->requireGroup($customFieldGroupId);

        $this->initialState->provideInitialState(
            'listViewColumnOrder-' . self::LIST_ID,
            $this->listViewSettingsService->getColumnOrder(self::LIST_ID)
        );
        $this->initialState->provideInitialState(
            'listViewUiState-' . self::LIST_ID,
            $this->listViewSettingsService->getUiState(self::LIST_ID)
        );

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
        int $limit = self::DEFAULT_LIMIT,
        ?array $filters = null): JSONResponse
    {
        $customFieldGroupId = (int)$this->request->getParam('customFieldGroupId');
        $this->requireGroup($customFieldGroupId);

        if (!in_array($limit, self::ALLOWED_LIMITS, true)) {
            $limit = self::DEFAULT_LIMIT;
        }

        $offset = (max($page, 1) - 1) * $limit;
        $result = $this->customFieldMapper->searchPaged($customFieldGroupId, $orderBy, $direction, $limit, $offset, $filters);
        $total = $this->customFieldMapper->countAll($customFieldGroupId, $filters);

        return new JSONResponse([
            'result' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/custom-field/save')]
    public function save(): DataResponse
    {
        $data = $this->customFieldService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->customFieldService->validateData($data);

        if ($result['valid'] === false) {
            return $this->validationError($result['errors']);
        }

        try {
            $customField = $this->customFieldService->createCustomField($data);
        } catch (\InvalidArgumentException $e) {
            return $this->dataError($e->getMessage(), Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->dataError('Unexpected error: ' . $e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new DataResponse([
            'status' => 'ok',
            'id'     => $customField->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $customField = $this->customFieldMapper->findById($id);
        } catch (DoesNotExistException) {
            return $this->jsonError('Custom field not found', Http::STATUS_NOT_FOUND);
        }

        return new JSONResponse($customField->jsonSerialize());
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/custom-field/{id}')]
    public function update(int $id): DataResponse
    {
        try {
            $customField = $this->customFieldMapper->findById($id);
        } catch (DoesNotExistException) {
            return $this->dataError('Custom field not found', Http::STATUS_NOT_FOUND);
        }

        $data = $this->customFieldService->getDataFromRequest($this->request->getParams(), $this->expectedUpdateFields);
        $result = $this->customFieldService->validateUpdateData($data, $id);

        if ($result['valid'] === false) {
            return $this->validationError($result['errors']);
        }

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
            'id' => $updated->getId(),
        ]);
    }

    /**
     * @return \OCP\AppFramework\Db\Entity
     */
    private function requireGroup(int $customFieldGroupId)
    {
        $customFieldGroup = $this->customFieldGroupMapper->findById($customFieldGroupId);

        if ($customFieldGroup === null) {
            throw new \Exception('Custom field group not found.');
        }

        return $customFieldGroup;
    }

    private function jsonError(string $message, int $status): JSONResponse
    {
        return new JSONResponse(['status' => 'error', 'message' => $message], $status);
    }

    private function dataError(string $message, int $status): DataResponse
    {
        return new DataResponse(['status' => 'error', 'message' => $message], $status);
    }

    private function validationError(array $errors): DataResponse
    {
        return new DataResponse(['status' => 'error', 'errors' => $errors], Http::STATUS_UNPROCESSABLE_ENTITY);
    }
}
