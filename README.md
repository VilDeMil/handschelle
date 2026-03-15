# Die-Handschelle

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.
> Documentation of crimes by political figures as a WordPress plugin.

| | |
|---|---|
| **Version** | 6.5 |
| **Autor** | Bernd K.R. Dorfmüller |
| **E-Mail** | Info@die-handschelle.de |
| **Website** | https://www.die-handschelle.de |
| **Lizenz** | GPL-2.0+ |
| **Requires** | WordPress 5.5+, PHP 7.4+, GD Library |

---

## Einleitung / Introduction

Die letzten Tage gab es immer wieder Berichte über verurteilte Straftäter in unseren Parlamenten, aber leider waren die Informationen immer dürftig, nur schwer zu finden und spätestens nach einigen Tagen verschwanden diese „Vorfälle" im digitalen Rauschen und wurden vergessen.

„Wie wäre es, wenn diese Informationen zentral gesammelt werden?" Eine interessante Frage und einige Stunden „Vibe-Coding" später gibt es nun **„Die-Handschelle"**. Eine Datenbank in der nach kriminellen **Mandatsträgern** gefiltert werden kann.

Jeder kann mitmachen und neue Fälle melden. Jeder Eintrag wird vor der Veröffentlichung genau geprüft.

---

**„Die-Handschelle" benötigt Deine Hilfe.**

Während der Entwicklung dieser Datenbank stellte sich schnell heraus, dass es auf allen politischen Ebenen Mandatsträger mit einer zweifelhaften Vergangenheit gibt — und es sind so viele, dass wir nicht alle Informationen alleine finden können.

Das Projekt „Die-Handschelle" steht noch ganz am Anfang. „Die-Handschelle" wird stetig weiter entwickelt und weitere Funktionen sind in Vorbereitung.

Bitte unterstützt das Projekt, indem ihr dabei helft, Straftäter in unseren Parlamenten zu identifizieren.

**Danke — „Die-Handschelle"**

---

## Dokumentation

Die detaillierte Dokumentation ist in separate Dateien aufgeteilt:

- [installation-from-github.txt](installation-from-github.txt) — Installation via ZIP, Git, WP-CLI
- [build-package.txt](build-package.txt) — ZIP erstellen für Distribution
- [shortcodes.txt](shortcodes.txt) — Alle Shortcodes mit Parametern und Beispielen
- [fields-database-schema.txt](fields-database-schema.txt) — Datenbankfelder und Schema
- [code-reference.txt](code-reference.txt) — PHP-Klassen, Funktionen, JS-API, CSS
- [plugin-structure.txt](plugin-structure.txt) — Dateistruktur des Plugins
- [important-notes.txt](important-notes.txt) — Wichtige Hinweise zur Nutzung
- [instructions-for-ai-llm.txt](instructions-for-ai-llm.txt) — Regeln für KI-Assistenten
- [recreate-from-scratch.txt](recreate-from-scratch.txt) — Prompt zum Nachbauen des Plugins

---

## Release Notes

### 6.5 *(2026-03-15)*
- **Dokumentation aufgeteilt**: Alle Themen-Abschnitte aus README.md in separate `.txt`-Dateien ausgelagert; README.md enthält nur noch Projektinfo, Einleitung und Release Notes

### 6.4 *(2026-03-15)*
- **Complete README update**: Fixed `HANDSCHELLE_VERSION` constant example (`6.2`→`6.3`); corrected field/column count from 32 to 31 everywhere (Fields section, CSV section, Recreate prompt); fixed shortcode count from 16 to 19 in Plugin Structure; added missing `[handschelle-asc-link]` to Shortcodes overview table, detailed section, and Recreate prompt; updated AI-instructions version examples from `6.2` to `6.3`

### 6.3 *(2026-03-15)*
- **Build / Package**: Added `## Build / Package` section with `zip` commands to create a distributable ZIP, a quick one-liner variant, and a GD verification command

### 6.2 *(2026-03-14)*
- **Recreate from Scratch**: Added `## Recreate from Scratch` section to README — a complete, self-contained prompt for rebuilding the entire plugin with an AI assistant from a blank slate
- **ToC**: Added entry for new section; fixed stale `HANDSCHELLE_VERSION // '6.0'` reference in Code Reference

