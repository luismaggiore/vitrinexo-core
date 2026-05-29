<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Templates de email con CSS inline.
 * Cada template recibe datos y devuelve HTML completo para enviar por correo.
 * Los CSS DEBEN ser inline — requerimiento de compatibilidad con Gmail/Outlook.
 */
class VX_Email_Templates
{
    /**
     * Dispatcher — llama al método del template correcto.
     *
     * @param string $template
     * @param array  $data
     * @return string  HTML del email o '' si no existe.
     */
    public static function render( string $template, array $data ): string
    {
        $method = 'tpl_' . str_replace( '-', '_', $template );
        if ( method_exists( self::class, $method ) ) {
            return self::$method( $data );
        }
        return '';
    }

    // ── Estructura base ──────────────────────────────────────────

    private static function wrapper( string $content ): string
    {
        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Vitrinexo</title></head>
<body style="margin:0;padding:0;background:#edf5fc;font-family:\'Switzer\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#edf5fc;">
<tr><td align="center" style="padding:32px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
<tr><td style="background:linear-gradient(135deg,#00aeb8 0%,#2ead6e 100%);padding:28px 32px;">
<img src="https://vitrinexo.com/wp-content/themes/vitrinexo-theme/assets/img/vitrinexo-blanco.svg"
     alt="Vitrinexo" width="130" style="display:block;">
</td></tr>
<tr><td style="padding:32px;">' . $content . '</td></tr>
<tr><td style="background:#f8fafc;padding:20px 32px;border-top:1px solid #e2ecf3;">
<p style="margin:0;font-size:12px;color:#8ea5b8;text-align:center;">
© 2026 Vitrinexo by Maggiore · <a href="https://vitrinexo.com/privacidad" style="color:#8ea5b8;">Privacidad</a> ·
<a href="https://vitrinexo.com/terminos" style="color:#8ea5b8;">Términos</a>
</p></td></tr>
</table></td></tr></table></body></html>';
    }

    private static function btn( string $url, string $label, string $color = '#2cced6' ): string
    {
        return '<a href="' . esc_url( $url ) . '" style="display:inline-block;background:' . $color . ';color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:999px;font-size:14px;font-weight:600;">' . esc_html( $label ) . '</a>';
    }

    private static function h1( string $text ): string
    {
        return '<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1a2335;">' . esc_html( $text ) . '</h1>';
    }

    private static function p( string $text ): string
    {
        return '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3d444e;">' . wp_kses_post( $text ) . '</p>';
    }

    // ── Templates ────────────────────────────────────────────────

    /**
     * Confirmación de email (email institucional).
     * $data: nombre, link
     */
    private static function tpl_confirmacion( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? 'hola' );
        $link   = esc_url( $d['link'] ?? '' );

