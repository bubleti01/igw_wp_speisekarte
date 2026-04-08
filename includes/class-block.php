<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_SPK_Block {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	public static function register_block() {
		wp_register_script(
			'igw-spk-home-block',
			IGW_SPK_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render' ),
			'1.0.0',
			true
		);

		register_block_type(
			IGW_SPK_PLUGIN_PATH . 'blocks/home',
			array(
				'editor_script'   => 'igw-spk-home-block',
				'render_callback' => array( __CLASS__, 'render_home_block' ),
			)
		);
	}

	public static function render_home_block( $attributes = array(), $content = '', $block = null ) {
		return igw_spk_render_home_list();
	}
}

function igw_spk_render_home_block( $attributes = array(), $content = '', $block = null ) {
	return IGW_SPK_Block::render_home_block( $attributes, $content, $block );
}
