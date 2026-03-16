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
            `straftat`        VARCHAR(200)     NOT NULL DEFAULT '',
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

    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . HANDSCHELLE_DB_TABLE, array( 'id' => intval( $id ) ) );
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
        self::drop_table();
        self::create_table();
    }

    /**
     * Checks whether the current DB schema matches the plugin version.
     * If not (or if columns are missing), runs dbDelta() to add any missing fields.
     * dbDelta() never removes columns, so existing data is always preserved.
     */
    public static function maybe_upgrade_table() {
        $stored = get_option( 'handschelle_db_version', '0' );
        if ( version_compare( $stored, HANDSCHELLE_VERSION, '>=' ) ) {
            return;
        }
        self::create_table(); // dbDelta inside create_table() adds missing columns
        update_option( 'handschelle_db_version', HANDSCHELLE_VERSION );
    }
}
