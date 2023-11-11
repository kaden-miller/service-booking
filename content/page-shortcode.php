<?php
function service_booking_calendar_shortcode($atts) {
    // Enqueue any necessary scripts or styles for the calendar
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css');
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], '3.0', true);
    wp_enqueue_script('moment', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js');


    ob_start(); // Start output buffering to capture the shortcode output

    // Load the services and blackout dates from the options
    $raw_services = get_option('service_booking_services', []);
    $services = [];
    


    foreach ($raw_services as $service_id => $service) {
        $services[$service_id] = [
            'service_name' => isset($service['name']) ? $service['name'] : '',
            'duration' => isset($service['duration']) ? $service['duration'] : '',
            'start_time' => isset($service['start_time']) ? $service['start_time'] : '',
            'end_time' => isset($service['end_time']) ? $service['end_time'] : '',
            'time_slot' => isset($service['time_slot']) ? $service['time_slot'] : '',
            'cost' => isset($service['cost']) ? $service['cost'] : 0, // Include the cost in the $services array
            'description' => isset($service['description']) ? $service['description'] : '', // Add this line
        ];
    }
    $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
    
    
    $blackout_dates = get_option('service_booking_blackout_dates', []);
    $blackout_dates = is_array($blackout_dates) ? $blackout_dates : [];
    // Render the calendar and time slots
    ?>
<div id="bookingWrapper">
    <div id="step-0">
    <div class="bookingCol serviceCol">
        <div class="bookingSubRow selector">
        <select id="service-selector" data-initial-service-id="<?php echo $service_id; ?>">
            <?php foreach ($services as $service_id => $service): ?>
                <option value="<?php echo $service_id; ?>" data-name="<?php echo $service['service_name']; ?>" data-cost="<?php echo $service['cost']; ?>" data-duration="<?php echo $service['duration']; ?>" data-description="<?php echo htmlspecialchars($service['description']); ?>"><?php echo $service['service_name']; ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        
        <div class="bookingSubRow title">
            <h6 id="service-name"></h6>
            <p id="service-description"></p>
        </div>
        <div class="bookingSubRow details">
            <p id="service-cost"></p>
            <p id="service-duration"></p>
        </div>

    </div>
    <div class="dividerBar"></div>
    </div>
    <div id="step-1">
    <div class="bookingCol col66">
        <div id="service-booking-calendar"></div>
    </div>
    <div class="dividerBar"></div>
    <div class="bookingCol col33">
        <div id="selected-date-display"></div>
        <div id="available-time-slots"></div>
        <button id="continue-to-step-2" type="button">Continue</button>
    </div>
</div>

<div id="step-2" style="display:none;">
            <div class="bookingCol col25">
                <div id="selected-details">
                    <div id="selected-details-date"></div>
                    <div id="selected-details-timeslot"></div>
                    <button id="go-back-to-step-1">Go Back</button>
                </div>
            </div>
            <div class="bookingCol col75">
<form id="booking-form">
    <input type="hidden" id="selected-date" name="selected_date" value="">
    <input type="hidden" id="selected-service" name="selected_service" value="">
    <input type="hidden" id="selected-time-slot" name="selected_time_slot" value="">
    <!-- <label for="name">Name:</label> -->
    <input type="text" id="customer-name" name="name" placeholder="name" required>
        <!-- <label for="email">Email:</label> -->
        <input type="email" id="customer-email" name="email" placeholder="email" required>
    <!-- <label for="phone">Phone:</label> -->
    <input type="tel" id="customer-phone" name="phone" pattern="\d{3}-\d{3}-\d{4}" placeholder="phone" required>
    <div id="checkout-form" style="display:none;">
        <label for="card-element"></label>
        <div id="card-element"><!-- Stripe Card Element will be inserted here --></div>
        <div id="card-errors" role="alert"></div>
    </div>

    <input type="submit" value="Book Service">

</form>
            </div>

</div>
    </div>

    <?php

    return ob_get_clean(); // Return the captured output
}
add_shortcode('service_booking_calendar', 'service_booking_calendar_shortcode');