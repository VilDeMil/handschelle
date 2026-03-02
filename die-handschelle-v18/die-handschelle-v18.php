<?php
/**
 * Plugin Name: Die Handschelle V.1.8
 * Description: Die-Handschelle - Wer hat eine Vorstrafe?
 * Version: 005
 * Author: Bernd K.R. Dorfmüller
 * Author URI: mailto:bernd.dorfmueller@gmail.com
 * Text Domain: die-handschelle
 */

if (!defined('ABSPATH')) {
    exit;
}

class Die_Handschelle_V18 {
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'handschelle_V06';

        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function activate(): void {
        $this->create_table();
    }

    private function create_table(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            datum_eintrag DATE NOT NULL,
            name VARCHAR(100) NOT NULL,
            beruf VARCHAR(50) DEFAULT '',
            bild_id BIGINT(20) UNSIGNED DEFAULT NULL,
            wikipedia_link VARCHAR(255) DEFAULT '',
            partei VARCHAR(50) DEFAULT '',
            parteifunktion VARCHAR(50) DEFAULT '',
            straftat VARCHAR(200) DEFAULT '',
            urteil VARCHAR(50) DEFAULT '',
            quelle_link VARCHAR(255) DEFAULT '',
            aktenzeichen VARCHAR(50) DEFAULT '',
            bemerkung TEXT,
            social_media_link VARCHAR(255) DEFAULT '',
            status_verurteilt TINYINT(1) NOT NULL DEFAULT 0,
            aktiv TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_name (name),
            KEY idx_partei (partei),
            KEY idx_straftat (straftat),
            KEY idx_aktiv (aktiv)
        ) $charset_collate;";

