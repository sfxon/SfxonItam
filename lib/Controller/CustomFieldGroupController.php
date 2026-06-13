<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\SfxonItam\AppInfo\Application;
use OCA\SfxonItam\Db\CustomFieldGroupMapper;

/**
 * @psalm-suppress UnusedClass
 */
class CustomFieldGroupController extends Controller {
    private array $expectedFields = ['name', 'entityName', 'comment'];

    public function __construct(
        string $appName,
        IRequest $request,
        private CustomFieldGroupMapper $customFieldGroupMapper,)
    {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field-group/')]
    public function index(): TemplateResponse
    {
        return new TemplateResponse(
            Application::APP_ID,
            'custom-field-group/list',
        );
    }

    #[NoCSRFRequired]
    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/custom-field-group/search')]
    public function search(
        string $orderBy = 'name',
        string $direction = 'ASC',
        int $page = 1,
        int $limit = 20,
    ): JSONResponse
    {
        $offset = ($page - 1) * $limit;
        $filters = $this->request->getParam('filters');
        $include = $this->request->getParam('include');
        $result = $this->customFieldGroupMapper->searchPaged($orderBy, $direction, $limit, $offset, $filters, $include);
        $total   = $this->customFieldGroupMapper->countAll($filters);

        return new JSONResponse([
            'result' => $result,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }
}
