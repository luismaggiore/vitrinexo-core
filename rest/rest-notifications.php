<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // GET /notificaciones — listado de notificaciones
    register_rest_route( VX_REST_NAMESPACE, '/notificaciones', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_notificaciones_listar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'pagina' => [ 'required' => false, 'default' => 1, 'sanitize_callback' => 'absint' ],
        ],
    ] );

    // GET /notificaciones/unread-count — contador de no leídas
    register_rest_route( VX_REST_NAMESPACE, '/notificaciones/unread-count', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_notificaciones_unread_count',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /notificaciones/leer-todas — marcar todas como leídas
    register_rest_route( VX_REST_NAMESPACE, '/notificaciones/leer-todas', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_notificaciones_leer_todas',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /notificaciones/{id}/leer — marcar una como leída
    register_rest_route( VX_REST_NAMESPACE, '/notificaciones/(?P<id>\d+)/leer', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_notificacion_leer',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

} );

function vx_rest_notificaciones_listar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $pagina  = max( 1, (int) $request->get_param( 'pagina' ) );

    $result = VX_Notification::get_for_user( $user_id, $pagina );

    return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
}

function vx_rest_notificaciones_unread_count(): WP_REST_Response
{
    $user_id = get_current_user_id();
    $count   = VX_Notification::count_unread( $user_id );

    return new WP_REST_Response( [ 'success' => true, 'count' => $count ], 200 );
}

function vx_rest_notificaciones_leer_todas(): WP_REST_Response
{
    $user_id = get_current_user_id();
    VX_Notification::mark_all_read( $user_id );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_notificacion_leer( WP_REST_Request $request ): WP_REST_Response
{
    $user_id  = get_current_user_id();
    $notif_id = $request->get_param( 'id' );

    $post = get_post( $notif_id );
    if ( ! $post || 'vx_notification' !== $post->post_type ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'notificacion_no_encontrada' ], 404 );
    }

    // Verificar que pertenece al usuario actual
    if ( (int) get_post_meta( $notif_id, 'vx_user_id', true ) !== $user_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_autorizado' ], 403 );
    }

    VX_Notification::mark_read( $notif_id );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}
