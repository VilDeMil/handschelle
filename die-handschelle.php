<?php
/**
 * Plugin Name: Die-Handschelle
 * Plugin URI:  https://github.com/VilDeMil/handschelle
 * Description: Dokumentation von Straftaten politischer Personen. Neue Einträge müssen im Admin-Bereich freigegeben werden.
 * Version:     3.00
 * Author:      Bernd K.R. Dorfmüller
 * Author URI:  mailto:bernd@xn--dorfmller-u9a.com
 * Text Domain: die-handschelle
 * License:     GPL-2.0+
 *
 * DISCLAIMER:
 * Dieses Plugin dient ausschließlich der sachlichen Dokumentation öffentlich
 * bekannter Straftaten politischer Personen auf Basis von Medienberichten und
 * Gerichtsurteilen. Es erhebt keinen Anspruch auf Vollständigkeit. Alle Angaben
 * ohne Gewähr. Betreiber haften nicht für die Richtigkeit der eingetragenen
 * Inhalte. Die Veröffentlichung eines Eintrags erfolgt erst nach manueller
 * Prüfung und Freigabe durch den Administrator.
 * Version wird bei jedem Commit um 0.01 erhöht.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HANDSCHELLE_VERSION',    '3.00' );
define( 'HANDSCHELLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HANDSCHELLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HANDSCHELLE_DB_TABLE',   'die_handschelle' );

require_once HANDSCHELLE_PLUGIN_DIR . 'includes/helpers.php';
require_once HANDSCHELLE_PLUGIN_DIR . 'includes/database.php';
require_once HANDSCHELLE_PLUGIN_DIR . 'includes/image-handler.php';
require_once HANDSCHELLE_PLUGIN_DIR . 'includes/admin.php';
require_once HANDSCHELLE_PLUGIN_DIR . 'includes/shortcodes.php';

register_activation_hook( __FILE__, array( 'Handschelle_Database', 'create_table' ) );
register_deactivation_hook( __FILE__, '__return_true' );

function handschelle_enqueue_assets() {
    wp_enqueue_style( 'handschelle-style', HANDSCHELLE_PLUGIN_URL . 'assets/css/handschelle.css', array(), HANDSCHELLE_VERSION );
    wp_enqueue_script( 'handschelle-script', HANDSCHELLE_PLUGIN_URL . 'assets/js/handschelle.js', array( 'jquery' ), HANDSCHELLE_VERSION, true );
    wp_localize_script( 'handschelle-script', 'handschelle_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'handschelle_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'handschelle_enqueue_assets' );

function handschelle_admin_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'handschelle' ) === false ) return;
    wp_enqueue_media();
    wp_enqueue_style( 'handschelle-admin-style', HANDSCHELLE_PLUGIN_URL . 'assets/css/handschelle.css', array(), HANDSCHELLE_VERSION );
    wp_enqueue_script( 'handschelle-admin-script', HANDSCHELLE_PLUGIN_URL . 'assets/js/handschelle.js', array( 'jquery' ), HANDSCHELLE_VERSION, true );
    wp_localize_script( 'handschelle-admin-script', 'handschelle_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'handschelle_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'handschelle_admin_enqueue_assets' );
