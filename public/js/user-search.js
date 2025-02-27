(function($) {
    'use strict';

    let searchTimeout = null;
    const searchInput = $('#user_search');
    const resultsContainer = $('#user-search-results');
    const selectedUserCard = $('#selected-user-card');
    let selectedUserData = null;

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

    // Handle user search
    const performSearch = debounce(function(searchTerm) {
        if (searchTerm.length < 3) {
            resultsContainer.hide();
            return;
        }

        $.ajax({
            url: pr_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'search_users',
                nonce: pr_ajax_object.nonce,
                search: searchTerm
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    resultsContainer.html('');
                    response.data.forEach(function(user) {
                        resultsContainer.append(`
                            <div class="user-result" data-user='${JSON.stringify(user)}'>
                                ${user.display_name} (${user.user_email})
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

    // Handle user selection
    resultsContainer.on('click', '.user-result', function() {
        selectedUserData = $(this).data('user');
        
        // Update card content
        $('#selected-user-name').text(selectedUserData.display_name);
        $('#selected-user-email').text(selectedUserData.user_email);
        $('#selected-user-id').text(selectedUserData.ID);
        
        // Show the card
        selectedUserCard.show();
        
        // Clear and hide search
        resultsContainer.hide();
        searchInput.val('');
    });

    // Handle add customer button click
    $('#add-customer-btn').on('click', function(e) {
        e.preventDefault();
        if (!selectedUserData) return;

        // Show loading state
        $(this).addClass('is-loading').prop('disabled', true);
        
        // AJAX call to save customer
        $.ajax({
            url: pr_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_customer',
                nonce: pr_ajax_object.nonce,
                user_id: selectedUserData.ID
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="woocommerce-message">' + response.data.message + '</div>')
                        .insertBefore('#selected-user-card')
                        .delay(3000)
                        .fadeOut();
                    
                    // Reset form
                    selectedUserCard.hide();
                    selectedUserData = null;
                    
                    // Redirect to customers list after delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 2000);
                } else {
                    // Show error with more details
                    console.error('Error adding customer:', response.data);
                    $('<div class="woocommerce-error">' + response.data + '</div>')
                        .insertBefore('#selected-user-card');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                console.error('AJAX error:', status, error);
                $('<div class="woocommerce-error">Server error: ' + error + '</div>')
                    .insertBefore('#selected-user-card');
            },
            complete: function() {
                // Reset button state
                $('#add-customer-btn').removeClass('is-loading').prop('disabled', false);
            }
        });
    });

    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#user_search, #user-search-results').length) {
            resultsContainer.hide();
        }
    });

})(jQuery); 