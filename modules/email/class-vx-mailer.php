<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wrapper de email sobre wp_mail().
 * Abstracción del proveedor (FluentSMTP + Postmark).
 */
class VX_Mailer
{
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
