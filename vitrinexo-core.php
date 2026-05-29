<?php
/**
 * Plugin Name: Vitrinexo Core
 * Plugin URI:  https://vitrinexo.com
 * Description: Lógica de negocio de la plataforma Vitrinexo — directorio B2B hispanohablante.
 * Version:     1.0.0
 * Author:      Maggiore Marketing
 * Author URI:  https://maggiore.cl
 * Text Domain: vitrinexo-core
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VX_VERSION',        '1.0.1' );
define( 'VX_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'VX_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'VX_REST_NAMESPACE', 'vitrinexo/v1' );

// Orden de carga: helpers → meta keys → CPTs → modelos → flujos → email → notif → REST → admin
$vx_modules = [
    // Helpers (sin dependencias)
    'helpers/helper-domains.php',
    'helpers/helper-tokens.php',
    'helpers/helper-tags.php',
    'helpers/helper-pagination.php',
    'helpers/helper-slugs.php',

    // Meta keys (sin dependencias)
    'modules/users/class-vx-user-meta.php',
    'modules/membership/class-vx-membership-meta.php',
    'modules/connections/class-vx-connection-meta.php',
    'modules/dinner/class-vx-dinner-meta.php',

    // CPTs
    'cpts/cpt-empresa.php',
    'cpts/cpt-conexion.php',
    'cpts/cpt-dinner.php',
    'cpts/cpt-notification.php',

    // Modelos
    'modules/users/class-vx-user.php',
    'modules/membership/class-vx-membership.php',
    'modules/membership/class-vx-plans.php',
    'modules/connections/class-vx-connection.php',
    'modules/dinner/class-vx-dinner.php',
    'modules/notifications/class-vx-notification.php',

    // Email (antes de flujos porque los flujos la usan)
    'modules/email/class-vx-email-templates.php',
    'modules/email/class-vx-mailer.php',

    // Flujos
    'modules/users/class-vx-verification.php',
    'modules/users/class-vx-auth.php',
    'modules/membership/class-vx-membership-hooks.php',
    'modules/onboarding/class-vx-onboarding.php',
    'modules/directory/class-vx-directory.php',
    'modules/directory/class-vx-search.php',
    'modules/directory/class-vx-matches.php',
    'modules/connections/class-vx-connection-flow.php',
    'modules/communities/class-vx-community.php',
    'modules/communities/class-vx-senior-verification.php',
    'modules/dinner/class-vx-dinner-assignment.php',

    // Cron
    'modules/email/class-vx-cron.php',

    // Notificaciones (depende de flujos)
    'modules/notifications/class-vx-notification-triggers.php',

    // REST API
    'rest/rest-auth.php',
    'rest/rest-account.php',
    'rest/rest-onboarding.php',
    'rest/rest-directory.php',
    'rest/rest-connections.php',
    'rest/rest-favorites.php',
    'rest/rest-notifications.php',
    'rest/rest-dinner.php',
    'rest/rest-communities.php',
    'rest/rest-upload.php',

    // Admin
    'modules/admin/class-vx-admin-users.php',
    'modules/admin/class-vx-admin-connections.php',
    'modules/admin/class-vx-admin-dinner.php',
    'modules/admin/class-vx-admin-membership.php',

    // Shortcodes
    'shortcodes/shortcodes-public.php',
    'shortcodes/shortcodes-auth.php',
    'shortcodes/shortcodes-flow.php',
    'shortcodes/shortcodes-fragments.php',
];

foreach ( $vx_modules as $file ) {
    $path = VX_PLUGIN_DIR . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

add_action( 'init', function () {
    if ( class_exists( 'VX_User_Meta' ) ) {
        VX_User_Meta::register();
    }
    if ( class_exists( 'VX_Auth' ) ) {
        VX_Auth::init();
    }
    if ( class_exists( 'VX_Notification_Triggers' ) ) {
        VX_Notification_Triggers::init();
    }

    // ── Rewrite rule para perfiles dinámicos: /perfil/{slug}/ ─────────────────
    add_rewrite_rule(
        '^perfil/([a-z0-9][a-z0-9\-]*)/?$',
        'index.php?pagename=perfil&vx_perfil_slug=$matches[1]',
        'top'
    );

    // Flush automático una sola vez cuando la regla no está registrada aún
    $rules = get_option( 'rewrite_rules', [] );
    if ( ! isset( $rules['^perfil/([a-z0-9][a-z0-9\-]*)/?$'] ) ) {
        flush_rewrite_rules( false );
    }
} );

// Registrar vx_perfil_slug como query var reconocida por WordPress
add_filter( 'query_vars', function ( array $vars ): array {
    $vars[] = 'vx_perfil_slug';
    return $vars;
} );

add_action( 'admin_menu', function () {
    add_menu_page(
        'Vitrinexo',
        'Vitrinexo',
        'manage_options',
        'vitrinexo-core',
        function () {
            echo '<div class="wrap"><h1>Vitrinexo Core</h1><p>Administra los CPTs del directorio desde los submenús.</p></div>';
        },
        'dashicons-networking',
        30
    );
} );

add_action( 'admin_init', function () {
    // Auto-create pages (runs once per version change or on first install)
    if ( get_option( 'vx_pages_version' ) !== VX_VERSION ) {
        vx_create_pages();
        update_option( 'vx_pages_version', VX_VERSION );
    }

    if ( class_exists( 'VX_Admin_Users' ) ) {
        VX_Admin_Users::init();
    }
    if ( class_exists( 'VX_Admin_Connections' ) ) {
        VX_Admin_Connections::init();
    }
    if ( class_exists( 'VX_Admin_Dinner' ) ) {
        VX_Admin_Dinner::init();
    }
    if ( class_exists( 'VX_Admin_Membership' ) ) {
        VX_Admin_Membership::init();
    }
} );

register_activation_hook( __FILE__, function () {
    if ( class_exists( 'VX_Cron' ) ) {
        VX_Cron::schedule();
    }
    vx_create_pages();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
    if ( class_exists( 'VX_Cron' ) ) {
        VX_Cron::unschedule();
    }
    flush_rewrite_rules();
} );

/**
 * Crea las páginas de WordPress con sus shortcodes al activar el plugin.
 */
