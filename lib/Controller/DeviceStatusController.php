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
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceStatusMapper;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeviceStatusService;

/**
 * @psalm-suppress UnusedClass
 */
class DeviceStatusController extends Controller
{
    private array $expectedFields = ['name', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private DeviceStatusMapper $deviceStatusMapper,
        private readonly DeviceStatusService $deviceStatusService,
        private CustomFieldService $customFieldService,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/device-status/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('device_status_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this status.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        try {
            $deviceStatus = $this->deviceStatusMapper->findById($id)['mainData'];
            $this->deviceStatusMapper->delete($deviceStatus);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-status/detail')]
    public function deviceStatusDetail(): TemplateResponse
    {
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_status');

        return new TemplateResponse(
            Application::APP_ID,
            'device-status/editor',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-status/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'device-status/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-status/list')]
    public function list(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $data = $this->deviceStatusMapper->findAllPaged($orderBy, $direction, $limit, $offset);
        $total   = $this->deviceStatusMapper->countAll();

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize(/* $customFields */), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'deviceStatis' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[\Deprecated(message: "Will be removed.", since: "1.9")]
    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-status/listall')]
    public function listall(): JSONResponse
    {
        $deviceStatis = $this->deviceStatusMapper->findAll();

        return new JSONResponse([
            'deviceStatis' => array_map(fn($d) => $d->jsonSerialize(), $deviceStatis),
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device-status/save')]
    public function save(): DataResponse
    {
        $data = $this->deviceStatusService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceStatusService->validateData($data);
        $deviceStatus = new DeviceStatus();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_status');
        $deviceStatus = $this->setDeviceStatusDataFromRequest($deviceStatus);
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

        $saved = $this->deviceStatusMapper->insert($deviceStatus);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_device_status', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/device-status/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $result = $this->deviceStatusMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters);
        $total   = $this->deviceStatusMapper->countAll($filters);

        return new JSONResponse([
            'mainData' => array_map(fn($d) => $d->jsonSerialize(), $result),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/device-status/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $data = $this->deviceStatusMapper->findById($id);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_status');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];
        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/device-status/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entry was not found.
        try {
            $deviceStatus = $this->deviceStatusMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'Device not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->deviceStatusService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->deviceStatusService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device_status');
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

        $deviceStatus = $this->setDeviceStatusDataFromRequest($deviceStatus);
        $updated = $this->deviceStatusMapper->update($deviceStatus);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_device_status', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $updated->getId(),
        ]);
    }

    private function setDeviceStatusDataFromRequest($deviceStatus)
    {
        $deviceStatus->setName($this->request->getParam('name'));
        $deviceStatus->setComment($this->request->getParam('comment') ?? '');

        return $deviceStatus;
    }
}
