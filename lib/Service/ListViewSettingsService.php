<?php declare(strict_types=1);

namespace OCA\SfxonItam\Service;

use OCA\SfxonItam\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\IUserSession;

class ListViewSettingsService
{
    public function __construct(
        private IUserConfig $userConfig,
        private IUserSession $userSession,)
    {
    }

    public function getColumnOrder(string $listId): ?array
    {
        $userId = $this->userSession->getUser()?->getUID();

        if ($userId === null) {
            return null;
        }

        if (!$this->userConfig->hasKey($userId, Application::APP_ID, $this->columnOrderKey($listId), lazy: true)) {
            return null;
        }

        return $this->userConfig->getValueArray(
            $userId,
            Application::APP_ID,
            $this->columnOrderKey($listId),
            default: [],
            lazy: true,
        );
    }

    public function saveColumnOrder(string $listId, array $columns): void
    {
        $userId = $this->userSession->getUser()?->getUID();

        if ($userId === null) {
            return;
        }

        $this->userConfig->setValueArray(
            $userId,
            Application::APP_ID,
            $this->columnOrderKey($listId),
            $columns,
            lazy: true,
        );
    }

    /**
     * @return array{filterSidebarOpen: bool, navigationOpen: bool}
     */
    public function getUiState(string $viewId): array
    {
        $userId = $this->userSession->getUser()?->getUID();

        if ($userId === null) {
            return $this->defaultUiState();
        }

        $state = $this->userConfig->getValueArray(
            $userId,
            Application::APP_ID,
            $this->uiStateKey($viewId),
            default: [],
            lazy: true,
        );

        return array_merge($this->defaultUiState(), $state);
    }

    /**
     * @param array{filterSidebarOpen?: mixed, navigationOpen?: mixed} $state
     */
    public function saveUiState(string $viewId, array $state): void
    {
        $userId = $this->userSession->getUser()?->getUID();

        if ($userId === null) {
            return;
        }

        $sanitized = [
            'filterSidebarOpen' => (bool)($state['filterSidebarOpen'] ?? true),
            'navigationOpen' => (bool)($state['navigationOpen'] ?? true),
        ];

        $this->userConfig->setValueArray(
            $userId,
            Application::APP_ID,
            $this->uiStateKey($viewId),
            $sanitized,
            lazy: true,
        );
    }

    private function defaultUiState(): array
    {
        return [
            'filterSidebarOpen' => true,
            'navigationOpen' => true,
        ];
    }

    private function columnOrderKey(string $listId): string
    {
        return 'listview_columns_' . $listId;
    }

    private function uiStateKey(string $viewId): string
    {
        return 'listview_ui_state_' . $viewId;
    }
}