function vx_create_pages(): void {
    $pages = [
        'home'                     => [ 'title' => 'Inicio',                     'shortcode' => '[vx_landing]' ],
        'login'                    => [ 'title' => 'Ingresar',                   'shortcode' => '[vx_login]' ],
        'recuperar-contrasena'     => [ 'title' => 'Recuperar contraseña',       'shortcode' => '[vx_recuperar_contrasena]' ],
        'nueva-contrasena'         => [ 'title' => 'Nueva contraseña',           'shortcode' => '[vx_nueva_contrasena]' ],
        'confirmar-correo'         => [ 'title' => 'Confirmar correo',           'shortcode' => '[vx_confirmar_correo]' ],
        'verificacion-pendiente'   => [ 'title' => 'Verificación pendiente',     'shortcode' => '[vx_verificacion_pendiente]' ],
        'onboarding'               => [ 'title' => 'Completa tu perfil',         'shortcode' => '[vx_onboarding]' ],
        'dashboard'                => [ 'title' => 'Dashboard',                  'shortcode' => '[vx_dashboard]' ],
        'directorio'               => [ 'title' => 'Directorio',                 'shortcode' => '[vx_directorio]' ],
        'matches'                  => [ 'title' => 'Mis matches',                'shortcode' => '[vx_matches]' ],
        'perfil'                   => [ 'title' => 'Perfil',                     'shortcode' => '[vx_perfil]' ],
        'editar-perfil'            => [ 'title' => 'Editar perfil',              'shortcode' => '[vx_editor_perfil]' ],
        'favoritos'                => [ 'title' => 'Mis favoritos',              'shortcode' => '[vx_favoritos]' ],
        'conexiones'               => [ 'title' => 'Mis conexiones',             'shortcode' => '[vx_conexiones]' ],
        'conexion-aceptada'        => [ 'title' => 'Conexión aceptada',          'shortcode' => '[vx_conexion_aceptada]' ],
        'conexion-rechazada'       => [ 'title' => 'Conexión rechazada',         'shortcode' => '[vx_conexion_rechazada]' ],
        'notificaciones'           => [ 'title' => 'Notificaciones',             'shortcode' => '[vx_notificaciones]' ],
        'configuracion'            => [ 'title' => 'Configuración',              'shortcode' => '[vx_configuracion]' ],
        'landing-4dinner'           => [ 'title' => '4Dinner — Sobre el evento',   'shortcode' => '[vx_landing_4dinner]' ],
        '4dinner'                  => [ 'title' => '4Dinner',                    'shortcode' => '[vx_4dinner]' ],
        'comunidad-out2b'          => [ 'title' => 'Comunidad Out2B',            'shortcode' => '[vx_comunidad slug="out2b"]' ],
        'comunidad-woman'          => [ 'title' => 'Comunidad Woman',            'shortcode' => '[vx_comunidad slug="woman"]' ],
        'comunidad-senior'         => [ 'title' => 'Comunidad Senior',           'shortcode' => '[vx_comunidad slug="senior"]' ],
    ];

    // Eliminar la página mi-4dinner si existe (fue renombrada a 4dinner)
    $mi4dinner = get_page_by_path( 'mi-4dinner' );
    if ( $mi4dinner ) {
        wp_delete_post( $mi4dinner->ID, true );
    }

    foreach ( $pages as $slug => $data ) {
        $existing = get_page_by_path( $slug );
        if ( ! $existing ) {
            wp_insert_post( [
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => $data['shortcode'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );
        } else {
            // Actualizar el shortcode si cambió (útil cuando se refactoriza la estructura)
            if ( trim( $existing->post_content ) !== $data['shortcode'] ) {
                wp_update_post( [
                    'ID'           => $existing->ID,
                    'post_content' => $data['shortcode'],
                    'post_title'   => $data['title'],
                ] );
            }
        }
    }

    // Set the home page as the static front page
    $home_page = get_page_by_path( 'home' );
    if ( $home_page ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $home_page->ID );
    }
}
