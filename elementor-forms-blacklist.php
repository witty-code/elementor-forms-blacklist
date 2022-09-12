<?php

/*
Plugin Name: elementor forms blacklist
Plugin URI: https://witty-code.com
Description: Elementor form protection based on wordpress comments blacklist
Version: 1.0.0
Author: witty-code.com
Author URI: https://witty-code.com
Text Domain: efb

* Elementor tested up to:     3.6.7
* Elementor Pro tested up to: 3.7.2
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

final class elementor_forms_blacklist_plugin {
    
    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '3.5.0';
    const MINIMUM_PHP_VERSION = '7.4';
    
    private static $_instance = null;
    
    public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}
	
	public function __construct() {

		if ( $this->is_compatible() ) {
			add_action( 'elementor_pro/init', [ $this, 'init' ] );
		}

	}
	
	public function is_compatible() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return false;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return false;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return false;
		}

		return true;

	}
	
	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'sender-for-elementor-forms' ),
			'<strong>' . esc_html__( 'Sender For Elementor Forms', 'sender-for-elementor-forms' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'sender-for-elementor-forms' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'sender-for-elementor-forms' ),
			'<strong>' . esc_html__( 'Sender For Elementor Forms', 'sender-for-elementor-forms' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'sender-for-elementor-forms' ) . '</strong>',
			 self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'sender-for-elementor-forms' ),
			'<strong>' . esc_html__( 'Sender For Elementor Forms', 'sender-for-elementor-forms' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'sender-for-elementor-forms' ) . '</strong>',
			 self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}
	
	public function init() {
	    
	    add_action( 'elementor_pro/forms/validation', function ( $record, $ajax_handler ) {
    
	        $ce = (int) class_exists('\ElementorPro\Core\Utils');
    
	        try{
		        $ip = \ElementorPro\Core\Utils::get_client_ip();
	        }
	        catch(Exception $e){
		        $ip = $e->getMessage();
	        }
	
            // 	$disallowed_keys = get_option('blacklist_keys');
	        $disallowed_keys = get_option('disallowed_keys');
	        $disallowed_keys_array = explode( "\n", $disallowed_keys );
	        $disallowed_keys = implode( '|', $disallowed_keys_array );
        
	        if ( false != preg_match( '/'.$disallowed_keys.'/', $ip ) ) {
                $ajax_handler->add_error( 'IP', 'You are not authorized to submit this form' );
            }
    	
            $raw_fields = $record->get( 'fields' );
            $fields = [];
            foreach ( $raw_fields as $id => $field ) {
		        if ( false != preg_match( '/'.$disallowed_keys.'/', $field['value'] ) ) {
        	        $ajax_handler->add_error( $field['id'], 'Invalid message' );
                }
            }
        }, 10, 2 );
	}
}


function elementor_forms_blacklist() {
	// Run the plugin
	elementor_forms_blacklist_plugin::instance();
}
add_action( 'plugins_loaded', 'elementor_forms_blacklist' );
