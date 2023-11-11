<?php
/*
Plugin Name: Service Booking
Description: A simple plugin for booking services.
Version: 1.0
Author: KMWD
*/

function service_booking_options_page() {
    add_menu_page(
        'Bookings',
        'Bookings',
        'manage_options',
        'service-booking',
        'service_booking_my_bookings_page_html',
        'dashicons-calendar-alt'
    );

    add_submenu_page(
        'service-booking',
        'My Bookings',
        'My Bookings',
        'manage_options',
        'service-booking',
        'service_booking_my_bookings_page_html'
    );

    $hook = add_submenu_page(
        'service-booking',
        'Options',
        'Options',
        'manage_options',
        'service-booking-options',
        'service_booking_options_page_html'
    );


    add_submenu_page(
        'service-booking',
        'Settings',
        'Settings',
        'manage_options',
        'service-booking-settings',
        'service_booking_settings_page_html'
    );

    add_action('admin_init', 'service_booking_admin_init');
}

add_action('admin_menu', 'service_booking_options_page');

function service_booking_admin_init() {
    register_setting('service_booking_services_options', 'service_booking_services');
    register_setting('service_booking_blackout_dates_options', 'service_booking_blackout_dates');
    // register_setting('service_booking_email_options', 'service_booking_email');
    register_setting('service_booking_email_options', 'service_booking_email', array('option_group' => 'service-booking-settings'));
    register_setting('service_booking_settings', 'stripe_publishable_key', array('option_group' => 'service-booking-settings'));
    register_setting('service_booking_settings', 'stripe_secret_key', array('option_group' => 'service-booking-settings'));

}



function service_booking_my_bookings_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get services
    $services = get_option('service_booking_services', []);

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $bookings = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY date ASC, time_slot ASC");

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2>Filter by Date</h2>
        <input type="text" id="booking-filter-date" class="booking-filter-date" placeholder="Select date" readonly>
        <div id="filtered-bookings-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Customer Phone</th>
                    <th scope="col">Customer Email</th>
                    <th scope="col">Service</th>
                    <th scope="col">Booking Date</th>
                    <th scope="col">Booking Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo esc_html($booking->customer_name); ?></td>
                        <td><?php echo esc_html($booking->customer_phone); ?></td>
                        <td><?php echo esc_html($booking->customer_email); ?></td>
                        <td><?php echo esc_html(get_service_name_by_id($services, $booking->service_id)); ?></td>
                        <?php
                        // Format the date in 'MM-DD-YYYY' format
                        $date_object = DateTime::createFromFormat('Y-m-d', $booking->date);
                        $formatted_date = $date_object ? $date_object->format('m-d-Y') : $booking->date;

                        // Format the time in 'g:ia' format
                        $time_object = DateTime::createFromFormat('H:i:s', $booking->time_slot);
                        $formatted_time = $time_object ? $time_object->format('g:ia') : $booking->time_slot;

                        ?>

                        <td><?php echo esc_html($formatted_date); ?></td>
                        <td><?php echo esc_html($formatted_time); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Customer Phone</th>
                    <th scope="col">Customer Email</th>
                    <th scope="col">Service</th>
                    <th scope="col">Booking Date</th>
                    <th scope="col">Booking Time</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}



function service_booking_options_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Add settings section and fields
    add_settings_section(
        'service_booking_section_services',
        'Services',
        '',
        'service-booking'
    );

    add_settings_field(
        'service_booking_services',
        'Add Services',
        'service_booking_services_field_callback',
        'service-booking',
        'service_booking_section_services'
    );

    

    // Your HTML form for admin settings will be here
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('service_booking_services_options');
            do_settings_sections('service-booking');
            submit_button('Save Services');
            ?>
        </form>
    </div>
    <?php

    // Your HTML form for blackout dates will be here
    ?>
    <div class="wrap">
        <h2>Blackout Dates</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('service_booking_blackout_dates_options');
            service_booking_blackout_dates_field_callback();
            submit_button('Save Blackout Dates');
            ?>
        </form>
    </div>
    <?php
}

