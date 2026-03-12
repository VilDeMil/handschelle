<?php
/**
 * Die-Handschelle 2.05 – Admin-Panel
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
                $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload' );
                if ( $attach_id ) $data['bild'] = $attach_id;
                Handschelle_Database::insert( $data );
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Eintrag gespeichert – bitte freigeben.' );
                break;

            case 'edit':
                $id = intval( $_POST['id'] ?? 0 );
                if ( ! $id ) break;
                $data      = handschelle_sanitize_entry( $_POST );
                $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload' );
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

            case 'export_csv':        $this->export_csv(); break;
            case 'import_csv':        $this->import_csv(); break;
            case 'export_images_zip': $this->export_images_zip(); break;

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
        $cols = array( 'id','datum_eintrag','name','beruf','bild','partei','aufgabe_partei','parlament','parlament_name','status_aktiv','straftat','urteil','link_quelle','aktenzeichen','bemerkung','status_straftat','sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_sonstige','freigegeben','erstellt_am','geaendert_am' );
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
        fgetcsv( $fh, 0, ';' );
        $count = 0;
        while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
            if ( count( $row ) < 20 ) continue;
            Handschelle_Database::insert( array(
                'datum_eintrag'  => sanitize_text_field( $row[1] ),
                'name'           => substr( sanitize_text_field( $row[2] ), 0, 50 ),
                'beruf'          => substr( sanitize_text_field( $row[3] ), 0, 50 ),
                'bild'           => sanitize_text_field( $row[4] ),
                'partei'         => substr( sanitize_text_field( $row[5] ), 0, 50 ),
                'aufgabe_partei' => substr( sanitize_text_field( $row[6] ), 0, 100 ),
                'parlament'      => sanitize_text_field( $row[7] ),
                'parlament_name' => substr( sanitize_text_field( $row[8] ), 0, 50 ),
                'status_aktiv'   => intval( $row[9] ),
                'straftat'       => substr( sanitize_textarea_field( $row[10] ), 0, 200 ),
                'urteil'         => substr( sanitize_text_field( $row[11] ), 0, 50 ),
                'link_quelle'    => esc_url_raw( $row[12] ),
                'aktenzeichen'   => substr( sanitize_text_field( $row[13] ), 0, 50 ),
                'bemerkung'      => sanitize_textarea_field( $row[14] ),
                'status_straftat'=> sanitize_text_field( $row[15] ),
                'sm_facebook'    => esc_url_raw( $row[16] ),
                'sm_youtube'     => esc_url_raw( $row[17] ),
                'sm_personal'    => esc_url_raw( $row[18] ),
                'sm_twitter'     => esc_url_raw( $row[19] ),
                'sm_homepage'    => esc_url_raw( $row[20] ?? '' ),
                'sm_wikipedia'   => esc_url_raw( $row[21] ?? '' ),
                'sm_sonstige'    => esc_url_raw( $row[22] ?? '' ),
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
        $entries = Handschelle_Database::get_all( array( 'freigegeben' => 'all', 'orderby' => 'erstellt_am', 'order' => 'DESC' ) );
        $total   = count( $entries );
        $pending = count( array_filter( $entries, fn( $e ) => ! $e->freigegeben ) );
        $nonce   = wp_create_nonce( 'handschelle_admin_action' );
        ?>
        <div class="wrap hs-wrap">
            <h1>🔒 Die-Handschelle <span class="hs-version">2.0 A</span></h1>
            <div class="hs-stats-bar">
                <span>Gesamt: <strong><?php echo $total; ?></strong></span>
                <span>Ausstehend: <strong class="<?php echo $pending ? 'hs-warn' : ''; ?>"><?php echo $pending; ?></strong></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle-add' ) ); ?>" class="button button-primary hs-btn">+ Neuer Eintrag</a>
            </div>
            <table class="widefat fixed striped hs-admin-table">
                <thead>
                    <tr>
                        <th style="width:70px">Bild</th>
                        <th>Name</th>
                        <th>Partei</th>
                        <th>Straftat</th>
                        <th style="width:90px">Status</th>
                        <th style="width:120px">Freigabe</th>
                        <th style="width:320px">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $entries ) ) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:#999;">Noch keine Einträge.</td></tr>
                <?php endif; ?>
                <?php foreach ( $entries as $e ) :
                    $img_url = handschelle_get_image_url( $e->bild );
                ?>
                    <tr>
                        <td>
                            <?php if ( $img_url ) : ?>
                                <img src="<?php echo esc_url( $img_url ); ?>" style="width:56px;height:56px;object-fit:cover;border-radius:4px;">
                            <?php else : ?>
                                <div style="width:56px;height:56px;background:#eee;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">👤</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $e->name ); ?></strong><br><small><?php echo esc_html( $e->beruf ); ?></small></td>
                        <td><?php echo esc_html( $e->partei ); ?><br><small><?php echo esc_html( $e->aufgabe_partei ); ?></small></td>
                        <td><?php echo esc_html( mb_substr( $e->straftat, 0, 90 ) ) . ( mb_strlen( $e->straftat ) > 90 ? '…' : '' ); ?></td>
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
            <?php echo $this->hs_footer(); ?>
        </div>
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
            'sm_facebook'  => '📘 Facebook',
            'sm_youtube'   => '▶ YouTube',
            'sm_personal'  => '👤 Persönliches Profil',
            'sm_twitter'   => '🐦 Twitter / X',
            'sm_homepage'  => '🌐 Persönliche Homepage',
            'sm_wikipedia' => '📖 Wikipedia',
            'sm_sonstige'  => '🔗 Sonstige',
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
                    <div class="hs-field"><label>Name <span>(max. 50 Zeichen)</span></label><input type="text" name="name" maxlength="50" value="<?php echo $v('name'); ?>" placeholder="Vor- und Nachname" required></div>
                    <div class="hs-field"><label>Beruf <span>(max. 50 Zeichen)</span></label><input type="text" name="beruf" maxlength="50" value="<?php echo $v('beruf'); ?>" placeholder="z.B. Politiker"></div>
                    <div class="hs-field hs-field-full">
                        <label>Bild</label>
                        <?php if ( $is_edit && ! empty( $entry->bild ) ) :
                            $img = handschelle_get_image_url( $entry->bild );
                            if ( $img ) : ?><div class="hs-current-image"><img src="<?php echo esc_url($img); ?>" style="max-height:120px;border-radius:6px;"><br><small>ID: <?php echo esc_html($entry->bild); ?></small></div><?php endif;
                        endif; ?>
                        <input type="file" name="bild_upload" accept="image/*" class="hs-file-input">
                        <small>Wird auf max. 450px Höhe skaliert. Oder Medienbibliothek-ID:</small>
                        <input type="text" name="bild" placeholder="Medienbibliothek-ID" value="<?php echo $v('bild'); ?>">
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
