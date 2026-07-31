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
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
         . '<rect width="200" height="200" fill="#00aeb8"/>'
         . '<text x="100" y="100" dy="0.35em" text-anchor="middle" '
         . 'font-family="Arial, Helvetica, sans-serif" font-size="88" font-weight="700" fill="#ffffff">'
         . htmlspecialchars( $initials, ENT_QUOTES, 'UTF-8' )
         . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

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
