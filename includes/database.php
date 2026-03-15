<?php
/**
 * Die-Handschelle 7.0 – Datenbankklasse
 *
 * Zwei-Tabellen-Struktur:
 *   wp_die_handschelle_personen   – Personen-Stammdaten
 *   wp_die_handschelle_straftaten – Straftaten (n:1 zu Personen)
 *
 * DISCLAIMER:
 * Dieses Plugin dient ausschließlich der sachlichen Dokumentation öffentlich
 * bekannter Straftaten politischer Personen auf Basis von Medienberichten und
 * Gerichtsurteilen. Es erhebt keinen Anspruch auf Vollständigkeit. Alle Angaben
 * ohne Gewähr. Betreiber haften nicht für die Richtigkeit der eingetragenen
 * Inhalte. Die Veröffentlichung eines Eintrags erfolgt erst nach manueller
 * Prüfung und Freigabe durch den Administrator.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Handschelle_Database {

    /* ── Feldlisten für Split-Operationen ────────────────────────── */

    private static $person_fields = array(
        'name', 'beruf', 'geburtsort', 'geburtsdatum', 'bild',
        'partei', 'aufgabe_partei', 'parlament', 'parlament_name', 'status_aktiv',
        'sm_facebook', 'sm_youtube', 'sm_personal', 'sm_twitter', 'sm_homepage',
        'sm_wikipedia', 'sm_sonstige', 'sm_linkedin', 'sm_xing', 'sm_truth_social',
    );

    private static $offence_fields = array(
        'datum_eintrag', 'straftat', 'urteil', 'link_quelle',
        'aktenzeichen', 'bemerkung', 'status_straftat', 'freigegeben',
    );

    /* ================================================================
       TABELLEN ERSTELLEN
    ================================================================ */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Personen-Tabelle ─────────────────────────────────────────
        $tp  = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $sql = "CREATE TABLE IF NOT EXISTS `{$tp}` (
            `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`            VARCHAR(50)      NOT NULL DEFAULT '',
            `beruf`           VARCHAR(50)      NOT NULL DEFAULT '',
            `geburtsort`      VARCHAR(100)     NOT NULL DEFAULT '',
            `geburtsdatum`    DATE             NULL DEFAULT NULL,
            `bild`            TEXT,
            `partei`          VARCHAR(50)      NOT NULL DEFAULT '',
            `aufgabe_partei`  VARCHAR(100)     NOT NULL DEFAULT '',
            `parlament`       VARCHAR(100)     NOT NULL DEFAULT '',
            `parlament_name`  VARCHAR(50)      NOT NULL DEFAULT '',
            `status_aktiv`    TINYINT(1)       NOT NULL DEFAULT 1,
            `sm_facebook`     TEXT,
            `sm_youtube`      TEXT,
            `sm_personal`     TEXT,
            `sm_twitter`      TEXT,
            `sm_homepage`     TEXT,
            `sm_wikipedia`    TEXT,
            `sm_sonstige`     TEXT,
            `sm_linkedin`     TEXT,
            `sm_xing`         TEXT,
            `sm_truth_social` TEXT,
            `erstellt_am`     DATETIME         NULL DEFAULT NULL,
            `geaendert_am`    DATETIME         NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_name`   (`name`),
            KEY `idx_partei` (`partei`)
        ) {$charset_collate};";
        dbDelta( $sql );

        // ── Straftaten-Tabelle ───────────────────────────────────────
        $ts  = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        $sql = "CREATE TABLE IF NOT EXISTS `{$ts}` (
            `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `person_id`       INT(11) UNSIGNED NOT NULL,
            `datum_eintrag`   DATE             NOT NULL,
            `straftat`        VARCHAR(200)     NOT NULL DEFAULT '',
            `urteil`          VARCHAR(50)      NOT NULL DEFAULT '',
            `link_quelle`     TEXT,
            `aktenzeichen`    VARCHAR(50)      NOT NULL DEFAULT '',
            `bemerkung`       TEXT,
            `status_straftat` VARCHAR(50)      NOT NULL DEFAULT 'Ermittlungen laufen',
            `freigegeben`     TINYINT(1)       NOT NULL DEFAULT 0,
            `erstellt_am`     DATETIME         NULL DEFAULT NULL,
            `geaendert_am`    DATETIME         NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_person_id`   (`person_id`),
            KEY `idx_freigegeben` (`freigegeben`)
        ) {$charset_collate};";
        dbDelta( $sql );
    }

    /** Backward-compat alias used by activation hook */
    public static function create_table() {
        self::create_tables();
    }

    /* ================================================================
       TABELLEN LÖSCHEN / LEEREN / NEU ERSTELLEN
    ================================================================ */
    public static function drop_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN . "`" );
        $wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN   . "`" );
    }

    public static function truncate_table() {
        global $wpdb;
        $wpdb->query( "SET FOREIGN_KEY_CHECKS=0" );
        $wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN . "`" );
        $wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN   . "`" );
        $wpdb->query( "SET FOREIGN_KEY_CHECKS=1" );
    }

    public static function recreate_table() {
        self::drop_table();
        self::create_tables();
    }

    /* ================================================================
       MIGRATION: alte Einzel-Tabelle → neue Zwei-Tabellen-Struktur
    ================================================================ */
    public static function migrate_from_legacy() {
        global $wpdb;
        $old_table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;

        // Check if old table exists
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $old_table
        ) );
        if ( ! $exists ) return 0;

        $rows = $wpdb->get_results( "SELECT * FROM `{$old_table}` ORDER BY id ASC" );
        if ( empty( $rows ) ) return 0;

        // Build person map: name → person_id in new table
        $person_map = array();

        foreach ( $rows as $row ) {
            $name = $row->name;

            if ( ! isset( $person_map[ $name ] ) ) {
                // Insert person (first occurrence wins for person data)
                $person_data = array(
                    'name'            => $row->name,
                    'beruf'           => $row->beruf,
                    'geburtsort'      => $row->geburtsort,
                    'geburtsdatum'    => ( ! empty( $row->geburtsdatum ) && $row->geburtsdatum !== '0000-00-00' ) ? $row->geburtsdatum : null,
                    'bild'            => $row->bild,
                    'partei'          => $row->partei,
                    'aufgabe_partei'  => $row->aufgabe_partei,
                    'parlament'       => $row->parlament,
                    'parlament_name'  => $row->parlament_name,
                    'status_aktiv'    => intval( $row->status_aktiv ),
                    'sm_facebook'     => $row->sm_facebook,
                    'sm_youtube'      => $row->sm_youtube,
                    'sm_personal'     => $row->sm_personal,
                    'sm_twitter'      => $row->sm_twitter,
                    'sm_homepage'     => $row->sm_homepage,
                    'sm_wikipedia'    => $row->sm_wikipedia,
                    'sm_sonstige'     => $row->sm_sonstige,
                    'sm_linkedin'     => $row->sm_linkedin,
                    'sm_xing'         => $row->sm_xing,
                    'sm_truth_social' => $row->sm_truth_social,
                );
                $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN, $person_data );
                $person_map[ $name ] = $wpdb->insert_id;
            } else {
                // Update person data with the most recent entry's data (later rows may have updated info)
                $person_data = array(
                    'beruf'           => $row->beruf,
                    'geburtsort'      => $row->geburtsort,
                    'geburtsdatum'    => ( ! empty( $row->geburtsdatum ) && $row->geburtsdatum !== '0000-00-00' ) ? $row->geburtsdatum : null,
                    'bild'            => $row->bild ? $row->bild : null,
                    'partei'          => $row->partei,
                    'aufgabe_partei'  => $row->aufgabe_partei,
                    'parlament'       => $row->parlament,
                    'parlament_name'  => $row->parlament_name,
                    'status_aktiv'    => intval( $row->status_aktiv ),
                );
                // Only update non-empty fields
                $person_data = array_filter( $person_data, function( $v ) { return $v !== null && $v !== ''; } );
                if ( ! empty( $person_data ) ) {
                    $wpdb->update(
                        $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN,
                        $person_data,
                        array( 'id' => $person_map[ $name ] )
                    );
                }
            }

            // Insert offence
            $offence_data = array(
                'person_id'       => $person_map[ $name ],
                'datum_eintrag'   => $row->datum_eintrag ?: date( 'Y-m-d' ),
                'straftat'        => $row->straftat,
                'urteil'          => $row->urteil,
                'link_quelle'     => $row->link_quelle,
                'aktenzeichen'    => $row->aktenzeichen,
                'bemerkung'       => $row->bemerkung,
                'status_straftat' => $row->status_straftat ?: 'Ermittlungen laufen',
                'freigegeben'     => intval( $row->freigegeben ),
            );
            $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN, $offence_data );
        }

        return count( $rows );
    }

    /* ================================================================
       SCHEMA-UPGRADE
    ================================================================ */
    public static function maybe_upgrade_table() {
        $stored = get_option( 'handschelle_db_version', '0' );
        if ( version_compare( $stored, HANDSCHELLE_VERSION, '>=' ) ) {
            return;
        }

        self::create_tables();

        // If new tables are empty but old flat table exists → migrate
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $person_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$tp}`" );
        if ( $person_count === 0 ) {
            self::migrate_from_legacy();
        }

        update_option( 'handschelle_db_version', HANDSCHELLE_VERSION );
    }

    /* ================================================================
       HELPER: Daten auf Person / Straftat aufteilen
    ================================================================ */
    private static function split_data( $data ) {
        $person_data  = array();
        $offence_data = array();

        foreach ( $data as $key => $value ) {
            if ( in_array( $key, self::$person_fields, true ) ) {
                $person_data[ $key ] = $value;
            } elseif ( in_array( $key, self::$offence_fields, true ) ) {
                $offence_data[ $key ] = $value;
            }
        }

        return array( $person_data, $offence_data );
    }

    /* ================================================================
       LESEN – Alle Einträge (backward-compat: flache JOIN-Rows)
       id = Straftat-ID (für Edit/Delete/Toggle-Actions)
    ================================================================ */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;

        $defaults = array(
            'freigegeben' => 1,
            'orderby'     => 'name',
            'order'       => 'ASC',
            'search'      => '',
            'partei'      => '',
            'name'        => '',
            'limit'       => 0,
            'offset'      => 0,
        );
        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $vals  = array();

        if ( $args['freigegeben'] !== 'all' ) {
            $where[] = 's.freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'p.partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'p.name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(p.name LIKE %s OR s.straftat LIKE %s OR p.partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $sql = "SELECT
                    s.id,
                    p.id   AS person_id,
                    s.datum_eintrag,
                    p.name, p.beruf, p.geburtsort, p.geburtsdatum, p.bild,
                    p.partei, p.aufgabe_partei, p.parlament, p.parlament_name, p.status_aktiv,
                    s.straftat, s.urteil, s.link_quelle, s.aktenzeichen, s.bemerkung, s.status_straftat,
                    p.sm_facebook, p.sm_youtube, p.sm_personal, p.sm_twitter, p.sm_homepage,
                    p.sm_wikipedia, p.sm_sonstige, p.sm_linkedin, p.sm_xing, p.sm_truth_social,
                    s.freigegeben, s.erstellt_am, s.geaendert_am
                FROM `{$ts}` s
                JOIN `{$tp}` p ON p.id = s.person_id";

        if ( ! empty( $where ) ) $sql .= ' WHERE ' . implode( ' AND ', $where );

        $allowed_orderby = array( 'id', 'name', 'partei', 'datum_eintrag', 'erstellt_am' );
        $orderby_field   = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
        $prefix          = in_array( $orderby_field, array( 'name', 'partei' ), true ) ? 'p.' : 's.';
        $order           = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $sql            .= " ORDER BY `{$prefix}{$orderby_field}` {$order}";

        if ( ! empty( $args['limit'] ) ) {
            $sql   .= ' LIMIT %d OFFSET %d';
            $vals[] = intval( $args['limit'] );
            $vals[] = intval( $args['offset'] );
        }

        if ( ! empty( $vals ) ) $sql = $wpdb->prepare( $sql, $vals );
        return $wpdb->get_results( $sql );
    }

    /* ================================================================
       LESEN – Einzelner Eintrag (backward-compat; id = Straftat-ID)
    ================================================================ */
    public static function get_one( $id ) {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT
                 s.id,
                 p.id   AS person_id,
                 s.datum_eintrag,
                 p.name, p.beruf, p.geburtsort, p.geburtsdatum, p.bild,
                 p.partei, p.aufgabe_partei, p.parlament, p.parlament_name, p.status_aktiv,
                 s.straftat, s.urteil, s.link_quelle, s.aktenzeichen, s.bemerkung, s.status_straftat,
                 p.sm_facebook, p.sm_youtube, p.sm_personal, p.sm_twitter, p.sm_homepage,
                 p.sm_wikipedia, p.sm_sonstige, p.sm_linkedin, p.sm_xing, p.sm_truth_social,
                 s.freigegeben, s.erstellt_am, s.geaendert_am
             FROM `{$ts}` s
             JOIN `{$tp}` p ON p.id = s.person_id
             WHERE s.id = %d",
            intval( $id )
        ) );
    }

    /* ================================================================
       LESEN – Person per ID
    ================================================================ */
    public static function get_person( $person_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN . "` WHERE id = %d",
            intval( $person_id )
        ) );
    }

    /* ================================================================
       LESEN – Person per Name
    ================================================================ */
    public static function find_person_by_name( $name ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN . "` WHERE name = %s LIMIT 1",
            $name
        ) );
    }

    /* ================================================================
       LESEN – Alle Straftaten einer Person
    ================================================================ */
    public static function get_offences_by_person( $person_id, $approved_only = false ) {
        global $wpdb;
        $ts    = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        $where = $approved_only ? ' AND freigegeben = 1' : '';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$ts}` WHERE person_id = %d{$where} ORDER BY datum_eintrag ASC, id ASC",
            intval( $person_id )
        ) );
    }

    /* ================================================================
       LESEN – Personen gruppiert mit Straftaten (für Frontend-Anzeige)
       Gibt zurück: [ ['person' => stdClass, 'offences' => [stdClass, ...]], ... ]
    ================================================================ */
    public static function get_persons_with_offences( $args = array() ) {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;

        $defaults = array(
            'freigegeben' => 1,
            'search'      => '',
            'partei'      => '',
            'name'        => '',
            'orderby'     => 'name',
            'order'       => 'ASC',
            'limit'       => 0,
            'offset'      => 0,
        );
        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $vals  = array();

        if ( $args['freigegeben'] !== 'all' ) {
            $where[] = 's.freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'p.partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'p.name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(p.name LIKE %s OR s.straftat LIKE %s OR p.partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $where_sql  = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $order_sql  = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $limit_sql  = '';
        if ( ! empty( $args['limit'] ) ) {
            $limit_sql = $wpdb->prepare( ' LIMIT %d OFFSET %d', intval( $args['limit'] ), intval( $args['offset'] ) );
        }

        // Get distinct persons that have matching offences
        $person_sql = "SELECT DISTINCT p.* FROM `{$tp}` p
                       JOIN `{$ts}` s ON s.person_id = p.id
                       {$where_sql}
                       ORDER BY p.`name` {$order_sql}{$limit_sql}";
        if ( ! empty( $vals ) ) $person_sql = $wpdb->prepare( $person_sql, $vals );
        $persons = $wpdb->get_results( $person_sql );

        $result = array();
        foreach ( $persons as $person ) {
            $fg_filter = $args['freigegeben'] !== 'all' ? intval( $args['freigegeben'] ) : null;
            $offences  = self::get_offences_for_person_display( intval( $person->id ), $fg_filter );
            if ( ! empty( $offences ) ) {
                $result[] = array( 'person' => $person, 'offences' => $offences );
            }
        }

        return $result;
    }

    private static function get_offences_for_person_display( $person_id, $freigegeben = 1 ) {
        global $wpdb;
        $ts    = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        $where = is_null( $freigegeben ) ? '' : $wpdb->prepare( ' AND freigegeben = %d', $freigegeben );
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$ts}` WHERE person_id = %d{$where} ORDER BY datum_eintrag ASC, id ASC",
            $person_id
        ) );
    }

    /* ================================================================
       LESEN – Anzahl Personen mit passenden Straftaten (für Paginierung)
    ================================================================ */
    public static function count_persons( $args = array() ) {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;

        $defaults = array( 'freigegeben' => 1, 'partei' => '', 'name' => '', 'search' => '' );
        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $vals  = array();

        if ( $args['freigegeben'] !== 'all' ) {
            $where[] = 's.freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'p.partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'p.name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(p.name LIKE %s OR s.straftat LIKE %s OR p.partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $sql = "SELECT COUNT(DISTINCT p.id) FROM `{$tp}` p JOIN `{$ts}` s ON s.person_id = p.id";
        if ( ! empty( $where ) ) $sql .= ' WHERE ' . implode( ' AND ', $where );
        if ( ! empty( $vals ) )  $sql  = $wpdb->prepare( $sql, $vals );
        return (int) $wpdb->get_var( $sql );
    }

    /* ================================================================
       EINFÜGEN – Person + Straftat (backward-compat; findet/erstellt Person)
       Gibt Straftat-ID zurück.
    ================================================================ */
    public static function insert( $data ) {
        global $wpdb;

        list( $person_data, $offence_data ) = self::split_data( $data );

        // Freigabe aus $data holen (nicht in offence_fields da es separat behandelt wird)
        $offence_data['freigegeben'] = 0;

        // Find or create person
        $person_id = self::find_or_create_person( $person_data );
        if ( ! $person_id ) return false;

        // Insert offence
        $offence_data['person_id']   = $person_id;
        $offence_data['erstellt_am'] = current_time( 'mysql' );
        if ( empty( $offence_data['datum_eintrag'] ) ) {
            $offence_data['datum_eintrag'] = current_time( 'Y-m-d' );
        }

        $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN, $offence_data );
        return $wpdb->insert_id;
    }

    /* ================================================================
       EINFÜGEN – nur Person
    ================================================================ */
    public static function insert_person( $data ) {
        global $wpdb;
        if ( empty( $data['erstellt_am'] ) )  $data['erstellt_am']  = current_time( 'mysql' );
        if ( empty( $data['geaendert_am'] ) ) $data['geaendert_am'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN, $data );
        return $wpdb->insert_id;
    }

    /* ================================================================
       EINFÜGEN – nur Straftat für existierende Person
    ================================================================ */
    public static function insert_offence( $person_id, $data ) {
        global $wpdb;
        $data['person_id']   = intval( $person_id );
        $data['freigegeben'] = 0;
        $data['erstellt_am'] = current_time( 'mysql' );
        if ( empty( $data['datum_eintrag'] ) ) {
            $data['datum_eintrag'] = current_time( 'Y-m-d' );
        }
        $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN, $data );
        return $wpdb->insert_id;
    }

    /** Hilfsfunktion: Person per Name finden oder neu anlegen */
    private static function find_or_create_person( $person_data ) {
        $name = $person_data['name'] ?? '';
        if ( empty( $name ) ) return false;

        global $wpdb;
        $existing = self::find_person_by_name( $name );
        if ( $existing ) {
            // Update person data with new values (non-empty fields only)
            $updates = array_filter( $person_data, function( $v ) {
                return $v !== null && $v !== '';
            } );
            unset( $updates['name'] ); // don't update name
            if ( ! empty( $updates ) ) {
                $updates['geaendert_am'] = current_time( 'mysql' );
                $wpdb->update(
                    $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN,
                    $updates,
                    array( 'id' => intval( $existing->id ) )
                );
            }
            return intval( $existing->id );
        }

        $person_data['erstellt_am']  = current_time( 'mysql' );
        $person_data['geaendert_am'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN, $person_data );
        return $wpdb->insert_id;
    }

    /* ================================================================
       AKTUALISIEREN – Straftat-ID; aktualisiert Straftat + verknüpfte Person
    ================================================================ */
    public static function update( $id, $data ) {
        global $wpdb;

        list( $person_data, $offence_data ) = self::split_data( $data );

        // freigegeben, datum_eintrag can be in $data directly
        foreach ( array( 'freigegeben', 'datum_eintrag', 'status_straftat' ) as $f ) {
            if ( isset( $data[ $f ] ) && ! isset( $offence_data[ $f ] ) ) {
                $offence_data[ $f ] = $data[ $f ];
            }
        }

        // Update offence
        if ( ! empty( $offence_data ) ) {
            $offence_data['geaendert_am'] = current_time( 'mysql' );
            $wpdb->update(
                $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN,
                $offence_data,
                array( 'id' => intval( $id ) )
            );
        }

        // Update linked person
        if ( ! empty( $person_data ) ) {
            $offence = $wpdb->get_row( $wpdb->prepare(
                "SELECT person_id FROM `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN . "` WHERE id = %d",
                intval( $id )
            ) );
            if ( $offence ) {
                $wpdb->update(
                    $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN,
                    $person_data,
                    array( 'id' => intval( $offence->person_id ) )
                );
            }
        }
    }

    /* ================================================================
       AKTUALISIEREN – nur Person
    ================================================================ */
    public static function update_person( $person_id, $data ) {
        global $wpdb;
        $data['geaendert_am'] = current_time( 'mysql' );
        return $wpdb->update(
            $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN,
            $data,
            array( 'id' => intval( $person_id ) )
        );
    }

    /* ================================================================
       LÖSCHEN – Straftat; löscht Person wenn keine Straftaten mehr
    ================================================================ */
    public static function delete( $id ) {
        global $wpdb;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;

        $offence = $wpdb->get_row( $wpdb->prepare( "SELECT person_id FROM `{$ts}` WHERE id = %d", intval( $id ) ) );
        if ( ! $offence ) return false;

        $result    = $wpdb->delete( $ts, array( 'id' => intval( $id ) ) );
        $remaining = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$ts}` WHERE person_id = %d",
            intval( $offence->person_id )
        ) );

        if ( $remaining === 0 ) {
            $wpdb->delete( $tp, array( 'id' => intval( $offence->person_id ) ) );
        }

        return $result;
    }

    /* ================================================================
       HILFSMETHODEN
    ================================================================ */
    public static function get_distinct_parteien() {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        return $wpdb->get_col(
            "SELECT DISTINCT p.partei FROM `{$tp}` p
             JOIN `{$ts}` s ON s.person_id = p.id
             WHERE s.freigegeben = 1 AND p.partei != ''
             ORDER BY p.partei ASC"
        );
    }

    public static function get_distinct_namen() {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;
        return $wpdb->get_col(
            "SELECT DISTINCT p.name FROM `{$tp}` p
             JOIN `{$ts}` s ON s.person_id = p.id
             WHERE s.freigegeben = 1 AND p.name != ''
             ORDER BY p.name ASC"
        );
    }

    public static function count_all( $args = array() ) {
        global $wpdb;
        $tp = $wpdb->prefix . HANDSCHELLE_DB_TABLE_PERSONEN;
        $ts = $wpdb->prefix . HANDSCHELLE_DB_TABLE_STRAFTATEN;

        $defaults = array( 'freigegeben' => 1, 'partei' => '', 'name' => '', 'search' => '' );
        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $vals  = array();

        if ( $args['freigegeben'] !== 'all' ) {
            $where[] = 's.freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'p.partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'p.name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(p.name LIKE %s OR s.straftat LIKE %s OR p.partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM `{$ts}` s JOIN `{$tp}` p ON p.id = s.person_id";
        if ( ! empty( $where ) ) $sql .= ' WHERE ' . implode( ' AND ', $where );
        if ( ! empty( $vals ) )  $sql  = $wpdb->prepare( $sql, $vals );
        return (int) $wpdb->get_var( $sql );
    }
}
