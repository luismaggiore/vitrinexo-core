<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Consultas del directorio de miembros.
 */
class VX_Directory
{
    const PER_PAGE = 20;

    /**
     * Consulta principal del directorio con filtros y paginación.
     *
     * @param array $args  Filtros: pais, rubro, comunidad, fundador, page, per_page
     * @return array  ['users' => VX_User[], 'total' => int, 'pages' => int, 'pagination' => array]
     */
    public static function get_members( array $args = [] ): array
    {
        $pais      = sanitize_text_field( $args['pais']      ?? '' );
        $comunidad = sanitize_text_field( $args['comunidad'] ?? '' );
        $fundador  = ! empty( $args['fundador'] );
        $page      = max( 1, absint( $args['page'] ?? 1 ) );
        $per_page  = absint( $args['per_page'] ?? self::PER_PAGE );

        $query_args = [
            'role'   => 'subscriber',
            'number' => -1,
            'fields' => 'ids',
        ];

        // Solo usuarios activos con onboarding completo
        $meta_query = [
            'relation' => 'AND',
            [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
            [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
        ];

        if ( $pais ) {
            $meta_query[] = [
                'key'     => VX_User_Meta::PAIS,
                'value'   => $pais,
                'compare' => '=',
            ];
        }

        if ( $comunidad && in_array( $comunidad, [ 'out2b', 'woman', 'senior' ], true ) ) {
            $key_map = [
                'out2b'  => VX_User_Meta::COMUNIDAD_OUT2B,
                'woman'  => VX_User_Meta::COMUNIDAD_WOMAN,
                'senior' => VX_User_Meta::COMUNIDAD_SENIOR,
            ];
            $meta_query[] = [
                'key'   => $key_map[ $comunidad ],
                'value' => '1',
            ];
        }

        if ( $fundador ) {
            $meta_query[] = [
                'key'   => VX_User_Meta::PLAN,
                'value' => 'fundador',
            ];
        }

        $query_args['meta_query'] = $meta_query;
        $query_args['meta_key']   = VX_User_Meta::APELLIDO;
        $query_args['orderby']    = 'meta_value';
        $query_args['order']      = 'ASC';

        $all_ids = get_users( $query_args );
        $total   = count( $all_ids );

        // Paginación manual
        $offset   = ( $page - 1 ) * $per_page;
        $page_ids = array_slice( $all_ids, $offset, $per_page );

        $users = array_filter( array_map( [ 'VX_User', 'get' ], $page_ids ) );

        return [
            'users'      => array_values( $users ),
            'total'      => $total,
            'pages'      => (int) ceil( $total / $per_page ),
            'pagination' => VX_Pagination::build( $total, $per_page, $page ),
        ];
    }

    /**
     * Formatea un usuario para renderizar como tarjeta de directorio.
     *
     * @param int $user_id
     * @return array|null
     */
    public static function format_for_card( int $user_id ): ?array
    {
        $user = VX_User::get( $user_id );
        return $user ? $user->to_card_array() : null;
    }

    /**
     * Devuelve las opciones disponibles de filtros (países y rubros únicos).
     *
     * @return array  ['paises' => string[], 'rubros' => string[]]
     */
    public static function get_filters(): array
    {
        $users = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'meta_query' => [
                [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
                [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
            ],
        ] );

        $paises = [];
        foreach ( $users as $uid ) {
            $pais = get_user_meta( $uid, VX_User_Meta::PAIS, true );
            if ( $pais ) $paises[] = $pais;
        }

        $paises = array_values( array_unique( $paises ) );
        sort( $paises );

        return [
            'paises' => $paises,
        ];
    }
}
