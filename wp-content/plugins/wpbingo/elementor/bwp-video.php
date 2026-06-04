<?php
namespace ElementorWpbingo\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Bwp_Video extends Widget_Base {
	public function get_name() {
		return 'bwp_video';
	}
	public function get_title() {
		return __( 'Wpbingo Video', 'wpbingo' );
	}
	public function get_icon() {
		return 'fa fa-video-camera';
	}	
	public function get_categories() {
		return [ 'general' ];
	}
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'wpbingo' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);		
		$this->add_control(
			'title1',
			[
				'label' => __( 'Title', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => __( 'Type your title here', 'wpbingo' ),
			]
		);	
		$this->add_control(
			'description',
			[
				'label' => __( 'Description', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
				'placeholder' => __( 'Type your description here', 'wpbingo' ),
			]
		);
		$this->add_control(
			'labelbutton',
			[
				'label' => __( 'Label Button', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => __( 'Type your label button here', 'wpbingo' ),
			]
		);
		$this->add_control(
			'linkbutton',
			[
				'label' => __( 'Link Button', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '#',
				'placeholder' => __( 'Type your link button here', 'wpbingo' ),
			]
		);
		$this->add_control(
			'class',
			[
				'label' => __( 'Extra Class', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => __( 'Type your Extra Class here', 'wpbingo' ),
			]
		);		
		$this->add_control(
			'link',
			[
				'label' => __( 'Video Link', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '#',
				'placeholder' => __( 'Type your title here', 'wpbingo' ),
			]
		);		
		$this->add_control(
			'image',
			[
				'label' => __( 'Choose Image', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);
		$this->add_control(
			'layout',
			[
				'label' => __( 'Layout', 'wpbingo' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default'  => __( 'Default', 'wpbingo' ),
					'default2'  => __( 'Default2', 'wpbingo' ),
					'layout1'  => __( 'Layout 1', 'wpbingo' )
				],
			]
		);		
		$this->end_controls_section();
	}
	protected function render() {
		$settings = $this->get_settings_for_display();
		extract( shortcode_atts(
			array(
				'title1' => '',
				'description' => '',
				'labelbutton' => '',
				'linkbutton' => '',
				'class' => '',
				'link' => '#',
				'image' => '',
				'layout'  => 'default',
			), $settings )
		);
		$image		 = 	( $settings['image'] && $settings['image']['url'] ) ? $settings['image']['url'] : '';
		if( $settings['layout'] == 'default' ){
			include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'bwp-video/default.php' );
		}elseif( $settings['layout'] == 'default2' ){
			include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'bwp-video/default2.php' );
		}elseif( $settings['layout'] == 'layout1' ){
			include(WPBINGO_ELEMENTOR_TEMPLATE_PATH.'bwp-video/layout1.php' );
		}
	}
}