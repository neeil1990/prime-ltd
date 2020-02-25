<?
/*
Plugin Name: Keyword generator
Description: Keyword generator
Version: 1.0
Author: Author's name
Author URI: 
Plugin URI: 
*/

define('K_DIR', plugin_dir_path(__FILE__));
define('K_URL', plugin_dir_url(__FILE__));






function index(){
	?>

	<iframe scrolling="no" src="<?=K_URL?>start.php" frameBorder="0" width="100%" height="800px" id="frameDemo"></iframe>
 
	<script>
		jQuery(function($){
		  var lastHeight = 0, curHeight = 0, $frame = $('iframe:eq(0)');
		  setInterval(function(){
			curHeight = $frame.contents().find('#keyword-generator').height() + 300;
			if ( curHeight != lastHeight ) {
			  $frame.css('height', (lastHeight = curHeight) + 'px' );
			}
		  },500);
		});
		
	</script>
	<?
}

add_shortcode( "k-generator" , "index" );