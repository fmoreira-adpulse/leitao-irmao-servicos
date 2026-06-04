<?php
    /*
    *
    *	Wpbingo Framework Menu Functions
    *	------------------------------------------------
    *	Wpbingo Framework v3.0
    * 	Copyright Wpbingo Ideas 2017 - http://wpbingosite.com/
    *
    *	funio_setup_menus()
    *
    */
    /* CUSTOM MENU SETUP
    ================================================== */
    register_nav_menus( array(
        'main_navigation' 	=> esc_html__( 'Main Menu', 'funio' ),
		'topbar_menu'   	=> esc_html__( 'Topbar Menu', 'funio' ),
		'menu_left'         => esc_html__( 'Menu Left', 'funio' ),
		'menu_right'        => esc_html__( 'Menu Right', 'funio' ) 
    ) );
?>