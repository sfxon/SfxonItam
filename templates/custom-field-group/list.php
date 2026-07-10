<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldGroupList');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldGroupList');

?>

<div
    id="sfxonitam"
    data-custom-field-groups="<?= htmlspecialchars(json_encode($_['customFieldGroups']), ENT_QUOTES, 'UTF-8') ?>"
></div>