### 6.1 *(2026-03-14)*
- **Version policy**: Version is now bumped by `0.1` per commit (was `0.01`); old comment in `die-handschelle.php` corrected accordingly
- **LLM Instructions**: Added `## Instructions for AI / LLM` section to README with version-bump rules, shortcode checklist, schema rules, and general coding standards

### 6.0 *(2026-03-14)*
- **`[wordcloud-name]`**: Word cloud of all approved person names — font size proportional to entry count, shows Name (Partei), tooltip shows exact count; pure CSS/HTML, no external library
- **`[wordcloud-urteil]`**: Word cloud of all distinct verdicts (`urteil`) — font size proportional to frequency; only entries with a non-empty verdict are included
- **Dropdown styling**: Text color set to black (`#000`), background set to white (`#fff`), transparency removed — applies to all select elements (`.hs-select`, `.hs-field select`, `.hs-edit-form select`, `.hs-bulk-select`)
- **Eintrag melden**: Every card now has a `⚠️ Eintrag melden!` mailto link in the footer — opens a pre-addressed e-mail to `info@hanschelle.com` with subject `Meldung - <Name> - <Partei>`
- **Bilder-Galerie**: Hover tooltip and click-link removed from gallery images — images display as plain `<img>` tags with name/crime captions only
- **Edit-Button Sichtbarkeit**: Inline-edit button and panel on frontend cards are now visible only to users with role **Author or higher** (`publish_posts` capability) — Subscribers and Contributors no longer see the edit controls

### 3.09 *(2026-03-14)*
- **Hintergrundfarbe**: `--hs-bg` auf `#f0f0f0` (neutrales Hellgrau) geändert; alle Eingabefelder und Dropdowns nutzen `var(--hs-bg)` statt #fafafa
- **Bugfix – Neue Einträge nicht gespeichert**: Bei Nonce-Fehler (z. B. Cache) erhält der Nutzer jetzt eine sichtbare Fehlermeldung (`⚠️ Fehler beim Speichern. Bitte Seite neu laden…`) statt stiller Weiterleitung; `$wpdb->insert()`-Ergebnis wird geprüft — Erfolgsmeldung erscheint nur bei tatsächlich gespeichertem Eintrag; `datum_eintrag` fällt jetzt auch bei leerem String auf das aktuelle Datum zurück (`?:` statt `??`)
- **Bugfix – Backup/Restore Feldzuordnung**: Im Restore-Code waren alle CSV-Spalten ab Index 4 um 2 Positionen verschoben (`geburtsort` und `geburtsdatum` wurden übersprungen); `bild`-ID wurde aus Spalte 4 (`geburtsort`) statt aus Spalte 6 gelesen → falsche Bildzuordnung; `geburtsort`, `geburtsdatum`, `sm_linkedin`, `sm_xing`, `sm_truth_social` wurden nie wiederhergestellt; `freigegeben` wurde aus Spalte 23 (`sm_wikipedia`) statt aus Spalte 28 gelesen — alles korrigiert

### 3.08 *(2026-03-14)*
- **`[handschelle-asc]`**: Output is now horizontally centered (`justify-content: center`)

### 3.07 *(2026-03-14)*
- **`[handschelle-disclaimer]`**: E-Mail → `Info@die-handschelle.de`, Website → `www.die-handschelle.de`, Buy-Me-A-Coffee → `buymeacoffee.com/dorfmuellersak47`, tagline in quotation marks
- **Neuer Shortcode `[handschelle-asc]`**: Horizontale zentrierte Liste aller Parteien mit Eintragsanzahl (A→Z, ohne Header, kleiner Font)
- **Neue Felder**: `geburtsort` (VARCHAR 100), `geburtsdatum` (DATE), `sm_linkedin`, `sm_xing`, `sm_truth_social` — in allen Formularen, Karten, CSV
- **Alter**: Admin-Übersicht zeigt berechnetes Alter; Karte zeigt Geburtsdatum + Alter
- **Suchmaschinen**: Qwant, DuckDuckGo und Bing überall, wo bisher nur Google stand (Karten-Footer, Name-Dropdown, Name-Anzeige, Admin-Formular, Inline-Edit-Panel)
- **`[handschelle-bilder]` klickbar**: Klick auf Bild öffnet Personendetails via `?hs_name=<Name>`; neues Attribut `link=""` für die Zielseite
- **CSV Import**: Komplett auf header-basiertes Mapping umgestellt (rückwärtskompatibel mit alten CSVs); CSV-Export enthält alle 32 Spalten

