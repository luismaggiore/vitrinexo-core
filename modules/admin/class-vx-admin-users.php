<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Columnas personalizadas en la lista de usuarios del admin.
 */
class VX_Admin_Users
{
    public static function init(): void
    {
        add_filter( 'manage_users_columns',       [ self::class, 'add_columns' ] );
        add_filter( 'manage_users_custom_column', [ self::class, 'render_column' ], 10, 3 );
        add_filter( 'manage_users_sortable_columns', [ self::class, 'sortable_columns' ] );

        // Acción de aprobar verificación manual
        add_action( 'admin_action_vx_aprobar_verificacion', [ self::class, 'handle_aprobar_verificacion' ] );
        add_action( 'admin_action_vx_verificar_senior',     [ self::class, 'handle_verificar_senior' ] );
        add_action( 'admin_action_vx_activar_directo',      [ self::class, 'handle_activar_directo' ] );

        // Filtro por estado en la lista
        add_action( 'restrict_manage_users', [ self::class, 'add_estado_filter' ] );
        add_filter( 'pre_get_users',         [ self::class, 'filter_by_estado' ] );
    }

    public static function add_columns( array $columns ): array
    {
        $new_columns = [];

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'email' === $key ) {
                $new_columns['vx_estado']    = 'Estado';
                $new_columns['vx_plan']      = 'Plan';
                $new_columns['vx_comunidades'] = 'Comunidades';
            }
        }

        return $new_columns;
    }

    public static function render_column( string $output, string $column, int $user_id ): string
    {
        switch ( $column ) {
            case 'vx_estado':
                $estado = get_user_meta( $user_id, VX_User_Meta::ESTADO, true );
                $labels = [
                    'activo'    => '<span class="dashicons dashicons-yes" style="color:green"></span> Activo',
                    'pendiente' => '<span class="dashicons dashicons-clock" style="color:orange"></span> Pendiente',
                    'rechazado' => '<span class="dashicons dashicons-no" style="color:red"></span> Rechazado',
                ];

                $html = $labels[ $estado ] ?? '<span class="dashicons dashicons-minus" style="color:#999"></span> Sin estado';

                if ( 'pendiente' === $estado ) {
                    $url   = wp_nonce_url(
                        admin_url( 'users.php?action=vx_aprobar_verificacion&user_id=' . $user_id ),
                        'vx_aprobar_' . $user_id
                    );
                    $html .= ' <a href="' . esc_url( $url ) . '" class="button button-small">Aprobar (email)</a>';
                }

                if ( 'activo' !== $estado ) {
                    $url   = wp_nonce_url(
                        admin_url( 'users.php?action=vx_activar_directo&user_id=' . $user_id ),
                        'vx_activar_' . $user_id
                    );
                    $html .= ' <a href="' . esc_url( $url ) . '" class="button button-small button-primary">Activar</a>';
                }

                return $html;

            case 'vx_plan':
                $plan  = get_user_meta( $user_id, VX_User_Meta::PLAN, true ) ?: '—';
                $estado = get_user_meta( $user_id, VX_User_Meta::PLAN_ESTADO, true );
                $color = 'activo' === $estado ? 'green' : '#999';
                return '<span style="color:' . $color . '">' . esc_html( $plan ) . '</span>';

            case 'vx_comunidades':
                $coms = [];
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_OUT2B, true ) ) $coms[] = 'Out2B';
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_WOMAN,  true ) ) $coms[] = 'Woman';
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_SENIOR, true ) ) $coms[] = 'Senior';
                return $coms ? esc_html( implode( ', ', $coms ) ) : '—';
        }

        return $output;
    }

    public static function sortable_columns( array $columns ): array
    {
        $columns['vx_estado'] = 'vx_estado';
        $columns['vx_plan']   = 'vx_plan';
        return $columns;
    }

    public static function add_estado_filter(): void
    {
        $estado = isset( $_GET['vx_estado_filter'] ) ? sanitize_key( $_GET['vx_estado_filter'] ) : '';
        ?>
        <select name="vx_estado_filter">
            <option value="">Todos los estados</option>
            <option value="activo"    <?php selected( $estado, 'activo' ); ?>>Activo</option>
            <option value="pendiente" <?php selected( $estado, 'pendiente' ); ?>>Pendiente</option>
            <option value="rechazado" <?php selected( $estado, 'rechazado' ); ?>>Rechazado</option>
        </select>
        <?php
    }

    public static function filter_by_estado( WP_User_Query $query ): void
    {
        if ( ! is_admin() ) return;

        $estado = isset( $_GET['vx_estado_filter'] ) ? sanitize_key( $_GET['vx_estado_filter'] ) : '';
        if ( ! $estado ) return;

        $meta_query   = (array) $query->get( 'meta_query' );
        $meta_query[] = [
            'key'   => VX_User_Meta::ESTADO,
            'value' => $estado,
        ];
        $query->set( 'meta_query', $meta_query );
    }

    public static function handle_aprobar_verificacion(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permiso.' );
        }

        $user_id = absint( $_GET['user_id'] ?? 0 );
        check_admin_referer( 'vx_aprobar_' . $user_id );

        VX_Verification::approve_manual( $user_id );

        wp_safe_redirect( admin_url( 'users.php?vx_aprobado=1' ) );
        exit;
    }

    public static function handle_verificar_senior(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permiso.' );
        }

        $user_id = absint( $_GET['user_id'] ?? 0 );
        check_admin_referer( 'vx_verificar_senior_' . $user_id );
        VX_Senior_Verification::approve( $user_id );

        wp_safe_redirect( admin_url( 'users.php?vx_senior_aprobado=1' ) );
        exit;
    }

    /**
     * Activa un usuario directamente sin email de confirmación.
     * Útil para cuentas de admin, pruebas y usuarios migrados.
     */
    public static function handle_activar_directo(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permiso.' );
        }

        $user_id = absint( $_GET['user_id'] ?? 0 );
        check_admin_referer( 'vx_activar_' . $user_id );

        if ( ! $user_id ) {
            wp_die( 'Usuario inválido.' );
        }

        $wp_user = get_userdata( $user_id );
        if ( ! $wp_user ) {
            wp_die( 'Usuario no encontrado.' );
        }

        // Marcar como activo
        update_user_meta( $user_id, VX_User_Meta::ESTADO,             'activo' );
        update_user_meta( $user_id, VX_User_Meta::ONBOARDING_COMPLETO, '1' );

        // Generar slug si no tiene
        $slug = (string) get_user_meta( $user_id, VX_User_Meta::PERFIL_SLUG, true );
        if ( ! $slug && class_exists( 'VX_Slug_Helper' ) ) {
            $nombre   = (string) get_user_meta( $user_id, VX_User_Meta::NOMBRE,   true ) ?: $wp_user->first_name;
            $apellido = (string) get_user_meta( $user_id, VX_User_Meta::APELLIDO,  true ) ?: $wp_user->last_name;
            if ( ! $nombre ) {
                // Fallback: usar display_name
                $parts    = explode( ' ', $wp_user->display_name, 2 );
                $nombre   = $parts[0];
                $apellido = $parts[1] ?? '';
            }
            $slug = VX_Slug_Helper::generate( $nombre, $apellido, $user_id );
            update_user_meta( $user_id, VX_User_Meta::PERFIL_SLUG, $slug );
        }

        wp_safe_redirect( admin_url( 'users.php?vx_activado=1' ) );
        exit;
    }
}
