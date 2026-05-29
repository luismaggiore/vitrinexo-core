<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Disparadores automáticos de notificaciones.
 * Único lugar que llama VX_Notification::create().
 * Escucha hooks de otros módulos y crea notificaciones según corresponda.
 */
class VX_Notification_Triggers
{
    /**
     * Registra los hooks. Llamado en init.
     */
    public static function init(): void
    {
        add_action( 'vx_connection_received',  [ self::class, 'on_connection_received' ],  10, 2 );
        add_action( 'vx_connection_accepted',  [ self::class, 'on_connection_accepted' ],  10, 2 );
        add_action( 'vx_profile_visited',      [ self::class, 'on_profile_visited' ],      10, 2 );
        add_action( 'vx_user_favorited',       [ self::class, 'on_user_favorited' ],       10, 2 );
        add_action( 'vx_dinner_available',     [ self::class, 'on_dinner_available' ],     10, 1 );
    }

    /**
     * Conexión nueva recibida → notif al receptor.
     *
     * @param int $receptor_id
     * @param int $conexion_id
     */
    public static function on_connection_received( int $receptor_id, int $conexion_id ): void
    {
        $emisor_id = (int) get_post_meta( $conexion_id, VX_Connection_Meta::EMISOR_ID, true );

        VX_Notification::create(
            $receptor_id,
            'conexion_nueva',
            home_url( '/conexiones/' ),
            $emisor_id,
            [ 'conexion_id' => $conexion_id ]
        );
    }

    /**
     * Conexión aceptada → notif al emisor.
     *
     * @param int $emisor_id
     * @param int $conexion_id
     */
    public static function on_connection_accepted( int $emisor_id, int $conexion_id ): void
    {
        $receptor_id = (int) get_post_meta( $conexion_id, VX_Connection_Meta::RECEPTOR_ID, true );

        VX_Notification::create(
            $emisor_id,
            'conexion_aceptada',
            home_url( '/conexiones/' ),
            $receptor_id,
            [ 'conexion_id' => $conexion_id ]
        );
    }

    /**
     * Visita al perfil → notif al visitado.
     *
     * @param int $visited_id   Usuario cuyo perfil fue visitado.
     * @param int $visitor_id   Usuario que visitó.
     */
    public static function on_profile_visited( int $visited_id, int $visitor_id ): void
    {
        if ( $visited_id === $visitor_id ) return; // no notificar al visitar el propio perfil

        VX_Notification::create(
            $visited_id,
            'visita_perfil',
            home_url( '/notificaciones/' ),
            $visitor_id
        );
    }

    /**
     * Usuario guardado como favorito → notif al guardado.
     *
     * @param int $favorited_id  Usuario que fue guardado.
     * @param int $saver_id      Usuario que guardó.
     */
    public static function on_user_favorited( int $favorited_id, int $saver_id ): void
    {
        VX_Notification::create(
            $favorited_id,
            'favorito',
            home_url( '/notificaciones/' ),
            $saver_id
        );
    }

    /**
     * Nuevo 4Dinner disponible → notif a todos los miembros activos.
     *
     * @param int $dinner_id
     */
    public static function on_dinner_available( int $dinner_id ): void
    {
        $dinner = VX_Dinner::get( $dinner_id );
        if ( ! $dinner ) return;

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

        $ciudad = $dinner->get_ciudad();

        foreach ( $active_users as $uid ) {
            VX_Notification::create(
                $uid,
                'dinner_disponible',
                home_url( '/events/4dinner/' ),
                0,
                [ 'dinner_id' => $dinner_id, 'ciudad' => $ciudad ]
            );
        }
    }
}
