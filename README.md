# Die-Handschelle

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.
> Documentation of crimes by political figures as a WordPress plugin.

| | |
|---|---|
| **Version** | 3.00 |
| **Autor** | Bernd K.R. Dorfmüller |
| **E-Mail** | bernd@xn--dorfmller-u9a.com |
| **Website** | https://xn--dorfmller-u9a.com/die-handschelle |
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
| `[handschelle-anzeige]` | Display all approved entries as cards |
| `[handschelle-suche]` | Search dropdowns: Party + Person name |
| `[handschelle-partei]` | Party search dropdown only |
| `[handschelle-name]` | Person name search dropdown only |
| `[handschelle-statistik]` | Statistics table with bar chart per party |
| `[handschelle-statistik-partei]` | Table: party / entry count (party links to filter) |
| `[handschelle-statistik-name]` | Table: person name / entry count |
| `[handschelle-name-anzeige]` | Name dropdown – shows cards for selected person |
| `[handschelle-name-partei]` | Party dropdown – shows cards for selected party |
| `[handschelle-bilder]` | Gallery of all approved entry images (max 300×300 px) |
| `[handschelle-karte]` | Single entry card by ID: `[handschelle-karte id="5"]` |
| `[handschelle-disclaimer]` | Copyright notice |

---

### `[handschelle]`

Renders a public submission form. New submissions are saved with `freigegeben = 0` (not approved) and must be approved by an admin before they appear on the site.

```
[handschelle]
```

**Form fields presented to visitors:**
- Name (required)
- Party
- Profession
- Position in party
- Parliament type & name
- Crime description (required, max 200 characters)
- Crime status
- Verdict
- Source link
- Case file number
- Notes / remarks
- Social media links (Facebook, YouTube, Twitter/X, personal, homepage, Wikipedia, other)
- Photo upload

---

### `[handschelle-anzeige]`

Displays approved entries (`freigegeben = 1`) as responsive cards with social media icons. Supports **pagination** and **text search** via URL parameters.

```
[handschelle-anzeige]
[handschelle-anzeige limit="12"]
```

**Optional shortcode attributes:**

```
[handschelle-anzeige partei="CDU"]
[handschelle-anzeige name="Max Mustermann"]
[handschelle-anzeige partei="SPD" name="Jane Doe"]
[handschelle-anzeige limit="10"]
```

| Attribute | Type | Default | Description |
|---|---|---|---|
| `partei` | string | — | Filter cards by political party name |
| `name` | string | — | Filter cards by person name |
| `limit` | int | `12` | Cards per page (0 = all, disables pagination) |

**URL parameters (set automatically by search/pagination UI):**

| Parameter | Description |
|---|---|
| `hs_paged` | Current page number (pagination) |
| `hs_search` | Full-text search term (searches name, party, crime description) |

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
- Profile photo (resized to max 450px height)
- Person name & party badge
- Profession & position
- Parliament affiliation
- Crime description & status badge
- Verdict, case number, source link
- Social media icon links

---

### `[handschelle-suche]`

Renders a **full-text search field** and two auto-submitting dropdowns (Party and Person name) that filter the entry display on the same page. Combine with `[handschelle-anzeige]`. The text search queries name, party, and crime description simultaneously.

```
[handschelle-suche]
```

---

### `[handschelle-partei]`

Renders only the Party search dropdown.

```
[handschelle-partei]
```

---

### `[handschelle-name]`

Renders only the Person name search dropdown.

```
[handschelle-name]
```

---

### `[handschelle-statistik]`

Displays a statistics table titled **"Einträge je Partei"** listing the number of entries per political party along with relative bar charts (percentage of total). Each party name links to the party filter page via `?hs_partei=` parameter.

```
[handschelle-statistik]
```

**Output columns:** Party name (linked) · Count · Relative bar chart

---

### `[handschelle-statistik-partei]`

Displays a table titled **"Wie viele Straftäter je Partei gibt es?"** with columns: Party (linked to `?hs_name_partei=`) and entry count.

```
[handschelle-statistik-partei]
```

---

### `[handschelle-statistik-name]`

Displays a table titled **"Wer hat bereits einen Eintrag?"** with columns: Name and entry count.

```
[handschelle-statistik-name]
```

---

### `[handschelle-name-anzeige]`

Renders a person name dropdown. After selection, shows all approved entry cards for that person.

```
[handschelle-name-anzeige]
```

---

### `[handschelle-name-partei]`

