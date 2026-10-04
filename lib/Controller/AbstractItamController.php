<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\EntityMapperAbstract;
use OCA\SfxonItam\Definition\EntityDefinition;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
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
    ) {
        parent::__construct($appName, $request);
        $this->entityDefinition = $definition;
        $this->entityRegistry = $entityRegistry;
        $this->deleteGuardService = $deleteGuardService;
        $this->mapper = $mapper;
        $this->initialState = $initialState;
        $this->listViewSettingsService = $listViewSettingsService;
        $this->customFieldService = $customFieldService;
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
            $position = $this->mapper->findById($id);
            $this->mapper->delete($position['mainData']);
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
}