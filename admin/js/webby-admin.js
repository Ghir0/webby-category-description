/**
 * JavaScript for admin settings page.
 *
 * @since      0.2
 * @package    Webby_Category_Description
 * @subpackage Webby_Category_Description/admin/js
 * @author     Michael Tamanti
 */

(function( $ ) {
    'use strict';

    $(document).ready(function() {
        // API key validation
        const apiKeyField = $('#webby_openai_api_key');
        const apiKeyContainer = apiKeyField.parent();
        
        // Add status indicator after the API key field
        if (!$('.webby-api-status').length) {
            apiKeyContainer.append('<span class="webby-api-status"></span>');
        }
        
        const statusIndicator = $('.webby-api-status');
        
        // Check API key when it changes
        apiKeyField.on('change', function() {
            const apiKey = $(this).val().trim();
            
            if (!apiKey) {
                statusIndicator.removeClass('valid invalid checking').text('');
                return;
            }
            
            // Show checking status
            statusIndicator.removeClass('valid invalid').addClass('checking').text('Checking...');
            
            // In a real implementation, you would make an AJAX call to validate the API key
            // For this example, we'll just simulate a check
            setTimeout(function() {
                // This is a placeholder - in a real implementation, you would check the API key with OpenAI
                const isValid = apiKey.startsWith('sk-') && apiKey.length > 20;
                
                if (isValid) {
                    statusIndicator.removeClass('checking invalid').addClass('valid').text('Valid');
                } else {
                    statusIndicator.removeClass('checking valid').addClass('invalid').text('Invalid format');
                }
            }, 1000);
        });
        
        // Trigger validation if there's already a value
        if (apiKeyField.val()) {
            apiKeyField.trigger('change');
        }
    });

})( jQuery );
