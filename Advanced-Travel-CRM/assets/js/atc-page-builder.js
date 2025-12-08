/**
 * ATC Page Builder JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize sortable
        if ($('#atc-sections-container').length) {
            $('#atc-sections-container').sortable({
                handle: '.atc-section-handle',
                placeholder: 'atc-section-placeholder',
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.8,
                update: function(event, ui) {
                    updateSectionIndices();
                    saveSectionOrder();
                }
            });
        }
        
        // Update indices on page load
        updateSectionIndices();
        
        // Add section
        $('.atc-add-section').on('click', function() {
            const sectionType = $(this).data('section');
            addSection(sectionType);
        });
        
        // Remove section
        $(document).on('click', '.atc-remove-section', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this section?')) {
                $(this).closest('.atc-section-wrapper').fadeOut(300, function() {
                    $(this).remove();
                    updateSectionIndices();
                    updateEmptyState();
                    saveLayout();
                    saveLayoutToServer();
                });
            }
        });
        
        // Edit section
        $(document).on('click', '.atc-edit-section', function(e) {
            e.preventDefault();
            const index = $(this).data('section');
            const $wrapper = $(this).closest('.atc-section-wrapper');
            const sectionType = $wrapper.data('section');
            
            // Simple edit: show settings (can be enhanced with modal later)
            const currentSettings = $wrapper.data('settings') || {};
            const newTitle = prompt('Edit Section Title:', $wrapper.find('.atc-section-title').text());
            if (newTitle !== null) {
                $wrapper.find('.atc-section-title').text(newTitle);
                saveLayout();
            }
        });
        
        // Save layout
        $(document).on('click', '.atc-save-layout', function(e) {
            e.preventDefault();
            saveLayoutToServer();
        });
        
        // Preview layout
        $(document).on('click', '.atc-preview-layout', function(e) {
            e.preventDefault();
            const postId = $(this).data('post-id');
            if (postId) {
                window.open('?p=' + postId + '&preview=true', '_blank');
            } else {
                alert('Please select a page first.');
            }
        });
    });
    
    function addSection(sectionType) {
        const sections = atcPageBuilder.sections || {};
        const section = sections[sectionType];
        
        if (!section) {
            console.error('Section not found:', sectionType);
            return;
        }
        
        // Get current index
        const currentIndex = $('#atc-sections-container .atc-section-wrapper').length;
        
        const sectionHtml = `
            <div class="atc-section-wrapper" data-section="${sectionType}" data-index="${currentIndex}">
                <div class="atc-section-header">
                    <span class="atc-section-handle" title="Drag to reorder">☰</span>
                    <span class="atc-section-icon-small">${section.icon || '📦'}</span>
                    <span class="atc-section-title">${section.title || sectionType}</span>
                    <div class="atc-section-actions">
                        <button class="button button-small atc-edit-section" data-section="${currentIndex}" title="Edit Section">⚙️ Edit</button>
                        <button class="button button-small atc-remove-section" data-section="${currentIndex}" title="Remove Section">🗑️ Remove</button>
                    </div>
                </div>
                <div class="atc-section-preview">
                    <div class="atc-shortcode-preview">[${section.shortcode || sectionType}]</div>
                </div>
            </div>
        `;
        
        $('#atc-sections-container').append(sectionHtml);
        updateEmptyState();
        updateSectionIndices();
        saveLayout();
        
        // Re-initialize sortable after adding
        $('#atc-sections-container').sortable('refresh');
    }
    
    function updateSectionIndices() {
        $('#atc-sections-container .atc-section-wrapper').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.atc-edit-section, .atc-remove-section').attr('data-section', index);
        });
    }
    
    function saveLayout() {
        const sections = [];
        $('#atc-sections-container .atc-section-wrapper').each(function() {
            const sectionType = $(this).data('section');
            sections.push({
                type: sectionType,
                settings: {}
            });
        });
        
        const layout = JSON.stringify(sections);
        $('#atc-page-layout').val(layout);
    }
    
    function saveSectionOrder() {
        saveLayout();
        updateSectionIndices();
        saveLayoutToServer();
    }
    
    function saveLayoutToServer() {
        const postId = $('.atc-save-layout').data('post-id');
        if (!postId) {
            console.warn('No post ID found');
            return;
        }
        
        const sections = [];
        $('#atc-sections-container .atc-section-wrapper').each(function() {
            const sectionType = $(this).data('section');
            sections.push({
                type: sectionType,
                settings: $(this).data('settings') || {}
            });
        });
        
        const layout = JSON.stringify(sections);
        
        // Show loading
        const $saveBtn = $('.atc-save-layout');
        const originalText = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('💾 Saving...');
        
        $.ajax({
            url: atcPageBuilder.ajaxUrl,
            type: 'POST',
            data: {
                action: 'atc_save_page_layout',
                nonce: atcPageBuilder.nonce,
                post_id: postId,
                layout: layout
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Layout saved successfully!', 'success');
                } else {
                    showNotice(response.data?.message || 'Failed to save layout', 'error');
                }
            },
            error: function() {
                showNotice('Network error. Please try again.', 'error');
            },
            complete: function() {
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible" style="margin: 20px 0;"><p>' + message + '</p></div>');
        $('.atc-page-builder-wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    function updateEmptyState() {
        const hasSections = $('#atc-sections-container .atc-section-wrapper').length > 0;
        $('#atc-empty-state').toggle(!hasSections);
    }
    
})(jQuery);

