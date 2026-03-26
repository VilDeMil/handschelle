# Die-Handschelle

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.
> Documentation of crimes by political figures as a WordPress plugin.

| | |
|---|---|
| **Version** | 15.1 |
| **E-Mail** | info@die-handschelle.com |
| **Website** | https://www.die-handschelle.com |
| **Lizenz** | GPL-2.0+ |
| **Requires** | WordPress 5.5+, PHP 7.4+, GD Library |

---

## Table of Contents

- [Einleitung / Introduction](#einleitung--introduction)
- [To Do](#to-do)
- [Important Notes](#important-notes)
- [Release Notes](#release-notes)
- [Installation from GitHub](installation-from-github.txt)
- [Shortcodes](#shortcodes)
  - [Overview](#overview)
  - [`[handschelle]`](#handschelle)
  - [`[handschelle-anzeige]`](#handschelle-anzeige)
  - [`[handschelle-asc]`](#handschelle-asc)
  - [`[handschelle-asc-link]`](#handschelle-asc-link)
  - [`[handschelle-bilder]`](#handschelle-bilder)
  - [`[handschelle-disclaimer]`](#handschelle-disclaimer)
  - [`[handschelle-karte]`](#handschelle-karte)
  - [`[handschelle-login]`](#handschelle-login)
  - [`[handschelle-name]`](#handschelle-name)
  - [`[handschelle-name-anzeige]`](#handschelle-name-anzeige)
  - [`[handschelle-name-partei]`](#handschelle-name-partei)
  - [`[handschelle-partei]`](#handschelle-partei)
  - [`[handschelle-pie-partei]`](#handschelle-pie-partei)
  - [`[handschelle-privacy]`](#handschelle-privacy)
  - [`[handschelle-register]`](#handschelle-register)
  - [`[handschelle-result]`](#handschelle-result)
  - [`[handschelle-statistik]`](#handschelle-statistik)
  - [`[handschelle-statistik-name]`](#handschelle-statistik-name)
  - [`[handschelle-statistik-nolink]`](#handschelle-statistik-nolink)
  - [`[handschelle-statistik-ol]`](#handschelle-statistik-ol)
  - [`[handschelle-statistik-partei]`](#handschelle-statistik-partei)
  - [`[handschelle-straftat]`](#handschelle-straftat)
  - [`[handschelle-straftat-link]`](#handschelle-straftat-link)
  - [`[handschelle-straftaten]`](#handschelle-straftaten)
  - [`[handschelle-suche]`](#handschelle-suche)
  - [`[handschelle-ticker]`](#handschelle-ticker)
  - [`[handschelle-ticker-icons]`](#handschelle-ticker-icons)
  - [`[wordcloud-name]`](#wordcloud-name)
  - [`[wordcloud-urteil]`](#wordcloud-urteil)
  - [Typical Page Setup](#typical-page-setup)
- [Build Package](#build-package)
- [Code Reference](#code-reference)
- [Fields & Database Schema](#fields--database-schema)
- [Next Commands](#next-commands)
- [Plugin Structure](#plugin-structure)
- [Prompt](#prompt)
- [Shortcodes Reference](#shortcodes-reference)
- [Instructions for AI / LLM](#instructions-for-ai--llm)
  - [Version Bumping](#version-bumping)
  - [Adding a Release Note](#adding-a-release-note)
  - [Shortcode Checklist](#shortcode-checklist)
  - [Database / Schema Changes](#database--schema-changes)
  - [General Rules](#general-rules)
- [Datenschutz / Privacy](#datenschutz--privacy)
- [Connect GitHub to AI Tools](Connect%20GitHub%20to%20AI%20Tools.txt)
- [Recreate from Scratch](#recreate-from-scratch)

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

## To Do

Planned features for upcoming versions:

- **Mailing-Liste**: Newsletter / subscription list so followers are notified when new entries are published.
- **Multilanguage**: Full multilingual support (DE / EN and more) via the WordPress language system.
- **Multiple Offences**: Support for recording multiple separate crimes per person, each with its own status, verdict, and source link.
- **Multiuser**: Role-based multi-user system with individual dashboards, personal submission histories, and per-user moderation rights.
- **International Version**

---

## Important Notes

- New public submissions are **not approved by default** (`freigegeben = 0`). An admin must approve them via **Übersicht → ✅ Freigeben**.
- Profile images are automatically resized to a maximum height of **450 px** using the GD library (required).
- CSV export uses **UTF-8 with BOM** and **semicolons** as delimiters for Excel compatibility. The import is header-based and backward-compatible.
- The **Edit page** is hidden from the admin sidebar but accessible via the ✏ button in the Overview table.
- **Authors and higher** (role `Author`, `Editor`, `Administrator`) see an inline edit button on every entry card in the frontend — Subscribers and Contributors do not.
- The inline edit panel and admin form both include **Google, Qwant, DuckDuckGo, Bing, Abgeordnetenwatch** search buttons next to the name field.
- All forms use **WordPress nonce verification** to prevent CSRF attacks. If nonce verification fails (e.g. after a long session or cached page), the user sees a visible error message (`⚠️ Fehler beim Speichern`) instead of a silent redirect.
- All user input is sanitized with WordPress sanitization functions before writing to the database.
- Social media icons are rendered as **inline SVG** with brand colors and hover effects — no external icon library required.
- **Image uploads** are automatically renamed to `name-HA.ext` (e.g. `max-mustermann-HA.jpg`) using `sanitize_title()`.
- The **admin image field** supports two workflows: (1) pick from the WP Media Library via the `wp.media` modal, or (2) upload a new file directly.
- **Database auto-migration:** After updating the plugin, `maybe_upgrade_table()` runs on `plugins_loaded` and adds any missing columns via `dbDelta()`. No data is ever lost.

> Full notes: [important-notes.txt](important-notes.txt)

---

## Release Notes

### 15.1 *(2026-03-26)*
- **KI-Analyse link format**: Updated all card/ticker templates to build the KI link for each Straftat as `/chat/?frage="Was weist du über"<Name>+<Partei>+<Straftat>?` (URL-encoded in code).
- **Version bump**: Bumped version from 15.0 to 15.1; updated README.md and all .txt files to reflect the new version.

### 15.0 *(2026-03-26)*
- **Version bump**: Bumped version from 14.9 to 15.0; updated README.md and all .txt files to reflect the new version.

### 14.9 *(2026-03-26)*
- **WordPress theme responsive width**: Mobile always renders at 95% viewport width; desktop (≥782px) always renders at 90% viewport width — implemented via CSS root padding variable overrides in `wordpressc template/style.css` and `style.min.css`.

### 14.8 *(2026-03-25)*
- **New shortcode `[handschelle-straftaten]`**: Dropdown listing all distinct Straftat values (from both the main table and the approved offences table). Selecting a crime displays full entry cards (`render_card()`) for every person linked to that crime — main straftat field and any additional offence in `wp_die_handschelle_offences` are both searched. Query parameter: `hs_straftat`.
- **`Handschelle_Database::get_distinct_straftaten()`**: New DB method — UNION of `straftat` from main table and `straftat` from offences table (both `freigegeben=1`, ordered A-Z).
- **`Handschelle_Database::get_entries_by_straftat()`**: New DB method — returns full rows (`SELECT *`) for all approved entries where `straftat` matches in the main table or in any approved offence row.

### 14.7 *(2026-03-25)*
- **Version bump**: Bumped version from 14.6 to 14.7.

### 14.6 *(2026-03-24)*
- **"Status der Straftat" – added "Berufung" option**: Extended the status dropdown for offences with a fourth option "Berufung" (appeal). Available choices are now: "Ermittlungen laufen", "Verurteilt", "Eingestellt", "Berufung".

### 14.5 *(2026-03-24)*
- **CSS – removed shortcode output width restrictions**: Removed `max-width: 440px` from `.hs-login-wrap` and `.hs-register-wrap`; removed `max-width: 600px` from `.hs-wanted-card`; removed `max-width: 520px` from pie-chart canvas elements (`[handschelle-pie-partei]`, `[handschelle-pie-partei-filter]`). All shortcode outputs now expand to the full available container width.

### 14.4 *(2026-03-24)*
- **README – txt files as chapters**: Replaced flat "Links to Documentation" list with individual `##` chapter sections for each `.txt` file (`build-package.txt`, `code-reference.txt`, `fields-database-schema.txt`, `Next-Commands.txt`, `plugin-structure.txt`, `prompt.txt`, `shortcodes.txt`). `important-notes.txt` and `instructions-for-ai-llm.txt` retain their inline content but now include a link to the txt file. Table of Contents updated accordingly.

### 14.3 *(2026-03-23)*
- **`[handschelle-smart]` – selector heading black**: "👤 Person auswählen" heading changed to "Bekannte Personendaten laden!" with black background and white text.
- **`[handschelle-smart]` – selector label updated**: Label renamed from "Bekannte Person" to "Bekannte Personendaten laden!".
- **`[handschelle-smart]` – note text colour black + bold**: The person-selector hint text now displays in black with bold weight for visibility.
- **`[handschelle-smart]` – loaded field data shown BOLD**: Locked/loaded fields (`hs-field-locked`) now render values in bold (`font-weight: bold`) so loaded data is clearly distinguishable from empty fields.
- **`[handschelle-smart]` – Personal section 2-column layout**: Personal section restructured into left (image) and right (personal data fields) columns using flexbox; responsive single-column on narrow screens.
- **`[handschelle-smart]` – image upload always in Personal left column**: Bild upload (when not loaded) and read-only Bild preview (when person loaded) are both placed in the left column of the Personal section.
- **README – Installation from GitHub moved**: Installation from GitHub section (with all sub-sections) removed from README; content lives in `installation-from-github.txt`.
- **README – Connect GitHub to AI Tools moved**: Connect GitHub to AI Tools section removed from README; content moved to new `Connect GitHub to AI Tools.txt`.

### 14.2 *(2026-03-23)*
- **`[handschelle-smart]` – section headings styled red**: `⚖ Neue Straftat melden`, `🏛 Politik`, `📱 Social-Media Links`, and `📋 Personal` headings now display with a red background and white text to visually distinguish form sections.
- **`[handschelle-smart]` – renamed Straftat heading**: "⚖ Details zur Straftat" renamed to "⚖ Neue Straftat melden. (Neue Einträge werden vor Freigabe überprüft!)" to inform users that new entries are reviewed before approval.
- **`[handschelle-smart]` – person-selector note updated**: Hint text updated to "Wähle eine bereits bekannte Person aus dem Dropdown, um deren Daten zu übernehmen. Alle geladenen Felder werden schreibgeschützt!".

### 14.1 *(2026-03-23)*
- **New shortcode `[handschelle-pie-partei-filter]`**: Pie chart showing entries per party (same as `[handschelle-pie-partei]`) with an "Nur Aktive anzeigen" checkbox; when checked, the chart filters to only entries where `status_aktiv = 1`.
- **`[handschelle-wanted]` – exclude iNAKTIV persons**: The wanted-poster shortcode now adds `AND status_aktiv = 1` to its query so persons marked as inactive are never selected for display.

### 14.0 *(2026-03-23)*
- **Version bump**: Bumped version from 12.Alpha.04 to 14.0; updated README.md and all .txt files to reflect the new version.

### 12.Alpha.02 *(2026-03-22)*
- **Fix: Weitere Straftaten not saving (MySQL 8.0)**: Removed invalid `DEFAULT ''` from `TEXT NOT NULL` columns in `create_table()` and `create_offences_table()` — MySQL 8.0 strict mode rejects this syntax (Error 1101), causing the offences table to never be created and all `insert_offence()` calls to silently fail. `maybe_upgrade_table()` now always calls `create_offences_table()` before the version-check early-return so the table is self-healing. The `recreate` admin action now also creates the offences table.

### 12.Alpha.01 *(2026-03-22)*
- **Version bump**: Bumped version from Final-11 to 12.Alpha.01; updated README.md and all .txt files to reflect the new version.

### Final-11 *(2026-03-19)*
- **Version bump**: Bumped version from Final-10 to Final-11; updated README.md and all .txt files to reflect the new version.

### Final-10 *(2026-03-19)*
- **Version milestone**: Marked version 10.1 as Final-10 to signify the stable, production-ready final release of the version-10 feature set.

### 10.1 *(2026-03-18)*
- **"Straftat melden!" link on every card**: All cards now show a permanent `⚠ Straftat melden!` mailto link (`info@die-handschelle.com`) with a pre-filled subject line (`Straftat melden - <Name> - <Partei>`). Visible to all visitors including guests (previously "Eintrag melden!" was logged-in only). Added `.hs-card-melden` and `.hs-melden-link` CSS classes.

### 10.0 *(2026-03-18)*
- **Version milestone**: Consolidated all changes from 9.3–9.6 into stable release 10.0. No functional changes; version bumped to mark the multiple-offences feature set as production-ready.

### 9.6 *(2026-03-18)*
- **`[handschelle-privacy]` shortcode**: New shortcode renders the bilingual Datenschutz / Privacy chapter as styled HTML (two cards, DE + EN). Covers GDPR legal basis, stored / not-stored data, guest anonymisation, and deletion-request contact. CSS classes: `.hs-privacy`, `.hs-privacy-section`, `.hs-privacy-heading`, `.hs-privacy-divider`.

### 9.5 *(2026-03-18)*
- **CSS – offence buttons**: Added `.hs-offence-remove-btn` / `.hs-offence-inline-remove` styles (red delete button; WP's `.button` class is admin-only and not available on the frontend). Added `.hs-add-offence-inline-btn` for the frontend inline-edit panel. Added `.hs-card-extra-offence p` paragraph style to match `.hs-card-straftat p`.

### 9.4 *(2026-03-18)*
- **Backup & Restore: Offences included**: `backup_full()` now exports a second file `handschelle-offences.csv` inside the ZIP (columns: `entry_id`, `straftat`, `urteil`, `status_straftat`, `link_quelle`, `aktenzeichen`, `bemerkung`). `restore_full()` now reads this file and re-inserts all additional offences after the main entries are restored, mapping old entry IDs to newly-assigned IDs via an `$entry_id_map`. The success message now reports offence count separately. Backward-compatible: old backups without `handschelle-offences.csv` restore main entries normally with zero offences.

### 9.3 *(2026-03-18)*
- **Multiple Offences per Person**: Each person entry can now have any number of additional offences. A new table `wp_die_handschelle_offences` stores the extra offences (fields: `straftat`, `urteil`, `status_straftat`, `link_quelle`, `aktenzeichen`, `bemerkung`). The primary offence remains in the main table for full backward compatibility.
- **Admin form**: New "⚖ Weitere Straftaten" section with existing offences shown as editable rows, a delete button per row, and an "Add further offence" button (JS-based cloning of a template row). Works for both "New Entry" and "Edit Entry" pages.
- **Frontend inline-edit**: Same add/remove functionality available in the per-card inline edit panel.
- **Card display**: `render_card()` fetches and renders all additional offences below the primary offence, each with its own status badge, source link (logged-in users only), and optional remarks. Each is labelled "Straftat 2", "Straftat 3", etc.
- **Cascade delete**: Deleting a main entry also deletes all its associated offences.
- **DB migration**: `maybe_upgrade_table()` now creates the offences table if missing. Existing data is untouched.

### 9.2 *(2026-03-16)*
- **Website-Icon für Gäste**: Nicht eingeloggte Besucher sehen statt des Personenfotos das Website-Icon (`get_site_icon_url`). Das Foto-Bild ist nicht verlinkt – der Klick-Link zum Detailprofil entfällt für Gäste.

### 9.1 *(2026-03-16)*
- **Karten-Footer für Gäste ausgeblendet**: Nicht eingeloggte Besucher sehen keine Links mehr in Karten (keine Quelle, keine E-Mail, keine Suchmaschinen, kein Social-Media, kein „Eintrag melden"). Der gesamte `hs-card-footer` wird nur noch gerendert, wenn `is_user_logged_in()` gilt.

### 9.0 *(2026-03-16)*
- **Gast-Karten: Website-Icon statt Profilfoto**: Nicht eingeloggte Besucher sehen in `render_card` das WordPress-Site-Icon (`get_site_icon_url(96)`) als Kartenbild statt des echten Profilfotos. Kein Site-Icon gesetzt → Fallback auf 👤. Klasse `hs-card-img-siteicon` sorgt für `object-fit:contain` und Innenabstand. Der Bearbeiten-Button prüft jetzt korrekt `publish_posts` über `$is_author`.

### 8.9 *(2026-03-16)*
- **Name-Datenschutz (global)**: Neue Hilfsfunktion `hs_display_name()` in `helpers.php`. Nicht eingeloggte Besucher sehen statt des Namens `████████`. Gilt für alle Shortcodes: Karten (`render_card`), alle Ticker-Varianten, Wordcloud, Statistik-Tabelle, Bilder-Galerie und Namens-Dropdowns.

### 8.8 *(2026-03-16)*
- **`[handschelle-ticker-icons]`**: Neuer Shortcode – identisch wie `[handschelle-straftat-link]`, aber mit kleinem rundem Profilbild (28 px) vor dem Namen; kein Bild → Initiale des Namens als Platzhalter (dunkelblauer Kreis). Attribute `speed` und `page` wie bei `[handschelle-straftat-link]`.

### 8.7 *(2026-03-16)*
- **`[handschelle-straftat-link]`**: Jeder Eintrag ist jetzt als Ganzes ein klickbarer Link (`<a>`) auf `?hs_name_name=<name>`; einzelne Partei- und Name-Links entfernt zugunsten des Item-Links.

### 8.6 *(2026-03-16)*
- **`[handschelle-result]`**: Neuer Shortcode – zeigt Eintrags-Karten für `?hs_name_name=<name>`; zeigt nichts an, wenn kein Name übergeben wurde oder keine freigegebenen Einträge vorhanden sind. Gedacht als Zielseite für Links aus `[handschelle-straftat-link]`.

### 8.5 *(2026-03-16)*
- **Straftat-Link-Ticker** `[handschelle-straftat-link]`: Neuer Shortcode – identisches Layout wie `[handschelle-straftat]`, aber Name und Partei sind klickbare Links; Name verlinkt auf `?hs_name_name=<name>`, Partei auf `?hs_name_partei=<partei>`; optionales Attribut `page` für Ziel-URL (Standard: aktuelle Seite).
- **CSS**: `.hs-st-link` – dezenter Unterstrich-Stil (dotted) für Ticker-Links; Hover-Effekt (Opacity).

### 8.4 *(2026-03-16)*
- **Registrierungsformular erweitert**: `[handschelle-register]` hat jetzt zusätzliche optionale Felder: Vorname, Nachname, Spitzname, Webseite; Vorname/Nachname nebeneinander (2-Spalten-Grid, responsive); gespeichert via `wp_update_user()` nach Kontoerstellung.

### 8.3 *(2026-03-16)*
- **Benutzer-Freischaltung**: Neue Registrierungen erhalten den Status `pending` (User-Meta `hs_user_status`); Login ist gesperrt bis zur Freischaltung; Admin wird per E-Mail benachrichtigt.
- **Login-Sperre**: `authenticate`-Filter blockiert `pending`- und `deactivated`-Konten mit jeweils eigenem Fehlertext.
- **Admin-Menü: 👥 Benutzer** (`Die-Handschelle → Benutzer`): Übersicht aller Benutzer mit Status-Badge; Aktionen: Freischalten, Deaktivieren, Löschen; Pending-Zähler als Badge im Menütitel; Admins sind vor Änderungen geschützt.

### 8.0 *(2026-03-16)*
- **README**: Shortcodes overview table sorted A-Z; added missing `[handschelle-ticker]` entry; fields in Person, Crime/Legal and Social Media schema groups sorted A-Z; `straftat` type corrected to TEXT (no limit); version constant updated to 8.0; shortcodes.php count corrected to 24.
- **To Do**: Added To Do section (after Einleitung) with planned features: Mailing-Liste, Multilanguage, Multiple Offences, Multiuser.

### 7.9 *(2026-03-16)*
- **Straftat-Feld**: Textfarbe auf Schwarz (#000000) gesetzt in allen Anzeigebereichen (Karte, Bild-Tooltip, Ticker); 200-Zeichen-Limit entfernt (kein maxlength, kein substr-Truncation); vollständiger Text wird in beiden Ticker-Shortcodes angezeigt.

### 7.8 *(2026-03-16)*
- **Registrierungs-Formular** `[handschelle-register]`: Neues Registrierungs-Shortcode mit Feldern für Benutzername, E-Mail und Passwort (mit Bestätigung); respektiert die WordPress-Einstellung „Jeder kann sich registrieren", zeigt passende Fehlermeldungen und sendet nach Erfolg E-Mails an Nutzer und Admin.

### 7.7 *(2026-03-16)*
- **Login-Formular** `[handschelle-login]`: Neues Anmelde-Shortcode mit Benutzername/Passwort-Eingabe, „Angemeldet bleiben"-Checkbox, „Passwort vergessen"-Link und optionalem `redirect`-Attribut; eingeloggten Nutzern wird eine Willkommensnachricht mit Abmelden-Button angezeigt.

### 7.6 *(2026-03-16)*
- **Straftat-Ticker** `[handschelle-straftat]`: Neuer News-Ticker mit weißem Hintergrund und schwarzem Rahmen; zeigt Partei (rot), Name (schwarz), Straftat (schwarz) und Status Straftat (rot) aller freigegebenen Einträge. Geschwindigkeit über Attribut `speed` einstellbar.

### 7.5 *(2026-03-16)*
- **News-Ticker** `[handschelle-ticker]`: Neuer Shortcode mit horizontalem CSS-Laufband; zeigt Name, Partei und Straftat aller freigegebenen Einträge. Geschwindigkeit über Attribut `speed` (Sekunden, Standard 40, Minimum 5) einstellbar.

### 7.4 *(2026-03-16)*
- **Profilfelder in Karten & Formularen**: Neue Felder `geburtsland`, `email_privat`, `email_oeffentlich` und `spitzname` werden nun in Frontend-Karten, Inline-Edit-Formular und Admin-Übersicht angezeigt.

### 7.3 *(2026-03-15)*
- **Domain & E-Mail update**: Changed all references from `www.die-handschelle.de` → `www.die-handschelle.com` and `Info@die-handschelle.de` → `info@die-handschelle.com` across shortcodes, README, and all doc files; also fixed typo `info@hanschelle.com` → `info@die-handschelle.com` in the "Eintrag melden" mailto link.

### 7.2 *(2026-03-15)*
- **Fix Backup/Restore image mapping**: Converted `restore_full()` from fixed numeric column indices to header-based mapping (same approach as CSV import); old backups created before the `verstorben`/`dod` columns were added now restore correctly instead of assigning `bild` to the wrong column.
- **Fix Backup/Restore bild validation**: After attempting ID remapping, if the attachment ID is not found in the remap table and does not exist on the current site, `bild` is cleared instead of storing a stale/wrong ID.
- **Fix image display after restore/import**: `handschelle_get_image_url()` now falls back from `medium` → `full` → `wp_get_attachment_url()` so images are always visible even when WP thumbnail sizes weren't regenerated.
- **Fix image replacement on edit**: Editing an entry and uploading a new image now deletes the old attachment from the media library (admin edit and frontend inline edit), preventing orphaned files and wrong image references.

### 7.1 *(2026-03-15)*
- **Cards & Forms**: Added `verstorben`/`dod` and `bemerkung_person` to frontend cards (display), frontend submission form, and inline edit form; JS toggle converted to delegated class-based handler (`hs-verstorben-cb` / `hs-dod-row`) so it works across all form instances on the same page.
- **CSS**: Added `.hs-badge-verstorben` (grey badge) and `.hs-card-bemerkung-person` styles.

### 7.0 *(2026-03-15)*
- **Fix Urteil maxlength in shortcodes**: Frontend submission form and frontend edit form both had `maxlength="50"` for `urteil`; corrected to `maxlength="200"` to match DB schema and admin form.

### 6.9 *(2026-03-15)*
- **DoD / Verstorben**: Added `verstorben` checkbox and `dod` (date of death) date field to Eintragsdetails; DoD field is shown/hidden via JS when checkbox is toggled.
- **Bemerkung zur Person**: New `bemerkung_person` text field (max. 500 chars) added to Eintragsdetails section for person-level remarks.
- **Urteil erweitert**: `urteil` field expanded from 50 to 200 characters in DB schema, form, sanitizer, and CSV import/export.

### 6.8 *(2026-03-15)*
- **Table of Contents**: Added TOC after the header metadata block with links to all `##` and `###` sections

### 6.7 *(2026-03-15)*
- **Restore .txt content to README**: All documentation sections (Installation, Build, Shortcodes, Fields/Schema, Code Reference, Plugin Structure, AI Instructions, Recreate from Scratch) merged back inline into README.md; Dokumentation link section removed

### 6.6 *(2026-03-15)*
- **Important Notes in README**: Moved "Important Notes" section back into README.md (was extracted to important-notes.txt in 6.5)

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
- **Eintrag melden**: Every card now has a `⚠️ Eintrag melden!` mailto link in the footer — opens a pre-addressed e-mail to `info@die-handschelle.com` with subject `Meldung - <Name> - <Partei>`
- **Bilder-Galerie**: Hover tooltip and click-link removed from gallery images — images display as plain `<img>` tags with name/crime captions only
- **Edit-Button Sichtbarkeit**: Inline-edit button and panel on frontend cards are now visible only to users with role **Author or higher** (`publish_posts` capability) — Subscribers and Contributors no longer see the edit controls

### 3.09 *(2026-03-14)*
- **Hintergrundfarbe**: `--hs-bg` auf `#f0f0f0` (neutrales Hellgrau) geändert; alle Eingabefelder und Dropdowns nutzen `var(--hs-bg)` statt #fafafa
- **Bugfix – Neue Einträge nicht gespeichert**: Bei Nonce-Fehler (z. B. Cache) erhält der Nutzer jetzt eine sichtbare Fehlermeldung (`⚠️ Fehler beim Speichern. Bitte Seite neu laden…`) statt stiller Weiterleitung; `$wpdb->insert()`-Ergebnis wird geprüft — Erfolgsmeldung erscheint nur bei tatsächlich gespeichertem Eintrag; `datum_eintrag` fällt jetzt auch bei leerem String auf das aktuelle Datum zurück (`?:` statt `??`)
- **Bugfix – Backup/Restore Feldzuordnung**: Im Restore-Code waren alle CSV-Spalten ab Index 4 um 2 Positionen verschoben (`geburtsort` und `geburtsdatum` wurden übersprungen); `bild`-ID wurde aus Spalte 4 (`geburtsort`) statt aus Spalte 6 gelesen → falsche Bildzuordnung; `geburtsort`, `geburtsdatum`, `sm_linkedin`, `sm_xing`, `sm_truth_social` wurden nie wiederhergestellt; `freigegeben` wurde aus Spalte 23 (`sm_wikipedia`) statt aus Spalte 28 gelesen — alles korrigiert

### 3.08 *(2026-03-14)*
- **`[handschelle-asc]`**: Output is now horizontally centered (`justify-content: center`)

### 3.07 *(2026-03-14)*
- **`[handschelle-disclaimer]`**: E-Mail → `info@die-handschelle.com`, Website → `www.die-handschelle.com`, Buy-Me-A-Coffee → `buymeacoffee.com/dorfmuellersak47`, tagline in quotation marks
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

## Installation from GitHub

See [installation-from-github.txt](installation-from-github.txt)

---

## Shortcodes

All shortcodes output HTML and can be placed on any WordPress page or post.

### Overview

| Shortcode | Description |
|---|---|
| `[handschelle]` | Frontend submission form for new entries |
| `[handschelle-anzeige]` | Display all approved entries as cards (no pagination by default) |
| `[handschelle-asc]` | Horizontal centered list: Partei (Anzahl), alphabetical, no header |
| `[handschelle-asc-link]` | Same as `[handschelle-asc]` but party names are clickable links (`?hs_partei=`) with a hover tooltip listing all persons |
| `[handschelle-bilder]` | Image gallery – clickable photos, name + crime caption (black), hover tooltip |
| `[handschelle-disclaimer]` | Copyright / contact notice |
| `[handschelle-karte]` | Single entry card by ID: `[handschelle-karte id="5"]` |
| `[handschelle-login]` | WordPress-Anmeldeformular; zeigt nach Login eine Willkommensmeldung mit Abmelden-Button |
| `[handschelle-name]` | Person name search dropdown only |
| `[handschelle-name-anzeige]` | Name dropdown – shows cards for selected person |
| `[handschelle-name-partei]` | Party dropdown – shows cards for selected party |
| `[handschelle-partei]` | Party search dropdown only |
| `[handschelle-result]` | Zeigt Eintrags-Karten für `?hs_name_name=<name>`; zeigt nichts, wenn kein Name bekannt |
| `[handschelle-pie-partei]` | Pie chart: approved entries per party (Anzahl Partei) — uses Chart.js 4 |
| `[handschelle-privacy]` | Renders the bilingual Datenschutz / Privacy section (DE + EN) |
| `[handschelle-register]` | Registrierungsformular: Benutzername, Vorname, Nachname, Spitzname, E-Mail, Webseite, Passwort; neues Konto erhält Status `pending` – Login erst nach Admin-Freischaltung |
| `[handschelle-statistik]` | Statistics table with bar chart per party (party names are links) |
| `[handschelle-statistik-name]` | Table: person name / entry count |
| `[handschelle-statistik-nolink]` | Same as `[handschelle-statistik]` but without links on party names |
| `[handschelle-statistik-ol]` | Ordered list: party – number of distinct names |
| `[handschelle-statistik-partei]` | Table: party / entry count (party links to filter) |
| `[handschelle-straftat]` | Scrolling ticker: Partei · Name · full Straftat · Status — white background, black border, black crime text |
| `[handschelle-straftat-link]` | Same as `[handschelle-straftat]` but Name is a link (`?hs_name_name=`) and Partei is a link (`?hs_name_partei=`); optional `page` attribute to set target URL |
| `[handschelle-straftaten]` | Straftat dropdown — select a crime to show full entry cards for all matching persons (searches main table and offences table) |
| `[handschelle-suche]` | Full-text search field + Party and Person dropdowns |
| `[handschelle-ticker]` | Scrolling news ticker: Name · Party · full Straftat text |
| `[handschelle-ticker-icons]` | Wie `[handschelle-straftat-link]` mit Profilbild-Icon (oder Initial-Platzhalter) vor dem Namen |
| `[wordcloud-name]` | Word cloud of person names (sized by entry count) — shows Name (Partei) |
| `[wordcloud-urteil]` | Word cloud of verdicts (`urteil`) sized by frequency |

---

### `[handschelle]`

Renders a public submission form. New submissions are saved with `freigegeben = 0` (not approved) and must be approved by an admin before they appear on the site.

```
[handschelle]
```

**Form fields presented to visitors:**
- Name (required)
- Profession
- Birth date & birth place
- Party, position in party
- Parliament type & name
- Active status
- Crime description (required)
- Crime status, verdict, source link, case file number
- Notes / remarks
- Social media links (Facebook, YouTube, Twitter/X, personal, homepage, Wikipedia, LinkedIn, Xing, Truth Social, other)
- Photo upload

---

### `[handschelle-anzeige]`

Displays all approved entries (`freigegeben = 1`) as responsive cards. Supports optional **text search** via URL parameter. Pagination is disabled by default (`limit=0`).

```
[handschelle-anzeige]
[handschelle-anzeige partei="CDU"]
[handschelle-anzeige limit="12"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `partei` | string | — | Filter by political party name |
| `name` | string | — | Filter by person name |
| `limit` | int | `0` | Cards per page (0 = all, disables pagination) |

**URL parameters:**

| Parameter | Description |
|---|---|
| `hs_search` | Full-text search (searches name, party, and crime description) |
| `hs_paged` | Page number when `limit > 0` |

---

### `[handschelle-asc]`

Compact horizontal centered list of all parties with entry count, sorted A→Z. No header, small font. Ideal for sidebars or page footers.

```
[handschelle-asc]
```

**Example output:** `AfD (12) · CDU (8) · FDP (3) · SPD (5)`

---

### `[handschelle-asc-link]`

Same as `[handschelle-asc]` but with two enhancements:

- Party names are **clickable links** — navigates to `?hs_partei=<party>` on the current page
- A **hover tooltip** lists all person names in that party (one per line)

```
[handschelle-asc-link]
```

**Example output:** `AfD (12) · CDU (8) · FDP (3)` — each party name is a link; hovering shows the persons

---

### `[handschelle-bilder]`

Responsive image gallery of all approved entries that have a photo.

- Images are displayed at max 300×300 px (aspect ratio preserved)
- **Name** and **Straftat** shown as caption below each image
- **Hover tooltip** shows all available person data (party, profession, parliament, crime, status, verdict, case number)
- **Clicking an image** navigates to `?hs_name=<name>` — shows the person's detail cards

```
[handschelle-bilder]
[handschelle-bilder link="/personen/"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `link` | string | current page | Base URL of the target page with `[handschelle-name]`. If empty, uses the current page. |

---

### `[handschelle-disclaimer]`

Copyright / contact block.

```
[handschelle-disclaimer]
```

**Output:**
> **Die-Handschelle © 2026**
> „Wer in unseren Parlamenten ist oder war kriminell?" Eine Datenbank der Straftaten.
> [www.die-handschelle.com](https://www.die-handschelle.com) · [info@die-handschelle.com](mailto:info@die-handschelle.com) · ☕ Unterstützen

---

### `[handschelle-karte]`

Displays a single entry card by database ID. Only shows approved entries.

```
[handschelle-karte id="5"]
```

| Attribute | Type | Description |
|---|---|---|
| `id` | int | Database ID of the entry to display |

**Card contents:**
- Profile photo — **clickable**, links to `?hs_name=` on the same page
- Name, profession, birth date + age, birth place
- Party, position, parliament
- Crime description & status badge
- Verdict, case number
- Footer: source link · Google · Qwant · DuckDuckGo · Bing · Abgeordnetenwatch · social media icons

---

### `[handschelle-login]`

Zeigt ein WordPress-Anmeldeformular. Ist der Nutzer bereits eingeloggt, wird stattdessen eine Willkommensnachricht mit Abmelden-Button angezeigt.

```
[handschelle-login]
[handschelle-login redirect="/mein-bereich/"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `redirect` | string | aktuelle Seite | URL, zu der nach dem Login weitergeleitet wird |

**Verhalten:**
- Nicht eingeloggt → Login-Formular mit Benutzername/Passwort, „Angemeldet bleiben"-Checkbox und „Passwort vergessen"-Link
- Eingeloggt → Willkommenstext + Abmelden-Button
- Fehlgeschlagene Anmeldung → Fehlermeldung (`?hs_login_error=1`)

---

### `[handschelle-name]`

Renders only the Person name search dropdown. After selection shows all cards for that person plus **Google, Qwant, DuckDuckGo, Bing, Abgeordnetenwatch** search buttons.

```
[handschelle-name]
```

---

### `[handschelle-name-anzeige]`

Name dropdown + result cards for the selected person. Includes Google, Qwant, DuckDuckGo, Bing, Abgeordnetenwatch buttons.

```
[handschelle-name-anzeige]
```

---

### `[handschelle-name-partei]`

Party dropdown + result cards for the selected party.

```
[handschelle-name-partei]
```

---

### `[handschelle-partei]`

Renders only the Party search dropdown. After selection shows all cards for that party.

```
[handschelle-partei]
```

---

### `[handschelle-pie-partei]`

Pie chart showing the number of approved entries per party (Anzahl Partei).
Uses **Chart.js 4** (loaded from CDN, footer). Tooltip shows name, count, and percentage; legend on the right.

```
[handschelle-pie-partei]
```

---

### `[handschelle-register]`

Zeigt ein Registrierungsformular für neue WordPress-Nutzer. Funktioniert nur, wenn in den WordPress-Einstellungen unter **Einstellungen → Allgemein** die Option „Jeder kann sich registrieren" aktiviert ist.

Neue Konten erhalten den Status **`pending`** und können sich nicht einloggen, bis ein Admin sie im Menü **Die-Handschelle → 👥 Benutzer** freischaltet.

```
[handschelle-register]
[handschelle-register redirect="/willkommen/"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `redirect` | string | aktuelle Seite | URL, zu der nach der Registrierung weitergeleitet wird |

**Felder:**

| Feld | Pflicht | Gespeichert als |
|---|---|---|
| Benutzername | ✅ | `user_login` |
| Vorname | — | `first_name` |
| Nachname | — | `last_name` |
| Spitzname | — | `nickname` (fällt auf Benutzername zurück) |
| E-Mail-Adresse | ✅ | `user_email` |
| Webseite | — | `user_url` |
| Passwort (min. 6 Zeichen) | ✅ | — |
| Passwort wiederholen | ✅ | — |

**Verhalten:**
- Registrierungen deaktiviert → Hinweismeldung
- Eingeloggt → Hinweistext mit aktuellem Benutzernamen
- Erfolgreiche Registrierung → Hinweismeldung „wartet auf Freischaltung" + E-Mail-Benachrichtigung an Admin
- Fehler (doppelter Name, E-Mail vergeben, Passwörter ungleich) → jeweils passende Fehlermeldung
- Pending-Konten → Login gesperrt mit Hinweis
- Deaktivierte Konten → Login gesperrt mit Hinweis

---

### `[handschelle-privacy]`

Renders the bilingual **Datenschutz / Privacy** chapter as styled HTML. Displays two cards — one German, one English — covering legal basis, data stored, data not stored, guest anonymisation, and contact for corrections / deletion requests.

```
[handschelle-privacy]
```

**Displayed content:**
- 🇩🇪 Legal basis (Art. 6(1)(f) DSGVO), stored / not-stored data lists, guest anonymisation (`████████`), deletion request contact
- 🇬🇧 Same content in English

No attributes.

---

### `[handschelle-result]`

Zeigt Eintrags-Karten für die über den URL-Parameter `hs_name_name` übergebene Person. Zeigt **nichts** an, wenn kein Name übergeben wurde oder keine freigegebenen Einträge vorhanden sind.

Gedacht als Zielseite für Links aus `[handschelle-straftat-link]`.

```
[handschelle-result]
```

**Kein Attribut erforderlich.** Der Name kommt ausschließlich aus dem URL-Parameter.

| URL-Parameter | Quelle | Beschreibung |
|---|---|---|
| `?hs_name_name=<name>` | `[handschelle-straftat-link]` | Name der anzuzeigenden Person |

**Typische Kombination:**

```
<!-- Ticker-Seite -->
[handschelle-straftat-link page="/person/"]

<!-- Zielseite /person/ -->
[handschelle-result]
```

---

### `[handschelle-statistik]`

Statistics table: entries per party with bar chart. Party names link to `?hs_partei=`.

```
[handschelle-statistik]
```

---

### `[handschelle-statistik-name]`

Table: Person name → entry count.

```
[handschelle-statistik-name]
```

---

### `[handschelle-statistik-nolink]`

Same as `[handschelle-statistik]` but party names are plain text (no links).

```
[handschelle-statistik-nolink]
```

---

### `[handschelle-statistik-ol]`

Numbered list: party — count of distinct person names, sorted descending.

```
[handschelle-statistik-ol]
```

**Example:**
1. CDU – 5 Namen
2. SPD – 3 Namen
3. AfD – 2 Namen

---

### `[handschelle-statistik-partei]`

Table: Party → entry count. Party names link to `?hs_name_partei=`.

```
[handschelle-statistik-partei]
```

---

### `[handschelle-straftat]`

Horizontally scrolling news ticker displaying all approved entries. Each item shows **Partei**, **Name**, **Straftat** and **Status Straftat** with a white background and black border.

```
[handschelle-straftat]
[handschelle-straftat speed="60"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `speed` | int | `40` | Scroll duration in seconds (lower = faster, minimum 5) |

**Color scheme:**

| Element | Color |
|---|---|
| Background | white |
| Border | black |
| Partei | red |
| Name | black |
| Straftat | black |
| Status Straftat | red |

---

### `[handschelle-straftat-link]`

Same as `[handschelle-straftat]` but **Name** and **Partei** are clickable links.

```
[handschelle-straftat-link]
[handschelle-straftat-link speed="60"]
[handschelle-straftat-link page="/straftaten/"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `speed` | int | `40` | Scroll duration in seconds (lower = faster, minimum 5) |
| `page` | string | aktuelle Seite | Target URL for all links |

**Link targets:**

| Click on | URL parameter added |
|---|---|
| Name | `?hs_name_name=<name>` (read by `[handschelle-name-anzeige]`) |
| Partei | `?hs_name_partei=<partei>` (read by `[handschelle-name-partei]`) |

---

### `[handschelle-straftaten]`

Dropdown of all distinct **Straftat** values. Selecting one shows full entry cards for every person linked to that crime. Searches both the main `straftat` field and additional offences in `wp_die_handschelle_offences`.

```
[handschelle-straftaten]
```

| URL Parameter | Description |
|---|---|
| `?hs_straftat=<value>` | Pre-selects the matching crime in the dropdown and displays results |

---

### `[handschelle-suche]`

Renders a **full-text search field** and two auto-submitting dropdowns (Party and Person name). Combine with `[handschelle-anzeige]` on the same page.

```
[handschelle-suche]
```

---

### `[handschelle-ticker]`

Horizontally scrolling news ticker showing all approved entries. Each item displays **Name**, **Partei**, and full **Straftat** text.

```
[handschelle-ticker]
[handschelle-ticker speed="60"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `speed` | int | `40` | Scroll duration in seconds (lower = faster, minimum 5) |

---

### `[handschelle-ticker-icons]`

Same as `[handschelle-straftat-link]` but each entry is prefixed with a small round profile photo (28 px). If no image is available, the first initial of the name is shown as a placeholder (dark-blue circle). Supports `speed` and `page` attributes.

```
[handschelle-ticker-icons]
[handschelle-ticker-icons speed="60" page="/person/"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `speed` | int | `40` | Scroll duration in seconds (lower = faster, minimum 5) |
| `page` | string | current page | Target URL for name / party links |

---

### `[wordcloud-name]`

Word cloud of all approved person names. Font size is proportional to the number of entries. Each word shows `Name (Partei)`; tooltip shows the exact count. Pure CSS/HTML, no external library.

```
[wordcloud-name]
```

---

### `[wordcloud-urteil]`

Word cloud of all distinct verdicts (`urteil`). Font size is proportional to frequency. Only entries with a non-empty verdict are included.

```
[wordcloud-urteil]
```

### Typical Page Setup

```
<!-- Main display page -->
[handschelle-suche]
[handschelle-anzeige]

<!-- Gallery page -->
[handschelle-bilder link="/person/"]

<!-- Statistics page -->
[handschelle-statistik]
[handschelle-asc]

<!-- Submit page -->
[handschelle]

<!-- Person detail page (target for bilder links) -->
[handschelle-name]
```

---

## Build Package

See [build-package.txt](build-package.txt)

---

## Code Reference

See [code-reference.txt](code-reference.txt)

---

## Fields & Database Schema

See [fields-database-schema.txt](fields-database-schema.txt)

---

## Next Commands

See [Next-Commands.txt](Next-Commands.txt)

---

## Plugin Structure

See [plugin-structure.txt](plugin-structure.txt)

---

## Prompt

See [prompt.txt](prompt.txt)

---

## Shortcodes Reference

See [shortcodes.txt](shortcodes.txt)

---

## Instructions for AI / LLM

> This section is written for AI assistants (Claude, GPT, Gemini, etc.) that contribute to this codebase. Follow these rules every time you make changes.
> Full instructions: [instructions-for-ai-llm.txt](instructions-for-ai-llm.txt)

### Version Bumping

**Bump the version by `0.1` with every commit / merge.**

Update the version string in **all three places** — they must always be in sync:

| File | Location | Example |
|---|---|---|
| `die-handschelle.php` | `* Version:` header comment (line ~6) | `* Version:     6.4` |
| `die-handschelle.php` | `HANDSCHELLE_VERSION` constant (line ~24) | `define( 'HANDSCHELLE_VERSION', '6.4' );` |
| `includes/admin.php` | `<span class="hs-version">` in admin header | `<span class="hs-version">6.4</span>` |
| `README.md` | Version row in the header table | `\| **Version** \| 6.4 \|` |

**How to calculate the new version:**
Take the current version shown in `README.md` → add `0.1` → round to one decimal place.
Examples: `6.1 → 6.2`, `6.9 → 7.0`, `7.0 → 7.1`.

### Adding a Release Note

Every commit must add a bullet point under the matching version heading in the **Release Notes** section of `README.md`:

```markdown
### 6.2 *(YYYY-MM-DD)*
- **Feature name**: Short description of what changed and why.
```

If the version heading already exists (e.g. multiple changes in one session), append bullet points to it.

### Shortcode Checklist

When adding a new shortcode:

1. Register it in `Handschelle_Shortcodes::__construct()` in `includes/shortcodes.php`
2. Implement the method in the same class
3. Add CSS in `assets/css/handschelle.css`
4. Add a row to the **Shortcodes Overview** table in `README.md`
5. Add a detailed section under `## Shortcodes` in `README.md`

### Database / Schema Changes

When adding a new column:

1. Add it to `Handschelle_Database::create_table()` in `includes/database.php`
2. Add it to `Handschelle_Database::maybe_upgrade_table()` so existing installs migrate automatically
3. Update the **Fields / Database Schema** table in `README.md`

### General Rules

- **Never skip the version bump** — every push must increment the version.
- **No destructive migrations** — `maybe_upgrade_table()` may only add columns, never drop or rename them.
- All queries use `$wpdb` prepared statements — never concatenate user input into SQL.
- All output uses `esc_html()` / `esc_url()` / `esc_attr()` — never echo raw data.
- New admin pages must be added to the **Admin Menu Structure** table in `README.md`.
- Keep German labels in UI, English in code (variable names, comments, README).

---

## Datenschutz / Privacy

### Datenschutz (Deutsch)

Das Plugin speichert ausschließlich Informationen über **öffentliche Mandatsträger** (z. B. Abgeordnete, Bürgermeister, Minister), die im Zusammenhang mit rechtskräftig verurteilten Straftaten oder laufenden Strafverfahren stehen. Die Verarbeitung erfolgt auf Grundlage des **berechtigten öffentlichen Interesses** gemäß Art. 6 Abs. 1 lit. f DSGVO sowie der Informationsfreiheit.

**Gespeicherte Daten:**
- Name und Funktion der Person (öffentliches Amt)
- Partei und Parlament
- Art und Status der Straftat (nur gerichtlich relevante Informationen)
- Quellen-URL (öffentlich zugängliche Nachrichtenartikel, Gerichtsurteile o. ä.)
- Optional: Profilfoto (nur öffentlich verfügbare Bilder)

**Nicht gespeicherte Daten:**
- Private Adressen, Telefonnummern oder E-Mail-Adressen
- Informationen über Privatpersonen ohne öffentliches Mandat
- Gesundheitsdaten oder andere besonders schutzwürdige Kategorien (Art. 9 DSGVO)

**Gastbesucher:** Nicht eingeloggte Besucher sehen Namen als `████████` (anonymisiert) und erhalten kein Profilfoto der eingetragenen Person — stattdessen wird das Website-Icon angezeigt.

**Datenmeldungen / Löschanfragen:** Fehleinträge oder Löschanfragen können per E-Mail an [info@die-handschelle.com](mailto:info@die-handschelle.com) gemeldet werden. Jeder Eintrag wird vor Veröffentlichung manuell geprüft (`freigegeben = 0` bis zur Freigabe durch einen Administrator).

---

### Privacy (English)

This plugin stores information exclusively about **public officeholders** (e.g. members of parliament, mayors, ministers) in connection with criminal convictions or ongoing criminal proceedings. Processing is based on **legitimate public interest** pursuant to Art. 6(1)(f) GDPR and the principle of freedom of information.

**Data stored:**
- Name and role of the person (public office)
- Party and parliament
- Type and status of the offence (court-relevant information only)
- Source URL (publicly accessible news articles, court rulings, etc.)
- Optionally: profile photo (publicly available images only)

**Data not stored:**
- Private addresses, phone numbers, or email addresses
- Information about private individuals without a public mandate
- Health data or other special categories under Art. 9 GDPR

**Guest visitors:** Non-logged-in visitors see names replaced with `████████` (anonymised) and do not see the person's profile photo — the site icon is shown instead.

**Corrections / Deletion requests:** Incorrect entries or deletion requests can be reported by email to [info@die-handschelle.com](mailto:info@die-handschelle.com). Every entry is manually reviewed before publication (`freigegeben = 0` until approved by an administrator).

---

## Connect GitHub to AI Tools

See [Connect GitHub to AI Tools.txt](Connect%20GitHub%20to%20AI%20Tools.txt)

---

## Recreate from Scratch

> Paste the prompt below into a new AI chat session to rebuild this plugin from scratch.

---

```
Build me a WordPress plugin called "Die-Handschelle" — a database of crimes
committed by political mandate holders (elected officials, MPs, councillors).

────────────────────────────────────────────────────────────────
GOAL
────────────────────────────────────────────────────────────────
A self-contained WordPress plugin that:
- Stores entries in a custom DB table (not posts/CPT)
- Lets visitors submit new entries via a frontend form
- Requires admin approval before entries go public
- Displays entries as responsive cards with search/filter
- Has a full wp-admin backend for CRUD, CSV import/export,
  image management, backup/restore, and DB maintenance

────────────────────────────────────────────────────────────────
FILE STRUCTURE
────────────────────────────────────────────────────────────────
die-handschelle/
├── die-handschelle.php           ← main plugin file
├── includes/
│   ├── helpers.php               ← parliament list, sanitizer, helpers
│   ├── database.php              ← Handschelle_Database (static CRUD class)
│   ├── image-handler.php         ← Handschelle_Image_Handler (upload + GD resize)
│   ├── admin.php                 ← Handschelle_Admin (all WP admin pages)
│   └── shortcodes.php            ← Handschelle_Shortcodes (all shortcodes)
└── assets/
    ├── css/handschelle.css       ← full stylesheet, CSS custom properties
    └── js/handschelle.js         ← frontend + admin JS (jQuery)

────────────────────────────────────────────────────────────────
DATABASE TABLE:  {prefix}die_handschelle  (31 fields)
────────────────────────────────────────────────────────────────
Core:      id, datum_eintrag, erstellt_am, geaendert_am
Person:    name (VARCHAR 50, required), beruf (50), geburtsort (100),
           geburtsdatum (DATE), bild (TEXT – WP attachment ID or URL),
           partei (50), aufgabe_partei (100),
           parlament (VARCHAR 100 – see list below),
           parlament_name (50), status_aktiv (TINYINT, default 1)
Crime:     straftat (VARCHAR 200, required),
           status_straftat (VARCHAR 50: "Ermittlungen laufen" |
             "Verurteilt" | "Eingestellt"),
           urteil (50), aktenzeichen (50),
           link_quelle (TEXT), bemerkung (TEXT)
Publish:   freigegeben (TINYINT, default 0)
Social:    sm_facebook, sm_youtube, sm_personal, sm_twitter,
           sm_homepage, sm_wikipedia, sm_linkedin, sm_xing,
           sm_truth_social, sm_sonstige  (all TEXT)
Indexes:   idx_freigegeben, idx_name, idx_partei

parlament options (23):
  Europäisches Parlament, Bundestag, Bundesrat,
  Landtag Baden-Württemberg, Landtag Bayern (Bayerischer Landtag),
  Abgeordnetenhaus Berlin, Brandenburgischer Landtag,
  Bürgerschaft Bremen, Bürgerschaft Hamburg, Hessischer Landtag,
  Landtag Mecklenburg-Vorpommern, Niedersächsischer Landtag,
  Landtag Nordrhein-Westfalen, Landtag Rheinland-Pfalz,
  Landtag des Saarlandes, Sächsischer Landtag,
  Landtag Sachsen-Anhalt, Schleswig-Holsteinischer Landtag,
  Thüringer Landtag, Stadtrat / Gemeinderat, Kreistag,
  Bezirkstag, Sonstiges

────────────────────────────────────────────────────────────────
SHORTCODES  (all in Handschelle_Shortcodes class)
────────────────────────────────────────────────────────────────
[handschelle]
  Public submission form. Saves with freigegeben=0.
  Fields: all person + crime + social fields, photo upload.
  Submit handler runs on 'init' hook (PRG pattern).
  Nonce verified; on failure show visible error, no silent redirect.

[handschelle-anzeige partei="" name="" limit="0"]
  Responsive card grid of approved entries.
  URL params: hs_search (full-text), hs_paged (pagination).
  limit=0 → show all (no pagination).

[handschelle-karte id="X"]
  Single entry card by DB id. Approved only.

[handschelle-suche]
  Full-text search input + auto-submit party dropdown +
  auto-submit name dropdown. Combine with [handschelle-anzeige].

[handschelle-partei]   Party dropdown only.
[handschelle-name]     Name dropdown only + search-engine buttons.

[handschelle-statistik]          Party stats table + bar chart, party names link to ?hs_partei=
[handschelle-statistik-nolink]   Same but no links.
[handschelle-statistik-partei]   Table: party → count (links to ?hs_name_partei=)
[handschelle-statistik-name]     Table: name → count
[handschelle-statistik-ol]       Ordered list: party – distinct name count

[handschelle-name-anzeige]   Name dropdown + cards for selected person
[handschelle-name-partei]    Party dropdown + cards for selected party

[handschelle-bilder link=""]
  Gallery of entries with photos. Plain <img> tags, no links, no hover.
  Name + Straftat as captions. max-height 300px, width auto.

[handschelle-asc]
  Horizontal centred list: Partei (Anzahl), A→Z, small font.

[handschelle-asc-link]
  Same as [handschelle-asc] but party names are clickable links (?hs_partei=)
  and a hover tooltip lists all person names per party.

[handschelle-disclaimer]
  Copyright block: Die-Handschelle © 2026, tagline, email, website,
  Buy-Me-A-Coffee link.

[wordcloud-name]
  Flex word cloud of person names. Font size ∝ entry count (0.85em–2.8em).
  Shows "Name (Partei)". 7-colour palette cycling. Hover: scale(1.1).

[wordcloud-urteil]
  Flex word cloud of distinct urteil values. Same sizing logic.
  Only entries where urteil != '' are included.

────────────────────────────────────────────────────────────────
ENTRY CARD  (render_card method, reused by all display shortcodes)
────────────────────────────────────────────────────────────────
Header (dark bg): profile photo (circle, 88px, links to ?hs_name=),
  name, profession, birth date + calculated age, birth place,
  party + role badge, parliament.
Body: crime description, status badge
  (Verurteilt=red / Ermittlungen laufen=orange / Eingestellt=grey),
  verdict, case number, source link, notes.
Footer: Quelle · Google · Qwant · DuckDuckGo · Bing ·
  Abgeordnetenwatch · social media icons (inline SVG, brand colours)
  · ⚠️ Eintrag melden! (mailto:info@die-handschelle.com?subject=Meldung - NAME - PARTEI)
Date: "Eingetragen am DD.MM.YYYY"
Edit button: visible only to users with publish_posts capability
  (Author, Editor, Administrator). Opens inline collapsible edit panel.
  Same panel includes search-engine buttons next to name field.

────────────────────────────────────────────────────────────────
IMAGE HANDLING
────────────────────────────────────────────────────────────────
- GD library required (check with extension_loaded('gd'))
- Upload via Handschelle_Image_Handler::handle_upload_and_resize()
- Rename to "{sanitize_title(name)}-HA.{ext}" (e.g. max-mustermann-HA.jpg)
- Resize to max height 450px, preserve aspect ratio, preserve PNG/GIF transparency
- Register as WP media attachment, store attachment ID in bild field
- Admin image field: WP Media Library picker (wp.media modal) OR direct upload
- handschelle_get_image_url($bild) resolves attachment ID or URL to display URL

────────────────────────────────────────────────────────────────
ADMIN BACKEND  (Handschelle_Admin class)
────────────────────────────────────────────────────────────────
Menu: Die Handschelle
  ├── Übersicht          Filter tabs (Alle / Ausstehend / Freigegeben + counts).
  │                      Table: checkbox, name, partei, straftat, status, age,
  │                      datum, actions (✏ Bearbeiten / ✅ Freigeben / 🗑 Löschen).
  │                      Bulk actions: freigeben / sperren / löschen.
  ├── + Neuer Eintrag    Full add form.
  ├── (Bearbeiten)       Hidden from sidebar; full edit form + search buttons.
  ├── Import / Export    CSV download (UTF-8 BOM, semicolon) + CSV upload/import.
  ├── Bilder             List of media images with ZIP export + ZIP import.
  ├── Backup & Restore   ZIP download (CSV + images/ + bild-map.json for ID remapping).
  │                      Restore: upload ZIP → truncate → re-import with ID remapping.
  │                      Requires confirmation checkbox.
  └── Datenbank          Truncate / Recreate / Drop table buttons.

────────────────────────────────────────────────────────────────
DATABASE CLASS  Handschelle_Database (all static, all $wpdb prepared)
────────────────────────────────────────────────────────────────
create_table()                  called on register_activation_hook
maybe_upgrade_table()           called on plugins_loaded; adds missing columns via dbDelta; never drops
get_all($args)                  freigegeben, partei, name, search, orderby, order, limit, offset
get_one($id)
insert($data)                   always sets freigegeben=0; returns new ID
update($id, $data)
delete($id)
count_all($args)
get_distinct_parteien()
get_distinct_namen()
truncate_table() / drop_table() / recreate_table()

────────────────────────────────────────────────────────────────
CSV FORMAT
────────────────────────────────────────────────────────────────
UTF-8 with BOM, semicolon delimiters (Excel-compatible).
31 columns in this order:
id · datum_eintrag · name · beruf · geburtsort · geburtsdatum · bild ·
partei · aufgabe_partei · parlament · parlament_name · status_aktiv ·
straftat · urteil · link_quelle · aktenzeichen · bemerkung · status_straftat ·
sm_facebook · sm_youtube · sm_personal · sm_twitter · sm_homepage ·
sm_wikipedia · sm_sonstige · sm_linkedin · sm_xing · sm_truth_social ·
freigegeben · erstellt_am · geaendert_am
Import is header-based (reads first row for column order) → backward-compatible
with old CSVs. id / erstellt_am / geaendert_am are auto-generated on import.

────────────────────────────────────────────────────────────────
CSS DESIGN SYSTEM  (assets/css/handschelle.css)
────────────────────────────────────────────────────────────────
:root {
  --hs-primary:  #1a1a2e;   /* dark navy */
  --hs-accent:   #c0392b;   /* red */
  --hs-accent-h: #e74c3c;   /* red hover */
  --hs-gold:     #f39c12;   /* gold */
  --hs-success:  #27ae60;   /* green */
  --hs-muted:    #7f8c8d;   /* grey */
  --hs-bg:       #f0f0f0;   /* light grey */
  --hs-card-bg:  #ffffff;
}
All selects: background #fff, color #000 (no transparency).
Card grid: CSS Grid, responsive, min-width 300px columns.
Prefix all classes with .hs- to avoid theme conflicts.
No external icon or font libraries — inline SVG for all brand icons.

────────────────────────────────────────────────────────────────
JAVASCRIPT  (assets/js/handschelle.js, jQuery, frontend + admin)
────────────────────────────────────────────────────────────────
- Character counter for <textarea maxlength> inside .hs-form
- Image preview for <input type="file" class="hs-file-input">
- Auto-submit on <select class="hs-select"> change
- Delete confirmation on .hs-btn-delete
- Required field validation on #hs-eingabe-form submit
- Alert fade-in for .hs-alert on page load
- Smooth scroll to URL hash on page load
- Scroll to ?hs_edited=ID after save
- ESC key closes inline edit panel
- WP Media Library modal on click of .hs-media-btn (admin only)
Localised object: handschelle_ajax.ajax_url, handschelle_ajax.nonce

────────────────────────────────────────────────────────────────
SECURITY REQUIREMENTS
────────────────────────────────────────────────────────────────
- All forms: wp_nonce_field() + wp_verify_nonce(); on failure show
  "⚠️ Fehler beim Speichern. Bitte Seite neu laden…" — no silent redirect
- All DB queries: $wpdb prepared statements only, never concatenate user input
- All output: esc_html() / esc_url() / esc_attr() everywhere
- All input: sanitize_text_field() / sanitize_url() / wp_kses_post()
  via handschelle_sanitize_entry($_POST)
- Inline edit + early_frontend_edit(): require current_user_can('publish_posts')
- Admin pages: require current_user_can('manage_options')
- File uploads: validate mime type and file extension

────────────────────────────────────────────────────────────────
VERSION CONVENTION
────────────────────────────────────────────────────────────────
Start at 6.2. Bump by 0.1 with every commit.
Update in 4 places simultaneously:
  die-handschelle.php  → * Version: X.Y
  die-handschelle.php  → define('HANDSCHELLE_VERSION', 'X.Y')
  includes/admin.php   → <span class="hs-version">X.Y</span>
  README.md            → | **Version** | X.Y |
Add a release note entry for each version in README.md ## Release Notes.

────────────────────────────────────────────────────────────────
IMPORTANT BEHAVIOURS
────────────────────────────────────────────────────────────────
- New submissions always saved with freigegeben=0; admin must approve
- datum_eintrag defaults to today; falls back to today if empty string
- $wpdb->insert() result is checked; success message only on actual save
- maybe_upgrade_table() only adds columns — never drops or renames
- Backup restore: truncates table, then re-imports, remaps bild IDs via bild-map.json
- German UI labels everywhere; English variable names and code comments
- Plugin integrates neutrally into any theme (no forced background colours
  on frontend wrappers)
```

---

