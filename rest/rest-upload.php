<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // POST /upload — sube una imagen y devuelve attachment_id + url
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

    $file = $files['file'];

    // Validar tipo MIME
    $allowed_mimes = [ 'image/jpeg', 'image/png', 'image/webp' ];
    $mime_type     = mime_content_type( $file['tmp_name'] );

    if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'formato_invalido' ], 400 );
    }

    // Validar tamaño (2MB máximo)
    $max_size = 2 * 1024 * 1024;
    if ( $file['size'] > $max_size ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'archivo_muy_grande' ], 400 );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Subir al media library de WordPress
    $attachment_id = media_handle_sideload( [
        'name'     => $file['name'],
        'type'     => $mime_type,
        'tmp_name' => $file['tmp_name'],
        'error'    => $file['error'],
        'size'     => $file['size'],
    ], 0 );

    if ( is_wp_error( $attachment_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => $attachment_id->get_error_code() ], 500 );
    }

    // Asignar el attachment al usuario/empresa según tipo y contexto
    $size_map = [
        'foto'   => 'vx-avatar',
        'logo'   => 'vx-logo',
        'banner' => 'vx-banner',
    ];

    $meta_map = [
        'foto'   => VX_User_Meta::FOTO,
        'logo'   => 'vx_logo',
        'banner' => 'vx_banner',
    ];

    if ( 'foto' === $tipo ) {
        update_user_meta( $user_id, $meta_map['foto'], $attachment_id );
    } else {
        // logo y banner pertenecen a la empresa (contexto = empresa post_id)
        $empresa_id = (int) $request->get_param( 'contexto' );
        if ( $empresa_id ) {
            $empresa = get_post( $empresa_id );
            if ( $empresa && 'vx_empresa' === $empresa->post_type && (int) $empresa->post_author === $user_id ) {
                update_post_meta( $empresa_id, $meta_map[ $tipo ], $attachment_id );
            }
        }
    }

    $size = $size_map[ $tipo ];
    $url  = wp_get_attachment_image_url( $attachment_id, $size );
    if ( ! $url ) {
        $url = wp_get_attachment_url( $attachment_id );
    }

    return new WP_REST_Response( [
        'success'       => true,
        'attachment_id' => $attachment_id,
        'url'           => $url,
    ], 201 );
}