function get_service_name_by_id($services, $service_id) {
    if (isset($services[$service_id])) {
        return $services[$service_id]['name'];
    }
    return 'Unknown';
}


function service_booking_settings_page_html() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
    
        // Add settings section and field for the custom email address
        add_settings_section(
            'service_booking_section_email',
            'Booking Email',
            '',
            'service-booking-settings'
        );
    
        add_settings_field(
            'service_booking_email',
            'Email Address',
            'service_booking_email_field_callback',
            'service-booking-settings',
            'service_booking_section_email'
        );
    
        // Register the setting for the custom email address
        register_setting('service_booking_email_options', 'service_booking_email');

    
        // Your HTML form for the custom email address
        ?>
        <div class="wrap">
            <h2>Booking Notification Email</h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('service_booking_email_options');
                do_settings_sections('service-booking-settings');
                submit_button('Save Email');
                ?>
            </form>
        </div>
        <?php

            // Add a section for Stripe settings
    add_settings_section(
        'service_booking_stripe_settings_section',
        'Stripe Settings',
        'service_booking_stripe_settings_section_callback',
        'service_booking_settings'
    );

    // Add the Stripe Publishable Key field
    add_settings_field(
        'stripe_publishable_key',
        'Stripe Publishable Key',
        'stripe_publishable_key_callback',
        'service_booking_settings',
        'service_booking_stripe_settings_section'
    );
    register_setting('service_booking_settings', 'stripe_publishable_key');

    // Add the Stripe Secret Key field
    add_settings_field(
        'stripe_secret_key',
        'Stripe Secret Key',
        'stripe_secret_key_callback',
        'service_booking_settings',
        'service_booking_stripe_settings_section'
    );
    register_setting('service_booking_settings', 'stripe_secret_key');
    ?>
    <div class="wrap">
        <h1>Service Booking</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('service_booking_settings');
            do_settings_sections('service_booking_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
    
}

function service_booking_email_field_callback() {
    $email = get_option('service_booking_email', '');
    ?>
    <input type="text" name="service_booking_email" value="<?php echo esc_attr($email); ?>" />
    <?php
}

function service_booking_stripe_settings_section_callback() {
    echo 'Enter your Stripe API keys below:';
}

function stripe_publishable_key_callback() {
    $stripe_publishable_key = get_option('stripe_publishable_key');
    echo "<input type='text' name='stripe_publishable_key' value='$stripe_publishable_key' />";
}

function stripe_secret_key_callback() {
    $stripe_secret_key = get_option('stripe_secret_key');
    echo "<input type='text' name='stripe_secret_key' value='$stripe_secret_key' />";
}




function service_booking_enqueue_admin_scripts($hook) {
    if ('toplevel_page_service-booking' !== $hook) {
        return;
    }

    // Enqueue your admin scripts and styles here
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('stripe', 'https://js.stripe.com/v3/');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    
    wp_enqueue_script('service_booking_admin_script', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('service_booking_admin_script', 'service_booking_admin_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('service_booking_nonce'),
    ));
}

add_action('admin_enqueue_scripts', 'service_booking_enqueue_admin_scripts');

