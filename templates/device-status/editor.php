<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceStatusEditor');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceStatusEditor');

?>

<div
    id="sfxonitamdevicestatuseditor"
    data-custom-fields="<?= htmlspecialchars(json_encode($_['customFields']), ENT_QUOTES, 'UTF-8') ?>"
></div>
