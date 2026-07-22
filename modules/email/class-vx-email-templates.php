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
    public static function render_plain( string $html_body ): string
    {
        return self::wrapper(
            '<p style="margin:0 0 20px;font-size:16px;line-height:1.7;color:#3d444e;">' . $html_body . '</p>'
        );
    }

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
<img src="https://vitrinexo.com/wp-content/themes/vitrinexo-theme/assets/img/vitrinexo-email.png"
     alt="Vitrinexo" width="173" height="42" style="display:block;">
</td></tr>
<tr><td style="padding:32px;">' . $content . '</td></tr>
<tr><td style="background:#f8fafc;padding:20px 32px;border-top:1px solid #e2ecf3;">
<p style="margin:0;font-size:12px;color:#8ea5b8;text-align:center;">
© ' . wp_date('Y') . ' Vitrinexo SpA &nbsp;·&nbsp;
<a href="' . home_url('/privacidad/') . '" style="color:#8ea5b8;text-decoration:none;">Privacidad</a> &nbsp;·&nbsp;
<a href="' . home_url('/terminos/') . '" style="color:#8ea5b8;text-decoration:none;">Términos</a>
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
        $mensaje  = trim( (string) ( $d['mensaje'] ?? '' ) );

        $mensaje_html = $mensaje
            ? '<div style="background:#f8fafc;border-left:4px solid #2cced6;padding:14px 18px;border-radius:0 8px 8px 0;margin:0 0 16px;">'
              . '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#2cced6;text-transform:uppercase;letter-spacing:.5px;">Mensaje de ' . $receptor . '</p>'
              . '<p style="margin:0;font-size:14px;color:#3d444e;font-style:italic;">"' . esc_html( $mensaje ) . '"</p>'
              . '</div>'
            : '';

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
            . $mensaje_html
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
     * Recordatorio diario de validaciones pendientes (9 AM → admin).
     * $data: n_cuentas, n_senior, lista_cuentas (array), lista_senior (array), validaciones_url
     */
    private static function tpl_admin_validaciones_reminder( array $d ): string
    {
        $n_cuentas       = (int) ( $d['n_cuentas'] ?? 0 );
        $n_senior        = (int) ( $d['n_senior']  ?? 0 );
        $lista_cuentas   = $d['lista_cuentas'] ?? [];
        $lista_senior    = $d['lista_senior']  ?? [];
        $url             = esc_url( $d['validaciones_url'] ?? admin_url( 'admin.php?page=vx-validaciones' ) );
        $total           = $n_cuentas + $n_senior;

        $content = self::h1( '⏳ ' . $total . ' validación' . ( $total !== 1 ? 'es' : '' ) . ' pendiente' . ( $total !== 1 ? 's' : '' ) );
        $content .= self::p( 'Hay usuarios esperando revisión en Vitrinexo. Puedes gestionarlos desde el panel de Validaciones.' );

        // ── Sección cuentas pendientes ─────────────────────────────────────────
        if ( $n_cuentas > 0 ) {
            $content .= '<p style="font-size:13px;font-weight:700;color:#b45309;margin:20px 0 8px;text-transform:uppercase;letter-spacing:.5px;">'
                . '📧 Cuentas por aprobar (' . $n_cuentas . ')</p>';

            $content .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">';
            $content .= '<tr style="background:#f8fafc;">'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Nombre</th>'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Email</th>'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Registro</th>'
                . '</tr>';

            foreach ( $lista_cuentas as $u ) {
                $content .= '<tr style="border-bottom:1px solid #f1f5f9;">'
                    . '<td style="padding:8px 12px;font-size:13px;color:#1a2335;">' . esc_html( $u['nombre'] ?: '(sin nombre)' ) . '</td>'
                    . '<td style="padding:8px 12px;font-size:13px;color:#5e6b7a;">' . esc_html( $u['email'] ) . '</td>'
                    . '<td style="padding:8px 12px;font-size:12px;color:#8ea5b8;">' . esc_html( $u['fecha'] ) . '</td>'
                    . '</tr>';
            }
            $content .= '</table>';
        }

        // ── Sección solicitudes Senior ─────────────────────────────────────────
        if ( $n_senior > 0 ) {
            $content .= '<p style="font-size:13px;font-weight:700;color:#4c1d95;margin:20px 0 8px;text-transform:uppercase;letter-spacing:.5px;">'
                . '🏆 Solicitudes Senior (' . $n_senior . ')</p>';

            $content .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">';
            $content .= '<tr style="background:#f8fafc;">'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Nombre</th>'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Email</th>'
                . '<th style="padding:8px 12px;text-align:left;font-size:12px;color:#64748b;border-bottom:1px solid #e2ecf3">Industria</th>'
                . '</tr>';

            foreach ( $lista_senior as $u ) {
                $content .= '<tr style="border-bottom:1px solid #f1f5f9;">'
                    . '<td style="padding:8px 12px;font-size:13px;color:#1a2335;">' . esc_html( $u['nombre'] ?: '(sin nombre)' ) . '</td>'
                    . '<td style="padding:8px 12px;font-size:13px;color:#5e6b7a;">' . esc_html( $u['email'] ) . '</td>'
                    . '<td style="padding:8px 12px;font-size:12px;color:#8ea5b8;">' . esc_html( $u['industria'] ?: '—' ) . '</td>'
                    . '</tr>';
            }
            $content .= '</table>';
        }

        $content .= '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $url, 'Revisar validaciones pendientes' )
            . '</div>'
            . self::p( '<small style="color:#8ea5b8;">Este recordatorio se envía cada mañana a las 9 AM solo si hay validaciones pendientes. No recibirás este email cuando todo esté al día.</small>' );

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
     * Confirmación de cambio de email.
     * $data: nombre, email_nuevo, email_actual, link
     */
    private static function tpl_cambio_email( array $d ): string
    {
        $nombre       = esc_html( $d['nombre']      ?? '' );
        $email_nuevo  = esc_html( $d['email_nuevo'] ?? '' );
        $email_actual = esc_html( $d['email_actual'] ?? '' );
        $link         = esc_url( $d['link']         ?? '#' );

        $content = self::h1( "Confirma tu nuevo email, $nombre" )
            . self::p( "Recibimos una solicitud para cambiar el email de tu cuenta Vitrinexo de <strong>$email_actual</strong> a <strong>$email_nuevo</strong>." )
            . self::p( 'Si fuiste tú, haz clic en el botón para confirmar el cambio. Si no reconoces esta acción, ignora este correo — tu email actual no cambiará.' )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $link, 'Confirmar nuevo email' )
            . '</div>'
            . self::p( '<small>Este enlace expira en 1 hora.</small>' );

        return self::wrapper( $content );
    }

    /**
     * Aviso de vencimiento de plan.
     * $data: nombre, days_left, fecha_vencimiento, plan_label, es_fundador, url_renovar
     */
    private static function tpl_plan_aviso_vencimiento( array $d ): string
    {
        $nombre    = esc_html( $d['nombre'] ?? '' );
        $days      = (int) ( $d['days_left'] ?? 0 );
        $fecha     = esc_html( $d['fecha_vencimiento'] ?? '' );
        $url       = esc_url( $d['url_renovar'] ?? home_url( '/configuracion/?tab=plan' ) );
        $fundador  = ! empty( $d['es_fundador'] );

        $urgencia  = match ( true ) {
            $days <= 1  => '🔴 ¡Último día!',
            $days <= 7  => '🟡 Esta semana',
            default     => '📅 En ' . $days . ' días',
        };

        $cta_text  = $fundador
            ? 'Activa tu precio preferencial'
            : 'Renovar mi plan';

        $msg_extra = $fundador
            ? '<p style="font-size:14px;color:#1a2335;">Como <strong>Miembro Pionero</strong> tienes acceso a un precio especial permanente. Actívalo antes de que venza tu acceso gratuito para no perder ninguna conexión.</p>'
            : '';

        $content = self::h1( "$urgencia Tu plan vence el $fecha" )
            . self::p( "Hola $nombre, tu plan en Vitrinexo vencerá el <strong>$fecha</strong> ($days día" . ( 1 === $days ? '' : 's' ) . ' restante' . ( 1 === $days ? '' : 's' ) . ').' )
            . $msg_extra
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $url, $cta_text )
            . '</div>'
            . self::p( 'Si no renuevas, perderás acceso al directorio, tus conexiones y los próximos eventos 4Dinner.' );

        return self::wrapper( $content );
    }

    /**
     * Confirmación de activación de plan (post-pago).
     * $data: nombre, plan, fecha_vencimiento, es_fundador
     */
    private static function tpl_plan_activado( array $d ): string
    {
        $nombre    = esc_html( $d['nombre'] ?? '' );
        $plan      = esc_html( ucfirst( $d['plan'] ?? '' ) );
        $fecha     = esc_html( $d['fecha_vencimiento'] ?? '' );
        $fundador  = ! empty( $d['es_fundador'] );

        $badge     = $fundador ? ' <span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:2px 8px;font-size:12px;font-weight:700;">⭐ Pionero</span>' : '';

        $content = self::h1( "¡Tu plan $plan está activo, $nombre!" )
            . self::p( 'Bienvenido de vuelta. Tienes acceso completo a Vitrinexo' . ( $fecha ? ' hasta el <strong>' . $fecha . '</strong>' : ' sin fecha de vencimiento' ) . '.' . $badge )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/dashboard/' ), 'Ir a mi dashboard' )
            . '</div>'
            . self::p( 'Gracias por ser parte de la red Vitrinexo. Si tienes dudas escríbenos a hola@vitrinexo.com.' );

        return self::wrapper( $content );
    }

    /**
     * Invitación a 4Dinner (enviada por el admin).
     * $data: usuario_nombre, dinner (ciudad/pais/fecha/restaurante/direccion), url_aceptar, url_rechazar
     */
    private static function tpl_dinner_invitacion( array $d ): string
    {
        $nombre    = esc_html( $d['usuario_nombre'] ?? '' );
        $dinner    = $d['dinner'] ?? [];
        $fecha_str = ! empty( $dinner['fecha'] ) ? date_i18n( 'l j \d\e F \d\e Y', (int) $dinner['fecha'] ) : '';

        $content = self::h1( "¡$nombre, tienes una invitación a un 4Dinner!" )
            . self::p( 'El equipo de Vitrinexo te ha reservado un cupo en la próxima cena. Aquí los detalles:' )
            . '<div style="background:#1a2335;border-radius:12px;padding:20px;margin:20px 0;color:#fff;">'
            . '<p style="margin:0 0 4px;font-size:12px;color:#8ea5b8;text-transform:uppercase;letter-spacing:.5px;">Tu invitación</p>'
            . '<p style="margin:0 0 16px;font-size:20px;font-weight:700;">🍽 4Dinner ' . esc_html( $dinner['ciudad'] ?? '' ) . '</p>'
            . '<p style="margin:0 0 6px;font-size:14px;color:#cdd7e2;">📅 ' . $fecha_str . ' · 8:00 PM</p>'
            . '<p style="margin:0;font-size:14px;color:#cdd7e2;">📍 ' . esc_html( ( $dinner['restaurante'] ?? '' ) . ( ! empty( $dinner['direccion'] ) ? ' — ' . $dinner['direccion'] : '' ) ) . '</p>'
            . '</div>'
            . self::p( '4 personas, 1 mesa, sin agenda formal. Solo una conversación real entre miembros verificados de Vitrinexo.' )
            . '<div style="text-align:center;margin:28px 0;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">'
            . self::btn( $d['url_aceptar'] ?? '#', '✓ Acepto la invitación', '#2cced6' )
            . '&nbsp;'
            . '<a href="' . esc_url( $d['url_rechazar'] ?? '#' ) . '" style="display:inline-block;background:#f1f5f9;color:#475569;text-decoration:none;padding:13px 28px;border-radius:999px;font-size:14px;font-weight:600;">No puedo asistir</a>'
            . '</div>'
            . self::p( '<small>Los links son de un solo uso. Si tienes dudas escríbenos a hola@vitrinexo.com</small>' );

        return self::wrapper( $content );
    }

    /**
     * Recuperación de contraseña (branded, reemplaza el email genérico de WP).
     * $data: nombre, reset_link
     */
    private static function tpl_reset_password( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? 'hola' );
        $link   = esc_url( $d['reset_link'] ?? '#' );

        $content = self::h1( "Recupera tu acceso, $nombre" )
            . self::p( 'Recibimos una solicitud para restablecer la contraseña de tu cuenta en Vitrinexo. Si fuiste tú, haz clic en el botón a continuación.' )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $link, 'Crear nueva contraseña' )
            . '</div>'
            . self::p( '<small style="color:#8ea5b8;">Este enlace es válido por 24 horas y solo puede usarse una vez. Si no solicitaste el cambio, ignora este mensaje — tu contraseña actual no cambiará.</small>' );

        return self::wrapper( $content );
    }

    /**
     * Confirmación de interés en un 4Dinner ("Quiero ir").
     * $data: nombre, dinner (ciudad, fecha_ts, restaurante)
     */
    private static function tpl_dinner_interes( array $d ): string
    {
        $nombre    = esc_html( $d['nombre'] ?? '' );
        $dinner    = $d['dinner'] ?? [];
        $ciudad    = esc_html( $dinner['ciudad'] ?? '' );
        $fecha_str = ! empty( $dinner['fecha'] ) ? date_i18n( 'l j \d\e F \d\e Y', (int) $dinner['fecha'] ) : '';

        $content = self::h1( "¡$nombre, recibimos tu interés!" )
            . self::p( "Tu interés en el <strong>4Dinner $ciudad</strong>" . ( $fecha_str ? " del $fecha_str" : '' ) . " quedó registrado." )
            . '<div style="background:#fef9ee;border:1px solid #fde68a;border-radius:12px;padding:20px;margin:20px 0;">'
            . '<p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;">¿Qué sigue?</p>'
            . '<ul style="margin:0;padding-left:18px;color:#78350f;font-size:14px;line-height:1.8;">'
            . '<li>El equipo de Vitrinexo revisa los interesados y arma las mesas.</li>'
            . '<li>Si quedas seleccionado, recibirás una <strong>invitación oficial</strong> con todos los detalles.</li>'
            . '<li>La cena es el <strong>miércoles a las 8pm</strong>, hora local.</li>'
            . '</ul>'
            . '</div>'
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/4dinner/' ), 'Ver próximos 4Dinners' )
            . '</div>'
            . self::p( '<small style="color:#8ea5b8;">¿No puedes asistir? Puedes retirar tu interés desde la página del evento.</small>' );

        return self::wrapper( $content );
    }

    /**
     * Recordatorio 24h antes del 4Dinner (para asignados confirmados).
     * $data: nombre, dinner (ciudad, fecha, restaurante, direccion), comensales (array)
     */
    private static function tpl_dinner_recordatorio( array $d ): string
    {
        $nombre      = esc_html( $d['nombre'] ?? '' );
        $dinner      = $d['dinner'] ?? [];
        $mesa_nombre = esc_html( $d['mesa_nombre'] ?? '' );
        $fecha_str   = ! empty( $dinner['fecha'] ) ? date_i18n( 'l j \d\e F', (int) $dinner['fecha'] ) : 'mañana';

        $mesa_html = $mesa_nombre
            ? '<div style="background:#eff6ff;border-radius:8px;padding:14px 16px;margin:20px 0;border-left:4px solid #3b82f6;">'
              . '<p style="margin:0 0 2px;font-size:11px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;">Tu mesa asignada</p>'
              . '<p style="margin:0;font-size:18px;font-weight:700;color:#1a2335;">' . $mesa_nombre . '</p>'
              . '</div>'
            : '';

        $content = self::h1( "🍽 ¡$nombre, tu 4Dinner es $fecha_str!" )
            . self::p( 'Solo quedan <strong>24 horas</strong>. Aquí un recordatorio de los detalles:' )
            . '<div style="background:#1a2335;border-radius:12px;padding:20px;margin:20px 0;color:#fff;">'
            . '<p style="margin:0 0 4px;font-size:12px;color:#8ea5b8;text-transform:uppercase;letter-spacing:.5px;">Tu cena</p>'
            . '<p style="margin:0 0 14px;font-size:20px;font-weight:700;">4Dinner ' . esc_html( $dinner['ciudad'] ?? '' ) . '</p>'
            . '<p style="margin:0 0 6px;font-size:14px;color:#cdd7e2;">📅 ' . $fecha_str . ' · 8:00 PM</p>'
            . '<p style="margin:0;font-size:14px;color:#cdd7e2;">📍 ' . esc_html( ( $dinner['restaurante'] ?? '' ) . ( ! empty( $dinner['direccion'] ) ? ' — ' . $dinner['direccion'] : '' ) ) . '</p>'
            . '</div>'
            . $mesa_html
            . '<div style="background:#f0fdf4;border-radius:8px;padding:14px 16px;margin:20px 0;">'
            . '<p style="margin:0;font-size:13px;color:#166534;"><strong>Recuerda:</strong> cada quien paga su consumo. Sin agenda formal — la conversación fluye sola.</p>'
            . '</div>'
            . '<div style="text-align:center;margin:24px 0;">'
            . self::btn( home_url( '/4dinner/' ), 'Ver detalles del evento' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Notificación manual de mesa asignada.
     * $data: nombre, mesa_nombre, dinner { ciudad, fecha, restaurante, direccion }
     */
    private static function tpl_dinner_mesa_asignada( array $d ): string
    {
        $nombre      = esc_html( $d['nombre'] ?? '' );
        $dinner      = $d['dinner'] ?? [];
        $mesa_nombre = esc_html( $d['mesa_nombre'] ?? '' );
        $fecha_str   = ! empty( $dinner['fecha'] ) ? date_i18n( 'l j \d\e F', (int) $dinner['fecha'] ) : '';

        $content = self::h1( "🍽 ¡$nombre, ya tienes mesa asignada!" )
            . self::p( 'El equipo ya armó las mesas para el 4Dinner de <strong>' . esc_html( $dinner['ciudad'] ?? '' ) . '</strong>. Esta es tu asignación:' )
            . '<div style="background:#eff6ff;border-radius:12px;padding:20px;margin:20px 0;border-left:4px solid #3b82f6;text-align:center;">'
            . '<p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;">Tu mesa</p>'
            . '<p style="margin:0;font-size:28px;font-weight:700;color:#1a2335;">' . $mesa_nombre . '</p>'
            . '</div>'
            . '<div style="background:#1a2335;border-radius:12px;padding:20px;margin:20px 0;color:#fff;">'
            . '<p style="margin:0 0 4px;font-size:12px;color:#8ea5b8;text-transform:uppercase;letter-spacing:.5px;">Detalles del evento</p>'
            . ( $fecha_str ? '<p style="margin:0 0 6px;font-size:14px;color:#cdd7e2;">📅 ' . $fecha_str . ' · 8:00 PM</p>' : '' )
            . '<p style="margin:0;font-size:14px;color:#cdd7e2;">📍 ' . esc_html( ( $dinner['restaurante'] ?? '' ) . ( ! empty( $dinner['direccion'] ) ? ' — ' . $dinner['direccion'] : '' ) ) . '</p>'
            . '</div>'
            . '<div style="background:#f0fdf4;border-radius:8px;padding:14px 16px;margin:20px 0;">'
            . '<p style="margin:0;font-size:13px;color:#166534;"><strong>Recuerda:</strong> cada quien paga su consumo. Sin agenda formal — la conversación fluye sola.</p>'
            . '</div>'
            . '<div style="text-align:center;margin:24px 0;">'
            . self::btn( home_url( '/4dinner/' ), 'Ver detalles del evento' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Aprobación de badge Senior.
     * $data: nombre
     */
    private static function tpl_senior_aprobado( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? '' );

        $content = self::h1( "¡$nombre, eres parte de Vitrinexo Senior!" )
            . self::p( 'Tu solicitud para unirte a la comunidad <strong>Vitrinexo Senior</strong> fue aprobada. Ahora tienes acceso al directorio vertical de ejecutivos con trayectoria consolidada.' )
            . '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:20px;margin:20px 0;text-align:center;">'
            . '<div style="font-size:36px;margin-bottom:8px;">🏆</div>'
            . '<p style="margin:0;font-size:18px;font-weight:700;color:#78350f;">Badge Senior activado</p>'
            . '<p style="margin:6px 0 0;font-size:13px;color:#92400e;">Tu insignia es visible en tu ficha de Vitrinexo.</p>'
            . '</div>'
            . self::p( 'Explora los perfiles de tu comunidad, conecta con otros ejecutivos senior y participa en los próximos 4Dinners exclusivos.' )
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( home_url( '/comunidad-senior/' ), 'Ir a mi comunidad Senior' )
            . '</div>';

        return self::wrapper( $content );
    }

    /**
     * Despedida al eliminar cuenta.
     * $data: nombre, email
     */
    private static function tpl_eliminacion_cuenta( array $d ): string
    {
        $nombre = esc_html( $d['nombre'] ?? '' );
        $email  = esc_html( $d['email']  ?? '' );

        $content = self::h1( "Lamentamos verte partir, $nombre" )
            . self::p( "Tu cuenta de Vitrinexo asociada a <strong>$email</strong> ha sido eliminada de forma permanente junto con todos tus datos, empresas y conexiones." )
            . '<div style="background:#f8fafc;border-radius:8px;padding:14px 16px;margin:20px 0;">'
            . '<p style="margin:0;font-size:13px;color:#5e6b7a;">Si esto fue un error o quieres volver en el futuro, puedes registrarte nuevamente en vitrinexo.com. Tu distintivo Pionero no puede ser recuperado.</p>'
            . '</div>'
            . self::p( 'Gracias por haber sido parte de la red. Si tienes comentarios sobre por qué decidiste irte, escríbenos a <a href="mailto:hola@vitrinexo.com" style="color:#2cced6;">hola@vitrinexo.com</a> — nos ayuda a mejorar.' );

        return self::wrapper( $content );
    }

    /**
     * Pago fallido (para webhook de Stripe).
     * $data: nombre, plan, fecha_intento, url_actualizar
     */
    private static function tpl_plan_pago_fallido( array $d ): string
    {
        $nombre       = esc_html( $d['nombre'] ?? '' );
        $plan         = esc_html( ucfirst( $d['plan'] ?? '' ) );
        $fecha        = esc_html( $d['fecha_intento'] ?? '' );
        $url          = esc_url( $d['url_actualizar'] ?? home_url( '/configuracion/?tab=plan' ) );

        $content = self::h1( "⚠ No pudimos procesar tu pago, $nombre" )
            . self::p( "El cobro de tu plan <strong>$plan</strong>" . ( $fecha ? " del $fecha" : '' ) . " no pudo completarse. Tu acceso a Vitrinexo podría suspenderse pronto si no se regulariza." )
            . '<div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:18px 20px;margin:20px 0;">'
            . '<p style="margin:0;font-size:14px;color:#dc2626;font-weight:600;">Posibles causas:</p>'
            . '<ul style="margin:8px 0 0;padding-left:18px;color:#7f1d1d;font-size:13px;line-height:1.8;">'
            . '<li>Fondos insuficientes en la tarjeta.</li>'
            . '<li>Tarjeta vencida o bloqueada.</li>'
            . '<li>Límite de crédito alcanzado.</li>'
            . '</ul>'
            . '</div>'
            . '<div style="text-align:center;margin:28px 0;">'
            . self::btn( $url, 'Actualizar método de pago', '#dc2626' )
            . '</div>'
            . self::p( 'Si el problema persiste, escríbenos a <a href="mailto:hola@vitrinexo.com" style="color:#2cced6;">hola@vitrinexo.com</a>.' );

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
            . self::btn( home_url( '/mis-eventos/' ), 'Ver mis detalles en Mis 4Dinners' )
            . '</div>'
            . self::p( 'No se requiere agenda. Es una cena informal. ¡Nos vemos ahí!' );

        return self::wrapper( $content );
    }
}
