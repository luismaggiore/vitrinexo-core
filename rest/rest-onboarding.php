<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    register_rest_route( VX_REST_NAMESPACE, '/onboarding/estado', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_onboarding_estado',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /onboarding/completar — idempotente: no repite si ya completó
    register_rest_route( VX_REST_NAMESPACE, '/onboarding/completar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_onboarding_completar',
        'permission_callback' => 'is_user_logged_in',
    ] );

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

    // Fix: idempotente — no ejecutar de nuevo si ya completó
    if ( (bool) get_user_meta( $user_id, VX_User_Meta::ONBOARDING_COMPLETO, true ) ) {
        return new WP_REST_Response( [ 'success' => true, 'already_complete' => true ], 200 );
    }

    // Fix: verificar que pasos 2 y 3 tienen datos antes de completar
    $datos_2 = get_user_meta( $user_id, 'vx_onboarding_datos_2', true );
    $datos_3 = get_user_meta( $user_id, 'vx_onboarding_datos_3', true );
    if ( empty( $datos_2 ) || empty( $datos_3 ) ) {
        return new WP_REST_Response( [
            'success' => false,
            'error'   => 'pasos_incompletos',
            'message' => 'Completa los pasos de datos personales y empresa antes de finalizar.',
        ], 400 );
    }

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

    // Fix: partial=true solo permitido para retroceder (paso < paso_actual)
    // Evita que el cliente bypasee validaciones avanzando con partial=true
    $partial_raw  = (bool) $request->get_param( 'partial' );
    $paso_actual  = (int) get_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO, true );
    $partial      = $partial_raw && ( $paso < $paso_actual ); // solo se aplica al retroceder

    if ( ! is_array( $datos ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'datos_invalidos' ], 400 );
    }

    $result = VX_Onboarding::save_step( $user_id, $paso, $datos, $partial );

    if ( ! empty( $result['errors'] ) ) {
        return new WP_REST_Response( [ 'success' => false, 'errors' => $result['errors'] ], 400 );
    }

    return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
}
