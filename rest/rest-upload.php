<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // POST /upload — sube, comprime, convierte a WebP y devuelve attachment_id + url
    register_rest_route( VX_REST_NAMESPACE, '/upload', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_upload',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'tipo'     => [ 'required' => true,  'sanitize_callback' => 'sanitize_key' ],
            'contexto' => [ 'required' => false, 'sanitize_callback' => 'absint' ],
        ],
    ] );

} );

/**
 * Procesa una imagen antes de subirla al media library:
 *  1. Valida MIME y tamaño (hasta 15 MB — se comprimirá)
 *  2. Redimensiona según el tipo (foto/logo/banner)
 *  3. Convierte a WebP (si el servidor lo soporta) o JPEG comprimido
 *  4. Sube al media library y asigna al usuario/empresa
 *
 * Dimensiones máximas por tipo:
 *  - foto   : 600 × 600 px
 *  - logo   : 400 × 400 px
 *  - banner : 1400 × 400 px
 */
function vx_rest_upload( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $tipo    = $request->get_param( 'tipo' );
    $files   = $request->get_file_params();

    $tipos_validos = [ 'foto', 'logo', 'banner' ];
    if ( ! in_array( $tipo, $tipos_validos, true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'tipo_invalido' ], 400 );
    }

    if ( empty( $files['file'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'archivo_requerido' ], 400 );
    }

    $file      = $files['file'];
    $mime_type = mime_content_type( $file['tmp_name'] );

    $allowed_mimes = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
    if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'formato_invalido' ], 400 );
    }

    // Límite generoso antes de comprimir (15 MB)
    $max_size = 15 * 1024 * 1024;
    if ( $file['size'] > $max_size ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'archivo_muy_grande' ], 400 );
    }

    // ── Procesar imagen (redimensionar + comprimir + WebP) ────────────────────
    $processed = vx_process_image( $file['tmp_name'], $mime_type, $tipo );
    if ( is_wp_error( $processed ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => $processed->get_error_code() ], 500 );
    }

    // $processed = [ 'tmp_path' => '...', 'mime' => 'image/webp', 'ext' => 'webp' ]
    $upload_name = pathinfo( $file['name'], PATHINFO_FILENAME ) . '.' . $processed['ext'];

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_sideload( [
        'name'     => $upload_name,
        'type'     => $processed['mime'],
        'tmp_name' => $processed['tmp_path'],
        'error'    => 0,
        'size'     => filesize( $processed['tmp_path'] ),
    ], 0 );

    // Limpiar archivo temporal
    if ( file_exists( $processed['tmp_path'] ) ) {
        @unlink( $processed['tmp_path'] );
    }

    if ( is_wp_error( $attachment_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => $attachment_id->get_error_code() ], 500 );
    }

    // ── Asignar al usuario o empresa ─────────────────────────────────────────
    $size_map = [ 'foto' => 'vx-avatar', 'logo' => 'vx-logo', 'banner' => 'vx-banner' ];
    $meta_map = [ 'foto' => VX_User_Meta::FOTO, 'logo' => 'vx_logo', 'banner' => 'vx_banner' ];

    if ( 'foto' === $tipo ) {
        update_user_meta( $user_id, $meta_map['foto'], $attachment_id );
    } else {
        $empresa_id = (int) $request->get_param( 'contexto' );
        if ( $empresa_id ) {
            $empresa = get_post( $empresa_id );
            if ( $empresa && 'vx_empresa' === $empresa->post_type && (int) $empresa->post_author === $user_id ) {
                update_post_meta( $empresa_id, $meta_map[ $tipo ], $attachment_id );
            }
        }
    }

    $url = wp_get_attachment_image_url( $attachment_id, $size_map[ $tipo ] ) ?: wp_get_attachment_url( $attachment_id );

    return new WP_REST_Response( [
        'success'       => true,
        'attachment_id' => $attachment_id,
        'url'           => $url,
    ], 201 );
}

/**
 * Redimensiona y convierte una imagen a WebP (o JPEG como fallback).
 *
 * @param string $src_path   Ruta al archivo temporal original
 * @param string $mime_type  MIME type de la imagen original
 * @param string $tipo       Contexto de uso: 'foto' | 'logo' | 'banner'
 * @return array|WP_Error   Array con 'tmp_path', 'mime', 'ext' o WP_Error
 */
function vx_process_image( string $src_path, string $mime_type, string $tipo ): array|WP_Error
{
    // Dimensiones máximas por contexto
    $max_dims = [
        'foto'   => [ 600,  600  ],
        'logo'   => [ 400,  400  ],
        'banner' => [ 1400, 400  ],
    ];

    [ $max_w, $max_h ] = $max_dims[ $tipo ] ?? [ 1200, 1200 ];

    // Cargar imagen con GD
    $src = match ( $mime_type ) {
        'image/jpeg' => @imagecreatefromjpeg( $src_path ),
        'image/png'  => @imagecreatefrompng( $src_path ),
        'image/webp' => @imagecreatefromwebp( $src_path ),
        'image/gif'  => @imagecreatefromgif( $src_path ),
        default      => false,
    };

    if ( ! $src ) {
        // GD no pudo cargar — usar el archivo original sin procesar
        return [
            'tmp_path' => $src_path,
            'mime'     => $mime_type,
            'ext'      => pathinfo( $src_path, PATHINFO_EXTENSION ) ?: 'jpg',
        ];
    }

    $orig_w = imagesx( $src );
    $orig_h = imagesy( $src );

    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min( $max_w / $orig_w, $max_h / $orig_h, 1.0 ); // nunca agrandar
    $new_w = (int) round( $orig_w * $ratio );
    $new_h = (int) round( $orig_h * $ratio );

    // Crear imagen de destino
    $dst = imagecreatetruecolor( $new_w, $new_h );

    // Preservar transparencia para PNG/WebP
    if ( in_array( $mime_type, [ 'image/png', 'image/webp' ], true ) ) {
        imagealphablending( $dst, false );
        imagesavealpha( $dst, true );
        $transparent = imagecolorallocatealpha( $dst, 255, 255, 255, 127 );
        imagefilledrectangle( $dst, 0, 0, $new_w, $new_h, $transparent );
    }

    imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
    imagedestroy( $src );

    // Guardar en archivo temporal
    $tmp_path = tempnam( sys_get_temp_dir(), 'vx_img_' );
    $quality  = 85;

    if ( function_exists( 'imagewebp' ) ) {
        // Convertir a WebP (mejor compresión y calidad que JPEG)
        $ok = imagewebp( $dst, $tmp_path, $quality );
        $out_mime = 'image/webp';
        $out_ext  = 'webp';
    } else {
        // Fallback: JPEG comprimido
        $ok = imagejpeg( $dst, $tmp_path, $quality );
        $out_mime = 'image/jpeg';
        $out_ext  = 'jpg';
    }

    imagedestroy( $dst );

    if ( ! $ok ) {
        return new WP_Error( 'image_process_failed', 'No se pudo procesar la imagen.' );
    }

    return [
        'tmp_path' => $tmp_path,
        'mime'     => $out_mime,
        'ext'      => $out_ext,
    ];
}
