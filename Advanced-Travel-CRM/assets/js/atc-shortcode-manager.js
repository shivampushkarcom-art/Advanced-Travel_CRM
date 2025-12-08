/**
 * ATC Shortcode Manager JavaScript
 */

(function($) {
    'use strict';
    
    let currentEditor = '';
    let currentShortcode = '';
    
    $(document).ready(function() {
        // Open modal
        $('.atc-insert-shortcode').on('click', function() {
            currentEditor = $(this).data('editor') || 'content';
            $('#atc-shortcode-modal').fadeIn();
        });
        
        // Close modal
        $('.atc-modal-close, #atc-shortcode-modal').on('click', function(e) {
            if (e.target === this) {
                $('#atc-shortcode-modal').fadeOut();
                resetModal();
            }
        });
        
        // Search shortcodes
        $('#atc-shortcode-search, #atc-manager-search').on('keyup', function() {
            const search = $(this).val().toLowerCase();
            $('.atc-shortcode-item, .atc-shortcode-card').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(search) > -1);
            });
        });
        
        // Select shortcode
        $('.atc-shortcode-item, .atc-shortcode-card').on('click', function() {
            currentShortcode = $(this).data('shortcode');
            showShortcodeParams(currentShortcode);
        });
        
        // Insert shortcode
        $('#atc-insert-shortcode-btn').on('click', function() {
            insertShortcode();
        });
        
        // Preview shortcode
        $('#atc-preview-shortcode-btn').on('click', function() {
            previewShortcode();
        });
        
        // Copy shortcode
        $('.atc-copy-shortcode').on('click', function(e) {
            e.stopPropagation();
            const shortcode = '[' + $(this).data('shortcode') + ']';
            copyToClipboard(shortcode);
            $(this).text('Copied!').delay(2000).queue(function() {
                $(this).text('Copy').dequeue();
            });
        });
    });
    
    function showShortcodeParams(shortcodeKey) {
        const shortcodes = atcShortcodeManager.shortcodes;
        const shortcode = shortcodes[shortcodeKey];
        
        if (!shortcode || !shortcode.params) {
            $('#atc-shortcode-params').hide();
            return;
        }
        
        let paramsHtml = '<div id="atc-params-container">';
        $.each(shortcode.params, function(key, param) {
            paramsHtml += '<div class="atc-param-field">';
            paramsHtml += '<label>' + param.label + '</label>';
            
            if (param.type === 'select') {
                paramsHtml += '<select name="' + key + '">';
                $.each(param.options, function(optKey, optValue) {
                    paramsHtml += '<option value="' + optKey + '">' + optValue + '</option>';
                });
                paramsHtml += '</select>';
            } else if (param.type === 'textarea') {
                paramsHtml += '<textarea name="' + key + '">' + (param.default || '') + '</textarea>';
            } else if (param.type === 'checkbox') {
                paramsHtml += '<input type="checkbox" name="' + key + '" value="' + (param.default || 'true') + '" ' + (param.default === 'true' ? 'checked' : '') + '>';
            } else {
                paramsHtml += '<input type="' + (param.type || 'text') + '" name="' + key + '" value="' + (param.default || '') + '">';
            }
            
            paramsHtml += '</div>';
        });
        paramsHtml += '</div>';
        
        $('#atc-params-container').html(paramsHtml);
        $('#atc-shortcode-params').show();
    }
    
    function insertShortcode() {
        let shortcode = '[' + currentShortcode;
        
        $('#atc-params-container input, #atc-params-container select, #atc-params-container textarea').each(function() {
            const name = $(this).attr('name');
            let value = $(this).val();
            
            if ($(this).is(':checkbox') && !$(this).is(':checked')) {
                return;
            }
            
            if (value && value !== $(this).data('default')) {
                shortcode += ' ' + name + '="' + value + '"';
            }
        });
        
        shortcode += ']';
        
        // Insert into editor
        if (typeof tinymce !== 'undefined' && tinymce.get(currentEditor)) {
            tinymce.get(currentEditor).execCommand('mceInsertContent', false, shortcode);
        } else {
            const editor = $('#' + currentEditor);
            const currentVal = editor.val();
            editor.val(currentVal + shortcode);
        }
        
        $('#atc-shortcode-modal').fadeOut();
        resetModal();
    }
    
    function previewShortcode() {
        let shortcode = '[' + currentShortcode;
        
        $('#atc-params-container input, #atc-params-container select, #atc-params-container textarea').each(function() {
            const name = $(this).attr('name');
            let value = $(this).val();
            
            if ($(this).is(':checkbox') && !$(this).is(':checked')) {
                return;
            }
            
            if (value) {
                shortcode += ' ' + name + '="' + value + '"';
            }
        });
        
        shortcode += ']';
        
        $.ajax({
            url: atcShortcodeManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'atc_preview_shortcode',
                nonce: atcShortcodeManager.nonce,
                shortcode: currentShortcode,
                params: getParamsObject()
            },
            success: function(response) {
                if (response.success) {
                    $('#atc-shortcode-preview').html('<strong>Preview:</strong><br>' + response.data.preview);
                }
            }
        });
    }
    
    function getParamsObject() {
        const params = {};
        $('#atc-params-container input, #atc-params-container select, #atc-params-container textarea').each(function() {
            const name = $(this).attr('name');
            let value = $(this).val();
            
            if ($(this).is(':checkbox')) {
                value = $(this).is(':checked') ? 'true' : 'false';
            }
            
            if (value) {
                params[name] = value;
            }
        });
        return params;
    }
    
    function resetModal() {
        currentShortcode = '';
        $('#atc-shortcode-params').hide();
        $('#atc-shortcode-preview').empty();
        $('#atc-shortcode-search').val('');
    }
    
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
})(jQuery);

