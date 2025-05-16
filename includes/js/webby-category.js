/**
 * JavaScript for category description generation.
 *
 * @since      0.2
 * @package    Webby_Category_Description
 * @subpackage Webby_Category_Description/includes/js
 */

(function( $ ) {
    'use strict';

    $(document).ready(function() {
        // Generate button elements
        const generateButton = $('#webby-generate-button');
        const languageSelect = $('#webby-language-select');
        const spinner = $('#webby-generate-spinner');
        const messageContainer = $('#webby-generate-message');
        
        // Check if API key is configured
        if (webbyCategory.apiKeyConfigured === false) {
            generateButton.attr('disabled', 'disabled').attr('title', webbyCategory.apiKeyMissing);
        }
        
        // Function to handle description generation
        function handleGenerateDescription(button, languageSelect, spinner, messageContainer) {
            // Get the term ID and taxonomy
            const termId = button.data('term-id') || 0;
            const taxonomy = button.data('taxonomy') || 'category';
            
            // Get the category name
            let name = '';
            
            if (termId > 0) {
                // Edit category page
                name = $('#name').val();
            } else {
                // Add category page
                name = $('#tag-name').val();
            }
            
            // Validate name
            if (!name) {
                messageContainer.removeClass('success').addClass('error').text(webbyCategory.error + 'Category name is required.');
                return;
            }
            
            // Get the selected language
            const language = languageSelect.val();
            
            // Show spinner
            spinner.addClass('is-active');
            
            // Clear previous messages
            messageContainer.removeClass('success error').empty();
            
            // Show generating message
            messageContainer.text(webbyCategory.generating);
            
            // Make AJAX request
            $.ajax({
                url: webbyCategory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'webby_generate_description',
                    nonce: webbyCategory.nonce,
                    term_id: termId,
                    name: name,
                    language: language,
                    taxonomy: taxonomy
                },
                success: function(response) {
                    // Hide spinner
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        // Set the description
                        if (termId > 0) {
                            // Edit category page - set in the wp_editor
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('description')) {
                                tinyMCE.get('description').setContent(response.data.description);
                            } else {
                                $('#description').val(response.data.description);
                            }
                        } else {
                            // Add category page - set in the textarea
                            $('#tag-description').val(response.data.description);
                        }
                        
                        // Show success message
                        messageContainer.removeClass('error').addClass('success').text(webbyCategory.success);
                    } else {
                        // Show error message
                        messageContainer.removeClass('success').addClass('error').text(webbyCategory.error + response.data.message);
                    }
                },
                error: function() {
                    // Hide spinner
                    spinner.removeClass('is-active');
                    
                    // Show error message
                    messageContainer.removeClass('success').addClass('error').text(webbyCategory.error + 'AJAX request failed.');
                }
            });
        }
        
        // Handle generate button click
        generateButton.on('click', function() {
            handleGenerateDescription($(this), languageSelect, spinner, messageContainer);
        });
    });

})( jQuery );
