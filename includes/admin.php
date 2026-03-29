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
        add_filter( 'authenticate',  array( $this, 'block_pending_users' ), 30, 3 );
        add_action( 'user_register', array( $this, 'set_new_user_pending' ), 10, 1 );
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
        $pending_off = Handschelle_Database::count_offences( 0 );
        $offences_label = '⚖ Straftaten freigeben' . ( $pending_off ? ' <span class="awaiting-mod">' . $pending_off . '</span>' : '' );
        add_submenu_page( 'handschelle', 'Straftaten freigeben', $offences_label, 'manage_options', 'handschelle-offences', array( $this, 'page_offences' ) );
        add_submenu_page( 'handschelle', 'Import / Export',    'Import / Export', 'manage_options', 'handschelle-import-export', array( $this, 'page_import_export' ) );
        add_submenu_page( 'handschelle', 'Bilder',             'Bilder',          'manage_options', 'handschelle-bilder',        array( $this, 'page_bilder' ) );
        add_submenu_page( 'handschelle', 'Backup & Restore',  'Backup & Restore','manage_options', 'handschelle-backup',        array( $this, 'page_backup' ) );
        add_submenu_page( 'handschelle', 'Datenbank',          'Datenbank',       'manage_options', 'handschelle-db',            array( $this, 'page_database' ) );
        $pending_count = count( get_users( array( 'meta_key' => 'hs_user_status', 'meta_value' => 'pending' ) ) );
        $users_label   = '👥 Benutzer' . ( $pending_count ? ' <span class="awaiting-mod">' . $pending_count . '</span>' : '' );
        add_submenu_page( 'handschelle', 'Benutzer',           $users_label,      'manage_options', 'handschelle-users',          array( $this, 'page_users' ) );
        add_submenu_page( 'handschelle', 'Ollama KI',          'Ollama KI',        'manage_options', 'handschelle-ollama',         array( $this, 'page_ollama' ) );
        add_submenu_page( 'handschelle', 'LLM Status',         'LLM Status',       'manage_options', 'handschelle-llm-status',     array( $this, 'page_llm_status' ) );
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
                $new_id = Handschelle_Database::insert( $data );
                if ( $new_id ) {
                    $this->save_offences( $new_id, (array) ( $_POST['hs_offences'] ?? array() ) );
                }
                $this->redirect( admin_url( 'admin.php?page=handschelle' ), 'Eintrag gespeichert – bitte freigeben.' );
                break;

            case 'edit':
                $id = intval( $_POST['id'] ?? 0 );
                if ( ! $id ) break;
                $entry_before = Handschelle_Database::get_one( $id );
                $data         = handschelle_sanitize_entry( $_POST );
                $attach_id    = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload', $data['name'] ?? '', $data['partei'] ?? '' );
                if ( $attach_id ) {
                    // New file uploaded: delete the old attachment to avoid orphans
                    if ( $entry_before && ! empty( $entry_before->bild ) && is_numeric( $entry_before->bild ) && intval( $entry_before->bild ) !== $attach_id ) {
                        wp_delete_attachment( intval( $entry_before->bild ), true );
                    }
                    $data['bild'] = $attach_id;
                }
                $data['freigegeben'] = isset( $_POST['freigegeben'] ) ? 1 : 0;
                Handschelle_Database::update( $id, $data );
                $this->save_offences( $id, (array) ( $_POST['hs_offences'] ?? array() ) );
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

            case 'approve_offence':
                $oid = intval( $_REQUEST['oid'] ?? 0 );
                if ( $oid ) {
                    Handschelle_Database::update_offence( $oid, array( 'freigegeben' => 1 ) );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-offences' ), 'Straftat freigegeben.' );
                }
                break;

            case 'reject_offence':
                $oid = intval( $_REQUEST['oid'] ?? 0 );
                if ( $oid ) {
                    Handschelle_Database::update_offence( $oid, array( 'freigegeben' => 0 ) );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-offences' ), 'Straftat gesperrt.' );
                }
                break;

            case 'delete_offence':
                $oid = intval( $_REQUEST['oid'] ?? 0 );
                if ( $oid ) {
                    Handschelle_Database::delete_offence( $oid );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-offences' ), 'Straftat gelöscht.' );
                }
                break;

            case 'edit_offence':
                $oid = intval( $_POST['oid'] ?? 0 );
                if ( $oid ) {
                    $urteil_raw = sanitize_text_field( $_POST['urteil'] ?? '' );
                    $data = array(
                        'straftat'        => sanitize_textarea_field( $_POST['straftat']       ?? '' ),
                        'urteil'          => substr( $urteil_raw, 0, 200 ),
                        'link_quelle'     => esc_url_raw( $_POST['link_quelle']   ?? '' ),
                        'aktenzeichen'    => substr( sanitize_text_field( $_POST['aktenzeichen'] ?? '' ), 0, 50 ),
                        'bemerkung'       => sanitize_textarea_field( $_POST['bemerkung']      ?? '' ),
                        'status_straftat' => sanitize_text_field( $_POST['status_straftat']    ?? 'Ermittlungen laufen' ),
                        'datum_eintrag'   => sanitize_text_field( $_POST['datum_eintrag']      ?? '' ) ?: null,
                        'freigegeben'     => isset( $_POST['freigegeben'] ) ? 1 : 0,
                    );
                    Handschelle_Database::update_offence( $oid, $data );
                    $filter = sanitize_text_field( $_POST['hs_filter'] ?? 'pending' );
                    $this->redirect( admin_url( "admin.php?page=handschelle-offences&hs_filter={$filter}" ), 'Straftat aktualisiert.' );
                }
                break;

            case 'bulk_offences':
                $op   = sanitize_text_field( $_POST['hs_bulk_op'] ?? '' );
                $oids = array_map( 'intval', (array) ( $_POST['hs_bulk_oids'] ?? array() ) );
                $oids = array_filter( $oids );
                if ( ! empty( $oids ) && in_array( $op, array( 'approve', 'reject', 'delete' ), true ) ) {
                    foreach ( $oids as $oid ) {
                        if ( $op === 'approve' ) {
                            Handschelle_Database::update_offence( $oid, array( 'freigegeben' => 1 ) );
                        } elseif ( $op === 'reject' ) {
                            Handschelle_Database::update_offence( $oid, array( 'freigegeben' => 0 ) );
                        } elseif ( $op === 'delete' ) {
                            Handschelle_Database::delete_offence( $oid );
                        }
                    }
                    $label = array( 'approve' => 'freigegeben', 'reject' => 'gesperrt', 'delete' => 'gelöscht' );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-offences' ), count( $oids ) . ' Straftat/Straftaten ' . $label[ $op ] . '.' );
                }
                $this->redirect( admin_url( 'admin.php?page=handschelle-offences' ), 'Keine Einträge ausgewählt.' );
                break;

            case 'activate_user':
                $uid = intval( $_REQUEST['uid'] ?? 0 );
                if ( $uid && get_userdata( $uid ) ) {
                    update_user_meta( $uid, 'hs_user_status', 'active' );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-users' ), 'Benutzer freigeschaltet.' );
                }
                break;

            case 'deactivate_user':
                $uid = intval( $_REQUEST['uid'] ?? 0 );
                if ( $uid && get_userdata( $uid ) ) {
                    update_user_meta( $uid, 'hs_user_status', 'deactivated' );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-users' ), 'Benutzer deaktiviert.' );
                }
                break;

            case 'delete_hs_user':
                $uid = intval( $_REQUEST['uid'] ?? 0 );
                if ( $uid && get_userdata( $uid ) && ! user_can( $uid, 'manage_options' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user( $uid );
                    $this->redirect( admin_url( 'admin.php?page=handschelle-users' ), 'Benutzer gelöscht.' );
                }
                break;

            case 'export_csv':        $this->export_csv(); break;
            case 'import_csv':        $this->import_csv(); break;
            case 'export_images_zip': $this->export_images_zip(); break;
            case 'import_images_zip': $this->import_images_zip(); break;
            case 'backup_full':       $this->backup_full(); break;
            case 'restore_full':      $this->restore_full(); break;
            case 'backup_pages':      $this->backup_pages(); break;
            case 'restore_pages':     $this->restore_pages(); break;
            case 'backup_posts':      $this->backup_posts(); break;
            case 'restore_posts':     $this->restore_posts(); break;
            case 'backup_theme':      $this->backup_theme(); break;
            case 'restore_theme':     $this->restore_theme(); break;
            case 'backup_all':        $this->backup_all(); break;
            case 'restore_all':       $this->restore_all(); break;

            case 'truncate':
                Handschelle_Database::truncate_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbank geleert.' );
                break;

            case 'recreate':
                Handschelle_Database::drop_table();
                Handschelle_Database::create_table();
                Handschelle_Database::create_offences_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbank neu erstellt.' );
                break;

            case 'drop':
                Handschelle_Database::drop_table();
                $this->redirect( admin_url( 'admin.php?page=handschelle-db' ), 'Datenbanktabelle gelöscht.' );
                break;

            case 'save_ollama':
                $ollama_mode_save = in_array( $_POST['hs_ollama_mode'] ?? 'local', array( 'local', 'remote' ), true )
                    ? $_POST['hs_ollama_mode'] : 'local';
                update_option( 'hs_ollama_mode', $ollama_mode_save );
                // In local mode always use localhost; in remote mode use the submitted URL.
                if ( $ollama_mode_save === 'remote' ) {
                    $url_save = sanitize_text_field( wp_unslash( $_POST['hs_ollama_url'] ?? '' ) );
                    update_option( 'hs_ollama_url', $url_save ?: 'http://localhost:11434' );
                } else {
                    update_option( 'hs_ollama_url', 'http://localhost:11434' );
                }
                update_option( 'hs_ollama_default_model',  sanitize_text_field( wp_unslash( $_POST['hs_ollama_default_model']  ?? '' ) ) );
                update_option( 'hs_ollama_system_prompt',  sanitize_textarea_field( wp_unslash( $_POST['hs_ollama_system_prompt'] ?? '' ) ) );
                update_option( 'hs_ollama_timeout',        max( 10, intval( $_POST['hs_ollama_timeout'] ?? 120 ) ) );
                update_option( 'hs_ollama_chat_page',      sanitize_text_field( wp_unslash( $_POST['hs_ollama_chat_page']      ?? '' ) ) );
                // Ollama Cloud API key (only updated if non-empty to avoid accidental clearing)
                if ( ! empty( $_POST['hs_ollama_api_key'] ) ) {
                    update_option( 'hs_ollama_api_key', sanitize_text_field( wp_unslash( $_POST['hs_ollama_api_key'] ) ) );
                } elseif ( isset( $_POST['hs_ollama_api_key_clear'] ) ) {
                    delete_option( 'hs_ollama_api_key' );
                }
                // OpenAI settings (key only updated if non-empty to avoid accidental clearing)
                if ( ! empty( $_POST['hs_openai_api_key'] ) ) {
                    update_option( 'hs_openai_api_key', sanitize_text_field( wp_unslash( $_POST['hs_openai_api_key'] ) ) );
                } elseif ( isset( $_POST['hs_openai_api_key_clear'] ) ) {
                    delete_option( 'hs_openai_api_key' );
                }
                update_option( 'hs_openai_default_model', sanitize_text_field( wp_unslash( $_POST['hs_openai_default_model'] ?? 'gpt-4o' ) ) );
                // Claude (Anthropic) settings
                if ( ! empty( $_POST['hs_claude_api_key'] ) ) {
                    update_option( 'hs_claude_api_key', sanitize_text_field( wp_unslash( $_POST['hs_claude_api_key'] ) ) );
                } elseif ( isset( $_POST['hs_claude_api_key_clear'] ) ) {
                    delete_option( 'hs_claude_api_key' );
                }
                update_option( 'hs_claude_default_model', sanitize_text_field( wp_unslash( $_POST['hs_claude_default_model'] ?? 'claude-3-5-sonnet-20241022' ) ) );
                // Google Gemini settings
                if ( ! empty( $_POST['hs_gemini_api_key'] ) ) {
                    update_option( 'hs_gemini_api_key', sanitize_text_field( wp_unslash( $_POST['hs_gemini_api_key'] ) ) );
                } elseif ( isset( $_POST['hs_gemini_api_key_clear'] ) ) {
                    delete_option( 'hs_gemini_api_key' );
                }
                update_option( 'hs_gemini_default_model', sanitize_text_field( wp_unslash( $_POST['hs_gemini_default_model'] ?? 'gemini-2.0-flash' ) ) );
                // AI-Profil settings
                update_option( 'hs_profile_questions',      sanitize_textarea_field( wp_unslash( $_POST['hs_profile_questions']    ?? '' ) ) );
                update_option( 'hs_profile_system_prompt',  sanitize_textarea_field( wp_unslash( $_POST['hs_profile_system_prompt'] ?? '' ) ) );
                $allowed_providers = array( 'ollama', 'openai', 'claude', 'gemini' );
                $profile_prov = in_array( $_POST['hs_profile_provider'] ?? 'ollama', $allowed_providers, true )
                    ? $_POST['hs_profile_provider'] : 'ollama';
                update_option( 'hs_profile_provider', $profile_prov );
                update_option( 'hs_profile_model',    sanitize_text_field( wp_unslash( $_POST['hs_profile_model'] ?? '' ) ) );
                $this->redirect( admin_url( 'admin.php?page=handschelle-ollama' ), 'Einstellungen gespeichert.' );
                break;
        }
    }

    /* ================================================================
       CSV EXPORT
       Format: type=entry  → full person row (all columns)
               type=offence → extra offence row (id = parent entry id,
                              straftat-columns only, personal cols empty)
    ================================================================ */
    private function export_csv() {
        $entries = Handschelle_Database::get_all( array( 'freigegeben' => 'all' ) );
        header( 'Content-Type: text/csv; charset=utf-8' );
        // cols updated below – spitzname, geburtsland, private_email, oeffentliche_email added
        header( 'Content-Disposition: attachment; filename=handschelle-export-' . date( 'Y-m-d' ) . '.csv' );
        header( 'Pragma: no-cache' );
        $out  = fopen( 'php://output', 'w' );
        fputs( $out, "\xEF\xBB\xBF" );

        $cols = array( 'id','datum_eintrag','name','spitzname','beruf','geburtsort','geburtsland','geburtsdatum','verstorben','dod','bild','partei','aufgabe_partei','parlament','parlament_name','status_aktiv','private_email','oeffentliche_email','straftat','urteil','link_quelle','aktenzeichen','bemerkung_person','bemerkung','status_straftat','sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_sonstige','sm_linkedin','sm_xing','sm_truth_social','freigegeben','erstellt_am','geaendert_am' );
        // Offence-specific columns (subset of $cols reused)
        $off_cols = array( 'straftat','urteil','link_quelle','aktenzeichen','bemerkung','status_straftat','datum_eintrag' );

        fputcsv( $out, array_merge( array( 'type' ), $cols ), ';' );

        foreach ( $entries as $e ) {
            // Entry row
            $row = array( 'entry' );
            foreach ( $cols as $c ) $row[] = $e->$c ?? '';
            fputcsv( $out, $row, ';' );

            // One offence row per extra offence
            foreach ( Handschelle_Database::get_offences( $e->id ) as $off ) {
                $row = array( 'offence' );
                foreach ( $cols as $c ) {
                    if ( $c === 'id' ) {
                        $row[] = $e->id; // parent entry reference
                    } elseif ( in_array( $c, $off_cols, true ) ) {
                        $row[] = $off->$c ?? '';
                    } else {
                        $row[] = '';
                    }
                }
                fputcsv( $out, $row, ';' );
            }
        }
        fclose( $out );
        exit;
    }

    /* ================================================================
       CSV IMPORT
       Supports:
         • New format (type column present):
             type=entry   → insert main entry; map csv id → new db id
             type=offence → insert offence linked to parent via csv id
         • Old format (no type column) → all rows treated as entries
    ================================================================ */
    private function import_csv() {
        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) return;
        $fh = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
        if ( ! $fh ) return;
        $bom = fread( $fh, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) rewind( $fh );
        $headers = fgetcsv( $fh, 0, ';' );
        if ( empty( $headers ) ) { fclose( $fh ); return; }

        $headers  = array_map( 'trim', $headers );
        $col_map  = array_flip( $headers );
        $has_type = isset( $col_map['type'] );
        $g = function( $field ) use ( &$row, $col_map ) {
            return ( isset( $col_map[$field] ) && isset( $row[$col_map[$field]] ) ) ? trim( $row[$col_map[$field]] ) : '';
        };

        $count_entries  = 0;
        $count_offences = 0;
        $id_map         = array(); // csv_id (original db id) → newly inserted db id

        while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
            if ( count( $row ) < 2 ) continue;
            $type = $has_type ? $g('type') : 'entry';

            if ( $type === 'offence' ) {
                // Link to parent entry via the id column (holds parent's csv id)
                $csv_parent_id = $g('id');
                $entry_db_id   = $id_map[ $csv_parent_id ] ?? 0;
                if ( ! $entry_db_id ) continue; // orphaned offence – skip

                $off_straftat = sanitize_textarea_field( $g('straftat') );
                if ( empty( $off_straftat ) ) continue;

                $off_datum_raw = sanitize_text_field( $g('datum_eintrag') );
                Handschelle_Database::insert_offence( $entry_db_id, array(
                    'straftat'        => $off_straftat,
                    'urteil'          => substr( sanitize_text_field( $g('urteil') ), 0, 200 ),
                    'status_straftat' => sanitize_text_field( $g('status_straftat') ) ?: 'Ermittlungen laufen',
                    'link_quelle'     => esc_url_raw( $g('link_quelle') ),
                    'aktenzeichen'    => substr( sanitize_text_field( $g('aktenzeichen') ), 0, 50 ),
                    'bemerkung'       => sanitize_textarea_field( $g('bemerkung') ),
                    'datum_eintrag'   => ( $off_datum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $off_datum_raw ) ) ? $off_datum_raw : null,
                ) );
                $count_offences++;

            } else {
                // Entry row
                if ( count( $row ) < 10 ) continue;

                $bild_raw = sanitize_text_field( $g('bild') );
                if ( is_numeric( $bild_raw ) && intval( $bild_raw ) > 0 && ! get_attached_file( intval( $bild_raw ) ) ) {
                    $bild_raw = '';
                }
                $geburtsdatum_raw = sanitize_text_field( $g('geburtsdatum') );
                $geburtsdatum     = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
                $dod_raw_csv      = sanitize_text_field( $g('dod') );
                $dod_val_csv      = ( $dod_raw_csv && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dod_raw_csv ) ) ? $dod_raw_csv : null;

                $csv_id = $g('id');
                $new_id = Handschelle_Database::insert( array(
                    'datum_eintrag'    => sanitize_text_field( $g('datum_eintrag') ) ?: date('Y-m-d'),
                    'name'             => substr( sanitize_text_field( $g('name') ), 0, 50 ),
                    'spitzname'        => substr( sanitize_text_field( $g('spitzname') ), 0, 100 ),
                    'beruf'            => substr( sanitize_text_field( $g('beruf') ), 0, 50 ),
                    'geburtsort'       => substr( sanitize_text_field( $g('geburtsort') ), 0, 100 ),
                    'geburtsland'      => substr( sanitize_text_field( $g('geburtsland') ), 0, 100 ),
                    'geburtsdatum'     => $geburtsdatum,
                    'verstorben'       => intval( $g('verstorben') ),
                    'dod'              => $dod_val_csv,
                    'bild'             => $bild_raw,
                    'partei'           => substr( sanitize_text_field( $g('partei') ), 0, 50 ),
                    'aufgabe_partei'   => substr( sanitize_text_field( $g('aufgabe_partei') ), 0, 100 ),
                    'parlament'        => sanitize_text_field( $g('parlament') ),
                    'parlament_name'   => substr( sanitize_text_field( $g('parlament_name') ), 0, 50 ),
                    'status_aktiv'     => intval( $g('status_aktiv') ),
                    'private_email'    => substr( sanitize_email( $g('private_email') ), 0, 200 ),
                    'oeffentliche_email' => substr( sanitize_email( $g('oeffentliche_email') ), 0, 200 ),
                    'straftat'         => sanitize_textarea_field( $g('straftat') ),
                    'urteil'           => substr( sanitize_text_field( $g('urteil') ), 0, 200 ),
                    'link_quelle'      => esc_url_raw( $g('link_quelle') ),
                    'aktenzeichen'     => substr( sanitize_text_field( $g('aktenzeichen') ), 0, 50 ),
                    'bemerkung_person' => substr( sanitize_textarea_field( $g('bemerkung_person') ), 0, 500 ),
                    'bemerkung'        => sanitize_textarea_field( $g('bemerkung') ),
                    'status_straftat'  => sanitize_text_field( $g('status_straftat') ) ?: 'Ermittlungen laufen',
                    'sm_facebook'      => esc_url_raw( $g('sm_facebook') ),
                    'sm_youtube'       => esc_url_raw( $g('sm_youtube') ),
                    'sm_personal'      => esc_url_raw( $g('sm_personal') ),
                    'sm_twitter'       => esc_url_raw( $g('sm_twitter') ),
                    'sm_homepage'      => esc_url_raw( $g('sm_homepage') ),
                    'sm_wikipedia'     => esc_url_raw( $g('sm_wikipedia') ),
                    'sm_sonstige'      => esc_url_raw( $g('sm_sonstige') ),
                    'sm_linkedin'      => esc_url_raw( $g('sm_linkedin') ),
                    'sm_xing'          => esc_url_raw( $g('sm_xing') ),
                    'sm_truth_social'  => esc_url_raw( $g('sm_truth_social') ),
                ) );

                if ( $new_id && $csv_id !== '' ) {
                    $id_map[ $csv_id ] = $new_id;
                }
                $count_entries++;
            }
        }
        fclose( $fh );

        $msg = "{$count_entries} Einträge importiert";
        if ( $count_offences ) $msg .= ", {$count_offences} Straftaten (Offences)";
        $msg .= ' (Freigabe ausstehend).';
        $this->redirect( admin_url( 'admin.php?page=handschelle' ), $msg );
    }

    /* ================================================================
       HILFSMETHODEN
    ================================================================ */

    /**
     * Save the hs_offences[] POST array for a given entry ID.
     * Each element may have: id, delete, straftat, urteil, status_straftat, link_quelle, aktenzeichen, bemerkung.
     */
    private function save_offences( $entry_id, $offences_input ) {
        foreach ( $offences_input as $off_data ) {
            $off_id     = intval( $off_data['id'] ?? 0 );
            $off_delete = intval( $off_data['delete'] ?? 0 );
            $off_text   = sanitize_textarea_field( $off_data['straftat'] ?? '' );

            if ( $off_id && $off_delete ) {
                Handschelle_Database::delete_offence( $off_id );
            } elseif ( $off_id && ! $off_delete && ! empty( $off_text ) ) {
                Handschelle_Database::update_offence( $off_id, $this->sanitize_offence( $off_data ) );
            } elseif ( ! $off_id && ! $off_delete && ! empty( $off_text ) ) {
                Handschelle_Database::insert_offence( $entry_id, $this->sanitize_offence( $off_data ) );
            }
        }
    }

    private function sanitize_offence( $off_data ) {
        return array(
            'straftat'        => sanitize_textarea_field( $off_data['straftat'] ?? '' ),
            'urteil'          => substr( sanitize_text_field( $off_data['urteil'] ?? '' ), 0, 200 ),
            'status_straftat' => sanitize_text_field( $off_data['status_straftat'] ?? 'Ermittlungen laufen' ),
            'link_quelle'     => esc_url_raw( $off_data['link_quelle'] ?? '' ),
            'aktenzeichen'    => substr( sanitize_text_field( $off_data['aktenzeichen'] ?? '' ), 0, 50 ),
            'bemerkung'       => sanitize_textarea_field( $off_data['bemerkung'] ?? '' ),
        );
    }

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
            <h1>🔒 Die-Handschelle <span class="hs-version"><?php echo esc_html( HANDSCHELLE_VERSION ); ?></span></h1>
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
                            <td>
                                <strong><?php echo esc_html( $e->name ); ?></strong>
                                <?php if ( ! empty( $e->spitzname ) ) : ?> <small style="color:#888;">(„<?php echo esc_html( $e->spitzname ); ?>")</small><?php endif; ?>
                                <br><small><?php echo esc_html( $e->beruf ); ?></small>
                                <?php if ( ! empty( $e->oeffentliche_email ) ) : ?><br><small>✉ <?php echo esc_html( $e->oeffentliche_email ); ?></small><?php endif; ?>
                            </td>
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
        $laender    = handschelle_laender();
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
                    <div class="hs-field"><label>Spitzname <span>(max. 100 Zeichen)</span></label><input type="text" name="spitzname" maxlength="100" value="<?php echo $v('spitzname'); ?>" placeholder="z.B. Der Fuchs"></div>
                    <div class="hs-field"><label>Geburtsort <span>(max. 100 Zeichen)</span></label><input type="text" name="geburtsort" maxlength="100" value="<?php echo $v('geburtsort'); ?>" placeholder="z.B. Berlin"></div>
                    <div class="hs-field">
                        <label>Geburtsland</label>
                        <select name="geburtsland">
                            <?php foreach ( $laender as $land ) : ?>
                                <option value="<?php echo esc_attr($land); ?>" <?php selected( $v('geburtsland','Deutschland'), esc_attr($land) ); ?>><?php echo esc_html($land); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hs-field"><label>Geburtsdatum</label><input type="date" name="geburtsdatum" value="<?php echo esc_attr( ( ! empty($entry->geburtsdatum) && $entry->geburtsdatum !== '0000-00-00' ) ? $entry->geburtsdatum : '' ); ?>"></div>
                    <div class="hs-field hs-email-toggle" style="display:none;"><label>Private E-Mail</label><input type="email" name="private_email" maxlength="200" value="<?php echo $v('private_email'); ?>" placeholder="privat@beispiel.de"></div>
                    <div class="hs-field hs-email-toggle" style="display:none;"><label>Öffentliche E-Mail</label><input type="email" name="oeffentliche_email" maxlength="200" value="<?php echo $v('oeffentliche_email'); ?>" placeholder="kontakt@beispiel.de"></div>
                    <div class="hs-field"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" id="hs-email-show" onchange="document.querySelectorAll('.hs-email-toggle').forEach(function(el){el.style.display=this.checked?'':'none';}.bind(this))"> E-Mail-Felder anzeigen</label></div>
                    <div class="hs-field">
                        <label class="hs-checkbox-label"><input type="checkbox" name="verstorben" id="hs-verstorben" class="hs-verstorben-cb" value="1" <?php checked( intval($entry->verstorben ?? 0), 1 ); ?>> Verstorben</label>
                    </div>
                    <div class="hs-field hs-dod-row" id="hs-dod-row" style="<?php echo empty($entry->verstorben) ? 'display:none;' : ''; ?>">
                        <label>Sterbedatum (DoD)</label>
                        <input type="date" name="dod" value="<?php echo esc_attr( ( ! empty($entry->dod) && $entry->dod !== '0000-00-00' ) ? $entry->dod : '' ); ?>">
                    </div>
                    <div class="hs-field hs-field-full">
                        <label>Bemerkung zur Person <span>(max. 500 Zeichen)</span></label>
                        <textarea name="bemerkung_person" maxlength="500" rows="4"><?php echo esc_textarea($entry->bemerkung_person ?? ''); ?></textarea>
                        <small class="hs-char-counter" data-target="bemerkung_person">0 / 500 Zeichen</small>
                    </div>
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
                    <div class="hs-field hs-field-full"><label>Straftat</label><textarea name="straftat" rows="3" required><?php echo esc_textarea($entry->straftat ?? ''); ?></textarea></div>
                    <div class="hs-field"><label>Urteil <span>(max. 200 Zeichen)</span></label><input type="text" name="urteil" maxlength="200" value="<?php echo $v('urteil'); ?>" placeholder="z.B. 2 Jahre auf Bewährung"></div>
                    <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="link_quelle" value="<?php echo $v('link_quelle'); ?>" placeholder="https://…"></div>
                    <div class="hs-field"><label>Aktenzeichen <span>(max. 50 Zeichen)</span></label><input type="text" name="aktenzeichen" maxlength="50" value="<?php echo $v('aktenzeichen'); ?>" placeholder="z.B. 1 StR 123/24"></div>
                    <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="bemerkung" rows="4"><?php echo esc_textarea($entry->bemerkung ?? ''); ?></textarea></div>
                    <div class="hs-field"><label>Status Straftat</label><select name="status_straftat"><?php foreach ( $st_opts as $s ) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected($v('status_straftat','Ermittlungen laufen'),$s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></div>
                </div>
            </div>

            <div class="hs-form-section" id="hs-extra-offences-section">
                <h2>⚖ Weitere Straftaten</h2>
                <p style="color:#666;font-size:.9em;margin-bottom:.8rem;">Füge hier weitere Straftaten für dieselbe Person hinzu. Die erste Straftat befindet sich im Abschnitt „Details zur Straftat" oben.</p>
                <div id="hs-offences-container">
                <?php
                $existing_offences = $is_edit ? Handschelle_Database::get_offences( $entry->id ) : array();
                foreach ( $existing_offences as $oi => $off ) :
                    $st_opts_off = handschelle_status_straftat_options();
                ?>
                <div class="hs-offence-row" data-index="<?php echo $oi; ?>">
                    <div class="hs-offence-header">
                        <strong>Straftat <?php echo $oi + 2; ?></strong>
                        <button type="button" class="button hs-offence-remove-btn" data-index="<?php echo $oi; ?>">🗑 Entfernen</button>
                    </div>
                    <input type="hidden" name="hs_offences[<?php echo $oi; ?>][id]"     value="<?php echo intval($off->id); ?>">
                    <input type="hidden" name="hs_offences[<?php echo $oi; ?>][delete]" value="0" class="hs-offence-delete-flag">
                    <div class="hs-form-grid" style="margin-top:.5rem;">
                        <div class="hs-field hs-field-full"><label>Straftat</label><textarea name="hs_offences[<?php echo $oi; ?>][straftat]" rows="3" required><?php echo esc_textarea($off->straftat); ?></textarea></div>
                        <div class="hs-field"><label>Urteil <span>(max. 200)</span></label><input type="text" name="hs_offences[<?php echo $oi; ?>][urteil]" maxlength="200" value="<?php echo esc_attr($off->urteil); ?>"></div>
                        <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="hs_offences[<?php echo $oi; ?>][link_quelle]" value="<?php echo esc_attr($off->link_quelle ?? ''); ?>" placeholder="https://…"></div>
                        <div class="hs-field"><label>Aktenzeichen <span>(max. 50)</span></label><input type="text" name="hs_offences[<?php echo $oi; ?>][aktenzeichen]" maxlength="50" value="<?php echo esc_attr($off->aktenzeichen); ?>"></div>
                        <div class="hs-field"><label>Status Straftat</label>
                            <select name="hs_offences[<?php echo $oi; ?>][status_straftat]">
                                <?php foreach ( $st_opts_off as $s ) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected($off->status_straftat, $s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="hs_offences[<?php echo $oi; ?>][bemerkung]" rows="2"><?php echo esc_textarea($off->bemerkung ?? ''); ?></textarea></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div><!-- #hs-offences-container -->
                <button type="button" class="button button-secondary" id="hs-add-offence-btn">+ Weitere Straftat hinzufügen</button>

                <!-- Hidden template for JS cloning -->
                <script type="text/html" id="hs-offence-template">
                <div class="hs-offence-row" data-index="__IDX__">
                    <div class="hs-offence-header">
                        <strong>Straftat __NUM__</strong>
                        <button type="button" class="button hs-offence-remove-btn" data-index="__IDX__">🗑 Entfernen</button>
                    </div>
                    <input type="hidden" name="hs_offences[__IDX__][id]"     value="">
                    <input type="hidden" name="hs_offences[__IDX__][delete]" value="0" class="hs-offence-delete-flag">
                    <div class="hs-form-grid" style="margin-top:.5rem;">
                        <div class="hs-field hs-field-full"><label>Straftat</label><textarea name="hs_offences[__IDX__][straftat]" rows="3"></textarea></div>
                        <div class="hs-field"><label>Urteil <span>(max. 200)</span></label><input type="text" name="hs_offences[__IDX__][urteil]" maxlength="200" value=""></div>
                        <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="hs_offences[__IDX__][link_quelle]" value="" placeholder="https://…"></div>
                        <div class="hs-field"><label>Aktenzeichen <span>(max. 50)</span></label><input type="text" name="hs_offences[__IDX__][aktenzeichen]" maxlength="50" value=""></div>
                        <div class="hs-field"><label>Status Straftat</label>
                            <select name="hs_offences[__IDX__][status_straftat]">
                                <?php foreach ( handschelle_status_straftat_options() as $s ) : ?><option value="<?php echo esc_attr($s); ?>"><?php echo esc_html($s); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="hs_offences[__IDX__][bemerkung]" rows="2"></textarea></div>
                    </div>
                </div>
                </script>
            </div>

            <div class="hs-form-section">
                <h2>📱 Social-Media Links</h2>
                <div class="hs-form-grid">
                    <?php foreach ( $sm_fields as $field => $label ) : ?>
                        <?php $sm_val = $entry ? esc_url( $entry->$field ?? '' ) : ''; ?>
                        <div class="hs-field">
                            <label style="display:flex;align-items:center;gap:.4rem;">
                                <?php echo $label; ?>
                                <a href="<?php echo $sm_val ?: '#'; ?>" target="_blank" rel="noopener noreferrer" title="Link öffnen" style="font-size:.85rem;line-height:1;text-decoration:none;<?php echo $sm_val ? '' : 'visibility:hidden;'; ?>" id="sm-link-<?php echo esc_attr($field); ?>">🔗</a>
                            </label>
                            <input type="url" name="<?php echo esc_attr($field); ?>" id="sm-input-<?php echo esc_attr($field); ?>" value="<?php echo $v($field); ?>" placeholder="https://…"
                                oninput="(function(i){var a=document.getElementById('sm-link-'+i.id.replace('sm-input-',''));a.href=i.value||'#';a.style.visibility=i.value?'':'hidden';})(this)">
                        </div>
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
       SEITE: STRAFTATEN FREIGEBEN
    ================================================================ */
    public function page_offences() {
        $filter    = sanitize_text_field( $_GET['hs_filter'] ?? 'pending' );
        $fg_filter = $filter === 'approved' ? 1 : ( $filter === 'all' ? 'all' : 0 );
        $offences  = Handschelle_Database::get_offences_with_person( $fg_filter );
        $pending   = Handschelle_Database::count_offences( 0 );
        $approved  = Handschelle_Database::count_offences( 1 );
        $total     = Handschelle_Database::count_offences( 'all' );
        $nonce     = wp_create_nonce( 'handschelle_admin_action' );
        $st_opts   = handschelle_status_straftat_options();
        ?>
        <div class="wrap hs-wrap">
            <h1>⚖ Straftaten freigeben</h1>
            <p style="color:#666;margin-bottom:1rem;">Hier werden Straftaten verwaltet, die über das <code>[handschelle-smart]</code>-Formular für bestehende Personen eingereicht wurden.</p>

            <div class="hs-stats-bar">
                <span>Gesamt: <strong><?php echo $total; ?></strong></span>
                <span>Ausstehend: <strong class="<?php echo $pending ? 'hs-warn' : ''; ?>"><?php echo $pending; ?></strong></span>
                <span>Freigegeben: <strong><?php echo $approved; ?></strong></span>
            </div>

            <!-- Filter Tabs -->
            <div class="hs-filter-tabs">
                <?php
                $tabs = array(
                    'pending'  => array( 'label' => '⏳ Ausstehend', 'count' => $pending ),
                    'approved' => array( 'label' => '✅ Freigegeben', 'count' => $approved ),
                    'all'      => array( 'label' => 'Alle',          'count' => $total ),
                );
                foreach ( $tabs as $key => $tab ) : ?>
                    <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-offences&hs_filter={$key}" ) ); ?>"
                       class="hs-filter-tab <?php echo $filter === $key ? 'active' : ''; ?>">
                        <?php echo esc_html( $tab['label'] ); ?> <span class="hs-filter-count"><?php echo $tab['count']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Bulk Actions -->
            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" id="hs-offences-bulk-form">
                <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                <input type="hidden" name="page"      value="handschelle-offences">
                <input type="hidden" name="hs_action" value="bulk_offences">
                <input type="hidden" name="hs_filter" value="<?php echo esc_attr( $filter ); ?>">

                <div class="hs-bulk-bar">
                    <label class="hs-bulk-select-all">
                        <input type="checkbox" id="hs-offences-bulk-all" class="hs-off-bulk-cb"> Alle auswählen
                    </label>
                    <select name="hs_bulk_op" class="hs-bulk-select">
                        <option value="">-- Bulk-Aktion wählen --</option>
                        <option value="approve">✅ Freigeben</option>
                        <option value="reject">🚫 Sperren</option>
                        <option value="delete">🗑 Löschen</option>
                    </select>
                    <button type="submit" class="button hs-btn"
                            onclick="return document.querySelectorAll('.hs-off-bulk-ids:checked').length > 0 || alert('Bitte mindestens einen Eintrag auswählen.')">
                        Ausführen
                    </button>
                </div>

                <?php if ( empty( $offences ) ) : ?>
                    <p class="hs-empty">Keine Straftaten vorhanden.</p>
                <?php else : ?>
                <table class="widefat fixed striped hs-admin-table">
                    <thead>
                        <tr>
                            <th style="width:36px"><input type="checkbox" id="hs-offences-bulk-top" class="hs-off-bulk-cb"></th>
                            <th style="width:140px">Person</th>
                            <th>Straftat</th>
                            <th style="width:110px">Status Straftat</th>
                            <th style="width:90px">Eingereicht</th>
                            <th style="width:90px">Freigabe</th>
                            <th style="width:260px">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $offences as $off ) : ?>
                        <tr id="hs-off-row-<?php echo intval( $off->id ); ?>">
                            <td><input type="checkbox" name="hs_bulk_oids[]" value="<?php echo intval( $off->id ); ?>" class="hs-off-bulk-ids hs-off-bulk-cb"></td>
                            <td>
                                <strong><?php echo esc_html( $off->person_name ); ?></strong>
                                <?php if ( $off->person_partei ) : ?>
                                    <br><small><?php echo esc_html( $off->person_partei ); ?></small>
                                <?php endif; ?>
                                <br>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-edit&id={$off->entry_id}" ) ); ?>" style="font-size:.8em;">Eintrag bearbeiten ↗</a>
                            </td>
                            <td>
                                <?php echo esc_html( mb_substr( $off->straftat, 0, 120 ) . ( mb_strlen( $off->straftat ) > 120 ? '…' : '' ) ); ?>
                                <?php if ( $off->urteil ) : ?>
                                    <br><small><em>Urteil:</em> <?php echo esc_html( $off->urteil ); ?></small>
                                <?php endif; ?>
                                <?php if ( $off->link_quelle ) : ?>
                                    <br><small><a href="<?php echo esc_url( $off->link_quelle ); ?>" target="_blank" rel="noopener noreferrer">🔗 Quelle</a></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="hs-badge hs-badge-<?php echo esc_attr( sanitize_title( $off->status_straftat ) ); ?>"><?php echo esc_html( $off->status_straftat ); ?></span></td>
                            <td style="font-size:.8em;color:#666;"><?php echo esc_html( $off->datum_eintrag ?: substr( $off->erstellt_am, 0, 10 ) ); ?></td>
                            <td><?php echo $off->freigegeben ? '<span class="hs-badge hs-badge-aktiv">✅ Freigegeben</span>' : '<span class="hs-badge hs-badge-pending">⏳ Ausstehend</span>'; ?></td>
                            <td class="hs-actions">
                                <?php if ( ! $off->freigegeben ) : ?>
                                    <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-offences&hs_action=approve_offence&oid={$off->id}&hs_filter={$filter}&_wpnonce={$nonce}" ) ); ?>" class="button button-small button-primary">✅ Freigeben</a>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-offences&hs_action=reject_offence&oid={$off->id}&hs_filter={$filter}&_wpnonce={$nonce}" ) ); ?>" class="button button-small">🚫 Sperren</a>
                                <?php endif; ?>
                                <button type="button" class="button button-small" onclick="hsOffToggleEdit(<?php echo intval( $off->id ); ?>)">✏ Bearbeiten</button>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-offences&hs_action=delete_offence&oid={$off->id}&hs_filter={$filter}&_wpnonce={$nonce}" ) ); ?>" class="button button-small hs-btn-delete" onclick="return confirm('Straftat wirklich löschen?')">🗑 Löschen</a>
                            </td>
                        </tr>
                        <!-- Inline edit row -->
                        <tr id="hs-off-edit-<?php echo intval( $off->id ); ?>" class="hs-offence-edit-row" style="display:none;">
                            <td colspan="7" style="padding:0;">
                                <div class="hs-offence-inline-edit">
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                                        <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                                        <input type="hidden" name="page"      value="handschelle-offences">
                                        <input type="hidden" name="hs_action" value="edit_offence">
                                        <input type="hidden" name="oid"       value="<?php echo intval( $off->id ); ?>">
                                        <input type="hidden" name="hs_filter" value="<?php echo esc_attr( $filter ); ?>">

                                        <h3 style="margin-top:0;">✏ Straftat bearbeiten <small style="color:#666;font-weight:normal;">— <?php echo esc_html( $off->person_name ); ?></small></h3>
                                        <div class="hs-form-grid">
                                            <div class="hs-field hs-field-full">
                                                <label>Straftat</label>
                                                <textarea name="straftat" rows="4" required><?php echo esc_textarea( $off->straftat ); ?></textarea>
                                            </div>
                                            <div class="hs-field">
                                                <label>Urteil <span>(max. 200 Zeichen)</span></label>
                                                <input type="text" name="urteil" maxlength="200" value="<?php echo esc_attr( $off->urteil ); ?>">
                                            </div>
                                            <div class="hs-field">
                                                <label>Link zur Quelle</label>
                                                <input type="url" name="link_quelle" value="<?php echo esc_attr( $off->link_quelle ?? '' ); ?>" placeholder="https://…">
                                            </div>
                                            <div class="hs-field">
                                                <label>Aktenzeichen <span>(max. 50 Zeichen)</span></label>
                                                <input type="text" name="aktenzeichen" maxlength="50" value="<?php echo esc_attr( $off->aktenzeichen ); ?>">
                                            </div>
                                            <div class="hs-field">
                                                <label>Status der Straftat</label>
                                                <select name="status_straftat">
                                                    <?php foreach ( $st_opts as $s ) : ?>
                                                        <option value="<?php echo esc_attr($s); ?>" <?php selected( $off->status_straftat, $s ); ?>><?php echo esc_html($s); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="hs-field">
                                                <label>Datum</label>
                                                <input type="date" name="datum_eintrag" value="<?php echo esc_attr( $off->datum_eintrag ?: '' ); ?>">
                                            </div>
                                            <div class="hs-field hs-field-full">
                                                <label>Bemerkung</label>
                                                <textarea name="bemerkung" rows="3"><?php echo esc_textarea( $off->bemerkung ?? '' ); ?></textarea>
                                            </div>
                                            <div class="hs-field hs-field-full">
                                                <label class="hs-checkbox-label">
                                                    <input type="checkbox" name="freigegeben" value="1" <?php checked( intval( $off->freigegeben ), 1 ); ?>>
                                                    Straftat freigeben (öffentlich sichtbar)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="hs-form-actions" style="margin-top:.8rem;">
                                            <button type="submit" class="button button-primary">💾 Speichern</button>
                                            <button type="button" class="button" onclick="hsOffToggleEdit(<?php echo intval( $off->id ); ?>)">Abbrechen</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </form>

            <?php echo $this->hs_footer(); ?>
        </div>
        <script>
        function hsOffToggleEdit(id) {
            var row = document.getElementById('hs-off-edit-' + id);
            if (!row) return;
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
        (function(){
            function syncOff(source) {
                document.querySelectorAll('.hs-off-bulk-ids').forEach(function(cb){ cb.checked = source.checked; });
            }
            var a = document.getElementById('hs-offences-bulk-all');
            var b = document.getElementById('hs-offences-bulk-top');
            if (a) a.addEventListener('change', function(){ syncOff(this); if(b) b.checked = this.checked; });
            if (b) b.addEventListener('change', function(){ syncOff(this); if(a) a.checked = this.checked; });
        })();
        </script>
        <style>
        .hs-offence-inline-edit { padding: 1.2rem 1.5rem; background: #f9f9f9; border-top: 3px solid #2271b1; }
        </style>
        <?php
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

            <!-- ── OVERVIEW ── -->
            <div class="hs-form-section" style="background:#f0f6fc;border-left:4px solid #2271b1;">
                <h2 style="margin-top:0;">ℹ Übersicht der Funktionen</h2>
                <table style="border-collapse:collapse;width:100%;font-size:.92em;">
                    <thead>
                        <tr style="border-bottom:2px solid #c3c4c7;">
                            <th style="text-align:left;padding:.4rem .8rem;">Funktion</th>
                            <th style="text-align:left;padding:.4rem .8rem;">Was wird gesichert?</th>
                            <th style="text-align:left;padding:.4rem .8rem;">Format</th>
                            <th style="text-align:left;padding:.4rem .8rem;">Beim Wiederherstellen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td style="padding:.4rem .8rem;white-space:nowrap;">📦 Vollständiges Backup</td>
                            <td style="padding:.4rem .8rem;">Alle Personen-Einträge (inkl. Straftaten) + zugehörige Bilder aus der Medienbibliothek</td>
                            <td style="padding:.4rem .8rem;">ZIP (CSV + Bilder)</td>
                            <td style="padding:.4rem .8rem;">Alle bestehenden Einträge werden <strong>gelöscht</strong> und neu eingelesen; Bilder werden hinzugefügt</td>
                        </tr>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td style="padding:.4rem .8rem;white-space:nowrap;">📄 Seiten-Backup</td>
                            <td style="padding:.4rem .8rem;">Alle WordPress-Seiten (Titel, Inhalt, Slug, Status, Veröffentlichungsdatum, benutzerdefinierte Felder)</td>
                            <td style="padding:.4rem .8rem;">JSON</td>
                            <td style="padding:.4rem .8rem;">Seiten werden per Slug abgeglichen – bestehende werden aktualisiert, neue werden angelegt</td>
                        </tr>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td style="padding:.4rem .8rem;white-space:nowrap;">📝 Beitrags-Backup</td>
                            <td style="padding:.4rem .8rem;">Alle WordPress-Beiträge (Titel, Inhalt, Slug, Status, Kategorien, Tags, benutzerdefinierte Felder)</td>
                            <td style="padding:.4rem .8rem;">JSON</td>
                            <td style="padding:.4rem .8rem;">Beiträge werden per Slug abgeglichen – bestehende werden aktualisiert, neue werden angelegt; Kategorien/Tags werden bei Bedarf neu erstellt</td>
                        </tr>
                        <tr>
                            <td style="padding:.4rem .8rem;white-space:nowrap;">🎨 Theme-Backup</td>
                            <td style="padding:.4rem .8rem;">Alle Dateien des aktuell aktiven Themes (Templates, CSS, JS, Bilder, functions.php usw.)</td>
                            <td style="padding:.4rem .8rem;">ZIP</td>
                            <td style="padding:.4rem .8rem;">Das Theme-Verzeichnis wird vollständig ersetzt; das Theme bleibt aktiv, falls es bereits aktiv war</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── ONE-CLICK-ALL BACKUP ── -->
            <div class="hs-form-section" style="margin-top:2rem;border:2px solid #2271b1;background:#f6f9ff;">
                <h2 style="margin-top:0;">🚀 One-Klick-All! – Komplettes WordPress-Backup</h2>
                <p>Erstellt <strong>ein einziges ZIP-Archiv</strong> mit dem vollständigen Zustand dieser WordPress-Installation:</p>
                <ul style="margin:.4rem 0 .8rem 1.5rem;line-height:1.8;">
                    <li>🗄 <strong>Datenbank</strong> – alle WordPress-Tabellen als SQL-Dump</li>
                    <li>🖼 <strong>Medien</strong> – alle Dateien aus <code>wp-content/uploads/</code></li>
                    <li>🎨 <strong>Theme</strong> – alle Dateien des aktuell aktiven Themes</li>
                    <li>📝 <strong>Beiträge</strong> – alle WordPress-Beiträge inkl. Kategorien, Tags und Meta-Felder</li>
                    <li>📄 <strong>Seiten</strong> – alle WordPress-Seiten inkl. Meta-Felder</li>
                    <li>📋 <strong>Manifest</strong> – Metadaten (Datum, WP-Version, Site-URL, PHP-Version)</li>
                </ul>
                <p style="color:#555;">Das Archiv kann auf demselben oder einem anderen WordPress-Server vollständig wiederhergestellt werden.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="backup_all">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <button type="submit" class="button button-primary hs-btn" style="font-size:1.05em;padding:.5em 1.4em;">🚀 One-Klick-All! Backup herunterladen</button>
                </form>
            </div>

            <!-- ── ONE-CLICK-ALL RESTORE ── -->
            <div class="hs-form-section" style="margin-top:1rem;border:2px solid #b32d2e;background:#fff6f6;">
                <h2 style="margin-top:0;">⬆ One-Klick-All! – Komplette Wiederherstellung</h2>
                <p>Stellt Datenbank, Medien, Theme, Beiträge und Seiten aus einem zuvor erstellten One-Klick-All-Archiv wieder her.</p>
                <p style="color:#c0392b;font-weight:600;">⚠ Alle betroffenen Bereiche werden vollständig überschrieben. Diese Aktion kann nicht rückgängig gemacht werden.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="restore_all">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>One-Klick-All ZIP auswählen</label>
                        <input type="file" name="all_backup_zip" accept=".zip" required>
                    </div>
                    <label class="hs-checkbox-label" style="margin-bottom:.8rem;display:block;">
                        <input type="checkbox" name="restore_all_confirm" value="1" required>
                        Ich verstehe, dass Datenbank, Medien, Theme, Beiträge und Seiten vollständig überschrieben werden.
                    </label>
                    <button type="submit" class="button hs-btn-danger" style="font-size:1.05em;padding:.5em 1.4em;" onclick="return confirm('WordPress wirklich komplett wiederherstellen? Alle Daten werden überschrieben!')">⬆ One-Klick-All! Wiederherstellen</button>
                </form>
            </div>

            <hr style="margin:2rem 0;border:none;border-top:2px solid #ddd;">

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
            <!-- ── BACKUP PAGES ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>📄 Seiten-Backup</h2>
                <?php
                $page_count = wp_count_posts( 'page' );
                $total_pages = array_sum( (array) $page_count );
                ?>
                <p>Exportiert alle WordPress-Seiten als JSON-Datei (Titel, Inhalt, Slug, Status, Meta).<br>
                   Aktuell: <strong><?php echo intval( $total_pages ); ?> Seiten</strong> (alle Status).</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="backup_pages">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <button type="submit" class="button button-primary hs-btn">📄 Seiten exportieren</button>
                </form>
            </div>

            <!-- ── RESTORE PAGES ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>⬆ Seiten wiederherstellen</h2>
                <p>Spielt eine zuvor exportierte JSON-Datei ein. Für jede Seite wird der Slug als Schlüssel verwendet:
                   bereits vorhandene Seiten werden aktualisiert (Inhalt, Titel, Status, Meta), nicht vorhandene werden neu angelegt.
                   Andere Seiten, die nicht in der Datei enthalten sind, bleiben unverändert.</p>
                <p style="color:#c0392b;font-weight:600;">⚠ Bestehende Seiten mit gleichem Slug werden überschrieben. Neue Seiten werden angelegt.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="restore_pages">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>Seiten-JSON auswählen</label>
                        <input type="file" name="pages_json" accept=".json" required>
                    </div>
                    <label class="hs-checkbox-label" style="margin-bottom:.8rem;display:block;">
                        <input type="checkbox" name="restore_pages_confirm" value="1" required>
                        Ich verstehe, dass bestehende Seiten überschrieben werden können.
                    </label>
                    <button type="submit" class="button hs-btn-danger" onclick="return confirm('Seiten wirklich wiederherstellen?')">⬆ Seiten einspielen</button>
                </form>
            </div>

            <!-- ── BACKUP POSTS ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>📝 Beitrags-Backup</h2>
                <?php
                $post_count = wp_count_posts( 'post' );
                $total_posts = array_sum( (array) $post_count );
                ?>
                <p>Exportiert alle WordPress-Beiträge als JSON-Datei (Titel, Inhalt, Slug, Status, Kategorien, Tags, Meta).<br>
                   Aktuell: <strong><?php echo intval( $total_posts ); ?> Beiträge</strong> (alle Status).</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="backup_posts">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <button type="submit" class="button button-primary hs-btn">📝 Beiträge exportieren</button>
                </form>
            </div>

            <!-- ── RESTORE POSTS ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>⬆ Beiträge wiederherstellen</h2>
                <p>Spielt eine zuvor exportierte JSON-Datei ein. Für jeden Beitrag wird der Slug als Schlüssel verwendet:
                   bereits vorhandene Beiträge werden aktualisiert (Inhalt, Titel, Status, Meta), nicht vorhandene werden neu angelegt.
                   Kategorien und Tags werden automatisch erstellt, falls sie noch nicht existieren.
                   Andere Beiträge, die nicht in der Datei enthalten sind, bleiben unverändert.</p>
                <p style="color:#c0392b;font-weight:600;">⚠ Bestehende Beiträge mit gleichem Slug werden überschrieben. Neue Beiträge werden angelegt.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="restore_posts">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>Beitrags-JSON auswählen</label>
                        <input type="file" name="posts_json" accept=".json" required>
                    </div>
                    <label class="hs-checkbox-label" style="margin-bottom:.8rem;display:block;">
                        <input type="checkbox" name="restore_posts_confirm" value="1" required>
                        Ich verstehe, dass bestehende Beiträge überschrieben werden können.
                    </label>
                    <button type="submit" class="button hs-btn-danger" onclick="return confirm('Beiträge wirklich wiederherstellen?')">⬆ Beiträge einspielen</button>
                </form>
            </div>

            <!-- ── BACKUP THEME ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>🎨 Theme-Backup</h2>
                <?php
                $theme      = wp_get_theme();
                $theme_name = $theme->get( 'Name' );
                $theme_ver  = $theme->get( 'Version' );
                ?>
                <p>Exportiert alle Dateien des aktuell aktiven Themes als ZIP-Archiv – inklusive Templates, Stylesheet, JavaScript,
                   Bilddateien und <code>functions.php</code>. Das Archiv kann als Sicherung oder zur Übertragung auf eine andere
                   WordPress-Installation verwendet werden.<br>
                   Aktuelles Theme: <strong><?php echo esc_html( $theme_name ); ?></strong>
                   (Version <?php echo esc_html( $theme_ver ); ?>).</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="backup_theme">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <button type="submit" class="button button-primary hs-btn">🎨 Theme exportieren</button>
                </form>
            </div>

            <!-- ── RESTORE THEME ── -->
            <div class="hs-form-section" style="margin-top:2rem;">
                <h2>⬆ Theme wiederherstellen</h2>
                <p>Spielt ein zuvor exportiertes Theme-ZIP ein. Der Theme-Ordner im ZIP bestimmt den Zielordner unter
                   <code>wp-content/themes/</code>. Ein eventuell vorhandenes Verzeichnis gleichen Namens wird dabei vollständig
                   gelöscht und neu entpackt. War das Theme bereits aktiv, bleibt es nach der Wiederherstellung aktiv.</p>
                <p style="color:#c0392b;font-weight:600;">⚠ Das bestehende Theme-Verzeichnis wird vollständig überschrieben.</p>
                <form method="post" action="<?php echo esc_url( admin_url('admin.php') ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                    <input type="hidden" name="hs_action" value="restore_theme">
                    <input type="hidden" name="page" value="handschelle-backup">
                    <div class="hs-field" style="max-width:420px;margin-bottom:.8rem;">
                        <label>Theme-ZIP auswählen</label>
                        <input type="file" name="theme_zip" accept=".zip" required>
                    </div>
                    <label class="hs-checkbox-label" style="margin-bottom:.8rem;display:block;">
                        <input type="checkbox" name="restore_theme_confirm" value="1" required>
                        Ich verstehe, dass das bestehende Theme überschrieben wird.
                    </label>
                    <button type="submit" class="button hs-btn-danger" onclick="return confirm('Theme wirklich wiederherstellen?')">⬆ Theme einspielen</button>
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
        $cols = array( 'id','datum_eintrag','name','spitzname','beruf','geburtsort','geburtsland','geburtsdatum','verstorben','dod','bild','partei','aufgabe_partei','parlament','parlament_name','status_aktiv','private_email','oeffentliche_email','straftat','urteil','link_quelle','aktenzeichen','bemerkung_person','bemerkung','status_straftat','sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_sonstige','sm_linkedin','sm_xing','sm_truth_social','freigegeben','erstellt_am','geaendert_am' );
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

        // Build offences CSV (entry_id references main table id)
        $off_cols = array( 'entry_id', 'straftat', 'urteil', 'status_straftat', 'link_quelle', 'aktenzeichen', 'bemerkung' );
        $off_csv  = "\xEF\xBB\xBF" . implode( ';', $off_cols ) . "\r\n";
        foreach ( $entries as $e ) {
            $offences = Handschelle_Database::get_offences( $e->id );
            foreach ( $offences as $off ) {
                $row = array();
                foreach ( $off_cols as $c ) {
                    $val = $c === 'entry_id' ? $e->id : ( $off->$c ?? '' );
                    if ( strpbrk( (string) $val, ";\"\r\n" ) !== false ) {
                        $val = '"' . str_replace( '"', '""', $val ) . '"';
                    }
                    $row[] = $val;
                }
                $off_csv .= implode( ';', $row ) . "\r\n";
            }
        }
        $zip->addFromString( 'handschelle-offences.csv', $off_csv );

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
            // Header-based mapping: works with old and new backup formats
            $restore_headers = fgetcsv( $fh, 0, ';' );
            if ( empty( $restore_headers ) ) { fclose( $fh ); } else {
            $restore_col_map = array_flip( array_map( 'trim', $restore_headers ) );
            $g = function( $field ) use ( &$row, $restore_col_map ) {
                return ( isset( $restore_col_map[ $field ] ) && isset( $row[ $restore_col_map[ $field ] ] ) )
                    ? $row[ $restore_col_map[ $field ] ] : '';
            };
            // entry_id_map: old CSV id → new DB id (needed for offences restore)
            $entry_id_map = array();
            while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
                if ( count( $row ) < 10 ) continue;
                // Remap bild attachment ID via bild-map.json; validate result exists locally
                $bild_raw = sanitize_text_field( $g( 'bild' ) );
                if ( is_numeric( $bild_raw ) && intval( $bild_raw ) > 0 ) {
                    $old_bild_id = intval( $bild_raw );
                    if ( isset( $old_id_to_new_id[ $old_bild_id ] ) ) {
                        $bild_val = $old_id_to_new_id[ $old_bild_id ];
                    } elseif ( get_attached_file( $old_bild_id ) ) {
                        $bild_val = $old_bild_id;
                    } else {
                        $bild_val = '';
                    }
                } else {
                    $bild_val = $bild_raw;
                }
                $geburtsdatum_raw = sanitize_text_field( $g( 'geburtsdatum' ) );
                $geburtsdatum_val = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
                $dod_raw          = sanitize_text_field( $g( 'dod' ) );
                $dod_val          = ( $dod_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dod_raw ) ) ? $dod_raw : null;
                $old_entry_id     = intval( $g( 'id' ) );
                $new_entry_id     = Handschelle_Database::insert( array(
                    'datum_eintrag'   => sanitize_text_field( $g( 'datum_eintrag' ) ) ?: date( 'Y-m-d' ),
                    'name'            => substr( sanitize_text_field( $g( 'name' ) ), 0, 50 ),
                    'beruf'           => substr( sanitize_text_field( $g( 'beruf' ) ), 0, 50 ),
                    'geburtsort'      => substr( sanitize_text_field( $g( 'geburtsort' ) ), 0, 100 ),
                    'geburtsdatum'    => $geburtsdatum_val,
                    'verstorben'      => intval( $g( 'verstorben' ) ),
                    'dod'             => $dod_val,
                    'bild'            => $bild_val,
                    'partei'          => substr( sanitize_text_field( $g( 'partei' ) ), 0, 50 ),
                    'aufgabe_partei'  => substr( sanitize_text_field( $g( 'aufgabe_partei' ) ), 0, 100 ),
                    'parlament'       => sanitize_text_field( $g( 'parlament' ) ),
                    'parlament_name'  => substr( sanitize_text_field( $g( 'parlament_name' ) ), 0, 50 ),
                    'status_aktiv'    => intval( $g( 'status_aktiv' ) ),
                    'straftat'        => sanitize_textarea_field( $g( 'straftat' ) ),
                    'urteil'          => substr( sanitize_text_field( $g( 'urteil' ) ), 0, 200 ),
                    'link_quelle'     => esc_url_raw( $g( 'link_quelle' ) ),
                    'aktenzeichen'    => substr( sanitize_text_field( $g( 'aktenzeichen' ) ), 0, 50 ),
                    'bemerkung_person' => substr( sanitize_textarea_field( $g( 'bemerkung_person' ) ), 0, 500 ),
                    'bemerkung'       => sanitize_textarea_field( $g( 'bemerkung' ) ),
                    'status_straftat' => sanitize_text_field( $g( 'status_straftat' ) ) ?: 'Ermittlungen laufen',
                    'sm_facebook'     => esc_url_raw( $g( 'sm_facebook' ) ),
                    'sm_youtube'      => esc_url_raw( $g( 'sm_youtube' ) ),
                    'sm_personal'     => esc_url_raw( $g( 'sm_personal' ) ),
                    'sm_twitter'      => esc_url_raw( $g( 'sm_twitter' ) ),
                    'sm_homepage'     => esc_url_raw( $g( 'sm_homepage' ) ),
                    'sm_wikipedia'    => esc_url_raw( $g( 'sm_wikipedia' ) ),
                    'sm_sonstige'     => esc_url_raw( $g( 'sm_sonstige' ) ),
                    'sm_linkedin'     => esc_url_raw( $g( 'sm_linkedin' ) ),
                    'sm_xing'         => esc_url_raw( $g( 'sm_xing' ) ),
                    'sm_truth_social' => esc_url_raw( $g( 'sm_truth_social' ) ),
                    'freigegeben'     => intval( $g( 'freigegeben' ) ),
                ) );
                if ( $new_entry_id && $old_entry_id ) {
                    $entry_id_map[ $old_entry_id ] = $new_entry_id;
                }
                $entry_count++;
            }
            fclose( $fh );
            } // end header check
        }

        // ── 2b. Weitere Straftaten (offences) wiederherstellen ───
        $off_csv_file  = $temp_dir . 'handschelle-offences.csv';
        $offence_count = 0;
        if ( file_exists( $off_csv_file ) ) {
            $ofh = fopen( $off_csv_file, 'r' );
            $obom = fread( $ofh, 3 );
            if ( $obom !== "\xEF\xBB\xBF" ) rewind( $ofh );
            $off_headers = fgetcsv( $ofh, 0, ';' );
            if ( ! empty( $off_headers ) ) {
                $off_col_map = array_flip( array_map( 'trim', $off_headers ) );
                $og = function( $field ) use ( &$off_row, $off_col_map ) {
                    return ( isset( $off_col_map[ $field ] ) && isset( $off_row[ $off_col_map[ $field ] ] ) )
                        ? $off_row[ $off_col_map[ $field ] ] : '';
                };
                while ( ( $off_row = fgetcsv( $ofh, 0, ';' ) ) !== false ) {
                    $old_entry_id = intval( $og( 'entry_id' ) );
                    if ( ! $old_entry_id || ! isset( $entry_id_map[ $old_entry_id ] ) ) continue;
                    $straftat = sanitize_textarea_field( $og( 'straftat' ) );
                    if ( empty( $straftat ) ) continue;
                    Handschelle_Database::insert_offence( $entry_id_map[ $old_entry_id ], array(
                        'straftat'        => $straftat,
                        'urteil'          => substr( sanitize_text_field( $og( 'urteil' ) ), 0, 200 ),
                        'status_straftat' => sanitize_text_field( $og( 'status_straftat' ) ) ?: 'Ermittlungen laufen',
                        'link_quelle'     => esc_url_raw( $og( 'link_quelle' ) ),
                        'aktenzeichen'    => substr( sanitize_text_field( $og( 'aktenzeichen' ) ), 0, 50 ),
                        'bemerkung'       => sanitize_textarea_field( $og( 'bemerkung' ) ),
                    ) );
                    $offence_count++;
                }
            }
            fclose( $ofh );
        }

        // ── 3. Temp cleanup ──────────────────────────────────────
        foreach ( glob( $temp_dir . 'images/*' ) as $f ) @unlink( $f );
        @rmdir( $temp_dir . 'images' );
        @unlink( $temp_dir . 'handschelle-data.csv' );
        @unlink( $temp_dir . 'handschelle-offences.csv' );
        @unlink( $temp_dir . 'bild-map.json' );
        @rmdir( $temp_dir );

        $this->redirect(
            admin_url( 'admin.php?page=handschelle-backup' ),
            "Wiederherstellung abgeschlossen: {$entry_count} Einträge, {$offence_count} Weitere Straftaten, {$img_count} Bilder importiert."
        );
    }

    /* ================================================================
       BACKUP PAGES – alle WP-Seiten als JSON exportieren
    ================================================================ */
    private function backup_pages() {
        $json     = $this->build_pages_export_json();
        $filename = 'handschelle-pages-' . date( 'Y-m-d_His' ) . '.json';

        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $json ) );
        header( 'Pragma: no-cache' );
        echo $json;
        exit;
    }

    /* ================================================================
       RESTORE PAGES – aus JSON-Datei wiederherstellen
    ================================================================ */
    private function restore_pages() {
        if ( empty( $_FILES['pages_json']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Keine JSON-Datei hochgeladen.' );
            return;
        }
        if ( empty( $_POST['restore_pages_confirm'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Bitte Bestätigung ankreuzen.' );
            return;
        }

        $raw = file_get_contents( $_FILES['pages_json']['tmp_name'] );
        if ( $raw === false ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Datei konnte nicht gelesen werden.' );
            return;
        }

        $pages = json_decode( $raw, true );
        if ( ! is_array( $pages ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Ungültiges JSON-Format.' );
            return;
        }

        $counts = $this->import_posts_from_array( $pages, 'page' );
        $this->redirect(
            admin_url( 'admin.php?page=handschelle-backup' ),
            "Seiten wiederhergestellt: {$counts['created']} neu angelegt, {$counts['updated']} aktualisiert."
        );
    }

    /* ================================================================
       BACKUP POSTS – alle WP-Beiträge als JSON exportieren
    ================================================================ */
    private function backup_posts() {
        $json     = $this->build_posts_export_json();
        $filename = 'handschelle-posts-' . date( 'Y-m-d_His' ) . '.json';

        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $json ) );
        header( 'Pragma: no-cache' );
        echo $json;
        exit;
    }

    /* ================================================================
       RESTORE POSTS – aus JSON-Datei wiederherstellen
    ================================================================ */
    private function restore_posts() {
        if ( empty( $_FILES['posts_json']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Keine JSON-Datei hochgeladen.' );
            return;
        }
        if ( empty( $_POST['restore_posts_confirm'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Bitte Bestätigung ankreuzen.' );
            return;
        }

        $raw = file_get_contents( $_FILES['posts_json']['tmp_name'] );
        if ( $raw === false ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Datei konnte nicht gelesen werden.' );
            return;
        }

        $posts = json_decode( $raw, true );
        if ( ! is_array( $posts ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Ungültiges JSON-Format.' );
            return;
        }

        $counts = $this->import_posts_from_array( $posts, 'post' );
        $this->redirect(
            admin_url( 'admin.php?page=handschelle-backup' ),
            "Beiträge wiederhergestellt: {$counts['created']} neu angelegt, {$counts['updated']} aktualisiert."
        );
    }

    /* ================================================================
       BACKUP THEME – aktives Theme als ZIP exportieren
    ================================================================ */
    private function backup_theme() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }

        $theme      = wp_get_theme();
        $theme_slug = $theme->get_stylesheet();
        $theme_dir  = get_stylesheet_directory();

        if ( ! is_dir( $theme_dir ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Theme-Verzeichnis nicht gefunden.' );
            return;
        }

        $zip_path = sys_get_temp_dir() . '/hs-theme-backup-' . time() . '.zip';
        $zip      = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht erstellt werden.' );
            return;
        }

        $base_len = strlen( dirname( $theme_dir ) ) + 1;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $theme_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ( $iterator as $file ) {
            $local_path = substr( $file->getRealPath(), $base_len );
            if ( $file->isDir() ) {
                $zip->addEmptyDir( $local_path );
            } else {
                $zip->addFile( $file->getRealPath(), $local_path );
            }
        }
        $zip->close();

        if ( ! file_exists( $zip_path ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Theme-Backup konnte nicht erstellt werden.' );
            return;
        }

        $filename = 'theme-' . sanitize_file_name( $theme_slug ) . '-' . date( 'Y-m-d_His' ) . '.zip';
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        header( 'Pragma: no-cache' );
        readfile( $zip_path );
        unlink( $zip_path );
        exit;
    }

    /* ================================================================
       RESTORE THEME – Theme aus ZIP-Datei wiederherstellen
    ================================================================ */
    private function restore_theme() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }
        if ( empty( $_FILES['theme_zip']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Keine ZIP-Datei hochgeladen.' );
            return;
        }
        if ( empty( $_POST['restore_theme_confirm'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Bitte Bestätigung ankreuzen.' );
            return;
        }

        $zip = new ZipArchive();
        if ( $zip->open( $_FILES['theme_zip']['tmp_name'] ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht geöffnet werden.' );
            return;
        }

        // Determine the top-level folder inside the ZIP (the theme slug)
        $first_entry = $zip->getNameIndex( 0 );
        $parts       = explode( '/', $first_entry );
        $theme_slug  = $parts[0];

        if ( empty( $theme_slug ) ) {
            $zip->close();
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Ungültige ZIP-Struktur (kein Theme-Verzeichnis gefunden).' );
            return;
        }

        $themes_dir  = get_theme_root();
        $target_dir  = $themes_dir . '/' . $theme_slug;

        // Remove existing theme directory before extracting
        if ( is_dir( $target_dir ) ) {
            $this->delete_directory( $target_dir );
        }

        $zip->extractTo( $themes_dir );
        $zip->close();

        // Activate the restored theme if it matches the currently active one
        $active_slug = get_stylesheet();
        $msg = "Theme \"{$theme_slug}\" erfolgreich wiederhergestellt.";
        if ( $active_slug === $theme_slug ) {
            switch_theme( $theme_slug );
            $msg .= ' Theme ist weiterhin aktiv.';
        }

        $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), $msg );
    }

    /* Helper: recursively delete a directory */
    private function delete_directory( $dir ) {
        if ( ! is_dir( $dir ) ) return;
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $items as $item ) {
            $item->isDir() ? rmdir( $item->getRealPath() ) : unlink( $item->getRealPath() );
        }
        rmdir( $dir );
    }

    /* Helper: recursively copy a directory */
    private function copy_directory( $src, $dst ) {
        wp_mkdir_p( $dst );
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $src, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ( $items as $item ) {
            $target = $dst . '/' . $items->getSubPathname();
            if ( $item->isDir() ) {
                wp_mkdir_p( $target );
            } else {
                copy( $item->getRealPath(), $target );
            }
        }
    }

    /* ================================================================
       BACKUP ALL – komplettes WordPress-Backup als ZIP
    ================================================================ */
    private function backup_all() {
        global $wpdb;

        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }

        $zip_path = sys_get_temp_dir() . '/hs-all-backup-' . time() . '.zip';
        $zip      = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht erstellt werden.' );
            return;
        }

        // ── 1. Manifest ──────────────────────────────────────────────
        $manifest = array(
            'type'         => 'wordpress-complete-backup',
            'created'      => date( 'Y-m-d H:i:s' ),
            'site_url'     => get_bloginfo( 'url' ),
            'wp_version'   => get_bloginfo( 'version' ),
            'php_version'  => PHP_VERSION,
            'db_prefix'    => $wpdb->prefix,
            'active_theme' => get_stylesheet(),
            'contents'     => array( 'database', 'uploads', 'theme', 'posts', 'pages' ),
        );
        $zip->addFromString( 'manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        // ── 2. Datenbank-Dump ────────────────────────────────────────
        $sql = $this->generate_sql_dump();
        $zip->addFromString( 'database/wordpress.sql', $sql );

        // ── 3. Mediendateien (wp-content/uploads/) ───────────────────
        $upload_info = wp_upload_dir();
        $upload_base = $upload_info['basedir'];
        if ( is_dir( $upload_base ) ) {
            $base_parent_len = strlen( $upload_base ) + 1;
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $upload_base, RecursiveDirectoryIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ( $iter as $file ) {
                $local = 'uploads/' . substr( $file->getRealPath(), $base_parent_len );
                if ( $file->isDir() ) {
                    $zip->addEmptyDir( $local );
                } else {
                    $zip->addFile( $file->getRealPath(), $local );
                }
            }
        }

        // ── 4. Aktives Theme ─────────────────────────────────────────
        $theme_dir  = get_stylesheet_directory();
        $theme_slug = get_stylesheet();
        if ( is_dir( $theme_dir ) ) {
            $theme_parent_len = strlen( dirname( $theme_dir ) ) + 1;
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $theme_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ( $iter as $file ) {
                $local = 'theme/' . substr( $file->getRealPath(), $theme_parent_len );
                if ( $file->isDir() ) {
                    $zip->addEmptyDir( $local );
                } else {
                    $zip->addFile( $file->getRealPath(), $local );
                }
            }
        }

        // ── 5. Beiträge ───────────────────────────────────────────────
        $zip->addFromString( 'posts.json', $this->build_posts_export_json() );

        // ── 6. Seiten ─────────────────────────────────────────────────
        $zip->addFromString( 'pages.json', $this->build_pages_export_json() );

        $zip->close();

        if ( ! file_exists( $zip_path ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Backup konnte nicht erstellt werden.' );
            return;
        }

        $filename = 'wordpress-complete-backup-' . date( 'Y-m-d_His' ) . '.zip';
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        header( 'Pragma: no-cache' );
        readfile( $zip_path );
        unlink( $zip_path );
        exit;
    }

    /* ================================================================
       RESTORE ALL – komplette Wiederherstellung aus ZIP
    ================================================================ */
    private function restore_all() {
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: PHP ZipArchive nicht verfügbar.' );
            return;
        }
        if ( empty( $_FILES['all_backup_zip']['tmp_name'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Keine ZIP-Datei hochgeladen.' );
            return;
        }
        if ( empty( $_POST['restore_all_confirm'] ) ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Bitte Bestätigung ankreuzen.' );
            return;
        }

        $zip = new ZipArchive();
        if ( $zip->open( $_FILES['all_backup_zip']['tmp_name'] ) !== true ) {
            $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: ZIP-Datei konnte nicht geöffnet werden.' );
            return;
        }

        $temp_dir = trailingslashit( sys_get_temp_dir() ) . 'hs_all_restore_' . time() . '/';
        wp_mkdir_p( $temp_dir );
        $zip->extractTo( $temp_dir );
        $zip->close();

        $log = array();

        // ── Manifest prüfen ──────────────────────────────────────────
        $manifest_file = $temp_dir . 'manifest.json';
        if ( file_exists( $manifest_file ) ) {
            $manifest = json_decode( file_get_contents( $manifest_file ), true );
            if ( ! empty( $manifest['type'] ) && $manifest['type'] !== 'wordpress-complete-backup' ) {
                $this->delete_directory( $temp_dir );
                $this->redirect( admin_url( 'admin.php?page=handschelle-backup' ), 'Fehler: Kein gültiges One-Klick-All-Archiv.' );
                return;
            }
            if ( $manifest ) {
                $log[] = 'Backup vom ' . ( $manifest['created'] ?? '?' ) . ' (' . ( $manifest['site_url'] ?? '?' ) . ').';
            }
        }

        // ── 1. Datenbank ─────────────────────────────────────────────
        $sql_file = $temp_dir . 'database/wordpress.sql';
        if ( file_exists( $sql_file ) ) {
            $db_errors = $this->execute_sql_dump( file_get_contents( $sql_file ) );
            $log[] = "Datenbank importiert ($db_errors Fehler).";
        }

        // ── 2. Mediendateien ─────────────────────────────────────────
        $uploads_src  = $temp_dir . 'uploads/';
        $upload_info  = wp_upload_dir();
        $upload_base  = $upload_info['basedir'];
        if ( is_dir( $uploads_src ) ) {
            $this->delete_directory( $upload_base );
            $this->copy_directory( $uploads_src, $upload_base );
            $log[] = 'Mediendateien wiederhergestellt.';
        }

        // ── 3. Theme ─────────────────────────────────────────────────
        $theme_src  = $temp_dir . 'theme/';
        $themes_dir = get_theme_root();
        if ( is_dir( $theme_src ) ) {
            foreach ( scandir( $theme_src ) as $entry ) {
                if ( $entry === '.' || $entry === '..' ) continue;
                $src = $theme_src . $entry;
                $dst = $themes_dir . '/' . $entry;
                if ( is_dir( $src ) ) {
                    if ( is_dir( $dst ) ) $this->delete_directory( $dst );
                    $this->copy_directory( $src, $dst );
                }
            }
            // Re-activate if this was the active theme
            $active_slug = get_stylesheet();
            if ( file_exists( $themes_dir . '/' . $active_slug . '/style.css' ) ) {
                switch_theme( $active_slug );
            }
            $log[] = 'Theme wiederhergestellt.';
        }

        // ── 4. Beiträge ───────────────────────────────────────────────
        $posts_file = $temp_dir . 'posts.json';
        if ( file_exists( $posts_file ) ) {
            $posts = json_decode( file_get_contents( $posts_file ), true );
            if ( is_array( $posts ) ) {
                $counts = $this->import_posts_from_array( $posts, 'post' );
                $log[] = "Beiträge: {$counts['created']} neu, {$counts['updated']} aktualisiert.";
            }
        }

        // ── 5. Seiten ─────────────────────────────────────────────────
        $pages_file = $temp_dir . 'pages.json';
        if ( file_exists( $pages_file ) ) {
            $pages = json_decode( file_get_contents( $pages_file ), true );
            if ( is_array( $pages ) ) {
                $counts = $this->import_posts_from_array( $pages, 'page' );
                $log[] = "Seiten: {$counts['created']} neu, {$counts['updated']} aktualisiert.";
            }
        }

        $this->delete_directory( $temp_dir );

        $this->redirect(
            admin_url( 'admin.php?page=handschelle-backup' ),
            'Komplette Wiederherstellung abgeschlossen. ' . implode( ' ', $log )
        );
    }

    /* ================================================================
       HELPER – SQL-Dump aller WP-Tabellen erzeugen
    ================================================================ */
    private function generate_sql_dump() {
        global $wpdb;

        $sql  = "-- WordPress Complete Database Backup\n";
        $sql .= "-- Generated: " . date( 'Y-m-d H:i:s' ) . "\n";
        $sql .= "-- Site: " . get_bloginfo( 'url' ) . "\n";
        $sql .= "-- WP Version: " . get_bloginfo( 'version' ) . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );

        foreach ( $tables as $table ) {
            // Table structure
            $create_row = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N ); // phpcs:ignore
            $sql .= "-- Table: `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create_row[1] . ";\n\n";

            // Table data in chunks of 500 rows
            $offset = 0;
            $chunk  = 500;
            do {
                $rows = $wpdb->get_results( // phpcs:ignore
                    "SELECT * FROM `{$table}` LIMIT {$chunk} OFFSET {$offset}",
                    ARRAY_A
                );
                foreach ( $rows as $row ) {
                    $values = array();
                    foreach ( $row as $val ) {
                        if ( $val === null ) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes( $val ) . "'";
                        }
                    }
                    $sql .= 'INSERT INTO `' . $table . '` VALUES (' . implode( ', ', $values ) . ");\n";
                }
                $offset += $chunk;
            } while ( count( $rows ) === $chunk );

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /* ================================================================
       HELPER – SQL-Dump ausführen
    ================================================================ */
    private function execute_sql_dump( $sql_content ) {
        global $wpdb;

        $errors  = 0;
        $current = '';

        foreach ( explode( "\n", $sql_content ) as $line ) {
            $line = rtrim( $line );
            // Skip comments and empty lines
            if ( $line === '' || strncmp( $line, '--', 2 ) === 0 || strncmp( $line, '/*', 2 ) === 0 ) continue;

            $current .= $line . "\n";

            if ( substr( rtrim( $line ), -1 ) === ';' ) {
                $stmt = trim( $current );
                if ( $stmt !== '' ) {
                    $wpdb->query( $stmt ); // phpcs:ignore
                    if ( $wpdb->last_error ) $errors++;
                }
                $current = '';
            }
        }

        return $errors;
    }

    /* ================================================================
       HELPER – Posts/Pages-Export als JSON-String
    ================================================================ */
    private function build_posts_export_json() {
        $posts = get_posts( array(
            'post_type'   => 'post',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );
        $data = array();
        foreach ( $posts as $post ) {
            $meta = get_post_meta( $post->ID );
            foreach ( array_keys( $meta ) as $key ) {
                if ( strpos( $key, '_' ) === 0 ) unset( $meta[ $key ] );
            }
            $categories = wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) );
            $tags       = wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) );
            $data[]     = array(
                'post_title'     => $post->post_title,
                'post_name'      => $post->post_name,
                'post_content'   => $post->post_content,
                'post_excerpt'   => $post->post_excerpt,
                'post_status'    => $post->post_status,
                'post_date'      => $post->post_date,
                'comment_status' => $post->comment_status,
                'ping_status'    => $post->ping_status,
                'categories'     => is_array( $categories ) ? $categories : array(),
                'tags'           => is_array( $tags ) ? $tags : array(),
                'meta'           => $meta,
            );
        }
        return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    private function build_pages_export_json() {
        $pages = get_posts( array(
            'post_type'   => 'page',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );
        $data = array();
        foreach ( $pages as $page ) {
            $meta = get_post_meta( $page->ID );
            foreach ( array_keys( $meta ) as $key ) {
                if ( strpos( $key, '_' ) === 0 ) unset( $meta[ $key ] );
            }
            $data[] = array(
                'post_title'     => $page->post_title,
                'post_name'      => $page->post_name,
                'post_content'   => $page->post_content,
                'post_excerpt'   => $page->post_excerpt,
                'post_status'    => $page->post_status,
                'post_date'      => $page->post_date,
                'menu_order'     => $page->menu_order,
                'comment_status' => $page->comment_status,
                'ping_status'    => $page->ping_status,
                'meta'           => $meta,
            );
        }
        return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /* ================================================================
       HELPER – Posts oder Pages aus Array importieren
    ================================================================ */
    private function import_posts_from_array( $items, $post_type ) {
        $created = 0;
        $updated = 0;

        foreach ( $items as $p ) {
            if ( empty( $p['post_title'] ) && empty( $p['post_name'] ) ) continue;

            $allowed_statuses = array( 'publish', 'draft', 'private', 'pending' );
            $post_data        = array(
                'post_type'      => $post_type,
                'post_title'     => sanitize_text_field( $p['post_title'] ?? '' ),
                'post_name'      => sanitize_title( $p['post_name'] ?? '' ),
                'post_content'   => wp_kses_post( $p['post_content'] ?? '' ),
                'post_excerpt'   => sanitize_textarea_field( $p['post_excerpt'] ?? '' ),
                'post_status'    => in_array( $p['post_status'] ?? '', $allowed_statuses, true ) ? $p['post_status'] : 'draft',
                'post_date'      => sanitize_text_field( $p['post_date'] ?? '' ),
                'comment_status' => in_array( $p['comment_status'] ?? '', array( 'open', 'closed' ), true ) ? $p['comment_status'] : 'closed',
                'ping_status'    => in_array( $p['ping_status'] ?? '', array( 'open', 'closed' ), true ) ? $p['ping_status'] : 'closed',
            );
            if ( $post_type === 'page' ) {
                $post_data['menu_order'] = intval( $p['menu_order'] ?? 0 );
            }

            $existing = get_page_by_path( $post_data['post_name'], OBJECT, $post_type );
            if ( $existing ) {
                $post_data['ID'] = $existing->ID;
                wp_update_post( $post_data );
                $post_id = $existing->ID;
                $updated++;
            } else {
                $post_id = wp_insert_post( $post_data );
                $created++;
            }

            if ( ! $post_id || is_wp_error( $post_id ) ) continue;

            // Categories and tags (posts only)
            if ( $post_type === 'post' ) {
                if ( ! empty( $p['categories'] ) && is_array( $p['categories'] ) ) {
                    $cat_ids = array();
                    foreach ( $p['categories'] as $cat_name ) {
                        $cat = get_term_by( 'name', $cat_name, 'category' );
                        if ( $cat ) {
                            $cat_ids[] = $cat->term_id;
                        } else {
                            $new_cat = wp_insert_term( $cat_name, 'category' );
                            if ( ! is_wp_error( $new_cat ) ) $cat_ids[] = $new_cat['term_id'];
                        }
                    }
                    if ( $cat_ids ) wp_set_post_categories( $post_id, $cat_ids );
                }
                if ( ! empty( $p['tags'] ) && is_array( $p['tags'] ) ) {
                    wp_set_post_tags( $post_id, $p['tags'] );
                }
            }

            // Meta fields
            if ( ! empty( $p['meta'] ) && is_array( $p['meta'] ) ) {
                foreach ( $p['meta'] as $meta_key => $meta_values ) {
                    $meta_key = sanitize_key( $meta_key );
                    if ( empty( $meta_key ) ) continue;
                    delete_post_meta( $post_id, $meta_key );
                    foreach ( (array) $meta_values as $val ) {
                        add_post_meta( $post_id, $meta_key, maybe_unserialize( $val ) );
                    }
                }
            }
        }

        return array( 'created' => $created, 'updated' => $updated );
    }

    /* ================================================================
       NEUE BENUTZER: automatisch auf "ausstehend" setzen
    ================================================================ */
    public function set_new_user_pending( $user_id ) {
        // Only set pending if no status has been assigned yet (e.g. via shortcode handler)
        if ( ! get_user_meta( $user_id, 'hs_user_status', true ) ) {
            update_user_meta( $user_id, 'hs_user_status', 'pending' );
            wp_new_user_notification( $user_id, null, 'admin' );
        }
    }

    /* ================================================================
       LOGIN-SPERRE: ausstehende / deaktivierte Benutzer
    ================================================================ */
    public function block_pending_users( $user, $username, $password ) {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) return $user;
        $status = get_user_meta( $user->ID, 'hs_user_status', true );
        if ( $status === 'pending' ) {
            return new WP_Error( 'hs_pending', __( 'Dein Konto wartet noch auf Freischaltung durch einen Administrator.', 'die-handschelle' ) );
        }
        if ( $status === 'deactivated' ) {
            return new WP_Error( 'hs_deactivated', __( 'Dein Konto wurde deaktiviert. Bitte wende dich an den Administrator.', 'die-handschelle' ) );
        }
        return $user;
    }

    /* ================================================================
       SEITE: BENUTZER-VERWALTUNG
    ================================================================ */
    public function page_users() {
        $nonce   = wp_create_nonce( 'handschelle_admin_action' );
        $users   = get_users( array( 'orderby' => 'registered', 'order' => 'DESC', 'exclude' => array( get_current_user_id() ) ) );
        $pending = array_filter( $users, fn( $u ) => get_user_meta( $u->ID, 'hs_user_status', true ) === 'pending' );
        ?>
        <div class="wrap hs-wrap">
            <h1>👥 Benutzer-Verwaltung <span class="hs-version"><?php echo esc_html( HANDSCHELLE_VERSION ); ?></span></h1>

            <?php if ( ! empty( $pending ) ) : ?>
                <div class="notice notice-warning"><p>⏳ <strong><?php echo count( $pending ); ?> Konto(s)</strong> warten auf Freischaltung.</p></div>
            <?php endif; ?>

            <table class="widefat fixed striped hs-admin-table" style="margin-top:1rem;">
                <thead>
                    <tr>
                        <th>Benutzername</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Registriert</th>
                        <th style="width:160px">Status</th>
                        <th style="width:260px">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $users ) ) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:#999;">Keine Benutzer vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ( $users as $u ) :
                    $status   = get_user_meta( $u->ID, 'hs_user_status', true );
                    $is_admin = user_can( $u->ID, 'manage_options' );
                    $roles    = array_map( fn( $r ) => translate_user_role( $r ), $u->roles );
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $u->user_login ); ?></strong><br><small><?php echo esc_html( $u->display_name ); ?></small></td>
                        <td><?php echo esc_html( $u->user_email ); ?></td>
                        <td><?php echo esc_html( implode( ', ', $roles ) ); ?></td>
                        <td><?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $u->user_registered ) ) ); ?></td>
                        <td>
                            <?php if ( $is_admin ) : ?>
                                <span class="hs-badge hs-badge-aktiv">Administrator</span>
                            <?php elseif ( $status === 'pending' ) : ?>
                                <span class="hs-badge hs-badge-pending">⏳ Ausstehend</span>
                            <?php elseif ( $status === 'deactivated' ) : ?>
                                <span class="hs-badge hs-badge-inaktiv">🚫 Deaktiviert</span>
                            <?php else : ?>
                                <span class="hs-badge hs-badge-aktiv">✅ Aktiv</span>
                            <?php endif; ?>
                        </td>
                        <td class="hs-actions">
                            <?php if ( ! $is_admin ) : ?>
                                <?php if ( $status !== 'active' ) : ?>
                                    <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-users&hs_action=activate_user&uid={$u->ID}&_wpnonce={$nonce}" ) ); ?>"
                                       class="button button-small button-primary">✅ Freischalten</a>
                                <?php endif; ?>
                                <?php if ( $status !== 'deactivated' ) : ?>
                                    <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-users&hs_action=deactivate_user&uid={$u->ID}&_wpnonce={$nonce}" ) ); ?>"
                                       class="button button-small">🚫 Deaktivieren</a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=handschelle-users&hs_action=delete_hs_user&uid={$u->ID}&_wpnonce={$nonce}" ) ); ?>"
                                   class="button button-small hs-btn-delete"
                                   onclick="return confirm('Benutzer <?php echo esc_js( $u->user_login ); ?> wirklich löschen?')">🗑 Löschen</a>
                            <?php endif; ?>
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
       SEITE: DATENBANK
    ================================================================ */
    public function page_ollama() {
        $nonce               = wp_create_nonce( 'handschelle_admin_action' );
        $ollama_mode         = get_option( 'hs_ollama_mode',             'local' );
        $ollama_url          = get_option( 'hs_ollama_url',              'http://localhost:11434' );
        $ollama_api_key      = get_option( 'hs_ollama_api_key',          '' );
        $default_model       = get_option( 'hs_ollama_default_model',    '' );
        $system_prompt       = get_option( 'hs_ollama_system_prompt',    'Du bist ein hilfreicher Assistent.' );
        $timeout             = intval( get_option( 'hs_ollama_timeout',  120 ) );
        $chat_page           = get_option( 'hs_ollama_chat_page',        '/chat/' );
        $profile_questions   = get_option( 'hs_profile_questions',       '' );
        $profile_sys_prompt  = get_option( 'hs_profile_system_prompt',   'Du bist ein sachlicher Fakten-Assistent. Antworte knapp und präzise.' );
        $profile_provider    = get_option( 'hs_profile_provider',        'ollama' );
        $profile_model       = get_option( 'hs_profile_model',           '' );
        ?>
        <div class="wrap hs-wrap">
            <h1>🤖 Ollama KI-Konfiguration</h1>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=handschelle-ollama' ) ); ?>">
                <?php wp_nonce_field( 'handschelle_admin_action' ); ?>
                <input type="hidden" name="hs_action" value="save_ollama">

                <div class="hs-form">
                    <div class="hs-form-section">
                        <h3>Verbindung</h3>
                        <div class="hs-form-grid">
                            <div class="hs-field hs-field-full">
                                <label>Server-Typ</label>
                                <div style="display:flex;gap:1.5rem;margin-top:.3rem;">
                                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                                        <input type="radio" name="hs_ollama_mode" id="hs_ollama_mode_local"
                                               value="local" <?php checked( $ollama_mode, 'local' ); ?>>
                                        🖥️ Lokaler Server
                                    </label>
                                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                                        <input type="radio" name="hs_ollama_mode" id="hs_ollama_mode_remote"
                                               value="remote" <?php checked( $ollama_mode, 'remote' ); ?>>
                                        ☁️ Remote-Server (Cloud)
                                    </label>
                                </div>
                                <span class="description" id="hs-ollama-mode-hint">
                                    <?php if ( $ollama_mode === 'remote' ) : ?>
                                    Remote-Modus: Die URL des Cloud-Servers und ein optionaler API-Key sind erforderlich. Der Chat-Widget erlaubt keine clientseitige URL-Änderung.
                                    <?php else : ?>
                                    Lokaler Modus: Ollama läuft auf demselben Server wie WordPress (Standard: <code>http://localhost:11434</code>).
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="hs-field" id="hs-ollama-url-row" <?php echo $ollama_mode === 'local' ? 'style="display:none"' : ''; ?>>
                                <label for="hs_ollama_url">Remote Server-URL</label>
                                <input type="text" id="hs_ollama_url" name="hs_ollama_url"
                                       value="<?php echo esc_attr( $ollama_url !== 'http://localhost:11434' ? $ollama_url : '' ); ?>"
                                       placeholder="https://ollama.example.com">
                                <span class="description">Vollständige URL des Remote-Ollama-Servers inkl. Protokoll und Port.</span>
                            </div>

                            <div class="hs-field">
                                <label for="hs_ollama_chat_page">Chat-Seiten-URL</label>
                                <input type="text" id="hs_ollama_chat_page" name="hs_ollama_chat_page"
                                       value="<?php echo esc_attr( $chat_page ); ?>"
                                       placeholder="/chat/">
                                <span class="description">
                                    Pfad zur Seite mit dem <code>[handschelle-chat urlparam="frage"]</code>-Shortcode.
                                    Wird für den „🤖 KI-Analyse"-Link in den Eintrags-Karten verwendet.
                                    Leer lassen, um den Link zu deaktivieren.
                                </span>
                            </div>
                            <div class="hs-field">
                                <label for="hs_ollama_timeout">Timeout (Sekunden)</label>
                                <input type="number" id="hs_ollama_timeout" name="hs_ollama_timeout"
                                       value="<?php echo esc_attr( $timeout ); ?>"
                                       min="10" max="600" style="width:120px;">
                                <span class="description">Max. Wartezeit auf eine Antwort (10–600 s).</span>
                            </div>

                            <div class="hs-field" id="hs-ollama-apikey-row" <?php echo $ollama_mode === 'local' ? 'style="display:none"' : ''; ?>>
                                <label for="hs_ollama_api_key">API-Key (optional)</label>
                                <?php $has_ollama_key = ! empty( $ollama_api_key ); ?>
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <input type="password" id="hs_ollama_api_key" name="hs_ollama_api_key"
                                           value=""
                                           placeholder="<?php echo $has_ollama_key ? '••••••••  (gesetzt – leer lassen zum Beibehalten)' : 'Bearer-Token …'; ?>"
                                           autocomplete="new-password" style="flex:1;font-family:monospace;">
                                    <?php if ( $has_ollama_key ) : ?>
                                    <label style="white-space:nowrap;font-size:.85rem;display:flex;align-items:center;gap:.3rem;">
                                        <input type="checkbox" name="hs_ollama_api_key_clear" value="1"> Key löschen
                                    </label>
                                    <?php endif; ?>
                                </div>
                                <span class="description">
                                    <?php if ( $has_ollama_key ) : ?>
                                    ✅ API-Key ist gesetzt. Wird als <code>Authorization: Bearer …</code> Header übermittelt.
                                    <?php else : ?>
                                    Für Remote-Server mit Authentifizierung. Wird als <code>Authorization: Bearer …</code> Header gesendet.
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <script>
                        (function() {
                            var radios   = document.querySelectorAll('input[name="hs_ollama_mode"]');
                            var urlRow   = document.getElementById('hs-ollama-url-row');
                            var keyRow   = document.getElementById('hs-ollama-apikey-row');
                            var hint     = document.getElementById('hs-ollama-mode-hint');
                            var urlInput = document.getElementById('hs_ollama_url');
                            function update(mode) {
                                var isRemote = mode === 'remote';
                                urlRow.style.display = isRemote ? '' : 'none';
                                keyRow.style.display = isRemote ? '' : 'none';
                                if (!isRemote) { urlInput.value = ''; }
                                hint.innerHTML = isRemote
                                    ? 'Remote-Modus: Die URL des Cloud-Servers und ein optionaler API-Key sind erforderlich. Der Chat-Widget erlaubt keine clientseitige URL-Änderung.'
                                    : 'Lokaler Modus: Ollama läuft auf demselben Server wie WordPress (Standard: <code>http://localhost:11434</code>).';
                            }
                            radios.forEach(function(r) {
                                r.addEventListener('change', function() { update(this.value); });
                            });
                        })();
                        </script>
                    </div>

                    <div class="hs-form-section">
                        <h3>Standard-Einstellungen</h3>
                        <div class="hs-form-grid">
                            <div class="hs-field">
                                <label for="hs_ollama_default_model">Standard-Modell</label>
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <input type="text" id="hs_ollama_default_model" name="hs_ollama_default_model"
                                           value="<?php echo esc_attr( $default_model ); ?>"
                                           placeholder="llama3.2" style="flex:1;">
                                    <button type="button" id="hs-ollama-load-models" class="button">
                                        ↻ Modelle laden
                                    </button>
                                </div>
                                <span class="description">
                                    Wird als Vorgabe im <code>[handschelle-chat]</code>-Shortcode verwendet, wenn kein <code>model=""</code>-Attribut angegeben ist.
                                </span>
                            </div>
                            <div class="hs-field hs-field-full">
                                <label for="hs_ollama_system_prompt">Standard System-Prompt</label>
                                <textarea id="hs_ollama_system_prompt" name="hs_ollama_system_prompt"
                                          rows="4" maxlength="2000"><?php echo esc_textarea( $system_prompt ); ?></textarea>
                                <span class="description">Systemanweisung an die KI. Wird verwendet, wenn kein <code>system=""</code>-Attribut im Shortcode gesetzt ist.</span>
                            </div>
                        </div>
                    </div>

                    <div class="hs-form-section">
                        <h3>🤖 OpenAI / ChatGPT</h3>
                        <p class="description" style="margin-bottom:1rem;">
                            Trage hier deinen OpenAI API-Key ein, damit der Chat-Widget auch GPT-Modelle nutzen kann.
                            Den Key erhältst du unter <strong>platform.openai.com → API keys</strong>.
                        </p>
                        <div class="hs-form-grid">
                            <div class="hs-field">
                                <label for="hs_openai_api_key">API-Key</label>
                                <?php $has_key = ! empty( get_option( 'hs_openai_api_key', '' ) ); ?>
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <input type="password" id="hs_openai_api_key" name="hs_openai_api_key"
                                           value=""
                                           placeholder="<?php echo $has_key ? '••••••••  (gesetzt – leer lassen zum Beibehalten)' : 'sk-...'; ?>"
                                           autocomplete="new-password" style="flex:1;font-family:monospace;">
                                    <?php if ( $has_key ) : ?>
                                    <label style="white-space:nowrap;font-size:.85rem;display:flex;align-items:center;gap:.3rem;">
                                        <input type="checkbox" name="hs_openai_api_key_clear" value="1"> Key löschen
                                    </label>
                                    <?php endif; ?>
                                </div>
                                <span class="description">
                                    <?php if ( $has_key ) : ?>
                                    ✅ API-Key ist gesetzt. Leer lassen, um ihn beizubehalten.
                                    <?php else : ?>
                                    Noch kein Key gesetzt. GPT-Modelle werden erst nach dem Speichern im Frontend angezeigt.
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="hs-field">
                                <label for="hs_openai_default_model">Standard-Modell</label>
                                <?php
                                $openai_models = array( 'gpt-4.5', 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo', 'o4-mini', 'o3', 'o3-mini', 'o1' );
                                $openai_current = get_option( 'hs_openai_default_model', 'gpt-4o' );
                                ?>
                                <select id="hs_openai_default_model" name="hs_openai_default_model" style="max-width:220px;">
                                    <?php foreach ( $openai_models as $m ) : ?>
                                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $openai_current, $m ); ?>>
                                        <?php echo esc_html( $m ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="description">Vorgabe, wenn OpenAI im Chat-Widget aktiv ist.</span>
                            </div>
                        </div>
                        <?php if ( $has_key ) : ?>
                        <div style="margin-top:.75rem;">
                            <button type="button" id="hs-openai-test-btn" class="button button-secondary">
                                🔌 OpenAI-Verbindung testen
                            </button>
                            <span id="hs-openai-test-result" style="margin-left:.75rem;font-weight:600;"></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="hs-form-section">
                        <h3>🤖 Claude (Anthropic)</h3>
                        <p class="description" style="margin-bottom:1rem;">
                            Trage hier deinen Anthropic API-Key ein, damit der Chat-Widget auch Claude-Modelle nutzen kann.
                            Den Key erhältst du unter <strong>console.anthropic.com → API keys</strong>.
                        </p>
                        <div class="hs-form-grid">
                            <div class="hs-field">
                                <label for="hs_claude_api_key">API-Key</label>
                                <?php $has_claude_key = ! empty( get_option( 'hs_claude_api_key', '' ) ); ?>
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <input type="password" id="hs_claude_api_key" name="hs_claude_api_key"
                                           value=""
                                           placeholder="<?php echo $has_claude_key ? '••••••••  (gesetzt – leer lassen zum Beibehalten)' : 'sk-ant-...'; ?>"
                                           autocomplete="new-password" style="flex:1;font-family:monospace;">
                                    <?php if ( $has_claude_key ) : ?>
                                    <label style="white-space:nowrap;font-size:.85rem;display:flex;align-items:center;gap:.3rem;">
                                        <input type="checkbox" name="hs_claude_api_key_clear" value="1"> Key löschen
                                    </label>
                                    <?php endif; ?>
                                </div>
                                <span class="description">
                                    <?php if ( $has_claude_key ) : ?>
                                    ✅ API-Key ist gesetzt. Leer lassen, um ihn beizubehalten.
                                    <?php else : ?>
                                    Noch kein Key gesetzt. Claude-Modelle werden erst nach dem Speichern im Frontend angezeigt.
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="hs-field">
                                <label for="hs_claude_default_model">Standard-Modell</label>
                                <?php
                                $claude_models  = array( 'claude-opus-4-5', 'claude-sonnet-4-5', 'claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229', 'claude-3-haiku-20240307' );
                                $claude_current = get_option( 'hs_claude_default_model', 'claude-3-5-sonnet-20241022' );
                                ?>
                                <select id="hs_claude_default_model" name="hs_claude_default_model" style="max-width:280px;">
                                    <?php foreach ( $claude_models as $m ) : ?>
                                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $claude_current, $m ); ?>>
                                        <?php echo esc_html( $m ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="description">Vorgabe, wenn Claude im Chat-Widget aktiv ist.</span>
                            </div>
                        </div>
                        <?php if ( $has_claude_key ) : ?>
                        <div style="margin-top:.75rem;">
                            <button type="button" id="hs-claude-test-btn" class="button button-secondary">
                                🔌 Claude-Verbindung testen
                            </button>
                            <span id="hs-claude-test-result" style="margin-left:.75rem;font-weight:600;"></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="hs-form-section">
                        <h3>🤖 Google Gemini</h3>
                        <p class="description" style="margin-bottom:1rem;">
                            Trage hier deinen Google AI API-Key ein, damit der Chat-Widget auch Gemini-Modelle nutzen kann.
                            Den Key erhältst du unter <strong>aistudio.google.com → API keys</strong>.
                        </p>
                        <div class="hs-form-grid">
                            <div class="hs-field">
                                <label for="hs_gemini_api_key">API-Key</label>
                                <?php $has_gemini_key = ! empty( get_option( 'hs_gemini_api_key', '' ) ); ?>
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <input type="password" id="hs_gemini_api_key" name="hs_gemini_api_key"
                                           value=""
                                           placeholder="<?php echo $has_gemini_key ? '••••••••  (gesetzt – leer lassen zum Beibehalten)' : 'AIza…'; ?>"
                                           autocomplete="new-password" style="flex:1;font-family:monospace;">
                                    <?php if ( $has_gemini_key ) : ?>
                                    <label style="white-space:nowrap;font-size:.85rem;display:flex;align-items:center;gap:.3rem;">
                                        <input type="checkbox" name="hs_gemini_api_key_clear" value="1"> Key löschen
                                    </label>
                                    <?php endif; ?>
                                </div>
                                <span class="description">
                                    <?php if ( $has_gemini_key ) : ?>
                                    ✅ API-Key ist gesetzt. Leer lassen, um ihn beizubehalten.
                                    <?php else : ?>
                                    Noch kein Key gesetzt. Gemini-Modelle werden erst nach dem Speichern im Frontend angezeigt.
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="hs-field">
                                <label for="hs_gemini_default_model">Standard-Modell</label>
                                <?php
                                $gemini_models  = array( 'gemini-2.5-pro', 'gemini-2.0-flash', 'gemini-2.0-flash-lite', 'gemini-1.5-pro', 'gemini-1.5-flash' );
                                $gemini_current = get_option( 'hs_gemini_default_model', 'gemini-2.0-flash' );
                                ?>
                                <select id="hs_gemini_default_model" name="hs_gemini_default_model" style="max-width:260px;">
                                    <?php foreach ( $gemini_models as $m ) : ?>
                                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $gemini_current, $m ); ?>>
                                        <?php echo esc_html( $m ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="description">Vorgabe, wenn Gemini im Chat-Widget aktiv ist.</span>
                            </div>
                        </div>
                        <?php if ( $has_gemini_key ) : ?>
                        <div style="margin-top:.75rem;">
                            <button type="button" id="hs-gemini-test-btn" class="button button-secondary">
                                🔌 Gemini-Verbindung testen
                            </button>
                            <span id="hs-gemini-test-result" style="margin-left:.75rem;font-weight:600;"></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="hs-form-section">
                        <h3>🧾 AI-Profil Fragen</h3>
                        <p class="description" style="margin-bottom:1rem;">
                            Diese Fragen werden der Reihe nach gestellt, wenn ein Besucher auf den <strong>🤖 AI-Profil</strong>-Button einer Karte klickt.
                            Nutze die Platzhalter <code>{name}</code>, <code>{partei}</code> und <code>{straftat}</code> – sie werden automatisch ersetzt.
                        </p>
                        <div class="hs-form-grid">
                            <div class="hs-field hs-field-full">
                                <label for="hs_profile_questions">Fragen <span style="font-weight:400;color:#7f8c8d;">(eine pro Zeile)</span></label>
                                <textarea id="hs_profile_questions" name="hs_profile_questions"
                                          rows="6" maxlength="4000"><?php echo esc_textarea( $profile_questions ); ?></textarea>
                                <span class="description">
                                    <strong>Verfügbare Platzhalter:</strong><br>
                                    <code>{name}</code> · <code>{beruf}</code> · <code>{spitzname}</code> · <code>{geburtsort}</code> · <code>{geburtsdatum}</code> · <code>{geburtsland}</code> · <code>{verstorben}</code> · <code>{dod}</code><br>
                                    <code>{partei}</code> · <code>{aufgabe_partei}</code> · <code>{parlament}</code> · <code>{parlament_name}</code> · <code>{status_aktiv}</code><br>
                                    <code>{straftat}</code> · <code>{urteil}</code> · <code>{aktenzeichen}</code> · <code>{status_straftat}</code> · <code>{bemerkung}</code><br>
                                    <br>Beispiel:<br>
                                    <code>Was weißt du über {name} ({partei}, {parlament})?</code><br>
                                    <code>Welche Vergehen ({status_straftat}) werden {name} vorgeworfen?</code>
                                </span>
                            </div>
                            <div class="hs-field hs-field-full">
                                <label for="hs_profile_system_prompt">System-Prompt für AI-Profil</label>
                                <textarea id="hs_profile_system_prompt" name="hs_profile_system_prompt"
                                          rows="3" maxlength="2000"><?php echo esc_textarea( $profile_sys_prompt ); ?></textarea>
                            </div>
                            <div class="hs-field">
                                <label for="hs_profile_provider">Anbieter</label>
                                <select id="hs_profile_provider" name="hs_profile_provider" style="max-width:180px;">
                                    <option value="ollama" <?php selected( $profile_provider, 'ollama' ); ?>>Ollama</option>
                                    <?php if ( ! empty( get_option( 'hs_openai_api_key', '' ) ) ) : ?>
                                    <option value="openai" <?php selected( $profile_provider, 'openai' ); ?>>OpenAI</option>
                                    <?php endif; ?>
                                    <?php if ( ! empty( get_option( 'hs_claude_api_key', '' ) ) ) : ?>
                                    <option value="claude" <?php selected( $profile_provider, 'claude' ); ?>>Claude</option>
                                    <?php endif; ?>
                                    <?php if ( ! empty( get_option( 'hs_gemini_api_key', '' ) ) ) : ?>
                                    <option value="gemini" <?php selected( $profile_provider, 'gemini' ); ?>>Gemini</option>
                                    <?php endif; ?>
                                </select>
                                <span class="description">Welcher LLM-Anbieter für das Profil verwendet wird.</span>
                            </div>
                            <div class="hs-field">
                                <label for="hs_profile_model">Modell</label>
                                <input type="text" id="hs_profile_model" name="hs_profile_model"
                                       value="<?php echo esc_attr( $profile_model ); ?>"
                                       placeholder="z.B. llama3.2 oder gpt-4o">
                                <span class="description">Leer lassen, um das Standard-Modell des gewählten Anbieters zu nutzen.</span>
                            </div>
                        </div>
                    </div>

                    <div class="hs-form-section">
                        <h3>Verbindungstest (Ollama)</h3>
                        <button type="button" id="hs-ollama-test-btn" class="button button-secondary">
                            🔌 Verbindung testen
                        </button>
                        <span id="hs-ollama-test-result" style="margin-left:.75rem;font-weight:600;"></span>
                        <p class="description" style="margin-top:.5rem;">
                            Prüft, ob der Ollama-Server erreichbar ist, und listet verfügbare Modelle auf.
                        </p>
                        <ul id="hs-ollama-model-list" style="margin-top:.5rem;display:none;"></ul>
                    </div>

                    <div class="hs-form-section">
                        <h3>💬 Chat-Test</h3>
                        <p class="description" style="margin-bottom:1rem;">
                            Sende eine Testfrage direkt an ein KI-Modell, um die Konfiguration zu prüfen.
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end;margin-bottom:.75rem;">
                            <div>
                                <label for="hs-chattest-provider" style="display:block;font-weight:600;margin-bottom:.2rem;">Anbieter</label>
                                <select id="hs-chattest-provider" style="min-width:120px;">
                                    <option value="ollama">Ollama</option>
                                    <?php if ( ! empty( get_option( 'hs_openai_api_key', '' ) ) ) : ?>
                                    <option value="openai">OpenAI</option>
                                    <?php endif; ?>
                                    <?php if ( ! empty( get_option( 'hs_claude_api_key', '' ) ) ) : ?>
                                    <option value="claude">Claude</option>
                                    <?php endif; ?>
                                    <?php if ( ! empty( get_option( 'hs_gemini_api_key', '' ) ) ) : ?>
                                    <option value="gemini">Gemini</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label for="hs-chattest-model" style="display:block;font-weight:600;margin-bottom:.2rem;">Modell</label>
                                <select id="hs-chattest-model" style="min-width:220px;">
                                    <option value="">— Lade …</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;gap:.6rem;align-items:flex-start;">
                            <input type="text" id="hs-chattest-question"
                                   value="Antworte in einem Satz: Was ist 2+2?"
                                   style="flex:1;max-width:480px;"
                                   placeholder="Testfrage eingeben …">
                            <button type="button" id="hs-chattest-send" class="button button-primary">
                                ▶ Senden
                            </button>
                        </div>
                        <div id="hs-chattest-result" style="margin-top:.75rem;display:none;border:1px solid #c3c4c7;border-radius:4px;padding:.75rem 1rem;background:#f9f9f9;">
                            <div id="hs-chattest-meta" style="font-size:.8rem;color:#646970;margin-bottom:.4rem;"></div>
                            <div id="hs-chattest-reply" style="white-space:pre-wrap;word-break:break-word;"></div>
                        </div>
                    </div>

                    <div class="hs-form-section">
                        <h3>Shortcode-Referenz</h3>
                        <p>Füge den Chatbot mit folgendem Shortcode in eine Seite ein:</p>
                        <code>[handschelle-chat]</code>
                        <p style="margin-top:.75rem;">Alle verfügbaren Attribute:</p>
                        <table class="widefat striped" style="max-width:640px;">
                            <thead><tr><th>Attribut</th><th>Standard</th><th>Beschreibung</th></tr></thead>
                            <tbody>
                                <tr><td><code>model</code></td><td><em>Standard-Modell (s.o.)</em></td><td>Ollama-Modellname</td></tr>
                                <tr><td><code>title</code></td><td><code>KI-Assistent</code></td><td>Titel im Chat-Header</td></tr>
                                <tr><td><code>system</code></td><td><em>Standard-Prompt (s.o.)</em></td><td>Systemanweisung an die KI</td></tr>
                                <tr><td><code>placeholder</code></td><td><code>Schreibe eine Nachricht …</code></td><td>Platzhalter im Eingabefeld</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary">💾 Einstellungen speichern</button>
                </p>
            </form>
            <?php echo $this->hs_footer(); ?>
        </div>

        <script>
        (function($){
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var nonce   = '<?php echo esc_js( wp_create_nonce( 'hs_chat_nonce' ) ); ?>';

            function fetchModels(onSuccess) {
                return $.post(ajaxUrl, { action: 'hs_chat_models', _nonce: nonce })
                    .done(function(res) {
                        if (res.success && res.data && res.data.models) {
                            onSuccess(res.data.models);
                        } else {
                            onSuccess(null);
                        }
                    })
                    .fail(function() { onSuccess(null); });
            }

            $('#hs-ollama-test-btn').on('click', function() {
                var $result = $('#hs-ollama-test-result');
                var $list   = $('#hs-ollama-model-list');
                $result.text('Teste …').css('color', '#7f8c8d');
                $list.hide().empty();

                fetchModels(function(models) {
                    if (models) {
                        $result.text('✅ Verbunden – ' + models.length + ' Modell(e) gefunden.').css('color', '#27ae60');
                        $.each(models, function(_, m) {
                            var label = m.size ? m.name + ' — ' + m.size : m.name;
                            $list.append('<li style="font-family:monospace;">' + $('<span>').text(label).html() + '</li>');
                        });
                        $list.show();
                    } else {
                        $result.text('❌ Verbindung fehlgeschlagen.').css('color', '#c0392b');
                    }
                });
            });

            $('#hs-openai-test-btn').on('click', function() {
                var $result = $('#hs-openai-test-result');
                $result.text('Teste …').css('color', '#7f8c8d');
                $.post(ajaxUrl, { action: 'hs_chat_openai_models', _nonce: nonce })
                    .done(function(res) {
                        if (res.success && res.data && res.data.models) {
                            $result.text('✅ API-Key gültig – ' + res.data.models.length + ' Modelle verfügbar.').css('color', '#27ae60');
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : 'Fehler.';
                            $result.text('❌ ' + msg).css('color', '#c0392b');
                        }
                    })
                    .fail(function() { $result.text('❌ Anfrage fehlgeschlagen.').css('color', '#c0392b'); });
            });

            $('#hs-claude-test-btn').on('click', function() {
                var $result = $('#hs-claude-test-result');
                $result.text('Teste …').css('color', '#7f8c8d');
                $.post(ajaxUrl, { action: 'hs_chat_claude_models', _nonce: nonce })
                    .done(function(res) {
                        if (res.success && res.data && res.data.models) {
                            $result.text('✅ API-Key gültig – ' + res.data.models.length + ' Modelle verfügbar.').css('color', '#27ae60');
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : 'Fehler.';
                            $result.text('❌ ' + msg).css('color', '#c0392b');
                        }
                    })
                    .fail(function() { $result.text('❌ Anfrage fehlgeschlagen.').css('color', '#c0392b'); });
            });

            $('#hs-gemini-test-btn').on('click', function() {
                var $result = $('#hs-gemini-test-result');
                $result.text('Teste …').css('color', '#7f8c8d');
                $.post(ajaxUrl, { action: 'hs_chat_gemini_models', _nonce: nonce })
                    .done(function(res) {
                        if (res.success && res.data && res.data.models) {
                            $result.text('✅ API-Key gültig – ' + res.data.models.length + ' Modelle verfügbar.').css('color', '#27ae60');
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : 'Fehler.';
                            $result.text('❌ ' + msg).css('color', '#c0392b');
                        }
                    })
                    .fail(function() { $result.text('❌ Anfrage fehlgeschlagen.').css('color', '#c0392b'); });
            });

            // ── Chat-Test ─────────────────────────────────────────────
            var chatTestProviderActions = {
                ollama: { models: 'hs_chat_models',         chat: 'hs_chat'         },
                openai: { models: 'hs_chat_openai_models',  chat: 'hs_chat_openai'  },
                claude: { models: 'hs_chat_claude_models',  chat: 'hs_chat_claude'  },
                gemini: { models: 'hs_chat_gemini_models',  chat: 'hs_chat_gemini'  }
            };

            function loadChatTestModels(provider) {
                var $sel = $('#hs-chattest-model');
                var pa   = chatTestProviderActions[provider];
                if (!pa) return;
                $sel.prop('disabled', true).empty().append('<option value="">Lade …</option>');
                $.post(ajaxUrl, { action: pa.models, _nonce: nonce })
                    .done(function(res) {
                        $sel.empty();
                        if (res.success && res.data && res.data.models && res.data.models.length) {
                            $.each(res.data.models, function(_, m) {
                                var label = m.size ? m.name + ' — ' + m.size : m.name;
                                $sel.append('<option value="' + $('<span>').text(m.name).html() + '">' + $('<span>').text(label).html() + '</option>');
                            });
                        } else {
                            $sel.append('<option value="">— Keine Modelle gefunden —</option>');
                        }
                    })
                    .fail(function() {
                        $sel.empty().append('<option value="">— Fehler beim Laden —</option>');
                    })
                    .always(function() { $sel.prop('disabled', false); });
            }

            $('#hs-chattest-provider').on('change', function() {
                loadChatTestModels($(this).val());
            });
            loadChatTestModels($('#hs-chattest-provider').val());

            $('#hs-chattest-send').on('click', function() {
                var provider = $('#hs-chattest-provider').val();
                var model    = $('#hs-chattest-model').val();
                var question = $('#hs-chattest-question').val().trim();
                var $btn     = $(this);
                var $result  = $('#hs-chattest-result');
                var $meta    = $('#hs-chattest-meta');
                var $reply   = $('#hs-chattest-reply');

                if (!model || !question) return;

                var pa = chatTestProviderActions[provider];
                if (!pa) return;

                $btn.prop('disabled', true).text('Sende …');
                $result.show();
                $meta.text('');
                $reply.text('⏳ Warte auf Antwort …');

                $.post(ajaxUrl, {
                    action      : pa.chat,
                    _nonce      : nonce,
                    message     : question,
                    model       : model,
                    system      : '',
                    temperature : 0.7,
                    history     : '[]'
                })
                .done(function(res) {
                    if (res.success && res.data && res.data.reply) {
                        var d = res.data;
                        var parts = [];
                        if (d.model)    parts.push(d.model);
                        if (d.time_s)   parts.push(d.time_s + 's');
                        if (d.toks_sec) parts.push(d.toks_sec + ' tok/s');
                        $meta.text(parts.join(' · '));
                        $reply.text(d.reply);
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                        $meta.text('');
                        $reply.text('❌ ' + msg);
                    }
                })
                .fail(function() {
                    $meta.text('');
                    $reply.text('❌ Verbindungsfehler.');
                })
                .always(function() { $btn.prop('disabled', false).text('▶ Senden'); });
            });
            // ── /Chat-Test ────────────────────────────────────────────

            $('#hs-ollama-load-models').on('click', function() {
                var $btn   = $(this);
                var $input = $('#hs_ollama_default_model');
                $btn.prop('disabled', true).text('Lade …');

                fetchModels(function(models) {
                    $btn.prop('disabled', false).text('↻ Modelle laden');
                    if (!models || !models.length) {
                        alert('Keine Modelle gefunden – ist Ollama aktiv?');
                        return;
                    }
                    // Build a quick picker
                    var $picker = $('<select style="margin-left:.5rem;max-width:220px;">');
                    $picker.append('<option value="">— Modell wählen —</option>');
                    $.each(models, function(_, m) {
                        var label = m.size ? m.name + ' — ' + m.size : m.name;
                        var sel   = (m.name === $input.val()) ? ' selected' : '';
                        $picker.append('<option value="' + $('<span>').text(m.name).html() + '"' + sel + '>' + $('<span>').text(label).html() + '</option>');
                    });
                    $picker.on('change', function() {
                        if ($(this).val()) {
                            $input.val($(this).val());
                            $picker.remove();
                        }
                    });
                    $input.after($picker);
                    $picker.focus();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    public function page_llm_status() {
        $nonce = wp_create_nonce( 'hs_chat_nonce' );

        $ollama_url     = get_option( 'hs_ollama_url', 'http://localhost:11434' );
        $ollama_mode    = get_option( 'hs_ollama_mode', 'local' );
        $ollama_model   = get_option( 'hs_ollama_default_model', '' ) ?: '—';
        $openai_model   = get_option( 'hs_openai_default_model', 'gpt-4o' );
        $claude_model   = get_option( 'hs_claude_default_model', 'claude-3-5-sonnet-20241022' );
        $gemini_model   = get_option( 'hs_gemini_default_model', 'gemini-2.0-flash' );
        $has_openai     = ! empty( get_option( 'hs_openai_api_key', '' ) );
        $has_claude     = ! empty( get_option( 'hs_claude_api_key', '' ) );
        $has_gemini     = ! empty( get_option( 'hs_gemini_api_key', '' ) );

        $providers = array(
            array(
                'id'         => 'ollama',
                'provider'   => 'Ollama',
                'name'       => $ollama_model,
                'url'        => $ollama_url,
                'configured' => true,
                'ajax'       => 'hs_chat_models',
            ),
            array(
                'id'         => 'openai',
                'provider'   => 'OpenAI',
                'name'       => $openai_model,
                'url'        => 'api.openai.com',
                'configured' => $has_openai,
                'ajax'       => 'hs_chat_openai_models',
            ),
            array(
                'id'         => 'claude',
                'provider'   => 'Anthropic (Claude)',
                'name'       => $claude_model,
                'url'        => 'api.anthropic.com',
                'configured' => $has_claude,
                'ajax'       => 'hs_chat_claude_models',
            ),
            array(
                'id'         => 'gemini',
                'provider'   => 'Google (Gemini)',
                'name'       => $gemini_model,
                'url'        => 'generativelanguage.googleapis.com',
                'configured' => $has_gemini,
                'ajax'       => 'hs_chat_gemini_models',
            ),
        );
        ?>
        <div class="wrap hs-wrap">
            <h1>🔌 LLM Status</h1>
            <p class="description" style="margin-bottom:1.5rem;">
                Übersicht aller konfigurierten KI-Anbieter und deren Verbindungsstatus.
            </p>

            <table class="widefat striped" style="max-width:860px;">
                <thead>
                    <tr>
                        <th>LLM Provider</th>
                        <th>LLM Name</th>
                        <th>LLM Version</th>
                        <th>Konfiguriert</th>
                        <th>Test</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $providers as $p ) :
                    // Split name into model-family and version
                    $model_name    = $p['name'];
                    $model_version = '—';
                    if ( preg_match( '/[:\-\/](\d[\d.\w\-]*)/', $model_name, $m ) ) {
                        $model_version = $m[1];
                    } elseif ( preg_match( '/(\d[\d.]+)/', $model_name, $m ) ) {
                        $model_version = $m[1];
                    }
                ?>
                    <tr id="hs-llm-row-<?php echo esc_attr( $p['id'] ); ?>">
                        <td><strong><?php echo esc_html( $p['provider'] ); ?></strong></td>
                        <td style="font-family:monospace;"><?php echo esc_html( $model_name ); ?></td>
                        <td style="font-family:monospace;"><?php echo esc_html( $model_version ); ?></td>
                        <td>
                            <?php if ( $p['configured'] ) : ?>
                                <span style="color:#27ae60;font-weight:600;">✅ Ja</span>
                            <?php else : ?>
                                <span style="color:#c0392b;font-weight:600;">❌ Nein</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $p['configured'] ) : ?>
                            <button type="button"
                                    class="button button-secondary hs-llm-test-btn"
                                    data-provider="<?php echo esc_attr( $p['id'] ); ?>"
                                    data-ajax="<?php echo esc_attr( $p['ajax'] ); ?>">
                                🔌 Testen
                            </button>
                            <?php else : ?>
                            <span style="color:#999;font-size:.85rem;">Kein API-Key</span>
                            <?php endif; ?>
                        </td>
                        <td class="hs-llm-status-cell" id="hs-llm-status-<?php echo esc_attr( $p['id'] ); ?>">
                            —
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:1.5rem;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=handschelle-ollama' ) ); ?>" class="button button-secondary">
                    ⚙️ LLM-Einstellungen bearbeiten
                </a>
            </p>

            <?php echo $this->hs_footer(); ?>
        </div>

        <script>
        (function($){
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var nonce   = '<?php echo esc_js( $nonce ); ?>';

            $('.hs-llm-test-btn').on('click', function() {
                var $btn      = $(this);
                var provider  = $btn.data('provider');
                var action    = $btn.data('ajax');
                var $status   = $('#hs-llm-status-' + provider);

                $btn.prop('disabled', true).text('Teste …');
                $status.html('<span style="color:#7f8c8d;">Verbinde …</span>');

                $.post(ajaxUrl, { action: action, _nonce: nonce })
                    .done(function(res) {
                        if (res.success && res.data && res.data.models) {
                            var count = res.data.models.length;
                            $status.html('<span style="color:#27ae60;font-weight:600;">✅ Verbunden – ' + count + ' Modell(e)</span>');
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : 'Verbindung fehlgeschlagen';
                            $status.html('<span style="color:#c0392b;font-weight:600;">❌ ' + $('<span>').text(msg).html() + '</span>');
                        }
                    })
                    .fail(function() {
                        $status.html('<span style="color:#c0392b;font-weight:600;">❌ Anfrage fehlgeschlagen</span>');
                    })
                    .always(function() {
                        $btn.prop('disabled', false).text('🔌 Testen');
                    });
            });
        })(jQuery);
        </script>
        <?php
    }

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
