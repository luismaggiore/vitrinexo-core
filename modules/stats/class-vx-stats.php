<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * VX_Stats — Contadores estadísticos de Vitrinexo.
 *
 * Filosofía: los contadores son ADITIVOS y PERMANENTES.
 * Nunca se decrementan aunque el usuario borre su cuenta o se elimine
 * el CPT de conexión. El global acumula para siempre en wp_options.
 *
 * Claves de user_meta:
 *   vx_stat_sol_recibidas  — solicitudes de conexión recibidas (receptor)
 *   vx_stat_conexiones     — conexiones efectivas (emisor + receptor)
 *
 * Clave de wp_options:
 *   vx_stat_global_conexiones — total de conexiones efectivas en toda la plataforma
 */
class VX_Stats
{
    // ── Meta keys ────────────────────────────────────────────────────────────

    const META_SOL_RECIBIDAS = 'vx_stat_sol_recibidas';
    const META_CONEXIONES    = 'vx_stat_conexiones';
    const OPT_GLOBAL         = 'vx_stat_global_conexiones';

    // ── Hooks ────────────────────────────────────────────────────────────────

    public static function init(): void
    {
        // Solicitud enviada → sumar al contador del receptor
        add_action( 'vx_connection_received', [ self::class, 'on_connection_received' ], 10, 2 );

        // Conexión aceptada → sumar a ambos usuarios + global
        add_action( 'vx_connection_accepted', [ self::class, 'on_connection_accepted' ], 10, 2 );
    }

    // ── Callbacks ────────────────────────────────────────────────────────────

    /**
     * Se dispara cuando el receptor recibe una solicitud de conexión.
     * Incrementa su contador de solicitudes recibidas.
     *
     * @param int $receptor_id
     * @param int $conexion_id  (no usado, pero disponible para extensiones)
     */
    public static function on_connection_received( int $receptor_id, int $conexion_id ): void
    {
        self::increment_user( $receptor_id, self::META_SOL_RECIBIDAS );
    }

    /**
     * Se dispara cuando una conexión es aceptada.
     * Incrementa el contador de conexiones efectivas de AMBOS usuarios
     * y el contador global de la plataforma.
     *
     * @param int $emisor_id
     * @param int $conexion_id
     */
    public static function on_connection_accepted( int $emisor_id, int $conexion_id ): void
    {
        // Recuperar el receptor_id desde el CPT
        $receptor_id = (int) get_post_meta( $conexion_id, VX_Connection_Meta::RECEPTOR_ID, true );

        self::increment_user( $emisor_id,   self::META_CONEXIONES );
        if ( $receptor_id && $receptor_id !== $emisor_id ) {
            self::increment_user( $receptor_id, self::META_CONEXIONES );
        }

        self::increment_global();
    }

    // ── Métodos de escritura ──────────────────────────────────────────────────

