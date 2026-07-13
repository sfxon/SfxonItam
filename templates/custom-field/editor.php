<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldEditor');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-customFieldEditor');

?>

<div
    id="sfxonitamcustomfieldeditor"
    data-custom-field-group-id="<?= htmlspecialchars((string)$_['customFieldGroupId'], ENT_QUOTES, 'UTF-8') ?>"
    data-custom-field-group="<?= htmlspecialchars(json_encode($_['customFieldGroup']), ENT_QUOTES, 'UTF-8') ?>"
></div>