function service_booking_fetch_filtered_bookings() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
        die();
    }

    $booking_date = sanitize_text_field($_POST['selected_date']);

    // Get services
    $services = get_option('service_booking_services', []);

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE date = %s ORDER BY date ASC, time_slot ASC", $booking_date));

    if (empty($bookings)) {
        $output = '<p>No bookings are found for your selected date.</p>';
        wp_send_json_success($output);
    }

    ob_start();
    ?>
    <div id="original-bookings-container">
    <table class="wp-list-table widefat fixed striped">
    
        <thead>
            <tr>
                <th scope="col">Customer Name</th>
                <th scope="col">Customer Phone</th>
                <th scope="col">Customer Email</th>
                <th scope="col">Service</th>
                <th scope="col">Booking Date</th>
                <th scope="col">Booking Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>

                <tr>
                    <td><?php echo esc_html($booking->customer_name); ?></td>
                    <td><?php echo esc_html($booking->customer_phone); ?></td>
                    <td><?php echo esc_html($booking->customer_email); ?></td>
                    <td>
                        <?php
                        echo esc_html(get_service_name_by_id($services, $booking->service_id));
                        ?>
                    </td>
                    <?php
                        // Format the date in 'MM-DD-YYYY' format
                        $date_object = DateTime::createFromFormat('Y-m-d', $booking->date);
                        $formatted_date = $date_object ? $date_object->format('m-d-Y') : $booking->date;

                        // Format the time in 'g:ia' format
                        $time_object = DateTime::createFromFormat('H:i:s', $booking->time_slot);
                        $formatted_time = $time_object ? $time_object->format('g:ia') : $booking->time_slot;

                        ?>

                        <td><?php echo esc_html($formatted_date); ?></td>
                        <td><?php echo esc_html($formatted_time); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">Customer Name</th>
                <th scope="col">Customer Phone</th>
                <th scope="col">Customer Email</th>
                <th scope="col">Service</th>
                <th scope="col">Booking Date</th>
                <th scope="col">Booking Time</th>
            </tr>
        </tfoot>
            
    </table>
    </div>
    <?php
    $output = ob_get_clean();
    wp_send_json_success($output);
}

add_action('wp_ajax_fetch_filtered_bookings', 'service_booking_fetch_filtered_bookings');


function service_booking_services_field_callback() {
    $services = get_option('service_booking_services', []);
    $services = is_array($services) ? $services : [];
    $days_of_week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $time_slot_durations = array('15' => '15 minutes', '30' => '30 minutes', '45' => '45 minutes', '60' => '1 hour');
    ?>

<div id="service-booking-services">
        <?php foreach ($services as $index => $service): ?>
            <div class="service-booking-service">
            <input type="text" name="service_booking_services[<?php echo $index; ?>][name]" value="<?php echo esc_attr($service['name']); ?>" data-id="<?php echo $service['id'] ?? ''; ?>" placeholder="Service Name" required />
            <input type="hidden" name="service_booking_services[<?php echo $index; ?>][id]" value="<?php echo $service['id'] ?? ''; ?>" />
                    <button class="remove-service">Remove Service</button>
                    <input type="number" step="0.01" name="service_booking_services[<?php echo $index; ?>][cost]" value="<?php echo esc_attr($service['cost'] ?? ''); ?>" placeholder="Service Cost" />


                    <label>
                        Time Slot Duration:
                        <select name="service_booking_services[<?php echo $index; ?>][duration]">
                            <?php foreach ($time_slot_durations as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php selected($service['duration'], $value); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <?php foreach ($days_of_week as $day): ?>
                        <fieldset>
                            <legend><?php echo $day; ?>:</legend>
                            <label>
                            <input type="hidden" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][disabled]" value="0" />
                            <input type="checkbox" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][disabled]" value="1" <?php checked($service[$day]['disabled'] ?? false); ?> />
                                No availability
                            </label>
                            <label>
                                Start Time:
                                <input type="time" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][start_time]" value="<?php echo $service[$day]['start_time'] ?? ''; ?>" />
                            </label>
                            <label>
                                End Time:
                                <input type="time" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][end_time]" value="<?php echo $service[$day]['end_time'] ?? ''; ?>" />
                            </label>
                        </fieldset>
                    <?php endforeach; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-service">Add Service</button>


        <script type="text/html" id="service-booking-service-template">
    <div class="service-booking-service">
        <input type="text" name="service_booking_services[{index}][name]" value="" data-id="{id}" placeholder="Service Name" required />
        <input type="hidden" name="service_booking_services[{index}][id]" value="{id}" />
        <button type="button" class="remove-service">Remove Service</button>
        <input type="number" step="0.01" name="service_booking_services[{index}][cost]" value="" placeholder="Service Cost" />


        <label>
            Time Slot Duration:
            <select name="service_booking_services[{index}][duration]">
                <option value="15">15 minutes</option>
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60">1 hour</option>
            </select>
        </label>

        <?php foreach ($days_of_week as $day): ?>
            <fieldset>
                <legend><?php echo $day; ?>:</legend>
                <label>
                    <input type="hidden" name="service_booking_services[{index}][<?php echo $day; ?>][disabled]" value="0" />
                    <input type="checkbox" name="service_booking_services[{index}][<?php echo $day; ?>][disabled]" value="1" />
                    No availability
                </label>
                <label>
                    Start Time:
                    <input type="time" name="service_booking_services[{index}][<?php echo $day; ?>][start_time]" />
                </label>
                <label>
                    End Time:
                    <input type="time" name="service_booking_services[{index}][<?php echo $day; ?>][end_time]" />
                </label>
            </fieldset>
        <?php endforeach; ?>

    </div>
