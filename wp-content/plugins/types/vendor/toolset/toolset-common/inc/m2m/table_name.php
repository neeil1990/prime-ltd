<?php

/**
 * Enum class. Holds names of m2m tables and provides methods that return full table names
 * with correct $wpdb prefixes.
 *
 * NOT to be used outside the m2m API under any circumstances.
 *
 * @since m2m
 */
abstract class Toolset_Relationship_Table_Name {


	/**
	 * Get the associations table name.
	 *
	 * @param string $table_name
	 * @return string
	 */
	public static function get_full_table_name( $table_name ) {
		global $wpdb;
		return $wpdb->prefix . $table_name;
	}


	public static function associations() {
		return self::get_full_table_name( 'toolset_associations' );
	}


	// fixme check all usages and update to the new table structure
	public static function association_translations() {
		throw new RuntimeException( 'The translations table was removed.');
	}


	public static function relationships() {
		return self::get_full_table_name( 'toolset_relationships' );
	}

}