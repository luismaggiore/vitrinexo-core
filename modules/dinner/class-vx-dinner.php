<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modelo del CPT vx_dinner (evento 4Dinner).
 */
class VX_Dinner
{
    private WP_Post $post;

    private function __construct( WP_Post $post )
    {
        $this->post = $post;
    }

    public static function get( int $post_id ): ?self
    {
        $post = get_post( $post_id );
        if ( ! $post || 'vx_dinner' !== $post->post_type ) {
            return null;
        }
        return new self( $post );
    }

    /**
     * Devuelve eventos futuros con estado 'abierto'.
     *
     * @return VX_Dinner[]
     */
    public static function get_upcoming(): array
    {
        $posts = get_posts( [
            'post_type'      => 'vx_dinner',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => VX_Dinner_Meta::ESTADO, 'value' => [ 'abierto', 'confirmado' ], 'compare' => 'IN' ],
                [ 'key' => VX_Dinner_Meta::FECHA,  'value' => time(), 'compare' => '>=', 'type' => 'NUMERIC' ],
            ],
            'meta_key'       => VX_Dinner_Meta::FECHA,
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ] );

        return array_filter( array_map( fn( $p ) => self::get( $p->ID ), $posts ) );
    }

    /**
     * Devuelve eventos realizados.
     *
     * @return VX_Dinner[]
     */
    public static function get_past(): array
    {
        $posts = get_posts( [
            'post_type'      => 'vx_dinner',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'meta_query'     => [
                [ 'key' => VX_Dinner_Meta::ESTADO, 'value' => 'realizado' ],
            ],
            'meta_key'       => VX_Dinner_Meta::FECHA,
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ] );

        return array_filter( array_map( fn( $p ) => self::get( $p->ID ), $posts ) );
    }

    // ── Getters ──────────────────────────────────────────────────

    public function get_id(): int { return $this->post->ID; }
    public function get_title(): string { return $this->post->post_title; }

    public function get_ciudad(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Dinner_Meta::CIUDAD, true );
    }

    public function get_pais(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Dinner_Meta::PAIS, true );
    }

    public function get_fecha(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Dinner_Meta::FECHA, true );
    }

    public function get_restaurante(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Dinner_Meta::RESTAURANTE, true );
    }

    public function get_direccion(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Dinner_Meta::DIRECCION, true );
    }

    public function get_estado(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Dinner_Meta::ESTADO, true );
    }

    public function get_asignados(): array
    {
        $arr = get_post_meta( $this->post->ID, VX_Dinner_Meta::ASIGNADOS, true );
        return is_array( $arr ) ? array_map( 'intval', $arr ) : [];
    }

    public function get_interesados(): array
    {
        $arr = get_post_meta( $this->post->ID, VX_Dinner_Meta::INTERESADOS, true );
        return is_array( $arr ) ? array_map( 'intval', $arr ) : [];
    }

    public function get_assigned_users(): array
    {
        return array_filter( array_map( [ 'VX_User', 'get' ], $this->get_asignados() ) );
    }

    public function get_interested_users(): array
    {
        return array_filter( array_map( [ 'VX_User', 'get' ], $this->get_interesados() ) );
    }

    /**
     * Siempre hay espacio — 4 es el MÍNIMO para confirmar la cena, no el máximo.
     * El admin puede seguir añadiendo personas después de los primeros 4.
     */
    public function has_space(): bool
    {
        return true;
    }

    /**
     * La cena está confirmada cuando hay al menos 4 asistentes.
     */
    public function is_confirmed(): bool
    {
        return count( $this->get_asignados() ) >= 4;
    }

    public function is_user_interested( int $user_id ): bool
    {
        return in_array( $user_id, $this->get_interesados(), true );
    }

    public function is_user_assigned( int $user_id ): bool
    {
        return in_array( $user_id, $this->get_asignados(), true );
    }

    // ── Setters ──────────────────────────────────────────────────

    public function add_interest( int $user_id ): void
    {
        $interesados = $this->get_interesados();
        if ( in_array( $user_id, $interesados, true ) ) return;

        $interesados[] = $user_id;
        update_post_meta( $this->post->ID, VX_Dinner_Meta::INTERESADOS, $interesados );

        // También actualizar user meta
        $user_dinners = (array) get_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, true );
        if ( ! in_array( $this->post->ID, $user_dinners, true ) ) {
            $user_dinners[] = $this->post->ID;
            update_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, $user_dinners );
        }
    }

    public function remove_interest( int $user_id ): void
    {
        $interesados = array_diff( $this->get_interesados(), [ $user_id ] );
        update_post_meta( $this->post->ID, VX_Dinner_Meta::INTERESADOS, array_values( $interesados ) );

        $user_dinners = array_diff( (array) get_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, true ), [ $this->post->ID ] );
        update_user_meta( $user_id, VX_User_Meta::DINNERS_INTERESADO, array_values( $user_dinners ) );
    }

    public function set_estado( string $estado ): void
    {
        update_post_meta( $this->post->ID, VX_Dinner_Meta::ESTADO, $estado );
    }

    // ── Deadline ─────────────────────────────────────────────────────────────

    /** Timestamp Unix del cierre de inscripciones (0 = sin límite). */
    public function get_deadline(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Dinner_Meta::DEADLINE, true );
    }

    /** True si el deadline ya pasó (hay uno configurado y es anterior a now). */
    public function is_deadline_passed(): bool
    {
        $dl = $this->get_deadline();
        return $dl > 0 && $dl < time();
    }

    /** True si las inscripciones están abiertas (estado abierto/confirmado y deadline no pasado). */
    public function is_open_for_registration(): bool
    {
        return in_array( $this->get_estado(), [ 'abierto', 'confirmado' ], true )
            && ! $this->is_deadline_passed();
    }

    // ── Mesas ─────────────────────────────────────────────────────────────────

    /**
     * Devuelve la estructura de mesas.
     *
     * @return array[]  Array de mesas: [['nombre'=>'Mesa 1','asignados'=>[uid,...]], ...]
     */
    public function get_mesas(): array
    {
        $raw = get_post_meta( $this->post->ID, VX_Dinner_Meta::MESAS, true );
        if ( ! $raw ) return [];
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : [];
    }

    /** Persiste la estructura de mesas. */
    public function set_mesas( array $mesas ): void
    {
        update_post_meta( $this->post->ID, VX_Dinner_Meta::MESAS, wp_json_encode( $mesas ) );
    }

    /**
     * Devuelve la mesa de un usuario (si está asignado a alguna).
     * Retorna el índice (0-based) de la mesa, o null si no está en ninguna.
     *
     * @param int $user_id
     * @return int|null  Índice de mesa (0-based), o null.
     */
    public function get_user_mesa_index( int $user_id ): ?int
    {
        foreach ( $this->get_mesas() as $idx => $mesa ) {
            if ( in_array( $user_id, (array) ( $mesa['asignados'] ?? [] ), true ) ) {
                return $idx;
            }
        }
        return null;
    }

    /**
     * Devuelve el array de la mesa de un usuario, o null.
     *
     * @param int $user_id
     * @return array|null
     */
    public function get_user_mesa( int $user_id ): ?array
    {
        $mesas = $this->get_mesas();
        foreach ( $mesas as $mesa ) {
            if ( in_array( $user_id, (array) ( $mesa['asignados'] ?? [] ), true ) ) {
                return $mesa;
            }
        }
        return null;
    }
}
