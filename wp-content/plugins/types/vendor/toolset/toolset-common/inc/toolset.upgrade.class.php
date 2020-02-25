<?php

/**
 * Toolset Common Library upgrade mechanism.
 * 
 * Compares a number of the library version in database with the current one. If the current version is lower,
 * it does the toolset_do_upgrade action and updates the database.
 * 
 * Note: For performance reasons, this is loaded only during admin page requests. As a consequence, everything running
 * on front-end that relies on certain state of the database must survive a TCL upgrade without an immediate database
 * upgrade.
 * 
 * @since m2m
 */
class Toolset_Upgrade {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() { }

	private function __clone() { }

	
	private $is_initialized = false;
	
	
	public static function initialize() {
		$instance = self::get_instance();
		if( $instance->is_initialized ) {
			return;
		}

		// When everything is loaded and possibly hooked in the upgrade action, we can proceed.
		add_action( 'toolset_common_loaded', array( $instance, 'check_upgrade' ) );
		
		$instance->is_initialized = true;
	}


	/** Name of the option used to store version number. */
	const DATABASE_VERSION_OPTION = 'toolset_database_version';


	/**
	 * Check if an upgrade is needed.
	 *
	 * Do not call this manually, there's no need to.
	 *
	 * @since m2m
	 */
	public function check_upgrade() {

		$database_version = $this->get_database_version();
		$library_version = $this->get_library_version();
		$is_upgrade_needed = ( $database_version < $library_version );

		if( $is_upgrade_needed ) {

			// Safety measure - Abort if the library isn't fully loaded.
			if( false == apply_filters( 'toolset_is_toolset_common_available', false ) ) {
				return;
			}

			$this->do_upgrade( $database_version, $library_version );
		}
	}


	/**
	 * Get current version number.
	 *
	 * @return int
	 * @since m2m
	 */
	private function get_library_version() {
		return ( defined( 'TOOLSET_COMMON_VERSION_NUMBER' ) ? (int) TOOLSET_COMMON_VERSION_NUMBER : 0 );
	}


	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 * @since m2m
	 */
	private function get_database_version() {
		$version = (int) get_option( self::DATABASE_VERSION_OPTION, 0 );
		return $version;
	}


	/**
	 * Update the version number stored in the database.
	 *
	 * @param int $version_number
	 * @since m2m
	 */
	private function update_database_version( $version_number ) {
		if( is_numeric( $version_number ) ) {
			update_option( self::DATABASE_VERSION_OPTION, (int) $version_number, true );
		}
	}


	/**
	 * Perform the actual upgrade.
	 * 
	 * @param int $from_version
	 * @param int $to_version
	 * @since m2m
	 */
	private function do_upgrade( $from_version, $to_version ) {

		/**
		 * This is fired when library version increase is detected.
		 *
		 * It happens only once per each increase and only after the whole library has been loaded.
		 *
		 * @param int $from_version
		 * @param int $to_version
		 * @since m2m
		 */
		do_action( 'toolset_do_upgrade', $from_version, $to_version );

		$this->update_database_version( $to_version );
	}


}