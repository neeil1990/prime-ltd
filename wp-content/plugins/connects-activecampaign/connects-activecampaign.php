<?php
/**
* Plugin Name: Connects - Active Campaign Addon
* Plugin URI: 
* Description: Use this plugin to integrate Active Campaign with Connects.
* Version: 1.0.1
* Author: Brainstorm Force
* Author URI: https://www.brainstormforce.com/
* License: http://themeforest.net/licenses
*/


if(!class_exists('Smile_Mailer_Activecampaign')){
	class Smile_Mailer_Activecampaign{

		private $slug;
		private $setting;

		function __construct(){

			require_once('activecampaign/ActiveCampaign.class.php');
			require_once('activecampaign/Auth.class.php');
			add_action( 'wp_ajax_get_activecampaign_data', array($this,'get_activecampaign_data' ));
			add_action( 'wp_ajax_update_activecampaign_authentication', array($this,'update_activecampaign_authentication' ));
			add_action( 'wp_ajax_disconnect_activecampaign', array($this,'disconnect_activecampaign' ));
			add_action( 'wp_ajax_activecampaign_add_subscriber', array($this,'activecampaign_add_subscriber' ));
			add_action( 'wp_ajax_nopriv_activecampaign_add_subscriber', array($this,'activecampaign_add_subscriber' ));
            add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );
			$this->setting  = array(
				'name' => 'Active Campaign',
				'parameters' => array( 'url', 'api_key' ),
				'where_to_find_url' => 'http://www.activecampaign.com/help/using-the-api/',
				'logo_url' => plugins_url('images/logo.png', __FILE__)
			);
			$this->slug = 'activecampaign';
		}

		 /*
         * Function Name: enqueue_scripts
         * Function Description: Add custon scripts
         */
        
        function enqueue_scripts() {
            if( function_exists( 'cp_register_addon' ) ) {
                cp_register_addon( $this->slug, $this->setting );
            }
            wp_register_script( $this->slug.'-script', plugins_url('js/'.$this->slug.'-script.js', __FILE__), array('jquery'), '1.1', true );
            wp_enqueue_script( $this->slug.'-script' );
            add_action( 'admin_head', array( $this, 'hook_css' ) );
        }

        /*
         * Function Name: hook_css
         * Function Description: Adds background style script for mailer logo.
         */


        function hook_css() {
            if( isset( $this->setting['logo_url'] ) ) {
                if( $this->setting['logo_url'] != '' ) {
                    $style = '<style>table.bsf-connect-optins td.column-provider.'.$this->slug.'::after {background-image: url("'.$this->setting['logo_url'].'");}.bend-heading-section.bsf-connect-list-header .bend-head-logo.'.$this->slug.'::before {background-image: url("'.$this->setting['logo_url'].'");}</style>';
                    echo $style;
                }
            }
            
        }


		// retrieve mailer info data
		function get_activecampaign_data() {
			$isKeyChanged = false;
			$connected = false;
			ob_start();
			$ac_api = get_option( $this->slug . '_api' );
			$ac_url = get_option( $this->slug . '_url' );

			if( $ac_api != '' ) {
	            try {
	            	$ac = new CP_ActiveCampaign($ac_url, $ac_api);
					if( !(int)$ac->credentials_test() ) {
						$formstyle = '';
						$isKeyChanged = true;
					} else {
						$formstyle = 'style="display:none;"';
					}
	            } catch( Exception $ex ) {
	            	$formstyle = '';
					$isKeyChanged = true;
	            }

			} else {
            	$formstyle = '';
			}
            ?>

			<div class="bsf-cnlist-form-row" <?php echo $formstyle; ?>>
				<label for="<?php echo $this->slug; ?>-list-name"><?php _e( $this->setting['name'] . " API URL", "smile" ); ?></label>
	            <input type="text" autocomplete="off" id="<?php echo $this->slug; ?>_url" name="<?php echo $this->slug; ?>_url" value="<?php echo esc_attr( $ac_url ); ?>"/>
	        </div>

            <div class="bsf-cnlist-form-row" <?php echo $formstyle; ?>>
	            <label for="<?php echo $this->slug; ?>-list-name"><?php _e( $this->setting['name']." API Key", "smile" ); ?></label>
	            <input type="text" autocomplete="off" id="<?php echo $this->slug; ?>_api_key" name="<?php echo $this->slug; ?>-auth-key" value="<?php echo esc_attr( $ac_api ); ?>"/>
	        </div>

            <div class="bsf-cnlist-form-row <?php echo $this->slug; ?>-list">
	        <?php
	        if( $ac_api != '' && !$isKeyChanged ) {
	            $ac_lists = ($ac_api != '' && !$isKeyChanged) ? $this->get_activecampaign_lists($ac_api,$ac_url) : array();

					if( !empty( $ac_lists ) ) {
						$connected = true;
					?>
					<label for="<?php echo $this->slug;?>-list"><?php echo __( "Select List", "smile" ); ?></label>
						<select id="<?php echo $this->slug; ?>-list" class="bsf-cnlist-select" name="<?php echo $this->slug; ?>-list">
					<?php
						foreach($ac_lists as $id => $name) {
						?>
							<option value="<?php echo $id; ?>"><?php echo $name; ?></option>
						<?php
						}
						?>
						</select>
						<?php
					} else {
					?>
					<label for="<?php echo $this->slug; ?>-list"><?php echo __( "You need at least one list added in " . $this->setting['name'] . " before proceeding.", "smile" ); ?></label>
					<?php
					}
				}
	            ?>
            </div>

            <div class="bsf-cnlist-form-row">

            	<?php if( $ac_api == "" ) { ?>
	            	<button id="auth-<?php echo $this->slug; ?>" class="button button-secondary auth-button" disabled><?php _e( "Authenticate ".$this->setting['name'],"smile" ); ?></button><span class="spinner" style="float: none;"></span>
	            <?php } else {
	            		if( $isKeyChanged ) {
	            ?>
	            	<div id="update-<?php echo $this->slug; ?>" class="update-mailer" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>"><span><?php _e( "Your credentials seems to be changed.</br>Use different '" . $this->setting['name'] . " credentials?", "smile" ); ?></span></div><span class="spinner" style="float: none;"></span>
	            <?php
	            		} else {
	            ?>
	            	<div id="disconnect-<?php echo $this->slug; ?>" class="button button-secondary" data-mailerslug="<?php echo $this->slug; ?>" data-mailer="<?php echo $this->slug; ?>"><span><?php _e( "Use different '" . $this->setting['name'] . "' account?", "smile" ); ?></span></div><span class="spinner" style="float: none;"></span>
	            <?php
	            		}
	            ?>
	            <?php } ?>
	        </div>

            <?php
            $content = ob_get_clean();

            $result['data'] = $content;
            $result['helplink'] = $this->setting['where_to_find_url'];
            $result['isconnected'] = $connected;
            echo json_encode($result);
            exit();

		}

		function activecampaign_add_subscriber(){
			$ret = true;
			$email_status = false;
            $style_id = isset( $_POST['style_id'] ) ? $_POST['style_id'] : '';
            $contact = $_POST['param'];
            $contact['source'] = ( isset( $_POST['source'] ) ) ? $_POST['source'] : '';
            $msg = isset( $_POST['message'] ) ? $_POST['message'] : __( 'Thanks for subscribing. Please check your mail and confirm the subscription.', 'smile' );

            $this->api_key = get_option($this->slug.'_api');
            $campurl = get_option($this->slug.'_url');

            //	Check Email in MX records
			if( isset( $_POST['param']['email'] ) ) {
                $email_status = ( !( isset( $_POST['only_conversion'] ) ? true : false ) ) ? apply_filters('cp_valid_mx_email', $_POST['param']['email'] ) : false;
            }

			if($email_status) {
				if( function_exists( "cp_add_subscriber_contact" ) ){
					$isuserupdated = cp_add_subscriber_contact( $_POST['option'] , $contact );
				}

				if ( !$isuserupdated ) {  // if user is updated dont count as a conversion
					// update conversions
					smile_update_conversions($style_id);
				}
				if( isset( $_POST['param']['email'] ) ) {
					$status = 'success';

					try {
						// Add user to contacts if MX rexord is valid
						$ac = new CP_ActiveCampaign($campurl, $this->api_key);

						$data = array(
							"email"           => $_POST['param']['email'],
							"first_name" => isset( $_POST['param']['first_name'] ) ? $_POST['param']['first_name'] : '',
							"last_name" => isset( $_POST['param']['last_name'] ) ? $_POST['param']['last_name'] : '',
							"p[{$_POST['list_id']}]"      => $_POST['list_id'],
							"status[{$_POST['list_id']}]" => 1, // "Active" status
						);

						foreach( $_POST['param'] as $key => $p ) {
	                        if( $key != 'email' && $key != 'user_id' && $key != 'date' && $key != 'first_name' && $key != 'last_name' ){
	                        	$data['field[%'.$key.'%,0]'] = $p;
	                        }
	                    }
						// sync contacts with mailer
						$contact_sync = $ac->api("contact/sync", $data);
					} catch( Exception $ex ) {
						if( isset( $_POST['source'] ) ) {
			                return false;
			            } else {
			            	print_r(json_encode(array(
								'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
								'email_status' => $email_status,
								'status' => 'error',
								'message' => __( "Something went wrong. Please try again.", "smile"),
								'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
							)));
							exit();
			            }
					}
						

					if( !is_object($contact_sync) || ( is_object($contact_sync) && !(int)$contact_sync->success ) ) {
						if( isset( $_POST['source'] ) ) {
			                return false;
			            } else {
			            	print_r(json_encode(array(
								'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
								'email_status' => $email_status,
								'status' => 'error',
								'message' => __( "Something went wrong. Please try again.", "smile"),
								'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
							)));
							exit();
			            }

					}
				}
			} else {
				if( isset( $_POST['only_conversion'] ) ? true : false ){
					// update conversions
					$status = 'success';
					smile_update_conversions( $style_id );
					$ret = true;
				} else {
					$msg = ( isset( $post['msg_wrong_email']  )  && $post['msg_wrong_email'] !== '' ) ? $post['msg_wrong_email'] : __( 'Please enter correct email address.', 'smile' );
					$status = 'error';
					$ret = false;
				}
			}

			if( isset( $_POST['source'] ) ) {
                return $ret;
            } else {
            	print_r(json_encode(array(
					'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
					'email_status' => $email_status,
					'status' => $status,
					'message' => $msg,
					'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
				)));

				exit();
            }
		}

		function update_activecampaign_authentication(){
			$post = $_POST;
			$data = array();
			$this->api_key = $post['authentication_token'];
			$campurl = $_POST['campaingURL'];


			if( $post['authentication_token'] == "" ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "Please provide valid API Key for your " . $this->setting['name'] . " account.", "smile" )
				)));
				exit();
			}
			if( $post['campaingURL'] == "" ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "Please provide valid Campaign URL for your " . $this->setting['name'] . " account.", "smile" )
				)));
				exit();
			}

			try {
				$ac = new CP_ActiveCampaign($campurl, $this->api_key);

				if (!(int)$ac->credentials_test()) {

					print_r(json_encode(array(
						'status' => "error",
						'message' => __( "Access denied: Invalid credentials (URL and/or API key).", "smile" )
					)));
					exit();
				}

				$param = array(
					"api_action" => "list_list",
					"api_key"    => $this->api_key,
					"ids"   => "all",
					"full" => 0
				);

				$lists = $ac->api("list/list_", $param);
			} catch( Exception $ex ) {
				print_r(json_encode(array(
						'status' => "error",
						'message' => __( "Something went wrong. Please try again.", "smile" )
					)));
					exit();
			}
				

			if( $lists->result_code == 0 ) {
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "You have zero lists in your " . $this->setting['name'] . " account. You must have at least one list before integration." , "smile" )
				)));
				exit();
			}
			ob_start();
			$ac_lists = array();
			$html = $query = '';
			if( !empty( $lists ) ) {
			?>
				<label for="<?php echo $this->slug; ?>-list"><?php echo __( "Select List", "smile" ); ?></label>
				<select id="<?php echo $this->slug; ?>-list" class="bsf-cnlist-select" name="<?php echo $this->slug; ?>-list">';
				<?php
				foreach( $lists as $offset => $list ) {
					if( isset($list->id) ) {
				?>
						<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
				<?php
						$query .= $list->id.'|'.$list->name.',';
						$ac_lists[$list->id] = $list->name;
					}
				}
				?>
				</select>
			<?php
			} else {
			?>
				<label for="<?php echo $this->slug; ?>-list"><?php echo __( "You need at least one list added in " . $this->setting['name'] . " before proceeding.", "smile" ); ?></label>
			<?php
			}
			?>
				
			<input type="hidden" id="mailer-all-lists" value="<?php echo esc_attr($query); ?>"/>
			<input type="hidden" id="mailer-list-action" value="update_<?php echo $this->slug; ?>_list"/>
			<input type="hidden" id="mailer-list-api" value="<?php echo esc_attr( $this->api_key ); ?>"/>
			<div class="bsf-cnlist-form-row">
				<div id="disconnect-<?php echo $this->slug; ?>" class="disconnect-mailer" data-mailerslug="<?php echo $this->slug; ?>" data-mailer="<?php echo $this->slug; ?>">
					<span>
						<?php _e( "Use different '" . $this->setting['name'] . "' account?", "smile" ); ?>
					</span>
				</div>
				<span class="spinner" style="float: none;"></span>
			</div>
			<?php
			$html .= ob_get_clean();

			update_option($this->slug.'_url',$campurl);
			update_option($this->slug.'_api',$this->api_key);
			update_option($this->slug.'_lists',$ac_lists);

			print_r(json_encode(array(
				'status' => "success",
				'message' => $html
			)));

			exit();
		}

		function disconnect_activecampaign(){
			delete_option( $this->slug.'_api' );
			delete_option( $this->slug.'_url' );
			delete_option( $this->slug.'_lists' );

			$smile_lists = get_option('smile_lists');
			if( !empty( $smile_lists ) ){
				foreach( $smile_lists as $key => $list ) {
					$provider = $list['list-provider'];
					if( strtolower( $provider ) == strtolower( $this->slug ) ){
						$smile_lists[$key]['list-provider'] = "Convert Plug";
						$contacts_option = "cp_" . $this->slug . "_" . preg_replace( '#[ _]+#', '_', strtolower( $list['list-name'] ) );
                        $contact_list = get_option( $contacts_option );
                        $deleted = delete_option( $contacts_option );
                        $status = update_option( "cp_connects_" . preg_replace( '#[ _]+#', '_', strtolower( $list['list-name'] ) ), $contact_list );
					}
				}
				update_option( 'smile_lists', $smile_lists );
			}

			print_r(json_encode(array(
                'message' => "disconnected",
			)));
			exit();
		}

		/*
		 * Function Name: get_activecampaign_lists
		 * Function Description: Get ActiveCampaign Mailer Campaign list
		 */

		function get_activecampaign_lists( $api_key = '', $url = '' ) {
			if( $api_key != '' && $url != '' ) {

				try{
					$ac = new CP_ActiveCampaign($url, $api_key);
					$param = array(
						"api_action" => "list_list",
						"api_key"    => $api_key,
						"ids"   => "all",
						"full" => 0
					);

					$lists = $ac->api("list/list_", $param);
				} catch( Exception $ex ) {
					return array();
				}
					

				$ac_lists = array();
				if( !empty( $lists ) ){
					foreach($lists as $offset => $list) {
						if(isset($list->id))
							$ac_lists[$list->id] = $list->name;
					}
					return $ac_lists;
				} else {
					return array();
				}
				
			}
			return array();
		}
	}
	new Smile_Mailer_Activecampaign;
}

$bsf_core_version_file = realpath(dirname(__FILE__).'/admin/bsf-core/version.yml');
if(is_file($bsf_core_version_file)) {
	global $bsf_core_version, $bsf_core_path;
	$bsf_core_dir = realpath(dirname(__FILE__).'/admin/bsf-core/');
	$version = file_get_contents($bsf_core_version_file);
	if(version_compare($version, $bsf_core_version, '>')) {
		$bsf_core_version = $version;
		$bsf_core_path = $bsf_core_dir;
	}
}
add_action('init', 'bsf_core_load', 999);
if(!function_exists('bsf_core_load')) {
	function bsf_core_load() {
		global $bsf_core_version, $bsf_core_path;
		if(is_file(realpath($bsf_core_path.'/index.php'))) {
			include_once realpath($bsf_core_path.'/index.php');
		}
	}
}
?>