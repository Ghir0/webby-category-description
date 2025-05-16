<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      0.2
 * @package    Webby_Category_Description
 * @subpackage Webby_Category_Description/admin
 * @author     Michael Tamanti
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The admin-specific functionality of the plugin.
 */
class Webby_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.2
     */
    public function __construct() {
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register the admin menu.
     *
     * @since    0.2
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Webby Category Description', 'webby-category-description' ),
            __( 'Webby Category', 'webby-category-description' ),
            'manage_options',
            'webby-category-description',
            array( $this, 'display_admin_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    0.2
     */
    public function register_settings() {
        // Register settings
        register_setting(
            'webby_category_settings',
            'webby_openai_api_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_api_key' ),
                'default'           => '',
            )
        );

        register_setting(
            'webby_category_settings',
            'webby_default_language',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'en',
            )
        );

        // Add settings section
        add_settings_section(
            'webby_category_section',
            __( 'OpenAI API Settings', 'webby-category-description' ),
            array( $this, 'settings_section_callback' ),
            'webby-category-description'
        );

        // Add settings fields
        add_settings_field(
            'webby_openai_api_key',
            __( 'OpenAI API Key', 'webby-category-description' ),
            array( $this, 'api_key_field_callback' ),
            'webby-category-description',
            'webby_category_section'
        );

        add_settings_field(
            'webby_default_language',
            __( 'Default Language', 'webby-category-description' ),
            array( $this, 'default_language_field_callback' ),
            'webby-category-description',
            'webby_category_section'
        );
    }

    /**
     * Sanitize the API key.
     *
     * @since    0.2
     * @param    string    $input    The API key input.
     * @return   string              The sanitized API key.
     */
    public function sanitize_api_key( $input ) {
        // Basic sanitization for API key
        return sanitize_text_field( $input );
    }

    /**
     * Settings section callback.
     *
     * @since    0.2
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Enter your OpenAI API key and default settings below.', 'webby-category-description' ) . '</p>';
    }

    /**
     * API key field callback.
     *
     * @since    0.2
     */
    public function api_key_field_callback() {
        $api_key = get_option( 'webby_openai_api_key' );
        ?>
        <input type="password" id="webby_openai_api_key" name="webby_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Enter your OpenAI API key. You can get one from https://platform.openai.com/api-keys', 'webby-category-description' ); ?></p>
        <?php
    }

    /**
     * Default language field callback.
     *
     * @since    0.2
     */
    public function default_language_field_callback() {
        $default_language = get_option( 'webby_default_language', 'en' );
        $languages = array(
            'en' => __( 'English', 'webby-category-description' ),
            'es' => __( 'Spanish', 'webby-category-description' ),
            'fr' => __( 'French', 'webby-category-description' ),
            'de' => __( 'German', 'webby-category-description' ),
            'it' => __( 'Italian', 'webby-category-description' ),
            'pt' => __( 'Portuguese', 'webby-category-description' ),
            'ru' => __( 'Russian', 'webby-category-description' ),
            'zh' => __( 'Chinese', 'webby-category-description' ),
            'ja' => __( 'Japanese', 'webby-category-description' ),
            'ko' => __( 'Korean', 'webby-category-description' ),
        );
        ?>
        <select id="webby_default_language" name="webby_default_language">
            <?php foreach ( $languages as $code => $name ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_language, $code ); ?>><?php echo esc_html( $name ); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Select the default language for generated descriptions.', 'webby-category-description' ); ?></p>
        <?php
    }

    /**
     * Display the admin page.
     *
     * @since    0.2
     */
    public function display_admin_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields( 'webby_category_settings' );
                
                // Output setting sections and their fields
                do_settings_sections( 'webby-category-description' );
                
                // Output save settings button
                submit_button( __( 'Save Settings', 'webby-category-description' ) );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the JavaScript and CSS for the admin area.
     *
     * @since    0.2
     */
    public function enqueue_scripts( $hook ) {
        // Only load on plugin settings page
        if ( 'settings_page_webby-category-description' !== $hook ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'webby-admin',
            WEBBY_CATEGORY_PLUGIN_URL . 'admin/css/webby-admin.css',
            array(),
            WEBBY_CATEGORY_VERSION,
            'all'
        );

        // Enqueue JS
        wp_enqueue_script(
            'webby-admin',
            WEBBY_CATEGORY_PLUGIN_URL . 'admin/js/webby-admin.js',
            array( 'jquery' ),
            WEBBY_CATEGORY_VERSION,
            false
        );
    }
}
