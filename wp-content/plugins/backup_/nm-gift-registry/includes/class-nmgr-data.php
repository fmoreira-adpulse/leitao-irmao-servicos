<?php

/**
 * Sync
 */
defined( 'ABSPATH' ) || exit;

/**
 * Abstract NMGR Data Class
 *
 * Handles generic data interaction implemented by classes using the same CRUD pattern.
 */
abstract class NMGR_Data {

	/**
	 * ID for this object.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Core data for this object.
	 *
	 * @var array
	 */
	protected $core_data = array();

	/**
	 * Meta data stored as internal meta keys for object
	 *
	 * @var array
	 */
	protected $meta_data = array();

	/**
	 * 	All data for this object (merges core and meta data)
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Set to $data on construct so we can track and reset data if needed.
	 *
	 * @var array
	 */
	protected $default_data = array();

	/**
	 * Extra data connected to the object that is not part of its core or meta data.
	 * Usually added externally.
	 *
	 * @var array
	 */
	protected $extra_data = array();

	/**
	 * Data changes for this object.
	 *
	 * @var array
	 */
	protected $changes = array();

	/**
	 * Whether the object has been read from the database
	 *
	 * This property is used to as a flag to store changes to the object in a separate array
	 * rather than in the actual object data from the database thus modifying the read results.
	 * It allows changes to be tracked and saved to database appropriately
	 */
	protected $object_read = false;

	/**
	 * Name of the object type
	 *
	 * @var string
	 */
	protected $object_type = 'data';

	/**
	 * Meta type.
	 *
	 * Example, post, user.
	 * Used to determine the db table to save the object meta to.
	 * @var string
	 */
	protected $meta_type;