### 3.06 *(2026-03-14)*
- **`[handschelle-bilder]`**: Name und Straftat als Beschriftung unter jedem Bild
- **`[handschelle-bilder]`**: Reiner CSS-Tooltip beim Hover mit allen Personendaten
- **`[handschelle-anzeige]`**: Standard `limit` auf 0 gesetzt (keine Paginierung)
- **DB-Migration**: `maybe_upgrade_table()` — fehlende Spalten werden via `dbDelta()` beim Plugin-Update automatisch ergänzt, kein Datenverlust

### 3.05 *(2026-03-13)*
- **Keine Hintergrundfarben**: Hintergrundfarben von Frontend-Containern entfernt — Plugin integriert sich neutral ins Theme
- **Volle Breite**: Alle Shortcode-Wrapper verwenden `hs-full-width` (100 % Breite)
- **Alle Links im Karten-Footer**: Google- und Abgeordnetenwatch-Links aus dem Karten-Header entfernt; alle Links jetzt im `.hs-card-footer`
- **Neuer Shortcode `[handschelle-statistik-nolink]`**
- **MediaID-Remapping beim Backup/Restore**: `bild-map.json` in ZIP; automatisches ID-Remapping beim Restore

### 3.04 *(2026-03-13)*
- **Such-Buttons überall**: Google und Abgeordnetenwatch in Admin-Formular, Frontend-Inline-Edit und Name-Anzeige
- **Karten-Bild klickbar**: Profilfoto verlinkt auf `?hs_name=<name>` der gleichen Seite
- **Vollbreite Name-Ergebnisse**: `.hs-cards-single` für Personenansicht

### 3.03 *(2026-03-13)*
- **Bild-Umbenennung**: Schema geändert auf `<Name>-HA.<ext>` (z. B. `max-mustermann-HA.jpg`)
- **Neuer Shortcode `[handschelle-statistik-ol]`**
- **Admin: Backup & Restore**: Vollständiges Backup als ZIP (CSV + Bilder); Restore importiert beides

### 3.02 *(2026-03-13)*
- **WP Media Manager** als primäre Bildauswahl; „Bild entfernen"-Button im Bearbeiten-Modus

### 3.00 *(2026-03-13)*
- **Paginierung** für `[handschelle-anzeige]` (`limit`-Attribut, `hs_paged`-Parameter)
- **Volltext-Suche** in `[handschelle-suche]` (`hs_search`-Parameter)
- **`[handschelle-karte id="X"]`**: Einzelkarte per Datenbank-ID
- **Admin-Filter-Tabs**: Alle / Ausstehend / Freigegeben mit Anzahl-Badges
- **Admin-Bulk-Aktionen**: Mehrere Einträge gleichzeitig freigeben, sperren oder löschen
- **WP-Medienbibliothek-Picker** im Admin-Formular

### 2.07 – 2.09 *(2026-03-13)*
- Shortcode `[handschelle-bilder]` (Bildergalerie)
- Admin-Seite **Bilder**: ZIP-Export & ZIP-Import von Anhängen
- Shortcodes `[handschelle-statistik-partei]`, `[handschelle-statistik-name]`, `[handschelle-name-anzeige]`, `[handschelle-name-partei]`
- Buy-Me-A-Coffee-Link, Einleitung in README

### Alpha-2 / 2.0 A *(initial)*
- Initial release: Frontend-Formular, Eintrags-Karten, Partei-/Personen-Dropdowns, Statistiktabelle, CSV-Import/Export, Datenbankverwaltung, Bild-Upload & GD-Resize (max. 450 px)

---

*Erstellt mit Vibe-Coding — KI-gestützte Entwicklung mit Claude (Anthropic)*
