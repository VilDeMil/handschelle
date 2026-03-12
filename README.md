# Die Handschelle V.Alpha-2

| | |
|---|---|
| **Version** | V.01.1 |
| **Autor** | Bernd K.R. Dorfmüller |
| **E-Mail** | bernd.dorfmueller@gmail.com |
| **Lizenz** | GPL-2.0+ |

## Installation

1. ZIP entpacken → Ordner `die-handschelle` nach `/wp-content/plugins/` kopieren
2. Plugin unter **WordPress → Plugins** aktivieren
3. Datenbanktabelle wird automatisch angelegt

**Anforderungen:** WordPress 5.5+ · PHP 7.4+ · GD-Bibliothek

## Shortcodes

| Shortcode | Funktion |
|---|---|
| `[handschelle]` | Eingabeformular (Frontend) |
| `[handschelle-anzeige]` | Alle freigegebenen Einträge |
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

## Hinweise

- Neue Einträge sind standardmäßig **nicht freigegeben** (freigegeben=0)
- Freigabe erfolgt im Admin unter **Übersicht → ✅ Freigeben**
- Bilder werden auf max. **450 px Höhe** skaliert
- CSV-Export: UTF-8 mit BOM, Semikolon-getrennt (Excel-kompatibel)
