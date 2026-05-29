<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // GET /dinners — lista de próximos 4Dinners
    register_rest_route( VX_REST_NAMESPACE, '/dinners', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_dinners_listar',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /dinners/{id}/interes — registrar interés
    register_rest_route( VX_REST_NAMESPACE, '/dinners/(?P<id>\d+)/interes', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_dinner_interes_agregar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

    // DELETE /dinners/{id}/interes — retirar interés
    register_rest_route( VX_REST_NAMESPACE, '/dinners/(?P<id>\d+)/interes', [
        'methods'             => 'DELETE',
        'callback'            => 'vx_rest_dinner_interes_retirar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
        ],
    ] );

} );

function vx_rest_dinners_listar(): WP_REST_Response
{
    $user_id  = get_current_user_id();
    $user     = VX_User::get( $user_id );
    $dinners  = VX_Dinner::get_upcoming();

    $result = [];

    foreach ( $dinners as $dinner ) {
        $asignados  = $dinner->get_asignados();
        $interesados = $dinner->get_interesados();

        $result[] = [
            'id'              => $dinner->get_id(),
            'ciudad'          => $dinner->get_ciudad(),
            'pais'            => $dinner->get_pais(),
            'fecha'           => $dinner->get_fecha(),
            'restaurante'     => $dinner->get_restaurante(),
            'cupos_disponibles' => max( 0, 4 - count( $asignados ) ),
            'estado'          => $dinner->get_estado(),
            'user_asignado'   => in_array( $user_id, $asignados, true ),
            'user_interesado' => in_array( $user_id, $interesados, true ),
        ];
    }

    return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
}

function vx_rest_dinner_interes_agregar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id   = get_current_user_id();
    $dinner_id = $request->get_param( 'id' );

    $dinner = VX_Dinner::get( $dinner_id );
    if ( ! $dinner ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'dinner_no_encontrado' ], 404 );
    }

    if ( 'abierto' !== $dinner->get_estado() ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'dinner_no_disponible' ], 409 );
    }

    $dinner->add_interest( $user_id );

    // Actualizar user meta
    $user_intereses   = (array) get_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, true );
    $user_intereses[] = $dinner_id;
    update_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, array_unique( $user_intereses ) );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_dinner_interes_retirar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id   = get_current_user_id();
    $dinner_id = $request->get_param( 'id' );

    $dinner = VX_Dinner::get( $dinner_id );
    if ( ! $dinner ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'dinner_no_encontrado' ], 404 );
    }

    $dinner->remove_interest( $user_id );

    $user_intereses = array_diff( (array) get_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, true ), [ $dinner_id ] );
    update_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, array_values( $user_intereses ) );

    return new WP_REST_Response( [ 'success' => true ], 200 );
}
