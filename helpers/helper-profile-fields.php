<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Única fuente de verdad de qué campos del perfil de un usuario participan
 * del flujo de "propuesta de perfil" (admin -> usuario, requiere consentimiento).
 * Reusado por el guardado directo y por aceptar una propuesta, para que
 * ambos caminos apliquen los campos exactamente igual (evita el bug de
 * normalización divergente que tuvimos con los tags).
 *
 * VX_User_Meta::ESTADO queda fuera a propósito: es un control de cuenta
 * administrativo, no contenido del perfil del usuario, y siempre se guarda
 * directo desde VX_Admin_Users::save_profile_fields().
 */
class VX_Profile_Fields
{
    /**
     * @return array[] Cada entrada: post_key, meta, label, type (text|textarea|url|tags|empresa).
     */
    public static function definitions(): array
    {
        return [
            [ 'post_key' => 'vx_nombre',           'meta' => VX_User_Meta::NOMBRE,     'label' => 'Nombre',                    'type' => 'text' ],
            [ 'post_key' => 'vx_apellido',         'meta' => VX_User_Meta::APELLIDO,   'label' => 'Apellido',                  'type' => 'text' ],
            [ 'post_key' => 'vx_empresa_inicial',  'meta' => 'vx_empresa_inicial',     'label' => 'Empresa',                   'type' => 'empresa' ],
            [ 'post_key' => VX_User_Meta::CARGO,      'meta' => VX_User_Meta::CARGO,      'label' => 'Cargo',                     'type' => 'text' ],
            [ 'post_key' => VX_User_Meta::CIUDAD,     'meta' => VX_User_Meta::CIUDAD,     'label' => 'Ciudad',                    'type' => 'text' ],
            [ 'post_key' => VX_User_Meta::PAIS,       'meta' => VX_User_Meta::PAIS,       'label' => 'País',                      'type' => 'text' ],
            [ 'post_key' => VX_User_Meta::INDUSTRIA,  'meta' => VX_User_Meta::INDUSTRIA,  'label' => 'Industria',                 'type' => 'text' ],
            [ 'post_key' => VX_User_Meta::TELEFONO,   'meta' => VX_User_Meta::TELEFONO,   'label' => 'Teléfono',                  'type' => 'text' ],
            [ 'post_key' => VX_User_Meta::LINKEDIN,   'meta' => VX_User_Meta::LINKEDIN,   'label' => 'LinkedIn',                  'type' => 'url' ],
            [ 'post_key' => VX_User_Meta::BIO,        'meta' => VX_User_Meta::BIO,        'label' => 'Bio profesional',           'type' => 'textarea' ],
            [ 'post_key' => VX_User_Meta::OFFER_TEXTO,'meta' => VX_User_Meta::OFFER_TEXTO,'label' => 'Descripción de "Ofrece"',   'type' => 'textarea' ],
            [ 'post_key' => VX_User_Meta::SEEK_TEXTO, 'meta' => VX_User_Meta::SEEK_TEXTO, 'label' => 'Descripción de "Busca"',    'type' => 'textarea' ],
            [ 'post_key' => VX_User_Meta::OFFER_TAGS, 'meta' => VX_User_Meta::OFFER_TAGS, 'label' => 'Tags que ofrece',           'type' => 'tags' ],
            [ 'post_key' => VX_User_Meta::SEEK_TAGS,  'meta' => VX_User_Meta::SEEK_TAGS,  'label' => 'Tags que busca',            'type' => 'tags' ],
        ];
    }

    /**
     * Antepone https:// si la URL no trae esquema.
     */
    public static function normalize_url( string $url ): string
    {
        $url = trim( $url );
        if ( $url === '' ) return '';
        if ( ! preg_match( '#^https?://#i', $url ) ) {
            $url = 'https://' . ltrim( $url, '/' );
        }
        return esc_url_raw( $url );
    }

