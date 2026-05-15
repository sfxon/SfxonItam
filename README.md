# IT Asset Management

ITAM (**IT Asset Management**) ist eine Nextcloud Erweiterung zur **Verwaltung von Hardwaregeräten** und **Verwaltung von Softwarelizenzen**.
Das Programm richtet sich an **KMU** (kleine und mittelständische Unternehmen) sowie Privatpersonen.

* Geräte-Verwaltung
* Lizenz-Verwaltung (Software-Lizenzen)
* Open Source + kostenfreie Nutzung (MIT-Lizenz)
* Release im Nextcloud Appstore zur einfacheren Installation ist geplant.
* All your data now belongs to you.

<img alt="IT-Asset-Management - Device List View" src="https://github.com/user-attachments/assets/7776981c-8e5f-403b-8719-463a43a21843" /><br><br>

Dark Mode:

<img alt="IT-Asset-Management - Device List View in Dark Mode" src="https://github.com/user-attachments/assets/1946c652-8f06-4311-8641-45ce03446bb7" /><br><br>

Editor:

<img alt="IT-Asset-Management - Device Editor" src="https://github.com/user-attachments/assets/63ddb78e-07e3-4dfd-84cb-b56211382cb3" /><br><br>

## Roadmap

* Start an devlog instead of twitch streams - because it might be more of an interest. https://www.youtube.com/watch?v=NWOUna4fSEI

