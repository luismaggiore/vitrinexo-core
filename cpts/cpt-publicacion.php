<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    register_post_type( 'vx_publicacion', [
        'labels' => [
            'name'               => 'Publicaciones Feed',
            'singular_name'      => 'Publicación Feed',
            'add_new_item'       => 'Nueva publicación',
            'edit_item'          => 'Editar publicación',
            'view_item'          => 'Ver publicación',
            'search_items'       => 'Buscar publicaciones',
            'not_found'          => 'No se encontraron publicaciones.',
            'not_found_in_trash' => 'No hay publicaciones en la papelera.',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => 'vitrinexo-core',
        'show_in_rest'        => false,
        'supports'            => [ 'editor', 'author', 'comments' ],
        'menu_icon'           => 'dashicons-format-status',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'rewrite'             => false,
        'comment_status'      => 'open',
    ] );
} );
