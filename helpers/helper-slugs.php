<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper para generación de slugs únicos de perfil.
 */
class VX_Slug_Helper
{
    /**
     * Genera un slug único para el perfil de un usuario.
     * Formato: nombre-apellido (sanitizado).
     * Si ya existe, agrega sufijo numérico: nombre-apellido-2.
     *
     * @param string $nombre
     * @param string $apellido
     * @param int    $exclude_user_id  Usuario a excluir de la búsqueda (para edición).
     * @return string
     */
    public static function generate( string $nombre, string $apellido, int $exclude_user_id = 0 ): string
    {
        $base = sanitize_title( $nombre . ' ' . $apellido );

        if ( empty( $base ) ) {
            $base = 'usuario';
        }

        $slug    = $base;
        $counter = 2;

        while ( self::exists( $slug, $exclude_user_id ) ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Verifica si un slug ya existe en la base de datos.
     *
     * @param string $slug
     * @param int    $exclude_user_id
     * @return bool
     */
    private static function exists( string $slug, int $exclude_user_id = 0 ): bool
    {
        $args = [
            'meta_key'   => VX_User_Meta::PERFIL_SLUG,
            'meta_value' => $slug,
            'number'     => 1,
            'fields'     => 'ID',
        ];

        if ( $exclude_user_id > 0 ) {
            $args['exclude'] = [ $exclude_user_id ];
        }

        $users = get_users( $args );

        return ! empty( $users );
    }
}
