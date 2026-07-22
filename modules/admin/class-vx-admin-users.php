<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── AJAX: buscar usuarios para el autocomplete del admin de 4Dinner ──────────
// AJAX handler para búsqueda de miembros en el admin de 4Dinner
add_action( 'wp_ajax_vx_dinner_buscar_miembros', [ 'VX_Admin_Dinner', 'ajax_buscar_miembros' ] );

add_action( 'wp_ajax_vx_user_search_ajax', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permiso', 403 );
    check_ajax_referer( 'vx_user_search', 'nonce' );

    $q = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
    if ( strlen( $q ) < 2 ) wp_send_json_success( [] );

    $users = get_users( [
        'search'         => '*' . $q . '*',
        'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
        'role'           => 'subscriber',
        'number'         => 10,
        'meta_query'     => [
            [ 'key' => VX_User_Meta::ESTADO, 'value' => 'activo' ],
        ],
    ] );

    $results = [];
    foreach ( $users as $wp_user ) {
        $vx = VX_User::get( $wp_user->ID );
        if ( ! $vx ) continue;
        $results[] = [
            'value' => $wp_user->ID,
            'label' => $vx->get_nombre_completo() . ' (' . $wp_user->user_email . ')',
        ];
    }

    wp_send_json_success( $results );
} );

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

        // Gestión de plan
        add_action( 'admin_action_vx_set_plan',             [ self::class, 'handle_set_plan' ] );
        add_action( 'admin_action_vx_set_vencimiento',      [ self::class, 'handle_set_vencimiento' ] );
        add_action( 'admin_action_vx_cambiar_plan',         [ self::class, 'handle_cambiar_plan' ] );
        add_action( 'admin_action_vx_dar_pionero',          [ self::class, 'handle_dar_pionero' ] );
        add_action( 'admin_action_vx_quitar_pionero',       [ self::class, 'handle_quitar_pionero' ] );
        add_action( 'admin_head-users.php',                 [ self::class, 'users_table_css' ] );
        add_action( 'admin_action_vx_normalize_ciudades',   [ self::class, 'handle_normalize_ciudades' ] );

        // Filtro por estado en la lista
        add_action( 'restrict_manage_users', [ self::class, 'add_estado_filter' ] );
        add_filter( 'pre_get_users',         [ self::class, 'filter_by_estado' ] );

        // Export CSV de todos los miembros
        add_action( 'admin_action_vx_export_members_csv', [ self::class, 'handle_export_members_csv' ] );

        // Avisos admin
        add_action( 'admin_notices', [ self::class, 'notice_normalize_ciudades' ] );
        add_action( 'admin_notices', [ self::class, 'notice_stats_backfill' ] );
        add_action( 'admin_notices', [ self::class, 'notice_export_csv' ] );
        add_action( 'admin_action_vx_stats_backfill', [ self::class, 'handle_stats_backfill' ] );
    }

    public static function add_columns( array $columns ): array
    {
        unset( $columns['posts'] );

        $new_columns = [];
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'name' === $key ) {
                $new_columns['vx_empresa']     = 'Empresa';
                $new_columns['vx_cargo']       = 'Cargo';
                $new_columns['vx_telefono']    = 'Teléfono';
            }
            if ( 'email' === $key ) {
                $new_columns['vx_estado']      = 'Estado';
                $new_columns['vx_registro']    = 'Registro';
                $new_columns['vx_plan']        = 'Plan';
                $new_columns['vx_vencimiento'] = 'Vencimiento';
                $new_columns['vx_pionero']     = 'Pionero';
            }
        }
        return $new_columns;
    }

    public static function render_column( string $output, string $column, int $user_id ): string
    {
        switch ( $column ) {
            case 'vx_empresa':
                return esc_html( get_user_meta( $user_id, 'vx_empresa_inicial', true ) ?: '—' );
            case 'vx_cargo':
                return esc_html( get_user_meta( $user_id, VX_User_Meta::CARGO, true ) ?: '—' );
            case 'vx_telefono':
                return esc_html( get_user_meta( $user_id, VX_User_Meta::TELEFONO, true ) ?: '—' );
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

            // ── Fecha de registro (solo lectura) ──────────────────────────────
            case 'vx_registro':
                $user = get_userdata( $user_id );
                return $user
                    ? '<span style="font-size:12px;color:#374151">' . date_i18n( 'd/m/Y', strtotime( $user->user_registered ) ) . '</span>'
                    : '—';

            // ── Plan (dropdown configurable) ───────────────────────────────────
            case 'vx_plan':
                $user = get_userdata( $user_id );
                if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) return '<span style="color:#9ca3af;font-size:12px">—</span>';
                $plan_actual = get_user_meta( $user_id, VX_User_Meta::PLAN, true ) ?: 'Gratuito';
                $planes      = vx_get_planes();
                $html  = '<span style="font-size:12px;font-weight:600;color:#1a2335;display:block;margin-bottom:4px">' . esc_html( $plan_actual ) . '</span>';
                $html .= '<form method="post" action="' . esc_url( admin_url( 'users.php' ) ) . '">';
                $html .= '<input type="hidden" name="action" value="vx_cambiar_plan">';
                $html .= '<input type="hidden" name="user_id" value="' . $user_id . '">';
                $html .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'vx_cambiar_plan_' . $user_id ) . '">';
                $html .= '<select name="plan" onchange="this.form.submit()" style="font-size:11px;padding:2px 4px;border:1px solid #d1d5db;border-radius:4px;color:#374151;max-width:110px">';
                $html .= '<option value="">Cambiar...</option>';
                foreach ( $planes as $p ) {
                    if ( $p !== $plan_actual ) $html .= '<option value="' . esc_attr( $p ) . '">' . esc_html( $p ) . '</option>';
                }
                $html .= '</select></form>';
                return $html;

            // ── Fecha de vencimiento (editable) ───────────────────────────────
            case 'vx_vencimiento':
                $user = get_userdata( $user_id );
                if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) return '<span style="color:#9ca3af;font-size:12px">—</span>';

                $expiry_ts   = (int) get_user_meta( $user_id, VX_User_Meta::PLAN_VENCIMIENTO, true );
                $meta_exists = metadata_exists( 'user', $user_id, VX_User_Meta::PLAN_VENCIMIENTO );

                // Auto-calcular solo si no se ha guardado nunca
                if ( ! $meta_exists && $user ) {
                    $expiry_ts = strtotime( $user->user_registered ) + ( 90 * DAY_IN_SECONDS );
                    update_user_meta( $user_id, VX_User_Meta::PLAN_VENCIMIENTO, $expiry_ts );
                }

                $expiry_date = ( $expiry_ts > 86400 ) ? date( 'Y-m-d', $expiry_ts ) : '';
                $diff        = $expiry_ts > 86400 ? ( $expiry_ts - time() ) : 0;
                $color       = ! $expiry_date ? '#9ca3af' : ( $diff < 0 ? '#dc2626' : ( $diff < 7 * DAY_IN_SECONDS ? '#f59e0b' : '#059669' ) );
                $label       = ! $expiry_date ? '—' : ( $diff < 0 ? '⚠ Venció ' . date_i18n( 'd/m/Y', $expiry_ts ) : date_i18n( 'd/m/Y', $expiry_ts ) );

                $html  = '<span style="font-size:12px;color:' . $color . ';font-weight:500;display:block;margin-bottom:4px">' . $label . '</span>';
                $html .= '<form method="post" action="' . esc_url( admin_url( 'users.php' ) ) . '" style="display:flex;gap:3px;align-items:center">';
                $html .= '<input type="hidden" name="action" value="vx_set_vencimiento">';
                $html .= '<input type="hidden" name="user_id" value="' . $user_id . '">';
                $html .= wp_nonce_field( 'vx_set_vencimiento_' . $user_id, '_wpnonce', true, false );
                $html .= '<input type="date" name="vencimiento" value="' . esc_attr( $expiry_date ) . '" style="font-size:11px;padding:2px 4px;border:1px solid #d1d5db;border-radius:4px;color:#374151">';
                $html .= '<button type="submit" class="button button-small" style="font-size:11px;padding:1px 6px">✓</button>';
                $html .= '</form>';
                return $html;

            // ── Distintivo Pionero ─────────────────────────────────────────────
            case 'vx_pionero':
                $user = get_userdata( $user_id );
                if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) return '<span style="color:#9ca3af;font-size:12px">—</span>';
                $es_fundador = (bool) get_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, true );
                if ( $es_fundador ) {
                    $rm_url = wp_nonce_url( admin_url( 'users.php?action=vx_quitar_pionero&user_id=' . $user_id ), 'vx_quitar_pionero_' . $user_id );
                    return '<span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;white-space:nowrap">⭐ Pionero</span><br>'
                         . '<a href="' . esc_url( $rm_url ) . '" style="font-size:10px;color:#dc2626;margin-top:3px;display:block" onclick="return confirm(\'¿Quitar distintivo Pionero?\')">✕ Quitar</a>';
                }
                $add_url = wp_nonce_url( admin_url( 'users.php?action=vx_dar_pionero&user_id=' . $user_id ), 'vx_dar_pionero_' . $user_id );
                return '<span style="color:#9ca3af;font-size:12px">—</span><br>'
                     . '<a href="' . esc_url( $add_url ) . '" style="font-size:10px;color:#d97706;margin-top:3px;display:block">⭐ Dar</a>';

            case 'vx_plan':
                $es_fundador = (bool) get_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, true );
                $plan        = get_user_meta( $user_id, VX_User_Meta::PLAN, true ) ?: 'gratuito';
                $expiry_ts   = (int) get_user_meta( $user_id, VX_User_Meta::PLAN_VENCIMIENTO, true );
                $expiry_date = $expiry_ts ? date( 'Y-m-d', $expiry_ts ) : '';

                // Distintivo Pionero
                $html = $es_fundador
                    ? '<span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700;margin-bottom:4px;display:inline-block">⭐ Pionero</span><br>'
                    : '';

                // Nombre del plan
                $html .= '<span style="font-weight:600;color:#1a2335">' . esc_html( ucfirst( $plan ) ) . '</span>';

                // Fecha de vencimiento editable inline
                $nonce = wp_create_nonce( 'vx_set_vencimiento_' . $user_id );
                if ( $expiry_ts ) {
                    $diff      = $expiry_ts - time();
                    $color_exp = $diff < 0 ? '#dc2626' : ( $diff < 7 * DAY_IN_SECONDS ? '#f59e0b' : '#059669' );
                    $label     = $diff < 0 ? 'Venció: ' : 'Vence: ';
                    $html .= '<br><small style="color:' . $color_exp . ';font-weight:500">' . $label . date_i18n( 'd/m/Y', $expiry_ts ) . '</small>';
                } else {
                    $html .= '<br><small style="color:#9ca3af">Sin vencimiento</small>';
                }

                // Formulario de fecha inline
                $html .= '<form method="post" action="' . esc_url( admin_url( 'users.php' ) ) . '" style="margin-top:5px;display:flex;gap:4px;align-items:center">';
                $html .= '<input type="hidden" name="action" value="vx_set_vencimiento">';
                $html .= '<input type="hidden" name="user_id" value="' . $user_id . '">';
                $html .= wp_nonce_field( 'vx_set_vencimiento_' . $user_id, '_wpnonce', true, false );
                $html .= '<input type="date" name="vencimiento" value="' . esc_attr( $expiry_date ) . '" style="font-size:11px;padding:2px 4px;border:1px solid #d1d5db;border-radius:4px;color:#374151">';
                $html .= '<button type="submit" class="button button-small" style="font-size:11px;padding:1px 6px">✓</button>';
                $html .= '</form>';

                // Toggle distintivo Pionero
                if ( $es_fundador ) {
                    $rm_url = wp_nonce_url( admin_url( 'users.php?action=vx_quitar_pionero&user_id=' . $user_id ), 'vx_quitar_pionero_' . $user_id );
                    $html .= '<a href="' . esc_url( $rm_url ) . '" style="font-size:10px;color:#dc2626;display:block;margin-top:3px" onclick="return confirm(\'¿Quitar distintivo Pionero?\')">✕ Quitar distintivo</a>';
                } else {
                    $add_url = wp_nonce_url( admin_url( 'users.php?action=vx_dar_pionero&user_id=' . $user_id ), 'vx_dar_pionero_' . $user_id );
                    $html .= '<a href="' . esc_url( $add_url ) . '" style="font-size:10px;color:#d97706;display:block;margin-top:3px">⭐ Dar distintivo</a>';
                }

                return $html;

                return $html;

            case 'vx_comunidades':
                $coms = [];
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_OUT2B, true ) ) $coms[] = 'Out2B';
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_WOMAN,  true ) ) $coms[] = 'Woman';
                if ( get_user_meta( $user_id, VX_User_Meta::COMUNIDAD_SENIOR, true ) ) $coms[] = 'Senior';
                return $coms ? esc_html( implode( ', ', $coms ) ) : '—';

            case 'vx_stat_sol':
                if ( class_exists( 'VX_Stats' ) ) {
                    $n = VX_Stats::get_sol_recibidas( $user_id );
                    return $n > 0 ? '<strong>' . $n . '</strong>' : '<span style="color:#9ca3af">0</span>';
                }
                return '—';

            case 'vx_stat_cnx':
                if ( class_exists( 'VX_Stats' ) ) {
                    $n = VX_Stats::get_conexiones( $user_id );
                    return $n > 0 ? '<strong style="color:#16a34a">' . $n . '</strong>' : '<span style="color:#9ca3af">0</span>';
                }
                return '—';
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

    /**
     * Cambia el plan de un usuario desde la lista de usuarios.
     * Acción: vx_set_plan (POST desde formulario en columna vx_plan).
     */
    public static function handle_set_plan(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sin permiso.' );
        }

        // Soporta tanto GET (links badge) como POST (formulario de plan)
        $params  = array_merge( (array) $_GET, (array) $_POST );
        $user_id = absint( $params['user_id'] ?? 0 );

        check_admin_referer( 'vx_set_plan_' . $user_id );

        if ( ! $user_id ) wp_die( 'Usuario inválido.' );

        // ── Cambio de distintivo Pionero (via GET link) ──
        if ( ! empty( $params['quitar_fundador'] ) ) {
            update_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, false );
            update_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE, false );
            wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
            exit;
        }

        if ( ! empty( $params['dar_fundador'] ) ) {
            update_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, true );
            update_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE, true );
            wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
            exit;
        }

        // ── Cambio de plan de facturación (via POST form) ──
        $plan  = sanitize_key( $params['plan'] ?? '' );
        $dias  = max( 0, absint( $params['dias'] ?? 0 ) );

        $planes_validos = [ 'gratuito', 'mensual', 'anual', 'preferencial' ];
        if ( ! in_array( $plan, $planes_validos, true ) ) {
            wp_die( 'Plan no válido.' );
        }

        $membresia = VX_Membership::get( $user_id );
        $expiry    = ( 'gratuito' === $plan || 0 === $dias )
            ? 0
            : (int) strtotime( '+' . $dias . ' days' );
        $membresia->activate( $plan, $expiry );

        wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
        exit;
    }

    /** CSS para compactar la tabla de usuarios */
    public static function users_table_css(): void
    {
        echo '<style>
        #the-list td { vertical-align: middle; padding: 8px 10px; }
        .column-vx_registro, .column-vx_pionero { width: 90px; }
        .column-vx_plan { width: 130px; }
        .column-vx_vencimiento { width: 140px; }
        .column-vx_estado { width: 120px; }
        .column-vx_empresa, .column-vx_cargo { width: 120px; }
        .column-vx_telefono { width: 110px; }
        </style>';
    }

    /** Cambia el plan de un usuario desde el dropdown inline. */
    public static function handle_cambiar_plan(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        $user_id = absint( $_POST['user_id'] ?? 0 );
        check_admin_referer( 'vx_cambiar_plan_' . $user_id );
        if ( ! $user_id ) wp_die( 'Usuario inválido.' );
        $plan = sanitize_text_field( $_POST['plan'] ?? '' );
        if ( $plan ) update_user_meta( $user_id, VX_User_Meta::PLAN, $plan );
        wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
        exit;
    }

    /** Actualiza la fecha de vencimiento del plan desde el input date inline. */
    public static function handle_set_vencimiento(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        $user_id = absint( $_POST['user_id'] ?? 0 );
        check_admin_referer( 'vx_set_vencimiento_' . $user_id );
        if ( ! $user_id ) wp_die( 'Usuario inválido.' );

        $fecha = sanitize_text_field( $_POST['vencimiento'] ?? '' );
        $ts    = $fecha ? (int) strtotime( $fecha . ' 23:59:59' ) : 0;
        update_user_meta( $user_id, VX_User_Meta::PLAN_VENCIMIENTO, $ts );
        wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
        exit;
    }

    /** Asigna el distintivo Pionero. */
    public static function handle_dar_pionero(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        $user_id = absint( $_GET['user_id'] ?? 0 );
        check_admin_referer( 'vx_dar_pionero_' . $user_id );
        update_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, true );
        update_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE, true );
        wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
        exit;
    }

    /** Quita el distintivo Pionero. */
    public static function handle_quitar_pionero(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        $user_id = absint( $_GET['user_id'] ?? 0 );
        check_admin_referer( 'vx_quitar_pionero_' . $user_id );
        update_user_meta( $user_id, VX_User_Meta::ES_FUNDADOR, false );
        update_user_meta( $user_id, VX_User_Meta::PRECIO_PREFERENTE, false );
        wp_safe_redirect( admin_url( 'users.php?vx_plan_cambiado=1' ) );
        exit;
    }

    /**
     * Muestra el aviso con el botón de normalizar ciudades en el listado de usuarios.
     */
    public static function notice_normalize_ciudades(): void
    {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'users' ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( ! empty( $_GET['vx_normalize_done'] ) ) {
            $n = (int) $_GET['vx_normalize_done'];
            echo '<div class="notice notice-success is-dismissible"><p>✅ Ciudades normalizadas: <strong>' . $n . '</strong> usuario' . ( $n !== 1 ? 's' : '' ) . ' actualizados.</p></div>';
            return;
        }

        $url = wp_nonce_url(
            admin_url( 'users.php?action=vx_normalize_ciudades' ),
            'vx_normalize_ciudades'
        );
        echo '<div class="notice notice-info"><p>'
           . '<strong>Vitrinexo:</strong> Estandarizar ciudades de usuarios al listado canónico. '
           . '<a href="' . esc_url( $url ) . '" class="button button-small" onclick="return confirm(\'¿Normalizar ciudades de todos los miembros? Se intentará mapear valores libres (ej: Providencia → Santiago). Los que no tengan match quedarán sin cambios.\')">🗺 Normalizar ciudades ahora</a>'
           . '</p></div>';
    }

    /**
     * Aviso con botón para migrar estadísticas históricas desde CPTs existentes.
     */
    public static function notice_stats_backfill(): void
    {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'users' ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( ! empty( $_GET['vx_stats_done'] ) ) {
            $r = json_decode( base64_decode( sanitize_text_field( $_GET['vx_stats_done'] ) ), true );
            if ( $r ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Stats migradas: <strong>'
                   . (int)($r['global']) . '</strong> conexiones efectivas · <strong>'
                   . (int)($r['sol_recibidas']) . '</strong> solicitudes totales. Total global actual: <strong>'
                   . ( class_exists('VX_Stats') ? VX_Stats::get_total_conexiones() : '?' )
                   . '</strong> conexiones.</p></div>';
            }
            return;
        }

        // Mostrar total actual
        $total = class_exists( 'VX_Stats' ) ? VX_Stats::get_total_conexiones() : 0;
        $url   = wp_nonce_url(
            admin_url( 'users.php?action=vx_stats_backfill' ),
            'vx_stats_backfill'
        );
        echo '<div class="notice notice-info"><p>'
           . '<strong>Vitrinexo Stats:</strong> '
           . 'Conexiones totales registradas: <strong>' . $total . '</strong>. '
           . 'Si acabas de activar el sistema de stats, migra los datos históricos: '
           . '<a href="' . esc_url( $url ) . '" class="button button-small" onclick="return confirm(\'¿Migrar estadísticas históricas desde las conexiones existentes?\')">📊 Migrar stats históricas</a>'
           . '</p></div>';
    }

    public static function handle_stats_backfill(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        check_admin_referer( 'vx_stats_backfill' );

        if ( ! class_exists( 'VX_Stats' ) ) wp_die( 'VX_Stats no disponible.' );

        $result = VX_Stats::backfill_from_existing_connections();
        $encoded = base64_encode( wp_json_encode( $result ) );

        wp_safe_redirect( admin_url( 'users.php?vx_stats_done=' . urlencode( $encoded ) ) );
        exit;
    }

    /**
     * Procesa la normalización: lee ciudad+país de cada usuario activo,
     * intenta mapearla al valor canónico y guarda si hay cambio.
     */
    public static function handle_normalize_ciudades(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        check_admin_referer( 'vx_normalize_ciudades' );

        if ( ! function_exists( 'vx_normalizar_ciudad' ) ) {
            wp_die( 'Función vx_normalizar_ciudad() no disponible.' );
        }

        $users = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'meta_query' => [
                [ 'key' => VX_User_Meta::ESTADO, 'value' => 'activo' ],
            ],
        ] );

        $cambiados = 0;

        foreach ( $users as $uid ) {
            $ciudad = (string) get_user_meta( $uid, VX_User_Meta::CIUDAD, true );
            $pais   = (string) get_user_meta( $uid, VX_User_Meta::PAIS,   true );

            if ( ! $ciudad || ! $pais ) continue;

            $normalizada = vx_normalizar_ciudad( $ciudad, $pais );

            if ( $normalizada !== $ciudad ) {
                update_user_meta( $uid, VX_User_Meta::CIUDAD, $normalizada );
                $cambiados++;
            }
        }

        wp_safe_redirect( admin_url( 'users.php?vx_normalize_done=' . $cambiados ) );
        exit;
    }

    // ── Export CSV de todos los miembros ──────────────────────────────────────

    /**
     * Aviso con botón de descarga en la pantalla de usuarios.
     */
    public static function notice_export_csv(): void
    {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'users' ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $url = wp_nonce_url(
            admin_url( 'users.php?action=vx_export_members_csv' ),
            'vx_export_members_csv'
        );

        echo '<div class="notice notice-info"><p>'
           . '<strong>Vitrinexo:</strong> '
           . '<a href="' . esc_url( $url ) . '" class="button button-primary button-small">⬇ Exportar todos los miembros (CSV)</a> '
           . '<span style="color:#6b7280;font-size:12px;margin-left:8px">Incluye tags, industria, ciudad, empresa, stats de conexiones y más.</span>'
           . '</p></div>';
    }

    /**
     * Genera y descarga el CSV completo de todos los miembros activos.
     * Incluye todos los datos relevantes para análisis.
     */
    public static function handle_export_members_csv(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso.' );
        check_admin_referer( 'vx_export_members_csv' );

        $users = get_users( [
            'role'       => 'subscriber',
            'fields'     => 'ids',
            'number'     => -1,
            'orderby'    => 'registered',
            'order'      => 'ASC',
        ] );

        $filename = 'vitrinexo-miembros-' . date( 'Ymd-His' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' );
        // BOM para Excel
        fprintf( $out, chr(0xEF) . chr(0xBB) . chr(0xBF) );

        // ── Cabecera ──────────────────────────────────────────────────────────
        fputcsv( $out, [
            // Identidad
            'ID', 'Nombre', 'Apellido', 'Email', 'Slug',
            // Datos personales
            'Ciudad', 'País', 'Género', 'Teléfono', 'LinkedIn personal',
            'Contacto preferido', 'Bio',
            // Plan y estado
            'Estado', 'Plan', 'Fundador', 'Fecha registro',
            // Empresa principal
            'Empresa', 'Cargo', 'Industria', 'Sector (tags)', 'Web empresa', 'LinkedIn empresa',
            // Tags de matching (clave para análisis)
            'Ofrece (texto)', 'Busca (texto)',
            'Offer tags', 'Seek tags', 'Profile tags',
            // Comunidades
            'Comunidad Out2B', 'Comunidad Woman', 'Comunidad Senior',
            // Estadísticas de conexiones
            'Solicitudes recibidas (histórico)', 'Conexiones efectivas (histórico)',
            // 4Dinner
            'Dinners asistidos',
        ] );

        // ── Filas ─────────────────────────────────────────────────────────────
        foreach ( $users as $uid ) {
            $u = VX_User::get( (int) $uid );
            if ( ! $u ) continue;

            $wp_user  = get_userdata( $uid );
            $empresa  = $u->get_empresa_activa();
            $emp_id   = $empresa ? $empresa->ID : 0;

            // Calcular dinners a los que asistió (estado = realizado y uid en asignados)
            $dinners_asistidos = 0;
            if ( class_exists( 'VX_Dinner' ) ) {
                $dinner_posts = get_posts( [
                    'post_type'      => 'vx_dinner',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => [
                        [ 'key' => VX_Dinner_Meta::ESTADO, 'value' => 'realizado' ],
                    ],
                ] );
                foreach ( $dinner_posts as $dp_id ) {
                    $asignados = (array) get_post_meta( $dp_id, VX_Dinner_Meta::ASIGNADOS, true );
                    if ( in_array( (int) $uid, array_map( 'intval', $asignados ), true ) ) {
                        $dinners_asistidos++;
                    }
                }
            }

            fputcsv( $out, [
                // Identidad
                $uid,
                $u->get_nombre(),
                $u->get_apellido(),
                $u->get_email(),
                $u->get_slug(),
                // Datos personales
                $u->get_ciudad(),
                $u->get_pais(),
                (string) get_user_meta( $uid, VX_User_Meta::GENERO,            true ),
                $u->get_telefono(),
                $u->get_linkedin(),
                $u->get_contacto_preferido(),
                $u->get_bio(),
                // Plan y estado
                (string) get_user_meta( $uid, VX_User_Meta::ESTADO,            true ),
                (string) get_user_meta( $uid, VX_User_Meta::PLAN,              true ),
                get_user_meta( $uid, VX_User_Meta::ES_FUNDADOR, true ) ? 'Sí' : 'No',
                $wp_user ? date( 'Y-m-d', strtotime( $wp_user->user_registered ) ) : '',
                // Empresa
                $empresa ? $empresa->post_title                                   : '',
                $emp_id  ? (string) get_post_meta( $emp_id, 'vx_cargo',     true ) : '',
                $u->get_industria(),
                $emp_id  ? (string) get_post_meta( $emp_id, 'vx_sector',    true ) : '',
                $emp_id  ? (string) get_post_meta( $emp_id, 'vx_web',       true ) : '',
                $emp_id  ? (string) get_post_meta( $emp_id, 'vx_linkedin',  true ) : '',
                // Tags
                (string) get_user_meta( $uid, VX_User_Meta::OFFER_TEXTO,       true ),
                (string) get_user_meta( $uid, VX_User_Meta::SEEK_TEXTO,        true ),
                implode( ' | ', (array) get_user_meta( $uid, VX_User_Meta::OFFER_TAGS,    true ) ),
                implode( ' | ', (array) get_user_meta( $uid, VX_User_Meta::SEEK_TAGS,     true ) ),
                implode( ' | ', (array) get_user_meta( $uid, VX_User_Meta::PROFILE_TAGS,  true ) ),
                // Comunidades
                get_user_meta( $uid, VX_User_Meta::COMUNIDAD_OUT2B,  true ) ? 'Sí' : 'No',
                get_user_meta( $uid, VX_User_Meta::COMUNIDAD_WOMAN,  true ) ? 'Sí' : 'No',
                get_user_meta( $uid, VX_User_Meta::COMUNIDAD_SENIOR, true ) ? 'Sí' : 'No',
                // Stats
                class_exists( 'VX_Stats' ) ? VX_Stats::get_sol_recibidas( $uid ) : 0,
                class_exists( 'VX_Stats' ) ? VX_Stats::get_conexiones( $uid )    : 0,
                // 4Dinner
                $dinners_asistidos,
            ] );
        }

        fclose( $out );
        exit;
    }

    // ── Campos Vitrinexo en pantalla de edición de usuario WP ───────────────

    public static function init_profile_fields(): void
    {
        add_action( 'show_user_profile',        [ self::class, 'render_profile_fields' ] );
        add_action( 'edit_user_profile',        [ self::class, 'render_profile_fields' ] );
        add_action( 'personal_options_update',  [ self::class, 'save_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ self::class, 'save_profile_fields' ] );
        // Agregar campos también al formulario "Añadir usuario"
        add_action( 'user_new_form',            [ self::class, 'render_profile_fields_new' ] );
        add_action( 'user_register',            [ self::class, 'save_profile_fields' ] );
    }

    public static function render_profile_fields_new(): void
    {
        ?>
        <h2>Datos Vitrinexo</h2>
        <table class="form-table" role="presentation">
        <?php
        $fields = [
            [ 'key' => 'vx_empresa_inicial',  'label' => 'Empresa',   'type' => 'text' ],
            [ 'key' => VX_User_Meta::CARGO,    'label' => 'Cargo',     'type' => 'text' ],
            [ 'key' => VX_User_Meta::LINKEDIN, 'label' => 'LinkedIn',  'type' => 'url'  ],
            [ 'key' => VX_User_Meta::PAIS,     'label' => 'País',      'type' => 'text' ],
            [ 'key' => VX_User_Meta::TELEFONO, 'label' => 'Teléfono',  'type' => 'tel'  ],
            [ 'key' => VX_User_Meta::ESTADO,   'label' => 'Estado',    'type' => 'text' ],
        ];
        foreach ( $fields as $f ) : ?>
        <tr>
            <th><label for="vx_new_<?php echo esc_attr( $f['key'] ); ?>"><?php echo esc_html( $f['label'] ); ?></label></th>
            <td><input type="<?php echo esc_attr( $f['type'] ); ?>" name="vx_<?php echo esc_attr( $f['key'] ); ?>" id="vx_new_<?php echo esc_attr( $f['key'] ); ?>" value="" class="regular-text" /></td>
        </tr>
        <?php endforeach; ?>
        </table>
        <?php
    }

    public static function render_profile_fields( WP_User $user ): void
    {
        $fields = [
            [ 'key' => 'vx_empresa_inicial', 'label' => 'Empresa',  'type' => 'text' ],
            [ 'key' => VX_User_Meta::CARGO,   'label' => 'Cargo',    'type' => 'text' ],
            [ 'key' => VX_User_Meta::LINKEDIN,'label' => 'LinkedIn', 'type' => 'url'  ],
            [ 'key' => VX_User_Meta::PAIS,    'label' => 'País',     'type' => 'text' ],
            [ 'key' => VX_User_Meta::ESTADO,  'label' => 'Estado Vitrinexo', 'type' => 'text' ],
        ];
        ?>
        <h2>Datos Vitrinexo</h2>
        <table class="form-table" role="presentation">
        <?php foreach ( $fields as $f ) : $val = esc_attr( get_user_meta( $user->ID, $f['key'], true ) ); ?>
        <tr>
            <th><label for="vx_<?php echo esc_attr( $f['key'] ); ?>"><?php echo esc_html( $f['label'] ); ?></label></th>
            <td><input type="<?php echo esc_attr( $f['type'] ); ?>" name="vx_<?php echo esc_attr( $f['key'] ); ?>" id="vx_<?php echo esc_attr( $f['key'] ); ?>" value="<?php echo $val; ?>" class="regular-text" /></td>
        </tr>
        <?php endforeach; ?>
        </table>
        <?php
    }

    public static function save_profile_fields( int $user_id ): void
    {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        $keys = [
            'vx_empresa_inicial', VX_User_Meta::CARGO, VX_User_Meta::LINKEDIN,
            VX_User_Meta::PAIS, VX_User_Meta::ESTADO,
        ];
        foreach ( $keys as $key ) {
            $posted = sanitize_text_field( wp_unslash( $_POST[ 'vx_' . $key ] ?? '' ) );
            update_user_meta( $user_id, $key, $posted );
        }
    }
}
