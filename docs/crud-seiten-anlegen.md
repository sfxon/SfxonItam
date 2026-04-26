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