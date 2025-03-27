(function($) {
    'use strict';
    
    $(document).ready(function() {
        initDragAndDrop();
    });
    
    function initDragAndDrop() {
        // Initialize sortable on each recommendations table
        $('.sortable-recommendations').sortable({
            handle: '.drag-handle',
            placeholder: 'ui-state-highlight',
            axis: 'y',
            opacity: 0.7,
            cursor: 'move',
            update: function(event, ui) {
                const roomId = $(this).closest('.recommendations-table').data('room-id');
                const roomName = $(this).closest('.recommendations-table').prev('h3').text();
                updatePositions(roomId, roomName);
            }
        });
    }
    
    function updatePositions(roomId, roomName) {
        const table = $(`.recommendations-table[data-room-id="${roomId}"]`);
        if (!table.length) return;
        
        // Show loading indicator
        const loadingIndicator = $('<div class="loading-indicator">Saving order...</div>');
        table.before(loadingIndicator);
        
        const positions = [];
        table.find('.recommendation-row').each(function(index) {
            positions.push({
                id: $(this).data('id'),
                position: index
            });
        });
        
        // AJAX call to update positions
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_recommendation_positions',
                nonce: pr_product_object.nonce,
                positions: positions,
                room_id: roomId
            },
            success: function(response) {
                loadingIndicator.remove();
                
                if (response.success) {
                    // Show success message
                    const successMessage = $(`<div class="notice notice-success"><p>Order updated for ${roomName}</p></div>`);
                    table.before(successMessage);
                    
                    // Remove success message after 2 seconds
                    setTimeout(function() {
                        successMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 2000);
                } else {
                    console.error('Failed to update positions:', response.data);
                    
                    // Show error message
                    const errorMessage = $(`<div class="notice notice-error"><p>Failed to update order: ${response.data.message}</p></div>`);
                    table.before(errorMessage);
                    
                    // Remove error message after 4 seconds
                    setTimeout(function() {
                        errorMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 4000);
                }
            },
            error: function(xhr, status, error) {
                loadingIndicator.remove();
                console.error('AJAX error:', error);
                
                // Show error message
                const errorMessage = $(`<div class="notice notice-error"><p>Error updating order: ${error}</p></div>`);
                table.before(errorMessage);
                
                // Remove error message after 4 seconds
                setTimeout(function() {
                    errorMessage.fadeOut(function() {
                        $(this).remove();
                    });
                }, 4000);
            }
        });
    }
})(jQuery); 