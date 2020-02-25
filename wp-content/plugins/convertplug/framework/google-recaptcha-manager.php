<?php
/**
 * Prohibit direct script loading.
 *
 * @package Convert_Plus.
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
?>
<div class="wrap about-wrap about-cp bend">
	<?php
	$cp_recaptcha_site_key   = get_option( 'cp_recaptcha_site_key' );
	$cp_recaptcha_secret_key = get_option( 'cp_recaptcha_secret_key' );

	?>
	<div class="wrap-container">
		<div class="bend-heading-section cp-about-header">
			<h1>
				<?php
				/* translators:%s Plugin name*/
				echo sprintf( __( '%s &mdash; Google Recaptcha', 'smile' ), CP_PLUS_NAME );
				?>
			</h1>
			<h3>
				<?php
				/* translators:%s Plugin name*/
				echo sprintf( __( 'Google reCAPTCHA is a free service that protects your site from spam and abuse. It uses advanced risk analysis techniques to tell humans and bots apart. In Convert Pro Google reCAPTCHA v2 is used. Get a Google reCAPTCHA keys from  <a class="cp-google-recaptcha-page-link" href="https://www.google.com/recaptcha/intro/v3.html" target="_blank" rel="noopener">here</a> and paste it below', 'smile' ), CP_PLUS_NAME );
				?>
			</h3>
			<div class="bend-head-logo">
				<div class="bend-product-ver">
					<?php
					_e( 'Version', 'smile' );
					echo ' ' . CP_VERSION;
					?>
				</div>
			</div>
		</div><!-- bend-heading section -->
		<form id="convert_plug_settings" class="cp-options-list"> 
			<input type="hidden" name="action" value="cp_google_recaptcha" />
		<div class="debug-section">
		<table class="cp-postbox-table form-table">
				<tbody>
				<tr>
					<th scope="row">
					<label for="hide-options" style="width:340px; display: inline-block;"><strong><?php _e( 'Google Recaptcha Site Key', 'smile' ); ?></strong>
									<span class="cp-tooltip-icon has-tip" data-position="top" style="cursor: help;" title="<?php _e( 'Google Recaptcha Site Key.', 'smile' ); ?>">
										<i class="dashicons dashicons-editor-help"></i>
									</span>
						</label>
					</th>
					<td>
						<input type="text" id="cp_recaptcha_site_key" name="cp_recaptcha_site_key" value="<?php echo esc_attr( $cp_recaptcha_site_key ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
					<label for="hide-options" style="width:340px; display: inline-block;"><strong><?php _e( 'Google Recaptcha Secret Key', 'smile' ); ?></strong>
									<span class="cp-tooltip-icon has-tip" data-position="top" style="cursor: help;" title="<?php _e( 'Google Recaptcha Secret Key.', 'smile' ); ?>">
										<i class="dashicons dashicons-editor-help"></i>
									</span>
						</label>
					</th>
					<td>
						<input type="text" id="cp_recaptcha_secret_key" name="cp_recaptcha_secret_key" value="<?php echo esc_attr( $cp_recaptcha_secret_key ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<button type="button" class="button button-primary button-update-settings"><?php _e( 'Save Settings', 'smile' ); ?></button>
	</div>
</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			var btn = jQuery(".button-update-settings");
			var form = jQuery("#convert_plug_settings");
			btn.click(function() {

				var ser  = jQuery("[name]").serialize();
				var data = ser;
				jQuery.ajax({
					url: ajaxurl,
					data: data,
					dataType: 'JSON',
					type: 'POST',
					success: function(result){
						if(result.message == "Settings Updated!"){
							swal("<?php _e( 'Updated!', 'smile' ); ?>", result.message, "success");
							setTimeout(function(){
								window.location = window.location;
							},500);
						} else {
							swal("<?php _e( 'Error!', 'smile' ); ?>", result.message, "error");
						}
					}
				});
			});
		});
	</script>

