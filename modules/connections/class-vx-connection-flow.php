<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Flujo completo del ciclo de vida de una conexión.
 * Crear, aceptar, rechazar, marcar sin respuesta.
 */
class VX_Connection_Flow
{
    /**
     * Crea una solicitud de conexión.
     *
     * @param int    $emisor_id
     * @param int    $receptor_id
     * @param string $pitch
     * @param array  $empresas  Nombres de empresa(s) desde las que contacta.
     * @return int|WP_Error  Post ID de la conexión o WP_Error en error.
     */
    public static function create(
        int $emisor_id,
        int $receptor_id,
        string $pitch,
        array $empresas = []
    ): int|WP_Error {
        $emisor   = VX_User::get( $emisor_id );
        $receptor = VX_User::get( $receptor_id );

        if ( ! $emisor ) {
            return new WP_Error( 'emisor_no_encontrado', 'El usuario emisor no existe.', [ 'status' => 404 ] );
        }

        if ( ! $receptor ) {
            return new WP_Error( 'receptor_no_encontrado', 'El usuario receptor no existe.', [ 'status' => 404 ] );
        }

        if ( $emisor_id === $receptor_id ) {
            return new WP_Error( 'mismo_usuario', 'No puedes conectarte contigo mismo.', [ 'status' => 400 ] );
        }

        // Verificar que no exista una conexión pendiente
        if ( self::has_pending_connection( $emisor_id, $receptor_id ) ) {
            return new WP_Error( 'conexion_pendiente', 'Ya existe una solicitud pendiente entre estos usuarios.', [ 'status' => 409 ] );
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'vx_conexion',
            'post_title'  => 'Conexión: ' . $emisor->get_nombre_completo() . ' → ' . $receptor->get_nombre_completo(),
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) ) {
            error_log( '[VX_Connection_Flow::create] wp_insert_post: ' . $post_id->get_error_message() );
            return new WP_Error( 'error_bd', 'Error al crear la conexión.', [ 'status' => 500 ] );
        }

