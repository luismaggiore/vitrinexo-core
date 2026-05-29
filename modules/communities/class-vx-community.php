<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gestión de comunidades (Out2B, Woman, Senior).
 */
class VX_Community
{
    const COMMUNITIES = [ 'out2b', 'woman', 'senior' ];

    private static function get_meta_key( string $community ): ?string
    {
        $map = [
            'out2b'  => VX_User_Meta::COMUNIDAD_OUT2B,
            'woman'  => VX_User_Meta::COMUNIDAD_WOMAN,
            'senior' => VX_User_Meta::COMUNIDAD_SENIOR,
        ];
        return $map[ $community ] ?? null;
    }

    public static function activate( int $user_id, string $community ): void
    {
        $key = self::get_meta_key( $community );
        if ( $key ) {
            update_user_meta( $user_id, $key, true );
            do_action( 'vx_community_joined', $user_id, $community );
        }
    }

    public static function deactivate( int $user_id, string $community ): void
    {
        $key = self::get_meta_key( $community );
        if ( $key ) {
            update_user_meta( $user_id, $key, false );
        }
    }

    public static function is_member( int $user_id, string $community ): bool
    {
        $key = self::get_meta_key( $community );
        return $key ? (bool) get_user_meta( $user_id, $key, true ) : false;
    }

    /**
     * Devuelve miembros de una comunidad con filtros opcionales.
     *
     * @param string $community  'out2b' | 'woman' | 'senior'
     * @param array  $args       page, per_page
     * @return array
     */
    public static function get_members( string $community, array $args = [] ): array
    {
        $key = self::get_meta_key( $community );
        if ( ! $key ) {
            return [ 'users' => [], 'total' => 0, 'pages' => 0 ];
        }

        $page     = max( 1, absint( $args['page'] ?? 1 ) );
        $per_page = absint( $args['per_page'] ?? 20 );

        $meta_query = [
            'relation' => 'AND',
            [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
            [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
            [ 'key' => $key,                               'value' => '1' ],
        ];

        // Senior: solo los verificados
        if ( 'senior' === $community ) {
            $meta_query[] = [ 'key' => VX_User_Meta::SENIOR_VERIFICADO, 'value' => '1' ];
        }

        $all_ids = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => $meta_query,
        ] );

        $total    = count( $all_ids );
        $offset   = ( $page - 1 ) * $per_page;
        $page_ids = array_slice( $all_ids, $offset, $per_page );
        $users    = array_filter( array_map( [ 'VX_User', 'get' ], $page_ids ) );

        return [
            'users'      => array_values( $users ),
            'total'      => $total,
            'pages'      => (int) ceil( $total / max( 1, $per_page ) ),
            'pagination' => VX_Pagination::build( $total, $per_page, $page ),
        ];
    }
}
