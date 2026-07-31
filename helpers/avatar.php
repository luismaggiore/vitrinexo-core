<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Genera un avatar placeholder (círculo con el color de Vitrinexo + iniciales)
 * como data-URI SVG. Sirve como `src` de cualquier <img>, evitando enlaces rotos
 * cuando la persona no ha subido foto.
 *
 * @param string $initials Iniciales (se usan las primeras 2 letras).
 * @return string data:image/svg+xml;base64,...
 */
function vx_avatar_placeholder_datauri( string $initials ): string {
    $initials = mb_strtoupper( mb_substr( trim( $initials ), 0, 2 ) );
    if ( '' === $initials ) {
        $initials = 'V';
    }
    return 'data:image/svg+xml;base64,' . base64_encode( vx_avatar_svg( $initials ) );
}

/** SVG del avatar (círculo teal + iniciales). */
function vx_avatar_svg( string $initials ): string {
    $initials = mb_strtoupper( mb_substr( trim( $initials ), 0, 2 ) );
    if ( '' === $initials ) {
        $initials = 'V';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
         . '<rect width="200" height="200" fill="#00aeb8"/>'
         . '<text x="100" y="100" dy="0.35em" text-anchor="middle" '
         . 'font-family="Arial, Helvetica, sans-serif" font-size="88" font-weight="700" fill="#ffffff">'
         . htmlspecialchars( $initials, ENT_QUOTES, 'UTF-8' )
         . '</text></svg>';
}

/**
 * URL real (no data-URI) del avatar placeholder. Se usa como `src` porque
 * esc_url() elimina los data-URI. Apunta a un endpoint admin-ajax que emite el SVG.
 */
function vx_avatar_placeholder_url( string $initials ): string {
    $initials = mb_strtoupper( mb_substr( trim( $initials ), 0, 2 ) );
    if ( '' === $initials ) {
        $initials = 'V';
    }
    return add_query_arg(
        [ 'action' => 'vx_avatar', 'i' => rawurlencode( $initials ) ],
        admin_url( 'admin-ajax.php' )
    );
}

/** Endpoint que emite el SVG del avatar (para usuarios sin foto). */
function vx_avatar_ajax_render() {
    $ini = isset( $_GET['i'] ) ? (string) wp_unslash( $_GET['i'] ) : 'V';
    $ini = preg_replace( '/[^\p{L}]/u', '', $ini );
    nocache_headers();
    header( 'Content-Type: image/svg+xml; charset=UTF-8' );
    header( 'Cache-Control: public, max-age=86400' );
    echo vx_avatar_svg( $ini );
    exit;
}
add_action( 'wp_ajax_vx_avatar', 'vx_avatar_ajax_render' );
add_action( 'wp_ajax_nopriv_vx_avatar', 'vx_avatar_ajax_render' );

/**
 * Iniciales a partir de nombre y apellido (o de un nombre completo).
 */
function vx_iniciales_de( string $nombre, string $apellido = '' ): string {
    $n = trim( $nombre );
    $a = trim( $apellido );
    if ( '' !== $n && '' !== $a ) {
        return mb_strtoupper( mb_substr( $n, 0, 1 ) . mb_substr( $a, 0, 1 ) );
    }
    $partes = preg_split( '/\s+/', trim( $n . ' ' . $a ) );
    $partes = array_values( array_filter( $partes ) );
    if ( count( $partes ) >= 2 ) {
        return mb_strtoupper( mb_substr( $partes[0], 0, 1 ) . mb_substr( $partes[1], 0, 1 ) );
    }
    if ( count( $partes ) === 1 ) {
        return mb_strtoupper( mb_substr( $partes[0], 0, 2 ) );
    }
    return 'V';
}
