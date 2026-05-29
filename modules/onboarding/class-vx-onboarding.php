<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gestión del wizard de onboarding en 6 pasos.
 */
class VX_Onboarding
{
    const PASOS = [ 1, 2, 3, 4, 5, 6 ];

    /**
     * Devuelve el estado actual del onboarding del usuario.
     *
     * @param int $user_id
     * @return array
     */
    public static function get_state( int $user_id ): array
    {
        $paso_actual = (int) get_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO, true );
        $datos       = [];

        foreach ( self::PASOS as $paso ) {
            $raw = get_user_meta( $user_id, 'vx_onboarding_datos_' . $paso, true );
            if ( $raw ) {
                $decoded = json_decode( $raw, true );
                if ( is_array( $decoded ) ) {
                    $datos[ $paso ] = $decoded;
                }
            }
        }

        return [
            'paso_actual' => max( 1, $paso_actual ),
            'datos'       => $datos,
            'completo'    => (bool) get_user_meta( $user_id, VX_User_Meta::ONBOARDING_COMPLETO, true ),
        ];
    }

    /**
     * Guarda los datos de un paso del onboarding.
     * Si partial=true, no valida campos obligatorios (para retroceso).
     *
     * @param int   $user_id
     * @param int   $paso
     * @param array $datos
     * @param bool  $partial
     * @return array  ['success' => bool, 'errors' => array]
     */
    public static function save_step( int $user_id, int $paso, array $datos, bool $partial = false ): array
    {
        if ( ! in_array( $paso, self::PASOS, true ) ) {
            return [ 'success' => false, 'errors' => [ 'paso_invalido' ] ];
        }

        if ( ! $partial ) {
            $validation = self::validate_step( $paso, $datos );
            if ( ! $validation['valid'] ) {
                return [ 'success' => false, 'errors' => $validation['errors'] ];
            }
        }

        // Guardar datos del paso
        update_user_meta( $user_id, 'vx_onboarding_datos_' . $paso, wp_json_encode( $datos ) );

        // Actualizar paso actual
        $current_paso = (int) get_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO, true );
        if ( $paso >= $current_paso ) {
            update_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO, $paso );
        }

        // Persistir datos en user meta si no es partial
        if ( ! $partial ) {
            self::persist_step_data( $user_id, $paso, $datos );
        }

        return [ 'success' => true, 'errors' => [] ];
    }

    /**
     * Completa el onboarding.
     * Marca como completo, dispara email de bienvenida.
     *
     * @param int $user_id
     */
    public static function complete( int $user_id ): void
    {
        update_user_meta( $user_id, VX_User_Meta::ONBOARDING_COMPLETO, true );
        update_user_meta( $user_id, VX_User_Meta::ONBOARDING_PASO, 6 );

        // Si solicitó Senior, notificar admin
        $state = self::get_state( $user_id );
        if ( ! empty( $state['datos'][5]['senior'] ) ) {
            VX_Senior_Verification::request( $user_id );
        }

        // Activar comunidades
        if ( ! empty( $state['datos'][5] ) ) {
            $comunidades = $state['datos'][5];
            if ( ! empty( $comunidades['out2b'] ) )  VX_Community::activate( $user_id, 'out2b' );
            if ( ! empty( $comunidades['woman'] ) )  VX_Community::activate( $user_id, 'woman' );
        }

        // Activar plan Fundador por defecto (fase inicial)
        $membresia = VX_Membership::get( $user_id );
        if ( empty( $membresia->get_plan() ) ) {
            $expiry = strtotime( '+180 days' );
            $membresia->activate( 'fundador', $expiry );
        }

        do_action( 'vx_onboarding_completed', $user_id );
    }

    /**
     * Valida los campos obligatorios de un paso.
     *
     * @param int   $paso
     * @param array $datos
     * @return array  ['valid' => bool, 'errors' => array]
     */
    public static function validate_step( int $paso, array $datos ): array
    {
        $errors = [];

        switch ( $paso ) {
            case 2:
                if ( empty( trim( $datos['nombre'] ?? '' ) ) )   $errors[] = 'nombre_requerido';
                if ( empty( trim( $datos['apellido'] ?? '' ) ) )  $errors[] = 'apellido_requerido';
                if ( empty( trim( $datos['pais'] ?? '' ) ) )      $errors[] = 'pais_requerido';
                break;

            case 3:
                if ( empty( trim( $datos['empresa_nombre'] ?? '' ) ) ) $errors[] = 'empresa_nombre_requerido';
                if ( empty( trim( $datos['empresa_cargo'] ?? '' ) ) )  $errors[] = 'empresa_cargo_requerido';
                break;

            case 4:
                // Tags son opcionales — el usuario puede completarlos después
                break;
        }

        return [ 'valid' => empty( $errors ), 'errors' => $errors ];
    }

    /**
     * Persiste los datos de un paso en los user meta definitivos.
     *
     * @param int   $user_id
     * @param int   $paso
     * @param array $datos
     */
    private static function persist_step_data( int $user_id, int $paso, array $datos ): void
    {
        switch ( $paso ) {
            case 2:
                update_user_meta( $user_id, VX_User_Meta::NOMBRE,             sanitize_text_field( $datos['nombre']             ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::APELLIDO,           sanitize_text_field( $datos['apellido']           ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::BIO,                sanitize_textarea_field( $datos['bio']            ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::CIUDAD,             sanitize_text_field( $datos['ciudad']             ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::PAIS,               sanitize_text_field( $datos['pais']               ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::CONTACTO_PREFERIDO, sanitize_text_field( $datos['contacto_preferido'] ?? 'email' ) );

                if ( ! empty( $datos['foto_id'] ) ) {
                    update_user_meta( $user_id, VX_User_Meta::FOTO, absint( $datos['foto_id'] ) );
                }

                // Generar slug de perfil
                $nombre   = sanitize_text_field( $datos['nombre']   ?? '' );
                $apellido = sanitize_text_field( $datos['apellido'] ?? '' );
                if ( $nombre && $apellido ) {
                    $slug = VX_Slug_Helper::generate( $nombre, $apellido, $user_id );
                    update_user_meta( $user_id, VX_User_Meta::PERFIL_SLUG, $slug );
                }
                break;

            case 3:
                // Crear o actualizar la empresa del usuario
                $empresa_activa = self::get_or_create_empresa( $user_id, $datos );
                if ( $empresa_activa ) {
                    update_post_meta( $empresa_activa, 'vx_empresa_activa', '1' );
                }
                break;

            case 4:
                $offer_tags = VX_Tag_Helper::normalize( (array) ( $datos['offer_tags'] ?? [] ) );
                $seek_tags  = VX_Tag_Helper::normalize( (array) ( $datos['seek_tags']  ?? [] ) );
                update_user_meta( $user_id, VX_User_Meta::OFFER_TAGS,  $offer_tags );
                update_user_meta( $user_id, VX_User_Meta::SEEK_TAGS,   $seek_tags );
                update_user_meta( $user_id, VX_User_Meta::OFFER_TEXTO, sanitize_textarea_field( $datos['offer_texto'] ?? '' ) );
                update_user_meta( $user_id, VX_User_Meta::SEEK_TEXTO,  sanitize_textarea_field( $datos['seek_texto']  ?? '' ) );
                break;

            case 5:
                // Las comunidades se activan en complete()
                break;
        }
    }

    /**
     * Crea o actualiza la empresa del usuario en el paso 3.
     *
     * @param int   $user_id
     * @param array $datos
     * @return int|null  Post ID de la empresa creada/actualizada.
     */
    private static function get_or_create_empresa( int $user_id, array $datos ): ?int
    {
        // Buscar empresa existente del usuario sin nombre de empresa aún asignado
        $existing = get_posts( [
            'post_type'      => 'vx_empresa',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'author'         => $user_id,
            'fields'         => 'ids',
            'meta_query'     => [
                [ 'key' => 'vx_user_id', 'value' => $user_id ],
            ],
        ] );

        $post_id = ! empty( $existing ) ? (int) $existing[0] : null;

        $empresa_nombre = sanitize_text_field( $datos['empresa_nombre'] ?? '' );
        if ( empty( $empresa_nombre ) ) return $post_id;

        $post_data = [
            'post_title'  => $empresa_nombre,
            'post_type'   => 'vx_empresa',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];

        if ( $post_id ) {
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
            if ( is_wp_error( $post_id ) ) return null;
        }

        update_post_meta( $post_id, 'vx_user_id',     $user_id );
        update_post_meta( $post_id, 'vx_cargo',     sanitize_text_field( $datos['empresa_cargo']    ?? '' ) );
        update_post_meta( $post_id, 'vx_web',       esc_url_raw( $datos['empresa_web']              ?? '' ) );
        update_post_meta( $post_id, 'vx_linkedin',  esc_url_raw( $datos['empresa_linkedin']         ?? '' ) );
        update_post_meta( $post_id, 'vx_descripcion', sanitize_textarea_field( $datos['empresa_desc'] ?? '' ) );

        if ( ! empty( $datos['empresa_logo_id'] ) ) {
            update_post_meta( $post_id, 'vx_logo', absint( $datos['empresa_logo_id'] ) );
        }

        return $post_id;
    }
}
