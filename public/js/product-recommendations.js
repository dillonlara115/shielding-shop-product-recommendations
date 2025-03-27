(function($) {
    'use strict';

    let searchTimeout = null;
    const searchInput = $('#product_search');
    const resultsContainer = $('#product-search-results');
    const selectedProductCard = $('#selected-product-card');
    let selectedProductData = null;

    // Debounce function
    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(searchTimeout);
                func(...args);
            };
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(later, wait);
        };
    }

    // Handle product search
    const performSearch = debounce(function(searchTerm) {
        if (searchTerm.length < 3) {
            resultsContainer.hide();
            return;
        }

        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'search_products',
                nonce: pr_product_object.nonce,
                search: searchTerm
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    resultsContainer.html('');
                    response.data.forEach(function(product) {
                        // Add member exclusive label if product is private
                        const privateLabel = product.is_private ? '<span class="private-product-label">Member Exclusive</span>' : '';
                        const variableClass = product.is_variable ? 'variable-product' : '';
                        
                        resultsContainer.append(`
                            <div class="product-result ${variableClass}" data-product='${JSON.stringify(product)}'>
                                <div class="product-result-image" style="background-image: url('${product.image}');"></div>
                                <div class="product-result-info">
                                    <div class="product-result-name">${product.name} ${privateLabel}</div>
                                    <div class="product-result-price">${product.price_html}</div>
                                </div>
                            </div>
                        `);
                    });
                    resultsContainer.show();
                } else {
                    resultsContainer.hide();
                }
            }
        });
    }, 300);

    // Search input event handler
    searchInput.on('input', function() {
        const searchTerm = $(this).val();
        performSearch(searchTerm);
    });

    // Handle product selection
    resultsContainer.on('click', '.product-result', function() {
        const product = $(this).data('product');
        
        if (product.is_variable) {
            // For variable products, show variant selection modal
            showVariantSelectionModal(product);
        } else {
            // For simple products, proceed as before
            selectedProductData = product;
            
            // Update card content
            $('#selected-product-name').text(selectedProductData.name);
            $('#selected-product-price').html(selectedProductData.price_html);
            $('#selected-product-image').html(`<img src="${selectedProductData.image}" alt="${selectedProductData.name}">`);
            
            // Show the card
            selectedProductCard.show();
        }
        
        // Clear and hide search
        resultsContainer.hide();
        searchInput.val('');
    });

    // Function to show variant selection modal
    function showVariantSelectionModal(product) {
        // Create modal if it doesn't exist
        if ($('#variant-selection-modal').length === 0) {
            $('body').append(`
                <div id="variant-selection-modal" class="modal">
                    <div class="modal-background"></div>
                    <div class="modal-card">
                        <header class="modal-card-head">
                            <p class="modal-card-title">Select Product Variant</p>
                            <button class="delete close-modal" aria-label="close"></button>
                        </header>
                        <div class="modal-card-body">
                            <div class="variant-list"></div>
                        </div>
                        <footer class="modal-card-foot">
                            <button class="button close-modal">Cancel</button>
                        </footer>
                    </div>
                </div>
            `);
            
            // Close modal when clicking close button or background
            $(document).on('click', '.close-modal, .modal-background', function() {
                $('#variant-selection-modal').removeClass('is-active');
            });
            
            // Close modal when pressing ESC key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#variant-selection-modal').hasClass('is-active')) {
                    $('#variant-selection-modal').removeClass('is-active');
                }
            });
        }
        
        // Load variants for this product
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_product_variants',
                nonce: pr_product_object.nonce,
                product_id: product.id
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const variantList = $('#variant-selection-modal .variant-list');
                    variantList.empty();
                    
                    // Add parent product as an option
                    variantList.append(`
                        <div class="variant-item" data-product='${JSON.stringify(product)}'>
                            <div class="variant-image">
                                <img src="${product.image}" alt="${product.name}">
                            </div>
                            <div class="variant-info">
                                <div class="variant-name">${product.name} (Any Variant)</div>
                                <div class="variant-price">${product.price_html}</div>
                            </div>
                        </div>
                    `);
                    
                    // Add separator
                    variantList.append('<hr class="variant-separator">');
                    
                    // Add individual variants
                    response.data.forEach(function(variant) {
                        variantList.append(`
                            <div class="variant-item" data-product='${JSON.stringify(variant)}'>
                                <div class="variant-image">
                                    <img src="${variant.image}" alt="${variant.name}">
                                </div>
                                <div class="variant-info">
                                    <div class="variant-name">${variant.name}</div>
                                    <div class="variant-price">${variant.price_html}</div>
                                    <div class="variant-attributes">${variant.attribute_summary}</div>
                                </div>
                            </div>
                        `);
                    });
                    
                    // Show modal
                    $('#variant-selection-modal').addClass('is-active');
                } else {
                    // If no variants found, just use the parent product
                    selectedProductData = product;
                    
                    // Update card content
                    $('#selected-product-name').text(selectedProductData.name);
                    $('#selected-product-price').html(selectedProductData.price_html);
                    $('#selected-product-image').html(`<img src="${selectedProductData.image}" alt="${selectedProductData.name}">`);
                    
                    // Show the card
                    selectedProductCard.show();
                }
            }
        });
    }

    // Handle variant selection
    $(document).on('click', '.variant-item', function() {
        selectedProductData = $(this).data('product');
        
        // Update card content
        $('#selected-product-name').text(selectedProductData.name);
        $('#selected-product-price').html(selectedProductData.price_html);
        $('#selected-product-image').html(`<img src="${selectedProductData.image}" alt="${selectedProductData.name}">`);
        
        // Show the card
        selectedProductCard.show();
        
        // Close modal
        $('#variant-selection-modal').removeClass('is-active');
    });

    // Handle add recommendation button click
    $('#add-recommendation-btn').on('click', function(e) {
        e.preventDefault();
        if (!selectedProductData) return;

        const customerId = $(this).data('customer-id');
        const notes = $('#recommendation_notes').val();
        const roomId = $('#recommendation_room').val();
        const quantity = parseInt($('#recommendation_quantity').val(), 10) || 1;

        // Show loading state
        $(this).addClass('is-loading').prop('disabled', true);
        
        // AJAX call to save recommendation
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_recommendation',
                nonce: pr_product_object.nonce,
                customer_id: customerId,
                product_id: selectedProductData.id,
                notes: notes,
                room_id: roomId,
                quantity: quantity
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="woocommerce-message">' + response.data.message + '</div>')
                        .insertBefore('.add-recommendation')
                        .delay(3000)
                        .fadeOut();
                    
                    // Reset form
                    $('#recommendation_notes').val('');
                    $('#recommendation_quantity').val('1');
                    selectedProductCard.hide();
                    selectedProductData = null;
                    
                    // Reload page to show new recommendation
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message with more details if available
                    const errorMsg = response.data || 'An error occurred while adding the recommendation';
                    $('<div class="woocommerce-error">' + errorMsg + '</div>')
                        .insertBefore('.add-recommendation');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                $('<div class="woocommerce-error">Server error: ' + error + '</div>')
                    .insertBefore('.add-recommendation');
            },
            complete: function() {
                // Reset button state
                $('#add-recommendation-btn').removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    // Handle remove recommendation button click
    $(document).on('click', '.button-remove-recommendation', function(e) {
        e.preventDefault();
        
        const recommendationId = $(this).data('id');
        const $row = $(this).closest('tr');
        const $table = $row.closest('table');
        const $section = $table.closest('div');
        
        if (confirm(pr_product_object.texts.confirm_remove)) {
            // Show loading state
            $(this).addClass('is-loading').prop('disabled', true);
            
            // AJAX call to remove recommendation
            $.ajax({
                url: pr_product_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_recommendation',
                    nonce: pr_product_object.nonce,
                    recommendation_id: recommendationId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove just this row
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // If no more recommendations in this table
                            if ($table.find('tbody tr').length === 0) {
                                // Add the "no items" row
                                $table.find('tbody').html(`
                                    <tr>
                                        <td colspan="6" class="woocommerce-no-items">
                                            ${pr_product_object.texts.no_recommendations}
                                        </td>
                                    </tr>
                                `);
                                
                                // If this was in a room section (has a title), remove the whole section
                                const $title = $table.prev('h3');
                                if ($title.length && !$title.text().includes('Core Recommendations')) {
                                    $title.fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                    $table.fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                }
                            }
                        });
                        
                        // Show success message
                        $('<div class="woocommerce-message">Recommendation removed successfully!</div>')
                            .insertBefore('.existing-recommendations')
                            .delay(3000)
                            .fadeOut();
                    } else {
                        // Show error message
                        $('<div class="woocommerce-error">' + response.data + '</div>')
                            .insertBefore('.existing-recommendations');
                    }
                },
                complete: function() {
                    // Reset button state
                    $('.button-remove-recommendation').removeClass('is-loading').prop('disabled', false);
                }
            });
        }
    });

    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#product_search, #product-search-results').length) {
            resultsContainer.hide();
        }
    });

    // Room Management
    $('#add-room-btn').on('click', function(e) {
        e.preventDefault();
        const roomName = prompt('Enter room name:');
        const customerId = $(this).data('customer-id');
        if (!roomName) return;
        
        // Show loading state
        $(this).addClass('is-loading').prop('disabled', true);
        
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_room',
                nonce: pr_product_object.nonce,
                room_name: roomName,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    // Add new row to table
                    if ($('.woocommerce-no-items').length) {
                        // Remove "no items" message if it exists
                        $('.woocommerce-no-items').parent().remove();
                    }
                    
                    $('#rooms-list').append(`
                        <tr data-id="${response.data.id}">
                            <td class="room-name">${response.data.name}</td>
                            <td>${new Date().toLocaleDateString()}</td>
                            <td>
                                <button class="button button-edit-room" data-id="${response.data.id}">Edit</button>
                                <button class="button button-remove-room" data-id="${response.data.id}">Delete</button>
                            </td>
                        </tr>
                    `);
                    
                    // Show success message
                    $('<div class="woocommerce-message">Room added successfully!</div>')
                        .insertBefore('.rooms-management')
                        .delay(3000)
                        .fadeOut();
                    
                    // Refresh the rooms dropdown in the add recommendation form
                    refreshRoomsDropdown(customerId);
                } else {
                    // Show error message
                    $('<div class="woocommerce-error">' + response.data + '</div>')
                        .insertBefore('.rooms-management');
                }
            },
            complete: function() {
                // Reset button state
                $('#add-room-btn').removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    // Edit room
    $(document).on('click', '.button-edit-room', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        const roomId = row.data('id');
        const currentName = row.find('.room-name').text();
        const newName = prompt('Enter new room name:', currentName);
        
        if (!newName || newName === currentName) return;
        
        // Show loading state
        $(this).addClass('is-loading').prop('disabled', true);
        
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'edit_room',
                nonce: pr_product_object.nonce,
                room_id: roomId,
                room_name: newName
            },
            success: function(response) {
                if (response.success) {
                    row.find('.room-name').text(newName);
                    
                    // Show success message
                    $('<div class="woocommerce-message">Room updated successfully!</div>')
                        .insertBefore('.rooms-management')
                        .delay(3000)
                        .fadeOut();
                } else {
                    // Show error message
                    $('<div class="woocommerce-error">' + response.data + '</div>')
                        .insertBefore('.rooms-management');
                }
            },
            complete: function() {
                // Reset button state
                $('.button-edit-room').removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    // Delete room
    $(document).on('click', '.button-remove-room', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        const roomId = row.data('id');
        
        if (!confirm('Are you sure you want to delete this room?')) return;
        
        // Show loading state
        $(this).addClass('is-loading').prop('disabled', true);
        
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_room',
                nonce: pr_product_object.nonce,
                room_id: roomId
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // If no more rooms, show empty message
                        if ($('#rooms-list tr').length === 0) {
                            $('#rooms-list').html(`
                                <tr>
                                    <td colspan="3" class="woocommerce-no-items">No rooms found</td>
                                </tr>
                            `);
                        }
                    });
                    
                    // Show success message
                    $('<div class="woocommerce-message">Room deleted successfully!</div>')
                        .insertBefore('.rooms-management')
                        .delay(3000)
                        .fadeOut();
                } else {
                    // Show error message
                    $('<div class="woocommerce-error">' + response.data + '</div>')
                        .insertBefore('.rooms-management');
                }
            },
            complete: function() {
                // Reset button state
                $('.button-remove-room').removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    // Add this function to refresh the rooms dropdown
    function refreshRoomsDropdown(customerId) {
        $.ajax({
            url: pr_product_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_customer_rooms',
                nonce: pr_product_object.nonce,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#recommendation_room');
                    $select.empty();
                    $select.append('<option value="">' + pr_product_object.texts.general_recommendations + '</option>');
                    
                    response.data.forEach(function(room) {
                        $select.append(`<option value="${room.id}">${room.name}</option>`);
                    });
                }
            }
        });
    }

})(jQuery); 