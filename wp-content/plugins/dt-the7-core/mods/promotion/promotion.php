<?php
/**
 * Promotion module.
 */

// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once plugin_dir_path( __FILE__ ) . 'includes/class-mod-promotion.php';

if ( ! function_exists( 'presscore_mod_promotion' ) ) {

	function presscore_mod_promotion() {
		return Presscore_Mod_Promotion::instance();
	}
	presscore_mod_promotion();

}
