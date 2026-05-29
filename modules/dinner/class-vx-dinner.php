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
                [ 'key' => VX_Dinner_Meta::ESTADO, 'value' => 'abierto' ],
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

    public function has_space(): bool
    {
        return count( $this->get_asignados() ) < 4;
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
}