* Device Entität umsetzen:
    - ✅ Übersichts-Seite für die Geräte umsetzen:
        - ✅ Tabelle Element für Element in einzelne wiederverwendbare Module aufteilen.
            - ✅ Tabelle insgesamt in neues Modul auslagern.
            - ✅ Tabellenkopf in eigene Komponente auslagern.
            - ✅ Tabellen-Body in eigene Komponente auslagern.
            - ✅ Tabellenspalte (Zelle) in eigene Komponente auslagern.
        - ✅ Pagination in eigene Komponente auslagern
        - ✅ Alte Elemente in VueJs entfernen, die aktuell noch als Referenz enthalten sind.
        - ✅ Action-Spalte hinzufügen.
    - ✅ Detail-Seite erstellen zum Anlegen und Bearbeiten
        - ✅ Eingaben prüfen
            - ✅ Erste Version der Eingabeprüfung umsetzen.
            - ✅ Frontend-Ausgabe von Fehlern beim Speichern umsetzen.
        - ✅ Ausgaben escapen -> ist nicht notwendig, so lange wir nicht mit v-html binden. Vue escaped die anderen Werte automatisch.
        - ✅ Alle Werte hinzufügen (Entitäten, "normale" Felder)
    - ✅ Controller
    - ✅ View
    - ✅ Speicher Aktion hinzufügen.
    - ✅ Einträge bearbeiten.
        - ✅ Füge Erfolgsmeldung hinzu, die angezeigt wird, wenn das Speichern erfolgreich war.
        - ✅ Es gibt offenbar einen Fehler mit dem Purchase Date. Es wurde weder geladen, noch richtig gespeichert.
    - ✅ Einträge löschen.
    - ✅ Entität in Datenbank erstellen.
    - ✅ Button "Neu" hinzufügen.
    - ✅ Paginierung funktioniert noch nicht.
    - ✅ Spaltensortierung funktioniert außer der des Namens noch nicht.
    - ✅ Vue.app umbenennen in DeviceList.vue.
    - ✅ Code für die nächsten Entitäten besser abstrahieren.
        - ✅ Beim Löschen eines Eintrages keinen nativen Dialog anzeigen, sondern stattdessen ein Overlay. Sonst kann man das umgehen - was kritische Ergebnisse haben kann.
        - ✅ Logik aus der DeviceList auslagern in eine Klasse Device, die dynamisch auf den Daten arbeitet.
        - ✅ Logik aus DeviceEditor ebenfalls auslagern in die Klasse Device. Das sollte die zentrale Klasse dafür werden.+
        - ✅ Denkbar ist jetzt noch eine Klasse DeviceApi oder ähnliches, welche die API-Logik noch etwas herauszieht, damit man schönen sauberen Code hat. Dann sollte es erstmal genug sein.
    - Geräte-Liste durchsuchbar und filterbar machen.
        - Geräteliste filterbar machen.
            - ✅ Eigene Komponente erstellen: SfxonFilterBar
            - Teilkomponenten dafür erstellen:
                - ✅ 1. Filter für Text-Felder / Number-Felder SfxonFilterFieldText
                - ✅ 2. Filter für Datum (von bis, mit Datum-Selector) SfxonFilterFieldDate
                - ✅ 3. Filter für Entitäten SfxonFilterFieldEntity
                    ✅ -> verwende hierzu Dropdown-Listen mit denen sich alle Werte von Entitäten auswählen lassen.
                    ✅ > unten drunter die aktuell ausgewählten Werte
                    ✅ -> Aktualisieren-Button
            - ✅ Controller: Filtereinstellungen entgegennehmen und Ergebnis filtern / suchen.
            - ✅ Filter für Standort hinzufügen (doppelte Tiefe)
            - ✅ Filter für Geräte-Typ-Hersteller hinzufügen (doppelte Tiefe)
            - ✅ Menge hinzufügen
            - ✅ Filter für Menge hinzufügen; 3 Felder: von, bis und Mengen-Einheit
            - ✅ Bild hinzufügen
            - ✅ QR-Code Generator hinzufügen (https://github.com/kazuhikoarase/qrcode-generator, by Kazuhiko Arase (https://github.com/kazuhikoarase))
                - ✅ Add it to the the editor.
                - ✅ Add it to the list view.
            - ✅ Add Image preview and qr code preview to the list.
            - Add Image and Qr Code Popup, that opens, when an image or qr code is clicked in the list. Should close on next left click in free room, for easy handling. But should also show a close button, just to indicate, it can be closed.
            - Make Elements in list clickable:
                - a) Every elements text should lead to the detail page, but as a link, so it can be opened in a new tab by clicking the middle mouse button.
                - b) Behind related entities i would like to have the forward button, that directly leads to the entities edit page, as a link, so that it can be opened in a new tab with a click on the middle mouse button.
            - Barcodes hinzufügen, Schema: DEVICE:JP001 oder LICENC:623498-23434-sdj 
                - Add it to the editor.
                - Add it to the list.
                - Add barcode preview to the list.
            - Geräte-Editor Darstellung optimieren. Mehrspaltig, wo möglich. Bild und QR-Code nach rechts. Inspiration bei Xanario holen - die haben imho den besten Editor für sowas gebaut (Übersichtlichkeit): https://www.xanario.de/naehere-informationen-software/ct-314.html.
            - Add a results per page dropdown/input, so we can also show 100 results or 1000 results on one page. Load data paged then too - meaning to not overload the server - so the loading should be junked to junks of size 20 (configurable in code).

        - Geräteliste auch nach Entitäten sortierbar machen.
    - Menü hinzufügen, mit dem die Reihenfolge der Spalten geändert werden kann, sowie eingestellt werden kann, in welcher Reihenfolge gefiltert werden kann.

* Filter, Sortier und Spalten-Einstellungen auch in andere Entitäten aufnehmen (einheitlich).

* Rechte-Verwaltung integrieren. Die Bestandteile dürfen nur mit der notwendigen Berechtigung verwendet werden dürfen.

* Lizenz-Management integrieren.

* Farbige Badges, bspw. bei Geräte-Status verwenden. Farbige Punkte überall dort, wo der Badge nicht direkt dargestellt werden kann/wird.

