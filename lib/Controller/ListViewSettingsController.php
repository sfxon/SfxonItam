<?php declare(strict_types=1);

namespace OCA\SfxonItam\Controller;

use OCA\SfxonItam\Service\ListViewSettingsService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class ListViewSettingsController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ListViewSettingsService $listViewSettingsService
    ) {
        parent::__construct($appName, $request);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/listview-settings/{listId}')]
    public function get(string $listId): DataResponse {
        return new DataResponse(['columns' => $this->listViewSettingsService->getColumnOrder($listId)]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/listview-settings/{listId}')]
    public function update(string $listId, array $columns): DataResponse {
        $this->listViewSettingsService->saveColumnOrder($listId, $columns);
        return new DataResponse(['success' => true]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'GET', url: '/listview-settings/{viewId}/ui-state')]
    public function getUiState(string $viewId): DataResponse {
        return new DataResponse(['uiState' => $this->listViewSettingsService->getUiState($viewId)]);
    }

    #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
    #[FrontpageRoute(verb: 'PUT', url: '/listview-settings/{viewId}/ui-state')]
    public function updateUiState(string $viewId, array $uiState): DataResponse {
        $this->listViewSettingsService->saveUiState($viewId, $uiState);
        return new DataResponse(['success' => true]);
    }
}