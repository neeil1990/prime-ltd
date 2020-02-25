<?php

/**
 * Holds helper methods related to native Toolset associations.
 *
 * Throughout m2m API, only these classes should directly touch the database:
 *
 * - Toolset_Relationship_Database_Operations
 * - Toolset_Relationship_Migration
 * - Toolset_Relationship_Driver
 * - Toolset_Relationship_Translation_View_Management
 * - Toolset_Association_Query
 *
 * @since m2m
 */
class Toolset_Relationship_Database_Operations {

	/**
	 * Warning: Changing this value in any way may break existing sites.
	 *
	 * @since m2m
	 */
	const MAXIMUM_RELATIONSHIP_SLUG_LENGTH = 255;


	/** @var wpdb */
	private $wpdb;


	public function __construct( wpdb $wpdb_di = null ) {

		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

	}


	/**
	 * Create new association and persist it.
	 *
	 * From outside of the m2m API, use Toolset_Relationship_Definition::create_association().
	 *
	 * @param Toolset_Relationship_Definition|string $relationship_definition_source Can also contain slug of
	 *     existing relationship definition.
	 * @param int|Toolset_Element|WP_Post $parent_source
	 * @param int|Toolset_Element|WP_Post $child_source
	 * @param int $intermediary_id
	 * @param bool $instantiate Whether to create an instance of the newly created association
	 *     or only return a result on success
	 *
	 * @return IToolset_Association|Toolset_Result
	 * @since m2m
	 */
	public static function create_association( $relationship_definition_source, $parent_source, $child_source, $intermediary_id, $instantiate = true ) {

		$relationship_definition = Toolset_Relationship_Utils::get_relationship_definition( $relationship_definition_source );

		if ( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Relationship definition "%s" doesn\'t exist.', 'wpcf' ),
					is_string( $relationship_definition_source ) ? $relationship_definition_source : print_r( $relationship_definition_source, true )
				)
			);
		}

		$driver = $relationship_definition->get_driver();

		$result = $driver->create_association(
			$parent_source,
			$child_source,
			array(
				'intermediary_id' => $intermediary_id,
				'instantiate' => (bool) $instantiate
			)
		);

		return $result;
	}


	private static $role_to_column_map = array(
		Toolset_Relationship_Role::PARENT => 'parent_id',
		Toolset_Relationship_Role::CHILD => 'child_id',
		Toolset_Relationship_Role::INTERMEDIARY => 'intermediary_id',
	);


	/**
	 * For a given role name, return the corresponding column in the associations table.
	 *
	 * @param string $role
	 * @return string
	 * @since m2m
	 */
	public static function role_to_column( $role ) {
		if ( ! array_key_exists( $role, self::$role_to_column_map ) ) {
			throw new InvalidArgumentException();
		}

		return self::$role_to_column_map[ $role ];
	}


	/**
	 * Update the database to support the native m2m implementation.
	 *
	 * Practically that means creating the wp_toolset_associations table.
	 *
	 * @since m2m
	 *
	 * TODO is it possible to reliably detect dbDelta failure?
	 */
	public function do_native_dbdelta() {

		$this->create_associations_table();

		$this->create_relationship_table();

		return true;
	}


	/**
	 * Execute a dbDelta() query, ensuring that the function is available.
	 *
	 * @param string $query MySQL query.
	 *
	 * @return array dbDelta return value.
	 */
	private static function dbdelta( $query ) {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		return dbDelta( $query );

	}


	/**
	 * Determine if a table exists in the database.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 * @since m2m
	 */
	public function table_exists( $table_name ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );

		return ( $wpdb->get_var( $query ) == $table_name );
	}


	private static function get_charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}


	/**
	 * Create the table for storing associations.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @since m2m
	 */
	private function create_associations_table() {

		$association_table_name = Toolset_Relationship_Table_Name::associations();

		if ( $this->table_exists( $association_table_name ) ) {
			return;
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$association_table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    			relationship varchar(" . self::MAXIMUM_RELATIONSHIP_SLUG_LENGTH . ") NOT NULL DEFAULT '',
			    parent_id bigint(20) UNSIGNED NOT NULL,
			    child_id bigint(20) UNSIGNED NOT NULL,
			    intermediary_id bigint(20) UNSIGNED NOT NULL,
			    trid bigint(20) UNSIGNED NOT NULL,
			    lang varchar(7) NOT NULL DEFAULT '',
			    translation_type enum('original','translation','none') NOT NULL DEFAULT 'none',
			    PRIMARY KEY  id (id)
			) " . self::get_charset_collate() . ";";

		// fixme: consider adding keys, but beware max length
		// KEY parent (parent_id, relationship),
		// KEY child (child_id, relationship)
		self::dbdelta( $query );
	}


	/**
	 * Create the table for the relationship definitions.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @since m2m
	 */
	private function create_relationship_table() {

		$table_name = Toolset_Relationship_Table_Name::relationships();

		if ( $this->table_exists( $table_name ) ) {
			return;
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    			slug varchar(" . self::MAXIMUM_RELATIONSHIP_SLUG_LENGTH . ") NOT NULL DEFAULT '',
    			display_name varchar(255) NOT NULL DEFAULT '',
    			driver varchar(50) NOT NULL DEFAULT '',
    			parent_domain varchar(20) NOT NULL DEFAULT '', 
    			parent_types text NOT NULL DEFAULT '',
    			child_domain varchar(20) NOT NULL DEFAULT '',
    			child_types text NOT NULL DEFAULT '',
    			intermediary_type varchar(20) NOT NULL DEFAULT '',
    			ownership enum('parent', 'child', 'none') NOT NULL DEFAULT 'none',
    			cardinality_parent_max int(10) NOT NULL DEFAULT -1,
    			cardinality_parent_min int(10) NOT NULL DEFAULT 0,
    			cardinality_child_max int(10) NOT NULL DEFAULT -1,
    			cardinality_child_min int(10) NOT NULL DEFAULT 0,
    			is_distinct tinyint(1) NOT NULL DEFAULT 0,
    			scope longtext NOT NULL DEFAULT '',
    			origin varchar(50) NOT NULL DEFAULT '',
    			extra longtext NOT NULL DEFAULT '',
			    PRIMARY KEY  id (id),
			    KEY slug (slug)
			) " . self::get_charset_collate() . ";";

		self::dbdelta( $query );

	}


	/**
	 * Get the next unused value for trid (translation ID, grouping different translations of
	 * one association together).
	 *
	 * Assumes that this method will be always called before inserting a new trid, and that
	 * the returned trid is always used.
	 *
	 * @return int
	 */
	public static function get_next_trid() {
		static $next_trid = 0;

		if ( 0 === $next_trid ) {
			global $wpdb;
			$associations_table = Toolset_Relationship_Table_Name::associations();
			$last_trid = $wpdb->get_var( "SELECT MAX(trid) FROM {$associations_table}" );

			// It will be incremented and becomes unique in the next step
			$next_trid = $last_trid;
		}

		$next_trid++;

		return $next_trid;
	}


	/**
	 * When a relationship definition slug is renamed, update the association table (where the slug is used as a foreign key).
	 *
	 * The usage of this method is strictly limited to the m2m API, always change the slug via
	 * Toolset_Relationship_Definition_Repository::change_definition_slug().
	 *
	 * @param string $old_slug
	 * @param string $new_slug
	 *
	 * @return Toolset_Result
	 *
	 * @since m2m
	 */
	public static function update_associations_on_definition_renaming( $old_slug, $new_slug ) {
		global $wpdb;

		$associations_table = Toolset_Relationship_Table_Name::associations();

		$rows_updated = $wpdb->update(
			$associations_table,
			array( 'relationship' => $new_slug ),
			array( 'relationship' => $old_slug ),
			'%s',
			'%s'
		);

		$is_success = ( false !== $rows_updated );

		$message = (
			$is_success
				? sprintf(
					__( 'The association table has been updated with the new relationship slug "%s". %d rows have been updated.', 'wpcf' ),
					$new_slug,
					$rows_updated
				)
				: sprintf(
					__( 'There has been an error when updating the assocation table with the new relationship slug: %s', 'wpcf' ),
					$wpdb->last_error
				)
		);

		return new Toolset_Result( $is_success, $message );
	}


	/**
	 * Delete all associations from a given relationship.
	 *
	 * @param string $relationship_slug
	 *
	 * @return Toolset_Result_Updated
	 */
	public function delete_associations_by_relationship( $relationship_slug ) {

		$associations_table = Toolset_Relationship_Table_Name::associations();

		$result = $this->wpdb->delete(
			$associations_table,
			array( 'relationship' => $relationship_slug ),
			array( '%s' )
		);

		if( false === $result ) {
			return new Toolset_Result_Updated(
				false, 0,
				sprintf( __( 'Database error when deleting associations: "%s"', 'wpcf' ), $this->wpdb->last_error )
			);
		} else {
			return new Toolset_Result_Updated(
				true, $result,
				sprintf( __( 'Deleted all associations for the relationship %s', 'wpcf'), $relationship_slug )
			);
		}
	}


	public function load_all_relationships() {
		$table_name = Toolset_Relationship_Table_Name::relationships();
		$rows = toolset_ensarr( $this->wpdb->get_results( "SELECT * FROM $table_name" ) );
		return $rows;
	}

}