    /**
     * Sanitiza los campos presentes en $post según su tipo declarado en definitions().
     * No aplica nada a la DB — solo parsea/sanitiza.
     *
     * @param array $post  Normalmente $_POST del form de edición de usuario.
     * @return array [post_key => valor_sanitizado]
     */
    public static function parse_posted( array $post ): array
    {
        $out = [];
        foreach ( self::definitions() as $def ) {
            $key = $def['post_key'];
            if ( ! isset( $post[ $key ] ) ) continue;

            $raw = wp_unslash( $post[ $key ] );
            switch ( $def['type'] ) {
                case 'textarea':
                    $out[ $key ] = sanitize_textarea_field( $raw );
                    break;
                case 'url':
                    $out[ $key ] = self::normalize_url( sanitize_text_field( $raw ) );
                    break;
                case 'tags':
                    $out[ $key ] = VX_Tag_Helper::normalize( explode( ',', sanitize_text_field( $raw ) ) );
                    break;
                default: // 'text', 'empresa'
                    $out[ $key ] = trim( sanitize_text_field( $raw ) );
                    break;
            }
        }
        return $out;
    }

    /**
     * Filtra $nuevos dejando solo los campos cuyo valor difiere del actual en user meta.
     * Evita que una propuesta muestre campos que el admin no tocó realmente
     * (el form siempre postea el valor actual de todos los inputs).
     *
     * @param int   $user_id
     * @param array $nuevos  Resultado de parse_posted().
     * @return array [post_key => valor_nuevo], solo los que cambiaron.
     */
    public static function diff( int $user_id, array $nuevos ): array
    {
        $cambiados = [];
        foreach ( self::definitions() as $def ) {
            $key = $def['post_key'];
            if ( ! array_key_exists( $key, $nuevos ) ) continue;

            $actual = get_user_meta( $user_id, $def['meta'], true );

            if ( 'tags' === $def['type'] ) {
                $actual_norm = array_map( 'mb_strtolower', (array) $actual );
                $nuevo_norm  = array_map( 'mb_strtolower', (array) $nuevos[ $key ] );
                sort( $actual_norm ); sort( $nuevo_norm );
                if ( $actual_norm === $nuevo_norm ) continue;
            } else {
                if ( (string) $actual === (string) $nuevos[ $key ] ) continue;
            }

            $cambiados[ $key ] = $nuevos[ $key ];
        }
        return $cambiados;
    }

    /**
     * Única función que aplica campos de perfil a un usuario real — usada
     * tanto por el guardado directo ("Actualizar usuario") como por aceptar
     * una propuesta pendiente. $campos ya debe venir sanitizado (parse_posted()
     * o lo que quedó guardado en la propuesta).
     *
     * @param int   $user_id
     * @param array $campos  [post_key => valor]
     */
    public static function apply( int $user_id, array $campos ): void
    {
        $by_key = [];
        foreach ( self::definitions() as $def ) {
            $by_key[ $def['post_key'] ] = $def;
        }

        foreach ( $campos as $key => $valor ) {
            $def = $by_key[ $key ] ?? null;
            if ( ! $def ) continue;

            if ( 'empresa' === $def['type'] ) {
                update_user_meta( $user_id, $def['meta'], $valor );
                if ( $valor !== '' && class_exists( 'VX_User' ) ) {
                    $vx  = VX_User::get( $user_id );
                    $emp = $vx ? $vx->get_empresa_activa() : null;
                    if ( $emp && $emp->post_title !== $valor ) {
                        wp_update_post( [ 'ID' => $emp->ID, 'post_title' => $valor ] );
                    }
                }
                continue;
            }

            // Nombre/Apellido: nunca los vaciamos por accidente (mismo resguardo que ya existía).
            if ( in_array( $key, [ 'vx_nombre', 'vx_apellido' ], true ) && '' === $valor ) {
                continue;
            }

            update_user_meta( $user_id, $def['meta'], $valor );
        }

        // Nombre/Apellido: además del meta, sincroniza display_name + slug público.
        if ( array_key_exists( 'vx_nombre', $campos ) || array_key_exists( 'vx_apellido', $campos ) ) {
            $n = (string) get_user_meta( $user_id, VX_User_Meta::NOMBRE,   true );
            $a = (string) get_user_meta( $user_id, VX_User_Meta::APELLIDO, true );
            if ( $n !== '' ) {
                wp_update_user( [ 'ID' => $user_id, 'display_name' => trim( $n . ' ' . $a ) ] );
                if ( class_exists( 'VX_Slug_Helper' ) ) {
                    update_user_meta( $user_id, VX_User_Meta::PERFIL_SLUG, VX_Slug_Helper::generate( $n, $a, $user_id ) );
                }
            }
        }
    }
}
