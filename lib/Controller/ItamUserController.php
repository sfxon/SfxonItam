<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\DeviceMapper;
use OCA\SfxonItam\Db\DeviceStatus;
use OCA\SfxonItam\Db\DeviceType;
use OCA\SfxonItam\Db\ItamUser;
use OCA\SfxonItam\Db\ItamUserMapper;
use OCA\SfxonItam\Db\Location;
use OCA\SfxonItam\Db\Manufacturer;
use OCA\SfxonItam\Db\Merchant;
use OCA\SfxonItam\Db\Position;
use OCA\SfxonItam\Db\QuantityUnit;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\ItamUserService;
use OCA\SfxonItam\Service\ListViewSettingsService;
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


/**
 * @psalm-suppress UnusedClass
 */
class ItamUserController extends Controller
{
    private array $expectedFields = ['firstname', 'lastname', 'email', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private DeviceMapper $deviceMapper,
        private ItamUserMapper $itamUserMapper,
        private readonly ItamUserService $itamUserService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,)
    {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/itam-user/{id}')]
    public function delete(int $id): JsonResponse
    {
        // Only allow delete, if the deviceStatus is still used by another entity.
        $hasEntries = $this->deviceMapper->isEntityValueInUse('itam_user_id', $id);

        if($hasEntries) {
            return new JsonResponse([
                'status' => 'error',
                'errors' => ['Cannot delete. There are still devices assigned to this itamUser.']
            ], Http::STATUS_UNPROCESSABLE_ENTITY); // Returns error 422
        }

        // Put this in a try-catch block, since findById will throw an error,
        // if it does not find an element with the given id.
        // @TODO:
        // Change this. Not showing an error, could lead to a user not seeing, that there was a problem and the data still exists.
        try {
            $itamUser = $this->itamUserMapper->findById($id);
            $this->itamUserMapper->delete($itamUser['mainData']);
        } catch(\Error $error) {
        }

        return new JSONResponse([
            'status' => 'ok',
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/detail')]
    public function itamUserDetail(): TemplateResponse
    {
        $entityDefinitions = [
            'deviceStatus' => DeviceStatus::getFieldDefinition(),
            'deviceType' => DeviceType::getFieldDefinition(),
            'itamUser' => ItamUser::getFieldDefinition(),
            'location' => Location::getFieldDefinition(),
            'manufacturer' => Manufacturer::getFieldDefinition(),
            'merchant' => Merchant::getFieldDefinition(),
            'position' => Position::getFieldDefinition(),
            'quantityUnit' => QuantityUnit::getFieldDefinition(),
        ];
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');

        return new TemplateResponse(
            Application::APP_ID,
            'itam-user/editor',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/')]
    public function index(): TemplateResponse
    {
        $listId = 'itam-user-list';

        $this->initialState->provideInitialState(
            'listViewColumnOrder-' . $listId,
            $this->listViewSettingsService->getColumnOrder($listId)
        );

        $this->initialState->provideInitialState(
            'listViewUiState-' . $listId,
            $this->listViewSettingsService->getUiState($listId)
        );

        $entityDefinitions = [
            'deviceStatus' => DeviceStatus::getFieldDefinition(),
            'deviceType' => DeviceType::getFieldDefinition(),
            'itamUser' => ItamUser::getFieldDefinition(),
            'location' => Location::getFieldDefinition(),
            'manufacturer' => Manufacturer::getFieldDefinition(),
            'merchant' => Merchant::getFieldDefinition(),
            'position' => Position::getFieldDefinition(),
            'quantityUnit' => QuantityUnit::getFieldDefinition(),
        ];

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_device');

        return new TemplateResponse(
            Application::APP_ID,
            'itam-user/list',
            [
                'entityDefinitions' => $entityDefinitions,
                'customFields' => $customFields,
            ]
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/list')]
    public function list(
        string $orderBy = 'email',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 25,
        ?array $filters = null,): JSONResponse
    {
        if($limit != 10 && $limit != 25 && $limit != 50 && $limit != 100 && $limit != 500 && $limit != 1000) {
            $limit = 25;
        }

        $offset = ($page - 1) * $limit;
	$include = $this->getDefaultIncludes();
        $data = $this->itamUserMapper->findAllPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->itamUserMapper->countAll($filters);
	$customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');

        $data['mainData'] = array_map(fn($d) => $d->jsonSerialize($customFields), $data['mainData']);
        $data['relations'] = $data['relations'];

        return new JSONResponse([
            'itamUsers' => $data,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/itam-user/save')]
    public function save(): DataResponse
    {
        $data = $this->itamUserService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->itamUserService->validateData($data);
        $itamUser = new ItamUser();
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');
        $itamUser = $this->setItamUserDataFromRequest($itamUser);
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

        $saved = $this->itamUserMapper->insert($itamUser);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_itam_user', $saved->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id' => $saved->getId(),
        ]);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/{id}')]
    public function show(int $id): JSONResponse
    {
        try {
            $include = $this->request->getParam('include');
            $data = $this->itamUserMapper->findById($id, $include);
        } catch (DoesNotExistException) {
            return new JSONResponse(
                ['status' => 'error', 'message' => 'ItamUser not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');
        $data['mainData'] = $data['mainData']->jsonSerialize($customFields);
        $data['relations'] = $data['relations'];

        return new JSONResponse($data);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/itam-user/{id}')]
    public function update(int $id): DataResponse
    {
        // Return 404 if entity is not found.
        try {
            $itamUser = $this->itamUserMapper->findById($id)['mainData'];
        } catch (\OCP\AppFramework\Db\DoesNotExistException) {
            return new DataResponse(
                ['status' => 'error', 'message' => 'ItamUser not found'],
                Http::STATUS_NOT_FOUND
            );
        }

        $data = $this->itamUserService->getDataFromRequest($this->request->getParams(), $this->expectedFields);
        $result = $this->itamUserService->validateData($data, $id);
        $customFields = $this->customFieldService->getCustomFieldsDefinitionByGroup('sfxon_itam_user');
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

        $itamUser = $this->setItamUserDataFromRequest($itamUser);
        $updated = $this->itamUserMapper->update($itamUser);
        $this->customFieldService->updateCustomFieldsForEntity('sfxon_itam_user', $updated->getId(), $customFieldData);

        return new DataResponse([
            'status' => 'ok',
            'id'     => $updated->getId(),
        ]);
    }
    
    private function getDefaultIncludes(): array {
        return [
            'deviceStatus' => [],
            'deviceType' => [],
            'itamUser' => ['fields' => ['id', 'firstname', 'lastname']],
            'merchant' => [],
            'position' => [
                'fields' => ['id', 'name', 'location_id'],
                'with' => [
                    'location' => [
                        'table' => 'sfxon_location',
                        'localKey' => 'location_id',
                        'fields' => ['id', 'name'],
                    ],
                ],
            ],
            'quantityUnit' => [],
        ];
    }
    
    private function sanitizeForeignKey($foreignKeyValue)
    {
        $foreignKeyValue = intval($foreignKeyValue);

        return ($foreignKeyValue === 0) ? null : $foreignKeyValue;
    }

    private function setItamUserDataFromRequest($itamUser) {
        $itamUser->setFirstname($this->request->getParam('firstname'));
        $itamUser->setLastname($this->request->getParam('lastname'));
        $itamUser->setEmail($this->request->getParam('email'));
        $itamUser->setComment($this->request->getParam('comment'));
        return $itamUser;
    }
}
