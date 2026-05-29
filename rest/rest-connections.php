<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // POST /conexiones — enviar solicitud de conexión
    register_rest_route( VX_REST_NAMESPACE, '/conexiones', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_conexiones_crear',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'receptor_id' => [ 'required' => true,  'sanitize_callback' => 'absint' ],
            'pitch'       => [ 'required' => true,  'sanitize_callback' => 'sanitize_textarea_field' ],
            'empresas'    => [ 'required' => false ],
        ],
    ] );

    // GET /conexiones — listado de conexiones del usuario
    register_rest_route( VX_REST_NAMESPACE, '/conexiones', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_conexiones_listar',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'tipo' => [ 'required' => false, 'default' => 'todas', 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // POST /conexiones/aceptar — aceptar por token (URL de email)
    register_rest_route( VX_REST_NAMESPACE, '/conexiones/aceptar', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_conexion_aceptar_token',
        'permission_callback' => '__return_true',
        'args' => [
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // POST /conexiones/rechazar — rechazar por token (URL de email)
    register_rest_route( VX_REST_NAMESPACE, '/conexiones/rechazar', [
        'methods'             => 'GET',
        'callback'            => 'vx_rest_conexion_rechazar_token',
        'permission_callback' => '__return_true',
        'args' => [
            'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

    // POST /conexiones/responder — aceptar o rechazar desde la UI (usuario logueado)
    register_rest_route( VX_REST_NAMESPACE, '/conexiones/responder', [
        'methods'             => 'POST',
        'callback'            => 'vx_rest_conexion_responder',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'conexion_id' => [ 'required' => true,  'sanitize_callback' => 'absint' ],
            'accion'      => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );

} );

function vx_rest_conexiones_crear( WP_REST_Request $request ): WP_REST_Response
{
    $emisor_id   = get_current_user_id();
    $receptor_id = $request->get_param( 'receptor_id' );
    $pitch       = $request->get_param( 'pitch' );
    $empresas    = $request->get_param( 'empresas' );

    if ( $emisor_id === $receptor_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'no_autoconexion' ], 400 );
    }

    if ( strlen( $pitch ) < 20 ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'pitch_muy_corto' ], 400 );
    }

    $empresas_ids = is_array( $empresas ) ? array_map( 'sanitize_text_field', $empresas ) : [];

    $result = VX_Connection_Flow::create( $emisor_id, $receptor_id, $pitch, $empresas_ids );

    if ( is_wp_error( $result ) ) {
        $status = (int) ( $result->get_error_data( $result->get_error_code() )['status'] ?? 400 );
        return new WP_REST_Response( [ 'success' => false, 'error' => $result->get_error_code() ], $status );
    }

    return new WP_REST_Response( [ 'success' => true, 'conexion_id' => $result ], 201 );
}

function vx_rest_conexiones_listar( WP_REST_Request $request ): WP_REST_Response
{
    $user_id = get_current_user_id();
    $tipo    = $request->get_param( 'tipo' );

    $data = [];

    switch ( $tipo ) {
        case 'enviadas':
            $data = vx_format_conexiones( VX_Connection::get_sent_by( $user_id ), $user_id );
            break;
        case 'recibidas':
            $data = vx_format_conexiones( VX_Connection::get_received_by( $user_id ), $user_id );
            break;
        case 'aceptadas':
            $data = vx_format_conexiones( VX_Connection::get_accepted( $user_id ), $user_id );
            break;
        default:
            $enviadas  = VX_Connection::get_sent_by( $user_id );
            $recibidas = VX_Connection::get_received_by( $user_id );
            $data = [
                'enviadas'  => vx_format_conexiones( $enviadas,  $user_id ),
                'recibidas' => vx_format_conexiones( $recibidas, $user_id ),
            ];
    }

    return new WP_REST_Response( [ 'success' => true, 'data' => $data ], 200 );
}

function vx_rest_conexion_aceptar_token( WP_REST_Request $request ): void
{
    $token  = $request->get_param( 'token' );
    $result = VX_Connection_Flow::accept( $token );

    if ( is_wp_error( $result ) ) {
        wp_safe_redirect( home_url( '/conexiones/?error=' . $result->get_error_code() ) );
        exit;
    }

    wp_safe_redirect( home_url( '/conexion-aceptada/' ) );
    exit;
}

function vx_rest_conexion_rechazar_token( WP_REST_Request $request ): void
{
    $token  = $request->get_param( 'token' );
    $result = VX_Connection_Flow::reject( $token );

    if ( is_wp_error( $result ) ) {
        wp_safe_redirect( home_url( '/conexiones/?error=' . $result->get_error_code() ) );
        exit;
    }

    wp_safe_redirect( home_url( '/conexion-rechazada/' ) );
    exit;
}

/**
 * Formatea un array de VX_Connection para la respuesta REST.
 *
 * @param VX_Connection[] $connections
 * @param int             $viewer_id
 * @return array
 */
function vx_format_conexiones( array $connections, int $viewer_id ): array
{
    $result = [];

    foreach ( $connections as $conn ) {
        $other_id   = $conn->get_other_user_id( $viewer_id );
        $other_user = VX_User::get( $other_id );

        $item = [
            'id'          => $conn->get_id(),
            'estado'      => $conn->get_estado(),
            'pitch'       => $conn->get_pitch(),
            'fecha_envio' => $conn->get_fecha_envio(),
            'user'        => $other_user ? $other_user->to_card_array() : [ 'id' => $other_id ],
        ];

        // Solo revelar datos de contacto si está aceptada
        if ( 'aceptado' === $conn->get_estado() ) {
            $item['contacto'] = $conn->get_contact_data();
        }

        $result[] = $item;
    }

    return $result;
}

/**
 * Acepta o rechaza una conexión desde la UI (usuario logueado como receptor).
 */
function vx_rest_conexion_responder( WP_REST_Request $request ): WP_REST_Response
{
    $user_id     = get_current_user_id();
    $conexion_id = (int) $request->get_param( 'conexion_id' );
    $accion      = $request->get_param( 'accion' );

    if ( ! in_array( $accion, [ 'aceptado', 'rechazado' ], true ) ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'accion_invalida' ], 400 );
    }

    $conexion = VX_Connection::get( $conexion_id );
    if ( ! $conexion ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'conexion_no_encontrada' ], 404 );
    }

    if ( $conexion->get_receptor_id() !== $user_id ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'sin_permiso' ], 403 );
    }

    if ( 'pendiente' !== $conexion->get_estado() ) {
        return new WP_REST_Response( [ 'success' => false, 'error' => 'estado_invalido' ], 409 );
    }

    update_post_meta( $conexion_id, VX_Connection_Meta::ESTADO,          $accion );
    update_post_meta( $conexion_id, VX_Connection_Meta::FECHA_RESPUESTA, time() );
    delete_post_meta( $conexion_id, VX_Connection_Meta::TOKEN_ACEPTAR );
    delete_post_meta( $conexion_id, VX_Connection_Meta::TOKEN_RECHAZAR );

    if ( 'aceptado' === $accion ) {
        $receptor = VX_User::get( $user_id );
        $emisor   = VX_User::get( $conexion->get_emisor_id() );
        if ( $receptor && $emisor ) {
            VX_Mailer::send(
                $emisor->get_email(),
                '¡' . $receptor->get_nombre() . ' aceptó tu solicitud de conexión!',
                'conexion_aceptada',
                [
                    'emisor_nombre'   => $emisor->get_nombre_completo(),
                    'receptor_nombre' => $receptor->get_nombre_completo(),
                    'contacto'        => [
                        'nombre'             => $receptor->get_nombre_completo(),
                        'email'              => $receptor->get_email(),
                        'telefono'           => $receptor->get_telefono(),
                        'linkedin'           => $receptor->get_linkedin(),
                        'contacto_preferido' => $receptor->get_contacto_preferido(),
                    ],
                ]
            );
        }
        do_action( 'vx_connection_accepted', $conexion->get_emisor_id(), $conexion_id );
    }

    return new WP_REST_Response( [ 'success' => true ], 200 );
}
