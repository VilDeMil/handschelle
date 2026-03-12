<?php
/**
 * Die-Handschelle 2.0 A – Bild-Upload & Resize auf max. 450px Höhe
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

class Handschelle_Image_Handler {

    public static function handle_upload_and_resize( $file_input_name ) {
        if ( empty( $_FILES[ $file_input_name ]['name'] ) ) return 0;
        if ( ! function_exists( 'wp_handle_upload' ) ) require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload( $_FILES[ $file_input_name ], array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) || empty( $upload['file'] ) ) return 0;

        $file_path = $upload['file'];
        self::resize_to_height( $file_path, 450 );

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name( basename( $file_path ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id   = wp_insert_attachment( $attachment, $file_path );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return $attach_id;
    }

    public static function resize_to_height( $file_path, $target_height = 450 ) {
        $info = @getimagesize( $file_path );
        if ( ! $info ) return false;
        list( $orig_w, $orig_h, $type ) = $info;
        if ( $orig_h <= $target_height ) return true;

        $ratio = $target_height / $orig_h;
        $new_w = (int) round( $orig_w * $ratio );
        $new_h = $target_height;

        switch ( $type ) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg( $file_path ); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng( $file_path );  break;
            case IMAGETYPE_GIF:  $src = @imagecreatefromgif( $file_path );  break;
            case IMAGETYPE_WEBP: $src = @imagecreatefromwebp( $file_path ); break;
            default: return false;
        }
        if ( ! $src ) return false;

        $dst = imagecreatetruecolor( $new_w, $new_h );
        if ( $type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF ) {
            imagealphablending( $dst, false );
            imagesavealpha( $dst, true );
            imagefilledrectangle( $dst, 0, 0, $new_w, $new_h, imagecolorallocatealpha( $dst, 0, 0, 0, 127 ) );
        }
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
        imagedestroy( $src );

        switch ( $type ) {
            case IMAGETYPE_JPEG: imagejpeg( $dst, $file_path, 90 ); break;
            case IMAGETYPE_PNG:  imagepng( $dst, $file_path, 6 );   break;
            case IMAGETYPE_GIF:  imagegif( $dst, $file_path );       break;
            case IMAGETYPE_WEBP: imagewebp( $dst, $file_path, 90 );  break;
        }
        imagedestroy( $dst );
        return true;
    }
}