        dbDelta($sql);
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'handschelle-style',
            plugin_dir_url(__FILE__) . 'assets/css/handschelle.css',
            [],
            '005'
        );
    }

    public function register_shortcodes(): void {
        add_shortcode('handschelle', [$this, 'render_form_shortcode']);
        add_shortcode('handschelle-anzeige', [$this, 'render_list_shortcode']);
        add_shortcode('handschelle-straftat', [$this, 'render_detail_shortcode']);
        add_shortcode('handschelle-suche', [$this, 'render_search_shortcode']);
    }

    public function render_form_shortcode(): string {
        global $wpdb;
        $notice = '';

        if (isset($_POST['hs_submit'])) {
            if (!isset($_POST['hs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hs_nonce'])), 'hs_front_submit')) {
                $notice = '<div class="hs-notice hs-notice-error">Sicherheitsprüfung fehlgeschlagen.</div>';
            } else {
                $image_id = null;
                if (!empty($_FILES['bild']['name'])) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $image_id = media_handle_upload('bild', 0);
                    if (is_wp_error($image_id)) {
                        $image_id = null;
                    }
                }

                $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
                if ($name === '') {
                    $notice = '<div class="hs-notice hs-notice-error">Name ist erforderlich.</div>';
                } else {
                    $inserted = $wpdb->insert(
                        $this->table_name,
                        [
                            'datum_eintrag' => isset($_POST['datum_eintrag']) ? sanitize_text_field(wp_unslash($_POST['datum_eintrag'])) : gmdate('Y-m-d'),
                            'name' => mb_substr($name, 0, 100),
                            'beruf' => mb_substr(sanitize_text_field(wp_unslash($_POST['beruf'] ?? '')), 0, 50),
                            'bild_id' => $image_id,
                            'wikipedia_link' => esc_url_raw(wp_unslash($_POST['wikipedia_link'] ?? '')),
                            'partei' => mb_substr(sanitize_text_field(wp_unslash($_POST['partei'] ?? '')), 0, 50),
                            'parteifunktion' => mb_substr(sanitize_text_field(wp_unslash($_POST['parteifunktion'] ?? '')), 0, 50),
                            'straftat' => mb_substr(sanitize_textarea_field(wp_unslash($_POST['straftat'] ?? '')), 0, 200),
                            'urteil' => mb_substr(sanitize_text_field(wp_unslash($_POST['urteil'] ?? '')), 0, 50),
                            'quelle_link' => esc_url_raw(wp_unslash($_POST['quelle_link'] ?? '')),
                            'aktenzeichen' => mb_substr(sanitize_text_field(wp_unslash($_POST['aktenzeichen'] ?? '')), 0, 50),
                            'bemerkung' => sanitize_textarea_field(wp_unslash($_POST['bemerkung'] ?? '')),
                            'social_media_link' => esc_url_raw(wp_unslash($_POST['social_media_link'] ?? '')),
                            'status_verurteilt' => isset($_POST['status_verurteilt']) ? 1 : 0,
                            'aktiv' => 0,
                        ]
                    );

                    if ($inserted) {
                        $notice = '<div class="hs-notice hs-notice-success">Eintrag gespeichert und wartet auf Freigabe.</div>';
                    } else {
                        $notice = '<div class="hs-notice hs-notice-error">Fehler beim Speichern.</div>';
                    }
                }
            }
        }

        $names = $wpdb->get_col("SELECT DISTINCT name FROM {$this->table_name} WHERE name <> '' ORDER BY name ASC");

        ob_start();
        ?>
        <div class="handschelle-wrap">
            <div class="handschelle-header"><span class="hs-icon">⛓️</span><h1>Die Handschelle V.1.8</h1></div>
            <?php echo wp_kses_post($notice); ?>
            <div class="handschelle-form-wrap">
                <h2>Eingabeformular</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('hs_front_submit', 'hs_nonce'); ?>
                    <div class="hs-form-grid">
                        <div class="hs-form-group">
                            <label for="datum_eintrag">Datum Eintrag</label>
                            <input type="date" id="datum_eintrag" name="datum_eintrag" value="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                        </div>
                        <div class="hs-form-group">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" maxlength="100" list="hs-name-list" required>
                            <datalist id="hs-name-list">
                                <?php foreach ($names as $name_item) : ?>
                                    <option value="<?php echo esc_attr($name_item); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <?php echo $this->input_field('beruf', 'Beruf', 'text', 50); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('bild', 'Bild', 'file'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('wikipedia_link', 'Wikipedia Link', 'url'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('partei', 'Partei', 'text', 50); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('parteifunktion', 'Parteifunktion', 'text', 50); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <div class="hs-form-group hs-full">
                            <label for="straftat">Straftat</label>
                            <textarea id="straftat" name="straftat" maxlength="200"></textarea>
                        </div>
                        <?php echo $this->input_field('urteil', 'Urteil', 'text', 50); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('quelle_link', 'Link zur Quelle / Straftat', 'url'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->input_field('aktenzeichen', 'Aktenzeichen', 'text', 50); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <div class="hs-form-group hs-full">
                            <label for="bemerkung">Bemerkung</label>
                            <textarea id="bemerkung" name="bemerkung"></textarea>
                        </div>
                        <?php echo $this->input_field('social_media_link', 'Social-Media Link', 'url'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <div class="hs-form-group">
                            <label><input type="checkbox" name="status_verurteilt" value="1"> Status: Verurteilt (Ja)</label>
                        </div>
                    </div>
                    <button class="hs-btn hs-btn-primary" type="submit" name="hs_submit">Eintrag speichern</button>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function input_field(string $name, string $label, string $type = 'text', int $max = 0): string {
        $max_attr = $max > 0 ? ' maxlength="' . absint($max) . '"' : '';
        return '<div class="hs-form-group"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label><input type="' . esc_attr($type) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '"' . $max_attr . '></div>';
    }

    private function get_entries(array $where = []): array {
        global $wpdb;

        $conditions = ['aktiv = 1'];
        $values = [];

        foreach ($where as $column => $value) {
            if ($value === '') {
                continue;
            }
            $conditions[] = "$column = %s";
            $values[] = $value;
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $conditions) . ' ORDER BY datum_eintrag DESC';
        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function render_list_shortcode(): string {
        $entries = $this->get_entries();

        ob_start();
        echo '<div class="handschelle-wrap"><div class="handschelle-header"><span class="hs-icon">📋</span><h1>Alle Einträge</h1></div>';
        echo $this->render_cards($entries); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
        return (string) ob_get_clean();
    }

    public function render_search_shortcode(): string {
        global $wpdb;

        $selected_name = sanitize_text_field(wp_unslash($_GET['hs_name'] ?? ''));
        $selected_partei = sanitize_text_field(wp_unslash($_GET['hs_partei'] ?? ''));
        $selected_straftat = sanitize_text_field(wp_unslash($_GET['hs_straftat'] ?? ''));

        $names = $wpdb->get_col("SELECT DISTINCT name FROM {$this->table_name} WHERE aktiv = 1 AND name <> '' ORDER BY name ASC");
        $parteien = $wpdb->get_col("SELECT DISTINCT partei FROM {$this->table_name} WHERE aktiv = 1 AND partei <> '' ORDER BY partei ASC");
        $straftaten = $wpdb->get_col("SELECT DISTINCT straftat FROM {$this->table_name} WHERE aktiv = 1 AND straftat <> '' ORDER BY straftat ASC");

        $filters = [];
        if ($selected_name !== '') {
            $filters['name'] = $selected_name;
        }
        if ($selected_partei !== '') {
            $filters['partei'] = $selected_partei;
        }
        if ($selected_straftat !== '') {
            $filters['straftat'] = $selected_straftat;
        }

        $entries = $this->get_entries($filters);

        ob_start();
        ?>
        <div class="handschelle-wrap">
            <div class="handschelle-search-wrap">
                <h2>Suche</h2>
                <form method="get">
                    <?php
                    foreach ($_GET as $key => $value) {
                        if (!in_array($key, ['hs_name', 'hs_partei', 'hs_straftat'], true)) {
                            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(sanitize_text_field(wp_unslash($value))) . '">';
                        }
                    }
                    ?>
                    <div class="hs-dropdown-row">
                        <?php echo $this->render_select('hs_name', 'Name', $names, $selected_name); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->render_select('hs_partei', 'Partei', $parteien, $selected_partei); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $this->render_select('hs_straftat', 'Straftat', $straftaten, $selected_straftat); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <button class="hs-btn hs-btn-primary" type="submit">Filtern</button>
                    <a class="hs-btn hs-btn-secondary" href="<?php echo esc_url(remove_query_arg(['hs_name', 'hs_partei', 'hs_straftat'])); ?>">Zurücksetzen</a>
                </form>
            </div>
            <?php echo $this->render_cards($entries); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function render_select(string $name, string $label, array $items, string $selected): string {
        $html = '<div class="hs-form-group"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label><select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '"><option value="">-- Alle --</option>';
        foreach ($items as $item) {
            $html .= '<option value="' . esc_attr($item) . '" ' . selected($selected, $item, false) . '>' . esc_html($item) . '</option>';
        }
        $html .= '</select></div>';
        return $html;
    }

    public function render_detail_shortcode(): string {
        $entry_id = absint($_GET['hs_entry'] ?? 0);
        if ($entry_id === 0) {
            return '<div class="hs-notice hs-notice-info">Kein Eintrag ausgewählt. Bitte einen Eintrag über [handschelle-suche] auswählen.</div>';
        }

        global $wpdb;
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d AND aktiv = 1", $entry_id), ARRAY_A);
        if (!$entry) {
            return '<div class="hs-notice hs-notice-error">Eintrag nicht gefunden.</div>';
        }

        ob_start();
        ?>
        <div class="handschelle-wrap hs-detail-wrap">
            <div class="hs-back-link"><a href="<?php echo esc_url(remove_query_arg('hs_entry')); ?>">Zurück</a></div>
            <div class="hs-detail-hero">
                <?php if (!empty($entry['bild_id'])) : ?>
                    <?php echo wp_get_attachment_image((int) $entry['bild_id'], 'medium'); ?>
                <?php else : ?>
                    <div class="hs-detail-hero-placeholder">👤</div>
                <?php endif; ?>
                <div>
                    <h2><?php echo esc_html($entry['name']); ?></h2>
                    <span class="hs-partei-badge"><?php echo esc_html($entry['partei'] ?: 'Ohne Partei'); ?></span>
                    <p>Status: Verurteilt <?php echo !empty($entry['status_verurteilt']) ? 'Ja' : 'Nein'; ?></p>
                </div>
            </div>
            <div class="hs-detail-body">
                <?php echo $this->render_template_fields($entry); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function render_cards(array $entries): string {
        if (!$entries) {
            return '<div class="hs-notice hs-notice-info">Keine Einträge gefunden.</div>';
        }

        ob_start();
        echo '<div class="hs-cards-grid">';
        foreach ($entries as $entry) {
            echo '<div class="hs-card"><div class="hs-card-header">';
            if (!empty($entry['bild_id'])) {
                echo wp_get_attachment_image((int) $entry['bild_id'], 'thumbnail', false, ['class' => 'hs-card-img']);
            } else {
                echo '<div class="hs-card-img-placeholder">👤</div>';
            }
            echo '<div class="hs-card-header-info"><h3>' . esc_html($entry['name']) . '</h3><span class="hs-partei-badge">' . esc_html($entry['partei'] ?: 'Ohne Partei') . '</span></div></div><div class="hs-card-body">';
            echo '<div class="hs-card-row"><span class="hs-label">Datum:</span><span class="hs-value">' . esc_html($entry['datum_eintrag']) . '</span></div>';
            echo '<div class="hs-card-row"><span class="hs-label">Beruf:</span><span class="hs-value">' . esc_html($entry['beruf']) . '</span></div>';
            echo '<div class="hs-card-row"><span class="hs-label">Urteil:</span><span class="hs-value">' . esc_html($entry['urteil']) . '</span></div>';
            echo '<div class="hs-card-row"><span class="hs-label">Aktenzeichen:</span><span class="hs-value">' . esc_html($entry['aktenzeichen']) . '</span></div>';
            echo '<div class="hs-card-row"><span class="hs-label">Status:</span><span class="hs-value">Verurteilt ' . (!empty($entry['status_verurteilt']) ? 'Ja' : 'Nein') . '</span></div>';
            if (!empty($entry['quelle_link'])) {
                echo '<div class="hs-card-row"><span class="hs-label">Quelle:</span><span class="hs-value"><a href="' . esc_url($entry['quelle_link']) . '" target="_blank" rel="noopener">Link</a></span></div>';
            }
            echo '<div class="hs-card-straftat"><strong>Straftat:</strong> ' . esc_html($entry['straftat']) . '</div>';
            echo '</div></div>';
        }
        echo '</div>';

        return (string) ob_get_clean();
    }

    private function render_template_fields(array $entry): string {
        $fields = [
            'Datum Eintrag' => $entry['datum_eintrag'],
            'Name' => $entry['name'],
            'Beruf' => $entry['beruf'],
            'Wikipedia Link' => $entry['wikipedia_link'],
            'Partei' => $entry['partei'],
            'Parteifunktion' => $entry['parteifunktion'],
            'Straftat' => $entry['straftat'],
            'Urteil' => $entry['urteil'],
            'Link zur Quelle' => $entry['quelle_link'],
            'Aktenzeichen' => $entry['aktenzeichen'],
            'Bemerkung' => $entry['bemerkung'],
            'Social-Media Link' => $entry['social_media_link'],
            'Status: Verurteilt' => !empty($entry['status_verurteilt']) ? 'Ja' : 'Nein',
        ];

        $html = '<div class="hs-detail-section"><h3>Ausgabetemplate</h3><div class="hs-detail-grid">';
        foreach ($fields as $label => $value) {
            $display = (str_contains($label, 'Link') && !empty($value)) ? '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">' . esc_html($value) . '</a>' : esc_html((string) $value);
            $html .= '<div class="hs-detail-item"><div class="hs-label">' . esc_html($label) . '</div><div class="hs-value">' . $display . '</div></div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    public function register_admin_menu(): void {
        add_menu_page(
            'Die Handschelle',
            'Die Handschelle',
            'manage_options',
            'handschelle-admin',
            [$this, 'render_admin_page'],
            'dashicons-shield-alt'
        );
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handle_admin_actions();
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC", ARRAY_A);
        ?>
        <div class="wrap handschelle-wrap">
            <h1>Admin Menü - Die Handschelle</h1>
            <p>
                <a class="hs-btn hs-btn-success" href="<?php echo esc_url(add_query_arg(['hs_action' => 'export_csv'])); ?>">Export to CSV</a>
                <a class="hs-btn hs-btn-secondary" href="<?php echo esc_url(add_query_arg(['hs_action' => 'recreate_db'])); ?>" onclick="return confirm('Datenbank löschen und neu erstellen?');">Neuanlage Datenbank</a>
                <a class="hs-btn hs-btn-danger" href="<?php echo esc_url(add_query_arg(['hs_action' => 'clear_db'])); ?>" onclick="return confirm('Datenbank leeren?');">Leeren der Datenbank</a>
            </p>
            <div class="handschelle-form-wrap">
                <h2>Import from CSV</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('hs_csv_import', 'hs_csv_nonce'); ?>
                    <input type="file" name="hs_csv_file" accept=".csv" required>
                    <button class="hs-btn hs-btn-primary" type="submit" name="hs_import_csv">Import starten</button>
                </form>
            </div>
            <h2>Einträge bearbeiten / löschen / aktivieren</h2>
            <table class="hs-table">
                <thead><tr><th>ID</th><th>Name</th><th>Partei</th><th>Straftat</th><th>Status</th><th>Aktiv</th><th>Aktionen</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $row['id']); ?></td>
                        <td><?php echo esc_html($row['name']); ?></td>
                        <td><?php echo esc_html($row['partei']); ?></td>
                        <td><?php echo esc_html($row['straftat']); ?></td>
                        <td><?php echo !empty($row['status_verurteilt']) ? 'Ja' : 'Nein'; ?></td>
                        <td class="<?php echo !empty($row['aktiv']) ? 'hs-status-aktiv' : 'hs-status-inaktiv'; ?>"><?php echo !empty($row['aktiv']) ? 'Aktiv' : 'Inaktiv'; ?></td>
                        <td class="hs-action-links">
                            <a class="hs-action-edit" href="<?php echo esc_url(add_query_arg(['hs_edit' => $row['id']])); ?>">Bearbeiten</a>
                            <a class="hs-action-delete" href="<?php echo esc_url(add_query_arg(['hs_action' => 'delete', 'id' => $row['id']])); ?>" onclick="return confirm('Eintrag löschen?');">Löschen</a>
                            <?php if (!empty($row['aktiv'])) : ?>
                                <a class="hs-action-toggle-on" href="<?php echo esc_url(add_query_arg(['hs_action' => 'toggle', 'id' => $row['id'], 'set' => 0])); ?>">Deaktivieren</a>
                            <?php else : ?>
                                <a class="hs-action-toggle-off" href="<?php echo esc_url(add_query_arg(['hs_action' => 'toggle', 'id' => $row['id'], 'set' => 1])); ?>">Aktivieren</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php $this->render_admin_edit_form(); ?>
        </div>
        <?php
    }

    private function handle_admin_actions(): void {
        global $wpdb;

        if (isset($_POST['hs_import_csv'])) {
            $this->import_csv();
        }

        $action = sanitize_text_field(wp_unslash($_GET['hs_action'] ?? ''));
        $id = absint($_GET['id'] ?? 0);

        if ($action === 'delete' && $id > 0) {
            $wpdb->delete($this->table_name, ['id' => $id]);
        }

        if ($action === 'toggle' && $id > 0) {
            $set = absint($_GET['set'] ?? 0);
            $wpdb->update($this->table_name, ['aktiv' => $set ? 1 : 0], ['id' => $id]);
        }

        if ($action === 'clear_db') {
            $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }

        if ($action === 'recreate_db') {
            $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
            $this->create_table();
        }

        if ($action === 'export_csv') {
            $this->export_csv();
        }

        if (isset($_POST['hs_save_edit'])) {
            $edit_id = absint($_POST['edit_id'] ?? 0);
            if ($edit_id > 0) {
                $wpdb->update(
                    $this->table_name,
                    [
                        'datum_eintrag' => sanitize_text_field(wp_unslash($_POST['datum_eintrag'] ?? gmdate('Y-m-d'))),
                        'name' => mb_substr(sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 0, 100),
                        'beruf' => mb_substr(sanitize_text_field(wp_unslash($_POST['beruf'] ?? '')), 0, 50),
                        'wikipedia_link' => esc_url_raw(wp_unslash($_POST['wikipedia_link'] ?? '')),
                        'partei' => mb_substr(sanitize_text_field(wp_unslash($_POST['partei'] ?? '')), 0, 50),
                        'parteifunktion' => mb_substr(sanitize_text_field(wp_unslash($_POST['parteifunktion'] ?? '')), 0, 50),
                        'straftat' => mb_substr(sanitize_textarea_field(wp_unslash($_POST['straftat'] ?? '')), 0, 200),
                        'urteil' => mb_substr(sanitize_text_field(wp_unslash($_POST['urteil'] ?? '')), 0, 50),
                        'quelle_link' => esc_url_raw(wp_unslash($_POST['quelle_link'] ?? '')),
                        'aktenzeichen' => mb_substr(sanitize_text_field(wp_unslash($_POST['aktenzeichen'] ?? '')), 0, 50),
                        'bemerkung' => sanitize_textarea_field(wp_unslash($_POST['bemerkung'] ?? '')),
                        'social_media_link' => esc_url_raw(wp_unslash($_POST['social_media_link'] ?? '')),
                        'status_verurteilt' => isset($_POST['status_verurteilt']) ? 1 : 0,
                    ],
                    ['id' => $edit_id]
                );
            }
        }
    }

    private function render_admin_edit_form(): void {
        global $wpdb;
        $edit_id = absint($_GET['hs_edit'] ?? 0);
        if ($edit_id === 0) {
            return;
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $edit_id), ARRAY_A);
        if (!$row) {
            return;
        }
        ?>
        <div class="handschelle-form-wrap">
            <h2>Eintrag bearbeiten (Vollbild)</h2>
            <form method="post">
                <input type="hidden" name="edit_id" value="<?php echo esc_attr((string) $row['id']); ?>">
                <div class="hs-form-grid">
                    <div class="hs-form-group"><label>Datum Eintrag</label><input type="date" name="datum_eintrag" value="<?php echo esc_attr($row['datum_eintrag']); ?>"></div>
                    <div class="hs-form-group"><label>Name</label><input type="text" name="name" maxlength="100" value="<?php echo esc_attr($row['name']); ?>" required></div>
                    <div class="hs-form-group"><label>Beruf</label><input type="text" name="beruf" maxlength="50" value="<?php echo esc_attr($row['beruf']); ?>"></div>
                    <div class="hs-form-group"><label>Wikipedia Link</label><input type="url" name="wikipedia_link" value="<?php echo esc_attr($row['wikipedia_link']); ?>"></div>
                    <div class="hs-form-group"><label>Partei</label><input type="text" name="partei" maxlength="50" value="<?php echo esc_attr($row['partei']); ?>"></div>
                    <div class="hs-form-group"><label>Parteifunktion</label><input type="text" name="parteifunktion" maxlength="50" value="<?php echo esc_attr($row['parteifunktion']); ?>"></div>
                    <div class="hs-form-group hs-full"><label>Straftat</label><textarea name="straftat" maxlength="200"><?php echo esc_textarea($row['straftat']); ?></textarea></div>
                    <div class="hs-form-group"><label>Urteil</label><input type="text" name="urteil" maxlength="50" value="<?php echo esc_attr($row['urteil']); ?>"></div>
                    <div class="hs-form-group"><label>Link zur Quelle</label><input type="url" name="quelle_link" value="<?php echo esc_attr($row['quelle_link']); ?>"></div>
                    <div class="hs-form-group"><label>Aktenzeichen</label><input type="text" name="aktenzeichen" maxlength="50" value="<?php echo esc_attr($row['aktenzeichen']); ?>"></div>
                    <div class="hs-form-group hs-full"><label>Bemerkung</label><textarea name="bemerkung"><?php echo esc_textarea($row['bemerkung']); ?></textarea></div>
                    <div class="hs-form-group"><label>Social-Media Link</label><input type="url" name="social_media_link" value="<?php echo esc_attr($row['social_media_link']); ?>"></div>
                    <div class="hs-form-group"><label><input type="checkbox" name="status_verurteilt" value="1" <?php checked((int) $row['status_verurteilt'], 1); ?>> Verurteilt (Ja)</label></div>
                </div>
                <button class="hs-btn hs-btn-primary" type="submit" name="hs_save_edit">Speichern</button>
            </form>
        </div>
        <?php
    }

    private function export_csv(): void {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=handschelle-export.csv');

        $output = fopen('php://output', 'w');
        if (!$output) {
            exit;
        }

        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit;
    }

    private function import_csv(): void {
        global $wpdb;

        if (!isset($_POST['hs_csv_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hs_csv_nonce'])), 'hs_csv_import')) {
            return;
        }

        if (empty($_FILES['hs_csv_file']['tmp_name'])) {
            return;
        }

        $file = fopen($_FILES['hs_csv_file']['tmp_name'], 'r');
        if (!$file) {
            return;
        }

        $header = fgetcsv($file);
        if (!$header) {
            fclose($file);
            return;
        }

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }
            unset($data['id']);
            $data['aktiv'] = isset($data['aktiv']) ? (int) $data['aktiv'] : 0;
            $data['status_verurteilt'] = isset($data['status_verurteilt']) ? (int) $data['status_verurteilt'] : 0;
            $wpdb->insert($this->table_name, $data);
        }

        fclose($file);
    }
}

new Die_Handschelle_V18();
