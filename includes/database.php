<?php
/**
 * Die-Handschelle 2.0 A – Datenbankklasse
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

    public static function create_table() {
        global $wpdb;
        $table           = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `datum_eintrag`   DATE             NOT NULL,
            `name`            VARCHAR(50)      NOT NULL DEFAULT '',
            `beruf`           VARCHAR(50)      NOT NULL DEFAULT '',
            `geburtsort`      VARCHAR(100)     NOT NULL DEFAULT '',
            `geburtsland`     VARCHAR(100)     NOT NULL DEFAULT 'Deutschland',
            `geburtsdatum`    DATE             NULL DEFAULT NULL,
            `verstorben`      TINYINT(1)       NOT NULL DEFAULT 0,
            `dod`             DATE             NULL DEFAULT NULL,
            `spitzname`       VARCHAR(100)     NOT NULL DEFAULT '',
            `private_email`   VARCHAR(200)     NOT NULL DEFAULT '',
            `oeffentliche_email` VARCHAR(200)  NOT NULL DEFAULT '',
            `bild`            TEXT,
            `partei`          VARCHAR(50)      NOT NULL DEFAULT '',
            `aufgabe_partei`  VARCHAR(100)     NOT NULL DEFAULT '',
            `parlament`       VARCHAR(100)     NOT NULL DEFAULT '',
            `parlament_name`  VARCHAR(50)      NOT NULL DEFAULT '',
            `status_aktiv`    TINYINT(1)       NOT NULL DEFAULT 1,
            `straftat`        TEXT             NOT NULL,
            `urteil`          VARCHAR(200)     NOT NULL DEFAULT '',
            `link_quelle`     TEXT,
            `aktenzeichen`    VARCHAR(50)      NOT NULL DEFAULT '',
            `bemerkung_person` VARCHAR(500)    NOT NULL DEFAULT '',
            `bemerkung`       TEXT,
            `status_straftat` VARCHAR(50)      NOT NULL DEFAULT 'Ermittlungen laufen',
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
            `freigegeben`     TINYINT(1)       NOT NULL DEFAULT 0,
            `erstellt_am`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `geaendert_am`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_freigegeben` (`freigegeben`),
            KEY `idx_name`        (`name`),
            KEY `idx_partei`      (`partei`)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function drop_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE . "`" );
    }

    public static function truncate_table() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE . "`" );
        $wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE . "_offences`" );
    }

    public static function get_all( $args = array() ) {
        global $wpdb;
        $table    = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
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
            $where[] = 'freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(name LIKE %s OR straftat LIKE %s OR partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $sql     = "SELECT * FROM `{$table}`";
        if ( ! empty( $where ) ) $sql .= ' WHERE ' . implode( ' AND ', $where );

        $allowed = array( 'id', 'name', 'partei', 'datum_eintrag', 'erstellt_am' );
        $orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'name';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $sql    .= " ORDER BY `{$orderby}` {$order}";

        if ( ! empty( $args['limit'] ) ) {
            $sql   .= ' LIMIT %d OFFSET %d';
            $vals[] = intval( $args['limit'] );
            $vals[] = intval( $args['offset'] );
        }
        if ( ! empty( $vals ) ) $sql = $wpdb->prepare( $sql, $vals );
        return $wpdb->get_results( $sql );
    }

    public static function get_one( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", intval( $id ) ) );
    }

    public static function insert( $data ) {
        global $wpdb;
        $table               = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $data['freigegeben'] = 0;
        $data['erstellt_am'] = current_time( 'mysql' );
        $wpdb->insert( $table, $data );
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . HANDSCHELLE_DB_TABLE, $data, array( 'id' => intval( $id ) ) );
    }

    public static function get_distinct_parteien() {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        return $wpdb->get_col( "SELECT DISTINCT partei FROM `{$table}` WHERE freigegeben=1 AND partei != '' ORDER BY partei ASC" );
    }

    public static function get_distinct_namen() {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        return $wpdb->get_col( "SELECT DISTINCT name FROM `{$table}` WHERE freigegeben=1 AND name != '' ORDER BY name ASC" );
    }

    public static function get_distinct_straftaten() {
        global $wpdb;
        $main = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $off  = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        return $wpdb->get_col(
            "SELECT DISTINCT straftat FROM `{$main}` WHERE freigegeben=1 AND straftat != ''
             UNION
             SELECT DISTINCT o.straftat FROM `{$off}` o
             INNER JOIN `{$main}` e ON e.id = o.entry_id
             WHERE o.freigegeben=1 AND o.straftat != ''
             ORDER BY straftat ASC"
        );
    }

    public static function get_entries_by_straftat( $straftat ) {
        global $wpdb;
        $main = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $off  = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        // Entries where main straftat matches OR any approved offence straftat matches
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT e.* FROM `{$main}` e
             LEFT JOIN `{$off}` o ON o.entry_id = e.id AND o.freigegeben = 1
             WHERE e.freigegeben = 1
               AND (e.straftat = %s OR o.straftat = %s)
             ORDER BY e.name ASC",
            $straftat, $straftat
        ) );
    }

    /**
     * Returns one representative row (highest ID) per unique person name.
     * Used for the [handschelle-smart] dropdown.
     * Includes freigegeben=1 and pending (freigegeben=0) entries so admins
     * can link new Straftaten to any known person.
     *
     * @return array  Array of objects with id, name, partei.
     */
    public static function get_persons_dropdown() {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        return $wpdb->get_results(
            "SELECT t1.id, t1.name, t1.partei
             FROM `{$table}` t1
             INNER JOIN (
                 SELECT MAX(id) AS max_id
                 FROM `{$table}`
                 WHERE name != ''
                 GROUP BY name
             ) t2 ON t1.id = t2.max_id
             ORDER BY t1.name ASC"
        );
    }

    public static function count_all( $args = array() ) {
        global $wpdb;
        $table    = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $defaults = array(
            'freigegeben' => 1,
            'partei'      => '',
            'name'        => '',
            'search'      => '',
        );
        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $vals  = array();

        if ( $args['freigegeben'] !== 'all' ) {
            $where[] = 'freigegeben = %d';
            $vals[]  = intval( $args['freigegeben'] );
        }
        if ( ! empty( $args['partei'] ) ) { $where[] = 'partei = %s'; $vals[] = $args['partei']; }
        if ( ! empty( $args['name'] ) )   { $where[] = 'name = %s';   $vals[] = $args['name']; }
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(name LIKE %s OR straftat LIKE %s OR partei LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $vals[]  = $like; $vals[] = $like; $vals[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM `{$table}`";
        if ( ! empty( $where ) ) $sql .= ' WHERE ' . implode( ' AND ', $where );
        if ( ! empty( $vals ) ) $sql = $wpdb->prepare( $sql, $vals );
        return (int) $wpdb->get_var( $sql );
    }

    public static function recreate_table() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . HANDSCHELLE_DB_TABLE . "_offences`" );
        self::drop_table();
        self::create_table();
        self::create_offences_table();
    }

    /**
     * Checks whether the current DB schema matches the plugin version.
     * If not (or if columns are missing), runs dbDelta() to add any missing fields.
     * dbDelta() never removes columns, so existing data is always preserved.
     */
    public static function maybe_upgrade_table() {
        // Always ensure the offences table exists — idempotent via IF NOT EXISTS.
        // This covers cases where the table was never created (e.g. MySQL 8 strict
        // mode rejected the old DEFAULT '' syntax during a previous upgrade) or was
        // accidentally dropped.
        self::create_offences_table();

        $stored = get_option( 'handschelle_db_version', '0' );
        if ( version_compare( $stored, HANDSCHELLE_VERSION, '>=' ) ) {
            return;
        }
        self::create_table(); // dbDelta inside create_table() adds missing columns
        self::create_offences_table();

        // v8.1: straftat was VARCHAR(200), must be TEXT (dbDelta cannot change column types)
        if ( version_compare( $stored, '8.1', '<' ) ) {
            global $wpdb;
            $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
            $wpdb->query( "ALTER TABLE `{$table}` MODIFY COLUMN `straftat` TEXT NOT NULL" );
        }

        // v12.Alpha.04: add freigegeben column to offences table
        if ( version_compare( $stored, '12.Alpha.04', '<' ) ) {
            global $wpdb;
            $off_table = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
            $col_exists = $wpdb->get_var( "SHOW COLUMNS FROM `{$off_table}` LIKE 'freigegeben'" );
            if ( ! $col_exists ) {
                $wpdb->query( "ALTER TABLE `{$off_table}` ADD COLUMN `freigegeben` TINYINT(1) NOT NULL DEFAULT 0 AFTER `erstellt_am`" );
            }
        }

        update_option( 'handschelle_db_version', HANDSCHELLE_VERSION );
    }

    /* ================================================================
       STRAFTATEN-TABELLE (multiple offences per person)
    ================================================================ */

    public static function create_offences_table() {
        global $wpdb;
        $table           = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id`        INT(11) UNSIGNED NOT NULL,
            `straftat`        TEXT             NOT NULL,
            `urteil`          VARCHAR(200)     NOT NULL DEFAULT '',
            `link_quelle`     TEXT,
            `aktenzeichen`    VARCHAR(50)      NOT NULL DEFAULT '',
            `bemerkung`       TEXT,
            `status_straftat` VARCHAR(50)      NOT NULL DEFAULT 'Ermittlungen laufen',
            `datum_eintrag`   DATE             NULL DEFAULT NULL,
            `erstellt_am`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `freigegeben`     TINYINT(1)       NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_entry_id`    (`entry_id`),
            KEY `idx_freigegeben` (`freigegeben`)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * @param int        $entry_id
     * @param int|string $freigegeben  1 = approved only (default), 0 = pending only, 'all' = no filter
     */
    public static function get_offences( $entry_id, $freigegeben = 1 ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        if ( $freigegeben === 'all' ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE entry_id = %d ORDER BY id ASC",
                intval( $entry_id )
            ) );
        }
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE entry_id = %d AND freigegeben = %d ORDER BY id ASC",
            intval( $entry_id ), intval( $freigegeben )
        ) );
    }

    /**
     * Returns all pending offences joined with person name and partei.
     *
     * @param int|string $freigegeben  0 = pending (default), 1 = approved, 'all' = all
     * @return array
     */
    public static function get_offences_with_person( $freigegeben = 0 ) {
        global $wpdb;
        $off   = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        $main  = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        if ( $freigegeben === 'all' ) {
            return $wpdb->get_results(
                "SELECT o.*, e.name AS person_name, e.partei AS person_partei, e.bild AS person_bild
                 FROM `{$off}` o
                 LEFT JOIN `{$main}` e ON e.id = o.entry_id
                 ORDER BY o.id DESC"
            );
        }
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT o.*, e.name AS person_name, e.partei AS person_partei, e.bild AS person_bild
             FROM `{$off}` o
             LEFT JOIN `{$main}` e ON e.id = o.entry_id
             WHERE o.freigegeben = %d
             ORDER BY o.id DESC",
            intval( $freigegeben )
        ) );
    }

    /** Count offences by freigegeben status (0=pending, 1=approved, 'all'=all). */
    public static function count_offences( $freigegeben = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        if ( $freigegeben === 'all' ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
        }
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE freigegeben = %d",
            intval( $freigegeben )
        ) );
    }

    public static function insert_offence( $entry_id, $data ) {
        global $wpdb;
        $table               = $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences';
        $data['entry_id']    = intval( $entry_id );
        $data['erstellt_am'] = current_time( 'mysql' );
        $wpdb->insert( $table, $data );
        return $wpdb->insert_id;
    }

    public static function update_offence( $id, $data ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences',
            $data,
            array( 'id' => intval( $id ) )
        );
    }

    public static function delete_offence( $id ) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences',
            array( 'id' => intval( $id ) )
        );
    }

    public static function delete_offences_for_entry( $entry_id ) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . HANDSCHELLE_DB_TABLE . '_offences',
            array( 'entry_id' => intval( $entry_id ) )
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        self::delete_offences_for_entry( $id );
        return $wpdb->delete( $wpdb->prefix . HANDSCHELLE_DB_TABLE, array( 'id' => intval( $id ) ) );
    }
}