* Die Menü-Leiste links verbessern:
    - Jedem Element eine Farbe und ein Icon zuweisen?
    - Die Abschnitte Bereiche und Stammdaten mit einem Icon versehen (rechts vom Text - rechtsbündig), mit dem sich über der Ansicht oben eine Leiste einblenden/ausblenden lässt - in der die Icons für einen Schnellzugriff angezeigt werden.
    Ist diese Leiste aktiv, soll der "Neu"-Button der einzelnen Seiten immer nach oben rutschen, damit er auch immer verfügbar ist.
    Ggf. muss man dazu diese Komponente auslagern, und auch für die Top-Bar eine Komponente erstellen.
    Design dann auch Xanario inspiriert - die haben im Admin so ein schönes farbiges Menü on top (siehe deren Screenshots).
    Vielleicht auch so bauen, das man mit Klicks durchschalten kann (oben anheften, unten anheften, gar nicht anheften. Büroklammer wäre sinnvoll dafür, die beim ersten mal aktiviert, beim zweiten mal gespiegelt, und beim dritten mal wieder deaktiviert ist. UX Genius. Hahaha. :D )

* Light Mode, Dark Mode debuggen.

* ✅ Alle weiteren Entitäten umsetzen:
    - ✅ Location
    - ✅ Position
    - ✅ Manufacturer/Hersteller
    - ✅ DeviceType
    - ✅ User: Benutzer werden erstmal "normale" Entität. Wir verwenden also nicht weiterhin die Benutzer von Nextcloud. Diese sollten dem Login vorbehalten bleiben (Design-Entscheidung).
    - ✅ Verkäufer (Merchant)

* ✅ DeviceStatus Entität umsetzen
    - ✅ Entität in Datenbank erstellen
    - ✅ Menüpunkt zum Verwalten von DeviceStati hinzufügen (verlinkt auf die Liste)
    - ✅ Liste darstellen
    - ✅ Button "Neu" hinzufügen
    - ✅ Einträge verwalten (CRUD)
    - ✅ nur Löschen, wenn ein Status nicht mehr in Device verwendet wird.
    - ✅ DeviceStatus in den Menüpunkten zum Verwalten von Geräten (Device) hinzufügen.

* Optimize UX - make it a little bit more gamy. Get inspiration from games: https://www.youtube.com/watch?v=NWOUna4fSEI

## Aktuelle Funktionen

Keine

## Kommende Funktionen

1. Hardware Inventar
2. Lizenz Management
3. Reporte
4. Export
5. Import


### Kommende Funktionen: 1. Hardware Inventar

Inventarisierung und Verwaltung von Hardware, bspw. Computer, Monitore, Notebooks, Laptops, Telefone, Headsets, Tastaturen, Mäuse, ...

Ein Datensatz im Inventar besteht unter anderem aus diesen Feldern:

- Geräte-Name
- Standort (bspw. "Filiale Allgäu" oder "Campus 1, Block A")
- Position (Arbeitsplatz 217)
- Kauf-Datum
- Verkäufer
- Inventarnummer (kaufmännisches Inventar)

und weitere. Die Felder sind neben Standard-Feldern flexibel erweiterbar.


### Kommende Funktionen: 2. Lizenz Management

Manage deine Software-Lizenzen.


### Kommende Funktionen: 3. Reporte und Exporte

ITAM unterstützt bei der Erstellung von Berichten und Exporten.
Jeder Report umfasst eine flexible Auswahl an Datensätzen und Feldern in auswählbarer Reihenfolge.
Es werden 2 Export-Formate unterstützt: druckbare HTML-Seiten und CSV-Dateien.


## Feature Requests

* ✅ QR-Codes als URLs (Chlorophylllius - Twitch)<br/>QR-Codes als URLs, welche direkt ins Asset-Maanagement verlinken.

* ✅ Bilder der Assets hinterlegen (Chlorophylllius - Twitch)

* ✅ Mengen-Angaben (bspw. bei Kabeln und Displays) (Chlorophylllius - Twitch)

* Custom-Fields

* Ausgeliehen an (Chlorophylllius - Twitch)
    - E-Mail Bestätigung / Ausgeliehen, zurückgegeben.