        $content = self::h1( "Hola $nombre, confirma tu email" )
            . self::p( "Estás a un clic de activar tu cuenta en Vitrinexo. Haz clic en el botón para confirmar tu email y acceder al directorio B2B hispanohablante." )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $link, 'Confirmar mi email' )
            . '</div>'
            . self::p( '<small style="color:#8ea5b8;">Este enlace expira en 24 horas. Si no creaste esta cuenta, ignora este mensaje.</small>' );

        return self::wrapper( $content );
    }

    /**
     * Aprobación de cuenta manual (email genérico, aprobado por admin).
     * $data: nombre, link
     */
    private static function tpl_aprobacion( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? 'hola' );
        $link   = esc_url( $d['link'] ?? '' );

        $content = self::h1( "¡Buenas noticias, $nombre!" )
            . self::p( "Tu cuenta en Vitrinexo ha sido revisada y aprobada por nuestro equipo. Ya puedes activarla y completar tu perfil." )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $link, 'Activar mi cuenta' )
            . '</div>'
            . self::p( '<small style="color:#8ea5b8;">Este enlace expira en 72 horas.</small>' );

        return self::wrapper( $content );
    }

    /**
     * Bienvenida post-activación.
     * $data: nombre
     */
    private static function tpl_bienvenida( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? 'hola' );

        $steps = [
            [ '👤', 'Completa tu perfil', 'Agrega tu foto, bio y empresa para causar buena impresión.' ],
            [ '🏷️', 'Define tus tags', 'Qué ofreces y qué buscas — son la clave del sistema de matches.' ],
            [ '🔍', 'Explora el directorio', 'Más de 300 profesionales B2B hispanohablantes te esperan.' ],
        ];

        $steps_html = '<table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">';
        foreach ( $steps as [$icon, $title, $desc] ) {
            $steps_html .= '<tr><td style="padding:10px 0;vertical-align:top;width:44px;font-size:22px;">' . $icon . '</td>
            <td style="padding:10px 0 10px 8px;">
            <strong style="font-size:14px;color:#1a2335;display:block;">' . $title . '</strong>
            <span style="font-size:13px;color:#5e6b7a;">' . $desc . '</span></td></tr>';
        }
        $steps_html .= '</table>';

        $content = self::h1( "¡Bienvenido a Vitrinexo, $nombre!" )
            . self::p( "Tu cuenta está activa. Empieza a construir tu vitrina y encuentra tus nexos B2B." )
            . $steps_html
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/onboarding/' ), 'Completar mi perfil' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Notificación de solicitud de conexión recibida.
     * $data: receptor_nombre, emisor_nombre, emisor_empresa, pitch, token_aceptar, token_rechazar
     */
    private static function tpl_conexion_recibida( array $d ): string
    {
        $receptor     = esc_html( $d['receptor_nombre'] ?? '' );
        $emisor       = esc_html( $d['emisor_nombre'] ?? '' );
        $empresa      = esc_html( $d['emisor_empresa'] ?? '' );
        $pitch        = esc_html( $d['pitch'] ?? '' );
        $url_aceptar  = esc_url( add_query_arg( 'token', $d['token_aceptar']  ?? '', rest_url( VX_REST_NAMESPACE . '/conexiones/aceptar' ) ) );
        $url_rechazar = esc_url( add_query_arg( 'token', $d['token_rechazar'] ?? '', rest_url( VX_REST_NAMESPACE . '/conexiones/rechazar' ) ) );

        $content = self::h1( "$receptor, tienes una solicitud de conexión" )
            . self::p( "<strong>$emisor</strong>" . ( $empresa ? " de <strong>$empresa</strong>" : '' ) . " quiere conectar contigo." )
            . '<div style="background:#f8fafc;border-left:4px solid #2cced6;padding:16px 20px;border-radius:0 8px 8px 0;margin:20px 0;">'
            . '<p style="margin:0;font-size:14px;color:#3d444e;font-style:italic;">"' . $pitch . '"</p></div>'
            . '<p style="font-size:13px;color:#8ea5b8;margin:0 0 24px;">Sus datos de contacto se revelarán <strong>solo si aceptas</strong>.</p>'
            . '<table cellpadding="0" cellspacing="0"><tr>'
            . '<td style="padding-right:12px;">' . self::btn( $url_aceptar, '✓ Aceptar', '#2ead6e' ) . '</td>'
            . '<td><a href="' . $url_rechazar . '" style="display:inline-block;color:#8ea5b8;text-decoration:none;padding:13px 24px;border:1.5px solid #d7e4ef;border-radius:999px;font-size:14px;">Rechazar</a></td>'
            . '</tr></table>';

        return self::wrapper( $content );
    }

    /**
     * Confirmación de conexión aceptada — enviada al emisor con datos del receptor.
     * $data: emisor_nombre, receptor_nombre, contacto (array con email, telefono, linkedin, preferido)
     */
    private static function tpl_conexion_aceptada( array $d ): string
    {
        $emisor   = esc_html( $d['emisor_nombre'] ?? '' );
        $receptor = esc_html( $d['receptor_nombre'] ?? '' );
        $contacto = $d['contacto'] ?? [];

        $links = '';
        if ( ! empty( $contacto['email'] ) ) {
            $links .= '<a href="mailto:' . esc_attr( $contacto['email'] ) . '" style="display:inline-block;margin:4px;padding:8px 16px;background:#f0faf5;border:1.5px solid #2ead6e;border-radius:999px;font-size:13px;color:#2ead6e;text-decoration:none;">✉ ' . esc_html( $contacto['email'] ) . '</a>';
        }
        if ( ! empty( $contacto['linkedin'] ) ) {
            $links .= '<a href="' . esc_url( $contacto['linkedin'] ) . '" style="display:inline-block;margin:4px;padding:8px 16px;background:#eef4ff;border:1.5px solid #5100ff;border-radius:999px;font-size:13px;color:#5100ff;text-decoration:none;">in LinkedIn</a>';
        }
        if ( ! empty( $contacto['telefono'] ) ) {
            $links .= '<a href="tel:' . esc_attr( str_replace( ' ', '', $contacto['telefono'] ) ) . '" style="display:inline-block;margin:4px;padding:8px 16px;background:#f8fafc;border:1.5px solid #d7e4ef;border-radius:999px;font-size:13px;color:#3d444e;text-decoration:none;">📞 ' . esc_html( $contacto['telefono'] ) . '</a>';
        }

        $content = self::h1( "¡$receptor aceptó tu conexión!" )
            . self::p( "Ya tienen contacto mutuo. Estos son sus datos:" )
            . '<div style="background:#f0faf5;border-radius:12px;padding:20px;margin:20px 0;">'
            . '<p style="margin:0 0 12px;font-size:16px;font-weight:600;color:#1a2335;">' . $receptor . '</p>'
            . $links
            . '</div>'
            . '<div style="text-align:center;margin:24px 0;">'
            . self::btn( home_url( '/conexiones/' ), 'Ver mis conexiones' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Recordatorio de conexión pendiente (72h sin respuesta).
     * $data: receptor_nombre, emisor_nombre, pitch, token_aceptar, token_rechazar
     */
    private static function tpl_recordatorio_conexion( array $d ): string
    {
        $receptor     = esc_html( $d['receptor_nombre'] ?? '' );
        $emisor       = esc_html( $d['emisor_nombre'] ?? '' );
        $pitch        = esc_html( $d['pitch'] ?? '' );
        $url_aceptar  = esc_url( add_query_arg( 'token', $d['token_aceptar']  ?? '', rest_url( VX_REST_NAMESPACE . '/conexiones/aceptar' ) ) );
        $url_rechazar = esc_url( add_query_arg( 'token', $d['token_rechazar'] ?? '', rest_url( VX_REST_NAMESPACE . '/conexiones/rechazar' ) ) );

        $content = self::h1( "Recordatorio: $emisor espera tu respuesta" )
            . self::p( "Hace 3 días te llegó una solicitud de conexión de <strong>$emisor</strong> y aún no ha sido respondida." )
            . '<div style="background:#f8fafc;border-left:4px solid #2cced6;padding:16px 20px;border-radius:0 8px 8px 0;margin:20px 0;">'
            . '<p style="margin:0;font-size:14px;color:#3d444e;font-style:italic;">"' . $pitch . '"</p></div>'
            . '<table cellpadding="0" cellspacing="0"><tr>'
            . '<td style="padding-right:12px;">' . self::btn( $url_aceptar, '✓ Aceptar', '#2ead6e' ) . '</td>'
            . '<td><a href="' . $url_rechazar . '" style="display:inline-block;color:#8ea5b8;text-decoration:none;padding:13px 24px;border:1.5px solid #d7e4ef;border-radius:999px;font-size:14px;">Rechazar</a></td>'
            . '</tr></table>';

        return self::wrapper( $content );
    }

    /**
     * Resumen semanal de matches.
     * $data: nombre, seeks_matches (array de usuarios), offers_matches (array de usuarios)
     */
    private static function tpl_match_semanal( array $d ): string
    {
        $nombre        = esc_html( $d['nombre'] ?? '' );
        $seeks_matches = $d['seeks_matches'] ?? [];
        $offers_matches = $d['offers_matches'] ?? [];
        $total         = count( $seeks_matches ) + count( $offers_matches );

        if ( $total === 0 ) {
            return '';
        }

        $content = self::h1( "Tus nuevos matches de la semana, $nombre" )
            . self::p( "Esta semana encontramos <strong>$total nuevos match" . ( $total > 1 ? 'es' : '' ) . "</strong> para ti." );

        if ( ! empty( $seeks_matches ) ) {
            $content .= '<p style="font-size:13px;font-weight:700;color:#2ead6e;margin:16px 0 8px;text-transform:uppercase;letter-spacing:.5px;">Ofrecen lo que buscas</p>';
            foreach ( array_slice( $seeks_matches, 0, 3 ) as $user ) {
                $content .= '<div style="display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #e2ecf3;">'
                    . '<div style="width:40px;height:40px;border-radius:50%;background:#e2ecf3;flex-shrink:0;overflow:hidden;">'
                    . '<img src="' . esc_url( $user['foto_url'] ?? '' ) . '" width="40" height="40" style="border-radius:50%;object-fit:cover;"></div>'
                    . '<div style="margin-left:12px;flex:1;">'
                    . '<strong style="font-size:14px;color:#1a2335;">' . esc_html( $user['nombre'] ?? '' ) . '</strong>'
                    . '<div style="font-size:12px;color:#8ea5b8;">' . esc_html( $user['empresa'] ?? '' ) . '</div></div></div>';
            }
        }

        if ( ! empty( $offers_matches ) ) {
            $content .= '<p style="font-size:13px;font-weight:700;color:#ff4d82;margin:16px 0 8px;text-transform:uppercase;letter-spacing:.5px;">Buscan lo que ofreces</p>';
            foreach ( array_slice( $offers_matches, 0, 3 ) as $user ) {
                $content .= '<div style="display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #e2ecf3;">'
                    . '<div style="width:40px;height:40px;border-radius:50%;background:#e2ecf3;flex-shrink:0;overflow:hidden;">'
                    . '<img src="' . esc_url( $user['foto_url'] ?? '' ) . '" width="40" height="40" style="border-radius:50%;object-fit:cover;"></div>'
                    . '<div style="margin-left:12px;flex:1;">'
                    . '<strong style="font-size:14px;color:#1a2335;">' . esc_html( $user['nombre'] ?? '' ) . '</strong>'
                    . '<div style="font-size:12px;color:#8ea5b8;">' . esc_html( $user['empresa'] ?? '' ) . '</div></div></div>';
            }
        }

        $content .= '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/matches/' ), 'Ver todos mis matches' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Notificación al admin de solicitud Senior.
     * $data: nombre_completo, email, approve_url
     */
    private static function tpl_admin_senior_request( array $d ): string
    {
        $nombre      = esc_html( $d['nombre_completo'] ?? '' );
        $email       = esc_html( $d['email'] ?? '' );
        $approve_url = esc_url( $d['approve_url'] ?? '' );

        $content = self::h1( 'Solicitud de verificación Senior' )
            . self::p( "<strong>$nombre</strong> ($email) solicita el badge de la comunidad Senior." )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $approve_url, 'Aprobar verificación Senior' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Notificación al admin de cuenta pendiente de aprobación.
     * $data: nombre_completo, email, approve_url
     */
    private static function tpl_admin_cuenta_pendiente( array $d ): string
    {
        $nombre      = esc_html( $d['nombre_completo'] ?? '' );
        $email       = esc_html( $d['email'] ?? '' );
        $approve_url = esc_url( $d['approve_url'] ?? '' );

        $content = self::h1( 'Nueva cuenta pendiente de aprobación' )
            . self::p( "El usuario <strong>$nombre</strong> ($email) se registró con un email personal y requiere aprobación manual." )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $approve_url, 'Aprobar cuenta' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Confirmación de 4Dinner.
     * $data: usuario_nombre, dinner (array con ciudad, fecha, restaurante, direccion), comensales (array)
     */
    private static function tpl_dinner_confirmacion( array $d ): string
    {
        $nombre     = esc_html( $d['usuario_nombre'] ?? '' );
        $dinner     = $d['dinner'] ?? [];
        $comensales = $d['comensales'] ?? [];

        $comensales_html = '';
        foreach ( $comensales as $c ) {
            $comensales_html .= '<div style="display:flex;align-items:center;padding:8px 0;">'
                . '<img src="' . esc_url( $c['foto_url'] ?? '' ) . '" width="36" height="36" style="border-radius:50%;object-fit:cover;flex-shrink:0;">'
                . '<div style="margin-left:10px;">'
                . '<strong style="font-size:14px;color:#1a2335;">' . esc_html( $c['nombre'] ?? '' ) . '</strong>'
                . '<div style="font-size:12px;color:#8ea5b8;">' . esc_html( $c['empresa'] ?? '' ) . '</div></div></div>';
        }

        $fecha_str = ! empty( $dinner['fecha'] ) ? date_i18n( 'l j \d\e F \d\e Y', $dinner['fecha'] ) : '';

        $content = self::h1( "¡Tu mesa para el 4Dinner está confirmada, $nombre!" )
            . '<div style="background:#1a2335;border-radius:12px;padding:20px;margin:20px 0;color:#fff;">'
            . '<p style="margin:0 0 4px;font-size:12px;color:#8ea5b8;text-transform:uppercase;letter-spacing:.5px;">Tu mesa</p>'
            . '<p style="margin:0 0 16px;font-size:20px;font-weight:700;">🍽 4Dinner ' . esc_html( $dinner['ciudad'] ?? '' ) . '</p>'
            . '<p style="margin:0 0 6px;font-size:14px;color:#cdd7e2;">📅 ' . $fecha_str . ' · 8:00 PM</p>'
            . '<p style="margin:0;font-size:14px;color:#cdd7e2;">📍 ' . esc_html( $dinner['restaurante'] ?? '' ) . ' — ' . esc_html( $dinner['direccion'] ?? '' ) . '</p>'
            . '</div>'
            . '<p style="font-size:14px;font-weight:600;color:#1a2335;margin:20px 0 8px;">Tus comensales:</p>'
            . $comensales_html
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/events/4dinner/' ), 'Ver detalles del evento' )
            . '</div>'
            . self::p( 'No se requiere agenda. Es una cena informal. ¡Nos vemos ahí!' );

        return self::wrapper( $content );
    }
}
