<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceEditor');
Util::addStyle(OCA\SfxonItam\AppInfo\Application::APP_ID, OCA\SfxonItam\AppInfo\Application::APP_ID . '-deviceEditor');
Util::addScript(OCA\SfxonItam\AppInfo\Application::APP_ID, 'vendor/qrcode-generator-kazuhiko-arase/qrcode');

?>

<div id="sfxonitamdeviceeditor"></div>