	/**
	 * Default constructor.
	 *
	 * @param int|object|array $object ID of Object or Object data with id property
	 */
	public function __construct( $object = 0 ) {
		if ( is_numeric( $object ) && $object > 0 ) {
			$this->set_id( $object );
		} elseif ( $object instanceof self ) {
			$this->set_id( absint( $object->get_id() ) );
		}

		$this->default_data = array_merge(
			[ 'id' => $this->get_id() ],
			$this->get_core_data(),
			$this->get_meta_data(),
			$this->get_extra_data()
		);
		$this->data = $this->get_default_data();
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @return int
	 */
	public function get_id() {
		return ( int ) $this->id;
	}

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data() {
		return apply_filters( 'nmgr_get_data', $this->data, $this );
	}

	/**
	 * Get the core data for this object
	 *
	 * @return array
	 */
	public function get_core_data() {
		$data = apply_filters( 'nmgr_get_core_data', $this->core_data, $this );
		return array_merge( $data, array_intersect_key( $this->data, $data ) );
	}

	/**
	 * Get the meta data for this object
	 *
	 * @return array
	 */
	public function get_meta_data() {
		$data = apply_filters( 'nmgr_get_meta_data', $this->meta_data, $this );
		return array_merge( $data, array_intersect_key( $this->data, $data ) );
	}

	/**
	 * Get the default data for this object
	 *
	 * @return array
	 */
	public function get_default_data() {
		return apply_filters( 'nmgr_default_data', $this->default_data, $this );
	}

	/**
	 * Get the extra data for this object
	 *
	 * @return array
	 */
	public function get_extra_data() {
		$data = apply_filters( 'nmgr_get_extra_data', $this->extra_data, $this );
		return array_merge( $data, array_intersect_key( $this->data, $data ) );
	}

	/**
	 * Get the data object type
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Save should create or update based on whether the object id exists
	 *
	 * @return int Object id
	 */
	public function save() {
		do_action( 'nmgr_data_before_save', $this );

		if ( $this->get_id() ) {
			$this->update();
		} else {
			$this->create();
		}

		do_action( 'nmgr_save_extra_data', $this->get_extra_data(), $this );
		do_action( 'nmgr_data_after_save', $this );

		return $this->get_id();
	}

	/**
	 * Set ID.
	 *
	 * @param int $id ID.
	 */
	public function set_id( $id ) {
		$this->id = ( int ) $id;
		$this->set_prop( 'id', ( int ) $id );
	}

	/**
	 * Set all props to default values.
	 */
	public function set_defaults() {
		$this->data = $this->default_data;
		$this->changes = array();
		$this->set_object_read( false );
	}

	/**
	 * Set object read property.
	 *
	 * @param boolean $read Should read?.
	 */
	public function set_object_read( $read = true ) {
		$this->object_read = ( bool ) $read;
	}

	/**
	 * Get object read property.
	 *
	 * @return boolean
	 */
	public function get_object_read() {
		return ( bool ) $this->object_read;
	}

	/**
	 * Set a collection of properties in one go
	 *
	 * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 */
	public function set_props( $props ) {
		foreach ( $props as $prop => $value ) {
			$setter = "set_$prop";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_prop( $prop, $value );
			}
		}
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving to the DB later.
	 *
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	public function set_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->get_data() ) ) {
			if ( true === $this->object_read ) {
				$stored_value = is_callable( array( $this, "get_$prop" ) ) ? $this->{"get_$prop"}( $value ) : $this->get_prop( $prop );
				if ( $value !== $stored_value ) {
					$this->changes[ $prop ] = $value;
				}
			} else {
				$this->data[ $prop ] = $value;
			}
		} else {
			$this->extra_data[ $prop ] = $value;
		}
	}

	/**
	 * Return data changes only.
	 *
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear.
	 */
	public function apply_changes() {
		$changes = $this->get_changes();
		$original_data = $this->get_data();
		$new_data = array_replace_recursive( $original_data, $changes );
		$this->data = $new_data;
		$this->changes = array();

		foreach ( $changes as $key => $new_value ) {
			$old_value = $original_data[ $key ] ?? '';
			do_action_deprecated( 'nmgr_updated_meta_key', array( $key, $new_value, $old_value, $this ), '4.4.0', 'nmgr_save_prop' );
			do_action( 'nmgr_save_prop', $key, $new_value, $old_value, $this );
		}

		do_action_deprecated( 'nmgr_updated_metadata', [ $new_data, $original_data, $this ], '4.4.0', 'nmgr_save' );
		do_action( 'nmgr_save', $new_data, $original_data, $this );
	}

	/**
	 * Gets a value of a property for the object
	 *
	 * Gets the value from either current pending changes or from the data itself.
	 *
	 * @param  string $prop Name of property to get.
	 * @return mixed
	 */
	public function get_prop( $prop ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data ) ) {
			$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];
		}

		return $this->filter_prop_value( $value, $prop );
	}

	/**
	 * Filter the returned value of a property
	 *
	 * This utility function is simply used to provide a common filter for normal
	 * properties and child properties of data objects
	 *
	 * @param mixed $value The property's value
	 * @param string $prop The property name
	 * @param string $parent The name of the property's parent if it is a child property
	 * @return mixed The filtered property's value
	 */
	public function filter_prop_value( $value, $prop, $parent = '' ) {
		$prop_name = $parent ? "{$parent}_{$prop}" : $prop;
		$value = apply_filters( "nmgr_get_prop_{$prop_name}", $value, $parent, $this );
		$value = apply_filters( 'nmgr_get_prop', $value, $prop_name, $parent, $this );
		return $value;
	}

	/**
	 * Get a list of object properties that need updating based on changed status
	 *
	 * @param bool $force	Whether to update all meta or only changed and non-existent ones
	 */
	public function get_props_to_update( $force = false ) {
		$props_to_update = array();
		$meta_keys = $this->get_meta_keys();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		if ( !$force ) {
			foreach ( $meta_keys as $display_key => $meta_key ) {
				if ( array_key_exists( $display_key, $this->get_changes() ) ||
					!metadata_exists( $this->meta_type, $this->get_id(), $meta_key ) ) {
					$props_to_update[ $display_key ] = $meta_key;
				}
			}
		} else {
			$props_to_update = $meta_keys;
		}

		return $props_to_update;
	}

	/**
	 * Get a key, value pair of object internal meta keys to their class properties
	 *
	 * This function expects the parameter $props to be a single-dimensional array
	 * so the array has to be flattened first if it is multi-dimensional else child properties
	 * of parent properties  in the multi-dimensional array would be ignored
	 *
	 * @param type $props Object properties
	 * @return array
	 */
	public function get_internal_meta_keys( $props = array() ) {
		$props_to_meta_keys = array();

		/**
		 * Get all internal meta properties
		 * we use $this->meta_data rather than $this->get_meta_data to get
		 * the core unfiltered meta properties of the object. These are the
		 * true internal meta_keys
		 */
		$internal_meta_props = $this->meta_data;

		if ( empty( $props ) ) {
			foreach ( array_keys( $internal_meta_props ) as $prop ) {
				$props_to_meta_keys[ $prop ] = (0 !== strpos( $prop, '_' )) ? "_$prop" : $prop;
			}
		} else {
			foreach ( array_keys( $props ) as $prop ) {
				if ( array_key_exists( $prop, $internal_meta_props ) ) {
					$props_to_meta_keys[ $prop ] = (0 !== strpos( $prop, '_' )) ? "_$prop" : $prop;
				}
			}
		}
		return $props_to_meta_keys;
	}

	/**
	 * Get the meta keys for saving an object's metadata to the database
	 *
	 * The meta keys should be gotten from get_meta_data() however this
	 * does not distinguish internal meta keys which have the '_' prefix from the
	 * public ones. For this reason, get_meta_keys() is used to return all the keys
	 * as they should be saved in the database. The internal meta keys would have the
	 * '_' prefix, while the public ones may not.
	 *
	 * We can then simply loop through this array and save all the metadata directly
	 * to the database based on their keys.
	 *
	 * @param array $props The supplied data to get all the object meta keys for
	 * @return array
	 */
	public function get_meta_keys( $props = array() ) {
		$internal = $this->get_internal_meta_keys( $props );
		$public = $this->get_meta_data();

		foreach ( array_keys( $public ) as $key ) {
			if ( !isset( $internal[ $key ] ) ) {
				$internal[ $key ] = $key;
			}
		}

		return $internal;
	}

}
