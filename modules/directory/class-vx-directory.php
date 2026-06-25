<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Consultas del directorio de miembros.
 * Soporta filtros por país, industria, fundador y búsqueda de texto libre.
 */
class VX_Directory
{
    const PER_PAGE = 20;

    /**
     * Consulta principal del directorio con filtros y paginación.
     *
     * @param array $args  pais, industria, fundador, busqueda, page, per_page
     * @return array  ['users' => VX_User[], 'total' => int, 'pages' => int, 'pagination' => array]
     */
    public static function get_members( array $args = [] ): array
    {
        $pais      = sanitize_text_field( $args['pais']      ?? '' );
        $industria = sanitize_text_field( $args['industria'] ?? '' );
        $busqueda  = sanitize_text_field( $args['busqueda']  ?? '' );
        $fundador  = ! empty( $args['fundador'] );
        $page      = max( 1, absint( $args['page'] ?? 1 ) );
        $per_page  = absint( $args['per_page'] ?? self::PER_PAGE );

        // ── Query base ───────────────────────────────────────────────────────
        $meta_query = [
            'relation' => 'AND',
            [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
            [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
        ];

        // Post-beta: excluir usuarios gratuitos que no son fundadores
        // (en beta auto_fundador=1 → todos son fundadores → no aplica este filtro)
        if ( get_option( 'vx_auto_fundador', '1' ) !== '1' ) {
            $meta_query[] = [
                'relation' => 'OR',
                [ 'key' => VX_User_Meta::ES_FUNDADOR, 'value' => '1' ],
                [ 'key' => VX_User_Meta::PLAN,        'value' => [ 'mensual', 'anual', 'preferencial' ], 'compare' => 'IN' ],
            ];
        }

        if ( $pais ) {
            $meta_query[] = [ 'key' => VX_User_Meta::PAIS, 'value' => $pais ];
        }

        if ( $industria ) {
            $meta_query[] = [ 'key' => VX_User_Meta::INDUSTRIA, 'value' => $industria ];
        }

        if ( $fundador ) {
            // Fix: los fundadores tienen vx_es_fundador='1', no vx_plan='fundador'
            $meta_query[] = [ 'key' => VX_User_Meta::ES_FUNDADOR, 'value' => '1' ];
        }

        $all_ids = get_users( [
            'role'       => 'subscriber',
            'number'     => -1,
            'fields'     => 'ids',
            'meta_query' => $meta_query,
            'meta_key'   => VX_User_Meta::APELLIDO,
            'orderby'    => 'meta_value',
            'order'      => 'ASC',
        ] );

        // ── Búsqueda de texto libre ──────────────────────────────────────────
        if ( $busqueda !== '' ) {
            $all_ids = self::filter_by_search( $all_ids, $busqueda );
        }

        $total    = count( $all_ids );
        $offset   = ( $page - 1 ) * $per_page;
        $page_ids = array_slice( $all_ids, $offset, $per_page );

        $users = array_values( array_filter( array_map( [ 'VX_User', 'get' ], $page_ids ) ) );

        return [
            'users'      => $users,
            'total'      => $total,
            'pages'      => (int) ceil( $total / max( 1, $per_page ) ),
            'pagination' => VX_Pagination::build( $total, $per_page, $page ),
        ];
    }

    /**
     * Filtra user_ids buscando el término en: nombre, apellido, ciudad, país,
     * industria, bio, offer_tags, seek_tags, profile_tags, empresa, cargo, sector.
     *
     * Estrategia eficiente:
     * 1. Pre-carga todos los user meta de una vez con get_users(meta_key).
     * 2. Pre-carga todas las empresas de los candidatos con una sola query.
     * 3. Filtra en PHP sin hacer queries por usuario.
     */
    private static function filter_by_search( array $user_ids, string $busqueda ): array
    {
        $term = mb_strtolower( trim( $busqueda ) );
        if ( $term === '' || empty( $user_ids ) ) return $user_ids;

        // ── 1. Pre-cargar user meta (un get_user_meta por clave es cacheado por WP) ──
        $user_data = [];
        foreach ( $user_ids as $uid ) {
            // get_user_meta con ID vacío carga todo el meta del usuario en el cache
            $all_meta = get_user_meta( $uid );  // carga todo de una vez para este user
            $get = function( string $key ) use ( $all_meta ): string {
                $v = $all_meta[ $key ][0] ?? '';
                return is_string( $v ) ? $v : '';
            };
            $get_arr = function( string $key ) use ( $all_meta ): string {
                $v = $all_meta[ $key ][0] ?? [];
                if ( is_string( $v ) ) {
                    $decoded = maybe_unserialize( $v );
                    $v = is_array( $decoded ) ? $decoded : [];
                }
                return is_array( $v ) ? implode( ' ', $v ) : '';
            };

            $user_data[ $uid ] = implode( ' ', array_filter( [
                $get( VX_User_Meta::NOMBRE ),
                $get( VX_User_Meta::APELLIDO ),
                $get( VX_User_Meta::CIUDAD ),
                $get( VX_User_Meta::PAIS ),
                $get( VX_User_Meta::INDUSTRIA ),
                $get( VX_User_Meta::BIO ),
                $get_arr( VX_User_Meta::OFFER_TAGS ),
                $get_arr( VX_User_Meta::SEEK_TAGS ),
                $get_arr( VX_User_Meta::PROFILE_TAGS ),
            ] ) );
        }

        // ── 2. Pre-cargar todas las empresas de estos usuarios en una sola query ──
        $empresa_posts = get_posts( [
            'post_type'      => 'vx_empresa',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [ [ 'key' => 'vx_user_id', 'value' => $user_ids, 'compare' => 'IN' ] ],
        ] );

        // Mapear user_id → texto de empresa
        $empresa_text = [];
        foreach ( $empresa_posts as $post ) {
            $uid = (int) get_post_meta( $post->ID, 'vx_user_id', true );
            $text = implode( ' ', array_filter( [
                $post->post_title,
                get_post_meta( $post->ID, 'vx_cargo',       true ),
                get_post_meta( $post->ID, 'vx_sector',      true ),
                get_post_meta( $post->ID, 'vx_industria',   true ),
                get_post_meta( $post->ID, 'vx_descripcion', true ),
            ] ) );
            $empresa_text[ $uid ] = ( $empresa_text[ $uid ] ?? '' ) . ' ' . $text;
        }

        // ── 3. Filtrar ───────────────────────────────────────────────────────────
        return array_values( array_filter( $user_ids, function ( int $uid ) use ( $term, $user_data, $empresa_text ) {
            $haystack = mb_strtolower(
                ( $user_data[ $uid ] ?? '' ) . ' ' . ( $empresa_text[ $uid ] ?? '' )
            );
            return str_contains( $haystack, $term );
        } ) );
    }

    /**
     * Formatea un usuario para renderizar como tarjeta de directorio.
     */
    public static function format_for_card( int $user_id ): ?array
    {
        $user = VX_User::get( $user_id );
        return $user ? $user->to_card_array() : null;
    }

    /**
     * Devuelve los valores únicos de país e industria de usuarios activos.
     *
     * @return array  ['paises' => string[], 'industrias' => string[]]
     */
    public static function get_filters(): array
    {
        $ids = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
                [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
            ],
        ] );

        $paises = $industrias = [];
        foreach ( $ids as $uid ) {
            $p = get_user_meta( $uid, VX_User_Meta::PAIS,      true );
            $i = get_user_meta( $uid, VX_User_Meta::INDUSTRIA, true );
            if ( $p ) $paises[]     = $p;
            if ( $i ) $industrias[] = $i;
        }

        $paises     = array_values( array_unique( $paises ) );
        $industrias = array_values( array_unique( $industrias ) );
        sort( $paises );
        sort( $industrias );

        return compact( 'paises', 'industrias' );
    }
}