* Bearbeitungs-Historie (Chlorophylllius - Twitch)

* Vor-Ort-Protokolle für Standorte:
    - Es kmomt wohl häufiger vor, dass Administratoren Standorte direkt betreuen.
    - Dabei kommt es zu dem Bedarf, dass diese regelmäßig besucht werden  müssen.
    - Es wäre super, wenn man dazu Protokolle führen könnte.
      Einzelne erledigte Wartungsaufgaben sollten in diesen Protokollen festgehalten werden können.
      Hier ist allerdings eine Überschneidung zwischen Aufgaben/Tickets und ITAM bemerkbar.
      Es wäre also interessant, hier Community-Feedback zu haben:
          - Wo wäre das wirklich gut aufgehoben?
          - Wäre es sinnvoll, Ticket-System und ITAM zu verknüpfen?
          - Alternativ kann man ja erstmal einfach nir mit Text-Protokollen oder Protokoll-Listen arbeiten,
            und an diese Links oder Ticket-Nummern mit anheften. Wenn das Zielsystem URLs auflösen kann über Ticket-Nummern,
            könnte man auch so eine Funktion nutzen.
          - Ein Ticket System für Nextcloud kommt ja geplant später auch noch,
            aber ich hätte die Dinge gern flexibel und nicht zwingend gebunden,
            auf der anderen Seite hätte ich sie auch gern so komfortabel wie irgendwie möglich.


## Systemvoraussetzungen

1. Nextcloud Version 33.x

## Installation

1. Projektordner in custom_apps oder apps verschieben.

2. Nextcloud Admin > Benutzer Icon (oben rechts) > Apps > Links im Menü "Deine Apps" > IT Asset Management<br>a) Custom App installieren<br>Aktivieren

<br><br>
---

# Entwickler Dokumentation


## Verwendete Technologien während der Entwicklung und eingebundene Bibliotheken.cla

* Docker Container (Beispiel docker-compose.yml siehe unten)

* Nextcloud

* Vue.js

* PHP

