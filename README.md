# Die-Handschelle

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.
> Documentation of crimes by political figures as a WordPress plugin.

| | |
|---|---|
| **Version** | 7.4 |
| **Autor** | Bernd K.R. Dorfmüller |
| **E-Mail** | info@die-handschelle.com |
| **Website** | https://www.die-handschelle.com |
| **Lizenz** | GPL-2.0+ |
| **Requires** | WordPress 5.5+, PHP 7.4+, GD Library |

---

## Table of Contents

- [Einleitung / Introduction](#einleitung--introduction)
- [Installation from GitHub](#installation-from-github)
  - [Method 1: Download ZIP](#method-1-download-zip)
  - [Method 2: Clone with Git](#method-2-clone-with-git)
  - [Method 3: WP-CLI from GitHub URL](#method-3-wp-cli-from-github-url)
  - [Updating](#updating)
  - [Requirements](#requirements)
- [Build / Package](#build--package)
  - [Create a release ZIP](#create-a-release-zip)
  - [Quick one-liner](#quick-one-liner-from-inside-the-repo)
  - [Verify GD](#verify-gd-is-available-on-the-target-server)
- [Shortcodes](#shortcodes)
  - [Overview](#overview)
  - [`[handschelle]`](#handschelle)
  - [`[handschelle-anzeige]`](#handschelle-anzeige)
  - [`[handschelle-karte]`](#handschelle-karte)
  - [`[handschelle-suche]`](#handschelle-suche)
  - [`[handschelle-partei]`](#handschelle-partei)
  - [`[handschelle-name]`](#handschelle-name)
  - [`[handschelle-statistik]`](#handschelle-statistik)
  - [`[handschelle-statistik-nolink]`](#handschelle-statistik-nolink)
  - [`[handschelle-statistik-partei]`](#handschelle-statistik-partei)
  - [`[handschelle-statistik-name]`](#handschelle-statistik-name)
  - [`[handschelle-statistik-ol]`](#handschelle-statistik-ol)
  - [`[handschelle-name-anzeige]`](#handschelle-name-anzeige)
  - [`[handschelle-name-partei]`](#handschelle-name-partei)
  - [`[handschelle-bilder]`](#handschelle-bilder)
  - [`[handschelle-asc]`](#handschelle-asc)
  - [`[handschelle-asc-link]`](#handschelle-asc-link)
  - [`[handschelle-disclaimer]`](#handschelle-disclaimer)
  - [Typical Page Setup](#typical-page-setup)
- [Fields / Database Schema](#fields--database-schema)
  - [Core Fields](#core-fields)
  - [Person Fields](#person-fields)
  - [Crime / Legal Fields](#crime--legal-fields)
  - [Publication Fields](#publication-fields)
  - [Social Media Fields](#social-media-fields)
  - [Database Indexes](#database-indexes)
  - [`status_straftat` Options](#status_straftat-options)
  - [`parlament` Options](#parlament-options-23-total)
- [Code Reference](#code-reference)
  - [Plugin Constants](#plugin-constants)
  - [`Handschelle_Database` Class](#handschelle_database-class)
  - [`Handschelle_Image_Handler` Class](#handschelle_image_handler-class)
  - [Helper Functions](#helper-functions)
  - [JavaScript API](#javascript-api)
  - [CSS Custom Properties](#css-custom-properties-design-tokens)
  - [Admin Menu Structure](#admin-menu-structure)
  - [Backup & Restore](#backup--restore)
  - [CSV Export / Import Format](#csv-export--import-format)
- [Plugin Structure](#plugin-structure)
- [Instructions for AI / LLM](#instructions-for-ai--llm)
  - [Version Bumping](#version-bumping)
  - [Adding a Release Note](#adding-a-release-note)
  - [Shortcode Checklist](#shortcode-checklist)
  - [Database / Schema Changes](#database--schema-changes)
  - [General Rules](#general-rules)
- [Recreate from Scratch](#recreate-from-scratch)
- [Important Notes](#important-notes)
- [Release Notes](#release-notes)

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

## Build / Package

This plugin has **no build toolchain** — PHP, CSS, and JS are plain files with no compilation step. "Building" means packaging the plugin folder into a ZIP for distribution or manual installation.

### Create a release ZIP

Run from the **parent directory** of `die-handschelle/`:

```bash
zip -r die-handschelle.zip die-handschelle/ \
  --exclude "die-handschelle/.git/*" \
  --exclude "die-handschelle/.gitignore" \
  --exclude "die-handschelle/prompt.txt"
```

The resulting `die-handschelle.zip` can be installed via **WordPress Admin → Plugins → Add New → Upload Plugin**.

### Quick one-liner (from inside the repo)

```bash
cd .. && zip -r die-handschelle.zip die-handschelle/ --exclude "die-handschelle/.git/*" && cd die-handschelle
```

### Verify GD is available on the target server

```bash
php -r "var_dump(extension_loaded('gd'));"
# expected: bool(true)
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
| `[handschelle-asc-link]` | Same as `[handschelle-asc]` but party names are clickable links (`?hs_partei=`) with a hover tooltip listing all persons |
| `[handschelle-disclaimer]` | Copyright / contact notice |
| `[wordcloud-name]` | Word cloud of person names (sized by entry count) — shows Name (Partei) |
| `[wordcloud-urteil]` | Word cloud of verdicts (`urteil`) sized by frequency |
| `[handschelle-ticker]` | Horizontal scrolling newsticker showing Name – Crime for approved entries |

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

### `[handschelle-asc-link]`

Same as `[handschelle-asc]` but with two enhancements:

- Party names are **clickable links** — navigates to `?hs_partei=<party>` on the current page
- A **hover tooltip** lists all person names in that party (one per line)

```
[handschelle-asc-link]
```

**Example output:** `AfD (12) · CDU (8) · FDP (3)` — each party name is a link; hovering shows the persons

---

### `[handschelle-ticker]`

Horizontal scrolling newsticker that continuously displays the most recent approved entries as **Name – Crime** pairs. Hovering pauses the animation.

```
[handschelle-ticker]
[handschelle-ticker limit="20" speed="30"]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `limit`   | `30`    | Number of most-recent entries to show |
| `speed`   | `40`    | Full-scroll duration in seconds (lower = faster) |

**Output:** A dark banner with a red "Aktuell" label, scrolling entries in the form `Name – Straftat [Status]`. The name is highlighted in gold; the status badge is shown in a muted pill. The track is duplicated internally so the animation loops seamlessly.

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
**Total fields:** 31

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
HANDSCHELLE_VERSION     // '6.3'
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

**Column order (31 fields):**
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
│   └── shortcodes.php            ← Handschelle_Shortcodes class (all 19 shortcodes,
│                                    PRG submit handler, inline SVG icons)
└── assets/
    ├── css/handschelle.css       ← Full stylesheet with CSS custom properties
    └── js/handschelle.js         ← Frontend and admin JS
```

---

## Instructions for AI / LLM

> This section is written for AI assistants (Claude, GPT, Gemini, etc.) that contribute to this codebase. Follow these rules every time you make changes.

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

---

## Release Notes

### 7.4 *(2026-03-16)*
- **Newsticker `[handschelle-ticker]`**: New shortcode that renders a horizontal CSS-animated news ticker. Displays approved entries as scrolling **Name – Straftat [Status]** items. Supports `limit` (default 30) and `speed` (default 40 s) attributes; hovering pauses the animation.

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
