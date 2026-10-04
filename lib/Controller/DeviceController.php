<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Definition\DeviceDefinition;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\DeviceService;
use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Db\Entity;
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
class DeviceController extends AbstractItamController
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
        private DeleteGuardService $deleteGuardService)
    {
        parent::__construct(
            $appName,
            $request,
            $definition,
            $deleteGuardService,
            $deviceMapper,
            $entityRegistry,
            $initialState,
            $listViewSettingsService,
            $customFieldService,
	    $deviceService);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device/{id}')]
    public function delete(int $id): JsonResponse
    {
        return $this->doDelete($id);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device/detail')]
    public function deviceDetail(): TemplateResponse
    {
        return $this->doDetail();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse
    {
        return $this->doIndex();
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
        return $this->doList($orderBy, $direction, $page, $limit, $filters);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/save')]
    public function save(): DataResponse
    {
        return $this->doUpsert();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device/{id}')]
    public function show(int $id): JSONResponse
    {
        return $this->doShow($id);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device/{id}')]
    public function update(int $id): DataResponse
    {
        return $this->doUpsert($id);
    }

    private function sanitizeForeignKey($foreignKeyValue)
    {
        $foreignKeyValue = intval($foreignKeyValue);

        return ($foreignKeyValue === 0) ? null : $foreignKeyValue;
    }

    protected function setDataFromRequest(Entity $entityObject): Entity
    {
        $entityObject->setAssetNumber($this->request->getParam('assetNumber'));

        $deviceStatusId = $this->sanitizeForeignKey($this->request->getParam('deviceStatusId') ?? '');
        $entityObject->setDeviceStatusId($deviceStatusId);

        $deviceTypeId = $this->sanitizeForeignKey($this->request->getParam('deviceTypeId') ?? '');
        $entityObject->setDeviceTypeId($deviceTypeId);

        $entityObject->setDescription($this->request->getParam('description'));

        $imageFileId = $this->sanitizeForeignKey($this->request->getParam('imageFileId') ?? '');
        $entityObject->setImageFileId($imageFileId);

        $entityObject->setInvoiceNumber($this->request->getParam('invoiceNumber'));

        $itamUserId = $this->sanitizeForeignKey($this->request->getParam('itamUserId') ?? '');
        $entityObject->setItamUserId($itamUserId);

        $merchantId = $this->sanitizeForeignKey($this->request->getParam('merchantId') ?? '');
        $entityObject->setMerchantId($merchantId);

        $entityObject->setName($this->request->getParam('name'));

        $positionId = $this->sanitizeForeignKey($this->request->getParam('positionId') ?? '');
        $entityObject->setPositionId($positionId);
        
        $purchaseDateRaw = $this->request->getParam('purchaseDate');
        $entityObject->setPurchaseDate($purchaseDateRaw);

        $quantity = $this->request->getParam('quantity');
        $quantity = is_numeric($quantity) ? (float)$quantity : null;
        $entityObject->setQuantity($quantity);

        $quantityUnitId = $this->sanitizeForeignKey($this->request->getParam('quantityUnitId') ?? '');
        $entityObject->setQuantityUnitId($quantityUnitId);

        $entityObject->setSerialNumber($this->request->getParam('serialNumber'));

        $entityObject->setSerialNumber2($this->request->getParam('serialNumber2'));

        return $entityObject;
    }
}
