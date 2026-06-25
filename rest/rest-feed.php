<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // POST /feed — crear publicación
    register_rest_route( VX_REST_NAMESPACE, '/feed', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_feed_crear',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'tipo'      => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'contenido' => [ 'required' => true,  'sanitize_callback' => 'sanitize_textarea_field' ],
        ],
    ] );

    // DELETE /feed/{id} — eliminar propia publicación
    register_rest_route( VX_REST_NAMESPACE, '/feed/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'vx_rest_feed_eliminar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

    // POST /feed/{id}/comentar — añadir comentario
    register_rest_route( VX_REST_NAMESPACE, '/feed/(?P<id>\d+)/comentar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_feed_comentar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id'    => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            'texto' => [ 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
        ],
    ] );

    // GET /feed/{id}/comentarios — obtener todos los comentarios
    register_rest_route( VX_REST_NAMESPACE, '/feed/(?P<id>\d+)/comentarios', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_feed_comentarios',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

} );

// ── Crear publicación ─────────────────────────────────────────────────────────

function vx_rest_feed_crear( WP_REST_Request $request ): WP_REST_Response
{
    $user_id   = get_current_user_id();
    $tipo      = $request->get_param( 'tipo' );
    $contenido = trim( $request->get_param( 'contenido' ) );

    if ( ! in_array( $tipo, [ 'ofrece', 'busca' ], true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'tipo_invalido' ], 400 );
    }

    if ( empty( $contenido ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'contenido_vacio' ], 400 );
    }

    $user = VX_User::get( $user_id );
    if ( ! $user || 'activo' !== $user->get_estado() ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_permiso' ], 403 );
    }

    $post_id = wp_insert_post( [
        'post_type'     => 'vx_publicacion',
        'post_status'   => 'publish',
        'post_content'  => $contenido,
        'post_author'   => $user_id,
        'comment_status'=> 'open',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_bd' ], 500 );
    }

    update_post_meta( $post_id, 'vx_pub_tipo', $tipo );

    return new WP_REST_Response( [ 'success' => true, 'id' => $post_id ], 201 );
}

// ── Eliminar publicación ──────────────────────────────────────────────────────

function vx_rest_feed_eliminar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $post_id = (int) $request->get_param( 'id' );

    $post = get_post( $post_id );
    if ( ! $post || 'vx_publicacion' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_encontrada' ], 404 );
    }

    if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_permiso' ], 403 );
    }

    wp_trash_post( $post_id );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

// ── Todos los comentarios ─────────────────────────────────────────────────────

function vx_rest_feed_comentarios( WP_REST_Request $request ): WP_REST_Response
{
    $post_id = (int) $request->get_param( 'id' );

    $post = get_post( $post_id );
    if ( ! $post || 'vx_publicacion' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_encontrada' ], 404 );
    }

    $comments = get_comments( [
        'post_id' => $post_id,
        'status'  => 'approve',
        'orderby' => 'comment_date',
        'order'   => 'ASC',
    ] );

    $data = [];
    foreach ( $comments as $c ) {
        $c_user = VX_User::get( (int) $c->user_id );
        $data[] = [
            'autor'      => $c->comment_author,
            'foto'       => $c_user ? $c_user->get_foto_url( 'vx-avatar' ) : '',
            'perfil_url' => $c_user ? home_url( '/perfil/' . $c_user->get_slug() . '/' ) : '',
            'texto'      => $c->comment_content,
            'fecha'      => date_i18n( 'j M Y', strtotime( $c->comment_date ) ),
        ];
    }

    return new WP_REST_Response( [ 'success' => true, 'comments' => $data ], 200 );
}

// ── Comentar ──────────────────────────────────────────────────────────────────

function vx_rest_feed_comentar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $post_id = (int) $request->get_param( 'id' );
    $texto   = trim( $request->get_param( 'texto' ) );

    $post = get_post( $post_id );
    if ( ! $post || 'vx_publicacion' !== $post->post_type || 'publish' !== $post->post_status ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'publicacion_no_encontrada' ], 404 );
    }

    if ( empty( $texto ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'texto_vacio' ], 400 );
    }

    $user = VX_User::get( $user_id );
    if ( ! $user || 'activo' !== $user->get_estado() ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_permiso' ], 403 );
    }

    $wp_user = get_userdata( $user_id );
    $comment_id = wp_insert_comment( [
        'comment_post_ID'      => $post_id,
        'comment_author'       => $user->get_nombre_completo(),
        'comment_author_email' => $wp_user->user_email,
        'comment_content'      => $texto,
        'comment_approved'     => 1,
        'user_id'              => $user_id,
        'comment_type'         => '',
    ] );

    if ( ! $comment_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_bd' ], 500 );
    }

    $autor_id = (int) $post->post_author;
    if ( $autor_id !== $user_id ) {
        do_action( 'vx_pub_comentario', $post_id, $user_id );
    }

    return new WP_REST_Response( [
        'success'    => true,
        'comment_id' => $comment_id,
        'autor'      => $user->get_nombre_completo(),
        'foto'       => $user->get_foto_url( 'vx-avatar' ),
        'perfil_url' => home_url( '/perfil/' . $user->get_slug() . '/' ),
        'texto'      => esc_html( $texto ),
        'fecha'      => date_i18n( 'j M Y', time() ),
    ], 201 );
}
