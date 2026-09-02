<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-merchantEditor');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-merchantEditor');

?>

<div
    id="sfxonitammerchanteditor"
    data-custom-fields="<?= htmlspecialchars(json_encode($_['customFields']), ENT_QUOTES, 'UTF-8') ?>"
></div>
