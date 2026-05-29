<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Meta boxes y columnas para el CPT vx_dinner en el admin.
 */
class VX_Admin_Dinner
{
    public static function init(): void
    {
        add_filter( 'manage_vx_dinner_posts_columns',       [ self::class, 'add_columns' ] );
        add_action( 'manage_vx_dinner_posts_custom_column', [ self::class, 'render_column' ], 10, 2 );

        add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
        add_action( 'save_post_vx_dinner', [ self::class, 'save_meta_box' ] );

        // Acción: asignar/desasignar usuario desde el admin
        add_action( 'admin_action_vx_dinner_asignar',   [ self::class, 'handle_asignar' ] );
        add_action( 'admin_action_vx_dinner_desasignar',[ self::class, 'handle_desasignar' ] );
        add_action( 'admin_action_vx_dinner_confirmar', [ self::class, 'handle_confirmar' ] );
    }

    public static function add_columns( array $columns ): array
    {
        unset( $columns['date'] );

        return array_merge( $columns, [
            'vx_ciudad'    => 'Ciudad',
            'vx_fecha'     => 'Fecha',
            'vx_cupos'     => 'Cupos',
            'vx_estado'    => 'Estado',
            'vx_acciones'  => 'Acciones',
        ] );
    }

    public static function render_column( string $column, int $post_id ): void
    {
        switch ( $column ) {
            case 'vx_ciudad':
                echo esc_html( get_post_meta( $post_id, VX_Dinner_Meta::CIUDAD, true ) );
                break;

            case 'vx_fecha':
                $ts = strtotime( (string) get_post_meta( $post_id, VX_Dinner_Meta::FECHA, true ) );
                echo $ts ? esc_html( date_i18n( 'd/m/Y', $ts ) ) : '—';
                break;

            case 'vx_cupos':
                $asignados = (array) get_post_meta( $post_id, VX_Dinner_Meta::ASIGNADOS, true );
                $total     = (int) ( get_post_meta( $post_id, VX_Dinner_Meta::CUPOS_TOTAL, true ) ?: 4 );
                echo count( $asignados ) . ' / ' . $total;
                break;

            case 'vx_estado':
                $estado = get_post_meta( $post_id, VX_Dinner_Meta::ESTADO, true );
                $colors = [
                    'abierto'   => '#22c55e',
                    'completo'  => '#f59e0b',
                    'realizado' => '#6b7280',
                    'cancelado' => '#ef4444',
                ];
                $color = $colors[ $estado ] ?? '#999';
                echo '<span style="color:' . $color . ';font-weight:600">' . esc_html( $estado ) . '</span>';
                break;

            case 'vx_acciones':
                $confirm_url = wp_nonce_url(
                    admin_url( 'admin.php?action=vx_dinner_confirmar&dinner_id=' . $post_id ),
                    'vx_dinner_confirmar_' . $post_id
                );
                echo '<a href="' . esc_url( $confirm_url ) . '" class="button button-small">Enviar confirmaciones</a>';
                break;
        }
    }

    public static function add_meta_boxes(): void
    {
        add_meta_box(
            'vx_dinner_info',
            'Información del 4Dinner',
            [ self::class, 'render_info_meta_box' ],
            'vx_dinner',
            'normal',
            'high'
        );

        add_meta_box(
            'vx_dinner_asignados',
            'Comensales asignados',
            [ self::class, 'render_asignados_meta_box' ],
            'vx_dinner',
            'side',
            'default'
        );
    }

