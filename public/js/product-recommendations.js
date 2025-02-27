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
                        resultsContainer.append(`
                            <div class="product-result" data-product='${JSON.stringify(product)}'>
                                <div class="product-result-image" style="background-image: url('${product.image}');"></div>
                                <div class="product-result-info">
                                    <div class="product-result-name">${product.name}</div>
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
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="woocommerce-message">' + response.data.message + '</div>')
                        .insertBefore('.add-recommendation')
                        .delay(3000)
                        .fadeOut();
                    
                    // Add the new recommendation to the list
                    if ($('#recommendations-list').length) {
                        if ($('.no-items').length) {
                            $('.no-items').remove();
                            $('.existing-recommendations').html(`
                                <h3 class="title is-4">${pr_product_object.texts.current_recommendations}</h3>
                                <table class="woocommerce-table shop_table recommendations-table">
                                    <thead>
                                        <tr>
                                            <th class="product-thumbnail">${pr_product_object.texts.image}</th>
                                            <th>${pr_product_object.texts.product}</th>
                                            <th>${pr_product_object.texts.date_added}</th>
                                            <th>${pr_product_object.texts.status}</th>
                                            <th>${pr_product_object.texts.notes}</th>
                                            <th>${pr_product_object.texts.actions}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recommendations-list"></tbody>
                                </table>
                            `);
                        }
                        
                        $('#recommendations-list').prepend(`
                            <tr data-id="${response.data.recommendation.id}">
                                <td class="product-thumbnail">
                                    <img src="${selectedProductData.image}" alt="${selectedProductData.name}" class="product-thumb" />
                                </td>
                                <td>
                                    <a href="${response.data.recommendation.product_url}" target="_blank">
                                        ${selectedProductData.name}
                                    </a>
                                </td>
                                <td>${response.data.recommendation.date_formatted}</td>
                                <td>
                                    <span class="status-badge status-${response.data.recommendation.status}">
                                        ${response.data.recommendation.status_label}
                                    </span>
                                </td>
                                <td>${notes}</td>
                                <td>
                                    <a href="#" class="button button-remove-recommendation" data-id="${response.data.recommendation.id}">
                                        ${pr_product_object.texts.remove}
                                    </a>
                                </td>
                            </tr>
                        `);
                    }
                    
                    // Reset form
                    selectedProductCard.hide();
                    selectedProductData = null;
                    $('#recommendation_notes').val('');
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
        const row = $(this).closest('tr');
        
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
                        // Remove the row
                        row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // If no more recommendations, show empty message
                            if ($('#recommendations-list tr').length === 0) {
                                $('.recommendations-table').replaceWith(`
                                    <p class="no-items">${pr_product_object.texts.no_recommendations}</p>
                                `);
                            }
                        });
                    } else {
                        // Show error
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

})(jQuery); 