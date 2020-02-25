<?php

/**
 * Various and constants for the Toolset relationships functionality.
 *
 * Note: Code related to native associations and database should go to Toolset_Relationship_Database_Operations.
 *
 * @since m2m
 */
class Toolset_Relationship_Utils {

	/**
	 * @param string|Toolset_Relationship_Definition $relationship_definition_source
	 *
	 * @return null|Toolset_Relationship_Definition
	 */
	public static function get_relationship_definition( $relationship_definition_source ) {
		if( is_string( $relationship_definition_source ) ) {
			$rd_factory = Toolset_Relationship_Definition_Repository::get_instance();
			$relationship_definition = $rd_factory->get_definition( $relationship_definition_source );
		} else {
			$relationship_definition = $relationship_definition_source;
		}

		return $relationship_definition;
	}

}