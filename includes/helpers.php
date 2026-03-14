<?php
/**
 * Die-Handschelle 2.0 A – Hilfsfunktionen
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

function handschelle_parlaments() {
    return array(
        'Europäisches Parlament',
        'Bundestag',
        'Bundesrat',
        'Landtag Baden-Württemberg',
        'Landtag Bayern (Bayerischer Landtag)',
        'Abgeordnetenhaus Berlin',
        'Brandenburgischer Landtag',
        'Bürgerschaft Bremen',
        'Bürgerschaft Hamburg',
        'Hessischer Landtag',
        'Landtag Mecklenburg-Vorpommern',
        'Niedersächsischer Landtag',
        'Landtag Nordrhein-Westfalen',
        'Landtag Rheinland-Pfalz',
        'Landtag des Saarlandes',
        'Sächsischer Landtag',
        'Landtag Sachsen-Anhalt',
        'Schleswig-Holsteinischer Landtag',
        'Thüringer Landtag',
        'Stadtrat / Gemeinderat',
        'Kreistag',
        'Bezirkstag',
        'Sonstiges',
    );
}

function handschelle_status_straftat_options() {
    return array( 'Ermittlungen laufen', 'Verurteilt', 'Eingestellt' );
}

function handschelle_get_image_url( $bild ) {
    if ( empty( $bild ) ) return '';
    if ( is_numeric( $bild ) && intval( $bild ) > 0 ) {
        $url = wp_get_attachment_image_url( intval( $bild ), 'medium' );
        return $url ? $url : '';
    }
    return esc_url( $bild );
}

function handschelle_sanitize_entry( $post ) {
    $geburtsdatum_raw = sanitize_text_field( $post['geburtsdatum'] ?? '' );
    $geburtsdatum     = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
    return array(
        'datum_eintrag'   => sanitize_text_field( $post['datum_eintrag']   ?: date( 'Y-m-d' ) ),
        'name'            => substr( sanitize_text_field( $post['name']           ?? '' ), 0, 50 ),
        'beruf'           => substr( sanitize_text_field( $post['beruf']          ?? '' ), 0, 50 ),
        'geburtsort'      => substr( sanitize_text_field( $post['geburtsort']     ?? '' ), 0, 100 ),
        'geburtsdatum'    => $geburtsdatum,
        'bild'            => sanitize_text_field( $post['bild']            ?? '' ),
        'partei'          => substr( sanitize_text_field( $post['partei']         ?? '' ), 0, 50 ),
        'aufgabe_partei'  => substr( sanitize_text_field( $post['aufgabe_partei'] ?? '' ), 0, 100 ),
        'parlament'       => sanitize_text_field( $post['parlament']       ?? '' ),
        'parlament_name'  => substr( sanitize_text_field( $post['parlament_name'] ?? '' ), 0, 50 ),
        'status_aktiv'    => ! empty( $post['status_aktiv'] ) ? intval( $post['status_aktiv'] ) : 0,
        'straftat'        => substr( sanitize_textarea_field( $post['straftat']   ?? '' ), 0, 200 ),
        'urteil'          => substr( sanitize_text_field( $post['urteil']         ?? '' ), 0, 50 ),
        'link_quelle'     => esc_url_raw( $post['link_quelle']     ?? '' ),
        'aktenzeichen'    => substr( sanitize_text_field( $post['aktenzeichen']   ?? '' ), 0, 50 ),
        'bemerkung'       => sanitize_textarea_field( $post['bemerkung']   ?? '' ),
        'status_straftat' => sanitize_text_field( $post['status_straftat'] ?? 'Ermittlungen laufen' ),
        'sm_facebook'     => esc_url_raw( $post['sm_facebook']     ?? '' ),
        'sm_youtube'      => esc_url_raw( $post['sm_youtube']      ?? '' ),
        'sm_personal'     => esc_url_raw( $post['sm_personal']     ?? '' ),
        'sm_twitter'      => esc_url_raw( $post['sm_twitter']      ?? '' ),
        'sm_homepage'     => esc_url_raw( $post['sm_homepage']     ?? '' ),
        'sm_wikipedia'    => esc_url_raw( $post['sm_wikipedia']    ?? '' ),
        'sm_sonstige'     => esc_url_raw( $post['sm_sonstige']     ?? '' ),
        'sm_linkedin'     => esc_url_raw( $post['sm_linkedin']     ?? '' ),
        'sm_xing'         => esc_url_raw( $post['sm_xing']         ?? '' ),
        'sm_truth_social' => esc_url_raw( $post['sm_truth_social'] ?? '' ),
    );
}

function handschelle_calc_age( $geburtsdatum ) {
    if ( empty( $geburtsdatum ) || $geburtsdatum === '0000-00-00' ) return null;
    try {
        $birth = new DateTime( $geburtsdatum );
        $today = new DateTime( 'today' );
        return (int) $birth->diff( $today )->y;
    } catch ( Exception $e ) {
        return null;
    }
}
