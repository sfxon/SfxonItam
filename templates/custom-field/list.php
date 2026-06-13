<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldList');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldList');

?>

<div
    id="sfxonitam"
    data-custom-field-group-id="<?= htmlspecialchars((string)$_['customFieldGroupId'], ENT_QUOTES, 'UTF-8') ?>"
></div>
