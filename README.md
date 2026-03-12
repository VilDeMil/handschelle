# 🔒 Die Handschelle V.Alpha-2

> Dokumentation von Straftaten politischer Personen als WordPress-Plugin.

| | |
|---|---|
| **Version** | V.01.1 |
| **Autor** | Bernd K.R. Dorfmüller |
| **E-Mail** | bernd@dorfmüller.com |
| **Website** | https://dorfmüller.com/die-handschelle/ |
| **Lizenz** | GPL-2.0+ |

---

## Installation

1. ZIP herunterladen → Ordner `die-handschelle` nach `/wp-content/plugins/` kopieren
2. Plugin unter **WordPress → Plugins** aktivieren
3. Datenbanktabelle `wp_{prefix}_die_handschelle` wird automatisch angelegt

**Anforderungen:** WordPress 5.5+ · PHP 7.4+ · GD-Bibliothek

---

## Shortcodes

| Shortcode | Funktion |
|---|---|
| `[handschelle]` | Eingabeformular (Frontend) |
| `[handschelle-anzeige]` | Alle freigegebenen Einträge als Karten |
| `[handschelle-suche]` | Dropdown Partei + Name |
| `[handschelle-partei]` | Nur Dropdown nach Partei |
| `[handschelle-name]` | Nur Dropdown nach Name |
| `[handschelle-statistik]` | Einträge je Partei (Tabelle + Balken) |
| `[handschelle-disclaimer]` | Copyright: Die-Handschelle! © 2026 |

### Optionale Attribute
```
[handschelle-anzeige partei="CDU"]
[handschelle-anzeige name="Max Mustermann"]
```

---

## Plugin-Struktur

```
die-handschelle/
├── die-handschelle.php           ← Haupt-Datei
├── includes/
│   ├── helpers.php               ← Parlamente, Sanitizer
│   ├── database.php              ← Klasse Handschelle_Database
│   ├── image-handler.php         ← Upload & Resize 450px
│   ├── admin.php                 ← Admin-Panel
│   └── shortcodes.php            ← Alle Shortcodes + PRG-Submit
└── assets/
    ├── css/handschelle.css       ← Vollständiges CSS
    └── js/handschelle.js         ← JavaScript
```

---

## Wichtige Hinweise

- Neue Einträge sind standardmäßig **nicht freigegeben** (`freigegeben=0`)
- Freigabe erfolgt im Admin unter **Übersicht → ✅ Freigeben**
- Bilder werden auf max. **450px Höhe** skaliert (GD-Bibliothek erforderlich)
- CSV-Export: UTF-8 mit BOM, Semikolon-getrennt (Excel-kompatibel)
- Bearbeiten-Seite ist aus der Admin-Sidebar ausgeblendet, aber über ✏ Button erreichbar

---

## Datenbankschema

Tabelle: `wp_{prefix}_die_handschelle` — 27 Felder

Schlüsselfelder: `id`, `name`, `partei`, `straftat`, `status_straftat`, `freigegeben`

Social-Media: `sm_facebook`, `sm_youtube`, `sm_personal`, `sm_twitter`, `sm_homepage`, `sm_wikipedia`, `sm_sonstige`

---

*Erstellt mit Vibe-Coding — KI-gestützte Entwicklung mit Claude (Anthropic)*
