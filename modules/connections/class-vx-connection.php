<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modelo del CPT vx_conexion.
 * Abstracción de lectura — VX_Connection_Flow maneja la escritura.
 */
class VX_Connection
{
    private WP_Post $post;

    private function __construct( WP_Post $post )
    {
        $this->post = $post;
    }

    /**
     * Factory por post ID.
     *
     * @param int $post_id
     * @return VX_Connection|null
     */
    public static function get( int $post_id ): ?self
    {
        $post = get_post( $post_id );
        if ( ! $post || 'vx_conexion' !== $post->post_type ) {
            return null;
        }
        return new self( $post );
    }

    /**
     * Busca una conexión por token (aceptar o rechazar).
     *
     * @param string $token
     * @param string $tipo  'aceptar' | 'rechazar'
     * @return VX_Connection|null
     */
    public static function get_by_token( string $token, string $tipo ): ?self
    {
        $key = 'aceptar' === $tipo
            ? VX_Connection_Meta::TOKEN_ACEPTAR
            : VX_Connection_Meta::TOKEN_RECHAZAR;

        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [ 'key' => $key, 'value' => $token ],
            ],
        ] );

        return ! empty( $posts ) ? self::get( $posts[0]->ID ) : null;
    }

    /**
     * Busca una conexión entre dos usuarios (en cualquier dirección).
     * Devuelve la más reciente.
     *
     * @param int $user_a
     * @param int $user_b
     * @return VX_Connection|null
     */
    public static function get_between( int $user_a, int $user_b ): ?self
    {
        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'relation' => 'AND',
                    [ 'key' => VX_Connection_Meta::EMISOR_ID,   'value' => $user_a ],
                    [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_b ],
                ],
                [
                    'relation' => 'AND',
                    [ 'key' => VX_Connection_Meta::EMISOR_ID,   'value' => $user_b ],
                    [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_a ],
                ],
            ],
        ] );

        return ! empty( $posts ) ? self::get( $posts[0]->ID ) : null;
    }

    /**
     * Devuelve conexiones enviadas por el usuario.
     *
     * @param int    $user_id
     * @param string $estado  '' = todas
     * @return VX_Connection[]
     */
    public static function get_sent_by( int $user_id, string $estado = '' ): array
    {
        $meta_query = [
            [ 'key' => VX_Connection_Meta::EMISOR_ID, 'value' => $user_id ],
        ];

        if ( $estado ) {
            $meta_query[] = [ 'key' => VX_Connection_Meta::ESTADO, 'value' => $estado ];
        }

        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        ] );

        return array_filter( array_map( [ self::class, 'get' ], array_column( $posts, 'ID' ) ) );
    }

    /**
     * Devuelve conexiones recibidas por el usuario.
     *
     * @param int    $user_id
     * @param string $estado  '' = todas
     * @return VX_Connection[]
     */
    public static function get_received_by( int $user_id, string $estado = '' ): array
    {
        $meta_query = [
            [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_id ],
        ];

        if ( $estado ) {
            $meta_query[] = [ 'key' => VX_Connection_Meta::ESTADO, 'value' => $estado ];
        }

        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        ] );

        return array_filter( array_map( [ self::class, 'get' ], array_column( $posts, 'ID' ) ) );
    }

    /**
     * Devuelve todas las conexiones aceptadas de un usuario (en cualquier dirección).
     *
     * @param int $user_id
     * @return VX_Connection[]
     */
    public static function get_accepted( int $user_id ): array
    {
        $posts = get_posts( [
            'post_type'      => 'vx_conexion',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => VX_Connection_Meta::ESTADO, 'value' => 'aceptado' ],
                [
                    'relation' => 'OR',
                    [ 'key' => VX_Connection_Meta::EMISOR_ID,   'value' => $user_id ],
                    [ 'key' => VX_Connection_Meta::RECEPTOR_ID, 'value' => $user_id ],
                ],
            ],
        ] );

        return array_filter( array_map( [ self::class, 'get' ], array_column( $posts, 'ID' ) ) );
    }

    // ── Getters ──────────────────────────────────────────────────

    public function get_id(): int
    {
        return $this->post->ID;
    }

    public function get_emisor_id(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_ID, true );
    }

    public function get_receptor_id(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Connection_Meta::RECEPTOR_ID, true );
    }

    public function get_estado(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Connection_Meta::ESTADO, true );
    }

    public function get_pitch(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Connection_Meta::PITCH, true );
    }

    public function get_emisor_nombre(): string
    {
        return (string) get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_NOMBRE, true );
    }

    public function get_emisor_empresas(): array
    {
        $empresas = get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_EMPRESAS, true );
        return is_array( $empresas ) ? $empresas : [];
    }

    public function get_fecha_envio(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Connection_Meta::FECHA_ENVIO, true );
    }

    public function get_fecha_respuesta(): int
    {
        return (int) get_post_meta( $this->post->ID, VX_Connection_Meta::FECHA_RESPUESTA, true );
    }

    public function is_recordatorio_enviado(): bool
    {
        return (bool) get_post_meta( $this->post->ID, VX_Connection_Meta::RECORDATORIO_ENVIADO, true );
    }

    /**
     * Devuelve los datos de contacto del emisor (solo si la conexión fue aceptada).
     *
     * @return array|null
     */
    public function get_contact_data(): ?array
    {
        if ( 'aceptado' !== $this->get_estado() ) {
            return null;
        }

        return [
            'nombre'             => get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_NOMBRE,             true ),
            'email'              => get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_EMAIL,              true ),
            'telefono'           => get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_TELEFONO,           true ),
            'linkedin'           => get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_LINKEDIN,           true ),
            'contacto_preferido' => get_post_meta( $this->post->ID, VX_Connection_Meta::EMISOR_CONTACTO_PREFERIDO, true ),
            'empresas'           => $this->get_emisor_empresas(),
        ];
    }

    /**
     * Devuelve el "otro" usuario en la conexión relativo a un user_id dado.
     *
     * @param int $viewer_id
     * @return int  user_id del otro lado
     */
    public function get_other_user_id( int $viewer_id ): int
    {
        return $this->get_emisor_id() === $viewer_id
            ? $this->get_receptor_id()
            : $this->get_emisor_id();
    }
}
