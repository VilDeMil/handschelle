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
    return array( 'Ermittlungen laufen', 'Verurteilt', 'Eingestellt', 'Berufung' );
}

function handschelle_laender() {
    return array(
        'Afghanistan', 'Ägypten', 'Albanien', 'Algerien', 'Andorra', 'Angola', 'Antigua und Barbuda',
        'Äquatorialguinea', 'Argentinien', 'Armenien', 'Aserbaidschan', 'Äthiopien', 'Australien',
        'Bahamas', 'Bahrain', 'Bangladesch', 'Barbados', 'Belarus', 'Belgien', 'Belize', 'Benin',
        'Bhutan', 'Bolivien', 'Bosnien und Herzegowina', 'Botswana', 'Brasilien', 'Brunei',
        'Bulgarien', 'Burkina Faso', 'Burundi', 'Chile', 'China', 'Costa Rica', "Côte d'Ivoire",
        'Dänemark', 'Deutschland', 'Dominica', 'Dominikanische Republik', 'Dschibuti', 'Ecuador',
        'El Salvador', 'Eritrea', 'Estland', 'Eswatini', 'Fidschi', 'Finnland', 'Frankreich',
        'Gabun', 'Gambia', 'Georgien', 'Ghana', 'Grenada', 'Griechenland', 'Guatemala', 'Guinea',
        'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Indien', 'Indonesien', 'Irak', 'Iran',
        'Irland', 'Island', 'Israel', 'Italien', 'Jamaika', 'Japan', 'Jemen', 'Jordanien',
        'Kambodscha', 'Kamerun', 'Kanada', 'Kap Verde', 'Kasachstan', 'Katar', 'Kenia', 'Kirgisistan',
        'Kiribati', 'Kolumbien', 'Komoren', 'Kongo (Demokratische Republik)', 'Kongo (Republik)',
        'Korea (Nord)', 'Korea (Süd)', 'Kosovo', 'Kroatien', 'Kuba', 'Kuwait', 'Laos', 'Lesotho',
        'Lettland', 'Libanon', 'Liberia', 'Libyen', 'Liechtenstein', 'Litauen', 'Luxemburg',
        'Madagaskar', 'Malawi', 'Malaysia', 'Malediven', 'Mali', 'Malta', 'Marokko', 'Marshallinseln',
        'Mauretanien', 'Mauritius', 'Mexiko', 'Mikronesien', 'Moldau', 'Monaco', 'Mongolei',
        'Montenegro', 'Mosambik', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Neuseeland', 'Nicaragua',
        'Niederlande', 'Niger', 'Nigeria', 'Nordmazedonien', 'Norwegen', 'Oman', 'Österreich',
        'Pakistan', 'Palau', 'Palästina', 'Panama', 'Papua-Neuguinea', 'Paraguay', 'Peru',
        'Philippinen', 'Polen', 'Portugal', 'Ruanda', 'Rumänien', 'Russland', 'Salomonen', 'Sambia',
        'Samoa', 'San Marino', 'São Tomé und Príncipe', 'Saudi-Arabien', 'Schweden', 'Schweiz',
        'Senegal', 'Serbien', 'Sierra Leone', 'Simbabwe', 'Singapur', 'Slowakei', 'Slowenien',
        'Somalia', 'Spanien', 'Sri Lanka', 'St. Kitts und Nevis', 'St. Lucia',
        'St. Vincent und die Grenadinen', 'Südafrika', 'Sudan', 'Südsudan', 'Suriname', 'Syrien',
        'Tadschikistan', 'Tansania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad und Tobago',
        'Tschad', 'Tschechien', 'Tunesien', 'Türkei', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine',
        'Ungarn', 'Uruguay', 'Usbekistan', 'Vanuatu', 'Vatikanstadt', 'Venezuela', 'Vereinigte Arabische Emirate',
        'Vereinigte Staaten', 'Vereinigtes Königreich', 'Vietnam', 'Zentralafrikanische Republik', 'Zypern',
    );
}

/**
 * Return the person's name for display.
 * Guests see a redacted placeholder; logged-in users see the real name.
 *
 * @param  string $name  Raw name from DB.
 * @return string        Name (for esc_html/esc_attr by caller) or placeholder.
 */
