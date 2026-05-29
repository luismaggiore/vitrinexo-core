<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // GET /onboarding/estado — devuelve el estado actual del onboarding
    register_rest_route( VX_REST_NAMESPACE, '/onboarding/estado', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_onboarding_estado',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /onboarding/completar — finaliza el onboarding (activa comunidades, plan, etc.)
    register_rest_route( VX_REST_NAMESPACE, '/onboarding/completar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_onboarding_completar',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /onboarding/paso — guarda un paso del onboarding
    register_rest_route( VX_REST_NAMESPACE, '/onboarding/paso', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_onboarding_paso',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'paso'    => [ 'required' => true,  'sanitize_callback' => 'absint' ],
            'datos'   => [ 'required' => true ],
            'partial' => [ 'required' => false, 'default' => false ],
        ],
    ] );

} );

function vx_rest_onboarding_completar(): WP_REST_Response
{
    $user_id = get_current_user_id();
    VX_Onboarding::complete( $user_id );
    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function vx_rest_onboarding_estado(): WP_REST_Response
{
    $user_id = get_current_user_id();
    $state   = VX_Onboarding::get_state( $user_id );

    return new WP_REST_Response( [ 'success' => true, 'data' => $state ], 200 );
}

function vx_rest_onboarding_paso( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $paso    = $request->get_param( 'paso' );
    $datos   = $request->get_param( 'datos' );
    $partial = (bool) $request->get_param( 'partial' );

    if ( ! is_array( $datos ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'datos_invalidos' ], 400 );
    }

    $result = VX_Onboarding::save_step( $user_id, $paso, $datos, $partial );

    if ( ! empty( $result['errors'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'errors' => $result['errors'] ], 400 );
    }

    return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
}
