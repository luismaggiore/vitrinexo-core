<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Búsqueda full-text en el directorio de miembros.
 */
class VX_Search
{
    /**
     * Búsqueda principal — busca en nombre, empresa, bio, tags.
     *
     * @param string $query    Texto a buscar.
     * @param array  $filters  Filtros adicionales (pais, comunidad, fundador, page, per_page).
     * @return array  Mismo formato que VX_Directory::get_members().
     */
    public static function search( string $query, array $filters = [] ): array
    {
        $query    = sanitize_text_field( $query );
        $page     = max( 1, absint( $filters['page'] ?? 1 ) );
        $per_page = absint( $filters['per_page'] ?? VX_Directory::PER_PAGE );

        if ( empty( $query ) ) {
            return VX_Directory::get_members( $filters );
        }

        // Obtener todos los usuarios activos primero
        $all_ids = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                'relation' => 'AND',
                [ 'key' => VX_User_Meta::ESTADO,              'value' => 'activo' ],
                [ 'key' => VX_User_Meta::ONBOARDING_COMPLETO, 'value' => '1' ],
            ],
        ] );

        $matching = [];
        $query_lower = strtolower( $query );

        foreach ( $all_ids as $uid ) {
            $user = VX_User::get( $uid );
            if ( ! $user ) continue;

            $score = self::calculate_relevance( $user, $query_lower, $filters );
            if ( $score > 0 ) {
                $matching[ $uid ] = $score;
            }
        }

        // Ordenar por relevancia
        arsort( $matching );
        $matching_ids = array_keys( $matching );

        $total    = count( $matching_ids );
        $offset   = ( $page - 1 ) * $per_page;
        $page_ids = array_slice( $matching_ids, $offset, $per_page );

        $users = array_filter( array_map( [ 'VX_User', 'get' ], $page_ids ) );

        return [
            'users'      => array_values( $users ),
            'total'      => $total,
            'pages'      => (int) ceil( $total / max( 1, $per_page ) ),
            'pagination' => VX_Pagination::build( $total, $per_page, $page ),
            'query'      => $query,
        ];
    }

    /**
     * Calcula la relevancia de un usuario para un query.
     * Score 0 = no match. Score > 0 = match (mayor = más relevante).
     *
     * @param VX_User $user
     * @param string  $query  Ya en lowercase.
     * @param array   $filters
     * @return int
     */
    private static function calculate_relevance( VX_User $user, string $query, array $filters ): int
    {
        $score = 0;

        // Filtros de contexto (deben cumplirse todos)
        if ( ! empty( $filters['pais'] ) && $user->get_pais() !== $filters['pais'] ) {
            return 0;
        }
        if ( ! empty( $filters['comunidad'] ) && ! $user->is_in_community( $filters['comunidad'] ) ) {
            return 0;
        }
        if ( ! empty( $filters['fundador'] ) && ! $user->is_founder() ) {
            return 0;
        }

        // Nombre completo (peso alto)
        if ( str_contains( strtolower( $user->get_nombre_completo() ), $query ) ) {
            $score += 10;
        }

        // Empresa activa
        $empresa = $user->get_empresa_activa();
        if ( $empresa && str_contains( strtolower( $empresa->post_title ), $query ) ) {
            $score += 8;
        }

        // Bio
        if ( str_contains( strtolower( $user->get_bio() ), $query ) ) {
            $score += 4;
        }

        // Tags offer (peso medio)
        foreach ( $user->get_offer_tags() as $tag ) {
            if ( str_contains( strtolower( $tag ), $query ) ) {
                $score += 5;
                break;
            }
        }

        // Tags seek
        foreach ( $user->get_seek_tags() as $tag ) {
            if ( str_contains( strtolower( $tag ), $query ) ) {
                $score += 3;
                break;
            }
        }

        // País
        if ( str_contains( strtolower( $user->get_pais() ), $query ) ) {
            $score += 2;
        }

        return $score;
    }
}
