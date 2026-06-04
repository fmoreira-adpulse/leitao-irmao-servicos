<?php

namespace NMGR\Sub;

defined( 'ABSPATH' ) || exit;

class Wishlist_Item extends \NMGR_Wishlist_Item {

	protected $pro_core_data = [
		'favourite' => 0,
		'archived' => 0,
	];

	public function __construct( $item = 0 ) {
		$this->core_data = array_merge( $this->core_data, $this->pro_core_data );
		parent::__construct( $item );
	}

	/**
	 * Get the favourite value of this item
	 *
	 * @return int
	 */
	public function get_favourite() {
		return absint( $this->get_prop( 'favourite' ) );
	}

	/**
	 * Set item favourite
	 * @param int $value favourite
	 */
	public function set_favourite( $value ) {
		$this->set_prop( 'favourite', absint( $value ) );
	}

	/**
	 * Get whether the item is a favourite
	 *
	 * @return boolean
	 */
	public function is_favourite() {
		return ( bool ) $this->get_favourite();
	}

	/**
	 * Get the archived value for the item
	 * @return int
	 */
	public function get_archived() {
		return absint( $this->get_prop( 'archived' ) );
	}

	/**
	 * Set the archived value for the item
	 * @param int $value
	 */
	public function set_archived( $value ) {
		$this->set_prop( 'archived', absint( $value ) );
	}

	public function is_archived() {
		return ( bool ) $this->get_archived();
	}

	/**
	 * Archive this item
	 */
	public function archive() {
		$this->set_archived( 1 );
		$this->save();
	}

	/**
	 * Unarchive this item
	 */
	public function unarchive() {
		$this->set_archived( 0 );
		$this->save();
	}

	public function save() {
		$changes = array_keys( $this->get_changes() );
		if ( !in_array( 'archived', $changes ) &&
			(in_array( 'purchased_quantity', $changes ) || in_array( 'quantity', $changes )) ) {
			$this->set_archived( ( int ) $this->is_fulfilled() );
		}
		parent::save();
	}

}
