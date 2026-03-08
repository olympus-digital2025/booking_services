/**
 * Booking Services Plugin - Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Geolocation handler
    $(document).on('click', '#cp-get-location', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Getting location...');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    $('#cp-latitude').val(lat);
                    $('#cp-longitude').val(lng);

                    btn.prop('disabled', false).text('Use My Location');
                    showAlert('Location obtained successfully', 'success');

                    // Get nearby services automatically
                    $('#cp-search-services').click();
                },
                function(error) {
                    btn.prop('disabled', false).text('Use My Location');
                    var message = 'Unable to get your location. Please enable location services.';

                    if (error.code === error.PERMISSION_DENIED) {
                        message = 'Location permission denied. Please enable location access.';
                    }

                    showAlert(message, 'warning');
                }
            );
        } else {
            btn.prop('disabled', false).text('Use My Location');
            showAlert('Geolocation is not supported by your browser.', 'warning');
        }
    });

    // Search services handler
    $(document).on('click', '#cp-search-services', function() {
        var latitude = $('#cp-latitude').val();
        var longitude = $('#cp-longitude').val();
        var searchText = $('#cp-service-search').val();

        if (!latitude || !longitude) {
            showAlert('Please enable location services or provide your location', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Searching...');

        $.ajax({
            url: cpAjax.ajaxUrl,
            method: 'GET',
            data: {
                action: 'cp_get_nearby_services',
                latitude: latitude,
                longitude: longitude,
                limit: 20,
                _wpnonce: cpAjax.nonce
            },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Search');

                if (response.success) {
                    renderServices(response.data.services, searchText);
                    showAlert('Found ' + response.data.services.length + ' services', 'info');
                } else {
                    showAlert('Error: ' + response.data.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).text('Search');
                console.error('AJAX Error:', error);
                showAlert('Error searching services. Please try again.', 'danger');
            }
        });
    });

    // Render services in grid
    function renderServices(services, searchText) {
        var servicesList = $('#cp-services-list');
        servicesList.empty();

        if (!services || services.length === 0) {
            servicesList.html('<p style="grid-column: 1/-1; text-align: center; padding: 40px;">No services found near you. Try expanding your search area.</p>');
            return;
        }

        // Filter by search text if provided
        var filteredServices = services;
        if (searchText) {
            filteredServices = services.filter(function(service) {
                return service.service_name.toLowerCase().includes(searchText.toLowerCase());
            });
        }

        if (filteredServices.length === 0) {
            servicesList.html('<p style="grid-column: 1/-1; text-align: center; padding: 40px;">No services match your search.</p>');
            return;
        }

        $.each(filteredServices, function(index, service) {
            var distance = parseFloat(service.distance_km).toFixed(1);
            var rating = service.rating ? parseFloat(service.rating).toFixed(1) : 'New';
            var ratingDisplay = rating === 'New' ? rating : rating + ' ⭐';

            var card = $('<div class="cp-service-card"></div>');
            var content = `
                <div class="cp-service-card-content">
                    <div class="cp-service-card-title">${escapeHtml(service.service_name)}</div>
                    <div class="cp-service-card-worker">Worker: ${escapeHtml(service.user_login)}</div>
                    <div class="cp-service-card-rating">${ratingDisplay}</div>
                    <div class="cp-service-card-distance">${distance} km away</div>
                    <button class="button button-primary cp-book-btn" 
                            data-service-id="${service.service_id}" 
                            data-worker-id="${service.worker_id}"
                            data-service-name="${escapeHtml(service.service_name)}">
                        Book Service
                    </button>
                </div>
            `;
            card.html(content);
            servicesList.append(card);
        });
    }

    // Book service handler
    $(document).on('click', '.cp-book-btn', function(e) {
        e.preventDefault();
        var serviceId = $(this).data('service-id');
        var workerId = $(this).data('worker-id');
        var serviceName = $(this).data('service-name');

        if (!serviceId || !workerId) {
            showAlert('Invalid service information', 'danger');
            return;
        }

        showBookingModal(serviceId, workerId, serviceName);
    });

    // Show booking modal
    function showBookingModal(serviceId, workerId, serviceName) {
        var modal = `
            <div class="cp-booking-modal" id="cp-booking-modal">
                <div class="cp-booking-modal-content">
                    <button class="cp-booking-modal-close" onclick="jQuery('#cp-booking-modal').remove();">&times;</button>
                    <h2>Book: ${escapeHtml(serviceName)}</h2>
                    <form id="cp-booking-form" class="cp-booking-form">
                        <div class="cp-booking-form-group">
                            <label for="scheduled-date">Preferred Date & Time:</label>
                            <input type="datetime-local" id="scheduled-date" name="scheduled_date" required>
                        </div>
                        <div class="cp-booking-form-group">
                            <label for="service-location">Service Location:</label>
                            <input type="text" id="service-location" name="service_location" placeholder="Enter your service address" required>
                        </div>
                        <div class="cp-booking-form-group">
                            <label for="notes">Additional Notes:</label>
                            <textarea id="notes" name="notes" placeholder="Any special requirements or instructions..."></textarea>
                        </div>
                        <input type="hidden" name="service_id" value="${serviceId}">
                        <input type="hidden" name="worker_id" value="${workerId}">
                        <input type="hidden" name="latitude" value="${$('#cp-latitude').val()}">
                        <input type="hidden" name="longitude" value="${$('#cp-longitude').val()}">
                        <input type="hidden" name="action" value="cp_book_service">
                        <input type="hidden" name="_wpnonce" value="${cpAjax.nonce}">
                        <button type="submit" class="button button-primary" style="width: 100%;">Confirm Booking</button>
                    </form>
                </div>
            </div>
        `;

        $('body').append(modal);

        // Set minimum datetime to now
        var now = new Date();
        var minDateTime = now.toISOString().slice(0, 16);
        $('#scheduled-date').attr('min', minDateTime);

        // Handle form submission
        $('#cp-booking-form').on('submit', function(e) {
            e.preventDefault();
            submitBooking($(this));
        });

        // Close modal on background click
        $('#cp-booking-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }

    // Submit booking
    function submitBooking(form) {
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true).text('Processing...');

        var formData = new FormData(form[0]);

        $.ajax({
            url: cpAjax.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Confirm Booking');

                if (response.success) {
                    showAlert('Booking confirmed! ID: ' + response.data.booking_id, 'success');
                    $('#cp-booking-modal').fadeOut(function() {
                        $(this).remove();
                    });
                    // Optionally redirect to bookings page
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('Booking failed: ' + response.data.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).text('Confirm Booking');
                console.error('Error:', error);
                showAlert('Error processing booking. Please try again.', 'danger');
            }
        });
    }

    // Show alert message
    function showAlert(message, type) {
        var alertTypes = {
            'info': 'cp-alert-info',
            'success': 'cp-alert-success',
            'warning': 'cp-alert-warning',
            'danger': 'cp-alert-danger'
        };

        var alertClass = alertTypes[type] || alertTypes['info'];
        var alert = `
            <div class="cp-alert ${alertClass}">
                ${escapeHtml(message)}
            </div>
        `;

        // Insert at top of container or body
        var container = $('.cp-service-browser').length ? $('.cp-service-browser') : $('body');
        var alertEl = $(alert);
        container.prepend(alertEl);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            alertEl.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    // Initialize hidden location fields if they don't exist
    if ($('#cp-latitude').length === 0) {
        $('body').append('<input type="hidden" id="cp-latitude">');
    }
    if ($('#cp-longitude').length === 0) {
        $('body').append('<input type="hidden" id="cp-longitude">');
    }
});
