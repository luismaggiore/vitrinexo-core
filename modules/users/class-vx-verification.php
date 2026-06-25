<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Flujo de verificación de cuentas nuevas.
 * Detecta tipo de email, genera tokens, activa cuentas.
 */
class VX_Verification
{
    const TOKEN_EXPIRY_INSTITUTIONAL = 86400;    // 24h
    const TOKEN_EXPIRY_MANUAL        = 259200;   // 72h

    /**
     * Inicia el flujo de verificación para un usuario recién registrado.
     * Llamado desde rest-auth.php al registrar.
     *
     * @param int    $user_id
     * @param string $email
     */
    public static function start( int $user_id, string $email ): void
    {
        if ( VX_Domain_Helper::is_institutional( $email ) ) {
            update_user_meta( $user_id, VX_User_Meta::TIPO_VERIFICACION, 'automatica' );
            self::send_confirmation_email( $user_id );
        } else {
            update_user_meta( $user_id, VX_User_Meta::TIPO_VERIFICACION, 'manual' );
            self::notify_admin_pending( $user_id );
        }
    }

    /**
     * Verifica si el dominio del email es institucional.
     *
     * @param string $email
     * @return bool
     */
    public static function is_institutional( string $email ): bool
    {
        return VX_Domain_Helper::is_institutional( $email );
    }

    /**
     * Genera un token de activación y lo guarda en user meta.
     *
     * @param int $user_id
     * @param int $expiry_hours  Horas de validez del token.
     * @return string  El token generado.
     */
    public static function generate_token( int $user_id, int $expiry_hours = 24 ): string
    {
        $token  = VX_Token_Helper::generate();
        $expiry = time() + ( $expiry_hours * 3600 );

        update_user_meta( $user_id, VX_User_Meta::TOKEN_CONFIRMACION, $token );
        update_user_meta( $user_id, VX_User_Meta::TOKEN_EXPIRA,       $expiry );

        return $token;
    }

    /**
     * Valida el token de activación de un usuario.
     * Si es válido, lo consume (elimina) y devuelve true.
     *
     * @param int    $user_id
     * @param string $token
     * @return bool
     */
    public static function validate_token( int $user_id, string $token ): bool
    {
        $stored_token = get_user_meta( $user_id, VX_User_Meta::TOKEN_CONFIRMACION, true );
        $expiry       = (int) get_user_meta( $user_id, VX_User_Meta::TOKEN_EXPIRA, true );

        if ( empty( $stored_token ) || $stored_token !== $token ) {
            return false;
        }

        if ( $expiry > 0 && $expiry < time() ) {
            return false; // expirado
        }

        // Consumir el token
        delete_user_meta( $user_id, VX_User_Meta::TOKEN_CONFIRMACION );
        delete_user_meta( $user_id, VX_User_Meta::TOKEN_EXPIRA );

        return true;
    }

    /**
     * Activa una cuenta: cambia vx_estado a 'activo' y envía email de bienvenida.
     *
     * @param int $user_id
     */
    public static function activate_account( int $user_id ): void
    {
        update_user_meta( $user_id, VX_User_Meta::ESTADO, 'activo' );

        $user = VX_User::get( $user_id );
        if ( ! $user ) return;

        VX_Mailer::send(
            $user->get_email(),
            '¡Bienvenido a Vitrinexo, ' . $user->get_nombre() . '!',
            'bienvenida',
            [ 'nombre' => $user->get_nombre() ]
        );

        do_action( 'vx_account_activated', $user_id );
    }

    /**
     * Envía el email de confirmación de cuenta (email institucional).
     *
     * @param int $user_id
     */
    public static function send_confirmation_email( int $user_id ): void
    {
        $user  = VX_User::get( $user_id );
        if ( ! $user ) return;

        $token = self::generate_token( $user_id, 24 );
        $link  = add_query_arg( [
            'uid'    => $user_id,
            'token'  => $token,
            'accion' => 'confirmar',
        ], rest_url( VX_REST_NAMESPACE . '/activar' ) );

        VX_Mailer::send(
            $user->get_email(),
            'Confirma tu email en Vitrinexo',
            'confirmacion',
            [
                'nombre' => $user->get_nombre(),
                'link'   => $link,
            ]
        );
    }

    /**
     * Notifica al admin que hay una cuenta manual pendiente de revisión.
     *
     * @param int $user_id
     */
    public static function notify_admin_pending( int $user_id ): void
    {
        $user        = VX_User::get( $user_id );
        $admin_email = get_option( 'admin_email' );

        if ( ! $user ) return;

        $approve_url = wp_nonce_url(
            admin_url( 'users.php?action=vx_aprobar_verificacion&user_id=' . $user_id ),
            'vx_aprobar_' . $user_id
        );

        VX_Mailer::send(
            $admin_email,
            '[Vitrinexo] Nueva cuenta pendiente de aprobación',
            'admin_cuenta_pendiente',
            [
                'nombre_completo' => $user->get_nombre_completo(),
                'email'           => $user->get_email(),
                'approve_url'     => $approve_url,
            ]
        );
    }

    /**
     * Aprueba manualmente una cuenta (admin action).
     * Genera token y envía email al usuario.
     *
     * @param int $user_id
     */
    public static function approve_manual( int $user_id ): void
    {
        $user = VX_User::get( $user_id );
        if ( ! $user ) return;

        // Fix: no regenerar token si ya está activo (previene doble email por doble clic)
        if ( 'activo' === $user->get_estado() ) return;

        $token = self::generate_token( $user_id, 72 ); // 72h para manual
        $link  = add_query_arg( [
            'uid'    => $user_id,
            'token'  => $token,
            'accion' => 'confirmar',
        ], rest_url( VX_REST_NAMESPACE . '/activar' ) );

        VX_Mailer::send(
            $user->get_email(),
            '¡Tu cuenta en Vitrinexo fue aprobada!',
            'aprobacion',
            [
                'nombre' => $user->get_nombre(),
                'link'   => $link,
            ]
        );
    }

    /**
     * Reenvía el email de confirmación (solicitud del usuario).
     * Genera un nuevo token invalidando el anterior.
     *
     * @param int $user_id
     * @return bool
     */
    public static function resend_token( int $user_id ): bool
    {
        $user = VX_User::get( $user_id );
        if ( ! $user ) return false;

        $tipo = $user->get_tipo_verificacion();

        if ( 'automatica' === $tipo ) {
            self::send_confirmation_email( $user_id );
            return true;
        }

        // Fix: si es manual y el token expiró, notificar al admin para que vuelva a aprobar
        $token   = get_user_meta( $user_id, VX_User_Meta::TOKEN_CONFIRMACION, true );
        $expiry  = (int) get_user_meta( $user_id, VX_User_Meta::TOKEN_EXPIRA, true );
        $expired = $expiry > 0 && $expiry < time();
        if ( ! $token || $expired ) {
            self::notify_admin_pending( $user_id ); // pedir al admin que apruebe de nuevo
        }
        return false;
    }
}
