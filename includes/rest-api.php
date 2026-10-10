<?php
/**
 * Authenticated REST API for the Handschelle custom records table.
 * All routes require a WordPress user with manage_options (e.g. Application Password).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Handschelle_REST_API {

	const API_NAMESPACE = 'handschelle/v1';

	/** Register routes after WordPress has initialized its REST server. */
	public static function register_routes() {
		register_rest_route( self::API_NAMESPACE, '/entries', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'list_entries' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
				'args'                => array(
					'freigegeben' => array( 'default' => 'all', 'enum' => array( '0', '1', 'all' ) ),
					'search'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'partei'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'limit'       => array( 'default' => 100, 'sanitize_callback' => 'absint' ),
					'offset'      => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_entry' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			),
		) );

		register_rest_route( self::API_NAMESPACE, '/entries/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_entry' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'update_entry' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			),
		) );
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	public static function list_entries( $request ) {
		$limit = min( 500, max( 1, (int) $request->get_param( 'limit' ) ) );
		$filter = $request->get_param( 'freigegeben' );
		if ( ! in_array( (string) $filter, array( '0', '1', 'all' ), true ) ) $filter = 'all';
		$rows = Handschelle_Database::get_all( array(
			'freigegeben' => $filter === 'all' ? 'all' : (int) $filter,
			'search'      => (string) $request->get_param( 'search' ),
			'partei'      => (string) $request->get_param( 'partei' ),
			'limit'       => $limit,
			'offset'      => (int) $request->get_param( 'offset' ),
		) );
		return rest_ensure_response( array_map( array( __CLASS__, 'public_record' ), $rows ) );
	}

	public static function get_entry( $request ) {
		$entry = Handschelle_Database::get_one( (int) $request['id'] );
		if ( ! $entry ) return new WP_Error( 'handschelle_not_found', 'Eintrag nicht gefunden.', array( 'status' => 404 ) );
		return rest_ensure_response( self::public_record( $entry ) );
	}

	public static function create_entry( $request ) {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) return new WP_Error( 'handschelle_invalid_body', 'JSON-Objekt erwartet.', array( 'status' => 400 ) );
		if ( empty( $input['name'] ) || empty( $input['straftat'] ) ) {
			return new WP_Error( 'handschelle_required_fields', 'name und straftat sind erforderlich.', array( 'status' => 400 ) );
		}
		if ( isset( $input['status_straftat'] ) && ! in_array( $input['status_straftat'], handschelle_status_straftat_options(), true ) ) {
			return new WP_Error( 'handschelle_invalid_status', 'Ungültiger Verfahrensstatus.', array( 'status' => 400 ) );
		}

		// Approval is intentionally impossible through this API: all submissions remain pending.
		unset( $input['freigegeben'], $input['id'], $input['erstellt_am'], $input['geaendert_am'], $input['private_email'] );
		$input['datum_eintrag'] = $input['datum_eintrag'] ?? current_time( 'Y-m-d' );
		$data = handschelle_sanitize_entry( $input );
		unset( $data['private_email'] );
		$id = Handschelle_Database::insert( $data );
		if ( ! $id ) return new WP_Error( 'handschelle_insert_failed', 'Eintrag konnte nicht gespeichert werden.', array( 'status' => 500 ) );

		$entry = Handschelle_Database::get_one( $id );
		$response = new WP_REST_Response( self::public_record( $entry ), 201 );
		$response->header( 'Location', rest_url( self::API_NAMESPACE . '/entries/' . $id ) );
		return $response;
	}

	public static function update_entry( $request ) {
		$id = (int) $request['id'];
		$current = Handschelle_Database::get_one( $id );
		if ( ! $current ) return new WP_Error( 'handschelle_not_found', 'Eintrag nicht gefunden.', array( 'status' => 404 ) );
		$input = $request->get_json_params();
		if ( ! is_array( $input ) || ! $input ) return new WP_Error( 'handschelle_invalid_body', 'Nicht-leeres JSON-Objekt erwartet.', array( 'status' => 400 ) );

		// Never expose private email or allow API callers to approve/unapprove records.
		unset( $input['freigegeben'], $input['id'], $input['erstellt_am'], $input['geaendert_am'], $input['private_email'] );
		$allowed = array(
			'datum_eintrag', 'name', 'beruf', 'geburtsort', 'geburtsland', 'geburtsdatum', 'verstorben', 'dod',
			'spitzname', 'oeffentliche_email', 'bild', 'partei', 'aufgabe_partei', 'parlament', 'parlament_name',
			'status_aktiv', 'straftat', 'urteil', 'link_quelle', 'aktenzeichen', 'bemerkung_person',
			'bemerkung_straftat', 'bemerkung', 'status_straftat', 'sm_facebook', 'sm_youtube', 'sm_personal',
			'sm_twitter', 'sm_homepage', 'sm_wikipedia', 'sm_sonstige', 'sm_linkedin', 'sm_xing', 'sm_truth_social',
		);
		$input = array_intersect_key( $input, array_fill_keys( $allowed, true ) );
		if ( ! $input ) return new WP_Error( 'handschelle_no_editable_fields', 'Keine bearbeitbaren Felder übergeben.', array( 'status' => 400 ) );
		if ( isset( $input['status_straftat'] ) && ! in_array( $input['status_straftat'], handschelle_status_straftat_options(), true ) ) {
			return new WP_Error( 'handschelle_invalid_status', 'Ungültiger Verfahrensstatus.', array( 'status' => 400 ) );
		}

		$merged = array_merge( (array) $current, $input );
		$sanitized = handschelle_sanitize_entry( $merged );
		$data = array_intersect_key( $sanitized, array_fill_keys( array_keys( $input ), true ) );
		$result = Handschelle_Database::update( $id, $data );
		if ( $result === false ) return new WP_Error( 'handschelle_update_failed', 'Eintrag konnte nicht aktualisiert werden.', array( 'status' => 500 ) );

		return rest_ensure_response( self::public_record( Handschelle_Database::get_one( $id ) ) );
	}

	/** Remove personal email from all REST responses. */
	private static function public_record( $record ) {
		$data = (array) $record;
		unset( $data['private_email'] );
		return $data;
	}
}

add_action( 'rest_api_init', array( 'Handschelle_REST_API', 'register_routes' ) );
