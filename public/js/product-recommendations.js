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
                        
                        resultsContainer.append(`
                            <div class="product-result" data-product='${JSON.stringify(product)}'>
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
        selectedProductData = $(this).data('product');
        
        // Update card content
        $('#selected-product-name').text(selectedProductData.name);
        $('#selected-product-price').html(selectedProductData.price_html);
        $('#selected-product-image').html(`<img src="${selectedProductData.image}" alt="${selectedProductData.name}">`);
        
        // Show the card
        selectedProductCard.show();
        
        // Clear and hide search
        resultsContainer.hide();
        searchInput.val('');
    });

    // Handle add recommendation button click
    $('#add-recommendation-btn').on('click', function(e) {
        e.preventDefault();
        if (!selectedProductData) return;

        const customerId = $(this).data('customer-id');
        const notes = $('#recommendation_notes').val();
        const roomId = $('#recommendation_room').val();

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
                room_id: roomId
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="woocommerce-message">' + response.data.message + '</div>')
                        .insertBefore('.add-recommendation')
                        .delay(3000)
                        .fadeOut();
                    
                    // Create the new recommendation row
                    const newRow = `
                        <tr data-id="${response.data.recommendation.id}">
                            <td class="product-thumbnail">
                                <img src="${response.data.recommendation.product_image}" alt="${response.data.recommendation.product_name}" />
                            </td>
                            <td>${response.data.recommendation.product_name}</td>
                            <td>${response.data.recommendation.date_created}</td>
                            <td>
                                <span class="status-badge status-${response.data.recommendation.status}">
                                    ${response.data.recommendation.status}
                                </span>
                            </td>
                            <td>${response.data.recommendation.notes}</td>
                            <td>
                                <button class="button button-remove-recommendation" data-id="${response.data.recommendation.id}">
                                    ${pr_product_object.texts.remove}
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    // Find the correct section to add the recommendation
                    const roomId = $('#recommendation_room').val();
                    if (roomId) {
                        // Add to specific room section
                        const roomName = response.data.recommendation.room_name;
                        console.log('Room Name:', roomName); // Debug log
                        
                        // Look for exact room name match
                        let roomSection = $('.existing-recommendations h3').filter(function() {
                            return $(this).text().trim() === roomName;
                        });
                        
                        if (roomSection.length) {
                            console.log('Found existing room section'); // Debug log
                            // Room section exists, add to its table
                            const $tbody = roomSection.next('table').find('tbody');
                            // Remove "no items" row if it exists
                            $tbody.find('.woocommerce-no-items').closest('tr').remove();
                            $tbody.prepend(newRow);
                        } else {
                            console.log('Creating new room section'); // Debug log
                            // Create new room section if it doesn't exist
                            $('.existing-recommendations').append(`
                                <h3 class="title is-4 mt-6">${roomName}</h3>
                                <table class="woocommerce-table shop_table recommendations-table">
                                    <thead>
                                        <tr>
                                            <th>${pr_product_object.texts.image}</th>
                                            <th>${pr_product_object.texts.product}</th>
                                            <th>${pr_product_object.texts.date_added}</th>
                                            <th>${pr_product_object.texts.status}</th>
                                            <th>${pr_product_object.texts.notes}</th>
                                            <th>${pr_product_object.texts.actions}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${newRow}
                                    </tbody>
                                </table>
                            `);
                        }
                    } else {
                        // Add to core recommendations
                        let generalSection = $('.existing-recommendations h3:contains("Core Recommendations")');
                        if (generalSection.length) {
                            const $tbody = generalSection.next('table').find('tbody');
                            // Remove "no items" row if it exists
                            $tbody.find('.woocommerce-no-items').closest('tr').remove();
                            $tbody.prepend(newRow);
                        } else {
                            // Create core recommendations section if it doesn't exist
                            $('.existing-recommendations').prepend(`
                                <h3 class="title is-4">Core Recommendations</h3>
                                <table class="woocommerce-table shop_table recommendations-table">
                                    <thead>
                                        <tr>
                                            <th>${pr_product_object.texts.image}</th>
                                            <th>${pr_product_object.texts.product}</th>
                                            <th>${pr_product_object.texts.date_added}</th>
                                            <th>${pr_product_object.texts.status}</th>
                                            <th>${pr_product_object.texts.notes}</th>
                                            <th>${pr_product_object.texts.actions}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${newRow}
                                    </tbody>
                                </table>
                            `);
                        }
                    }
                    
                    // Clear the form
                    $('#selected-product-card').hide();
                    $('#product_search').val('');
                    $('#recommendation_notes').val('');
                    selectedProductData = null;
                } else {
                    // Show error
                    $('<div class="woocommerce-error">' + response.data + '</div>')
                        .insertBefore('.add-recommendation');
                }
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