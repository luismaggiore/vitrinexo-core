<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wrapper de email sobre wp_mail().
 * Abstracción del proveedor — Resend vía SMTP.
 *
 * SMTP: smtp.resend.com:465 (SSL)
 * Usuario: resend
 * Clave:   opción WP vx_resend_api_key
 */
class VX_Mailer
{
    /**
     * Registra el hook phpmailer_init para configurar Resend SMTP.
     * Llamar desde el bootstrap del plugin (init o plugins_loaded).
     */
    public static function init(): void
    {
        add_action( 'phpmailer_init', [ self::class, 'configure_smtp' ] );
        add_filter( 'wp_mail_from',      fn() => 'hola@vitrinexo.com' );
        add_filter( 'wp_mail_from_name', fn() => 'Vitrinexo' );
    }

    /**
     * Inyecta la configuración SMTP de Resend en PHPMailer.
     */
    public static function configure_smtp( \PHPMailer\PHPMailer\PHPMailer $mailer ): void
    {
        $api_key = get_option( 'vx_resend_api_key', '' );
        if ( ! $api_key ) return;

        $mailer->isSMTP();
        $mailer->Host       = 'smtp.resend.com';
        $mailer->SMTPAuth   = true;
        $mailer->Port       = 465;
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mailer->Username   = 'resend';
        $mailer->Password   = $api_key;
    }
    const FROM_NAME  = 'Vitrinexo';
    const FROM_EMAIL = 'hola@vitrinexo.com';

    /**
     * Envía un email transaccional.
     *
     * @param string $to        Email del destinatario.
     * @param string $subject   Asunto.
     * @param string $template  Nombre del template (ej: 'bienvenida').
     * @param array  $data      Datos para el template.
     * @return bool
     */
    public static function send( string $to, string $subject, string $template, array $data = [] ): bool
    {
        if ( ! is_email( $to ) ) {
            error_log( "[VX_Mailer::send] Email inválido: $to" );
            return false;
        }

        $html = VX_Email_Templates::render( $template, $data );

        if ( empty( $html ) ) {
            error_log( "[VX_Mailer::send] Template '$template' no encontrado o vacío" );
            return false;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . '>',
        ];

        $result = wp_mail( $to, $subject, $html, $headers );

        if ( ! $result ) {
            error_log( "[VX_Mailer::send] wp_mail falló para $to — template=$template" );
        }

        return $result;
    }

    /**
     * Envío múltiple (para confirmaciones de 4Dinner).
     * Envía el mismo email a múltiples destinatarios con sus propios datos.
     *
     * @param array  $recipients  Array de arrays con keys 'to', 'subject', 'data'
     * @param string $template    Nombre del template
     * @return int  Número de emails enviados exitosamente
     */
    public static function send_bulk( array $recipients, string $template ): int
    {
        $sent = 0;
        foreach ( $recipients as $recipient ) {
            $ok = self::send(
                $recipient['to'],
                $recipient['subject'],
                $template,
                $recipient['data'] ?? []
            );
            if ( $ok ) $sent++;
        }
        return $sent;
    }
}
