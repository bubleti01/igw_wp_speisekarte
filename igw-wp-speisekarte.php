<?php
/**
 * Plugin Name: IGW WP Speisekarte
 * Plugin URI: https://igo2web.com/de/wordpress-plugins-von-igw-design/wp-speisekarte/
 * Description: Verwalte Speisen mit Zutaten, Allergenen und Zusatzstoffen und gib sie per Shortcode flexibel aus.
 * Version: 1.0.9
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: IGW Design
 * Author URI: https://igo2web.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: igw_wp_speisekarte
 * Domain Path: /languages
 * Update URI: https://igo2web.com/de/wordpress-plugins-von-igw-design/wp-speisekarte/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IGW_SPK_PLUGIN_FILE', __FILE__ );
define( 'IGW_SPK_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'IGW_SPK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IGW_SPK_VERSION', '1.0.9' );

require_once IGW_SPK_PLUGIN_PATH . 'includes/helpers.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-cpt.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-meta.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-admin.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-seed.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-render.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-shortcodes.php';
require_once IGW_SPK_PLUGIN_PATH . 'includes/class-templates.php';

function igw_spk_load_textdomain() {
	load_plugin_textdomain( 'igw_wp_speisekarte', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'igw_spk_load_textdomain' );

function igw_spk_bootstrap() {
	IGW_SPK_CPT::init();
	IGW_SPK_Meta::init();
	IGW_SPK_Admin::init();
	IGW_SPK_Shortcodes::init();
	IGW_SPK_Templates::init();
}
add_action( 'plugins_loaded', 'igw_spk_bootstrap', 20 );

function igw_spk_enqueue_assets() {
	wp_enqueue_style( 'igw-spk-frontend', IGW_SPK_PLUGIN_URL . 'assets/css/frontend.css', array(), IGW_SPK_VERSION );
}
add_action( 'wp_enqueue_scripts', 'igw_spk_enqueue_assets' );

function igw_spk_admin_assets() {
	wp_enqueue_style( 'igw-spk-admin', IGW_SPK_PLUGIN_URL . 'assets/css/admin.css', array(), IGW_SPK_VERSION );
}
add_action( 'admin_enqueue_scripts', 'igw_spk_admin_assets' );

function igw_spk_activate_plugin() {
	IGW_SPK_CPT::register_post_types();
	IGW_SPK_CPT::register_taxonomies();
	IGW_SPK_Meta::register_meta();
	IGW_SPK_Seed::seed_default_terms();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'igw_spk_activate_plugin' );

function igw_spk_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'igw_spk_deactivate_plugin' );
