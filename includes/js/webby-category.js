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
        
        // Bulk generate button elements
        const bulkGenerateButton = $('#webby-bulk-generate-button');
        const bulkLanguageSelect = $('#webby-bulk-language-select');
        const bulkLengthSelect = $('#webby-bulk-length-select');
        const bulkToneSelect = $('#webby-bulk-tone-select');
        const bulkSpinner = $('#webby-bulk-spinner');
        const bulkProgress = $('#webby-bulk-progress');
        const bulkProgressBar = bulkProgress.find('.progress-bar-fill');
        const bulkProgressText = bulkProgress.find('.progress-text');
        const bulkMessageContainer = $('#webby-bulk-message');
        
        // Check if API key is configured
        if (webbyCategory.apiKeyConfigured === false) {
            generateButton.attr('disabled', 'disabled').attr('title', webbyCategory.apiKeyMissing);
            if (bulkGenerateButton.length) {
                bulkGenerateButton.attr('disabled', 'disabled').attr('title', webbyCategory.apiKeyMissing);
            }
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
            
            // Get the selected options
            const language = languageSelect.val();
            const length = $('#webby-length-select').val();
            const tone = $('#webby-tone-select').val();
            
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
                    taxonomy: taxonomy,
                    length: length,
                    tone: tone
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
        
        // Function to handle bulk description generation
        function handleBulkGeneration() {
            // Get the taxonomy
            const taxonomy = bulkGenerateButton.data('taxonomy') || 'product_cat';
            
            // Get the selected options
            const language = bulkLanguageSelect.val();
            const length = bulkLengthSelect.val();
            const tone = bulkToneSelect.val();
            
            // Show spinner
            bulkSpinner.addClass('is-active');
            
            // Clear previous messages
            bulkMessageContainer.removeClass('success error').empty();
            
            // Show generating message
            bulkMessageContainer.text(webbyCategory.bulkGenerating);
            
            // Disable button during processing
            bulkGenerateButton.attr('disabled', 'disabled');
            
            // Make initial AJAX request to get total categories
            $.ajax({
                url: webbyCategory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'webby_bulk_generate_descriptions',
                    nonce: webbyCategory.nonce,
                    taxonomy: taxonomy,
                    language: language,
                    length: length,
                    tone: tone
                },
                success: function(response) {
                    if (response.success) {
                        const totalCategories = response.data.total;
                        
                        if (totalCategories > 0) {
                            // Show progress bar
                            bulkProgress.show();
                            bulkProgressBar.css('width', '0%');
                            bulkProgressText.text(response.data.message);
                            
                            // Start processing categories one by one
                            processNextCategory(0, totalCategories, taxonomy, language, length, tone);
                        } else {
                            // No categories found
                            bulkSpinner.removeClass('is-active');
                            bulkGenerateButton.removeAttr('disabled');
                            bulkMessageContainer.removeClass('success').addClass('error').text(webbyCategory.error + 'No categories found.');
                        }
                    } else {
                        // Error
                        bulkSpinner.removeClass('is-active');
                        bulkGenerateButton.removeAttr('disabled');
                        bulkMessageContainer.removeClass('success').addClass('error').text(webbyCategory.error + response.data.message);
                    }
                },
                error: function() {
                    // AJAX error
                    bulkSpinner.removeClass('is-active');
                    bulkGenerateButton.removeAttr('disabled');
                    bulkMessageContainer.removeClass('success').addClass('error').text(webbyCategory.error + 'AJAX request failed.');
                }
            });
        }
        
        // Function to process categories one by one
        function processNextCategory(index, total, taxonomy, language, length, tone) {
            // Get all terms
            const terms = $('.wp-list-table tbody tr');
            
            // If we've processed all categories
            if (index >= total) {
                // Hide spinner
                bulkSpinner.removeClass('is-active');
                
                // Enable button
                bulkGenerateButton.removeAttr('disabled');
                
                // Show success message
                bulkMessageContainer.removeClass('error').addClass('success').text(webbyCategory.bulkSuccess);
                
                // Set progress to 100%
                bulkProgressBar.css('width', '100%');
                bulkProgressText.text(webbyCategory.bulkSuccess);
                
                return;
            }
            
            // Update progress
            const progress = Math.round((index / total) * 100);
            bulkProgressBar.css('width', progress + '%');
            
            // Get the term ID from the term row
            let termId = 0;
            if (terms.length > index) {
                const termRow = $(terms[index]);
                termId = termRow.attr('id').replace('tag-', '');
            }
            
            // If no term ID found, skip to next
            if (!termId) {
                processNextCategory(index + 1, total, taxonomy, language, length, tone);
                return;
            }
            
            // Make AJAX request to process this category
            $.ajax({
                url: webbyCategory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'webby_bulk_generate_descriptions',
                    nonce: webbyCategory.nonce,
                    term_id: termId,
                    taxonomy: taxonomy,
                    language: language,
                    length: length,
                    tone: tone
                },
                success: function(response) {
                    // Update progress text
                    if (response.success) {
                        bulkProgressText.text(sprintf(
                            webbyCategory.bulkProcessing,
                            index + 1,
                            total,
                            response.data.name
                        ));
                    } else {
                        bulkProgressText.text(webbyCategory.error + response.data.message);
                    }
                    
                    // Process next category
                    setTimeout(function() {
                        processNextCategory(index + 1, total, taxonomy, language, length, tone);
                    }, 500);
                },
                error: function() {
                    // AJAX error, try next category
                    bulkProgressText.text(webbyCategory.error + 'AJAX request failed for category #' + (index + 1));
                    
                    // Process next category
                    setTimeout(function() {
                        processNextCategory(index + 1, total, taxonomy, language, length, tone);
                    }, 500);
                }
            });
        }
        
        // Simple sprintf implementation for progress text
        function sprintf(format) {
            const args = Array.prototype.slice.call(arguments, 1);
            return format.replace(/%(\d+)\$s/g, function(match, number) {
                return typeof args[number - 1] !== 'undefined' ? args[number - 1] : match;
            });
        }
        
        // Handle generate button click
        generateButton.on('click', function() {
            handleGenerateDescription($(this), languageSelect, spinner, messageContainer);
        });
        
        // Handle bulk generate button click
        if (bulkGenerateButton.length) {
            bulkGenerateButton.on('click', function() {
                handleBulkGeneration();
            });
        }
    });

})( jQuery );
