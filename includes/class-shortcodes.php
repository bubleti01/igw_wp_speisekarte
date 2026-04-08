<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Shortcodes {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
	}

	public static function register_shortcodes() {
		add_shortcode( 'igw_spk_in_home', array( __CLASS__, 'shortcode_home' ) );
		add_shortcode( 'igw_spk_abschnitte', array( __CLASS__, 'shortcode_abschnitt' ) );
	}

	public static function shortcode_home( $atts = array() ) {
		return igw_spk_render_home_list();
	}

	public static function shortcode_abschnitt( $atts = array() ) {
		return igw_spk_render_abschnitt_list( $atts );
	}
}
