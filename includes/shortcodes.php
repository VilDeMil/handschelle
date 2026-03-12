<?php
/**
 * Die-Handschelle 2.0 A – Shortcodes
 *
 * Shortcodes:
 *   [handschelle]            – Eingabeformular
 *   [handschelle-anzeige]    – Alle freigegebenen Einträge
 *   [handschelle-suche]      – Beide Dropdowns
 *   [handschelle-partei]     – Dropdown nach Partei
 *   [handschelle-name]       – Dropdown nach Name
 *   [handschelle-statistik]  – Einträge je Partei (Tabelle + Balken)
 *   [handschelle-disclaimer] – Copyright-Hinweis
 *
 * Submit-Handler läuft auf dem 'init'-Hook (VOR jeder HTTP-Ausgabe),
 * damit wp_safe_redirect() zuverlässig funktioniert.
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

class Handschelle_Shortcodes {

    public function __construct() {
        add_shortcode( 'handschelle',            array( $this, 'sc_eingabe' ) );
        add_shortcode( 'handschelle-anzeige',    array( $this, 'sc_anzeige' ) );
        add_shortcode( 'handschelle-suche',      array( $this, 'sc_suche' ) );
        add_shortcode( 'handschelle-partei',     array( $this, 'sc_partei' ) );
        add_shortcode( 'handschelle-name',       array( $this, 'sc_name' ) );
        add_shortcode( 'hndschelle-name',        array( $this, 'sc_name' ) ); // Typo-Alias
        add_shortcode( 'handschelle-statistik',       array( $this, 'sc_statistik' ) );
        add_shortcode( 'handschelle-statistik-partei', array( $this, 'sc_statistik_partei' ) );
        add_shortcode( 'handschelle-statistik-name',   array( $this, 'sc_statistik_name' ) );
        add_shortcode( 'handschelle-name-anzeige',     array( $this, 'sc_name_anzeige' ) );
        add_shortcode( 'handschelle-name-partei',      array( $this, 'sc_name_partei' ) );
        add_shortcode( 'handschelle-disclaimer',       array( $this, 'sc_disclaimer' ) );

        // Submit früh verarbeiten – BEVOR Header gesendet werden
        add_action( 'init', array( $this, 'early_frontend_submit' ) );
        add_action( 'init', array( $this, 'early_frontend_edit' ) );
    }

    /* ================================================================
       [handschelle] – Eingabeformular
    ================================================================ */
    public function sc_eingabe( $atts ) {
        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-eingabe-form">
                <h2 class="hs-section-title">📝 Neuen Eintrag melden</h2>

                <?php if ( ! empty( $_GET['hs_success'] ) ) : ?>
                    <div class="hs-alert hs-alert-success">✅ Danke! Eintrag empfangen!</div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="hs-form" id="hs-eingabe-form">
                    <?php wp_nonce_field( 'hs_frontend_submit', 'hs_nonce' ); ?>
                    <input type="hidden" name="hs_submit"     value="1">
                    <input type="hidden" name="hs_return_url" value="<?php echo esc_url( get_permalink() ); ?>">

                    <!-- ── Eintragsdetails ──────────────────── -->
                    <div class="hs-form-section">
                        <h3>📋 Eintragsdetails</h3>
                        <div class="hs-form-grid">
                            <div class="hs-field"><label>Datum</label><input type="date" name="datum_eintrag" value="<?php echo esc_attr( date('Y-m-d') ); ?>" required></div>
                            <div class="hs-field"><label>Name <span>(max. 50 Zeichen)</span></label><input type="text" name="name" maxlength="50" required placeholder="Vor- und Nachname"></div>
                            <div class="hs-field"><label>Beruf <span>(max. 50 Zeichen)</span></label><input type="text" name="beruf" maxlength="50" placeholder="z.B. Politiker, Unternehmer"></div>
                            <div class="hs-field hs-field-full">
                                <label>Bild hochladen</label>
                                <input type="file" name="bild_upload" accept="image/*" class="hs-file-input">
                                <div class="hs-file-preview" id="hs-file-preview"></div>
                                <small>Wird automatisch auf max. 450 px Höhe skaliert.</small>
                            </div>
                        </div>
                    </div>

                    <!-- ── Politisch ────────────────────────── -->
                    <div class="hs-form-section">
                        <h3>🏛 Politisch</h3>
                        <div class="hs-form-grid">
                            <div class="hs-field"><label>Partei <span>(max. 50 Zeichen)</span></label><input type="text" name="partei" maxlength="50" placeholder="z.B. CDU, SPD, Grüne …"></div>
                            <div class="hs-field"><label>Aufgabe in der Partei</label><input type="text" name="aufgabe_partei" maxlength="100" placeholder="z.B. Vorsitzender, MdB …"></div>
                            <div class="hs-field">
                                <label>Parlament</label>
                                <select name="parlament">
                                    <option value="">-- Bitte wählen --</option>
                                    <?php foreach ( handschelle_parlaments() as $p ) : ?>
                                        <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hs-field"><label>Parlament Name <span>(max. 50 Zeichen)</span></label><input type="text" name="parlament_name" maxlength="50" placeholder="z.B. Wahlkreis München"></div>
                            <div class="hs-field"><label>Status</label><select name="status_aktiv"><option value="1">Aktiv</option><option value="0">Inaktiv</option></select></div>
                        </div>
                    </div>

                    <!-- ── Straftat ──────────────────────────── -->
                    <div class="hs-form-section">
                        <h3>⚖ Details zur Straftat</h3>
                        <div class="hs-form-grid">
                            <div class="hs-field hs-field-full">
                                <label>Straftat <span>(max. 200 Zeichen)</span></label>
                                <textarea name="straftat" maxlength="200" rows="3" placeholder="Kurze Beschreibung der Straftat …" required></textarea>
                                <small class="hs-char-counter" data-target="straftat">0 / 200 Zeichen</small>
                            </div>
                            <div class="hs-field"><label>Urteil <span>(max. 50 Zeichen)</span></label><input type="text" name="urteil" maxlength="50" placeholder="z.B. 2 Jahre auf Bewährung"></div>
                            <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="link_quelle" placeholder="https://…"></div>
                            <div class="hs-field"><label>Aktenzeichen <span>(max. 50 Zeichen)</span></label><input type="text" name="aktenzeichen" maxlength="50" placeholder="z.B. 1 StR 123/24"></div>
                            <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="bemerkung" rows="4" placeholder="Weitere Anmerkungen …"></textarea></div>
                            <div class="hs-field">
                                <label>Status der Straftat</label>
                                <select name="status_straftat">
                                    <?php foreach ( handschelle_status_straftat_options() as $s ) : ?>
                                        <option value="<?php echo esc_attr($s); ?>"><?php echo esc_html($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ── Social Media ──────────────────────── -->
                    <div class="hs-form-section">
                        <h3>📱 Social-Media Links</h3>
                        <div class="hs-form-grid">
                            <?php foreach ( array( 'sm_facebook'=>'📘 Facebook','sm_youtube'=>'▶ YouTube','sm_personal'=>'👤 Persönliches Profil','sm_twitter'=>'🐦 Twitter / X','sm_homepage'=>'🌐 Persönliche Homepage','sm_wikipedia'=>'📖 Wikipedia','sm_sonstige'=>'🔗 Sonstige' ) as $field => $label ) : ?>
                                <div class="hs-field"><label><?php echo $label; ?></label><input type="url" name="<?php echo esc_attr($field); ?>" placeholder="https://…"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ── Submit ────────────────────────────── -->
                    <div class="hs-form-actions">
                        <button type="submit" class="hs-btn hs-btn-primary">📨 Eintrag einreichen</button>
                        <p class="hs-note">* Einreichungen werden vor der Veröffentlichung geprüft.</p>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       SUBMIT-HANDLER auf init-Hook (vor allen HTTP-Headern)
    ================================================================ */
    public function early_frontend_submit() {
        if ( empty( $_POST['hs_submit'] ) ) return;

        if ( ! isset( $_POST['hs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hs_nonce'] ) ), 'hs_frontend_submit' ) ) {
            wp_safe_redirect( $this->return_url() );
            exit;
        }

        $data      = handschelle_sanitize_entry( $_POST );
        $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload' );
        if ( $attach_id ) $data['bild'] = $attach_id;
        Handschelle_Database::insert( $data );

        // PRG-Redirect – verhindert Doppel-Submit bei F5
        wp_safe_redirect( add_query_arg( 'hs_success', '1', $this->return_url() ) );
        exit;
    }

    private function return_url() {
        if ( ! empty( $_POST['hs_return_url'] ) ) {
            $url = esc_url_raw( wp_unslash( $_POST['hs_return_url'] ) );
            if ( strpos( $url, home_url() ) === 0 ) return $url;
        }
        return home_url();
    }

    /* ================================================================
       FRONTEND-EDIT-HANDLER auf init-Hook
    ================================================================ */
    public function early_frontend_edit() {
        if ( empty( $_POST['hs_edit_submit'] ) ) return;
        if ( ! is_user_logged_in() ) return;

        if ( ! isset( $_POST['hs_edit_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hs_edit_nonce'] ) ), 'hs_frontend_edit' ) ) {
            wp_safe_redirect( $this->return_url() );
            exit;
        }

        $id = intval( $_POST['hs_edit_id'] ?? 0 );
        if ( ! $id ) {
            wp_safe_redirect( $this->return_url() );
            exit;
        }

        $data      = handschelle_sanitize_entry( $_POST );
        $attach_id = Handschelle_Image_Handler::handle_upload_and_resize( 'bild_upload' );
        if ( $attach_id ) $data['bild'] = $attach_id;

        // Freigabe-Status nur für Admins änderbar
        if ( current_user_can( 'manage_options' ) ) {
            $data['freigegeben'] = isset( $_POST['freigegeben'] ) ? 1 : 0;
        } else {
            unset( $data['freigegeben'] );
        }

        Handschelle_Database::update( $id, $data );

        wp_safe_redirect( add_query_arg( 'hs_edited', $id, $this->return_url() ) );
        exit;
    }

    /* ================================================================
       [handschelle-anzeige] – Alle freigegebenen Einträge
    ================================================================ */
    public function sc_anzeige( $atts ) {
        $atts = shortcode_atts( array( 'partei' => '', 'name' => '' ), $atts );
        $args = array( 'freigegeben' => 1 );
        if ( ! empty( $atts['partei'] ) ) $args['partei'] = sanitize_text_field( $atts['partei'] );
        if ( ! empty( $atts['name'] ) )   $args['name']   = sanitize_text_field( $atts['name'] );
        $entries = Handschelle_Database::get_all( $args );
        ob_start();
        echo '<div class="hs-frontend hs-full-width">';
        echo '<h2 class="hs-section-title">📋 Einträge</h2>';
        if ( empty( $entries ) ) {
            echo '<p class="hs-empty">Keine Einträge vorhanden.</p>';
        } else {
            echo '<div class="hs-cards-grid">';
            foreach ( $entries as $e ) echo $this->render_card( $e );
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-suche] – Beide Dropdowns
    ================================================================ */
    public function sc_suche( $atts ) {
        ob_start();
        echo '<div class="hs-frontend hs-full-width">';
        echo $this->render_partei_dropdown();
        echo $this->render_name_dropdown();
        echo '</div>';
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-partei] – Partei-Dropdown
    ================================================================ */
    public function sc_partei( $atts ) {
        ob_start();
        echo '<div class="hs-frontend hs-full-width">';
        echo $this->render_partei_dropdown();
        echo '</div>';
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-name] – Personen-Dropdown
    ================================================================ */
    public function sc_name( $atts ) {
        ob_start();
        echo '<div class="hs-frontend hs-full-width">';
        echo $this->render_name_dropdown();
        echo '</div>';
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-statistik] – Einträge je Partei
    ================================================================ */
    public function sc_statistik( $atts ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $rows  = $wpdb->get_results(
            "SELECT partei, COUNT(*) AS anzahl FROM `{$table}`
             WHERE freigegeben = 1 AND partei != ''
             GROUP BY partei ORDER BY anzahl DESC, partei ASC"
        );
        $total = 0;
        foreach ( $rows as $r ) $total += intval( $r->anzahl );

        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-statistik">
                <h2 class="hs-section-title">📊 Einträge je Partei</h2>
                <?php if ( empty( $rows ) ) : ?>
                    <p class="hs-empty">Noch keine freigegebenen Einträge vorhanden.</p>
                <?php else : ?>
                    <div class="hs-stat-total">Gesamt freigegebene Einträge: <strong><?php echo intval($total); ?></strong></div>
                    <div class="hs-stat-table-wrap">
                        <table class="hs-stat-table">
                            <thead><tr><th>#</th><th>Partei</th><th>Einträge</th><th>Anteil</th><th>Balken</th></tr></thead>
                            <tbody>
                            <?php foreach ( $rows as $i => $r ) :
                                $anzahl  = intval( $r->anzahl );
                                $pct     = $total > 0 ? round( $anzahl / $total * 100, 1 ) : 0;
                                $bar_pct = $total > 0 ? round( $anzahl / $rows[0]->anzahl * 100, 1 ) : 0;
                            ?>
                                <tr>
                                    <td class="hs-stat-rank"><?php echo $i + 1; ?></td>
                                    <td class="hs-stat-partei"><a href="<?php echo esc_url( add_query_arg( 'hs_partei', urlencode( $r->partei ), get_permalink() ) ); ?>" class="hs-stat-partei-link"><?php echo esc_html( $r->partei ); ?></a></td>
                                    <td class="hs-stat-count"><?php echo $anzahl; ?></td>
                                    <td class="hs-stat-pct"><?php echo $pct; ?>&nbsp;%</td>
                                    <td class="hs-stat-bar-cell"><div class="hs-stat-bar-wrap"><div class="hs-stat-bar" style="width:<?php echo $bar_pct; ?>%"></div></div></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr><td colspan="2"><strong>Gesamt</strong></td><td><strong><?php echo intval($total); ?></strong></td><td><strong>100&nbsp;%</strong></td><td></td></tr></tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-name-anzeige] – Name-Dropdown + Einträge (leer am Anfang)
    ================================================================ */
    public function sc_name_anzeige( $atts ) {
        $namen    = Handschelle_Database::get_distinct_namen();
        $selected = sanitize_text_field( wp_unslash( $_GET['hs_name_anzeige'] ?? '' ) );
        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-search-box">
                <form method="get" class="hs-search-form">
                    <?php $this->preserve_page_param(); ?>
                    <select name="hs_name_anzeige" class="hs-select" onchange="this.form.submit()">
                        <option value="">-- Person auswählen --</option>
                        <?php foreach ( $namen as $n ) : ?>
                            <option value="<?php echo esc_attr($n); ?>" <?php selected($selected,$n); ?>><?php echo esc_html($n); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="hs-btn">Suchen</button></noscript>
                </form>
                <?php if ( ! empty( $selected ) ) :
                    $entries = Handschelle_Database::get_all( array( 'freigegeben' => 1, 'name' => $selected ) );
                ?>
                    <div class="hs-search-results">
                        <?php if ( empty($entries) ) : ?>
                            <p class="hs-empty">Keine Einträge für diese Person.</p>
                        <?php else : ?>
                            <div class="hs-cards-grid"><?php foreach ( $entries as $e ) echo $this->render_card($e); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-name-partei] – Partei-Dropdown + Einträge (leer am Anfang)
    ================================================================ */
    public function sc_name_partei( $atts ) {
        $parteien = Handschelle_Database::get_distinct_parteien();
        $selected = sanitize_text_field( wp_unslash( $_GET['hs_name_partei'] ?? '' ) );
        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-search-box">
                <form method="get" class="hs-search-form">
                    <?php $this->preserve_page_param(); ?>
                    <select name="hs_name_partei" class="hs-select" onchange="this.form.submit()">
                        <option value="">-- Partei auswählen --</option>
                        <?php foreach ( $parteien as $p ) : ?>
                            <option value="<?php echo esc_attr($p); ?>" <?php selected($selected,$p); ?>><?php echo esc_html($p); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="hs-btn">Suchen</button></noscript>
                </form>
                <?php if ( ! empty( $selected ) ) :
                    $entries = Handschelle_Database::get_all( array( 'freigegeben' => 1, 'partei' => $selected ) );
                ?>
                    <div class="hs-search-results">
                        <?php if ( empty($entries) ) : ?>
                            <p class="hs-empty">Keine Einträge für diese Partei.</p>
                        <?php else : ?>
                            <div class="hs-cards-grid"><?php foreach ( $entries as $e ) echo $this->render_card($e); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-statistik-partei] – Partei / Anzahl Einträge
    ================================================================ */
    public function sc_statistik_partei( $atts ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $rows  = $wpdb->get_results(
            "SELECT partei, COUNT(*) AS anzahl FROM `{$table}`
             WHERE freigegeben = 1 AND partei != ''
             GROUP BY partei ORDER BY anzahl DESC, partei ASC"
        );
        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-statistik">
                <h2 class="hs-section-title">Wie viele Straftäter je Partei gibt es?</h2>
                <?php if ( empty( $rows ) ) : ?>
                    <p class="hs-empty">Noch keine freigegebenen Einträge vorhanden.</p>
                <?php else : ?>
                    <div class="hs-stat-table-wrap">
                        <table class="hs-stat-table">
                            <thead><tr><th>Partei</th><th>Anzahl Einträge</th></tr></thead>
                            <tbody>
                            <?php foreach ( $rows as $r ) : ?>
                                <tr>
                                    <td class="hs-stat-partei"><a href="<?php echo esc_url( add_query_arg( 'hs_name_partei', urlencode( $r->partei ), get_permalink() ) ); ?>" class="hs-stat-partei-link"><?php echo esc_html( $r->partei ); ?></a></td>
                                    <td class="hs-stat-count"><?php echo intval( $r->anzahl ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-statistik-name] – Name / Anzahl Einträge
    ================================================================ */
    public function sc_statistik_name( $atts ) {
        global $wpdb;
        $table = $wpdb->prefix . HANDSCHELLE_DB_TABLE;
        $rows  = $wpdb->get_results(
            "SELECT name, COUNT(*) AS anzahl FROM `{$table}`
             WHERE freigegeben = 1 AND name != ''
             GROUP BY name ORDER BY anzahl DESC, name ASC"
        );
        ob_start();
        ?>
        <div class="hs-frontend hs-full-width">
            <div class="hs-statistik">
                <h2 class="hs-section-title">Wer hat bereits einen Eintrag?</h2>
                <?php if ( empty( $rows ) ) : ?>
                    <p class="hs-empty">Noch keine freigegebenen Einträge vorhanden.</p>
                <?php else : ?>
                    <div class="hs-stat-table-wrap">
                        <table class="hs-stat-table">
                            <thead><tr><th>Name</th><th>Anzahl Einträge</th></tr></thead>
                            <tbody>
                            <?php foreach ( $rows as $r ) : ?>
                                <tr>
                                    <td class="hs-stat-partei"><?php echo esc_html( $r->name ); ?></td>
                                    <td class="hs-stat-count"><?php echo intval( $r->anzahl ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       [handschelle-disclaimer] – Copyright-Hinweis
    ================================================================ */
    public function sc_disclaimer( $atts ) {
        ob_start();
        ?>
        <div class="hs-disclaimer">
            <p class="hs-disclaimer-title">Die-Handschelle &copy; 2026</p>
            <p class="hs-disclaimer-tagline">Wer in unseren Parlamenten ist oder war kriminell?<br>Eine Datenbank der Straftaten.</p>
            <p class="hs-disclaimer-links">
                <a href="mailto:bernd@xn--dorfmller-u9a.com" class="hs-disclaimer-link">bernd@xn--dorfmller-u9a.com</a>
                &nbsp;&middot;&nbsp;
                <a href="https://xn--dorfmller-u9a.com/die-handschelle" target="_blank" rel="noopener noreferrer" class="hs-disclaimer-link">xn--dorfmller-u9a.com/die-handschelle</a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       PARTEI-DROPDOWN (intern)
    ================================================================ */
    private function render_partei_dropdown() {
        $parteien = Handschelle_Database::get_distinct_parteien();
        $selected = sanitize_text_field( wp_unslash( $_GET['hs_partei'] ?? '' ) );
        ob_start();
        ?>
        <div class="hs-search-box">
            <h3 class="hs-search-title">🏛 Nach Partei suchen</h3>
            <form method="get" class="hs-search-form">
                <?php $this->preserve_page_param(); ?>
                <select name="hs_partei" class="hs-select" onchange="this.form.submit()">
                    <option value="">-- Partei auswählen --</option>
                    <?php foreach ( $parteien as $p ) : ?>
                        <option value="<?php echo esc_attr($p); ?>" <?php selected($selected,$p); ?>><?php echo esc_html($p); ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="hs-btn">Suchen</button></noscript>
            </form>
            <?php if ( ! empty( $selected ) ) :
                $entries = Handschelle_Database::get_all( array( 'partei' => $selected ) );
            ?>
                <div class="hs-search-results">
                    <h4>Einträge für Partei: <em><?php echo esc_html($selected); ?></em> <span class="hs-count">(<?php echo count($entries); ?>)</span></h4>
                    <?php if ( empty($entries) ) : ?>
                        <p class="hs-empty">Keine Einträge für diese Partei.</p>
                    <?php else : ?>
                        <div class="hs-cards-grid"><?php foreach ( $entries as $e ) echo $this->render_card($e); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       PERSONEN-DROPDOWN (intern)
    ================================================================ */
    private function render_name_dropdown() {
        $namen    = Handschelle_Database::get_distinct_namen();
        $selected = sanitize_text_field( wp_unslash( $_GET['hs_name'] ?? '' ) );
        ob_start();
        ?>
        <div class="hs-search-box">
            <h3 class="hs-search-title">👤 Nach Person suchen</h3>
            <form method="get" class="hs-search-form">
                <?php $this->preserve_page_param(); ?>
                <select name="hs_name" class="hs-select" onchange="this.form.submit()">
                    <option value="">-- Person auswählen --</option>
                    <?php foreach ( $namen as $n ) : ?>
                        <option value="<?php echo esc_attr($n); ?>" <?php selected($selected,$n); ?>><?php echo esc_html($n); ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="hs-btn">Suchen</button></noscript>
            </form>
            <?php if ( ! empty( $selected ) ) :
                $entries = Handschelle_Database::get_all( array( 'name' => $selected ) );
            ?>
                <div class="hs-search-results">
                    <h4>Einträge für: <em><?php echo esc_html($selected); ?></em> <span class="hs-count">(<?php echo count($entries); ?>)</span></h4>
                    <?php if ( empty($entries) ) : ?>
                        <p class="hs-empty">Keine Einträge für diese Person.</p>
                    <?php else : ?>
                        <div class="hs-cards-grid"><?php foreach ( $entries as $e ) echo $this->render_card($e); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       KARTE – einzelner Eintrag
    ================================================================ */
    public function render_card( $e ) {
        $img_url = handschelle_get_image_url( $e->bild );
        $status_class = array(
            'Verurteilt'          => 'hs-status-verurteilt',
            'Ermittlungen laufen' => 'hs-status-ermittlung',
            'Eingestellt'         => 'hs-status-eingestellt',
        );
        $is_logged_in = is_user_logged_in();
        $is_admin     = current_user_can( 'manage_options' );
        $edited       = isset( $_GET['hs_edited'] ) && intval( $_GET['hs_edited'] ) === intval( $e->id );
        ob_start();
        ?>
        <div class="hs-card" id="hs-card-<?php echo intval($e->id); ?>">
            <?php if ( $edited ) : ?>
                <div class="hs-alert hs-alert-success">✅ Eintrag erfolgreich aktualisiert!</div>
            <?php endif; ?>
            <div class="hs-card-header">
                <div class="hs-card-img-wrap <?php echo $img_url ? '' : 'hs-card-img-placeholder'; ?>">
                    <?php if ( $img_url ) : ?><img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($e->name); ?>" class="hs-card-img"><?php else : ?>👤<?php endif; ?>
                </div>
                <div class="hs-card-meta">
                    <h3 class="hs-card-name"><?php echo esc_html($e->name); ?></h3>
                    <?php if ( $e->beruf ) : ?><p class="hs-card-beruf"><?php echo esc_html($e->beruf); ?></p><?php endif; ?>
                    <?php if ( $e->partei ) : ?><p class="hs-card-partei">🏛 <?php echo esc_html($e->partei); ?><?php if($e->aufgabe_partei) echo ' &ndash; '.esc_html($e->aufgabe_partei); ?></p><?php endif; ?>
                    <?php if ( $e->parlament ) : ?><p class="hs-card-parlament">📜 <?php echo esc_html($e->parlament); ?><?php if($e->parlament_name) echo ' ('.esc_html($e->parlament_name).')'; ?></p><?php endif; ?>
                </div>
                <?php if ( $is_logged_in ) : ?>
                <button type="button"
                    class="hs-card-edit-btn"
                    onclick="hsToggleEdit(<?php echo intval($e->id); ?>)"
                    title="Eintrag bearbeiten">
                    ✏ Bearbeiten
                </button>
                <?php endif; ?>
            </div>
            <div class="hs-card-body">
                <div class="hs-card-straftat"><span class="hs-label">⚖ Straftat:</span><p><?php echo nl2br(esc_html($e->straftat)); ?></p></div>
                <?php if ( $e->urteil ) : ?><div class="hs-card-row"><span class="hs-label">📋 Urteil:</span> <?php echo esc_html($e->urteil); ?></div><?php endif; ?>
                <?php if ( $e->aktenzeichen ) : ?><div class="hs-card-row"><span class="hs-label">📁 Aktenzeichen:</span> <?php echo esc_html($e->aktenzeichen); ?></div><?php endif; ?>
                <div class="hs-card-row">
                    <span class="hs-badge <?php echo esc_attr($status_class[$e->status_straftat] ?? 'hs-status-ermittlung'); ?>"><?php echo esc_html($e->status_straftat); ?></span>
                    <?php echo $e->status_aktiv ? '<span class="hs-badge hs-badge-aktiv">Aktiv</span>' : '<span class="hs-badge hs-badge-inaktiv">Inaktiv</span>'; ?>
                </div>
                <?php if ( $e->bemerkung ) : ?><div class="hs-card-bemerkung"><span class="hs-label">💬 Bemerkung:</span><p><?php echo nl2br(esc_html($e->bemerkung)); ?></p></div><?php endif; ?>
                <?php if ( $e->link_quelle ) : ?><div class="hs-card-row"><a href="<?php echo esc_url($e->link_quelle); ?>" target="_blank" rel="noopener noreferrer" class="hs-link">🔍 Quelle ansehen</a></div><?php endif; ?>
            </div>
            <?php
            $sm_links = array();
            foreach ( $this->sm_fields() as $field => list( $icon, $label ) ) {
                if ( ! empty( $e->$field ) ) {
                    $key = str_replace( 'sm_', '', $field );
                    $sm_links[] = '<a href="'.esc_url($e->$field).'" target="_blank" rel="noopener noreferrer" class="hs-sm-link" data-sm="'.esc_attr($key).'" title="'.esc_attr($label).'">'.$icon.' '.esc_html($label).'</a>';
                }
            }
            if ( ! empty( $sm_links ) ) : ?>
                <div class="hs-card-footer"><?php echo implode( '', $sm_links ); ?></div>
            <?php endif; ?>
            <div class="hs-card-date">Eingetragen am <?php echo esc_html( date_i18n('d.m.Y', strtotime($e->datum_eintrag)) ); ?></div>

            <?php if ( $is_logged_in ) : ?>
            <!-- ── Inline-Bearbeitungsformular (eingeklappt) ─────── -->
            <div class="hs-card-edit-panel" id="hs-edit-panel-<?php echo intval($e->id); ?>" style="display:none;">
                <div class="hs-card-edit-header">
                    ✏ Eintrag bearbeiten
                    <button type="button" class="hs-edit-close" onclick="hsToggleEdit(<?php echo intval($e->id); ?>)" title="Schließen">✕</button>
                </div>
                <form method="post" enctype="multipart/form-data" class="hs-edit-form">
                    <?php wp_nonce_field( 'hs_frontend_edit', 'hs_edit_nonce' ); ?>
                    <input type="hidden" name="hs_edit_submit"  value="1">
                    <input type="hidden" name="hs_edit_id"      value="<?php echo intval($e->id); ?>">
                    <input type="hidden" name="hs_return_url"   value="<?php echo esc_url( get_permalink() ); ?>">

                    <div class="hs-edit-grid">
                        <!-- Eintragsdetails -->
                        <div class="hs-edit-section-title">📋 Eintragsdetails</div>
                        <div class="hs-field"><label>Datum</label><input type="date" name="datum_eintrag" value="<?php echo esc_attr($e->datum_eintrag); ?>" required></div>
                        <div class="hs-field"><label>Name <span>(max. 50)</span></label><input type="text" name="name" maxlength="50" value="<?php echo esc_attr($e->name); ?>" required></div>
                        <div class="hs-field"><label>Beruf <span>(max. 50)</span></label><input type="text" name="beruf" maxlength="50" value="<?php echo esc_attr($e->beruf); ?>"></div>
                        <div class="hs-field hs-field-full">
                            <label>Bild ersetzen <span>(optional)</span></label>
                            <input type="file" name="bild_upload" accept="image/*" class="hs-file-input">
                            <?php if ( $img_url ) : ?><div class="hs-edit-current-img"><img src="<?php echo esc_url($img_url); ?>" alt="Aktuell"><small>Aktuelles Bild</small></div><?php endif; ?>
                            <input type="hidden" name="bild" value="<?php echo esc_attr($e->bild); ?>">
                        </div>

                        <!-- Politisch -->
                        <div class="hs-edit-section-title">🏛 Politisch</div>
                        <div class="hs-field"><label>Partei <span>(max. 50)</span></label><input type="text" name="partei" maxlength="50" value="<?php echo esc_attr($e->partei); ?>"></div>
                        <div class="hs-field"><label>Aufgabe in der Partei</label><input type="text" name="aufgabe_partei" maxlength="100" value="<?php echo esc_attr($e->aufgabe_partei); ?>"></div>
                        <div class="hs-field">
                            <label>Parlament</label>
                            <select name="parlament">
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ( handschelle_parlaments() as $parl ) : ?>
                                    <option value="<?php echo esc_attr($parl); ?>" <?php selected($e->parlament, $parl); ?>><?php echo esc_html($parl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hs-field"><label>Parlament Name</label><input type="text" name="parlament_name" maxlength="50" value="<?php echo esc_attr($e->parlament_name); ?>"></div>
                        <div class="hs-field">
                            <label>Status</label>
                            <select name="status_aktiv">
                                <option value="1" <?php selected(intval($e->status_aktiv), 1); ?>>Aktiv</option>
                                <option value="0" <?php selected(intval($e->status_aktiv), 0); ?>>Inaktiv</option>
                            </select>
                        </div>

                        <!-- Straftat -->
                        <div class="hs-edit-section-title">⚖ Straftat</div>
                        <div class="hs-field hs-field-full">
                            <label>Straftat <span>(max. 200 Zeichen)</span></label>
                            <textarea name="straftat" maxlength="200" rows="3" required><?php echo esc_textarea($e->straftat); ?></textarea>
                            <small class="hs-char-counter" data-target="straftat">0 / 200 Zeichen</small>
                        </div>
                        <div class="hs-field"><label>Urteil <span>(max. 50)</span></label><input type="text" name="urteil" maxlength="50" value="<?php echo esc_attr($e->urteil); ?>"></div>
                        <div class="hs-field"><label>Link zur Quelle</label><input type="url" name="link_quelle" value="<?php echo esc_attr($e->link_quelle); ?>"></div>
                        <div class="hs-field"><label>Aktenzeichen</label><input type="text" name="aktenzeichen" maxlength="50" value="<?php echo esc_attr($e->aktenzeichen); ?>"></div>
                        <div class="hs-field hs-field-full"><label>Bemerkung</label><textarea name="bemerkung" rows="3"><?php echo esc_textarea($e->bemerkung); ?></textarea></div>
                        <div class="hs-field">
                            <label>Status Straftat</label>
                            <select name="status_straftat">
                                <?php foreach ( handschelle_status_straftat_options() as $st ) : ?>
                                    <option value="<?php echo esc_attr($st); ?>" <?php selected($e->status_straftat, $st); ?>><?php echo esc_html($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Social Media -->
                        <div class="hs-edit-section-title">📱 Social-Media</div>
                        <?php foreach ( array(
                            'sm_facebook'  => '📘 Facebook',
                            'sm_youtube'   => '▶ YouTube',
                            'sm_personal'  => '👤 Persönliches Profil',
                            'sm_twitter'   => '🐦 Twitter / X',
                            'sm_homepage'  => '🌐 Homepage',
                            'sm_wikipedia' => '📖 Wikipedia',
                            'sm_sonstige'  => '🔗 Sonstige',
                        ) as $field => $label ) : ?>
                            <div class="hs-field"><label><?php echo $label; ?></label><input type="url" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($e->$field ?? ''); ?>" placeholder="https://…"></div>
                        <?php endforeach; ?>

                        <?php if ( $is_admin ) : ?>
                        <!-- Freigabe (nur Admins) -->
                        <div class="hs-edit-section-title">⚙ Freigabe</div>
                        <div class="hs-field hs-field-full">
                            <label class="hs-checkbox-label">
                                <input type="checkbox" name="freigegeben" value="1" <?php checked( intval($e->freigegeben), 1 ); ?>>
                                Eintrag freigeben (öffentlich sichtbar)
                            </label>
                        </div>
                        <?php endif; ?>
                    </div><!-- .hs-edit-grid -->

                    <div class="hs-edit-actions">
                        <button type="submit" class="hs-btn hs-btn-primary">💾 Speichern</button>
                        <button type="button" class="hs-btn hs-btn-cancel" onclick="hsToggleEdit(<?php echo intval($e->id); ?>)">Abbrechen</button>
                    </div>
                </form>
            </div><!-- .hs-card-edit-panel -->
            <?php endif; ?>
        </div><!-- .hs-card -->
        <?php
        return ob_get_clean();
    }

    /* ================================================================
       HILFSMETHODEN
    ================================================================ */
    private function sm_fields() {
        return array(
            'sm_facebook'  => array( $this->svg_facebook(),  'Facebook' ),
            'sm_youtube'   => array( $this->svg_youtube(),   'YouTube' ),
            'sm_personal'  => array( $this->svg_person(),    'Persönliches Profil' ),
            'sm_twitter'   => array( $this->svg_twitter(),   'Twitter / X' ),
            'sm_homepage'  => array( $this->svg_homepage(),  'Persönliche Homepage' ),
            'sm_wikipedia' => array( $this->svg_wikipedia(), 'Wikipedia' ),
            'sm_sonstige'  => array( $this->svg_link(),      'Sonstige' ),
        );
    }

    private function preserve_page_param() {
        $page_id = get_queried_object_id();
        if ( $page_id ) echo '<input type="hidden" name="page_id" value="' . intval($page_id) . '">';
    }

    // ── SVG Brand Logos ───────────────────────────────────────
    private function svg_facebook() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>';
    }
    private function svg_youtube() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#FF0000"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>';
    }
    private function svg_twitter() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#000"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
    }
    private function svg_homepage() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#2c3e50"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
    }
    private function svg_wikipedia() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#000"><path d="M12.09 13.119c-.936 1.932-2.217 4.548-2.853 5.728-.616 1.074-1.127.931-1.532.029-1.406-3.321-4.293-9.144-5.044-10.84-.34-.758-.66-1.159-1.4-1.275-.52-.088-1.304-.176-1.304-.176v-.5h6.528v.5s-.811.088-1.441.246c-.42.104-.42.472-.17.996.519 1.077 3.374 7.196 3.374 7.196l.805-1.564-2.516-5.279c-.34-.758-.66-1.159-1.399-1.275-.52-.088-1.305-.176-1.305-.176v-.5h6.018v.5s-.811.088-1.441.246c-.42.104-.42.472-.17.996l1.945 4.062 1.929-4.02c.25-.524.249-.892-.169-.996-.63-.158-1.441-.246-1.441-.246v-.5h5.079v.5s-.785.088-1.305.176c-.739.116-1.059.517-1.4 1.275l-2.417 5.031 2.081 4.329c.519 1.077 3.374 7.196 3.374 7.196l3.374-7.196c.519-1.077.52-1.445.17-.996-.63-.158-1.441-.246-1.441-.246v-.5h5.079v.5s-.785.088-1.305.176c-.739.116-1.059.517-1.4 1.275l-5.044 10.84c-.405.902-.916 1.045-1.532-.029-.636-1.18-1.917-3.796-2.853-5.728l-1.047 2.175c-.905 1.879-1.847 3.702-2.428 4.786-.616 1.074-1.127.931-1.532.029L.474 6.801c-.34-.758-.66-1.159-1.4-1.275C-1.446 5.438-2.23 5.35-2.23 5.35v-.5H4.298v.5s-.811.088-1.441.246c-.42.104-.42.472-.17.996.519 1.077 3.374 7.196 3.374 7.196s2.375-4.942 3.084-6.487c-.246-.545-.499-.94-.849-1.051-.52-.088-1.305-.176-1.305-.176v-.5h6.018v.5s-.811.088-1.441.246c-.42.104-.42.472-.17.996l1.945 4.062 1.053-2.195z"/></svg>';
    }
    private function svg_person() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#555"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
    }
    private function svg_link() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="#555"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';
    }
}

new Handschelle_Shortcodes();
