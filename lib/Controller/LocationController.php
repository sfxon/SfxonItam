<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\Db\LocationMapper;
use OCA\SfxonItam\Definition\EntityRegistry;
use OCA\SfxonItam\Definition\LocationDefinition;
use OCA\SfxonItam\Service\CustomFieldService;
use OCA\SfxonItam\Service\DeleteGuardService;
use OCA\SfxonItam\Service\LocationService;
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
class LocationController extends AbstractItamController
{
    public function __construct(
        string $appName,
        IRequest $request,
        private LocationMapper $locationMapper,
        private readonly LocationService $locationService,
        private CustomFieldService $customFieldService,
        private ListViewSettingsService $listViewSettingsService,
        private IInitialState $initialState,
        private readonly LocationDefinition $definition,
        private EntityRegistry $entityRegistry,
        private DeleteGuardService $deleteGuardService)
    {
        parent::__construct(
            $appName,
            $request,
            $definition,
            $deleteGuardService,
            $locationMapper,
            $entityRegistry,
            $initialState,
            $listViewSettingsService,
            $customFieldService,
            $locationService);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'DELETE', url: '/location/{id}')]
    public function delete(int $id): JsonResponse
    {
        return $this->doDelete($id);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/detail')]
    public function locationDetail(): TemplateResponse
    {
        return $this->doDetail();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/')]
    public function index(): TemplateResponse
    {
        return $this->doIndex();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/location/list')]
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
    #[FrontpageRoute(verb: 'POST', url: '/location/save')]
    public function save(): DataResponse
    {
        return $this->doUpsert();
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'POST', url: '/location/{id}')]
    public function show(int $id): JSONResponse
    {
        return $this->doShow($id);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/location/{id}')]
    public function update(int $id): DataResponse
    {
        return $this->doUpsert($id);
    }
}