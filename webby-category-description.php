<?php
/**
 * Plugin Name: Webby for WordPress: Category Description
 * Plugin URI: https://www.webemento.com/webby-category-description
 * Description: Generate category and product category descriptions using OpenAI API with customizable length and tone options.
 * Version: 0.3
 * Author: Michael Tamanti
 * Author URI: https://www.webemento.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: webby-category-description
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'WEBBY_CATEGORY_VERSION', '0.3' );
define( 'WEBBY_CATEGORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBBY_CATEGORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WEBBY_CATEGORY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_webby_category_description() {
    // Add default options
    add_option( 'webby_openai_api_key', '' );
    add_option( 'webby_default_language', 'en' );
    add_option( 'webby_prompt_context', '' );
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_webby_category_description() {
    // Cleanup if needed
}

register_activation_hook( __FILE__, 'activate_webby_category_description' );
register_deactivation_hook( __FILE__, 'deactivate_webby_category_description' );

/**
 * Load plugin text domain for translations.
 */
function webby_category_load_textdomain() {
    load_plugin_textdomain( 'webby-category-description', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'webby_category_load_textdomain' );

/**
 * Require the necessary files.
 */
require_once WEBBY_CATEGORY_PLUGIN_DIR . 'includes/class-webby-api.php';
require_once WEBBY_CATEGORY_PLUGIN_DIR . 'includes/class-webby-category.php';

// Load admin functionality only in admin area
if ( is_admin() ) {
    require_once WEBBY_CATEGORY_PLUGIN_DIR . 'admin/class-webby-admin.php';
    new Webby_Admin();
}

// Initialize the category functionality
new Webby_Category();