    /**
     * Incrementa en 1 un contador de user_meta.
     * Usa UPDATE atómico directo en DB para evitar condiciones de carrera.
     *
     * @param int    $user_id
     * @param string $meta_key
     */
    private static function increment_user( int $user_id, string $meta_key ): void
    {
        if ( ! $user_id ) return;

        global $wpdb;

        // Intentar UPDATE en fila existente
        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->usermeta}
             SET meta_value = meta_value + 1
             WHERE user_id = %d AND meta_key = %s",
            $user_id,
            $meta_key
        ) );

        // Si no existía la fila, insertar con valor inicial 1
        if ( 0 === $updated ) {
            add_user_meta( $user_id, $meta_key, 1, /* unique= */ true );
        }
    }

    /**
     * Incrementa en 1 el contador global de conexiones.
     * Atómico mediante UPDATE directo en wp_options.
     */
    private static function increment_global(): void
    {
        global $wpdb;

        // Asegurar que la opción exista antes del UPDATE
        if ( false === get_option( self::OPT_GLOBAL ) ) {
            add_option( self::OPT_GLOBAL, 0, '', 'no' ); // autoload=no
        }

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options}
             SET option_value = option_value + 1
             WHERE option_name = %s",
            self::OPT_GLOBAL
        ) );

        // Limpiar caché de options para que get_option devuelva el valor fresco
        wp_cache_delete( self::OPT_GLOBAL, 'options' );
    }

    // ── Métodos de lectura ────────────────────────────────────────────────────

    /**
     * Solicitudes de conexión recibidas por un usuario (históricas).
     *
     * @param int $user_id
     * @return int
     */
    public static function get_sol_recibidas( int $user_id ): int
    {
        return (int) get_user_meta( $user_id, self::META_SOL_RECIBIDAS, true );
    }

    /**
     * Conexiones efectivas (aceptadas) de un usuario (históricas).
     *
     * @param int $user_id
     * @return int
     */
    public static function get_conexiones( int $user_id ): int
    {
        return (int) get_user_meta( $user_id, self::META_CONEXIONES, true );
    }

    /**
     * Total global de conexiones efectivas realizadas en la plataforma.
     * Independiente de cuentas eliminadas.
     *
     * @return int
     */
    public static function get_total_conexiones(): int
    {
        return (int) get_option( self::OPT_GLOBAL, 0 );
    }

    /**
     * Top N usuarios por conexiones efectivas.
     * Útil para el futuro "Influencer de Vitrinexo".
     *
     * @param int $limit
     * @return array[]  [user_id, conexiones]
     */
    public static function get_top_conectores( int $limit = 10 ): array
    {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, CAST(meta_value AS UNSIGNED) AS conexiones
             FROM {$wpdb->usermeta}
             WHERE meta_key = %s
             ORDER BY conexiones DESC
             LIMIT %d",
            self::META_CONEXIONES,
            $limit
        ), ARRAY_A );

        return $results ?: [];
    }

    /**
     * Inicializa los contadores retroactivos a partir de los CPTs existentes.
     * Útil para migrar datos históricos antes de que existiera el sistema de stats.
     * Se llama UNA VEZ desde el admin — no destructiva, suma lo que falta.
     *
     * @return array{sol_recibidas: int, conexiones: int, global: int}
     */
    public static function backfill_from_existing_connections(): array
    {
        global $wpdb;

        // Leer todas las conexiones aceptadas
        $conexiones = $wpdb->get_results(
            "SELECT p.ID as post_id,
                    MAX(CASE WHEN pm.meta_key = 'vx_conexion_emisor_id'   THEN pm.meta_value END) AS emisor_id,
                    MAX(CASE WHEN pm.meta_key = 'vx_conexion_receptor_id' THEN pm.meta_value END) AS receptor_id,
                    MAX(CASE WHEN pm.meta_key = 'vx_conexion_estado'      THEN pm.meta_value END) AS estado
             FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
             WHERE p.post_type = 'vx_conexion'
               AND p.post_status = 'publish'
               AND pm.meta_key IN ('vx_conexion_emisor_id','vx_conexion_receptor_id','vx_conexion_estado')
             GROUP BY p.ID",
            ARRAY_A
        );

        $sol_recibidas_map = []; // receptor_id → count
        $conexiones_map    = []; // user_id → count
        $global            = 0;

        foreach ( $conexiones as $row ) {
            $emisor_id   = (int) $row['emisor_id'];
            $receptor_id = (int) $row['receptor_id'];
            $estado      = $row['estado'];

            // Toda solicitud enviada cuenta para sol_recibidas del receptor
            if ( $receptor_id ) {
                $sol_recibidas_map[ $receptor_id ] = ( $sol_recibidas_map[ $receptor_id ] ?? 0 ) + 1;
            }

            // Solo las aceptadas cuentan para conexiones efectivas
            if ( 'aceptado' === $estado ) {
                if ( $emisor_id )   $conexiones_map[ $emisor_id ]   = ( $conexiones_map[ $emisor_id ]   ?? 0 ) + 1;
                if ( $receptor_id ) $conexiones_map[ $receptor_id ] = ( $conexiones_map[ $receptor_id ] ?? 0 ) + 1;
                $global++;
            }
        }

        // Guardar sol_recibidas (sólo si el usuario no tiene ya un valor ≥)
        foreach ( $sol_recibidas_map as $uid => $count ) {
            $existing = (int) get_user_meta( $uid, self::META_SOL_RECIBIDAS, true );
            if ( $count > $existing ) {
                update_user_meta( $uid, self::META_SOL_RECIBIDAS, $count );
            }
        }

        // Guardar conexiones efectivas
        foreach ( $conexiones_map as $uid => $count ) {
            $existing = (int) get_user_meta( $uid, self::META_CONEXIONES, true );
            if ( $count > $existing ) {
                update_user_meta( $uid, self::META_CONEXIONES, $count );
            }
        }

        // Guardar global (sólo si lo calculado es mayor — preserva crecimiento futuro)
        $global_existing = self::get_total_conexiones();
        if ( $global > $global_existing ) {
            update_option( self::OPT_GLOBAL, $global, 'no' );
            wp_cache_delete( self::OPT_GLOBAL, 'options' );
        }

        return [
            'sol_recibidas' => array_sum( $sol_recibidas_map ),
            'conexiones'    => (int) ( array_sum( $conexiones_map ) / 2 ), // cada conexión suma en ambos
            'global'        => $global,
        ];
    }
}
