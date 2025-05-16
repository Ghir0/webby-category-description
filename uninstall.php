<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      0.2
 * @package    Webby_Category_Description
 * @author     Michael Tamanti
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'webby_openai_api_key' );
delete_option( 'webby_default_language' );

// Clean up any other options or data if needed
