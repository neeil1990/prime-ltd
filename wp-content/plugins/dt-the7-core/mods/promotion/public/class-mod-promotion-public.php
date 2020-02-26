<?php
/**
 * Promotion public part.
 */

// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Presscore_Mod_Promotion_Public {

	public function resolve_template_ajax( $response, $data, $template_name ) {
		if ( in_array( $template_name, array( 'template-promotion-masonry.php', 'template-promotion-list.php', 'template-promotion-jgrid.php' ) ) ) {

			$ajax_content = new Presscore_Mod_Promotion_Ajax_Content_Builder();
			$response = $ajax_content->get_response( $data );

		}
		return $response;
	}

	public function register_shortcodes() {
		foreach ( array( 'promotion', 'promotion-jgrid', 'promotion-slider' ) as $shortcode_name ) {
			include_once plugin_dir_path( __FILE__ ) . "shortcodes/{$shortcode_name}/{$shortcode_name}.php";
		}
	}

	public function load_shortcodes_vc_bridge() {
		include_once plugin_dir_path( __FILE__ ) . "shortcodes/mod-promotion-shortcodes-bridge.php";
	}

	public function init_widgets() {
		register_widget( 'Presscore_Inc_Widgets_Promotion' );
	}

	public function init_template_config( $post_type, $template = null ) {
		if ( 'dt_promotion' == $post_type ) {
			presscore_congif_populate_single_promotion_vars();
		} else if ( 'page' == $post_type && 'promotion' == $template ) {
			presscore_congif_populate_promotion_vars();
		}
	}

	public function archive_post_content( $html ) {
		if ( ! $html ) {
			ob_start();

			presscore_populate_promotion_config();
			presscore_get_template_part( 'mod_promotion', 'masonry/project' );

			$html = ob_get_contents();
			ob_end_clean();
		}
		return $html;
	}

	public function cache_attachments( $attachments_id, $post_type, $posts_query ) {
		if ( 'dt_promotion' === $post_type ) {
			foreach( $posts_query->posts as $_post ) {
				$post_media = get_post_meta( $_post->ID, '_dt_project_media_items', true );
				$preview_style = get_post_meta( $_post->ID, '_dt_project_options_preview_style', true );

				if ( $post_media && is_array( $post_media ) && 'slideshow' == $preview_style ) {
					$attachments_id = array_merge( $attachments_id, $post_media );
				}
			}
		}
		return $attachments_id;
	}

	public function archive_page_id( $page_id ) {
		if ( is_tax( 'dt_promotion_category' ) ) {
			$page_id = of_get_option( 'template_page_id_promotion_category', null );
		}

		return $page_id;
	}

	public function post_meta_wrap_class_filter( $class ) {
		if ( 'dt_promotion' == get_post_type() ) {
			$class[] = 'promotion-categories';
		}
		return $class;
	}

	public function filter_page_title( $page_title ) {
		if ( is_tax( 'dt_promotion_category' ) ) {
			$page_title = sprintf( __( 'Promotion Archives: %s', 'the7mk2' ), '<span>' . single_term_title( '', false ) . '</span>' );
		}
		return $page_title;
	}

	public function filter_body_class( $classes ) {
		// fix single promotion class
		if ( is_single() && 'dt_promotion' === get_post_type() ) {
			$key = array_search( 'single-dt_promotion', $classes );
			if ( false !== $key ) {
				$classes[ $key ] = 'single-promotion';
			}
		}

		// hover icons style
		switch ( presscore_config()->get( 'post.preview.hover.icon.style' ) ) {
			case 'outline':
				$classes[] = 'outlined-promotion-icons';
				break;
			case 'transparent':
				$classes[] = 'semitransparent-promotion-icons';
				break;
			case 'accent':
				$classes[] = 'accent-promotion-icons';
				break;
			case 'small':
				$classes[] = 'small-promotion-icons';
				break;
		}

		return $classes;
	}

	public function filter_masonry_wrap_taxonomy( $taxonomy, $post_type ) {
		if ( 'dt_promotion' == $post_type ) {
			$taxonomy = 'dt_promotion_category';
		}
		return $taxonomy;
	}

	public function filter_add_to_author_archive( $new_post_types ) {
		$new_post_types[] = 'dt_promotion';
		return $new_post_types;
	}
}
