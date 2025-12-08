/**
 * ATC Inline Text Editor JavaScript
 */

(function($) {
    'use strict';
    
    if (!atcTextEditor.isAdmin) {
        return;
    }
    
    let currentElement = null;
    let originalText = '';
    
    $(document).ready(function() {
        // Make elements editable
        $('.atc-editable').each(function() {
            $(this).addClass('atc-editable');
        });
        
        // Click to edit
        $(document).on('click', '.atc-editable', function(e) {
            e.preventDefault();
            currentElement = $(this);
            originalText = $(this).text().trim();
            
            $('#atc-editor-textarea').val(originalText);
            $('#atc-text-editor-modal').fadeIn();
        });
        
        // Close modal
        $('.atc-modal-close, #atc-text-editor-modal').on('click', function(e) {
            if (e.target === this) {
                $('#atc-text-editor-modal').fadeOut();
                resetEditor();
            }
        });
        
        // Save text
        $('#atc-save-text-btn').on('click', function() {
            saveText();
        });
        
        // Cancel
        $('#atc-cancel-edit-btn').on('click', function() {
            $('#atc-text-editor-modal').fadeOut();
            resetEditor();
        });
    });
    
    function saveText() {
        const newText = $('#atc-editor-textarea').val();
        const elementId = currentElement.data('element-id') || currentElement.attr('id') || 'element-' + Date.now();
        const postId = $('body').data('post-id') || 0;
        
        $.ajax({
            url: atcTextEditor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'atc_save_text',
                nonce: atcTextEditor.nonce,
                element_id: elementId,
                text: newText,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    currentElement.text(newText);
                    $('#atc-text-editor-modal').fadeOut();
                    resetEditor();
                } else {
                    alert('Error saving text: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error saving text. Please try again.');
            }
        });
    }
    
    function resetEditor() {
        currentElement = null;
        originalText = '';
        $('#atc-editor-textarea').val('');
    }
    
})(jQuery);

