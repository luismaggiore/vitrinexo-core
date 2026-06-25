<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Flujo de verificación de la comunidad Senior (requiere aprobación manual del admin).
 */
class VX_Senior_Verification
{
    public static function request( int $user_id ): void
    {
        update_user_meta( $user_id, VX_User_Meta::SENIOR_SOLICITADO, true );

        $user        = VX_User::get( $user_id );
        $admin_email = get_option( 'admin_email' );

        if ( ! $user ) return;

        $approve_url = wp_nonce_url(
            admin_url( 'users.php?action=vx_verificar_senior&user_id=' . $user_id ),
            'vx_verificar_senior_' . $user_id
        );

        VX_Mailer::send(
            $admin_email,
            '[Vitrinexo] Solicitud de verificación Senior',
            'admin_senior_request',
            [
                'nombre_completo' => $user->get_nombre_completo(),
                'email'           => $user->get_email(),
                'approve_url'     => $approve_url,
            ]
        );
    }

    public static function approve( int $user_id ): void
    {
        update_user_meta( $user_id, VX_User_Meta::SENIOR_VERIFICADO, true );
        VX_Community::activate( $user_id, 'senior' );

        // Notificar al usuario que fue aprobado
        $user = VX_User::get( $user_id );
        if ( $user ) {
            VX_Mailer::send(
                $user->get_email(),
                '¡Bienvenido a Vitrinexo Senior!',
                'senior_aprobado',
                [ 'nombre' => $user->get_nombre() ]
            );
        }
    }

    public static function reject( int $user_id ): void
    {
        delete_user_meta( $user_id, VX_User_Meta::SENIOR_SOLICITADO );
        delete_user_meta( $user_id, VX_User_Meta::SENIOR_VERIFICADO );
    }
}
