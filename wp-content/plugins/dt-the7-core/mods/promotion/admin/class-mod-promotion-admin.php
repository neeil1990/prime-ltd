<?php
/**
 * Promotion admin part.
 */

// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Presscore_Mod_Promotion_Admin {

	public function register_post_types() {
		$post_type = 'dt_promotion';
		$args = array(
			'labels'                => array(
				'name'                  => _x( 'Продвижение направлений',              'backend promotion', 'the7mk2' ),
				'singular_name'         => _x( 'Продвижение направлений',              'backend promotion', 'the7mk2' ),
				'add_new'               => _x( 'Add New',                'backend promotion', 'the7mk2' ),
				'add_new_item'          => _x( 'Add New Item',           'backend promotion', 'the7mk2' ),
				'edit_item'             => _x( 'Edit Item',              'backend promotion', 'the7mk2' ),
				'new_item'              => _x( 'New Item',               'backend promotion', 'the7mk2' ),
				'view_item'             => _x( 'View Item',              'backend promotion', 'the7mk2' ),
				'search_items'          => _x( 'Search Items',           'backend promotion', 'the7mk2' ),
				'not_found'             => _x( 'No items found',         'backend promotion', 'the7mk2' ),
				'not_found_in_trash'    => _x( 'No items found in Trash','backend promotion', 'the7mk2' ),
				'parent_item_colon'     => '',
				'menu_name'             => _x( 'Продвижение направлений', 'backend promotion', 'the7mk2' )
			),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true, 
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'promotion' ),
			'capability_type'       => 'post',
			'has_archive'           => true, 
			'hierarchical'          => false,
			'menu_position'         => 35,
			'supports'              => array( 'author', 'title', 'editor', 'thumbnail', 'comments', 'excerpt', 'revisions' )
		);

		$args = apply_filters( "presscore_post_type_{$post_type}_args", $args );

		register_post_type( $post_type, $args );
	}

	public function register_taxonomies() {
		$post_type = 'dt_promotion';
		$taxonomy = 'dt_promotion_category';
		$args = array(
			'labels'                => array(
				'name'              => _x( 'Promotion Categories', 'backend promotion', 'the7mk2' ),
				'singular_name'     => _x( 'Promotion Category', 'backend promotion', 'the7mk2' ),
				'search_items'      => _x( 'Search in Category', 'backend promotion', 'the7mk2' ),
				'all_items'         => _x( 'Promotion Categories', 'backend promotion', 'the7mk2' ),
				'parent_item'       => _x( 'Parent Promotion Category', 'backend promotion', 'the7mk2' ),
				'parent_item_colon' => _x( 'Parent Promotion Category:', 'backend promotion', 'the7mk2' ),
				'edit_item'         => _x( 'Edit Category', 'backend promotion', 'the7mk2' ), 
				'update_item'       => _x( 'Update Category', 'backend promotion', 'the7mk2' ),
				'add_new_item'      => _x( 'Add New Promotion Category', 'backend promotion', 'the7mk2' ),
				'new_item_name'     => _x( 'New Category Name', 'backend promotion', 'the7mk2' ),
				'menu_name'         => _x( 'Promotion Categories', 'backend promotion', 'the7mk2' )
			),
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'rewrite'               => array( 'slug' => 'project-category' ),
			'show_admin_column'		=> true,
		);

		$args = apply_filters( "presscore_taxonomy_{$taxonomy}_args", $args );

		register_taxonomy( $taxonomy, array( $post_type ), $args );
	}

	public function add_meta_boxes( $metaboxes ) {
		$metaboxes[] = plugin_dir_path( __FILE__ ) . 'metaboxes/metaboxes-promotion.php';
		return $metaboxes;
	}

	public function add_basic_meta_boxes_support( $pages ) {
		$pages[] = 'dt_promotion';
		return $pages;
	}

	public function add_options( $options ) {
		if ( array_key_exists( 'of-blog-and-promotion-menu', $options ) ) {
			$options['of-promotion-mod-injected-options'] = plugin_dir_path( __FILE__ ) . 'options/options-promotion.php';
		} else if ( function_exists( 'presscore_module_archive_get_menu_slug' ) && array_key_exists( presscore_module_archive_get_menu_slug(), $options ) ) {
			$options['of-promotion-mod-injected-archive-options'] = plugin_dir_path( __FILE__ ) . 'options/options-archive-promotion.php';
		} else if ( array_key_exists( 'options-framework', $options ) ) {
			$options['of-promotion-mod-injected-slug-options'] = plugin_dir_path( __FILE__ ) . 'options/options-slug-promotion.php';
		}
		return $options;
	}

	public function js_composer_default_editor_post_types_filter( $post_types ) {
		$post_types[] = 'dt_promotion';
		return $post_types;
	}
}
