<?php
/**
 * Promotion archive options.
 *
 * @package the7
 * @since 3.0.0
 */

// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

$new_options[] = array( 'name' => _x( 'Promotion archives', 'theme-options', 'the7mk2' ), 'type' => 'block' );

	$new_options['template_page_id_promotion_category'] = array(
		'id'		=> 'template_page_id_promotion_category',
		'name'		=> _x( 'Promotion category template', 'theme-options', 'the7mk2' ),
		'type'		=> 'pages_list',
	);

// add new options
if ( isset( $options ) ) {
	$options = dt_array_push_after( $options, $new_options, 'archive_placeholder' );
}

// cleanup
unset( $new_options );