</script>

<script>
    jQuery(document).ready(function($) {
        $("#add-service").on("click", function() {
            var index = $("#service-booking-services .service-booking-service").length;
            var newIndex = $("#service-booking-services .service-booking-service").length;
            var newId = newIndex > 0 ? Math.max(...$("#service-booking-services .service-booking-service input[name$='[name]']").map((_, el) => parseInt(el.dataset.id) || 0)) + 1 : 1;
            var template = $("#service-booking-service-template").html().replace(/{index}/g, newIndex).replace(/{id}/g, newId);

            $("#service-booking-services").append(template);
        });

        $("#service-booking-services").on("click", ".remove-service", function() {
            $(this).closest(".service-booking-service").remove();
        });

        // Initialize time fields for existing services
        $("#service-booking-services input[type='checkbox'][name$='[disabled]']").each(function() {
            toggleTimeFieldsAvailability(this);
        });

        // Add event listener for existing and new "No availability" checkboxes
        $("#service-booking-services").on("change", "input[type='checkbox'][name$='[disabled]']", function() {
            toggleTimeFieldsAvailability(this);
        });

         // Add event listener for the "Add Blackout Date" button
        $("#add-blackout-date").on("click", function() {
            var dateInput = $('<input type="date" name="service_booking_blackout_dates[]" />');
            var removeButton = $('<button type="button" class="remove-blackout-date">Remove Date</button>');
            var dateContainer = $('<div class="service-booking-blackout-date"></div>').append(dateInput, removeButton);
            $("#service-booking-blackout-dates").append(dateContainer);
        });

        // Add event listener for the "Remove Date" buttons
        $("#service-booking-blackout-dates").on("click", ".remove-blackout-date", function() {
            $(this).closest(".service-booking-blackout-date").remove();
        });
    });

    function toggleTimeFieldsAvailability(checkbox) {
        var startTimeInput = checkbox.closest('fieldset').querySelector('input[type="time"][name$="[start_time]"]');
        var endTimeInput = checkbox.closest('fieldset').querySelector('input[type="time"][name$="[end_time]"]');

        if (checkbox.checked) {
            startTimeInput.value = '';
            endTimeInput.value = '';
            startTimeInput.disabled = true;
            endTimeInput.disabled = true;
        } else {
            startTimeInput.disabled = false;
            endTimeInput.disabled = false;
        }
    }
</script>
    <?php
}

function service_booking_enqueue_scripts() {
    $blackout_dates = get_option('service_booking_blackout_dates', []);
    $blackout_dates = is_array($blackout_dates) ? $blackout_dates : [];
    $services = get_option('service_booking_services', []);
    $services = is_array($services) ? $services : [];
    $stripe_publishable_key = get_option('stripe_publishable_key');

    wp_enqueue_style('jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('service-booking', plugins_url('service-booking.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), '1.0', true);
    wp_localize_script('service-booking', 'service_booking_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('service_booking_nonce'),
        'blackout_dates' => $blackout_dates,
        'services' => $services, // Add this line
        'stripe_publishable_key' => $stripe_publishable_key,
    ));
}
add_action('wp_enqueue_scripts', 'service_booking_enqueue_scripts');


function get_available_time_slots($time_slots, $booked_slots, $date, $service_id) {
    $available_time_slots = array();
    foreach ($time_slots as $time_slot) {
        $booked = false;
        foreach ($booked_slots as $booked_slot) {
            $booked_time_slot = substr($booked_slot['time_slot'], 0, 5); // Extract the H:i part from the booked time slot
            if ($time_slot === $booked_time_slot && $date == $booked_slot['date'] && (int)$service_id === (int)$booked_slot['service_id']) {
                $booked = true;
                break;
            }
        }
        if (!$booked) {
            $available_time_slots[] = $time_slot;
        }
    }

    return $available_time_slots;
}



