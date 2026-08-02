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

    // Reacción (aplauso) a un artículo — toggle. Requiere sesión iniciada.
    register_rest_route( VX_REST_NAMESPACE, '/blog/(?P<id>\d+)/reaccion', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_blog_reaccion',
        'permission_callback' => function () { return is_user_logged_in(); },
    ] );
} );

/**
 * Toggle de reacción (aplauso) de un artículo.
 * Guarda los IDs de usuario en el post meta 'vx_reacciones'.
 */
function vx_rest_blog_reaccion( WP_REST_Request $request ): WP_REST_Response
{
    $post_id = absint( $request->get_param( 'id' ) );
    $post    = get_post( $post_id );
    if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_encontrado' ], 404 );
    }

    $user_id = get_current_user_id();
    $users   = (array) ( get_post_meta( $post_id, 'vx_reacciones', true ) ?: [] );
    $users   = array_map( 'intval', $users );

    $pos = array_search( $user_id, $users, true );
    if ( false !== $pos ) {
        unset( $users[ $pos ] );
        $reacted = false;
    } else {
        $users[] = $user_id;
        $reacted = true;
    }
    $users = array_values( array_unique( $users ) );
    update_post_meta( $post_id, 'vx_reacciones', $users );

    return new WP_REST_Response( [ 'success' => true, 'reacted' => $reacted, 'count' => count( $users ) ], 200 );
}

/** Helpers de reacciones para las plantillas. */
function vx_blog_reacciones_count( int $post_id ): int
{
    return count( (array) ( get_post_meta( $post_id, 'vx_reacciones', true ) ?: [] ) );
}
function vx_blog_usuario_reacciono( int $post_id, int $user_id ): bool
{
    if ( ! $user_id ) return false;
    $users = array_map( 'intval', (array) ( get_post_meta( $post_id, 'vx_reacciones', true ) ?: [] ) );
    return in_array( $user_id, $users, true );
}

/**
 * Comentarios del blog:
 *  - Siempre abiertos en artículos (post), sin importar el estado guardado.
 *  - Solo miembros con sesión pueden comentar.
 *  - Comentarios de miembros logeados se aprueban automáticamente.
 */
add_filter( 'comments_open', function ( $open, $post_id ) {
    return ( 'post' === get_post_type( $post_id ) ) ? true : $open;
}, 10, 2 );

add_filter( 'pre_comment_approved', function ( $approved, $commentdata ) {
    if ( ! empty( $commentdata['user_id'] ) ) {
        return 1; // Miembro logeado → aprobado directo.
    }
    return $approved;
}, 10, 2 );

add_filter( 'preprocess_comment', function ( $commentdata ) {
    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id && 'post' === get_post_type( $post_id ) && ! is_user_logged_in() ) {
        wp_die(
            esc_html__( 'Debes iniciar sesión o inscribirte en Vitrinexo para comentar.', 'vitrinexo' ),
            esc_html__( 'Comentario no permitido', 'vitrinexo' ),
            [ 'response' => 403, 'back_link' => true ]
        );
    }
    return $commentdata;
} );

function vx_rest_blog_crear( WP_REST_Request $request ): WP_REST_Response
{
    $user_id  = get_current_user_id();
    $titulo   = sanitize_text_field( (string) $request->get_param( 'titulo' ) );
    $contenido= (string) $request->get_param( 'contenido' );
    $cat_id   = absint( $request->get_param( 'categoria' ) );

    if ( '' === trim( $titulo ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'titulo_requerido', 'message' => 'El título es obligatorio.' ], 400 );
    }
    if ( '' === trim( wp_strip_all_tags( $contenido ) ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'contenido_requerido', 'message' => 'El contenido es obligatorio.' ], 400 );
    }

    $post_id = wp_insert_post( [
        'post_type'     => 'post',
        'post_status'   => 'pending',
        'post_author'   => $user_id,
        'post_title'    => $titulo,
        'post_content'  => wp_kses_post( $contenido ),
        'comment_status'=> 'open',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_bd' ], 500 );
    }

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
