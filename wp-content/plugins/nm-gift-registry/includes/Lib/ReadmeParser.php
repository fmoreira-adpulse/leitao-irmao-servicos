<?php

namespace NMGR\Lib;

defined( 'ABSPATH' ) || exit;

class ReadmeParser {

	public $filepath; // Full filepath to the readme.txt file
	public $content; // Raw contents of the readme.txt file
	public $split_content = []; // Contents of the readme.txt file split by sections
	public $section_heading_pattern = '/(^== .*? ==$)/m'; // Pattern for spliting sections
	public $subsection_heading_pattern = '/(^= .*? =$)/m'; // Pattern for splitting subsections
	private $metadata = null;

	/**
	 * Parse WordPress readme.txt file
	 * @param string $filepath The filepath to the readme.txt file
	 */
	public function __construct( $filepath ) {
		$this->filepath = $filepath;
		$this->content = file_get_contents( $this->filepath );
		$this->split_content = $this->split_by_section( $this->content );
	}

	/**
	 * Splits a readme.txt string by the sections in the string.
	 *
	 * Sections are defined by headings such as "== Heading =="
	 * @param string $string The content to split
	 * @return array|false
	 */
	public function split_by_section( $string ) {
		$split = preg_split( $this->section_heading_pattern, $string, -1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		return $split ? $split : [];
	}

	/**
	 * Splits a readme.txt string by the subsections in the string.
	 *
	 * Subsections are defined by headings such as "= Heading ="
	 * @param string $string The content to split
	 * @return array|false
	 */
	public function split_by_subsection( $string ) {
		$split = preg_split( $this->subsection_heading_pattern, $string, -1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		return $split ? $split : [];
	}

	/**
	 * Get the raw heading from a markdown heading string
	 * e.g get "Heading" from "== Heading =="
	 * This function can be used for headings with "===", "==", or "=" markdown
	 * @param string $string The markdown heading string
	 * @return string
	 */
	public function get_heading( $string ) {
		$heading = [];
		preg_match( '/= (.+?) =/', $string, $heading );
		return $heading[ 1 ] ?? '';
	}

	/**
	 * Get the plugin header
	 * This is the plugin title and metadata such as Tags, Requires at least
	 * @return string
	 */
	public function get_header() {
		return $this->split_content[ 0 ] ?? '';
	}

	/**
	 * Get the plugin title
	 * @return string
	 */
	public function get_title() {
		return $this->get_heading( $this->get_header() );
	}

	/**
	 * Get the stable tag
	 * @return string
	 */
	public function get_stable_tag() {
		return $this->get_metadata()[ 'Stable tag' ] ?? '';
	}

	/**
	 * Get the latest version of the plugin from the Stable tag property.
	 *
	 * Alias of get_stable_tag().
	 * @return string
	 */
	public function get_latest_version() {
		return $this->get_stable_tag();
	}

	/**
	 * Get the plugin metadata e.g. Tags, Requires at least
	 * @return array
	 */
	public function get_metadata() {
		if ( !is_null( $this->metadata ) ) {
			return $this->metadata;
		}

		$pattern = '/(^=== .*? ===$)/m'; // title pattern in header
		$meta = preg_split( $pattern, $this->get_header(), -1, PREG_SPLIT_NO_EMPTY );

		if ( !$meta ) {
			return [];
		}

		$meta_array = $metadataum_matches = [];
		foreach ( array_filter( explode( "\n", $meta[ 0 ] ) ) as $metadatum ) {
			if ( false !== preg_match( '/^(.+?):\s*?(.+)$/', $metadatum, $metadataum_matches ) &&
				!empty( $metadataum_matches ) ) {
				list( $name, $value ) = array_slice( $metadataum_matches, 1, 2 );
				$meta_array[ $name ] = trim( $value );
			}
		}
		return $meta_array;
	}

	/**
	 * Get the sections in the readme.txt file
	 *
	 * Sections are defined by headings such as "== Heading =="
	 */
	public function get_sections() {
		return $this->join_section_heading_to_content( $this->split_content, $this->section_heading_pattern );
	}

	/**
	 * Get the subsections of a particular section in the readme.txt file
	 *
	 * Subections are defined by headings such as "= Heading ="
	 * @param string $string The content of the section to get subsections for
	 * @return array|false
	 */
	public function get_subsections( $string ) {
		return $this->join_section_heading_to_content( $this->split_by_subsection( $string ),
				$this->subsection_heading_pattern );
	}

	/**
	 * Create an array of section headings to their content from the array of a split section
	 * @param array $split_section The split section
	 * @param string $section_pattern The regex pattern used to extract the section heading
	 * @return array
	 */
	private function join_section_heading_to_content( $split_section, $section_pattern ) {
		$sections = [];

		for ( $i = 0; $i < count( ( array ) $split_section ) - 1; $i++ ) {
			$current = current( $split_section );
			$next = next( $split_section );
			if ( preg_match( $section_pattern, $current ) ) {
				$sections[ $this->get_heading( $current ) ] = $next;
			}
		}

		return $sections;
	}

	/**
	 * Get the pro version features from the description section.
	 *
	 * Pro version features are list items which start with the heading
	 * '= Pro version features ='
	 * @param boolean $as_array Whether to return features as an array. Default false.
	 * @return string|array
	 */
	public function get_pro_version_features( $as_array = false ) {
		$subsections = $this->get_subsections( $this->get_sections()[ 'Description' ] ?? '' );
		$features = $subsections[ 'Pro version features' ] ?? '';
		return $as_array ? $this->get_list( $features ) : $features;
	}

	/**
	 * Extract list items from a string.
	 *
	 * List items start with "* "
	 * @param string $string The string to get the list items from
	 * @return array
	 */
	public function get_list( $string ) {
		$matches = [];
		preg_match_all( '/\*\s(.*)/', $string, $matches );
		return $matches[ 1 ] ?? [];
	}

	/**
	 * Convert a readme text to html
	 * @todo Not-tested
	 * @param string $string The text to convert
	 * @return string
	 */
	public function to_html( $string ) {
		$string = preg_replace( '/\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $string );
		$string = preg_replace( '/\*(.*?)\*/', ' <em>\\1</em>', $string );
		$string = preg_replace( '/=== (.*?) ===/', '<h2>\\1</h2>', $string );
		$string = preg_replace( '/== (.*?) ==/', '<h3>\\1</h3>', $string );
		$string = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $string );
		$string = preg_replace( '/\[([^]]*)\]\(([^\)]+)\)/', '<a href="\2">\1</a>', $string );

		$string = preg_replace( "/\*+(.*)?/i", "<ul><li>$1</li></ul>", $string );
		$string = preg_replace( "/(\<\/ul\>\n(.*)\<ul\>*)+/", "", $string );

		return $string;
	}

}