function get_booked_time_slots($selected_date, $selected_service) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $query = $wpdb->prepare("SELECT date, time_slot, service_id FROM $table_name WHERE date = %s AND service_id = %s", $selected_date, $selected_service);
    $booked_time_slots = $wpdb->get_results($query, ARRAY_A);
    return $booked_time_slots;
}


function service_booking_process() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
        die();
    }

    error_log('[service_booking_process] POST data: ' . print_r($_POST, true));

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $booking_date = sanitize_text_field($_POST['selected_date']);
    $booking_time = sanitize_text_field($_POST['selected_time_slot']);
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $customer_email = sanitize_text_field($_POST['customer_email']);

    $user_id = get_current_user_id();
    $service_id = intval(sanitize_text_field($_POST['selected_service']));
    $services = get_option('service_booking_services', []);
    $service_name = get_service_name_by_id($services, $service_id);

    // Format the date in 'MM-DD-YYYY' format
    $formatted_date = DateTime::createFromFormat('Y-m-d', $booking_date)->format('m-d-Y');

    // Format the time in 'g:ia' format
    $formatted_time = DateTime::createFromFormat('H:i', $booking_time)->format('g:ia');



    $booking_saved = $wpdb->insert($table_name, array(
        'date' => $booking_date,
        'time_slot' => $booking_time,
        'user_id' => $user_id,
        'service_id' => $service_id,
        'customer_name' => $customer_name, // Save the customer name
        'customer_phone' => $customer_phone, // Save the customer phone number
        'customer_email' => $customer_email // Save the customer email
    ));

    // If the booking is saved successfully, send an email to the custom email address
    if ($booking_saved) {
        // Get the custom email address or use the admin email as a fallback
        $email_address = get_option('service_booking_email', get_option('admin_email'));

        $admin_email = get_option('admin_email');
        $subject = 'New Booking on Your Website';
        $message = "Hello,\n\nA new booking has been made on your website. Here are the details:\n\n";
        $message .= "Service: " . $service_name . "\n"; // Get the service name instead of the ID
        $message .= "Date: " . $formatted_date . "\n"; // Use the formatted date
        $message .= "Time: " . $formatted_time . "\n"; // Use the formatted time
        $message .= "Name: " . $customer_name . "\n";
        $message .= "Phone: " . $customer_phone . "\n\n";
        $message .= "Email: " . $customer_email . "\n\n";
        $message .= "Please log in to your website dashboard to manage the booking.";
    

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Send the email
        wp_mail($email_address, $subject, $message, $headers);
    }
    


    // Send the updated time slots in the response
    wp_send_json_success(['message' => 'Booking has been saved.', 'time_slots' => get_booked_time_slots($booking_date, $service_id)]);
}
add_action('wp_ajax_service_booking_process', 'service_booking_process');
add_action('wp_ajax_nopriv_service_booking_process', 'service_booking_process');



function service_booking_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date date NOT NULL,
        time_slot time NOT NULL,
        user_id bigint(20) NOT NULL,
        service_id mediumint(9) NOT NULL, // Add this line
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'service_booking_install');

