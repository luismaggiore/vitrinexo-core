<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper para normalización y procesamiento de tags de offer/seek.
 */
class VX_Tag_Helper
{
    const MAX_TAGS = 5;

    /**
     * Normaliza un array de tags: lowercase, trim, sin duplicados, máximo 5.
     *
     * @param array $tags
     * @return array
     */
    public static function normalize( array $tags ): array
    {
        $tags = array_map( 'trim', $tags );
        $tags = array_map( 'strtolower', $tags );
        $tags = array_filter( $tags ); // eliminar vacíos
        $tags = array_unique( $tags );
        $tags = array_values( $tags ); // re-indexar
        return array_slice( $tags, 0, self::MAX_TAGS );
    }

    /**
     * Calcula la intersección entre dos arrays de tags.
     * Usado para el cálculo de matches.
     *
     * @param array $a
     * @param array $b
     * @return array Tags en común
     */
    public static function intersect( array $a, array $b ): array
    {
        $a = self::normalize( $a );
        $b = self::normalize( $b );
        return array_values( array_intersect( $a, $b ) );
    }

    /**
     * Calcula un score de coincidencia entre 0 y 1.
     *
     * @param array $a
     * @param array $b
     * @return float
     */
    public static function score( array $a, array $b ): float
    {
        if ( empty( $a ) || empty( $b ) ) {
            return 0.0;
        }

        $common = count( self::intersect( $a, $b ) );
        $total  = max( count( $a ), count( $b ) );

        return $total > 0 ? round( $common / $total, 2 ) : 0.0;
    }
}
