<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\Device;
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Definition\DeviceDefinition;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\DeviceService;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private readonly DeviceService $deviceService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,
        private readonly DeviceDefinition $definition,
        private EntityRegistry $entityRegistry,
        private DeleteGuardService $deleteGuardService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device/{id}')]
    public function delete(int $id): JsonResponse
    {
        $violation = $this->deleteGuardService->findViolation($this->definition, $id);

        if($violation !== null) {
            return new JSONResponse([
                'status' => 'error',
                'errors' => [$violation]
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        try {
            $device = $this->deviceMapper->findById($id);
            $this->deviceMapper->delete($device['mainData']);
        } catch(DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device/detail')]
    public function deviceDetail(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            $this->definition->templateDir() . '/editor',
            $this->getTemplateParameters()
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse
    {
        $listId = $this->definition->listId();

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
            $this->definition->templateDir() . '/list',
            $this->getTemplateParameters()
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device/list')]
    public function list(
        ?string $orderBy = null,
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 25,
        ?array $filters = null,): JSONResponse
    {
        $orderBy ??= $this->definition->defaultOrderBy;

        if($limit != 10 && $limit != 25 && $limit != 50 && $limit != 100 && $limit != 500 && $limit != 1000) {
            $limit = 25;
        }

        $offset = ($page - 1) * $limit;
        $data = $this->deviceMapper->findAllPaged(
            $orderBy,
            $direction,
            $limit,
            $offset,
            $filters,
            $this->definition->listIncludes
        );
        $total   = $this->deviceMapper->countAll($filters);
        $customFields = $this->getCustomFields();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize($customFields), $data['mainData']);

        return new JSONResponse([
            'devices' => $data,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/save')]
    public function save(): DataResponse
    {
        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $this->definition->expectedFields);
        $result = $this->deviceService->validateData($data);
        $device = new Device();
        $customFields = $this->getCustomFields();
        $device = $this->setDeviceDataFromRequest($device);
        $customFieldData = $this->customFieldService->getCustomFieldDataFromRequest($customFields, $this->request->getParams());
        $customFieldErrors = $this->customFieldService->validateCustomFieldData($customFields, $customFieldData);

        if(count($customFieldErrors) > 0) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $customFieldErrors);
        }

        if($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        $saved = $this->deviceMapper->insert($device);
        $this->customFieldService->updateCustomFieldsForEntity($this->definition->customFieldGroup, $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');
            $data = $this->deviceMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data['mainData'] = $data['mainData']->jsonSerialize($this->getCustomFields());

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $device = $this->deviceMapper->findById($id)['mainData'];
        } catch (DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => $this->definition->label . ' not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceService->getDataFromRequest($this->request->getParams(), $this->definition->expectedFields);
        $result = $this->deviceService->validateData($data, $id);
        $customFields = $this->getCustomFields();
        $customFieldData = $this->customFieldService->getCustomFieldDataFromRequest($customFields, $this->request->getParams());
        $customFieldErrors = $this->customFieldService->validateCustomFieldData($customFields, $customFieldData);

        if(count($customFieldErrors) > 0) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $customFieldErrors);
        }

        if ($result['valid'] === false) {
            return new DataResponse([
                'status' => 'error',
                'errors' => $result['errors'],
            ], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $device = $this->setDeviceDataFromRequest($device);
        $updated = $this->deviceMapper->update($device);
        $this->customFieldService->updateCustomFieldsForEntity($this->definition->customFieldGroup, $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function getCustomFields()
    {
        return $this->customFieldService->getCustomFieldsDefinitionByGroup($this->definition->customFieldGroup);
    }

    private function getTemplateParameters(): array
    {
        return [
            'entityDefinitions' => $this->entityRegistry->fieldDefinitions(),
            'customFields' => $this->getCustomFields(),
        ];
    }

    private function sanitizeForeignKey($foreignKeyValue)
    {
        $foreignKeyValue = intval($foreignKeyValue);

        return ($foreignKeyValue === 0) ? null : $foreignKeyValue;
    }

    private function setDeviceDataFromRequest($device)
    {
        $device->setAssetNumber($this->request->getParam('assetNumber'));

        $deviceStatusId = $this->sanitizeForeignKey($this->request->getParam('deviceStatusId') ?? '');
        $device->setDeviceStatusId($deviceStatusId);

        $deviceTypeId = $this->sanitizeForeignKey($this->request->getParam('deviceTypeId') ?? '');
        $device->setDeviceTypeId($deviceTypeId);

        $device->setDescription($this->request->getParam('description'));

        $imageFileId = $this->sanitizeForeignKey($this->request->getParam('imageFileId') ?? '');
        $device->setImageFileId($imageFileId);

        $device->setInvoiceNumber($this->request->getParam('invoiceNumber'));

        $itamUserId = $this->sanitizeForeignKey($this->request->getParam('itamUserId') ?? '');
        $device->setItamUserId($itamUserId);

        $merchantId = $this->sanitizeForeignKey($this->request->getParam('merchantId') ?? '');
        $device->setMerchantId($merchantId);

        $device->setName($this->request->getParam('name'));

        $positionId = $this->sanitizeForeignKey($this->request->getParam('positionId') ?? '');
        $device->setPositionId($positionId);
        
        $purchaseDateRaw = $this->request->getParam('purchaseDate');
        $device->setPurchaseDate($purchaseDateRaw);

        $quantity = $this->request->getParam('quantity');
        $quantity = is_numeric($quantity) ? (float)$quantity : null;
        $device->setQuantity($quantity);

        $quantityUnitId = $this->sanitizeForeignKey($this->request->getParam('quantityUnitId') ?? '');
        $device->setQuantityUnitId($quantityUnitId);

        $device->setSerialNumber($this->request->getParam('serialNumber'));

        $device->setSerialNumber2($this->request->getParam('serialNumber2'));

        return $device;
    }
}
