<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Blog de miembros con moderación.
 * Los miembros crean artículos que quedan "pending"; un admin los aprueba
 * (publish) o rechaza (draft) desde el panel de Vitrinexo.
 */
add_action( 'rest_api_init', function () {

    register_rest_route( VX_REST_NAMESPACE, '/blog/crear', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_blog_crear',
        'permission_callback' => function () { return is_user_logged_in(); },
    ] );

    register_rest_route( VX_REST_NAMESPACE, '/blog/(?P<id>\d+)/aprobar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_blog_aprobar',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
    ] );

    register_rest_route( VX_REST_NAMESPACE, '/blog/(?P<id>\d+)/rechazar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_blog_rechazar',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
    ] );
} );

function vx_rest_blog_crear( WP_REST_Request $request ): WP_REST_Response
{
    $user_id  = get_current_user_id();
    $titulo   = sanitize_text_field( (string) $request->get_param( 'titulo' ) );
    $contenido= (string) $request->get_param( 'contenido' );
    $cover_id = absint( $request->get_param( 'cover_id' ) );
    $cat_id   = absint( $request->get_param( 'categoria' ) );

    if ( '' === trim( $titulo ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'titulo_requerido', 'message' => 'El título es obligatorio.' ], 400 );
    }
    if ( '' === trim( wp_strip_all_tags( $contenido ) ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'contenido_requerido', 'message' => 'El contenido es obligatorio.' ], 400 );
    }
    if ( ! $cover_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'cover_requerido', 'message' => 'La imagen de portada es obligatoria.' ], 400 );
    }

    $post_id = wp_insert_post( [
        'post_type'    => 'post',
        'post_status'  => 'pending',
        'post_author'  => $user_id,
        'post_title'   => $titulo,
        'post_content' => wp_kses_post( $contenido ),
    ], true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_bd' ], 500 );
    }

    set_post_thumbnail( $post_id, $cover_id );
    if ( $cat_id ) {
        wp_set_post_categories( $post_id, [ $cat_id ] );
    }

    // Aviso a los administradores.
    if ( class_exists( 'VX_Mailer' ) ) {
        $autor = class_exists( 'VX_User' ) ? VX_User::get( $user_id ) : null;
        $nombre_autor = $autor ? $autor->get_nombre_completo() : 'Un miembro';
        foreach ( [ 'joao@vitrinexo.com', 'marcia@vitrinexo.com' ] as $admin ) {
            VX_Mailer::send(
                $admin,
                '[Vitrinexo] Nuevo artículo pendiente de aprobación',
                'blog_pendiente_admin',
                [
                    'nombre' => $nombre_autor,
                    'titulo' => $titulo,
                    'link'   => home_url( '/blog-moderacion/' ),
                ]
            );
        }
    }

    return new WP_REST_Response( [ 'success' => true, 'post_id' => $post_id ], 201 );
}

function vx_rest_blog_aprobar( WP_REST_Request $request ): WP_REST_Response
{
    $post_id = absint( $request->get_param( 'id' ) );
    $post    = get_post( $post_id );
    if ( ! $post || 'post' !== $post->post_type ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_encontrado' ], 404 );
    }

    wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
    vx_blog_notificar_autor( $post, 'blog_aprobado', '¡Tu artículo fue publicado en Vitrinexo!' );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_blog_rechazar( WP_REST_Request $request ): WP_REST_Response
{
    $post_id = absint( $request->get_param( 'id' ) );
    $post    = get_post( $post_id );
    if ( ! $post || 'post' !== $post->post_type ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_encontrado' ], 404 );
    }

    wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
    vx_blog_notificar_autor( $post, 'blog_rechazado', 'Sobre tu artículo en Vitrinexo' );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

/** Notifica al autor del artículo (aprobado/rechazado). */
function vx_blog_notificar_autor( WP_Post $post, string $template, string $subject ): void
{
    if ( ! class_exists( 'VX_Mailer' ) || ! class_exists( 'VX_User' ) ) {
        return;
    }
    $autor = VX_User::get( (int) $post->post_author );
    if ( ! $autor ) {
        return;
    }
    VX_Mailer::send(
        $autor->get_email(),
        $subject,
        $template,
        [
            'nombre' => $autor->get_nombre(),
            'titulo' => $post->post_title,
            'link'   => 'blog_aprobado' === $template ? get_permalink( $post ) : home_url( '/blog-nuevo/' ),
        ]
    );
}
