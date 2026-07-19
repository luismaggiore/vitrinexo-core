<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    register_post_type( 'vx_notification', [
        'labels' => [
            'name'               => 'Notificaciones',
            'singular_name'      => 'Notificación',
            'edit_item'          => 'Ver notificación',
            'view_item'          => 'Ver notificación',
            'search_items'       => 'Buscar notificaciones',
            'not_found'          => 'No se encontraron notificaciones',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => 'vitrinexo-core',
        'show_in_rest'        => false,
        'supports'            => [ 'title' ],
        'menu_icon'           => 'dashicons-bell',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'rewrite'             => false,
    ] );

    // Mostrar todas las notificaciones sin paginación en el admin
    add_filter( 'edit_vx_notification_per_page', fn() => -1 );
} );
