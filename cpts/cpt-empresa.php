<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    register_post_type( 'vx_empresa', [
        'labels' => [
            'name'               => 'Empresas',
            'singular_name'      => 'Empresa',
            'add_new'            => 'Agregar empresa',
            'add_new_item'       => 'Agregar nueva empresa',
            'edit_item'          => 'Editar empresa',
            'view_item'          => 'Ver empresa',
            'search_items'       => 'Buscar empresas',
            'not_found'          => 'No se encontraron empresas',
            'not_found_in_trash' => 'No hay empresas en la papelera',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => 'vitrinexo-core',
        'show_in_rest'        => false,
        'supports'            => [ 'title', 'author' ],
        'menu_icon'           => 'dashicons-building',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'rewrite'             => false,
    ] );
} );