* QR-Code Generator (Kazuhiko Arase, https://github.com/kazuhikoarase/qrcode-generator)

## Beispiel der Installation der Entwicklungsumgebung

### 1. Docker installieren

### 2. Ordner für das Projekt erstellen.
```
mkdir sfxonitam
chdir sfxonitam
```

### 3. docker-compose.yml in neuen Ordner kopieren oder erstellen:

```
nano docker-compose.yml
```

Datei-Inhalt:

```yml
version: "3"

services:
  itam:
    image: nextcloud:latest
    container_name: itam
    restart: always
    ports:
      - "88:80"
      - "22:22"
    networks:
      - web
    environment:
      - XDEBUG_ENABLED=1
      - XDEBUG_REMOTE_HOST=172.17.0.1
      - VIRTUAL_HOST="itam.local"
      - VIRTUAL_PORT=88
    volumes:
      - "./:/var/www/html"
      - "itam_db:/var/lib/mysql"
  itam_db:
    image: mariadb:latest
    container_name: mysql_itam
    networks:
      - web
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_USER=nextcloud
      - MYSQL_PASSWORD=nextcloud
      - MYSQL_DATABASE=nextcloud
networks:
  web:
    external: false
volumes:
  itam_backend_cache:
    driver: local
  itam_db:
    driver: local
```

### 4. Docker Container starten

```bash
docker compose up -d
```

### 5. NVM und NPM installieren

```bash
# In den Container wechseln
docker exec -it itam bash

curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

nvm install --lts
nvm use --lts

node --version
```

Die Versionsnummer sollte ausgegeben werden. Es ist in der Regel nur notwendig, dass man über der benötigten Version liegt.
Nur in Ausnahmefällen sollte man exakt die in der beim kompilieren angegebene avisierte nmp Version verwenden.
Da hier nvm eingesetzt wird, sollte ein Wechsel dorthin kein Problem darstellen.

```bash
npm install
npm i --save @nextcloud/axios @nextcloud/dialogs @nextcloud/initial-state @nextcloud/l10n @nextcloud/router @nextcloud/vue-richtext vue-material-design-icons vue-click-outside
npm i --save-dev vite-plugin-eslint vite-plugin-stylelint


```

### 7. Einige ESLint Regeln deaktivieren (Optional)

Das reduziert die Anzahl an Warnungen, die beim Kompilieren ausgegeben werden, was die Ausgabe beim Kompilieren leichter lesbar macht.

```
nano eslintrc.cjs
```

Neuer Datei-Inhalt:

```cjs
module.exports = {
    globals: {
        appVersion: true
    },
    parserOptions: {
        requireConfigFile: false
    },
    extends: [
        '@nextcloud',
    ],
    rules: {
        'import/extensions': 'off'
        'jsdoc/require-jsdoc': 'off',
        'jsdoc/tag-lines': 'off',
        'vue/first-attribute-linebreak': 'off',
        'vue/html-indent': ['error', 4],
        'indent': ['error', 4],
    },
}

```

## Kompilieren von Vue.js

```bash
# 1. ssh into container or docker exec in container:
docker exec -it itam bash

# 2. Go to app directory.
cd /var/www/html/custom_apps/sfxonitam

# 3. Compile
npm run dev

# 4. Alternatively compile prod environment: npm run prod
```

## Datenbank Migration ausführen

# 2. Go to root directory.

```shell
# Go to nextcloud root directory.
cd /var/www/html

# Run upgrade.
php -d memory_limit=512M occ upgrade

# Disable Maintenance Mode.
php -d memory_limit=512M occ maintenance:mode --off
```

## Datenmodell

```mermaid
erDiagram
    Device }o--o| Position : has
    Device }o--o| DeviceType : has
    Device }o--o| ItamUser : has
    Device }o--o| Merchant : has
    Device }o--o| DeviceStatus : has
    Position }o--o| Location : has
    DeviceType }o--o| Manufacturer : has

    Device {
        BIGINT id PK
        string name UK
        BIGINT deviceStatusId FK "DEFAULT NULL"
        BIGINT positionId FK "DEFAULT NULL;<br>Im Sinn von Position"
        BIGINT deviceTypeId FK "DEFAULT NULL;"
        BIGINT itemUserId FK
        string serialNumber
        string serialNumber2
        string assetNumber "Anlagennummer"
        BIGINT merchantId FK "DEFAULT NULL"
        string invoiceNumber
        DATETIME purchaseDate "DEFAULT NULL"
        string customFields "LONGTEXT; Contains a JSON string"
        string comment
    }

    DeviceStatus {
        BIGINT id PK
        string name
        string comment
    }

    Location {
        BIGINT id PK
        string name
        string comment
    }

    Position {
        BIGINT id PK
        BIGINT locationId FK "DEFAULT NULL<br>Im Sinn von Standort"
        string name
        string comment
    }

    DeviceType {
        BIGINT id PK
        string name UK
        BIGINT manufacturerId FK "DEFAULT NULL"
        string comment
    }

    ItamUser {
        BIGINT id PK
        string firstname
        string lastname
        string email
        string comment
    }

    Manufacturer {
        BIGINT id PK
        string name UK
        string comment
    }

    Merchant {
        BIGINT id PK
        string name
        string comment
    }
```

## Fragen während der Entwicklung

Nachfolgend habe ich Fragen zusammengestellt, die ich mir während der Entwicklung gestellt habe.

1. Was ist OCA für ein Namespace?

* OCA steht für „Owncloud Apps" – ein historisch gewachsener Name aus der Zeit, bevor Nextcloud aus ownCloud hervorgegangen ist. In Nextcloud hat er sich als Konvention erhalten. OCA taucht in zwei Kontexten auf:

a) PHP-Namespace (serverseitig)
\OCA\ ist der Top-Level-PHP-Namespace für alle Nextcloud-Apps. Nextcloud verwendet einen PSR-4-Autoloader, bei dem \OCA\MyApp auf das Verzeichnis /apps/myapp/lib/ gemappt wird. Nextcloud
Jede App bekommt also ihren eigenen Unter-Namespace darunter, z. B.:

\OCA\Files\Controller\...
\OCA\MyApp\Service\...

Dabei gilt: OCA ist für Apps reserviert, während der Kern von Nextcloud unter OC\Core liegt.


b) 2. JavaScript-Namespace (clientseitig)
Das globale window.OCA-Objekt stellt Namespaces für app-spezifisches JavaScript bereit. Apps registrieren sich unter diesem Objekt, um ihre APIs und ihren Zustand zugänglich zu machen.
Klassisches Beispiel wäre OCA.Files.fileActions. Dieser Ansatz gilt mittlerweile als weitgehend veraltet – moderne Apps sollen stattdessen das separate NPM-Paket @nextcloud/files verwenden.


2. Welche Datenbank-Wrapper werden verwendet?<br>
Offenbar läuft im Hintergrund DOCTRINE.


3. Kann ich im Ordner Controller einfach neue Controller hinzufügen,
oder läuft alles über ApiController und PageController?
  * Ich konnte es nur mit einem einmaligen CamelCase Dateinamen zum Laufen bekommen.
  * Ansonsten funktioniert es zuverlässig. Das Caching macht ggf. Probleme aktivieren und deaktivieren der App soll helfen -> das habe ich noch nicht gestetet. Ansonsten hilft das Anheben der Version und das Ausführen des App Update Mechanismus.


4. Wie funktioniert das Senden von Formularen inkl. CSRF Token?

Eine von Nextcloud bereitgestellte Version von axios fügt automatisch den benötigten CSRF-Token hinzu:

```js
// Axios importieren.
import axios from '@nextcloud/axios'

// Beispiel-Methode, welche Formulardaten sendet. name, invoiceDate und selectedUser sind jeweils Variablen im vue script -
// hier nicht definiert - sie würden weiter oben - also globaler definiert werden und an Eingabeelemente gebunden werden (v-model="name").
async function submitForm() {
    console.log('submitForm');

    if (!name.value) {
        // Fehlerbehandlung
        return
    }

    try {
        const response = await axios.post(
            generateUrl('/apps/sfxonitam/device/save'),
            {
                name:        name.value,
                invoiceDate: invoiceDate.value?.toISOString().split('T')[0] ?? null,
                userId:      selectedUser.value?.id ?? null,
            }
            // Kein manueller CSRF-Header nötig –
            // @nextcloud/axios ergänzt ihn automatisch!
        )
        console.log('Gespeichert:', response.data)
    } catch (error) {
        console.error('Fehler beim Speichern:', error)
    }
}
```

5. Woher kommt NcListItem, dass im VueJs Code von Nextcloud immer wieder verwendet wird?

NcListItem kommt aus dem offiziellen @nextcloud/vue-Paket – der zentralen Vue-Komponentenbibliothek von Nextcloud.

https://github.com/nextcloud-libraries/nextcloud-vue/blob/e5d5bd6e8abed8ef4670be69cd69beae237013c5/src/components/NcListItem/NcListItem.vue#L41


6. Wie kann man die Actions verwenden, wie es NcListItem macht?

NcListItem verwendet hierzu NcActions.
Da es sich hierbei ebenfalls um eine Component aus nextcloud-vue handelt, kann diese weiterverwendet werden.

NcActions und NcAction Button einbinden:
```
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
```


Im HTML-Teil definieren:
```
<NcActions>
    <NcActionButton @click="onEditDevice(device)">
        <template #icon>
            <NcIconSvgWrapper :path="mdiPencil" :size="20" />
        </template>
        Bearbeiten
    </NcActionButton>
    <NcActionButton @click="onDeleteDevice(device)">
        <template #icon>
            <NcIconSvgWrapper :path="mdiDelete" :size="20" />
        </template>
        Löschen
    </NcActionButton>
</NcActions>
```