function hs_display_name( $name ) {
    return is_user_logged_in() ? $name : '████████';
}

/**
 * Encode a person name for use in URL parameters.
 * Prefixes with 'n:' so the decoder can distinguish encoded from plain values.
 */
function hs_encode_url_name( $name ) {
    return 'n:' . base64_encode( $name );
}

/**
 * Decode a URL name parameter — handles both encoded ('n:…') and plain values.
 */
function hs_decode_url_name( $value ) {
    if ( substr( $value, 0, 2 ) === 'n:' ) {
        return base64_decode( substr( $value, 2 ) );
    }
    return $value;
}

function handschelle_get_image_url( $bild ) {
    if ( empty( $bild ) ) return '';
    if ( is_numeric( $bild ) && intval( $bild ) > 0 ) {
        $id  = intval( $bild );
        $url = wp_get_attachment_image_url( $id, 'medium' );
        if ( ! $url ) $url = wp_get_attachment_image_url( $id, 'full' );
        if ( ! $url ) {
            $file = get_attached_file( $id );
            if ( $file ) $url = wp_get_attachment_url( $id );
        }
        return $url ? $url : '';
    }
    return esc_url( $bild );
}

function handschelle_sanitize_entry( $post ) {
    $geburtsdatum_raw = sanitize_text_field( $post['geburtsdatum'] ?? '' );
    $geburtsdatum     = ( $geburtsdatum_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $geburtsdatum_raw ) ) ? $geburtsdatum_raw : null;
    $dod_raw          = sanitize_text_field( $post['dod'] ?? '' );
    $dod              = ( $dod_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dod_raw ) ) ? $dod_raw : null;
    return array(
        'datum_eintrag'   => sanitize_text_field( $post['datum_eintrag']   ?: date( 'Y-m-d' ) ),
        'name'            => substr( sanitize_text_field( $post['name']           ?? '' ), 0, 50 ),
        'beruf'           => substr( sanitize_text_field( $post['beruf']          ?? '' ), 0, 50 ),
        'geburtsort'      => substr( sanitize_text_field( $post['geburtsort']     ?? '' ), 0, 100 ),
        'geburtsland'     => substr( sanitize_text_field( $post['geburtsland']    ?? 'Deutschland' ), 0, 100 ),
        'geburtsdatum'    => $geburtsdatum,
        'verstorben'      => isset( $post['verstorben'] ) ? 1 : 0,
        'dod'             => $dod,
        'spitzname'       => substr( sanitize_text_field( $post['spitzname']       ?? '' ), 0, 100 ),
        'private_email'   => substr( sanitize_email( $post['private_email']   ?? '' ), 0, 200 ),
        'oeffentliche_email' => substr( sanitize_email( $post['oeffentliche_email'] ?? '' ), 0, 200 ),
        'bild'            => sanitize_text_field( $post['bild']            ?? '' ),
        'partei'          => substr( sanitize_text_field( $post['partei']         ?? '' ), 0, 50 ),
        'aufgabe_partei'  => substr( sanitize_text_field( $post['aufgabe_partei'] ?? '' ), 0, 100 ),
        'parlament'       => sanitize_text_field( $post['parlament']       ?? '' ),
        'parlament_name'  => substr( sanitize_text_field( $post['parlament_name'] ?? '' ), 0, 50 ),
        'status_aktiv'    => ! empty( $post['status_aktiv'] ) ? intval( $post['status_aktiv'] ) : 0,
        'straftat'        => sanitize_textarea_field( $post['straftat']   ?? '' ),
        'urteil'          => substr( sanitize_text_field( $post['urteil']         ?? '' ), 0, 200 ),
        'link_quelle'     => esc_url_raw( $post['link_quelle']     ?? '' ),
        'aktenzeichen'    => substr( sanitize_text_field( $post['aktenzeichen']   ?? '' ), 0, 50 ),
        'bemerkung_person' => substr( sanitize_textarea_field( $post['bemerkung_person'] ?? '' ), 0, 500 ),
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
