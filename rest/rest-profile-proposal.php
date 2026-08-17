<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Propuestas de perfil (admin -> usuario): el admin sugiere tags de
 * ofrece/busca y el usuario debe aceptarlos o rechazarlos antes de que
 * se apliquen. Ver VX_User_Meta::PROFILE_PROPOSAL y
 * VX_Admin_Users::create_profile_proposal().
 */

add_action( 'rest_api_init', function () {

    // GET /perfil/propuesta/ver — router del link del email. Público (el
    // token da la autorización), pero NUNCA aplica el cambio en el propio
    // GET: solo enruta al editor de perfil, donde el usuario ya autenticado
    // ve el banner con la propuesta real y decide desde ahí.
    register_rest_route( VX_REST_NAMESPACE, '/perfil/propuesta/ver', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_perfil_propuesta_ver',
        'permission_callback' => '__return_true',
        'args'                => [
            'uid'   => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // POST /perfil/propuesta/aceptar — aplica la propuesta pendiente del usuario logueado.
    register_rest_route( VX_REST_NAMESPACE, '/perfil/propuesta/aceptar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_perfil_propuesta_aceptar',
        'permission_callback' => 'is_user_logged_in',
    ] );

    // POST /perfil/propuesta/rechazar — descarta la propuesta pendiente del usuario logueado.
    register_rest_route( VX_REST_NAMESPACE, '/perfil/propuesta/rechazar', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_perfil_propuesta_rechazar',
        'permission_callback' => 'is_user_logged_in',
    ] );

} );

/**
 * Única fuente de verdad para "¿hay una propuesta pendiente para este usuario?".
 * Usada por el admin (aviso + invalidación), el banner del editor de perfil,
 * y los 3 endpoints de este archivo — evita 5 copias del mismo chequeo.
 *
 * @return array|null  La propuesta si está pendiente, null si no existe o ya se resolvió.
 */
function vx_get_pending_profile_proposal( int $user_id ): ?array
{
    $propuesta = get_user_meta( $user_id, VX_User_Meta::PROFILE_PROPOSAL, true );
    if ( ! is_array( $propuesta ) || ( $propuesta['estado'] ?? '' ) !== 'pendiente' ) {
        return null;
    }
    return $propuesta;
}

/**
 * Valida que exista una propuesta pendiente para $uid y que $token coincida.
 *
 * @return array|null  La propuesta si es válida, null si no.
 */
function vx_rest_perfil_propuesta_validar( int $uid, string $token ): ?array
{
    $propuesta = vx_get_pending_profile_proposal( $uid );
    if ( ! $propuesta || ! VX_Token_Helper::validate( $token, $propuesta['token'] ?? '' ) ) {
        return null;
    }
    return $propuesta;
}

function vx_rest_perfil_propuesta_ver( WP_REST_Request $request ): void
{
    $uid   = (int) $request->get_param( 'uid' );
    $token = (string) $request->get_param( 'token' );

    if ( ! vx_rest_perfil_propuesta_validar( $uid, $token ) ) {
        // Token inválido o la propuesta ya no existe (resuelta o reemplazada por otra más nueva).
        wp_safe_redirect( home_url( '/editar-perfil/?vx_propuesta_error=token_invalido' ) );
        exit;
    }

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( home_url( '/editar-perfil/' ) ) ) );
        exit;
    }

    if ( get_current_user_id() !== $uid ) {
        // Sesión abierta como un usuario distinto al dueño de la propuesta.
        wp_safe_redirect( home_url( '/editar-perfil/?vx_propuesta_error=no_autorizado' ) );
        exit;
    }

    // Válido y es el dueño: el banner de la propia página del editor de
    // perfil ya lee VX_User_Meta::PROFILE_PROPOSAL y muestra la propuesta.
    wp_safe_redirect( home_url( '/editar-perfil/' ) );
    exit;
}

function vx_rest_perfil_propuesta_aceptar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id   = get_current_user_id();
    $propuesta = vx_get_pending_profile_proposal( $user_id );

    if ( ! $propuesta ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_propuesta' ], 404 );
    }

    // Misma función que aplica el guardado directo del admin — ver VX_Admin_Users::save_profile_fields().
    VX_Profile_Fields::apply( $user_id, (array) ( $propuesta['campos'] ?? [] ) );

    delete_user_meta( $user_id, VX_User_Meta::PROFILE_PROPOSAL );
    do_action( 'vx_profile_proposal_resolved', $user_id, 'aceptada' );

    return new WP_REST_Response( [ 'success' => true ] );
}

function vx_rest_perfil_propuesta_rechazar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id   = get_current_user_id();
    $propuesta = vx_get_pending_profile_proposal( $user_id );

    if ( ! $propuesta ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_propuesta' ], 404 );
    }

    delete_user_meta( $user_id, VX_User_Meta::PROFILE_PROPOSAL );
    do_action( 'vx_profile_proposal_resolved', $user_id, 'rechazada' );

    return new WP_REST_Response( [ 'success' => true ] );
}
