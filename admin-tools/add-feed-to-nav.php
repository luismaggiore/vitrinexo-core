<?php
/**
 * Script de un solo uso: añade la página "publicaciones" a los menús primary y footer.
 * Acceder a: /wp-admin/admin.php?page=vitrinexo-core&vx_tool=add_feed_nav
 * Se puede borrar después de ejecutar.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', function () {
    if ( empty( $_GET['vx_tool'] ) || $_GET['vx_tool'] !== 'add_feed_nav' ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permiso' );

    $page = get_page_by_path( 'publicaciones' );
    if ( ! $page ) { wp_die( 'No se encontró la página con slug "publicaciones".' ); }

    $menus = wp_get_nav_menus();
    if ( empty( $menus ) ) { wp_die( 'No hay menús registrados.' ); }

    $added = [];
    foreach ( $menus as $menu ) {
        // Buscar si ya existe un item que apunte a esta página
        $items = wp_get_nav_menu_items( $menu->term_id );
        $exists = false;
        foreach ( (array) $items as $item ) {
            if ( (int) $item->object_id === $page->ID && $item->object === 'page' ) {
                $exists = true;
                break;
            }
        }
        if ( $exists ) {
            $added[] = $menu->name . ' (ya existía)';
            continue;
        }

        wp_update_nav_menu_item( $menu->term_id, 0, [
            'menu-item-title'     => 'Feed',
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page->ID,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ] );
        $added[] = $menu->name . ' ✓';
    }

    wp_die( '<h1 style="font-family:sans-serif">Feed añadido a los menús</h1><ul style="font-family:sans-serif">' . implode( '', array_map( fn($s) => '<li>' . esc_html($s) . '</li>', $added ) ) . '</ul><p><a href="/wp-admin/">Volver al admin</a></p>' );
} );
