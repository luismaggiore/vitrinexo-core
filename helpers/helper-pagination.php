<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper para construir datos de paginación.
 */
class VX_Pagination
{
    /**
     * Construye los datos necesarios para renderizar la paginación.
     *
     * @param int $total     Total de items.
     * @param int $per_page  Items por página.
     * @param int $current   Página actual (1-indexed).
     * @return array
     */
    public static function build( int $total, int $per_page, int $current ): array
    {
        $per_page    = max( 1, $per_page );
        $total_pages = max( 1, (int) ceil( $total / $per_page ) );
        $current     = max( 1, min( $current, $total_pages ) );

        $pages = [];
        $range = 2; // páginas a mostrar alrededor de la actual

        for ( $i = max( 1, $current - $range ); $i <= min( $total_pages, $current + $range ); $i++ ) {
            $pages[] = $i;
        }

        return [
            'total'       => $total,
            'per_page'    => $per_page,
            'current'     => $current,
            'total_pages' => $total_pages,
            'has_prev'    => $current > 1,
            'has_next'    => $current < $total_pages,
            'prev'        => $current - 1,
            'next'        => $current + 1,
            'pages'       => $pages,
            'show_first'  => ! in_array( 1, $pages, true ),
            'show_last'   => ! in_array( $total_pages, $pages, true ),
            'offset'      => ( $current - 1 ) * $per_page,
        ];
    }
}