Die Methoden müssen noch ausdefiniert werden, also hier bspw. onEdit und onDelete. Die übergebenen Parameter sind ein Daten-Objekt aus unserem Projekt.


7. Caching

Änderungen an Controllern werden nicht immer sofort übernommen.
Das Problem ist vermutlich der aktivierte opcache im Nextcloud Image.
Ich habe ihn folgendermaßen deaktiviert:

```cli
# Ordner angelegt:
[nextcloud-ordner]/php/

# Datei angelegt
nano ./php/zzz-opcache.ini

# Inhalt der Datei gespeichert:
opcache.enable=0
opcache.enable_cli=0
```

Anschließend in Docker-Compose unter Volumes folgendes eintragen:

```yml
- "./php/zzz-opcache.ini:/usr/local/etc/php/conf.d/zzz-opcache.ini"
```

Das sieht dann darin also in etwa so aus:
```
services:
  [...]
  itam:
    [...]
    volumes:
      - "./:/var/www/html"
      - "itam_db:/var/lib/mysql"
      - "./php/zzz-opcache.ini:/usr/local/etc/php/conf.d/zzz-opcache.ini"
```

Eine Anzeige der PHP-Ini sollte danach für den Wert ```opcache.enable``` den Wert ```Off``` ergeben.


8. Adminer

Wenn ich eine adminer.php in das Root-Verzeichnis lege, wird diese nicht angezeigt.

-> Grund: htaccess-Datei verhindert dies.

Einfach diese Zeile hinzufügen:

```
RewriteCond %{REQUEST_FILENAME} !/adminer\.php$
```

Sie muss kurz vor das Ende der Datei hier:

```
  RewriteCond %{REQUEST_FILENAME} !/richdocumentscode(_arm64)?/proxy.php$
  RewriteCond %{REQUEST_FILENAME} !/adminer\.php$
  RewriteRule . index.php [PT,E=PATH_INFO:$1]
  RewriteBase /
  <IfModule mod_env.c>
    SetEnv front_controller_active true
    <IfModule mod_dir.c>
      DirectorySlash off
    </IfModule>
  </IfModule>
</IfModule>
```

9. Statische Javascript-Dateien und andere Assets einbinden.

I did it like this before,
but now I altered the file a bit,
and added it as a static file with an import.

I had to change the bottom part of the original library:

```export default qrcode;```


Die Datei vite.config.js anpassen. Ich habe alle relevanten Stellen mit <-- Neu markiert.

```js
import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'
import { copyFileSync, mkdirSync } from 'fs'    <-- Neu

// Neu
const copyStaticAssets = () => ({
    name: 'copy-static-assets',
    closeBundle() {
        mkdirSync('js/vendor/qrcode-generator-kazuhiko-arase', { recursive: true })
        copyFileSync(
            'static/qrcode-generator-kazuhiko-arase/qrcode.js',
            'js/vendor/qrcode-generator-kazuhiko-arase/qrcode.js'
        )
    }
})
// Ende Neu

export default createAppConfig(
    {
        // deine Entries...
    },
    {
        createEmptyCSSEntryPoints: true,
        extractLicenseInformation: true,
        thirdPartyLicense: false,
        config: {
            plugins: [copyStaticAssets()],    <-- Neu
            resolve: {
                alias: {
                    '@': resolve('src'),
                },
            },
        },
    },
)
```

Dadurch sollte die Datei dann in den Ordner ./js/vendor/qrcode-generator-kazuhiko-arase/qrcode kopiert werden.

Dann kann man sie in allen benötigten Templates einbinden,
also bspw. in ```./templates/device/editor.php``` so hinzufügen:

```php
Util::addScript(Application::APP_ID, 'vendor/qrcode-generator-kazuhiko-arase/qrcode');
```