function fetch_time_slots() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
        die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $booking_date = sanitize_text_field($_POST['selected_date']);
    $selected_service_id = intval(sanitize_text_field($_POST['selected_service']));

    $selected_date = $_POST['selected_date'];
    $all_services_time_slots = get_all_services_time_slots($selected_date);
    $services = get_option('service_booking_services', []);

    foreach ($all_services_time_slots as $service_id => $time_slots) {
        $all_services_time_slots[$service_id] = [
            'service_id' => $service_id,
            'service_name' => $services[$service_id]['name'],
            'time_slots' => $time_slots,
        ];
    }

    if (isset($services[$selected_service_id])) {
        $selected_service = $services[$selected_service_id];
        $service_time_slots = $all_services_time_slots[$selected_service_id]['time_slots'] ?? [];

        // Fetch booked time slots and filter them out from the available time slots
        $booked_time_slots = get_booked_time_slots($booking_date, $selected_service_id);
        $available_time_slots = get_available_time_slots($service_time_slots, $booked_time_slots, $booking_date, $selected_service_id);

        // Debugging logs
        error_log('Service Time Slots: ' . print_r($service_time_slots, true));
        error_log('Booked Time Slots: ' . print_r($booked_time_slots, true));
        error_log('Available Time Slots: ' . print_r($available_time_slots, true));
    } else {
        $available_time_slots = [];
    }

    // Return the available time slots as well as booked time slots in the response
    wp_send_json_success([
        'available_time_slots' => $available_time_slots,
        'booked_time_slots' => $booked_time_slots,
    ]);
}



add_action('wp_ajax_fetch_time_slots', 'fetch_time_slots');
add_action('wp_ajax_nopriv_fetch_time_slots', 'fetch_time_slots');

function get_all_services_time_slots($selected_date) {
    $services = get_option('service_booking_services', []);
    $time_slots = [];

    // Get the day of the week for the selected date
    $selected_day_of_week = date('l', strtotime($selected_date));

    foreach ($services as $service_id => $service) {
        $service_time_slots = [];

        // Generate time slots only for the selected day of the week
        if (isset($service[$selected_day_of_week]) && !$service[$selected_day_of_week]['disabled']) {
            $start_time = strtotime($service[$selected_day_of_week]['start_time']);
            $end_time = strtotime($service[$selected_day_of_week]['end_time']);
            $duration = $service['duration'] * 60;

            while ($start_time < $end_time) {
                $service_time_slots[] = date('H:i', $start_time);
                $start_time += $duration;
            }
        }

        // Fetch booked time slots and filter them out from the available time slots
        $booked_time_slots = get_booked_time_slots($selected_date, $service_id);
        $available_time_slots = array_filter($service_time_slots, function ($time_slot) use ($booked_time_slots) {
            return !in_array($time_slot, $booked_time_slots);
        });

        $time_slots[$service_id] = $available_time_slots;
    }

    return $time_slots;
}

function service_booking_blackout_dates_field_callback() {
    $blackout_dates = get_option('service_booking_blackout_dates', []);
    $blackout_dates = is_array($blackout_dates) ? $blackout_dates : [];

    ?>
    <div id="service-booking-blackout-dates">
        <?php foreach ($blackout_dates as $index => $date) : ?>
            <div class="service-booking-blackout-date">
                <input type="date" name="service_booking_blackout_dates[]" value="<?php echo esc_attr($date); ?>" />
                <button type="button" class="remove-blackout-date">Remove Date</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-blackout-date">Add Blackout Date</button>
    <?php
}

function service_booking_calendar_shortcode($atts) {
    // Enqueue any necessary scripts or styles for the calendar
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css');
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
        ];
    }

    
    
    $blackout_dates = get_option('service_booking_blackout_dates', []);
    $blackout_dates = is_array($blackout_dates) ? $blackout_dates : [];
    // Render the calendar and time slots
    ?>
<select id="service-selector">
    <?php foreach ($services as $service_id => $service): ?>
        <option value="<?php echo $service_id; ?>" data-cost="<?php echo $service['cost']; ?>"><?php echo $service['service_name']; ?></option>
    <?php endforeach; ?>
</select>
<p id="service-cost"></p> <!-- Display the cost below the service selector -->

    <div id="service-booking-calendar"></div>
    <div id="available-time-slots"></div>


<form id="booking-form">
    <input type="hidden" id="selected-date" name="selected_date" value="">
    <input type="hidden" id="selected-service" name="selected_service" value="">
    <input type="hidden" id="selected-time-slot" name="selected_time_slot" value="">
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required>
    <label for="phone">Phone:</label>
    <input type="text" id="phone" name="phone" required>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <input type="submit" value="Book">
</form>

    <?php

    return ob_get_clean(); // Return the captured output
}
add_shortcode('service_booking_calendar', 'service_booking_calendar_shortcode');