    public static function render_info_meta_box( WP_Post $post ): void
    {
        wp_nonce_field( 'vx_dinner_save_' . $post->ID, 'vx_dinner_nonce' );

        $fields = [
            'Ciudad'      => [ VX_Dinner_Meta::CIUDAD,      'text' ],
            'País'        => [ VX_Dinner_Meta::PAIS,        'text' ],
            'Fecha'       => [ VX_Dinner_Meta::FECHA,       'date' ],
            'Restaurante' => [ VX_Dinner_Meta::RESTAURANTE, 'text' ],
            'Dirección'   => [ VX_Dinner_Meta::DIRECCION,   'text' ],
            'Estado'      => [ VX_Dinner_Meta::ESTADO,      'select' ],
        ];

        echo '<table class="form-table">';
        foreach ( $fields as $label => [ $key, $type ] ) {
            $value = get_post_meta( $post->ID, $key, true );
            echo '<tr><th><label>' . esc_html( $label ) . '</label></th><td>';

            if ( 'select' === $type ) {
                echo '<select name="' . esc_attr( $key ) . '">';
                foreach ( [ 'abierto', 'completo', 'realizado', 'cancelado' ] as $opt ) {
                    echo '<option value="' . esc_attr( $opt ) . '"' . selected( $value, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text">';
            }

            echo '</td></tr>';
        }
        echo '</table>';
    }

    public static function render_asignados_meta_box( WP_Post $post ): void
    {
        $asignados = (array) get_post_meta( $post->ID, VX_Dinner_Meta::ASIGNADOS, true );

        if ( empty( $asignados ) ) {
            echo '<p>Sin comensales asignados.</p>';
            return;
        }

        echo '<ul>';
        foreach ( $asignados as $uid ) {
            $user   = VX_User::get( (int) $uid );
            $nombre = $user ? $user->get_nombre_completo() : '#' . $uid;
            $remove_url = wp_nonce_url(
                admin_url( 'admin.php?action=vx_dinner_desasignar&dinner_id=' . $post->ID . '&user_id=' . $uid ),
                'vx_dinner_desasignar_' . $post->ID
            );
            echo '<li>' . esc_html( $nombre ) . ' <a href="' . esc_url( $remove_url ) . '" style="color:red">✕</a></li>';
        }
        echo '</ul>';
    }

    public static function save_meta_box( int $post_id ): void
    {
        if ( ! isset( $_POST['vx_dinner_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['vx_dinner_nonce'], 'vx_dinner_save_' . $post_id ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [
            VX_Dinner_Meta::CIUDAD,
            VX_Dinner_Meta::PAIS,
            VX_Dinner_Meta::FECHA,
            VX_Dinner_Meta::RESTAURANTE,
            VX_Dinner_Meta::DIRECCION,
            VX_Dinner_Meta::ESTADO,
        ];

        foreach ( $fields as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }

    public static function handle_asignar(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );

        $dinner_id = absint( $_GET['dinner_id'] ?? 0 );
        $user_id   = absint( $_GET['user_id']   ?? 0 );

        check_admin_referer( 'vx_dinner_asignar_' . $dinner_id );

        $result = VX_Dinner_Assignment::assign( $dinner_id, $user_id );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( admin_url( 'post.php?post=' . $dinner_id . '&action=edit&vx_error=' . $result->get_error_code() ) );
        } else {
            wp_safe_redirect( admin_url( 'post.php?post=' . $dinner_id . '&action=edit&vx_asignado=1' ) );
        }
        exit;
    }

    public static function handle_desasignar(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );

        $dinner_id = absint( $_GET['dinner_id'] ?? 0 );
        $user_id   = absint( $_GET['user_id']   ?? 0 );

        check_admin_referer( 'vx_dinner_desasignar_' . $dinner_id );

        VX_Dinner_Assignment::unassign( $dinner_id, $user_id );

        wp_safe_redirect( admin_url( 'post.php?post=' . $dinner_id . '&action=edit' ) );
        exit;
    }

    public static function handle_confirmar(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );

        $dinner_id = absint( $_GET['dinner_id'] ?? 0 );
        check_admin_referer( 'vx_dinner_confirmar_' . $dinner_id );

        $sent = VX_Dinner_Assignment::send_confirmations( $dinner_id );

        wp_safe_redirect( admin_url( 'edit.php?post_type=vx_dinner&vx_confirmaciones=' . $sent ) );
        exit;
    }
}
