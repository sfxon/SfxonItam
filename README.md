# ITAM

ITAM (IT Asset Management) ist eine Nextcloud Erweiterung zur Verwaltung von Hardwaregeräten und Softwarelizenzen.
Das Programm richtet sich an kleine und mittelständische Unternehmen (KMU) sowie Privatpersonen.

* MIT-Lizenz
* kostenfreie Nutzung
* Release im Nextcloud Appstore zur einfacheren Installation ist geplant.


## Timeline

* Device Entität umsetzen:
  - Übersichts-Seite für die Geräte umsetzen:
    - Tabelle Element für Element in einzelne wiederverwendbare Module aufteilen.
        - ✅ Tabelle insgesamt in neues Modul auslagern.
        - ✅ Tabellenkopf in eigene Komponente auslagern.
        - Tabellenzeile in eigene Komponente auslagern.
        - Tabellenspalte (Zelle) in eigene Komponente auslagern.
    - Pagination in eigene Komponente auslagern
    - Alte Elemente in VueJs entfernen, die aktuell noch als Referenz enthalten sind.
    - ✅ Action-Spalte hinzufügen.
  - Detail-Seite erstellen zum Anlegen und Bearbeiten
    - Eingaben prüfen
    - Ausgaben escapen?
    - Alle Werte hinzufügen (Entitäten, "normale" Felder)
    - ✅ Controller
    - ✅ View
    - ✅ Speicher Aktion
  - Einträge bearbeiten
  - Einträge löschen
  - ✅ Entität in Datenbank erstellen
  - ✅ Button "Neu" hinzufügen

* DeviceStatus Entität umsetzen
  - Entität in Datenbank erstellen
  - Menüpunkt zum Verwalten von DeviceStati hinzufügen (verlinkt auf die Liste)
  - Liste darstellen
  - Button "Neu" hinzufügen
  - Einträge verwalten (CRUD)
    - nur Löschen, wenn ein Status nicht mehr in Device verwendet wird.
  - DeviceStatus in den Menüpunkten zum Verwalten von Geräten (Device) hinzufügen.


## Aktuelle Funktionen

Keine

## Kommende Funktionen

1. Hardware Inventar
2. Lizenz Management
3. Reporte und Exporte


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

## Systemvoraussetzungen

1. Nextcloud Version 33.x

## Installation

1. Projektordner in custom_apps oder apps verschieben.

2. Nextcloud Admin > Benutzer Icon (oben rechts) > Apps > Links im Menü "Deine Apps" > IT Asset Management<br>a) Custom App installieren<br>Aktivieren

<br><br>
---

# Entwickler Dokumentation


## Verwendete Technologien während der Entwicklung

* Docker Container (Beispiel docker-compose.yml siehe unten)

* Nextcloud

* Vue.js

* PHP

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

### 5. SSH im Docker Container installieren

```bash
# In den Container wechseln
docker exec -it itam bash

# Dann im Container:
apt-get update
apt-get install -y openssh-server
mkdir -p /var/run/sshd
echo 'root:root' | chpasswd
sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config
service ssh start
```

Benutzername und Passwort lauten für den Zugriff auf den Docker-Container dann:
User: root
Pass: root

ssh -p 22 root@localhost

Das ist keine dauerhafte Lösung. Killt man den Container bspw. mit docker-compose down, ist der SSH-Server weg.
Langfristig wäre eine Implementierung mittels Dockerfile besser geeignet.

### 6. NPM installieren

```bash
ssh -p 22 root@localhost

cd /var/www/html/custom_apps/sfxonitam

nvm use --lts

node -v
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
		'jsdoc/require-jsdoc': 'off',
        'jsdoc/tag-lines': 'off',
		'vue/first-attribute-linebreak': 'off',
        'import/extensions': 'off'
	},
}

```

## Kompilieren von Vue.js

```bash
# 1. ssh into container

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
    Device }o--o| User : has
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
        BIGINT userId FK
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