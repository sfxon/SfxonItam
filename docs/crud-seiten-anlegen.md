# Crud Seiten anlegen

Dieses Dokument beschreibt, wie einem einfachen und einheitlichen Schema gefolgt werden kann, um neue CRUD-Seiten anzulegen.
Mit CRUD-Seiten sind Content-Seiten gemeint, wie sie für immer wieder übliche Listen-Ansichten mit Detail-Seiten/Editoren verwendet werden,
also solche Seiten, die dazu dienen, Daten zu verwalten.

Dieses Grundgerüst kann und sollte aber auch gern für komplexere Seiten verwendet werden.

- Datenbankschichten beschreiben (Migration und Entity-Layer)

- Backend beschreiben (PHP):
    - Controller
    - Services
    - Besonderheit: Validator
    - Templates

- Frontend beschreiben (VueJs):
    - vite.config.ts (Konfiguration)
    - Generelle Struktur der Ordner
    - views mit den Untergeordneten Seiten.
    - components als UI-Bausteine (bspw. Eingaben, oder in unserem Fall Tabelle und Pagination die wiederverwendbar sind).
    - composables (kapselt Vue-Reaktivität, also Zustand + zugehörige Logik + Lifecycle), ist eher ein Verhaltensmuster.

- Aufbau eines VueJs Views.

- Erklärung der Tabellen-Komponente.


---

Dateien, die ich der Reihe nach anlege und verändere:

a) lib/Migration/Version010100Date20260427174000.php (Neu)
b) lib/Db/DeviceStatus.php (Neu)
c) lib/Db/DeviceStatusMapper.php (Neu)
d) lib/Controller/DeviceStatusController.php (Neu)
e) lib/Service/DeviceStatusService.php (Neu)
f) lib/Validator/DeviceStatusValidator.php (Neu)
g) vite.config.ts (Ändern)
h) Ordner anlegen: src/views/DeviceStatusEditor
i) Ordner anlegen: src/views/DeviceStatusList
i) src/views/DeviceStatusEditor/index.ts
j) src/views/DeviceStatusEditor/DeviceStatusEditor.vue
k) src/views/DeviceStatusList/index.ts
l) src/views/DeviceStatusList/DeviceStatusList.vue
m) src/services/DeviceStatusService.ts
n) src/components/SfxonMainNavigation.vue (Ändern)
o) templates/device-status/editor.php
p) templates/device-status/list.php
q) appinfo/info.xml (Versionsnummer anheben wegen Migration)


r) Migration laufen lassen: <br />```shell
# Go to nextcloud root directory.
cd /var/www/html

# Run upgrade.
php -d memory_limit=512M occ upgrade

# Disable Maintenance Mode.
php -d memory_limit=512M occ maintenance:mode --off
```
s) Vue kompilieren:
```bash
# 1. ssh into container or docker exec in container:
docker exec -it itam bash

# 2. Go to app directory.
cd /var/www/html/custom_apps/sfxonitam

# 3. Compile
npm run dev

# 4. Alternatively compile prod environment: npm run prod
```
t) Testen