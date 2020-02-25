<?php

/**
 * Factory class for relationship definitions.
 *
 * Use as a singleton in production code.
 *
 * All relationship definitions are stored in a form of definition arrays in a single option.
 * When this class is instantiated, they will be all loaded at once.
 *
 * After making changes to relationship definitions, those must be persisted by calling save_definitions().
 *
 * TODO Lot of things here can be optimized now that we store definitions in their own table.
 *
 * @since m2m
 */
class Toolset_Relationship_Definition_Repository {

	private static $instance = null;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->load_definitions();
		}
		return self::$instance;
	}


	/** @var Toolset_Association_Repository|null */
	private $_association_repository;

	/** @var Toolset_Relationship_Database_Operations|null */
	private $_database_operations;

	/** @var Toolset_Relationship_Definition_Factory|null */
	private $_definition_factory;


	public function __construct(
		Toolset_Association_Repository $association_repository_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Definition_Factory $definition_factory_di = null
	) {
		$this->_association_repository = $association_repository_di;
		$this->_database_operations = $database_operations_di;
		$this->_definition_factory = $definition_factory_di;
	}


	/** @var Toolset_Relationship_Definition[] Managed relationship instances. */
	private $definitions;


	/**
	 * Load relationship definitions.
	 *
	 * Never use from outside the class, except testing.
	 *
	 * @since m2m
	 */
	public function load_definitions() {

		$rows = $this->get_database_operations()->load_all_relationships();

		$this->definitions = array();
		foreach( $rows as $row ) {

			$definition_array = $this->database_row_to_definition_array( $row );
			$definition = $this->load_definition_from_array( $definition_array );

			if( null != $definition ) {
				$this->insert_definition( $definition );
			}
		}
	}


	/**
	 * Convert a row from the relationships table into a relationship definition array.
	 *
	 * @param $row
	 * @return array
	 * @since m2m
	 */
	private function database_row_to_definition_array( $row ) {

		$extra_data = toolset_ensarr( maybe_unserialize( $row->extra ) );

		$definition_array = array(
			Toolset_Relationship_Definition::DA_SLUG => $row->slug,
			Toolset_Relationship_Definition::DA_DISPLAY_NAME_PLURAL => $row->display_name,
			Toolset_Relationship_Definition::DA_DISPLAY_NAME_SINGULAR => toolset_getarr( $extra_data, 'display_name_singular', $row->slug ),
			Toolset_Relationship_Definition::DA_DRIVER => $row->driver,
			Toolset_Relationship_Definition::DA_PARENT_TYPE => array(
				Toolset_Relationship_Element_Type::DA_DOMAIN => $row->parent_domain,
				Toolset_Relationship_Element_Type::DA_TYPES => maybe_unserialize( $row->parent_types )
			),
			Toolset_Relationship_Definition::DA_CHILD_TYPE => array(
				Toolset_Relationship_Element_Type::DA_DOMAIN => $row->child_domain,
				Toolset_Relationship_Element_Type::DA_TYPES => maybe_unserialize( $row->child_types )
			),
			Toolset_Relationship_Definition::DA_CARDINALITY => array(
				Toolset_Relationship_Role::PARENT => array(
					Toolset_Relationship_Cardinality::MAX => (int) $row->cardinality_parent_max,
					Toolset_Relationship_Cardinality::MIN => (int) $row->cardinality_parent_min
				),
				Toolset_Relationship_Role::CHILD => array(
					Toolset_Relationship_Cardinality::MAX => (int) $row->cardinality_child_max,
					Toolset_Relationship_Cardinality::MIN => (int) $row->cardinality_child_min
				)
			),
			Toolset_Relationship_Definition::DA_DRIVER_SETUP => array(
				Toolset_Relationship_Driver::DA_INTERMEDIARY_POST_TYPE => $row->intermediary_type
			),
			Toolset_Relationship_Definition::DA_OWNERSHIP => $row->ownership,
			Toolset_Relationship_Definition::DA_IS_DISTINCT => (bool) $row->is_distinct,
			Toolset_Relationship_Definition::DA_SCOPE => maybe_unserialize( $row->scope ),
			Toolset_Relationship_Definition::DA_ORIGIN => maybe_unserialize( $row->origin ),
			Toolset_Relationship_Definition::DA_ROLE_NAMES => toolset_ensarr( toolset_getarr( $extra_data, 'role_names' ) ),
			Toolset_Relationship_Definition::DA_NEEDS_LEGACY_SUPPORT => (bool) toolset_getarr( $extra_data, 'needs_legacy_support', false ),
			Toolset_Relationship_Definition::DA_IS_ACTIVE => (bool) toolset_getarr( $extra_data, 'is_active', true )
		);

		return $definition_array;
	}


	/**
	 * Load a single relationship definition from a definition array.
	 *
	 * @param array $definition_array
	 * @return null|Toolset_Relationship_Definition The relationship definition or null if it was not
	 *     possible to load it (which means that the definition array was invalid).
	 * @since m2m
	 */
	private function load_definition_from_array( $definition_array ) {

		try {
			return $this->get_definition_factory()->create( $definition_array );
		} catch( Exception $e ) {
			// todo log the error somehow
			return null;
		}

	}


	/**
	 * Insert a definition into the array of managed ones.
	 * 
	 * @param $definition Toolset_Relationship_Definition
	 * @since m2m
	 */
	private function insert_definition( $definition ) {
		// We can rely on this, the slug never changes.
		$this->definitions[ $definition->get_slug() ] = $definition;
	}


	/**
	 * Remove a definition from the array of managed ones.
	 *
	 * If it isn't there already, it does nothing.
	 *
	 * @param $definition IToolset_Relationship_Definition|string Definition itself or its slug.
	 *
	 * @param bool $do_cleanup true to delete related associations,
	 *     intermediary post type and the intermediary post field group, if they exist.
	 *
	 * @return Toolset_Result_Set
	 * @since m2m
	 */
	public function remove_definition( $definition, $do_cleanup = true ) {
		/**@var Toolset_Result */
		$toolset_results = array();

		if( ! $definition instanceof Toolset_Relationship_Definition ) {
			if( ! is_string( $definition ) || ! $this->definition_exists( $definition ) ) {
				throw new InvalidArgumentException( 'Relationship definition doesn\'t exist.' );
			}

			$definition = $this->get_definition( $definition );
		}

		$slug = $definition->get_slug();

		do_action( 'toolset_before_delete_relationship', $slug );

		// fixme: abstract this away
		if( $do_cleanup ) {
			// delete associations of relationship
			$toolset_results[] = $this->get_association_repository()->remove_by_relationship( $definition );

			$intermediary_post_type = $definition->get_intermediary_post_type();
			if( null !== $intermediary_post_type ) {
				$post_type_repository = Toolset_Post_Type_Repository::get_instance();
				$intermediary_post_type = $post_type_repository->get( $intermediary_post_type );
				if( $intermediary_post_type instanceof IToolset_Post_Type_From_Types ) {
					$group_factory = Toolset_Field_Group_Post_Factory::get_instance();
					$groups = $group_factory->get_groups_by_post_type( $intermediary_post_type->get_slug() );
					foreach( $groups as $group ) {
						wp_delete_post( $group->get_id() );
					}

					$post_type_repository->delete( $intermediary_post_type );
				}
			}
		}

		unset( $this->definitions[ $slug ] );
		$toolset_results[] = new Toolset_Result( true, sprintf( __( 'Relationship "%s" has been deleted.', 'wpcf' ), $slug ) );

		// No "after_delete_relationship" action as long as we have to save_relationships() manually. This can change in the future.

		return new Toolset_Result_Set( $toolset_results );
	}


	/**
	 * Get all relationship definitions.
	 * 
	 * @return IToolset_Relationship_Definition[]
	 */
	public function get_definitions() {
		return $this->definitions;
	}


	/**
	 * Determine if a relationship definition with a given slug exists.
	 * 
	 * @param string $slug
	 * @return bool
	 * @since m2m
	 */
	public function definition_exists( $slug ) {
		return array_key_exists( $slug, $this->definitions );
	}


	/**
	 * Get a relationship definition with given slug.
	 * 
	 * @param string $slug
	 * @return null|IToolset_Relationship_Definition
	 * @since m2m 
	 */
	public function get_definition( $slug ) {
		return ( $this->definition_exists( $slug ) ? $this->definitions[ $slug ] : null );
	}


	/**
	 * Create a new definition and start managing it.
	 *
	 * Note that it doesn't save anything to database automatically.
	 *
	 * @param string $slug Valid (sanitized) relationship slug.
	 * @param Toolset_Relationship_Element_Type $parent Parent entity type.
	 * @param Toolset_Relationship_Element_Type $child Child entity type.
	 *
	 * @param bool $allow_slug_adjustment
	 *
	 * @return IToolset_Relationship_Definition
	 * @since m2m
	 */
	public function create_definition( $slug, $parent, $child, $allow_slug_adjustment = true ) {
		if( $slug != sanitize_title( $slug ) ) {
			throw new InvalidArgumentException( 'Poorly sanitized relationship definition slug.' );
		}
		if( ! $parent instanceof Toolset_Relationship_Element_Type ) {
			throw new InvalidArgumentException( 'Invalid parent entity type.' );
		}
		if( ! $child instanceof Toolset_Relationship_Element_Type ) {
			throw new InvalidArgumentException( 'Invalid child entity type.' );
		}
		if( $this->definition_exists( $slug ) ) {
			// If we're allowed to adjust the slug, we'll generate an unique one.
			if( $allow_slug_adjustment ) {
				$naming_helper = Toolset_Naming_Helper::get_instance();
				$slug = $naming_helper->generate_unique_slug( $slug, null, Toolset_Naming_Helper::DOMAIN_RELATIONSHIPS );
			} else {
				throw new InvalidArgumentException( 'Definition slug already taken.' );
			}
		}

		$definition_array = array(
			Toolset_Relationship_Definition::DA_SLUG => $slug,
			Toolset_Relationship_Definition::DA_DRIVER => Toolset_Relationship_Definition::DRIVER_NATIVE,
			Toolset_Relationship_Definition::DA_PARENT_TYPE => $parent->get_definition_array(),
			Toolset_Relationship_Definition::DA_CHILD_TYPE => $child->get_definition_array(),
			Toolset_Relationship_Definition::DA_IS_ACTIVE => true
		);
		
		$new_definition = new Toolset_Relationship_Definition( $definition_array );
		
		$this->insert_definition( $new_definition );

		Toolset_Relationship_Multilingual_Mode::flush_cache();
		
		return $new_definition;
	}

	/**
	 * Creates a definition for the Post Reference Field
	 *
	 * @param $field_slug
	 * @param $field_group_slug
	 * @param $post_reference_type
	 * @param $parent
	 * @param $child
	 *
	 * @return IToolset_Relationship_Definition
	 * @since m2m
	 */
	public function create_definition_post_reference_field( $field_slug, $field_group_slug, $post_reference_type, $parent, $child ) {
		return $this->create_definition(
			$field_slug . '_' . $field_group_slug . '__' . $post_reference_type,
			$parent,
			$child,
			false
		);
	}


	/**
	 * Persist all relationship definitions in the database.
	 * 
	 * @since m2m
	 */
	public function save_definitions() {

		global $wpdb;

		$table_name = Toolset_Relationship_Table_Name::relationships();
		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' );

		$wpdb->query( "TRUNCATE $table_name" );

		foreach( $this->definitions as $definition ) {

			$row = $this->definition_to_database_row( $definition );

			$wpdb->insert( $table_name, $row, $format );
		}
	}


	/**
	 * Convert a relationship definition into a database row
	 *
	 * @param Toolset_Relationship_Definition $definition
	 * @return array
	 * @since m2m
	 */
	private function definition_to_database_row( $definition ) {

		$defintion_array = $definition->get_definition_array();

		$row = array(
			'slug' => $definition->get_slug(),
			'display_name' => $definition->get_display_name(),
			'driver' => $defintion_array[ Toolset_Relationship_Definition::DA_DRIVER ],
			'parent_domain' => $definition->get_parent_type()->get_domain(),
			'parent_types' => maybe_serialize( $definition->get_parent_type()->get_types() ),
			'child_domain' => $definition->get_child_type()->get_domain(),
			'child_types' => maybe_serialize( $definition->get_child_type()->get_types() ),
			'intermediary_type' => $definition->get_driver()->get_setup( Toolset_Relationship_Driver::DA_INTERMEDIARY_POST_TYPE, '' ),
			'ownership' => ( null == $definition->get_owner() ? 'none' : $definition->get_owner() ),
			'cardinality_parent_max' => $definition->get_cardinality()->get_parent( Toolset_Relationship_Cardinality::MAX ),
			'cardinality_parent_min' => $definition->get_cardinality()->get_parent( Toolset_Relationship_Cardinality::MIN ),
			'cardinality_child_max' => $definition->get_cardinality()->get_child( Toolset_Relationship_Cardinality::MAX ),
			'cardinality_child_min' => $definition->get_cardinality()->get_child( Toolset_Relationship_Cardinality::MIN ),
			'is_distinct' => ( $definition->is_distinct() ? 1 : 0 ),
			'scope' => maybe_serialize( $definition->has_scope() ? $definition->get_scope()->get_scope_data() : '' ),
			'origin' => $definition->get_origin()->get_origin_keyword(),
			'extra' => maybe_serialize(
				array(
					'role_names' => $definition->get_role_names(),
					'needs_legacy_support' => ( $definition->needs_legacy_support() ? 1 : 0 ),
					'is_active' => ( $definition->is_active() ? 1 : 0 ),
                    'display_name_singular' => $definition->get_display_name_singular()
				)
			)
		);

		return $row;
	}


	/**
	 * Look for a relationship between posts that was migrated from the legacy post relationships.
	 *
	 * @param $parent_post_type
	 * @param $child_post_type
	 *
	 * @return IToolset_Relationship_Definition|null Relationship definition or null if none exists.
	 * @since m2m
	 *
	 * todo This can be optimized greatly by extending Toolset_Relationship_Query
	 */
	public function get_legacy_definition( $parent_post_type, $child_post_type ) {

		$query = new Toolset_Relationship_Query(
			array(
				Toolset_Relationship_Query::QUERY_IS_LEGACY => true,
				Toolset_Relationship_Query::QUERY_HAS_TYPE => array(
					'domain' => Toolset_Field_Utils::DOMAIN_POSTS,
					'type' => $parent_post_type
				)
			)
		);

		$result_candidates = $query->get_results();

		// Find the specific match. There should be only one.
		foreach( $result_candidates as $relationship_definition ) {
			$candidate_parent_types = $relationship_definition->get_parent_type()->get_types();
			$candidate_parent_type = array_pop( $candidate_parent_types );

			$candidate_child_types = $relationship_definition->get_child_type()->get_types();
			$candidate_child_type = array_pop( $candidate_child_types );

			if( $candidate_parent_type === $parent_post_type && $candidate_child_type === $child_post_type ) {
				return $relationship_definition;
			}
		}

		return null;
	}


	/**
	 * Rename the relationship definition slug properly.
	 *
	 * Ensure that:
	 * - the database integrity is maintained
	 * - the cache in this repository is updated
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $new_slug
	 *
	 * @return Toolset_Result
	 *
	 * @since m2m
	 */
	public function change_definition_slug( $relationship_definition, $new_slug ) {
		if( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			throw new InvalidArgumentException();
		}

		$slug_validator = new Toolset_Relationship_Slug_Validator( $new_slug, $relationship_definition );

		$slug_validation_result = $slug_validator->validate();
		if( $slug_validation_result->is_error() ) {
			return $slug_validation_result;
		}

		// Update the definition instance
		$previous_slug = $relationship_definition->get_slug();
		$relationship_definition->set_slug( $new_slug );

		// Update the storage
		$this->remove_definition( $previous_slug );
		$this->insert_definition( $relationship_definition );

		$this->save_definitions();

		// The association table needs an update as well
		$association_update_result = Toolset_Relationship_Database_Operations::update_associations_on_definition_renaming(
			$previous_slug, $new_slug
		);

		if( $association_update_result->is_error() ) {
			return $association_update_result;
		}

		return new Toolset_Result(
			true,
			sprintf(
				__( 'Relationship slug was successfully renamed from "%s" to "%s".', 'wpcf' ),
				$previous_slug,
				$new_slug
			)
		);
	}


	private function get_association_repository() {
		if( null === $this->_association_repository ) {
			$this->_association_repository = Toolset_Association_Repository::get_instance();
		}

		return $this->_association_repository;
	}


	private function get_database_operations() {
		if( null === $this->_database_operations ) {
			$this->_database_operations = new Toolset_Relationship_Database_Operations();
		}

		return $this->_database_operations;
	}

	private function get_definition_factory() {
		if( null === $this->_definition_factory ) {
			$this->_definition_factory = new Toolset_Relationship_Definition_Factory();
		}

		return $this->_definition_factory;
	}

}