<?php
/**
 * The category functionality of the plugin.
 *
 * @since      0.2
 * @package    Webby_Category_Description
 * @subpackage Webby_Category_Description/includes
 * @author     Michael Tamanti
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The category functionality of the plugin.
 */
class Webby_Category {

    /**
     * The OpenAI API instance.
     *
     * @since    0.2
     * @access   private
     * @var      Webby_API    $api    The OpenAI API instance.
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.2
     */
    public function __construct() {
        // Initialize the API
        $this->api = new Webby_API();

        // Add the generate button to category edit form
        add_action( 'category_edit_form_fields', array( $this, 'add_generate_button' ), 10, 2 );
        
        // Add the generate button to add category form
        add_action( 'category_add_form_fields', array( $this, 'add_generate_button_new' ), 10 );
        
        // WooCommerce product category support
        add_action( 'product_cat_edit_form_fields', array( $this, 'add_generate_button' ), 10, 2 );
        add_action( 'product_cat_add_form_fields', array( $this, 'add_generate_button_new' ), 10 );
        
        // Register AJAX handler
        add_action( 'wp_ajax_webby_generate_description', array( $this, 'ajax_generate_description' ) );
        
        // Enqueue scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add generate button to category edit form.
     *
     * @since    0.2
     * @param    object    $tag       The tag object.
     * @param    string    $taxonomy  The taxonomy slug.
     */
    public function add_generate_button( $tag, $taxonomy ) {
        // Only add to supported taxonomies
        if ( 'category' !== $taxonomy && 'product_cat' !== $taxonomy ) {
            return;
        }

        // Get the default language
        $default_language = get_option( 'webby_default_language', 'en' );

        // Get available languages
        $languages = $this->get_available_languages();
        ?>
        <tr class="form-field term-description-wrap">
            <th scope="row"></th>
            <td>
                <div id="webby-generate-container" style="margin-top: 10px;">
                    <select id="webby-language-select" style="vertical-align: middle;">
                        <?php foreach ( $languages as $code => $name ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_language, $code ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="webby-generate-button" class="button button-secondary" data-term-id="<?php echo esc_attr( $tag->term_id ); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
                        <?php esc_html_e( 'Generate Description with AI', 'webby-category-description' ); ?>
                    </button>
                    <span id="webby-generate-spinner" class="spinner" style="float: none;"></span>
                    <div id="webby-generate-message" style="margin-top: 5px;"></div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Add generate button to add category form.
     *
     * @since    0.2
     * @param    string    $taxonomy  The taxonomy slug.
     */
    public function add_generate_button_new( $taxonomy ) {
        // Only add to supported taxonomies
        if ( 'category' !== $taxonomy && 'product_cat' !== $taxonomy ) {
            return;
        }

        // Get the default language
        $default_language = get_option( 'webby_default_language', 'en' );

        // Get available languages
        $languages = $this->get_available_languages();
        ?>
        <div class="form-field term-description-wrap">
            <div id="webby-generate-container" style="margin-top: 10px;">
                <select id="webby-language-select" style="vertical-align: middle;">
                    <?php foreach ( $languages as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_language, $code ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="webby-generate-button" class="button button-secondary" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
                    <?php esc_html_e( 'Generate Description with AI', 'webby-category-description' ); ?>
                </button>
                <span id="webby-generate-spinner" class="spinner" style="float: none;"></span>
                <div id="webby-generate-message" style="margin-top: 5px;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for generating descriptions.
     *
     * @since    0.2
     */
    public function ajax_generate_description() {
        // Check nonce
        if ( ! check_ajax_referer( 'webby_generate_description', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'webby-category-description' ),
            ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_categories' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'webby-category-description' ),
            ) );
        }

        // Get parameters
        $term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'en';
        $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : 'category';

        // Validate parameters
        if ( empty( $name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Category name is required.', 'webby-category-description' ),
            ) );
        }

        // Get parent category name if applicable
        $parent_name = '';
        if ( $term_id > 0 ) {
            $term = get_term( $term_id, $taxonomy );
            if ( $term && $term->parent > 0 ) {
                $parent_term = get_term( $term->parent, $taxonomy );
                if ( $parent_term && ! is_wp_error( $parent_term ) ) {
                    $parent_name = $parent_term->name;
                }
            }
        }

        // Generate description
        $result = $this->api->generate_description( $name, $language, $parent_name );

        if ( ! $result['success'] ) {
            wp_send_json_error( array(
                'message' => $result['message'],
            ) );
        }

        wp_send_json_success( array(
            'description' => $result['description'],
        ) );
    }

    /**
     * Enqueue scripts and styles for category pages.
     *
     * @since    0.2
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on category pages
        if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
            return;
        }

        // Get the taxonomy
        $taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
        if ( 'category' !== $taxonomy && 'product_cat' !== $taxonomy ) {
            return;
        }

        // Enqueue script
        wp_enqueue_script(
            'webby-category',
            WEBBY_CATEGORY_PLUGIN_URL . 'includes/js/webby-category.js',
            array( 'jquery' ),
            WEBBY_CATEGORY_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'webby-category',
            'webbyCategory',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'webby_generate_description' ),
                'generating' => __( 'Generating...', 'webby-category-description' ),
                'success'    => __( 'Description generated successfully!', 'webby-category-description' ),
                'error'      => __( 'Error: ', 'webby-category-description' ),
                'apiKeyMissing' => __( 'OpenAI API key is not configured. Please set it in the plugin settings.', 'webby-category-description' ),
                'apiKeyConfigured' => $this->api->is_api_key_set(),
            )
        );

        // Add inline CSS
        wp_add_inline_style( 'wp-admin', '
            #webby-generate-message.success {
                color: green;
                font-weight: bold;
            }
            #webby-generate-message.error {
                color: red;
                font-weight: bold;
            }
        ' );
    }

    /**
     * Add a prominent generate button at the top of the category edit form.
     *
     * @since    0.2
     * @param    object    $tag       The tag object.
     */
    public function add_prominent_generate_button( $tag ) {
        // Get the default language
        $default_language = get_option( 'webby_default_language', 'en' );

        // Get available languages
        $languages = $this->get_available_languages();
        ?>
        <div class="form-wrap">
            <div id="webby-prominent-generate-container" style="margin: 15px 0; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <h3 style="margin-top: 0;"><?php esc_html_e( 'Generate Category Description with AI', 'webby-category-description' ); ?></h3>
                <p><?php esc_html_e( 'Use OpenAI to automatically generate a description for this category.', 'webby-category-description' ); ?></p>
                <div style="display: flex; align-items: center; margin-top: 10px;">
                    <select id="webby-prominent-language-select" style="margin-right: 10px;">
                        <?php foreach ( $languages as $code => $name ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_language, $code ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="webby-prominent-generate-button" class="button button-primary" data-term-id="<?php echo esc_attr( $tag->term_id ); ?>" data-taxonomy="category">
                        <?php esc_html_e( 'Generate Description', 'webby-category-description' ); ?>
                    </button>
                    <span id="webby-prominent-spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
                </div>
                <div id="webby-prominent-message" style="margin-top: 10px; font-weight: bold;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Get available languages.
     *
     * @since    0.2
     * @return   array    The available languages.
     */
    private function get_available_languages() {
        return array(
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
    }
}
