<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceTypeEditor');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceTypeEditor');

?>

<div
    id="sfxonitamdevicetypeeditor"
    data-entity-definitions="<?= htmlspecialchars(json_encode($_['entityDefinitions']), ENT_QUOTES, 'UTF-8') ?>"
    data-custom-fields="<?= htmlspecialchars(json_encode($_['customFields']), ENT_QUOTES, 'UTF-8') ?>"
></div>
