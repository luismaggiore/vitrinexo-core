<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // POST /favoritos/{user_id} — guardar usuario como favorito
    register_rest_route( VX_REST_NAMESPACE, '/favoritos/(?P<user_id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_favorito_guardar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'user_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

    // DELETE /favoritos/{user_id} — eliminar de favoritos
    register_rest_route( VX_REST_NAMESPACE, '/favoritos/(?P<user_id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'vx_rest_favorito_eliminar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'user_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

    // GET /favoritos — lista de favoritos del usuario actual
    register_rest_route( VX_REST_NAMESPACE, '/favoritos', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_favoritos_listar',
        'permission_callback' => 'is_user_logged_in',
    ] );

} );

function vx_rest_favorito_guardar( WP_REST_Request $request ): WP_REST_Response
{
    $viewer_id  = get_current_user_id();
    $target_id  = $request->get_param( 'user_id' );

    if ( $viewer_id === $target_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_autofavorito' ], 400 );
    }

    $target = VX_User::get( $target_id );
    if ( ! $target || ! $target->is_active() ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'usuario_no_encontrado' ], 404 );
    }

    $viewer = VX_User::get( $viewer_id );
    if ( ! $viewer ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_interno' ], 500 );
    }

    $viewer->add_favorito( $target_id );

    do_action( 'vx_user_favorited', $target_id, $viewer_id );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_favorito_eliminar( WP_REST_Request $request ): WP_REST_Response
{
    $viewer_id = get_current_user_id();
    $target_id = $request->get_param( 'user_id' );

    $viewer = VX_User::get( $viewer_id );
    if ( ! $viewer ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_interno' ], 500 );
    }

    $viewer->remove_favorito( $target_id );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_favoritos_listar(): WP_REST_Response
{
    $viewer_id = get_current_user_id();
    $viewer    = VX_User::get( $viewer_id );

    if ( ! $viewer ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'error_interno' ], 500 );
    }

    $favoritos_ids = $viewer->get_favoritos();
    $members       = [];

    foreach ( $favoritos_ids as $uid ) {
        $user = VX_User::get( (int) $uid );
        if ( $user && $user->is_active() ) {
            $members[] = $user->to_card_array();
        }
    }

    return new WP_REST_Response( [ 'success' => true, 'data' => $members ], 200 );
}