In vue ist sie dann automatisch als globales Script geladen.
Es kann dann auf diese Art verwendet werden:

```js
// Ganz oben das hier einbinden:
declare function qrcode(typeNumber: number, errorCorrectionLevel: string): any

// Die Komponente definieren:
const qrCodeSvg = ref<string | null>(null)

// Funktion zum Generieren der QR-Codes schreiben
function generateQrCode(id: number) {
    const qr = qrcode(0, 'M')
    qr.addData(generateUrl(`/apps/sfxonitam/device/detail?deviceId=${id}`))
    qr.make()
    qrCodeSvg.value = qr.createSvgTag(4, 0)
}

// Zum Ausführen bspw. nach dem Laden der Geräte-Daten das hier hinzufügen:
generateQrCode(id)

// also so hier bspw.:
onMounted(async () => {
    await loadDeviceStatis()
    await loadDeviceTypes()
    await loadItamUsers()
    await loadMerchants()
    await loadPositions()
    await loadQuantityUnits()

    if (deviceId.value) {
        await loadDevice(deviceId.value)
        generateQrCode(deviceId.value)
    }
})

// An der richtigen Stelle ausgeben lassen:
<div v-if="isEditMode && qrCodeSvg" v-html="qrCodeSvg" :class="$style.qrCode" />

// Ggf. noch mit Style/CSS gestalten:
.qrCode {
    margin-top: 8px;
    width: 120px;
    height: 120px;
}

.qrCode :global(svg) {
    width: 100%;
    height: 100%;
}
```

## Komponenten

### SfxonTable

* Mit dieser Komponente können Tabellen erstellt werden.

* SfxonTable wurde so konzipiert, dass sie unabhängig vom Datentypen funktioniert, und über Definitionen ganze Listen von Datensätzen darstellen kann.

* Die Komponente besteht aus diesen Unter-Komponenten:
    - SfxonTableHeader
    - SfxonTableBody
    - SfxonTableRow

#### Event Handler

Es gibt drei Event-Handler, die verwendet werden können, um auf den Hover-Status von Zeilen und Spalten zu reagieren:

Callback-Name | Signatur                              | Priorität
--------------|---------------------------------------|-----------
onColHover    | (dataRow, col) => boolean | void      | Hoch - wenn sie true zurück gibt, wird onRowHandler unterdrückt.
onRowHover    | (dataRow) => void                     | Niedrig - feuert nur, wenn kein onColHover definiert oder dieser false/void zurückgibt
onRowLeave    | (dataRow) => void                     | Immer beim Verlassen der Zeile

**Einsatz der Event Handler:**

```js
// Define Event Handlers in calling vue Element, Component or View:
function onColHover(dataRow: any, col: any): boolean | void {
    // do something, return true to suppress on RowHover
}

function onRowHover(dataRow: any) {
    // do something
}

function onRowLeave(_dataRow: any) {
    // do something
}

// Assign the Event Handlers in the col definition:
const columns = [
    {
        type: 'image',
        label: t('sfxonitam', 'Image'),
        key: 'imageFileId', 
        colHandler: onColHover,
        rowHandler: previewImage,
        rowLeaveHandler: previewClear
    },
]
```

Eingesetzt wird das bspw. im View *DeviceList.vue*. Dort werden die Events verwendet, um:

* in der Seitenleiste eine Bild-Vorschau darzustellen wenn man über die Zeile hovert
* falls man aber über der Spalte QR-Code hovert, wird der QR-Code statt des Bildes an dieser Stelle angezeigt.
* eine Besonderheit ist, dass der colHandler aktuell nur beim QR-Code Einsatz findet. Dieser wird also nur ausgelöst, wenn man über der QR-Code Spalte hovert - ansonsten wird dort immer der Row-Handler bei jeder Zeile ausgelöst. Dadurch ist in der Regel das Bild des Eintrages zu sehen, und nur der QR-Code, wenn man auch wirklich über der QR-Code Zeile hovert.
