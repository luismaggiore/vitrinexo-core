<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tareas programadas via WP Cron.
 */
class VX_Cron
{
    const HOOK_HOURLY  = 'vx_cron_hourly';
    const HOOK_DAILY   = 'vx_cron_daily';
    const HOOK_WEEKLY  = 'vx_cron_weekly';

    /**
     * Registra las tareas cron. Llamado en register_activation_hook.
     */
    public static function schedule(): void
    {
        if ( ! wp_next_scheduled( self::HOOK_HOURLY ) ) {
            wp_schedule_event( time(), 'hourly', self::HOOK_HOURLY );
        }
        if ( ! wp_next_scheduled( self::HOOK_DAILY ) ) {
            wp_schedule_event( time(), 'daily', self::HOOK_DAILY );
        }
        if ( ! wp_next_scheduled( self::HOOK_WEEKLY ) ) {
            // Programar para el próximo lunes
            $next_monday = strtotime( 'next monday 8:00 AM' );
            wp_schedule_event( $next_monday, 'weekly', self::HOOK_WEEKLY );
        }

        add_action( self::HOOK_HOURLY, [ self::class, 'check_pending_connections' ] );
        add_action( self::HOOK_DAILY,  [ self::class, 'check_expired_memberships' ] );
        add_action( self::HOOK_WEEKLY, [ self::class, 'send_weekly_matches' ] );
    }

    /**
     * Elimina las tareas cron. Llamado en register_deactivation_hook.
     */
    public static function unschedule(): void
    {
        foreach ( [ self::HOOK_HOURLY, self::HOOK_DAILY, self::HOOK_WEEKLY ] as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }
        }
    }

    /**
     * Verifica conexiones pendientes:
     * - A las 72h: envía recordatorio al receptor.
     * - A los 7 días: marca como sin_respuesta.
     */
    public static function check_pending_connections(): void
    {
        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [ 'key' => VX_Connection_Meta::ESTADO, 'value' => 'pendiente' ],
            ],
        ] );

        $now          = time();
        $hours_72     = 72 * 3600;
        $days_7       = 7 * 24 * 3600;

        foreach ( $posts as $post_id ) {
            $fecha_envio  = (int) get_post_meta( $post_id, VX_Connection_Meta::FECHA_ENVIO,          true );
            $recordatorio = (bool) get_post_meta( $post_id, VX_Connection_Meta::RECORDATORIO_ENVIADO, true );
            $elapsed      = $now - $fecha_envio;

            if ( $elapsed >= $days_7 ) {
                VX_Connection_Flow::mark_no_response( $post_id );
                continue;
            }

            if ( $elapsed >= $hours_72 && ! $recordatorio ) {
                self::send_reminder( $post_id );
            }
        }
    }

    /**
     * Envía el email de recordatorio para una conexión pendiente.
     *
     * @param int $post_id
     */
    private static function send_reminder( int $post_id ): void
    {
        $receptor_id   = (int) get_post_meta( $post_id, VX_Connection_Meta::RECEPTOR_ID, true );
        $emisor_nombre = (string) get_post_meta( $post_id, VX_Connection_Meta::EMISOR_NOMBRE,  true );
        $pitch         = (string) get_post_meta( $post_id, VX_Connection_Meta::PITCH,          true );
        $token_aceptar = (string) get_post_meta( $post_id, VX_Connection_Meta::TOKEN_ACEPTAR,  true );
        $token_rechazar= (string) get_post_meta( $post_id, VX_Connection_Meta::TOKEN_RECHAZAR, true );

        $receptor = VX_User::get( $receptor_id );
        if ( ! $receptor ) return;

        VX_Mailer::send(
            $receptor->get_email(),
            'Recordatorio: ' . $emisor_nombre . ' espera tu respuesta',
            'recordatorio_conexion',
            [
                'receptor_nombre' => $receptor->get_nombre_completo(),
                'emisor_nombre'   => $emisor_nombre,
                'pitch'           => $pitch,
                'token_aceptar'   => $token_aceptar,
                'token_rechazar'  => $token_rechazar,
            ]
        );

        update_post_meta( $post_id, VX_Connection_Meta::RECORDATORIO_ENVIADO, true );
    }

    /**
     * Envía el resumen semanal de matches (cada lunes).
     */
    public static function send_weekly_matches(): void
    {
        $active_users = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                'relation' => 'AND',
                [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
                [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
            ],
        ] );

        $since = strtotime( '-7 days' );

        foreach ( $active_users as $uid ) {
            $user    = VX_User::get( $uid );
            if ( ! $user ) continue;

            $matches = VX_Matches::get_new_since( $uid, $since );

            if ( empty( $matches['seeks'] ) && empty( $matches['offers'] ) ) {
                continue;
            }

            VX_Mailer::send(
                $user->get_email(),
                'Tus matches de la semana en Vitrinexo',
                'match_semanal',
                [
                    'nombre'         => $user->get_nombre(),
                    'seeks_matches'  => $matches['seeks'],
                    'offers_matches' => $matches['offers'],
                ]
            );
        }
    }

    /**
     * Verifica membresías vencidas y actualiza su estado (diario).
     */
    public static function check_expired_memberships(): void
    {
        $users = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                [ 'key' => VX_User_Meta::PLAN_ESTADO, 'value' => 'activo' ],
            ],
        ] );

        $now = time();

        foreach ( $users as $uid ) {
            $vencimiento = (int) get_user_meta( $uid, VX_User_Meta::PLAN_VENCIMIENTO, true );
            if ( $vencimiento > 0 && $vencimiento < $now ) {
                $membresia = VX_Membership::get( $uid );
                $membresia->mark_expired();
            }
        }
    }
}

