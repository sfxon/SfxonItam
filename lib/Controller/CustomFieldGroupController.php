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
        $customFieldGroups = $this->customFieldGroupMapper->searchPaged(
            'name',
            'ASC',
            100,
            0,
            [],
            []
        );

        return new TemplateResponse(
            Application::APP_ID,
            'custom-field-group/list',
            ['customFieldGroups' => $customFieldGroups]
        );
    }
}
