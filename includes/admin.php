<?php
/**
 * Die-Handschelle 3.00 – Admin-Panel
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

class Handschelle_Admin {

    public function __construct() {
        add_action( 'admin_menu',    array( $this, 'register_menus' ) );
        add_action( 'admin_init',    array( $this, 'handle_actions' ) );
        add_action( 'admin_notices', array( $this, 'show_notice' ) );
        add_action( 'admin_head',    array( $this, 'hide_edit_menu_item' ) );
    }

    /* ── Bearbeitungsseite aus Menü ausblenden (bleibt aber erreichbar) ── */
    public function hide_edit_menu_item() {
        echo '<style>#adminmenu a[href="admin.php?page=handschelle-edit"]{display:none!important}</style>';
    }

    /* ================================================================
       MENÜS
    ================================================================ */
    public function register_menus() {
        add_menu_page( 'Die-Handschelle', 'Die-Handschelle', 'manage_options', 'handschelle', array( $this, 'page_overview' ), 'dashicons-lock', 25 );
        add_submenu_page( 'handschelle', 'Übersicht',          'Übersicht',       'manage_options', 'handschelle',              array( $this, 'page_overview' ) );
        add_submenu_page( 'handschelle', 'Neuer Eintrag',      '+ Neuer Eintrag', 'manage_options', 'handschelle-add',           array( $this, 'page_add' ) );
        add_submenu_page( 'handschelle', 'Eintrag bearbeiten', 'Bearbeiten',      'manage_options', 'handschelle-edit',          array( $this, 'page_edit' ) );
        add_submenu_page( 'handschelle', 'Import / Export',    'Import / Export', 'manage_options', 'handschelle-import-export', array( $this, 'page_import_export' ) );
        add_submenu_page( 'handschelle', 'Bilder',             'Bilder',          'manage_options', 'handschelle-bilder',        array( $this, 'page_bilder' ) );
        add_submenu_page( 'handschelle', 'Backup & Restore',  'Backup & Restore','manage_options', 'handschelle-backup',        array( $this, 'page_backup' ) );
        add_submenu_page( 'handschelle', 'Datenbank',          'Datenbank',       'manage_options', 'handschelle-db',            array( $this, 'page_database' ) );
    }

    /* ================================================================
       AKTIONEN
    ================================================================ */
    public function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $action = sanitize_text_field( wp_unslash( $_REQUEST['hs_action'] ?? '' ) );
        if ( empty( $action ) ) return;
        check_admin_referer( 'handschelle_admin_action' );

        switch ( $action ) {
            case 'add':
                $data      = handschelle_sanitize_entry( $_POST );
                $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload', $data['name'] ?? '', $data['partei'] ?? '' );
                if ( $attach_id ) $data['bild'] = $attach_id;
                Handschelle_Database::insert( $data );
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Eintrag gespeichert – bitte freigeben.' );
                break;

            case 'edit':
                $id = intval( $_POST['id'] ?? 0 );
                if ( ! $id ) break;
                $data      = handschelle_sanitize_entry( $_POST );
                $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload', $data['name'] ?? '', $data['partei'] ?? '' );
                if ( $attach_id ) $data['bild'] = $attach_id;
                $data['freigegeben'] = isset( $_POST['freigegeben'] ) ? 1 : 0;
                Handschelle_Database::update( $id, $data );
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Eintrag aktualisiert.' );
                break;

            case 'delete':
                $id    = intval( $_REQUEST['id'] ?? 0 );
                $entry = $id ? Handschelle_Database::get_one( $id ) : null;
                if ( $entry ) {
                    if ( ! empty( $entry->bild ) && is_numeric( $entry->bild ) ) wp_delete_attachment( intval( $entry->bild ), true );
                    Handschelle_Database::delete( $id );
                }
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Eintrag gelöscht.' );
                break;

            case 'toggle_freigabe':
                $id    = intval( $_REQUEST['id'] ?? 0 );
                $entry = $id ? Handschelle_Database::get_one( $id ) : null;
                if ( $entry ) {
                    $neu = $entry->freigegeben ? 0 : 1;
                    Handschelle_Database::update( $id, array( 'freigegeben' => $neu ) );
                    $this->redirect( admin_url( 'admin.php?page=handschelle' ), $neu ? 'Eintrag freigegeben.' : 'Eintrag gesperrt.' );
                }
                break;

            case 'toggle_aktiv':
                $id    = intval( $_REQUEST['id'] ?? 0 );
                $entry = $id ? Handschelle_Database::get_one( $id ) : null;
                if ( $entry ) {
                    Handschelle_Database::update( $id, array( 'status_aktiv' => $entry->status_aktiv ? 0 : 1 ) );
                    $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Status geändert.' );
                }
                break;

            case 'bulk_action':
                $op  = sanitize_text_field( $_POST['hs_bulk_op'] ?? '' );
                $ids = array_map( 'intval', (array) ( $_POST['hs_bulk_ids'] ?? array() ) );
                $ids = array_filter( $ids );
                if ( ! empty( $ids ) && in_array( $op, array( 'approve', 'reject', 'delete' ), true ) ) {
                    foreach ( $ids as $bulk_id ) {
                        if ( $op === 'approve' ) {
                            Handschelle_Database::update( $bulk_id, array( 'freigegeben' => 1 ) );
                        } elseif ( $op === 'reject' ) {
                            Handschelle_Database::update( $bulk_id, array( 'freigegeben' => 0 ) );
                        } elseif ( $op === 'delete' ) {
                            $entry = Handschelle_Database::get_one( $bulk_id );
                            if ( $entry && ! empty( $entry->bild ) && is_numeric( $entry->bild ) ) {
                                wp_delete_attachment( intval( $entry->bild ), true );
                            }
                            Handschelle_Database::delete( $bulk_id );
                        }
                    }
                    $label = array( 'approve' => 'freigegeben', 'reject' => 'gesperrt', 'delete' => 'gelöscht' );
                    $this->redirect( admin_url( 'admin.php?page=handschelle' ), count( $ids ) . ' Eintrag/Einträge ' . $label[ $op ] . '.' );
                }
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Keine Einträge ausgewählt.' );
                break;

            case 'export_csv':        $this->export_csv(); break;
            case 'import_csv':        $this->import_csv(); break;
            case 'export_images_zip': $this->export_images_zip(); break;
            case 'import_images_zip': $this->import_images_zip(); break;
            case 'backup_full':       $this->backup_full(); break;
            case 'restore_full':      $this->restore_full(); break;

            case 'truncate':
                Handschelle_Database::truncate_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbank geleert.' );
                break;

            case 'recreate':
                Handschelle_Database::drop_table();
                Handschelle_Database::create_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbank neu erstellt.' );
                break;

            case 'drop':
                Handschelle_Database::drop_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbanktabelle gelöscht.' );
                break;
        }
    }

    /* ================================================================
       CSV EXPORT
    ================================================================ */
    private function export_csv() {
        $entries = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=handschelle-export-' . date( 'Y-m-d' ) . '.csv' );
        header( 'Pragma: no-cache' );
        $out  = fopen( 'php://output', 'w' );
        fputs( $out, "\xEF\xBB\xBF" );
        $cols = array( 'id','datum_eintrag','name','beruf','geburtsort','geburtsdatum','bild','partei','aufgabe_partei','parlament','parlament_name','status_aktiv','straftat','urteil','link_quelle','aktenzeichen','bemerkung','status_straftat','sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_sonstige','sm_linkedin','sm_xing','sm_truth_social','freigegeben','erstellt_am','geaendert_am' );
        fputcsv( $out, $cols, ';' );
        foreach ( $entries as $e ) {
            $row = array();
            foreach ( $cols as $c ) $row[] = $e->$c ?? '';
            fputcsv( $out, $row, ';' );
        }
        fclose( $out );
        exit;
    }

    /* ================================================================
       CSV IMPORT
    ================================================================ */
    private function import_csv() {
        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) return;
        $fh = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
        if ( ! $fh ) return;
        $bom = fread( $fh, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) rewind( $fh );
        $headers = fgetcsv( $fh, 0, ';' );
        if ( empty( $headers ) ) { fclose( $fh ); return; }
        // Header-based column mapping: works with old (26 col) and new (31 col) CSVs
        $col_map = array_flip( array_map( 'trim', $headers ) );
        $g = function( $field ) use ( &$row, $col_map ) {
            return ( isset( $col_map[$field] ) && isset( $row[$col_map[$field]] ) ) ? $row[$col_map[$field]] : '';
        };
        $count = 0;
        while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
            if ( count( $row ) < 10 ) continue;
            // Resolve bild: if numeric ID doesn't exist locally, clear it
            $bild_raw = sanitize_text_field( $g('bild') );
            if ( is_numeric( $bild_raw ) && intval( $bild_raw ) > 0 && ! get_attached_file( intval( $bild_raw ) ) ) {
                $bild_raw = '';
            }
            $geburtsdatum_raw = sanitize_text_field( $g('geburtsdatum') );
            $geburtsdatum = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
            Handschelle_Database::insert( array(
                'datum_eintrag'   => sanitize_text_field( $g('datum_eintrag') ) ?: date('Y-m-d'),
                'name'            => substr( sanitize_text_field( $g('name') ), 0, 50 ),
                'beruf'           => substr( sanitize_text_field( $g('beruf') ), 0, 50 ),
                'geburtsort'      => substr( sanitize_text_field( $g('geburtsort') ), 0, 100 ),
                'geburtsdatum'    => $geburtsdatum,
                'bild'            => $bild_raw,
                'partei'          => substr( sanitize_text_field( $g('partei') ), 0, 50 ),
                'aufgabe_partei'  => substr( sanitize_text_field( $g('aufgabe_partei') ), 0, 100 ),
                'parlament'       => sanitize_text_field( $g('parlament') ),
                'parlament_name'  => substr( sanitize_text_field( $g('parlament_name') ), 0, 50 ),
                'status_aktiv'    => intval( $g('status_aktiv') ),
                'straftat'        => substr( sanitize_textarea_field( $g('straftat') ), 0, 200 ),
                'urteil'          => substr( sanitize_text_field( $g('urteil') ), 0, 50 ),
                'link_quelle'     => esc_url_raw( $g('link_quelle') ),
                'aktenzeichen'    => substr( sanitize_text_field( $g('aktenzeichen') ), 0, 50 ),
                'bemerkung'       => sanitize_textarea_field( $g('bemerkung') ),
                'status_straftat' => sanitize_text_field( $g('status_straftat') ) ?: 'Ermittlungen laufen',
                'sm_facebook'     => esc_url_raw( $g('sm_facebook') ),
                'sm_youtube'      => esc_url_raw( $g('sm_youtube') ),
                'sm_personal'     => esc_url_raw( $g('sm_personal') ),
                'sm_twitter'      => esc_url_raw( $g('sm_twitter') ),
                'sm_homepage'     => esc_url_raw( $g('sm_homepage') ),
                'sm_wikipedia'    => esc_url_raw( $g('sm_wikipedia') ),
                'sm_sonstige'     => esc_url_raw( $g('sm_sonstige') ),
                'sm_linkedin'     => esc_url_raw( $g('sm_linkedin') ),
                'sm_xing'         => esc_url_raw( $g('sm_xing') ),
                'sm_truth_social' => esc_url_raw( $g('sm_truth_social') ),
            ) );
            $count++;
        }
        fclose( $fh );
        $this->redirect( admin_url( 'admin.php?page=handschelle' ), "{$count} Einträge importiert (Freigabe ausstehend)." );
    }

    /* ================================================================
       HILFSMETHODEN
    ================================================================ */
    private function redirect( $url, $msg ) {
        set_transient( 'hs_admin_notice_' . get_current_user_id(), $msg, 30 );
        wp_safe_redirect( $url );
        exit;
    }

    public function show_notice() {
        $key = 'hs_admin_notice_' . get_current_user_id();
        $msg = get_transient( $key );
        if ( $msg ) {
            delete_transient( $key );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
    }

    private function hs_footer() {
        return '<p class="hs-page-footer"><a href="https://github.com/VilDeMil/handschelle" target="_blank" rel="noopener noreferrer">github.com/VilDeMil/handschelle</a></p>';
    }

    /* ================================================================
       SEITE: ÜBERSICHT
    ================================================================ */
    public function page_overview() {
        $filter      = sanitize_text_field( $_GET['hs_filter'] ?? 'all' );
        $fg_filter   = $filter === 'approved' ? 1 : ( $filter === 'pending' ? 0 : 'all' );
        $entries     = Handschelle_Database::get_all( array( 'freigegeben' => $fg_filter, 'orderby' => 'erstellt_am', 'order' => 'DESC' ) );
        $all_entries = Handschelle_Database::get_all( array( 'freigegeben' => 'all', 'orderby' => 'erstellt_am', 'order' => 'DESC' ) );
        $total       = count( $all_entries );
        $pending     = count( array_filter( $all_entries, fn( $e ) => ! $e->freigegeben ) );
        $approved    = $total - $pending;
        $nonce       = wp_create_nonce( 'handschelle_admin_action' );
        ?>
        <div class="wrap hs-wrap">
            <h1>🔒 Die-Handschelle <span class="hs-version">6.8</span></h1>
            <div class="hs-stats-bar">
                <span>Gesamt: <strong><?php echo $total; ?></strong></span>
                <span>Ausstehend: <strong class="<?php echo $pending ? 'hs-warn' : ''; ?>"><?php echo $pending; ?></strong></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle-add' ) ); ?>" class="button button-primary hs-btn">+ Neuer Eintrag</a>
            </div>

            <!-- Filter Tabs -->
            <div class="hs-filter-tabs">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle&hs_filter=all' ) ); ?>"
                   class="hs-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    Alle <span class="hs-filter-count"><?php echo $total; ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle&hs_filter=pending' ) ); ?>"
                   class="hs-filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    ⏳ Ausstehend <span class="hs-filter-count"><?php echo $pending; ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle&hs_filter=approved' ) ); ?>"
                   class="hs-filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                    ✅ Freigegeben <span class="hs-filter-count"><?php echo $approved; ?></span>
                </a>
            </div>

            <!-- Bulk Action Form -->
            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="hs-bulk-form">
                <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                <input type="hidden" name="page"      value="handschelle">
                <input type="hidden" name="hs_action" value="bulk_action">

                <div class="hs-bulk-bar">
                    <label class="hs-bulk-select-all">
                        <input type="checkbox" id="hs-bulk-all" class="hs-bulk-checkbox"> Alle auswählen
                    </label>
                    <select name="hs_bulk_op" class="hs-bulk-select">
                        <option value="">-- Bulk-Aktion wählen --</option>
                        <option value="approve">✅ Freigeben</option>
                        <option value="reject">🚫 Sperren</option>
                        <option value="delete">🗑 Löschen</option>
                    </select>
                    <button type="submit" class="button hs-btn"
                            onclick="return document.querySelectorAll('.hs-bulk-ids:checked').length > 0 || alert('Bitte mindestens einen Eintrag auswählen.')">
                        Ausführen
                    </button>
                </div>

                <table class="widefat fixed striped hs-admin-table">
                    <thead>
                        <tr>
                            <th style="width:36px"><input type="checkbox" id="hs-bulk-all-top" class="hs-bulk-checkbox"></th>
                            <th style="width:70px">Bild</th>
                            <th>Name</th>
                            <th style="width:50px">Alter</th>
                            <th>Partei</th>
                            <th>Straftat</th>
                            <th style="width:90px">Status</th>
                            <th style="width:120px">Freigabe</th>
                            <th style="width:300px">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $entries ) ) : ?>
                        <tr><td colspan="8" style="text-align:center;padding:2rem;color:#999;">Keine Einträge vorhanden.</td></tr>
                    <?php endif; ?>
                    <?php foreach ( $entries as $e ) :
                        $img_url = handschelle_get_image_url( $e->bild );
                    ?>
                        <tr>
                            <td><input type="checkbox" name="hs_bulk_ids[]" value="<?php echo intval( $e->id ); ?>" class="hs-bulk-ids hs-bulk-checkbox"></td>
                            <td>
                                <?php if ( $img_url ) : ?>
                                    <img src="<?php echo esc_url( $img_url ); ?>" style="width:56px;height:56px;object-fit:cover;border-radius:4px;">
                                <?php else : ?>
                                    <div style="width:56px;height:56px;background:#eee;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">👤</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $e->name ); ?></strong><br><small><?php echo esc_html( $e->beruf ); ?></small></td>
                            <td><?php $age = handschelle_calc_age( $e->geburtsdatum ?? '' ); echo $age !== null ? $age : '—'; ?></td>
                            <td><?php echo esc_html( $e->partei ); ?><br><small><?php echo esc_html( $e->aufgabe_partei ); ?></small></td>
                            <td><?php echo esc_html( mb_substr( $e->straftat, 0, 80 ) ) . ( mb_strlen( $e->straftat ) > 80 ? '…' : '' ); ?></td>
                            <td><?php echo $e->status_aktiv ? '<span class="hs-badge hs-badge-aktiv">Aktiv</span>' : '<span class="hs-badge hs-badge-inaktiv">Inaktiv</span>'; ?></td>
                            <td><?php echo $e->freigegeben ? '<span class="hs-badge hs-badge-aktiv">✅ Freigegeben</span>' : '<span class="hs-badge hs-badge-pending">⏳ Ausstehend</span>'; ?></td>
                            <td class="hs-actions">
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-edit&id={$e->id}" ) ); ?>" class="button button-small">✏ Bearbeiten</a>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle&hs_action=toggle_freigabe&id={$e->id}&_wpnonce={$nonce}" ) ); ?>" class="button button-small"><?php echo $e->freigegeben ? '🚫 Sperren' : '✅ Freigeben'; ?></a>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle&hs_action=toggle_aktiv&id={$e->id}&_wpnonce={$nonce}" ) ); ?>" class="button button-small"><?php echo $e->status_aktiv ? '⏸ Deaktiv.' : '▶ Aktivieren'; ?></a>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle&hs_action=delete&id={$e->id}&_wpnonce={$nonce}" ) ); ?>" class="button button-small hs-btn-delete" onclick="return confirm('Eintrag wirklich löschen?')">🗑 Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?php echo $this->hs_footer(); ?>
        </div>
        <script>
        (function(){
            function syncCheckboxes(source) {
                document.querySelectorAll('.hs-bulk-ids').forEach(function(cb){ cb.checked = source.checked; });
            }
            var bulkAll = document.getElementById('hs-bulk-all');
            var bulkAllTop = document.getElementById('hs-bulk-all-top');
            if (bulkAll) bulkAll.addEventListener('change', function(){ syncCheckboxes(this); if(bulkAllTop) bulkAllTop.checked = this.checked; });
            if (bulkAllTop) bulkAllTop.addEventListener('change', function(){ syncCheckboxes(this); if(bulkAll) bulkAll.checked = this.checked; });
        })();
        </script>
        <?php
    }

    /* ================================================================
       SEITE: NEUER EINTRAG / BEARBEITEN
    ================================================================ */
    public function page_add() {
        echo '<div class="wrap hs-wrap"><h1>➕ Neuer Eintrag</h1>' . $this->render_form( null ) . $this->hs_footer() . '</div>';
    }

    public function page_edit() {
        $id    = intval( $_GET['id'] ?? 0 );
        $entry = $id ? Handschelle_Database::get_one( $id ) : null;
        if ( ! $entry ) {
            echo '<div class="wrap hs-wrap"><div class="notice notice-error"><p>Eintrag nicht gefunden.</p></div></div>';
            return;
        }
        echo '<div class="wrap hs-wrap"><h1>✏ Bearbeiten: ' . esc_html( $entry->name ) . '</h1>' . $this->render_form( $entry ) . $this->hs_footer() . '</div>';
    }

    /* ================================================================
       FORMULAR (Neu + Bearbeiten)
    ================================================================ */
    public function render_form( $entry = null ) {
        $is_edit    = ! is_null( $entry );
        $parlaments = handschelle_parlaments();
        $st_opts    = handschelle_status_straftat_options();
        $sm_fields  = array(
            'sm_facebook'     => '📘 Facebook',
            'sm_youtube'      => '▶ YouTube',
            'sm_personal'     => '👤 Persönliches Profil',
            'sm_twitter'      => '🐦 Twitter / X',
            'sm_homepage'     => '🌐 Persönliche Homepage',
            'sm_wikipedia'    => '📖 Wikipedia',
            'sm_linkedin'     => '💼 LinkedIn',
            'sm_xing'         => '💼 Xing',
            'sm_truth_social' => '🗣 Truth Social',
            'sm_sonstige'     => '🔗 Sonstige',
        );
        $v = function( $field, $default = '' ) use ( $entry ) {
            if ( ! $entry ) return esc_attr( $default );
            return esc_attr( $entry->$field ?? $default );
        };
        ob_start();
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" enctype="multipart/form-data" class="hs-form">
            <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
            <input type="hidden" name="hs_action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <input type="hidden" name="page" value="<?php echo $is_edit ? 'handschelle-edit' : 'handschelle-add'; ?>">
            <?php if ( $is_edit ) : ?><input type="hidden" name="id" value="<?php echo esc_attr( $entry->id ); ?>"><?php endif; ?>

            <div class="hs-form-section">
                <h2>📋 Eintragsdetails</h2>
                <div class="hs-form-grid">
                    <div class="hs-field"><label>Datum Eintrag</label><input type="date" name="datum_eintrag" value="<?php echo $v( 'datum_eintrag', date('Y-m-d') ); ?>" required></div>
                    <div class="hs-field">
                        <label>Name <span>(max. 50 Zeichen)</span></label>
                        <input type="text" name="name" maxlength="50" value="<?php echo $v('name'); ?>" placeholder="Vor- und Nachname" required>
                        <?php if ( $is_edit && $v('name') ) : ?>
                        <div class="hs-search-buttons">
                            <a href="<?php echo esc_url( 'https://www.google.com/search?q=' . urlencode( $entry->name ) ); ?>" target="_blank" rel="noopener" class="button button-secondary hs-search-btn">🔍 Google</a>
                            <a href="<?php echo esc_url( 'https://www.qwant.com/?l=de&q=' . urlencode( $entry->name ) ); ?>" target="_blank" rel="noopener" class="button button-secondary hs-search-btn">🔍 Qwant</a>
                            <a href="<?php echo esc_url( 'https://duckduckgo.com/?q=' . urlencode( $entry->name ) ); ?>" target="_blank" rel="noopener" class="button button-secondary hs-search-btn">🔍 DuckDuckGo</a>
                            <a href="<?php echo esc_url( 'https://www.bing.com/search?q=' . urlencode( $entry->name ) ); ?>" target="_blank" rel="noopener" class="button button-secondary hs-search-btn">🔍 Bing</a>
                            <a href="<?php echo esc_url( 'https://www.abgeordnetenwatch.de/profile?politician_search_keys=' . urlencode( $entry->name ) ); ?>" target="_blank" rel="noopener" class="button button-secondary hs-search-btn">🏛 Abgeordnetenwatch</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="hs-field"><label>Beruf <span>(max. 50 Zeichen)</span></label><input type="text" name="beruf" maxlength="50" value="<?php echo $v('beruf'); ?>" placeholder="z.B. Politiker"></div>
                    <div class="hs-field"><label>Geburtsort <span>(max. 100 Zeichen)</span></label><input type="text" name="geburtsort" maxlength="100" value="<?php echo $v('geburtsort'); ?>" placeholder="z.B. Berlin"></div>
                    <div class="hs-field"><label>Geburtsdatum</label><input type="date" name="geburtsdatum" value="<?php echo esc_attr( ( ! empty($entry->geburtsdatum) && $entry->geburtsdatum !== '0000-00-00' ) ? $entry->geburtsdatum : '' ); ?>"></div>
                    <div class="hs-field hs-field-full">
                        <label>Bild</label>
                        <div class="hs-media-picker">
                            <?php $cur_img = $is_edit ? handschelle_get_image_url( $entry->bild ) : ''; ?>
                            <div class="hs-media-preview" id="hs-media-preview-bild">
                                <?php if ( $cur_img ) : ?>
                                    <img src="<?php echo esc_url( $cur_img ); ?>" style="max-height:100px;border-radius:4px;display:block;margin-bottom:.4rem;">
                                    <small>ID: <?php echo esc_html( $entry->bild ); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="hs-media-picker-row">
                                <input type="hidden" name="bild" id="hs-media-id-bild"
                                       class="hs-media-id"
                                       value="<?php echo $v('bild'); ?>">
                                <button type="button" class="button button-primary hs-media-btn"
                                        data-target-id="hs-media-id-bild"
                                        data-preview-id="hs-media-preview-bild">
                                    🖼 Medienbibliothek öffnen
                                </button>
                                <?php if ( $is_edit && $v('bild') ) : ?>
                                <button type="button" class="button hs-media-remove-btn"
                                        data-target-id="hs-media-id-bild"
                                        data-preview-id="hs-media-preview-bild">
                                    ✕ Bild entfernen
                                </button>
                                <?php endif; ?>
                                <span class="hs-media-sep">oder neu hochladen:</span>
                                <input type="file" name="bild_upload" accept="image/*" class="hs-file-input">
                            </div>
                            <small>Upload: Datei wird als <em>&lt;Name&gt;HA.&lt;Endung&gt;</em> gespeichert und auf max. 450&nbsp;px Höhe skaliert.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hs-form-section">
                <h2>🏛 Politisch</h2>
                <div class="hs-form-grid">
                    <div class="hs-field"><label>Partei <span>(max. 50 Zeichen)</span></label><input type="text" name="partei" maxlength="50" value="<?php echo $v('partei'); ?>" placeholder="z.B. CDU, SPD …"></div>
                    <div class="hs-field"><label>Aufgabe in der Partei</label><input type="text" name="aufgabe_partei" maxlength="100" value="<?php echo $v('aufgabe_partei'); ?>" placeholder="z.B. Vorsitzender, MdB …"></div>
                    <div class="hs-field">
                        <label>Parlament</label>
                        <select name="parlament">
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ( $parlaments as $p ) : ?><option value="<?php echo esc_attr($p); ?>" <?php selected($v('parlament'),esc_attr($p)); ?>><?php echo esc_html($p); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hs-field"><label>Parlament Name <span>(max. 50 Zeichen)</span></label><input type="text" name="parlament_name" maxlength="50" value="<?php echo $v('parlament_name'); ?>"></div>
                    <div class="hs-field"><label>Status</label><select name="status_aktiv"><option value="1" <?php selected($v('status_aktiv','1'),'1'); ?>>Aktiv</option><option value="0" <?php selected($v('status_aktiv','1'),'0'); ?>>Inaktiv</option></select></div>
                </div>
            </div>

            <div class="hs-form-section">
                <h2>⚖ Details zur Straftat</h2>
                <div class="hs-form-grid">
                    <div class="hs-field hs-field-full"><label>Straftat <span>(max. 200 Zeichen)</span></label><textarea name="straftat" maxlength="200" rows="3" required><?php echo esc_textarea($entry->straftat ?? ''); ?></textarea><small class="hs-char-counter" data-target="straftat">0 / 200 Zeichen</small></div>
                    <div class="hs-field"><label>Urteil <span>(max. 50 Zeichen)</span></label><input type="text" name="urteil" maxlength="50" value="<?php echo $v('urteil'); ?>" placeholder="z.B. 2 Jahre auf Bewährung"></div>
                    <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="link_quelle" value="<?php echo $v('link_quelle'); ?>" placeholder="https://…"></div>
                    <div class="hs-field"><label>Aktenzeichen <span>(max. 50 Zeichen)</span></label><input type="text" name="aktenzeichen" maxlength="50" value="<?php echo $v('aktenzeichen'); ?>" placeholder="z.B. 1 StR 123/24"></div>
                    <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="bemerkung" rows="4"><?php echo esc_textarea($entry->bemerkung ?? ''); ?></textarea></div>
                    <div class="hs-field"><label>Status Straftat</label><select name="status_straftat"><?php foreach ( $st_opts as $s ) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected($v('status_straftat','Ermittlungen laufen'),$s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></div>
                </div>
            </div>

            <div class="hs-form-section">
                <h2>📱 Social-Media Links</h2>
                <div class="hs-form-grid">
                    <?php foreach ( $sm_fields as $field => $label ) : ?>
                        <div class="hs-field"><label><?php echo $label; ?></label><input type="url" name="<?php echo esc_attr($field); ?>" value="<?php echo $v($field); ?>" placeholder="https://…"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ( $is_edit ) : ?>
            <div class="hs-form-section">
                <h2>⚙ Freigabe</h2>
                <div class="hs-form-grid">
                    <div class="hs-field hs-field-full"><label class="hs-checkbox-label"><input type="checkbox" name="freigegeben" value="1" <?php checked( intval($entry->freigegeben ?? 0), 1 ); ?>> Eintrag freigeben (öffentlich sichtbar)</label></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="hs-form-actions">
                <button type="submit" class="button button-primary hs-btn"><?php echo $is_edit ? '💾 Aktualisieren' : '➕ Eintrag speichern'; ?></button>
                <a href="<?php echo esc_url( admin_url('admin.php?page=handschelle') ); ?>" class="button hs-btn-cancel">Abbrechen</a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       SEITE: IMPORT / EXPORT
    ================================================================ */
    public function page_import_export() {
        $nonce = wp_create_nonce( 'handschelle_admin_action' );
        ?>
        <div class="wrap hs-wrap">
            <h1>📂 Import / Export</h1>
            <div class="hs-form-section">
                <h2>⬇ CSV Export</h2>
                <p>Exportiert alle Einträge als CSV-Datei (UTF-8 mit BOM, Semikolon-getrennt).</p>
                <a href="<?php echo esc_url( admin_url("admin.php?page=handschelle-import-export&hs_action=export_csv&_wpnonce={$nonce}") ); ?>" class="button button-primary hs-btn">📥 CSV herunterladen</a>
            </div>
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>⬆ CSV Import</h2>
                <p>Importiert Einträge im gleichen Format. Neue Einträge sind standardmäßig <em>nicht freigegeben</em>.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data" class="hs-form" style="padding:1.2rem;">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="import_csv">
                    <input type="hidden" name="page" value="handschelle-import-export">
                    <div class="hs-field" style="max-width:420px;"><label>CSV-Datei auswählen</label><input type="file" name="csv_file" accept=".csv" required></div>
                    <button type="submit" class="button button-primary hs-btn" style="margin-top:.8rem;">⬆ Importieren</button>
                </form>
            </div>
            <?php echo $this->hs_footer(); ?>
        </div>
        <?php
    }

    /* ================================================================
       SEITE: BILDER
    ================================================================ */
    public function page_bilder() {
        $all     = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );
        $entries = array_filter( $all, fn( $e ) => ! empty( $e->bild ) );
        $zippable = 0;
        foreach ( $entries as $e ) {
            if ( is_numeric( $e->bild ) && get_attached_file( intval( $e->bild ) ) ) $zippable++;
        }
        $nonce = wp_create_nonce( 'handschelle_admin_action' );
        ?>
        <div class="wrap hs-wrap">
            <h1>🖼 Bilder Backup</h1>
            <div class="hs-form-section">
                <p>Gesamt Einträge mit Bild: <strong><?php echo count( $entries ); ?></strong> &nbsp;|&nbsp; ZIP-fähige Attachments: <strong><?php echo $zippable; ?></strong></p>
                <?php if ( $zippable > 0 ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="export_images_zip">
                    <input type="hidden" name="page" value="handschelle-bilder">
                    <button type="submit" class="button button-primary hs-btn">📦 ZIP erstellen &amp; herunterladen</button>
                </form>
                <?php else : ?>
                    <p style="color:#999;">Keine Attachments zum Zippen vorhanden.</p>
                <?php endif; ?>
            </div>
            <div class="hs-form-section" style="margin-top:1.5rem;">
                <h2>⬆ ZIP Import</h2>
                <p>Importiert alle Bilder aus einer ZIP-Datei in die Medienbibliothek (JPEG, PNG, GIF, WebP). Bilder werden auf max. 450&nbsp;px Höhe skaliert.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="import_images_zip">
                    <input type="hidden" name="page" value="handschelle-bilder">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>ZIP-Datei auswählen</label>
                        <input type="file" name="zip_file" accept=".zip" required>
                    </div>
                    <button type="submit" class="button button-primary hs-btn">📤 ZIP importieren</button>
                </form>
            </div>
            <div class="hs-form-section" style="margin-top:1.5rem;">
                <h2>Bildliste</h2>
                <?php if ( empty( $entries ) ) : ?>
                    <p class="hs-empty">Keine Einträge mit Bild.</p>
                <?php else : ?>
                <table class="widefat fixed striped hs-admin-table">
                    <thead>
                        <tr>
                            <th style="width:70px">Vorschau</th>
                            <th>Name</th>
                            <th>Partei</th>
                            <th>Typ</th>
                            <th>Dateiname</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $entries as $e ) :
                        $img_url  = handschelle_get_image_url( $e->bild );
                        $is_attach = is_numeric( $e->bild ) && intval( $e->bild ) > 0;
                        $file_path = $is_attach ? get_attached_file( intval( $e->bild ) ) : '';
                        $filename  = $file_path ? basename( $file_path ) : '—';
                        $typ       = $is_attach ? 'Attachment (ID: ' . intval( $e->bild ) . ')' : 'Externe URL';
                    ?>
                        <tr>
                            <td>
                                <?php if ( $img_url ) : ?>
                                    <img src="<?php echo esc_url( $img_url ); ?>" style="width:56px;height:56px;object-fit:cover;border-radius:4px;">
                                <?php else : ?>
                                    <div style="width:56px;height:56px;background:#eee;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">👤</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $e->name ); ?></strong></td>
                            <td><?php echo esc_html( $e->partei ); ?></td>
                            <td><?php echo esc_html( $typ ); ?></td>
                            <td><code><?php echo esc_html( $filename ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php echo $this->hs_footer(); ?>
        </div>
        <?php
    }

    /* ================================================================
       ZIP EXPORT
    ================================================================ */
    private function export_images_zip() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }
        $all     = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );
        $entries = array_filter( $all, fn( $e ) => ! empty( $e->bild ) && is_numeric( $e->bild ) );

        $zip_path = sys_get_temp_dir() . '/hs_bilder_' . time() . '.zip';
        $zip      = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Fehler: ZIP-Datei konnte nicht erstellt werden.' );
            return;
        }
        $count = 0;
        foreach ( $entries as $e ) {
            $file = get_attached_file( intval( $e->bild ) );
            if ( $file && file_exists( $file ) ) {
                $zip->addFile( $file, basename( $file ) );
                $count++;
            }
        }
        $zip->close();

        if ( $count === 0 || ! file_exists( $zip_path ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Keine Dateien gefunden.' );
            return;
        }

        $filename = 'handschelle-bilder-' . date( 'Y-m-d' ) . '.zip';
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        header( 'Pragma: no-cache' );
        readfile( $zip_path );
        unlink( $zip_path );
        exit;
    }

    /* ================================================================
       ZIP IMPORT
    ================================================================ */
    private function import_images_zip() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }
        if ( empty( $_FILES['zip_file']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Fehler: Keine ZIP-Datei hochgeladen.' );
            return;
        }

        $zip = new ZipArchive();
        if ( $zip->open( $_FILES['zip_file']['tmp_name'] ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), 'Fehler: ZIP-Datei konnte nicht geöffnet werden.' );
            return;
        }

        if ( ! function_exists( 'wp_handle_sideload' ) ) require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';

        $allowed  = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
        $temp_dir = trailingslashit( sys_get_temp_dir() ) . 'hs_zip_import_' . time() . '/';
        wp_mkdir_p( $temp_dir );
        $upload_dir = wp_upload_dir();
        $count      = 0;

        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex( $i );
            // skip directories and hidden files
            if ( substr( $name, -1 ) === '/' || strpos( basename( $name ), '.' ) === 0 ) continue;
            $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
            if ( ! in_array( $ext, $allowed, true ) ) continue;

            $temp_file = $temp_dir . wp_unique_filename( $temp_dir, basename( $name ) );
            $stream    = $zip->getStream( $name );
            if ( ! $stream ) continue;
            file_put_contents( $temp_file, stream_get_contents( $stream ) );
            fclose( $stream );
            if ( ! file_exists( $temp_file ) ) continue;

            // Resize to max 450px height
            Handschelle_Image_Handler::resize_to_height( $temp_file, 450 );

            // Move to WP uploads
            $dest = trailingslashit( $upload_dir['path'] ) . wp_unique_filename( $upload_dir['path'], basename( $temp_file ) );
            if ( ! rename( $temp_file, $dest ) ) { unlink( $temp_file ); continue; }

            $filetype  = wp_check_filetype( basename( $dest ) );
            $attach_id = wp_insert_attachment( array(
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name( pathinfo( $dest, PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ), $dest );

            if ( $attach_id && ! is_wp_error( $attach_id ) ) {
                wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $dest ) );
                $count++;
            }
        }

        $zip->close();
        // cleanup temp dir
        foreach ( glob( $temp_dir . '*' ) as $f ) @unlink( $f );
        @rmdir( $temp_dir );

        $this->redirect( admin_url( 'admin.php?page=handschelle-bilder' ), "{$count} Bild(er) aus ZIP importiert und in Medienbibliothek gespeichert." );
    }

    /* ================================================================
       SEITE: BACKUP & RESTORE
    ================================================================ */
    public function page_backup() {
        $nonce = wp_create_nonce( 'handschelle_admin_action' );
        $all   = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );
        $total = count( $all );
        $imgs  = count( array_filter( $all, fn( $e ) => ! empty( $e->bild ) && is_numeric( $e->bild ) && get_attached_file( intval( $e->bild ) ) ) );
        ?>
        <div class="wrap hs-wrap">
            <h1>💾 Backup &amp; Restore</h1>

            <!-- ── BACKUP ── -->
            <div class="hs-form-section">
                <h2>⬇ Vollständiges Backup erstellen</h2>
                <p>Erstellt eine ZIP-Datei mit allen Einträgen (CSV) <strong>und</strong> allen Bildern aus der Medienbibliothek.<br>
                   Aktuell: <strong><?php echo $total; ?> Einträge</strong>, <strong><?php echo $imgs; ?> Bilder</strong> verfügbar.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="backup_full">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <button type="submit" class="button button-primary hs-btn">📦 Backup herunterladen</button>
                </form>
            </div>

            <!-- ── RESTORE ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>⬆ Backup wiederherstellen</h2>
                <p style="color:#c0392b;font-weight:600;">⚠ Beim Wiederherstellen werden <strong>alle bestehenden Einträge gelöscht</strong> und durch die Einträge aus dem Backup ersetzt. Bilder werden zusätzlich importiert.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="restore_full">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>Backup-ZIP auswählen</label>
                        <input type="file" name="backup_zip" accept=".zip" required>
                    </div>
                    <label class="hs-checkbox-label" style="margin-bottom:.8rem;display:block;">
                        <input type="checkbox" name="restore_confirm" value="1" required>
                        Ich verstehe, dass alle vorhandenen Einträge überschrieben werden.
                    </label>
                    <button type="submit" class="button hs-btn-danger" onclick="return confirm('Wirklich wiederherstellen? Alle Einträge werden überschrieben!')">⬆ Backup einspielen</button>
                </form>
            </div>
            <?php echo $this->hs_footer(); ?>
        </div>
        <?php
    }

    /* ================================================================
       BACKUP FULL – ZIP mit CSV + Bildern
    ================================================================ */
    private function backup_full() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }

        $entries = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );

        // Build in-memory CSV
        $cols = array( 'id','datum_eintrag','name','beruf','geburtsort','geburtsdatum','bild','partei','aufgabe_partei','parlament','parlament_name','status_aktiv','straftat','urteil','link_quelle','aktenzeichen','bemerkung','status_straftat','sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_sonstige','sm_linkedin','sm_xing','sm_truth_social','freigegeben','erstellt_am','geaendert_am' );
        $csv  = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= implode( ';', $cols ) . "\r\n";
        foreach ( $entries as $e ) {
            $row = array();
            foreach ( $cols as $c ) {
                $val = $e->$c ?? '';
                // Escape for CSV
                if ( strpbrk( (string) $val, ";\"\r\n" ) !== false ) {
                    $val = '"' . str_replace( '"', '""', $val ) . '"';
                }
                $row[] = $val;
            }
            $csv .= implode( ';', $row ) . "\r\n";
        }

        // Create ZIP
        $zip_path = sys_get_temp_dir() . '/hs_backup_' . time() . '.zip';
        $zip      = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht erstellt werden.' );
            return;
        }

        $zip->addFromString( 'handschelle-data.csv', $csv );

        // Build bild-map: attachment ID → image basename (for ID remapping on restore)
        $bild_map = array();
        foreach ( $entries as $e ) {
            if ( empty( $e->bild ) || ! is_numeric( $e->bild ) ) continue;
            $file = get_attached_file( intval( $e->bild ) );
            if ( $file && file_exists( $file ) ) {
                $zip->addFile( $file, 'images/' . basename( $file ) );
                $bild_map[ intval( $e->bild ) ] = basename( $file );
            }
        }
        $zip->addFromString( 'bild-map.json', wp_json_encode( $bild_map ) );
        $zip->close();

        if ( ! file_exists( $zip_path ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Backup konnte nicht erstellt werden.' );
            return;
        }

        $filename = 'handschelle-backup-' . date( 'Y-m-d_His' ) . '.zip';
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        header( 'Pragma: no-cache' );
        readfile( $zip_path );
        unlink( $zip_path );
        exit;
    }

    /* ================================================================
       RESTORE FULL – aus Backup-ZIP wiederherstellen
    ================================================================ */
    private function restore_full() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }
        if ( empty( $_FILES['backup_zip']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Keine Backup-Datei hochgeladen.' );
            return;
        }
        if ( empty( $_POST['restore_confirm'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Bitte Bestätigung ankreuzen.' );
            return;
        }

        $zip = new ZipArchive();
        if ( $zip->open( $_FILES['backup_zip']['tmp_name'] ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht geöffnet werden.' );
            return;
        }

        $temp_dir = trailingslashit( sys_get_temp_dir() ) . 'hs_restore_' . time() . '/';
        wp_mkdir_p( $temp_dir );
        $zip->extractTo( $temp_dir );
        $zip->close();

        // ── 1. Bilder importieren (images/ Unterordner) ──────────
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';
        if ( ! function_exists( 'wp_handle_sideload' ) ) require_once ABSPATH . 'wp-admin/includes/file.php';

        $allowed    = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
        $img_map    = array(); // original basename → new attachment ID
        $upload_dir = wp_upload_dir();
        $img_dir    = $temp_dir . 'images/';
        $img_count  = 0;

        if ( is_dir( $img_dir ) ) {
            foreach ( glob( $img_dir . '*' ) as $src_file ) {
                $ext = strtolower( pathinfo( $src_file, PATHINFO_EXTENSION ) );
                if ( ! in_array( $ext, $allowed, true ) ) continue;
                $dest = trailingslashit( $upload_dir['path'] ) . wp_unique_filename( $upload_dir['path'], basename( $src_file ) );
                if ( ! copy( $src_file, $dest ) ) continue;

                Handschelle_Image_Handler::resize_to_height( $dest, 450 );

                $filetype  = wp_check_filetype( basename( $dest ) );
                $attach_id = wp_insert_attachment( array(
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name( pathinfo( $dest, PATHINFO_FILENAME ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ), $dest );

                if ( $attach_id && ! is_wp_error( $attach_id ) ) {
                    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $dest ) );
                    $img_map[ basename( $src_file ) ] = $attach_id;
                    $img_count++;
                }
            }
        }

        // ── 1b. Lade bild-map.json für ID-Remapping ──────────────
        $bild_map_file = $temp_dir . 'bild-map.json';
        // $img_map: basename → new attachment ID (built in step 1)
        // We need: old attachment ID → new attachment ID
        // bild-map.json contains: old_id → basename
        $old_id_to_new_id = array();
        if ( file_exists( $bild_map_file ) ) {
            $bild_map_data = json_decode( file_get_contents( $bild_map_file ), true );
            if ( is_array( $bild_map_data ) ) {
                foreach ( $bild_map_data as $old_id => $basename ) {
                    if ( isset( $img_map[ $basename ] ) ) {
                        $old_id_to_new_id[ intval( $old_id ) ] = $img_map[ $basename ];
                    }
                }
            }
        }

        // ── 2. CSV einlesen und Einträge ersetzen ────────────────
        $csv_file  = $temp_dir . 'handschelle-data.csv';
        $entry_count = 0;
        if ( file_exists( $csv_file ) ) {
            // Wipe existing entries
            Handschelle_Database::truncate_table();

            $fh = fopen( $csv_file, 'r' );
            $bom = fread( $fh, 3 );
            if ( $bom !== "\xEF\xBB\xBF" ) rewind( $fh );
            fgetcsv( $fh, 0, ';' ); // header row
            // CSV column order (matches backup_full $cols):
            // 0:id 1:datum_eintrag 2:name 3:beruf 4:geburtsort 5:geburtsdatum
            // 6:bild 7:partei 8:aufgabe_partei 9:parlament 10:parlament_name
            // 11:status_aktiv 12:straftat 13:urteil 14:link_quelle 15:aktenzeichen
            // 16:bemerkung 17:status_straftat 18:sm_facebook 19:sm_youtube
            // 20:sm_personal 21:sm_twitter 22:sm_homepage 23:sm_wikipedia
            // 24:sm_sonstige 25:sm_linkedin 26:sm_xing 27:sm_truth_social
            // 28:freigegeben 29:erstellt_am 30:geaendert_am
            while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
                if ( count( $row ) < 29 ) continue;
                // Remap bild attachment ID: old ID → new ID via bild-map.json
                $bild_raw = sanitize_text_field( $row[6] );
                if ( is_numeric( $bild_raw ) && isset( $old_id_to_new_id[ intval( $bild_raw ) ] ) ) {
                    $bild_val = $old_id_to_new_id[ intval( $bild_raw ) ];
                } else {
                    $bild_val = $bild_raw;
                }
                $geburtsdatum_raw = sanitize_text_field( $row[5] );
                $geburtsdatum_val = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
                Handschelle_Database::insert( array(
                    'datum_eintrag'   => sanitize_text_field( $row[1] ) ?: date( 'Y-m-d' ),
                    'name'            => substr( sanitize_text_field( $row[2] ), 0, 50 ),
                    'beruf'           => substr( sanitize_text_field( $row[3] ), 0, 50 ),
                    'geburtsort'      => substr( sanitize_text_field( $row[4] ), 0, 100 ),
                    'geburtsdatum'    => $geburtsdatum_val,
                    'bild'            => $bild_val,
                    'partei'          => substr( sanitize_text_field( $row[7] ), 0, 50 ),
                    'aufgabe_partei'  => substr( sanitize_text_field( $row[8] ), 0, 100 ),
                    'parlament'       => sanitize_text_field( $row[9] ),
                    'parlament_name'  => substr( sanitize_text_field( $row[10] ), 0, 50 ),
                    'status_aktiv'    => intval( $row[11] ),
                    'straftat'        => substr( sanitize_textarea_field( $row[12] ), 0, 200 ),
                    'urteil'          => substr( sanitize_text_field( $row[13] ), 0, 50 ),
                    'link_quelle'     => esc_url_raw( $row[14] ),
                    'aktenzeichen'    => substr( sanitize_text_field( $row[15] ), 0, 50 ),
                    'bemerkung'       => sanitize_textarea_field( $row[16] ),
                    'status_straftat' => sanitize_text_field( $row[17] ),
                    'sm_facebook'     => esc_url_raw( $row[18] ),
                    'sm_youtube'      => esc_url_raw( $row[19] ),
                    'sm_personal'     => esc_url_raw( $row[20] ),
                    'sm_twitter'      => esc_url_raw( $row[21] ),
                    'sm_homepage'     => esc_url_raw( $row[22] ),
                    'sm_wikipedia'    => esc_url_raw( $row[23] ),
                    'sm_sonstige'     => esc_url_raw( $row[24] ),
                    'sm_linkedin'     => esc_url_raw( $row[25] ),
                    'sm_xing'         => esc_url_raw( $row[26] ),
                    'sm_truth_social' => esc_url_raw( $row[27] ),
                    'freigegeben'     => intval( $row[28] ),
                ) );
                $entry_count++;
            }
            fclose( $fh );
        }

        // ── 3. Temp cleanup ──────────────────────────────────────
        foreach ( glob( $temp_dir . 'images/*' ) as $f ) @unlink( $f );
        @rmdir( $temp_dir . 'images' );
        @unlink( $temp_dir . 'handschelle-data.csv' );
        @unlink( $temp_dir . 'bild-map.json' );
        @rmdir( $temp_dir );

        $this->redirect(
            admin_url( 'admin.php?page=handschelle-backup' ),
            "Wiederherstellung abgeschlossen: {$entry_count} Einträge importiert, {$img_count} Bilder importiert."
        );
    }

    /* ================================================================
       SEITE: DATENBANK
    ================================================================ */
    public function page_database() {
        $nonce = wp_create_nonce( 'handschelle_admin_action' );
        ?>
        <div class="wrap hs-wrap">
            <h1>🗄 Datenbank-Verwaltung</h1>
            <p style="color:#c0392b;font-weight:600;">⚠ Diese Aktionen können nicht rückgängig gemacht werden!</p>
            <div class="hs-db-actions">
                <div class="hs-db-card">
                    <h3>🧹 Datenbank leeren</h3><p>Alle Einträge löschen – Tabelle bleibt bestehen.</p>
                    <a href="<?php echo esc_url( admin_url("admin.php?page=handschelle-db&hs_action=truncate&_wpnonce={$nonce}") ); ?>" class="button button-secondary hs-btn" onclick="return confirm('Wirklich ALLE Einträge löschen?')">🧹 Leeren</a>
                </div>
                <div class="hs-db-card">
                    <h3>🔄 Datenbank neu erstellen</h3><p>Tabelle löschen und neu anlegen. Alle Daten gehen verloren.</p>
                    <a href="<?php echo esc_url( admin_url("admin.php?page=handschelle-db&hs_action=recreate&_wpnonce={$nonce}") ); ?>" class="button button-secondary hs-btn" onclick="return confirm('Datenbank neu erstellen? Alle Daten gehen verloren!')">🔄 Neu erstellen</a>
                </div>
                <div class="hs-db-card hs-db-card-danger">
                    <h3>🗑 Datenbanktabelle löschen</h3><p>Tabelle vollständig und dauerhaft entfernen.</p>
                    <a href="<?php echo esc_url( admin_url("admin.php?page=handschelle-db&hs_action=drop&_wpnonce={$nonce}") ); ?>" class="button hs-btn-danger" onclick="return confirm('Tabelle WIRKLICH löschen? Kann NICHT rückgängig gemacht werden!')">🗑 Tabelle löschen</a>
                </div>
            </div>
            <?php echo $this->hs_footer(); ?>
        </div>
        <?php
    }
}

new Handschelle_Admin();
