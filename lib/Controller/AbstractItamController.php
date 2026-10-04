<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\EntityMapperAbstract;
use OCA\SfxonItam\Definition\EntityDefinition;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\ItamServiceAbstract;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;

abstract class AbstractItamController extends Controller
{
    protected EntityDefinition $entityDefinition;
    private EntityMapperAbstract $mapper;
    private DeleteGuardService $deleteGuardService;
    private EntityRegistry $entityRegistry;
    private IInitialState $initialState;
    private ListViewSettingsService $listViewSettingsService;
    private CustomFieldService $customFieldService;
    private ItamServiceAbstract $itamService;

    public function __construct(
        string $appName,
        IRequest $request,
        EntityDefinition $definition,
        DeleteGuardService $deleteGuardService,
        EntityMapperAbstract $mapper,
        EntityRegistry $entityRegistry,
        IInitialState $initialState,
        ListViewSettingsService $listViewSettingsService,
        CustomFieldService $customFieldService,
        ItamServiceAbstract $itamService
    ) {
        parent::__construct($appName, $request);
        $this->entityDefinition = $definition;
        $this->entityRegistry = $entityRegistry;
        $this->deleteGuardService = $deleteGuardService;
        $this->mapper = $mapper;
        $this->initialState = $initialState;
        $this->listViewSettingsService = $listViewSettingsService;
        $this->customFieldService = $customFieldService;
        $this->itamService = $itamService;
    }

    protected function doDelete(int $id): JSONResponse
    {
        $violation = $this->deleteGuardService->findViolation($this->entityDefinition, $id);

        if($violation !== null) {
            return new JSONResponse([
                'status' => 'error',
                'errors' => [$violation]
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        try {
            $entityObject = $this->mapper->findById($id);
            $this->mapper->delete($entityObject['mainData']);
        } catch(DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->entityDefinition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    protected function doDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            $this->entityDefinition->templateDir() . '/editor',
            $this->getTemplateParameters()
        );
    }

    protected function doIndex(): TemplateResponse
    {
        $listId = $this->entityDefinition->listId();

        $this->initialState->provideInitialState(
            'listViewColumnOrder-' . $listId,
            $this->listViewSettingsService->getColumnOrder($listId)
        );

        $this->initialState->provideInitialState(
            'listViewUiState-' . $listId,
            $this->listViewSettingsService->getUiState($listId)
        );

        return new TemplateResponse(
            Application::APP_ID,
            $this->entityDefinition->templateDir() . '/list',
            $this->getTemplateParameters()
        );
    }

    public function doList(
        ?string $orderBy = null,
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 25,
        ?array $filters = null,): JSONResponse
    {
        $orderBy ??= $this->entityDefinition->defaultOrderBy;

        if($limit != 10 && $limit != 25 && $limit != 50 && $limit != 100 && $limit != 500 && $limit != 1000) {
            $limit = 25;
        }

        $offset = ($page - 1) * $limit;
        $data = $this->mapper->findAllPaged(
            $orderBy,
            $direction,
            $limit,
            $offset,
            $filters,
            $this->entityDefinition->listIncludes
        );
        $total = $this->mapper->countAll($filters);
        $customFields = $this->getCustomFields();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize($customFields), $data['mainData']);

        return new JSONResponse([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function doShow(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');

            if(!is_array($include)) {
                $include = [];
            }

            if(!isset($include['location'])) {
                $include['location'] = [];
            }

            $data = $this->mapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->entityDefinition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data['mainData'] = $data['mainData']->jsonSerialize($this->getCustomFields());

        return new JSONResponse($data);
    }

    public function doUpsert(?int $id = null): DataResponse
    {
        $isUpdate = $id !== null;

        // Entity laden bzw. neu erzeugen
        if ($isUpdate) {
            try {
                $entityObject = $this->mapper->findById($id)['mainData'];
            } catch (DoesNotExistException) {
                return new DataResponse(
                    ['status' => 'error', 'message' => $this->entityDefinition->label . ' not found'],
                    Http::STATUS_NOT_FOUND
                );
            }
        } else {
            $entityObject = $this->itamService->createNewEntity();
        }

        $params = $this->request->getParams();
        $customFields = $this->getCustomFields();

        // Validierung
        $data = $this->itamService->getDataFromRequest($params, $this->entityDefinition->expectedFields);
        $result = $this->itamService->validateData($data, $id); 
        $customFieldData = $this->customFieldService->getCustomFieldDataFromRequest($customFields, $params);
        $customFieldErrors = $this->customFieldService->validateCustomFieldData($customFields, $customFieldData);

        if (count($customFieldErrors) > 0) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $customFieldErrors);
        }

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        // Daten übernehmen und speichern
        $entityObject = $this->setDataFromRequest($entityObject);
        $saved = $isUpdate
            ? $this->mapper->update($entityObject)
            : $this->mapper->insert($entityObject);

        $this->customFieldService->updateCustomFieldsForEntity(
            $this->entityDefinition->customFieldGroup,
            $saved->getId(),
            $customFieldData
        );

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    private function getCustomFields()
    {
        return $this->customFieldService->getCustomFieldsDefinitionByGroup($this->entityDefinition->customFieldGroup);
    }

    private function getTemplateParameters(): array
    {
        return [
            'entityDefinitions' => $this->entityRegistry->fieldDefinitions(),
            'customFields' => $this->getCustomFields(),
        ];
    }

    protected function setDataFromRequest(Entity $entityObject): Entity
    {
        $fieldDefinitions = $this->getEntityFieldDefinitions();

        foreach ($this->entityDefinition->expectedFields as $field) {
            $fieldDefinition = $fieldDefinitions[$field]
                ?? throw new \LogicException(sprintf(
                    'Field "%s" is expected by %s but missing in %s::getFieldDefinition().',
                    $field,
                    $this->entityDefinition->label,
                    $this->entityDefinition->entityClass
                ));

            $setter = 'set' . ucfirst($fieldDefinition['propertyName'] ?? $field);
            $entityObject->$setter(
                $this->castRequestValue($this->request->getParam($field), $fieldDefinition)
            );
        }

        return $entityObject;
    }

    private function getEntityFieldDefinitions(): array
    {
        /** @var class-string<Entity> $entityClass */
        $entityClass = $this->entityDefinition->entityClass;
        $indexed = [];

        foreach ($entityClass::getFieldDefinition() as $definition) {
            $indexed[$definition['propertyName'] ?? $definition['name']] = $definition;
        }

        return $indexed;
    }

    private function castRequestValue(mixed $raw, array $fieldDefinition): mixed
    {
        if (!empty($fieldDefinition['foreignEntity'])) {
            return $this->normalizeForeignKey($raw);
        }

        $isEmpty = $raw === null || $raw === '';
        $type = strtoupper((string)($fieldDefinition['type'] ?? 'VARCHAR'));

        return match (true) {
            in_array($type, ['INT', 'INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT'], true)
                => is_numeric($raw) ? (int)$raw : null,

            in_array($type, ['DECIMAL', 'NUMERIC', 'FLOAT', 'DOUBLE'], true)
                => is_numeric($raw) ? (float)$raw : null,

            $type === 'TEXT'
                => $isEmpty ? '' : $raw,

            default => $raw,
        };
    }

    private function normalizeForeignKey(mixed $raw): ?int
    {
        if (!is_numeric($raw)) {
            return null;
        }

        $id = (int)$raw;

        return $id > 0 ? $id : null;
    }
}