<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\Db\ItamUserMapper;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Definition\ItamUserDefinition;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\ItamUserService;
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
class ItamUserController extends AbstractItamController
{
    public function __construct(
        string $appName,
        IRequest $request,
        private ItamUserMapper $itamUserMapper,
        private readonly ItamUserService $itamUserService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,
        private readonly ItamUserDefinition $definition,
        private EntityRegistry $entityRegistry,
        private DeleteGuardService $deleteGuardService,)
    {
        parent::__construct(
            $appName,
            $request,
            $definition,
            $deleteGuardService,
            $itamUserMapper,
            $entityRegistry,
            $initialState,
            $listViewSettingsService,
            $customFieldService,
            $itamUserService);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/itam-user/{id}')]
    public function delete(int $id): JSONResponse
    {
        return $this->doDelete($id);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/detail')]
    public function itamUserDetail(): TemplateResponse
    {
        return $this->doDetail();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/')]
    public function index(): TemplateResponse
    {
        return $this->doIndex();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/itam-user/list')]
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
    #[FrontpageRoute(verb: 'POST', url: '/itam-user/save')]
    public function save(): DataResponse
    {
        return $this->doUpsert();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/itam-user/{id}')]
    public function show(int $id): JSONResponse
    {
        return $this->doShow($id);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/itam-user/{id}')]
    public function update(int $id): DataResponse
    {
        return $this->doUpsert($id);
    }
}
