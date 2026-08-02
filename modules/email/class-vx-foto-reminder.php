<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Recordatorio semanal por correo a los miembros activos que aún no subieron foto.
 * La plantilla es editable desde Vitrinexo → Emails (slug: foto_recordatorio).
 */
class VX_Foto_Reminder
{
    const HOOK = 'vx_foto_reminder_weekly';

    public static function init(): void
    {
        add_action( 'init', [ self::class, 'schedule' ] );
        add_action( self::HOOK, [ self::class, 'run' ] );
    }

    /** Programa el evento semanal si no existe. */
    public static function schedule(): void
    {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            // 'weekly' existe en WordPress desde 5.4.
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK );
        }
    }

    /** Envía el recordatorio a cada miembro activo sin foto. */
    public static function run(): void
    {
        $ids = get_users( [
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                'relation' => 'AND',
                [ 'key' => 'vx_estado',              'value' => 'activo' ],
                [ 'key' => 'vx_onboarding_completo', 'value' => '1' ],
                [
                    'relation' => 'OR',
                    [ 'key' => 'vx_foto', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => 'vx_foto', 'value' => '',  'compare' => '=' ],
                    [ 'key' => 'vx_foto', 'value' => '0', 'compare' => '=' ],
                ],
            ],
        ] );

        if ( ! class_exists( 'VX_Mailer' ) ) {
            return;
        }

        $link = home_url( '/editar-perfil/' );

        foreach ( $ids as $uid ) {
            $foto = (int) get_user_meta( $uid, 'vx_foto', true );
            if ( $foto ) {
                continue; // por si acaso ya tiene
            }
            $user = class_exists( 'VX_User' ) ? VX_User::get( $uid ) : null;
            if ( ! $user ) {
                continue;
            }
            VX_Mailer::send(
                $user->get_email(),
                '', // el asunto lo define la plantilla editable
                'foto_recordatorio',
                [ 'nombre' => $user->get_nombre(), 'link' => $link ]
            );
        }
    }

    /** Limpia el evento programado (para desactivación del plugin). */
    public static function unschedule(): void
    {
        $ts = wp_next_scheduled( self::HOOK );
        if ( $ts ) {
            wp_unschedule_event( $ts, self::HOOK );
        }
    }
}
