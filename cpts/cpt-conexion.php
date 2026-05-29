<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    register_post_type( 'vx_conexion', [
        'labels' => [
            'name'               => 'Conexiones',
            'singular_name'      => 'Conexión',
            'add_new_item'       => 'Nueva conexión',
            'edit_item'          => 'Ver conexión',
            'view_item'          => 'Ver conexión',
            'search_items'       => 'Buscar conexiones',
            'not_found'          => 'No se encontraron conexiones',
            'not_found_in_trash' => 'No hay conexiones en la papelera',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => 'vitrinexo-core',
        'show_in_rest'        => false,
        'supports'            => [ 'title' ],
        'menu_icon'           => 'dashicons-networking',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'rewrite'             => false,
    ] );
} );
