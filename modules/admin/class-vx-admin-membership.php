<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gestión de membresías desde el admin de usuarios.
 */
class VX_Admin_Membership
{
    public static function init(): void
    {
        add_action( 'show_user_profile',   [ self::class, 'render_membership_section' ] );
        add_action( 'edit_user_profile',   [ self::class, 'render_membership_section' ] );
        add_action( 'personal_options_update',  [ self::class, 'save_membership_section' ] );
        add_action( 'edit_user_profile_update', [ self::class, 'save_membership_section' ] );

        // Acción: activar plan manualmente desde users.php
        add_action( 'admin_action_vx_activar_plan', [ self::class, 'handle_activar_plan' ] );
    }

    public static function render_membership_section( WP_User $user ): void
    {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $membresia   = VX_Membership::get( $user->ID );
        $plan        = $membresia->get_plan() ?: '—';
        $estado      = $membresia->get_plan_estado() ?: '—';
        $vencimiento = $membresia->get_expiry();
        $precio_pref = get_user_meta( $user->ID, VX_User_Meta::PRECIO_PREFERENTE, true );
        ?>
        <h2>Membresía Vitrinexo</h2>
        <table class="form-table">
            <tr>
                <th>Plan actual</th>
                <td><strong><?php echo esc_html( $plan ); ?></strong> (<?php echo esc_html( $estado ); ?>)</td>
            </tr>
            <tr>
                <th>Vencimiento</th>
                <td><?php echo $vencimiento ? esc_html( date_i18n( 'd/m/Y', $vencimiento ) ) : '—'; ?></td>
            </tr>
            <tr>
                <th>Precio preferente</th>
                <td>
                    <select name="vx_precio_preferente">
                        <option value="">Sin precio preferente</option>
                        <?php foreach ( VX_Plans::all() as $id => $info ) : ?>
                            <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $precio_pref, $id ); ?>>
                                <?php echo esc_html( $info['nombre'] . ' ($' . number_format( $info['precio'] / 100, 2 ) . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Activar plan manual</th>
                <td>
                    <?php wp_nonce_field( 'vx_activar_plan_' . $user->ID, 'vx_plan_nonce' ); ?>
                    <select name="vx_plan_manual">
                        <option value="">— seleccionar —</option>
                        <?php foreach ( VX_Plans::all() as $id => $info ) : ?>
                            <option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $info['nombre'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Activará el plan por su duración estándar desde hoy.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_membership_section( int $user_id ): void
    {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! isset( $_POST['vx_plan_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['vx_plan_nonce'], 'vx_activar_plan_' . $user_id ) ) return;

        // Precio preferente
        if ( isset( $_POST['vx_precio_preferente'] ) ) {
            $precio = sanitize_key( $_POST['vx_precio_preferente'] );
            if ( $precio ) {
                update_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE, $precio );
            } else {
                delete_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE );
            }
        }

        // Activar plan manual
        if ( ! empty( $_POST['vx_plan_manual'] ) ) {
            $plan_id = sanitize_key( $_POST['vx_plan_manual'] );
            $plan    = VX_Plans::get( $plan_id );

            if ( $plan ) {
                $expiry    = time() + ( $plan['duracion_dias'] * DAY_IN_SECONDS );
                $membresia = VX_Membership::get( $user_id );
                $membresia->activate( $plan_id, $expiry );
            }
        }
    }

    public static function handle_activar_plan(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );

        $user_id = absint( $_GET['user_id'] ?? 0 );
        $plan_id = sanitize_key( $_GET['plan'] ?? '' );

        check_admin_referer( 'vx_activar_plan_' . $user_id );

        $plan = VX_Plans::get( $plan_id );
        if ( ! $plan ) {
            wp_safe_redirect( admin_url( 'user-edit.php?user_id=' . $user_id . '&vx_error=plan_invalido' ) );
            exit;
        }

        $expiry    = time() + ( $plan['duracion_dias'] * DAY_IN_SECONDS );
        $membresia = VX_Membership::get( $user_id );
        $membresia->activate( $plan_id, $expiry );

        wp_safe_redirect( admin_url( 'user-edit.php?user_id=' . $user_id . '&vx_plan_activado=1' ) );
        exit;
    }
}