        // Snapshot del emisor al momento del envío
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_ID,                $emisor_id );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_NOMBRE,            $emisor->get_nombre_completo() );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_EMAIL,             $emisor->get_email() );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_TELEFONO,          $emisor->get_telefono() );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_LINKEDIN,          $emisor->get_linkedin() );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_CONTACTO_PREFERIDO, $emisor->get_contacto_preferido() );
        update_post_meta( $post_id, VX_Connection_Meta::EMISOR_EMPRESAS,          $empresas );
        update_post_meta( $post_id, VX_Connection_Meta::RECEPTOR_ID,              $receptor_id );
        update_post_meta( $post_id, VX_Connection_Meta::RECEPTOR_NOMBRE,          $receptor->get_nombre_completo() );
        update_post_meta( $post_id, VX_Connection_Meta::RECEPTOR_EMAIL,           $receptor->get_email() );
        update_post_meta( $post_id, VX_Connection_Meta::PITCH,                    $pitch );
        update_post_meta( $post_id, VX_Connection_Meta::ESTADO,                   'pendiente' );
        update_post_meta( $post_id, VX_Connection_Meta::FECHA_ENVIO,              time() );
        update_post_meta( $post_id, VX_Connection_Meta::RECORDATORIO_ENVIADO,     false );

        // Generar tokens de un solo uso
        $token_aceptar  = VX_Token_Helper::generate();
        $token_rechazar = VX_Token_Helper::generate();
        update_post_meta( $post_id, VX_Connection_Meta::TOKEN_ACEPTAR,  $token_aceptar );
        update_post_meta( $post_id, VX_Connection_Meta::TOKEN_RECHAZAR, $token_rechazar );

        // Determinar empresa(s) para el email
        $empresa_str = ! empty( $empresas ) ? implode( ', ', $empresas ) : '';
        if ( empty( $empresa_str ) ) {
            $empresa_activa = $emisor->get_empresa_activa();
            $empresa_str    = $empresa_activa ? $empresa_activa->post_title : '';
        }

        // Email al receptor
        VX_Mailer::send(
            $receptor->get_email(),
            $receptor->get_nombre() . ', ' . $emisor->get_nombre_completo() . ' quiere conectar contigo',
            'conexion_recibida',
            [
                'receptor_nombre' => $receptor->get_nombre_completo(),
                'emisor_nombre'   => $emisor->get_nombre_completo(),
                'emisor_empresa'  => $empresa_str,
                'pitch'           => $pitch,
                'token_aceptar'   => $token_aceptar,
                'token_rechazar'  => $token_rechazar,
            ]
        );

        do_action( 'vx_connection_received', $receptor_id, $post_id );

        return $post_id;
    }

    /**
     * Acepta una conexión por token.
     *
     * @param string $token  Token de aceptación del email.
     * @return array|WP_Error
     */
    public static function accept( string $token ): array|WP_Error
    {
        $conexion = VX_Connection::get_by_token( $token, 'aceptar' );

        if ( ! $conexion ) {
            return new WP_Error( 'token_invalido', 'El enlace de aceptación no es válido o ya fue usado.', [ 'status' => 400 ] );
        }

        if ( 'pendiente' !== $conexion->get_estado() ) {
            return new WP_Error( 'estado_invalido', 'Esta solicitud ya fue procesada.', [ 'status' => 409 ] );
        }

        // Actualizar estado y consumir token
        update_post_meta( $conexion->get_id(), VX_Connection_Meta::ESTADO,          'aceptado' );
        update_post_meta( $conexion->get_id(), VX_Connection_Meta::FECHA_RESPUESTA, time() );
        delete_post_meta( $conexion->get_id(), VX_Connection_Meta::TOKEN_ACEPTAR );
        delete_post_meta( $conexion->get_id(), VX_Connection_Meta::TOKEN_RECHAZAR );

        // Construir datos de contacto del receptor para revelar al emisor
        $receptor_id = $conexion->get_receptor_id();
        $receptor    = VX_User::get( $receptor_id );

        if ( $receptor ) {
            $contacto = [
                'nombre'             => $receptor->get_nombre_completo(),
                'email'              => $receptor->get_email(),
                'telefono'           => $receptor->get_telefono(),
                'linkedin'           => $receptor->get_linkedin(),
                'contacto_preferido' => $receptor->get_contacto_preferido(),
            ];

            // Email al emisor con datos de contacto del receptor
            $emisor = VX_User::get( $conexion->get_emisor_id() );
            if ( $emisor ) {
                VX_Mailer::send(
                    $emisor->get_email(),
                    '¡' . $receptor->get_nombre() . ' aceptó tu solicitud de conexión!',
                    'conexion_aceptada',
                    [
                        'emisor_nombre'   => $emisor->get_nombre_completo(),
                        'receptor_nombre' => $receptor->get_nombre_completo(),
                        'contacto'        => $contacto,
                    ]
                );
            }
        }

        do_action( 'vx_connection_accepted', $conexion->get_emisor_id(), $conexion->get_id() );

        return [
            'success'     => true,
            'conexion_id' => $conexion->get_id(),
        ];
    }

    /**
     * Rechaza una conexión por token.
     * El rechazo es PRIVADO — el emisor no recibe notificación.
     *
     * @param string $token
     * @return bool|WP_Error
     */
    public static function reject( string $token ): bool|WP_Error
    {
        $conexion = VX_Connection::get_by_token( $token, 'rechazar' );

        if ( ! $conexion ) {
            return new WP_Error( 'token_invalido', 'El enlace de rechazo no es válido o ya fue usado.', [ 'status' => 400 ] );
        }

        if ( 'pendiente' !== $conexion->get_estado() ) {
            return new WP_Error( 'estado_invalido', 'Esta solicitud ya fue procesada.', [ 'status' => 409 ] );
        }

        update_post_meta( $conexion->get_id(), VX_Connection_Meta::ESTADO,          'rechazado' );
        update_post_meta( $conexion->get_id(), VX_Connection_Meta::FECHA_RESPUESTA, time() );
        delete_post_meta( $conexion->get_id(), VX_Connection_Meta::TOKEN_ACEPTAR );
        delete_post_meta( $conexion->get_id(), VX_Connection_Meta::TOKEN_RECHAZAR );

        // Sin email al emisor — rechazo privado

        return true;
    }

    /**
     * Marca una conexión como sin_respuesta (llamado por el cron a los 7 días).
     *
     * @param int $conexion_id
     */
    public static function mark_no_response( int $conexion_id ): void
    {
        $conexion = VX_Connection::get( $conexion_id );
        if ( ! $conexion ) return;
        if ( 'pendiente' !== $conexion->get_estado() ) return;

        update_post_meta( $conexion_id, VX_Connection_Meta::ESTADO, 'sin_respuesta' );
        do_action( 'vx_connection_no_response', $conexion->get_emisor_id(), $conexion_id );
    }

    /**
     * Verifica si ya existe una conexión pendiente entre dos usuarios (en cualquier dirección).
     *
     * @param int $user_a
     * @param int $user_b
     * @return bool
     */
    private static function has_pending_connection( int $user_a, int $user_b ): bool
    {
        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => VX_Connection_Meta::ESTADO, 'value' => 'pendiente' ],
                [
                    'relation' => 'OR',
                    [
                        'relation' => 'AND',
                        [ 'key' => VX_Connection_Meta::EMISOR_ID,   'value' => $user_a ],
                        [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_b ],
                    ],
                    [
                        'relation' => 'AND',
                        [ 'key' => VX_Connection_Meta::EMISOR_ID,   'value' => $user_b ],
                        [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_a ],
                    ],
                ],
            ],
        ] );

        return ! empty( $posts );
    }
}
