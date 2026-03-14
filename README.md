# Die-Handschelle

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.
> Documentation of crimes by political figures as a WordPress plugin.

| | |
|---|---|
| **Version** | 6.0 |
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

[![Buy Me A Coffee](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://buymeacoffee.com/dorfmuellersak47)

---

## Table of Contents

1. [Installation from GitHub](#installation-from-github)
2. [Shortcodes](#shortcodes)
3. [Fields / Database Schema](#fields--database-schema)
4. [Code Reference](#code-reference)
5. [Plugin Structure](#plugin-structure)
6. [Important Notes](#important-notes)
7. [Release Notes](#release-notes)

---

## Installation from GitHub

### Method 1: Download ZIP

1. Open the repository on GitHub: `https://github.com/VilDeMil/handschelle`
2. Click **Code → Download ZIP**
3. Extract the ZIP — rename the extracted folder to `die-handschelle`
4. Upload the folder to your server: `/wp-content/plugins/die-handschelle/`
5. In WordPress go to **Plugins → Installed Plugins**
6. Find **Die Handschelle** and click **Activate**
7. The database table `wp_{prefix}_die_handschelle` is created automatically on activation

### Method 2: Clone with Git

```bash
# Navigate to your WordPress plugins directory
cd /var/www/html/wp-content/plugins/

# Clone the repository
git clone https://github.com/VilDeMil/handschelle.git die-handschelle

# Activate via WP-CLI (optional)
wp plugin activate die-handschelle
```

### Method 3: WP-CLI from GitHub URL

```bash
# Install directly using WP-CLI and GitHub ZIP
wp plugin install https://github.com/VilDeMil/handschelle/archive/refs/heads/master.zip \
  --activate --force
```

### Updating

When updating the plugin, missing database columns are added automatically on the next page load — no manual migration needed. Existing data is never changed or removed.

### Requirements

| Requirement | Minimum Version |
|---|---|
| WordPress | 5.5+ |
| PHP | 7.4+ |
| GD Library | any (for image resizing) |
| MySQL / MariaDB | any WordPress-compatible version |

To verify GD is available on your server:
```php
<?php
var_dump(extension_loaded('gd')); // should print: bool(true)
```

---

## Shortcodes

All shortcodes output HTML and can be placed on any WordPress page or post.

### Overview

| Shortcode | Description |
|---|---|
| `[handschelle]` | Frontend submission form for new entries |
| `[handschelle-anzeige]` | Display all approved entries as cards (no pagination by default) |
| `[handschelle-suche]` | Full-text search field + Party and Person dropdowns |
| `[handschelle-partei]` | Party search dropdown only |
| `[handschelle-name]` | Person name search dropdown only |
| `[handschelle-statistik]` | Statistics table with bar chart per party (party names are links) |
| `[handschelle-statistik-nolink]` | Same as `[handschelle-statistik]` but without links on party names |
| `[handschelle-statistik-partei]` | Table: party / entry count (party links to filter) |
| `[handschelle-statistik-name]` | Table: person name / entry count |
| `[handschelle-statistik-ol]` | Ordered list: party – number of distinct names |
| `[handschelle-name-anzeige]` | Name dropdown – shows cards for selected person |
| `[handschelle-name-partei]` | Party dropdown – shows cards for selected party |
| `[handschelle-bilder]` | Image gallery – clickable photos, name + crime caption, hover tooltip |
| `[handschelle-karte]` | Single entry card by ID: `[handschelle-karte id="5"]` |
| `[handschelle-asc]` | Horizontal centered list: Partei (Anzahl), alphabetical, no header |
| `[handschelle-disclaimer]` | Copyright / contact notice |

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
- Crime description (required, max 200 characters)
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

### `[handschelle-suche]`

Renders a **full-text search field** and two auto-submitting dropdowns (Party and Person name). Combine with `[handschelle-anzeige]` on the same page.

```
[handschelle-suche]
```

---

### `[handschelle-partei]`

Renders only the Party search dropdown. After selection shows all cards for that party.

```
[handschelle-partei]
```

---

### `[handschelle-name]`

Renders only the Person name search dropdown. After selection shows all cards for that person plus **Google, Qwant, DuckDuckGo, Bing, Abgeordnetenwatch** search buttons.

```
[handschelle-name]
```

---

### `[handschelle-statistik]`

Statistics table: entries per party with bar chart. Party names link to `?hs_partei=`.

```
[handschelle-statistik]
```

---

### `[handschelle-statistik-nolink]`

Same as `[handschelle-statistik]` but party names are plain text (no links).

```
[handschelle-statistik-nolink]
```

---

### `[handschelle-statistik-partei]`

Table: Party → entry count. Party names link to `?hs_name_partei=`.

```
[handschelle-statistik-partei]
```

---

### `[handschelle-statistik-name]`

Table: Person name → entry count.

```
[handschelle-statistik-name]
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

### `[handschelle-asc]`

Compact horizontal centered list of all parties with entry count, sorted A→Z. No header, small font. Ideal for sidebars or page footers.

```
[handschelle-asc]
```

**Example output:** `AfD (12) · CDU (8) · FDP (3) · SPD (5)`

---

### `[handschelle-disclaimer]`

Copyright / contact block.

```
[handschelle-disclaimer]
```

**Output:**
> **Die-Handschelle © 2026**
> „Wer in unseren Parlamenten ist oder war kriminell?" Eine Datenbank der Straftaten.
> [www.die-handschelle.de](https://www.die-handschelle.de) · [Info@die-handschelle.de](mailto:Info@die-handschelle.de) · ☕ Unterstützen

---

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

## Fields / Database Schema

**Table name:** `wp_{prefix}_die_handschelle`
**Total fields:** 32

### Core Fields

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | — | Primary key |
| `datum_eintrag` | DATE | — | Entry date (default: today) |
| `erstellt_am` | DATETIME | — | Created timestamp (auto) |
| `geaendert_am` | DATETIME | — | Last modified timestamp (auto) |

### Person Fields

| Field | Type | Max Length | Required | Description |
|---|---|---|---|---|
| `name` | VARCHAR | 50 | Yes | Person's full name |
| `beruf` | VARCHAR | 50 | No | Profession / occupation |
| `geburtsort` | VARCHAR | 100 | No | Place of birth |
| `geburtsdatum` | DATE | — | No | Date of birth (age is calculated automatically) |
| `bild` | TEXT | — | No | WordPress attachment ID or image URL |
| `partei` | VARCHAR | 50 | No | Political party |
| `aufgabe_partei` | VARCHAR | 100 | No | Position / role within the party |
| `parlament` | VARCHAR | 100 | No | Parliament type (see options below) |
| `parlament_name` | VARCHAR | 50 | No | Constituency / parliament seat name |
| `status_aktiv` | TINYINT(1) | — | — | Active status: `1` = active, `0` = inactive (default `1`) |

### Crime / Legal Fields

| Field | Type | Max Length | Required | Description |
|---|---|---|---|---|
| `straftat` | VARCHAR | 200 | Yes | Description of the crime / offence |
| `status_straftat` | VARCHAR | 50 | No | Status of the criminal case (see options below) |
| `urteil` | VARCHAR | 50 | No | Verdict / sentence |
| `aktenzeichen` | VARCHAR | 50 | No | Case file / docket number |
| `link_quelle` | TEXT | — | No | Source link (URL to article / document) |
| `bemerkung` | TEXT | — | No | Admin notes / remarks |

### Publication Fields

| Field | Type | Description |
|---|---|---|
| `freigegeben` | TINYINT(1) | Published: `1` = approved, `0` = pending (default `0`) |

### Social Media Fields

| Field | Type | Platform |
|---|---|---|
| `sm_facebook` | TEXT | Facebook |
| `sm_youtube` | TEXT | YouTube |
| `sm_personal` | TEXT | Personal profile |
| `sm_twitter` | TEXT | Twitter / X |
| `sm_homepage` | TEXT | Personal website |
| `sm_wikipedia` | TEXT | Wikipedia |
| `sm_linkedin` | TEXT | LinkedIn |
| `sm_xing` | TEXT | Xing |
| `sm_truth_social` | TEXT | Truth Social |
| `sm_sonstige` | TEXT | Other |

### Database Indexes

| Index | Field(s) | Purpose |
|---|---|---|
| `idx_freigegeben` | `freigegeben` | Fast filtering of approved entries |
| `idx_name` | `name` | Fast search by person name |
| `idx_partei` | `partei` | Fast filtering by party |

---

### `status_straftat` Options

| Value | Meaning |
|---|---|
| `Ermittlungen laufen` | Investigations ongoing |
| `Verurteilt` | Convicted |
| `Eingestellt` | Case dismissed / closed |

---

### `parlament` Options (23 total)

| Value |
|---|
| Europäisches Parlament |
| Bundestag |
| Bundesrat |
| Landtag Baden-Württemberg |
| Landtag Bayern (Bayerischer Landtag) |
| Abgeordnetenhaus Berlin |
| Brandenburgischer Landtag |
| Bürgerschaft Bremen |
| Bürgerschaft Hamburg |
| Hessischer Landtag |
| Landtag Mecklenburg-Vorpommern |
| Niedersächsischer Landtag |
| Landtag Nordrhein-Westfalen |
| Landtag Rheinland-Pfalz |
| Landtag des Saarlandes |
| Sächsischer Landtag |
| Landtag Sachsen-Anhalt |
| Schleswig-Holsteinischer Landtag |
| Thüringer Landtag |
| Stadtrat / Gemeinderat |
| Kreistag |
| Bezirkstag |
| Sonstiges |

*(Full list defined in `includes/helpers.php` → `handschelle_parlaments()`)*

---

## Code Reference

### Plugin Constants

Defined in `die-handschelle.php`:

```php
HANDSCHELLE_VERSION     // '6.0'
HANDSCHELLE_PLUGIN_DIR  // Absolute path to plugin directory
HANDSCHELLE_PLUGIN_URL  // URL to plugin directory
HANDSCHELLE_DB_TABLE    // Table name suffix, e.g. 'die_handschelle'
```

---

### `Handschelle_Database` Class

File: `includes/database.php`

All methods are static. All queries use `$wpdb` prepared statements.

```php
// Create the database table (called on plugin activation)
Handschelle_Database::create_table();

// Check version and add missing columns (called on plugins_loaded)
Handschelle_Database::maybe_upgrade_table();

// Retrieve multiple entries
$entries = Handschelle_Database::get_all([
    'freigegeben' => 1,       // 1 = approved only, 0 = pending only, 'all' = no filter
    'partei'      => 'CDU',   // optional: filter by party
    'name'        => 'Doe',   // optional: filter by person name
    'search'      => 'fraud', // optional: full-text search (name, party, straftat)
    'orderby'     => 'name',  // optional: sort column
    'order'       => 'ASC',   // optional: ASC | DESC
    'limit'       => 20,      // optional: max results (0 = all)
    'offset'      => 0,       // optional: pagination offset
]);

// Retrieve a single entry by ID
$entry = Handschelle_Database::get_one( $id );

// Insert a new entry (always sets freigegeben = 0; returns new ID)
$new_id = Handschelle_Database::insert([
    'name'            => 'Max Mustermann',
    'partei'          => 'ExamplePartei',
    'straftat'        => 'Betrug',
    'status_straftat' => 'Ermittlungen laufen',
    'geburtsdatum'    => '1970-05-12',
    'geburtsort'      => 'Berlin',
]);

// Update an entry
Handschelle_Database::update( $id, [
    'freigegeben' => 1,
    'urteil'      => 'Freigesprochen',
]);

// Delete an entry
Handschelle_Database::delete( $id );

// Count entries (supports same filters as get_all)
$total = Handschelle_Database::count_all(['freigegeben' => 1]);

// Get distinct party names (for dropdowns)
$parties = Handschelle_Database::get_distinct_parteien();

// Get distinct person names (for dropdowns)
$names = Handschelle_Database::get_distinct_namen();

// Database maintenance (use with caution)
Handschelle_Database::truncate_table();   // empty table
Handschelle_Database::drop_table();       // delete table
Handschelle_Database::recreate_table();   // drop + re-create
```

---

### `Handschelle_Image_Handler` Class

File: `includes/image-handler.php`

```php
/**
 * Upload, rename to "{name}-HA.ext", resize and register as WP media attachment.
 *
 * @param  string $file_input_name  $_FILES key  (e.g. 'bild_upload')
 * @param  string $person_name      Person name – used to build filename slug
 * @return int    Attachment ID on success, 0 on failure / no file uploaded.
 */
$attachment_id = Handschelle_Image_Handler::handle_upload_and_resize(
    'bild_upload',    // $_FILES key
    'Max Mustermann', // → sanitize_title() → "max-mustermann-HA.jpg"
);

// Supported formats: JPEG, PNG, GIF, WebP
// PNG and GIF transparency is preserved.
// Images are resized to a maximum height of 450 px (aspect ratio preserved).
```

---

### Helper Functions

File: `includes/helpers.php`

```php
// Returns all parliament options as an array
$parlaments = handschelle_parlaments();

// Returns crime status options
$statuses = handschelle_status_straftat_options();
// ['Ermittlungen laufen', 'Verurteilt', 'Eingestellt']

// Resolve a display URL from an attachment ID or direct URL string
$url = handschelle_get_image_url( $bild_value );

// Sanitize and normalize all entry fields from raw POST data
$data = handschelle_sanitize_entry( $_POST );

// Calculate age from a date string (Y-m-d); returns int or null if unknown
$age = handschelle_calc_age( $entry->geburtsdatum );
// Example: handschelle_calc_age('1970-05-12') → 55
```

---

### JavaScript API

File: `assets/js/handschelle.js`

Enqueued on frontend and admin. Uses the global `handschelle_ajax` object (localized via `wp_localize_script`).

```javascript
handschelle_ajax.ajax_url  // WordPress AJAX URL
handschelle_ajax.nonce     // Security nonce
```

**Automatic behaviors:**

| Behavior | Trigger |
|---|---|
| Character counter | `<textarea maxlength>` inside `.hs-form` |
| Image preview | `<input type="file" class="hs-file-input">` |
| Auto-submit dropdowns | `<select class="hs-select">` change |
| Delete confirmation | Click on `.hs-btn-delete` |
| Required field validation | Submit of `#hs-eingabe-form` |
| Alert fade-in | `.hs-alert` on page load |
| Smooth scroll to anchor | URL hash on page load |
| Scroll to edited entry | `?hs_edited=ID` after save |
| ESC closes edit panel | Keyboard ESC (frontend inline edit) |
| WP Media Library picker | Click on `.hs-media-btn` (admin) |

---

### CSS Custom Properties (Design Tokens)

File: `assets/css/handschelle.css`

```css
:root {
    --hs-primary:  #1a1a2e;  /* Main dark background */
    --hs-accent:   #c0392b;  /* Red accent */
    --hs-accent-h: #e74c3c;  /* Red accent hover */
    --hs-gold:     #f39c12;  /* Gold highlights */
    --hs-success:  #27ae60;  /* Green */
    --hs-muted:    #7f8c8d;  /* Muted gray */
    --hs-bg:       #f0f0f0;  /* Page background (light grey) */
    --hs-card-bg:  #ffffff;  /* Card background */
}
```

**Key CSS classes:**

| Class | Element |
|---|---|
| `.hs-frontend` | Outer wrapper for all frontend output |
| `.hs-wrap` | Outer wrapper for admin pages |
| `.hs-form` | Form container |
| `.hs-cards-grid` | Responsive card grid |
| `.hs-card` | Individual entry card |
| `.hs-badge` | Status badge (party, crime status) |
| `.hs-admin-table` | Admin overview table |
| `.hs-statistik` | Statistics table wrapper |
| `.hs-alert` | Alert / notice messages |
| `.hs-sm-link` | Social media icon link |
| `.hs-pagination` | Pagination nav container |
| `.hs-search-input` | Full-text search input |
| `.hs-search-info` | Active search term banner |
| `.hs-filter-tabs` | Admin filter tab row |
| `.hs-bulk-bar` | Admin bulk-action toolbar |
| `.hs-cards-single` | Full-width single-column card list |
| `.hs-card-img-link` | Anchor wrapping the card profile image |
| `.hs-search-buttons` | Search engine button row |
| `.hs-search-btn` | Individual search engine button |
| `.hs-media-picker` | Admin image-field wrapper |
| `.hs-media-btn` | Opens the WP Media Library modal |
| `.hs-media-preview` | Thumbnail preview area |
| `.hs-asc-list` | `[handschelle-asc]` horizontal party list |
| `.hs-bild-item` | `[handschelle-bilder]` single image card |
| `.hs-bild-img-wrap` | Image wrapper (hover target for tooltip) |
| `.hs-bild-tooltip` | Hover tooltip with all person data |
| `.hs-bild-caption` | Name caption below image |
| `.hs-bild-straftat` | Crime caption below image |

---

### Admin Menu Structure

| Menu Item | Slug | Description |
|---|---|---|
| Die Handschelle | `handschelle` | Main menu (Overview) |
| Übersicht | `handschelle` | All entries — filter tabs (Alle / Ausstehend / Freigegeben), bulk actions, age column |
| + Neuer Eintrag | `handschelle-add` | Add new entry form |
| *(Bearbeiten)* | `handschelle-edit` | Edit entry (hidden from sidebar, accessible via ✏ button) |
| Import / Export | `handschelle-import-export` | CSV import & export |
| Bilder | `handschelle-bilder` | Image list, ZIP export & ZIP import |
| Backup & Restore | `handschelle-backup` | Full backup (CSV + images ZIP) and restore |
| Datenbank | `handschelle-db` | Database management (truncate / recreate / drop) |

---

### Backup & Restore

**Admin → Backup & Restore**

| Action | Description |
|---|---|
| **Backup herunterladen** | Creates a ZIP with `handschelle-data.csv` (all entries) + all media images in `images/` + `bild-map.json` for ID remapping |
| **Backup einspielen** | Upload a backup ZIP → truncates existing data → re-imports entries and images; attachment IDs are remapped automatically |

> **Warning:** Restore overwrites all existing entries. A confirmation checkbox is required.

---

### CSV Export / Import Format

The CSV export is UTF-8 with BOM, semicolon-delimited (Excel-compatible).

**Column order (32 fields):**
`id · datum_eintrag · name · beruf · geburtsort · geburtsdatum · bild · partei · aufgabe_partei · parlament · parlament_name · status_aktiv · straftat · urteil · link_quelle · aktenzeichen · bemerkung · status_straftat · sm_facebook · sm_youtube · sm_personal · sm_twitter · sm_homepage · sm_wikipedia · sm_sonstige · sm_linkedin · sm_xing · sm_truth_social · freigegeben · erstellt_am · geaendert_am`

The import is **header-based** — it reads the first row to determine the column order. This makes it fully backward-compatible with older CSVs (26 or 27 columns). Missing columns are silently ignored and default to empty.

> **Note:** `id`, `erstellt_am`, and `geaendert_am` are set automatically during import.

To export: **Admin → Import / Export → CSV herunterladen**
To import: **Admin → Import / Export → CSV-Datei hochladen → Importieren**

---

## Plugin Structure

```
die-handschelle/
├── die-handschelle.php           ← Main plugin file: constants, hooks, asset enqueue
├── includes/
│   ├── helpers.php               ← Parliament list, status options, sanitizer,
│   │                                image URL helper, handschelle_calc_age()
│   ├── database.php              ← Handschelle_Database class (CRUD, table management,
│   │                                maybe_upgrade_table() for auto-migration)
│   ├── image-handler.php         ← Handschelle_Image_Handler (upload + GD resize 450px)
│   ├── admin.php                 ← Handschelle_Admin class (admin menus, forms,
│   │                                CSV import/export, backup/restore)
│   └── shortcodes.php            ← Handschelle_Shortcodes class (all 16 shortcodes,
│                                    PRG submit handler, inline SVG icons)
└── assets/
    ├── css/handschelle.css       ← Full stylesheet with CSS custom properties
    └── js/handschelle.js         ← Frontend and admin JS
```

---

## Important Notes

- New public submissions are **not approved by default** (`freigegeben = 0`). An admin must approve them via **Übersicht → ✅ Freigeben**.
- Profile images are automatically resized to a maximum height of **450 px** using the GD library (required).
- CSV export uses **UTF-8 with BOM** and **semicolons** as delimiters for Excel compatibility. The import is header-based and backward-compatible.
- The **Edit page** is hidden from the admin sidebar but accessible via the ✏ button in the Overview table.
- **Logged-in users** see an inline edit button on every entry card in the frontend — no need to use the admin backend.
- The inline edit panel and admin form both include **Google, Qwant, DuckDuckGo, Bing, Abgeordnetenwatch** search buttons next to the name field.
- All forms use **WordPress nonce verification** to prevent CSRF attacks. If nonce verification fails (e.g. after a long session or cached page), the user sees a visible error message (`⚠️ Fehler beim Speichern`) instead of a silent redirect.
- All user input is sanitized with WordPress sanitization functions before writing to the database.
- Social media icons are rendered as **inline SVG** with brand colors and hover effects — no external icon library required.
- **Image uploads** are automatically renamed to `name-HA.ext` (e.g. `max-mustermann-HA.jpg`) using `sanitize_title()`.
- The **admin image field** supports two workflows: (1) pick from the WP Media Library via the `wp.media` modal, or (2) upload a new file directly.
- **Database auto-migration:** After updating the plugin, `maybe_upgrade_table()` runs on `plugins_loaded` and adds any missing columns via `dbDelta()`. No data is ever lost.

---

## Release Notes

### 6.0 *(2026-03-14)*
- **Dropdown styling**: Text color set to black (`#000`), background set to white (`#fff`), transparency removed — applies to all select elements (`.hs-select`, `.hs-field select`, `.hs-edit-form select`, `.hs-bulk-select`)
- **Eintrag melden**: Every card now has a `⚠️ Eintrag melden!` mailto link in the footer — opens a pre-addressed e-mail to `info@die-handschelle.de` with subject `Meldung - <Name> - <Partei>`

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