Renders a party dropdown. After selection, shows all approved entry cards for that party.

```
[handschelle-name-partei]
```

---

### `[handschelle-bilder]`

Displays a responsive gallery of all approved entries that have an image. Images are displayed with `max-width: 300px` and `max-height: 300px` while preserving the original aspect ratio. Each image is captioned with the person's name.

```
[handschelle-bilder]
```

---

### `[handschelle-disclaimer]`

Outputs the copyright/disclaimer block:

```
[handschelle-disclaimer]
```

**Output:**
> **Die-Handschelle © 2026**
> Wer in unseren Parlamenten ist oder war kriminell? Eine Datenbank der Straftaten.
> [bernd@xn--dorfmller-u9a.com](mailto:bernd@xn--dorfmller-u9a.com) · [xn--dorfmller-u9a.com/die-handschelle](https://xn--dorfmller-u9a.com/die-handschelle)

---

### Typical Page Setup

```
<!-- Search page -->
[handschelle-suche]
[handschelle-anzeige]

<!-- Statistics page -->
[handschelle-statistik]

<!-- Submit page -->
[handschelle]
```

---

## Fields / Database Schema

**Table name:** `wp_{prefix}_die_handschelle`
**Total fields:** 27

### Core Fields

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | — | Primary key |
| `datum_eintrag` | DATE | — | Entry date (default: today) |
| `erstellt_am` | DATETIME | — | Created timestamp |
| `geaendert_am` | DATETIME | — | Last modified timestamp |

### Person Fields

| Field | Type | Max Length | Required | Description |
|---|---|---|---|---|
| `name` | VARCHAR | 50 | Yes | Person's full name |
| `beruf` | VARCHAR | 50 | No | Profession / occupation |
| `bild` | TEXT | — | No | WordPress attachment ID or image URL |
| `partei` | VARCHAR | 50 | No | Political party |
| `aufgabe_partei` | VARCHAR | 100 | No | Position / role within the party |
| `parlament` | VARCHAR | 100 | No | Parliament type (see options below) |
| `parlament_name` | VARCHAR | 50 | No | Constituency / parliament seat name |
| `status_aktiv` | TINYINT(1) | — | — | Active status: `1` = active, `0` = inactive (default: `1`) |

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
| `freigegeben` | TINYINT(1) | Published: `1` = approved, `0` = pending (default: `0`) |

### Social Media Fields

| Field | Type | Platform |
|---|---|---|
| `sm_facebook` | TEXT | Facebook profile URL |
| `sm_youtube` | TEXT | YouTube channel URL |
| `sm_personal` | TEXT | Personal profile URL |
| `sm_twitter` | TEXT | Twitter / X profile URL |
| `sm_homepage` | TEXT | Personal website / homepage URL |
| `sm_wikipedia` | TEXT | Wikipedia article URL |
| `sm_sonstige` | TEXT | Other social media URL |

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
| Landtag Bayern |
| Abgeordnetenhaus Berlin |
| Bürgerschaft Bremen |
| Bürgerschaft Hamburg |
| Landtag Hessen |
| Landtag Niedersachsen |
| Landtag Nordrhein-Westfalen |
| Landtag Rheinland-Pfalz |
| Landtag Sachsen |
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
HANDSCHELLE_VERSION     // '3.00'
HANDSCHELLE_PLUGIN_DIR  // Absolute path to plugin directory
HANDSCHELLE_PLUGIN_URL  // URL to plugin directory
HANDSCHELLE_DB_TABLE    // Full table name, e.g. 'wp_die_handschelle'
```

---

### `Handschelle_Database` Class

File: `includes/database.php`

All methods are static. Use `$wpdb` and prepared statements internally.

```php
// Create the database table (called on plugin activation)
Handschelle_Database::create_table();

// Retrieve multiple entries
$entries = Handschelle_Database::get_all([
    'freigegeben' => 1,       // optional: filter approved entries
    'partei'      => 'CDU',   // optional: filter by party
    'name'        => 'Doe',   // optional: filter by person name
    'search'      => 'fraud', // optional: full-text search
    'orderby'     => 'name',  // optional: sort column
    'order'       => 'ASC',   // optional: ASC | DESC
    'limit'       => 20,      // optional: max results
    'offset'      => 0,       // optional: pagination offset
]);

// Retrieve a single entry by ID
$entry = Handschelle_Database::get_one( $id );

// Insert a new entry (returns new ID or false)
$new_id = Handschelle_Database::insert([
    'name'           => 'Max Mustermann',
    'partei'         => 'ExamplePartei',
    'straftat'       => 'Betrug',
    'status_straftat'=> 'Ermittlungen laufen',
    'freigegeben'    => 0,
]);

// Update an entry
Handschelle_Database::update( $id, [
    'freigegeben' => 1,
    'urteil'      => 'Freigesprochen',
]);

// Delete an entry
Handschelle_Database::delete( $id );

// Count entries
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
// Upload an image file and create a WordPress media attachment.
// Returns attachment ID (int) or WP_Error on failure.
$attachment_id = Handschelle_Image_Handler::handle_upload_and_resize( $_FILES['bild'] );

// The image is automatically resized to a maximum height of 450px.
// Supported formats: JPEG, PNG, GIF, WebP
// PNG and GIF transparency is preserved.
```

---

### Helper Functions

File: `includes/helpers.php`

```php
// Returns an array of all parliament options
$parlaments = handschelle_parlaments();
// Returns: ['Europäisches Parlament', 'Bundestag', ...]

// Returns an array of crime status options
$statuses = handschelle_status_straftat_options();
// Returns: ['Ermittlungen laufen', 'Verurteilt', 'Eingestellt']

// Resolve an image URL from attachment ID or direct URL string
$url = handschelle_get_image_url( $bild_value );

// Sanitize and validate all entry fields from raw POST/GET data.
// Returns sanitized array or WP_Error on validation failure.
$data = handschelle_sanitize_entry( $_POST );
```

---

### JavaScript API

File: `assets/js/handschelle.js`

The script is enqueued on both frontend and admin. It uses the global object `handschelle_ajax` (localized by `wp_localize_script`).

```javascript
// Available global:
handschelle_ajax.ajax_url  // WordPress AJAX URL
handschelle_ajax.nonce     // Security nonce for requests
```

**Automatic behaviors (no setup required):**

| Behavior | Trigger |
|---|---|
| Character counter | Any `<textarea>` with a `maxlength` attribute inside `.hs-form` |
| Image preview | `<input type="file" name="bild">` file change |
| Auto-submit dropdowns | `<select>` change inside `.hs-suche` |
| Delete confirmation | Click on any `.hs-delete-btn` link |
| Required field validation | Submit of `.hs-form` |
| Alert fade-in | `.hs-alert` elements on page load |
| Smooth scroll to anchor | Any `<a href="#...">` link |

---

### CSS Custom Properties (Design Tokens)

File: `assets/css/handschelle.css`

Override these in your theme to customize the plugin appearance:

```css
:root {
    --hs-primary:  #1a1a2e;  /* Main dark background color */
    --hs-accent:   #c0392b;  /* Red accent (crime alert) */
    --hs-accent-h: #e74c3c;  /* Red accent hover state */
    --hs-gold:     #f39c12;  /* Gold highlights */
    --hs-success:  #27ae60;  /* Green (approved / success) */
    --hs-muted:    #7f8c8d;  /* Muted gray text */
    --hs-bg:       #f4f6f8;  /* Page background */
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
| `.hs-statistik` | Statistics table |
| `.hs-alert` | Alert / notice messages |
| `.hs-sm-link` | Social media icon link |

---

### Admin Menu Structure

Registered in `includes/admin.php`:

| Menu Item | Slug | Description |
|---|---|---|
| Die Handschelle | `die-handschelle` | Main menu (Overview) |
| Übersicht | `die-handschelle` | List all entries with filter tabs + bulk actions |
| + Neuer Eintrag | `die-handschelle-add` | Add new entry form |
| *(Bearbeiten)* | `die-handschelle-edit` | Edit entry (hidden from sidebar) |
| Import / Export | `die-handschelle-importexport` | CSV import & export |
| Bilder | `die-handschelle-bilder` | Image list, ZIP export & ZIP import |
| Datenbank | `die-handschelle-db` | Database management |

**v3.0 Admin features:**
- **Filter tabs:** Switch between Alle / Ausstehend / Freigegeben with entry counts
- **Bulk actions:** Select multiple entries via checkboxes → Freigeben / Sperren / Löschen

---

### CSV Export / Import Format

The CSV export is UTF-8 with BOM, semicolon-delimited (Excel-compatible).

**Column order in CSV:** All 27 fields in the order they are defined in the database schema above.

To export: **Admin → Import / Export → Export CSV**
To import: **Admin → Import / Export → CSV-Datei hochladen → Importieren**

> **Note:** The `id`, `erstellt_am`, and `geaendert_am` columns are set automatically during import and can be left empty in the CSV.

---

## Plugin Structure

```
die-handschelle/
├── die-handschelle.php           ← Main plugin file, constants, hooks, asset enqueue
├── includes/
│   ├── helpers.php               ← Parliament list, status options, sanitizer, image URL helper
│   ├── database.php              ← Handschelle_Database class (CRUD + table management)
│   ├── image-handler.php         ← Handschelle_Image_Handler (upload + GD resize to 450px)
│   ├── admin.php                 ← Handschelle_Admin class (admin menu, pages, actions)
│   └── shortcodes.php            ← Handschelle_Shortcodes class (all shortcodes + PRG submit)
└── assets/
    ├── css/handschelle.css       ← Full stylesheet with CSS custom properties
    └── js/handschelle.js         ← Frontend and admin JS (counters, preview, validation)
```

---

## Important Notes

- New public submissions are **not approved by default** (`freigegeben = 0`). An admin must approve them via **Übersicht → ✅ Freigeben**.
- Profile images are automatically resized to a maximum height of **450px** using the GD library (required).
- CSV export uses **UTF-8 with BOM** and **semicolons** as delimiters for Excel compatibility.
- The **Edit page** is hidden from the admin sidebar but accessible via the ✏ button in the Overview table.
- **Logged-in users** also see an edit button directly on entry cards in the frontend (`[handschelle-anzeige]`) — no need to navigate to the admin backend.
- All forms use **WordPress nonce verification** to prevent CSRF attacks.
- All user input is sanitized with WordPress sanitization functions before writing to the database.
- Social media icons are rendered as **inline SVG** with brand colors and hover effects — no external icon library required.

---

---

## Release Notes

### 3.00 *(2026-03-13)*
- **Paginierung für `[handschelle-anzeige]`**: Neues `limit`-Attribut (Standard: 12 Einträge pro Seite), URL-Parameter `hs_paged` für Seitennavigation
- **Volltext-Suche in `[handschelle-suche]`**: Neues Textsuchfeld durchsucht Name, Partei und Straftat gleichzeitig; URL-Parameter `hs_search`; alle Filter kombinierbar
- **`[handschelle-karte id="X"]`**: Neuer Shortcode zur Anzeige einer einzelnen Eintragskarte per Datenbank-ID
- **Admin-Übersicht: Filter-Tabs**: Schnellfilter Alle / Ausstehend / Freigegeben mit Anzahl-Badges
- **Admin-Übersicht: Bulk-Aktionen**: Mehrere Einträge per Checkbox auswählen und gemeinsam freigeben, sperren oder löschen
- **`Handschelle_Database::count_all()`** erweitert: unterstützt jetzt dieselben Filter wie `get_all()` (search, partei, name) – benötigt für genaue Paginierung
- **`Handschelle_Database::recreate_table()`** als eigenständige Methode hinzugefügt

### 2.09 *(2026-03-13)*
- Added "Buy Me A Coffee" support link to README.md

### 2.08 *(2026-03-13)*
- Added Introduction / Einleitung section to README.md

### 2.07 *(2026-03-13)*
- Added shortcode `[handschelle-bilder]`: gallery of all approved entry images, max 300×300 px, aspect ratio preserved
- Version bump rule documented: +0.01 per commit

### 2.06 *(2026-03-13)*
- Added admin page **Bilder**: list all entries with images, ZIP export & ZIP import of attachments
- ZIP import: extracts images, resizes to max 450 px height via GD, registers as WP media attachments

### 2.05 *(2026-03-13)*
- Added shortcodes: `[handschelle-statistik-partei]`, `[handschelle-statistik-name]`, `[handschelle-name-anzeige]`, `[handschelle-name-partei]`
- Party column in `[handschelle-statistik-partei]` links to `?hs_name_partei=` filter

### 2.04 *(2026-03-13)*
- Updated author email to `bernd@xn--dorfmller-u9a.com`
- Updated project URL to `https://xn--dorfmller-u9a.com/die-handschelle`
- Version numbering converted to numeric format (0.01 increments)

### Alpha-2 / 2.0 A *(initial)*
- Initial release: frontend submission form, entry cards, party/name dropdowns, statistics table, CSV import/export, database management, image upload & resize (GD, max 450 px height)

---

*Erstellt mit Vibe-Coding — KI-gestützte Entwicklung mit Claude (Anthropic)*
