<?php
/**
 * The OpenAI API functionality of the plugin.
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
 * The OpenAI API functionality of the plugin.
 */
class Webby_API {

    /**
     * The OpenAI API key.
     *
     * @since    0.2
     * @access   private
     * @var      string    $api_key    The OpenAI API key.
     */
    private $api_key;

    /**
     * The OpenAI API endpoint.
     *
     * @since    0.2
     * @access   private
     * @var      string    $api_endpoint    The OpenAI API endpoint.
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.2
     */
    public function __construct() {
        $this->api_key = get_option( 'webby_openai_api_key', '' );
    }

    /**
     * Check if the API key is set.
     *
     * @since    0.2
     * @return   boolean    True if the API key is set, false otherwise.
     */
    public function is_api_key_set() {
        return ! empty( $this->api_key );
    }

    /**
     * Generate a category description using OpenAI.
     *
     * @since    0.2
     * @param    string    $category_name    The category name.
     * @param    string    $language         The language code.
     * @param    string    $parent_name      The parent category name (optional).
     * @param    string    $length           The description length (short, medium, long).
     * @param    string    $tone             The description tone (standard, professional, friendly, personal).
     * @return   array                       The response array with success/error info.
     */
    public function generate_description( $category_name, $language = 'en', $parent_name = '', $length = 'medium', $tone = 'standard' ) {
        // Check if API key is set
        if ( ! $this->is_api_key_set() ) {
            return array(
                'success' => false,
                'message' => __( 'OpenAI API key is not set. Please configure it in the plugin settings.', 'webby-category-description' ),
            );
        }

        // Prepare the prompt based on language, parent category, length, and tone
        $prompt = $this->prepare_prompt( $category_name, $language, $parent_name, $length, $tone );

        // Prepare the request arguments
        $args = array(
            'method'  => 'POST',
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'model'       => 'gpt-3.5-turbo',
                'messages'    => array(
                    array(
                        'role'    => 'system',
                        'content' => 'You are a helpful assistant that writes concise, engaging category descriptions for e-commerce or blog websites.',
                    ),
                    array(
                        'role'    => 'user',
                        'content' => $prompt,
                    ),
                ),
                'temperature' => 0.7,
                'max_tokens'  => 300,
            ) ),
        );

        // Make the API request
        $response = wp_remote_post( $this->api_endpoint, $args );

        // Check for errors
        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        // Get the response body
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Check if the response is valid
        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown error occurred.', 'webby-category-description' );
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        // Return the generated description
        return array(
            'success'     => true,
            'description' => trim( $data['choices'][0]['message']['content'] ),
        );
    }

    /**
     * Prepare the prompt for OpenAI based on the category and language.
     *
     * @since    0.2
     * @param    string    $category_name    The category name.
     * @param    string    $language         The language code.
     * @param    string    $parent_name      The parent category name (optional).
     * @param    string    $length           The description length (short, medium, long).
     * @param    string    $tone             The description tone (standard, professional, friendly, personal).
     * @return   string                      The prepared prompt.
     */
    private function prepare_prompt( $category_name, $language = 'en', $parent_name = '', $length = 'medium', $tone = 'standard' ) {
        // Get the language name
        $language_names = array(
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
        );

        $language_name = isset( $language_names[ $language ] ) ? $language_names[ $language ] : 'English';

        // Base prompt
        $prompt = sprintf(
            'Write a concise, engaging and relevant description for a category named "%s" in %s.',
            $category_name,
            $language_name
        );

        // Add parent category context if available
        if ( ! empty( $parent_name ) ) {
            $prompt .= sprintf(
                ' This is a subcategory of "%s".',
                $parent_name
            );
        }

        // Get additional context from settings
        $additional_context = get_option( 'webby_prompt_context', '' );
        if ( ! empty( $additional_context ) ) {
            $prompt .= ' ' . $additional_context;
        }

        // Determine length instructions
        $length_instructions = '';
        switch ( $length ) {
            case 'ultrashort':
                $length_instructions = '3-4 words';
                break;
            case 'short':
                $length_instructions = '1 sentences long';
                break;
            case 'medium':
                $length_instructions = '2 sentences long';
                break;
            case 'long':
                $length_instructions = '3 sentences long';
                break;
            default:
                $length_instructions = '1 sentences long';
        }
        
        // Determine tone instructions
        $tone_instructions = '';
        switch ( $tone ) {
            case 'professional':
                $tone_instructions = 'using a professional and formal tone';
                break;
            case 'friendly':
                $tone_instructions = 'using a friendly and approachable tone';
                break;
            case 'personal':
                $tone_instructions = 'using a personal and conversational tone';
                break;
            case 'standard':
            default:
                $tone_instructions = 'using a standard e-commerce tone';
        }
        
        // Additional instructions
        $prompt .= sprintf(
            ' The description should be %s, %s, highlight the key aspects of this category, and encourage users to explore it. Do not use HTML tags or formatting.',
            $length_instructions,
            $tone_instructions
        );

        return $prompt;
    }

    /**
     * Validate the OpenAI API key.
     *
     * @since    0.2
     * @param    string    $api_key    The API key to validate.
     * @return   boolean               True if the API key is valid, false otherwise.
     */
    public function validate_api_key( $api_key ) {
        // Simple format validation
        if ( empty( $api_key ) || ! preg_match( '/^sk-[a-zA-Z0-9]{32,}$/', $api_key ) ) {
            return false;
        }

        // Prepare the request arguments for a minimal API call
        $args = array(
            'method'  => 'POST',
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'model'       => 'gpt-3.5-turbo',
                'messages'    => array(
                    array(
                        'role'    => 'user',
                        'content' => 'Hello',
                    ),
                ),
                'max_tokens'  => 5,
            ) ),
        );

        // Make the API request
        $response = wp_remote_post( $this->api_endpoint, $args );

        // Check if the request was successful
        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Check if the response contains choices
        return isset( $data['choices'] ) && ! empty( $data['choices'] );
    }
}
