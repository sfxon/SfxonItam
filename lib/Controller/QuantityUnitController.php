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
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Db\QuantityUnitMapper;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\QuantityUnitService;

/**
 * @psalm-suppress UnusedClass
 */
class QuantityUnitController extends Controller
{
    private array $expectedFields = ['name', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private QuantityUnitMapper $quantityUnitMapper,
        private readonly QuantityUnitService $quantityUnitService,
        private CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/quantity-unit/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('quantity_unit_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this quantityUnit.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $quantityUnit = $this->quantityUnitMapper->findById($id);
            $this->quantityUnitMapper->delete($quantityUnit);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/detail')]
    public function quantityUnitDetail(): TemplateResponse
    {
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_quantity_unit');

        return new TemplateResponse(
            Application::APP_ID,
            'quantity-unit/editor',
            [
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'quantity-unit/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $data = $this->quantityUnitMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->quantityUnitMapper->countAll();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(/* $customFields */), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'quantityUnits' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/listall')]
    public function listall(): JSONResponse
    {
        $quantityUnits = $this->quantityUnitMapper->findAll();

        return new JSONResponse([
            'quantityUnits' => array_map(fn($d) => $d->jsonSerialize(), $quantityUnits),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/quantity-unit/save')]
    public function save(): DataResponse
    {
        $data = $this->quantityUnitService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->quantityUnitService->validateData($data);
        $quantityUnit = new QuantityUnit();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_quantity_unit');
        $quantityUnit = $this->setQuantityUnitDataFromRequest($quantityUnit);
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

        $saved = $this->quantityUnitMapper->insert($quantityUnit);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_quantity_unit', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/quantity-unit/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');

        $data = $this->quantityUnitMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters);
        $total = $this->quantityUnitMapper->countAll($filters);

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(), $data['mainData']);

        return new JSONResponse([
            'mainData' => $data['mainData'],
            'relations' => $data['relations'],
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/quantity-unit/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $data = $this->quantityUnitMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'QuantityUnit not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_quantity_unit');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/quantity-unit/{id}')]
    public function update(int $id): DataResponse
    {
        // Load Device – 404 if not found.
        try {
            $quantityUnit = $this->quantityUnitMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'QuantityUnit not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->quantityUnitService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->quantityUnitService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_quantity_unit');
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

        $quantityUnit = $this->setQuantityUnitDataFromRequest($quantityUnit);
        $updated = $this->quantityUnitMapper->update($quantityUnit);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_quantity_unit', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function setQuantityUnitDataFromRequest($quantityUnit)
    {
        $quantityUnit->setName($this->request->getParam('name'));
        $quantityUnit->setComment($this->request->getParam('comment') ?? '');

        return $quantityUnit;
    }
}