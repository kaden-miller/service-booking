<?php
/*
Plugin Name: Service Booking
Description: A simple plugin for booking services.
Version: 1.0
Author: KMWD
*/

require_once 'calendar_invite.php';
include_once(plugin_dir_path(__FILE__) . 'content/options-page.php');
require_once(plugin_dir_path(__FILE__) . 'content/page-shortcode.php');
require_once(plugin_dir_path(__FILE__) . 'content/stripe-content.php');
// Include other PHP files from the plugin folder
// include_once(plugin_dir_path(__FILE__) . 'path/to/your/other-file.php');
// Require other PHP files from the plugin folder
// require_once(plugin_dir_path(__FILE__) . 'path/to/your/other-file.php');

 
function service_booking_enqueue_admin_scripts($hook) {
    if ('toplevel_page_service-booking' !== $hook) {
        return;
    }

    // Enqueue your admin scripts and styles here
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], '3.0', true);

    wp_enqueue_script('service_booking_admin_script', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('service_booking_admin_script', 'service_booking_admin_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('service_booking_nonce'),
    ));
}

add_action('admin_enqueue_scripts', 'service_booking_enqueue_admin_scripts');


function service_booking_services_field_callback() {
    $services = get_option('service_booking_services', []);
    $services = is_array($services) ? $services : [];
    $days_of_week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    $time_slot_durations = array('15' => '15 minutes', '30' => '30 minutes', '45' => '45 minutes', '60' => '1 hour');
    ?>

<div id="service-booking-services">
        <?php foreach ($services as $index => $service): ?>
            <div class="service-booking-service">
            <div class="row">
            <input type="text" name="service_booking_services[<?php echo $index; ?>][name]" value="<?php echo esc_attr($service['name']); ?>" data-id="<?php echo $service['id'] ?? ''; ?>" placeholder="Service Name" required />
            <input type="hidden" name="service_booking_services[<?php echo $index; ?>][id]" value="<?php echo $service['id'] ?? ''; ?>" />
                    <button class="remove-service">Remove Service</button>
                    </div>
                    <div class="row">
                    <label>
                        Cost:
                    <input type="number" step="0.01" name="service_booking_services[<?php echo $index; ?>][cost]" value="<?php echo esc_attr($service['cost'] ?? ''); ?>" placeholder="Service Cost" />
                    </label>    

                    <label>
                        Time Slot Duration:
                        <select name="service_booking_services[<?php echo $index; ?>][duration]">
                            <?php foreach ($time_slot_durations as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php selected($service['duration'], $value); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    </div>
                    <div class="row fullWidth">
                        <label>
                            Service Description:
                    <textarea name="service_booking_services[<?php echo $index; ?>][description]" rows="3" placeholder="Service Description"><?php echo esc_attr($service['description'] ?? ''); ?></textarea>
                            </label>
                </div>
                    <?php foreach ($days_of_week as $day): ?>
                        <fieldset>
                            <legend><?php echo $day; ?>:</legend>

                            <label>
                                Start Time:
                                <input type="time" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][start_time]" value="<?php echo $service[$day]['start_time'] ?? ''; ?>" />
                            </label>
                            <label>
                                End Time:
                                <input type="time" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][end_time]" value="<?php echo $service[$day]['end_time'] ?? ''; ?>" />
                            </label>
                            <label>
                            <input type="hidden" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][disabled]" value="0" />
                                No availability
                                <input type="checkbox" name="service_booking_services[<?php echo $index; ?>][<?php echo $day; ?>][disabled]" value="1" <?php checked($service[$day]['disabled'] ?? false); ?> />
                            </label>
                        </fieldset>
                    <?php endforeach; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-service">Add Service</button>


        <script type="text/html" id="service-booking-service-template">
            
    <div class="service-booking-service">
    <div class="row">
        <input type="text" name="service_booking_services[{index}][name]" value="" data-id="{id}" placeholder="Service Name" required />
        <input type="hidden" name="service_booking_services[{index}][id]" value="{id}" />
        <button type="button" class="remove-service">Remove Service</button>
                    </div>
                    <div class="row">
                    <label>
                        Cost:
                        <input type="number" step="0.01" name="service_booking_services[{index}][cost]" value="" placeholder="Service Cost" />
                    </label>   


        <label>
            Time Slot Duration:
            <select name="service_booking_services[{index}][duration]">
                <option value="15">15 minutes</option>
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60">1 hour</option>
            </select>
        </label>
                    </div>
        <div class="row fullWidth">
        <textarea name="service_booking_services[{index}][description]" rows="3" placeholder="Service Description"></textarea>
        </div>
        <?php foreach ($days_of_week as $day): ?>
            <fieldset>
                <legend><?php echo $day; ?>:</legend>

                <label>
                    Start Time:
                    <input type="time" name="service_booking_services[{index}][<?php echo $day; ?>][start_time]" />
                </label>
                <label>
                    End Time:
                    <input type="time" name="service_booking_services[{index}][<?php echo $day; ?>][end_time]" />
                </label>
                <label>
                    <input type="hidden" name="service_booking_services[{index}][<?php echo $day; ?>][disabled]" value="0" />
                    No availability
                    <input type="checkbox" name="service_booking_services[{index}][<?php echo $day; ?>][disabled]" value="1" />

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

function service_booking_admin_styles() {
    wp_enqueue_style('service-booking-admin', plugin_dir_url(__FILE__) . 'css/admin-style.css');
}
add_action('admin_enqueue_scripts', 'service_booking_admin_styles');

function service_booking_frontend_styles() {
    wp_enqueue_style('service-booking-frontend', plugin_dir_url(__FILE__) . 'css/style.css');
}
add_action('wp_enqueue_scripts', 'service_booking_frontend_styles');


function service_booking_enqueue_scripts() {
    $blackout_dates = get_option('service_booking_blackout_dates', []);
    $blackout_dates = is_array($blackout_dates) ? $blackout_dates : [];
    $services = get_option('service_booking_services', []);
    $services = is_array($services) ? $services : [];


    wp_enqueue_style('jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('service-booking', plugins_url('js/service-booking.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), '1.0', true);
    wp_localize_script('service-booking', 'service_booking_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('service_booking_nonce'),
        'blackout_dates' => $blackout_dates,
        'services' => $services,
    ));

        // Get Stripe keys from the options
        $stripe_publishable_key = get_option('stripe_publishable_key');
        $stripe_secret_key = get_option('stripe_secret_key');
    
        // Pass the Stripe keys to the JavaScript code
        wp_localize_script('service-booking', 'stripe_keys', array(
            'publishable_key' => $stripe_publishable_key,
            'secret_key' => $stripe_secret_key
        ));
}
add_action('wp_enqueue_scripts', 'service_booking_enqueue_scripts');


function get_available_time_slots($time_slots, $booked_slots, $date) {
    $available_time_slots = array();
    foreach ($time_slots as $time_slot) {
        $booked = false;
        foreach ($booked_slots as $booked_slot) {
            $booked_time_slot = substr($booked_slot['time_slot'], 0, 5); // Extract the H:i part from the booked time slot
            if ($time_slot === $booked_time_slot && $date == $booked_slot['date']) {
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




function get_booked_time_slots($selected_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $query = $wpdb->prepare("SELECT date, time_slot FROM $table_name WHERE date = %s", $selected_date);
    $booked_time_slots = $wpdb->get_results($query, ARRAY_A);
    return $booked_time_slots;
}


function get_service_duration_by_id($services, $service_id) {
    foreach ($services as $id => $service) {
        if ($id == $service_id) {
            return $service['duration'];
        }
    }
    return 0;
}


function service_booking_process($session = null) {
    if (!$session) {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
            wp_send_json_error('Invalid nonce', 403);
            die();
        }
    }


    // error_log('[service_booking_process] POST data: ' . print_r($_POST, true));

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    // Use $_POST data if $session is null, otherwise use session data
    $booking_date = !$session ? sanitize_text_field($_POST['selected_date']) : sanitize_text_field($session->metadata->selected_date);
    $booking_time = !$session ? sanitize_text_field($_POST['selected_time_slot']) : sanitize_text_field($session->metadata->selected_time_slot);
    $customer_name = !$session ? sanitize_text_field($_POST['customer_name']) : sanitize_text_field($session->metadata->customer_name);
    $customer_phone = !$session ? sanitize_text_field($_POST['customer_phone']) : sanitize_text_field($session->metadata->customer_phone);
    $customer_email = !$session ? sanitize_text_field($_POST['customer_email']) : sanitize_text_field($session->metadata->customer_email);
    $service_id = !$session ? intval(sanitize_text_field($_POST['selected_service'])) : intval(sanitize_text_field($session->metadata->service_id));
    $services = get_option('service_booking_services', []);
    $service_name = get_service_name_by_id($services, $service_id);
    $service_duration = get_service_duration_by_id($services, $service_id); // Get the service duration


    // Format the date in 'MM-DD-YYYY' format
    $date_object = DateTime::createFromFormat('Y-m-d', $booking_date);
    if (!$date_object) {
        // error_log("Error: Invalid date format for booking_date: $booking_date");
        wp_send_json_error('Invalid date format', 400);
        die();
    }
    $formatted_date = $date_object->format('m-d-Y');

    // Format the time in 'g:ia' format
    $time_object = DateTime::createFromFormat('H:i', $booking_time);
    if (!$time_object) {
        // error_log("Error: Invalid time format for booking_time: $booking_time");
        wp_send_json_error('Invalid time format', 400);
        die();
    }
    $formatted_time = $time_object->format('g:ia');

    // Calculate the end time based on the service duration
    $time_object->add(new DateInterval('PT' . $service_duration . 'M'));
    $formatted_time_end = $time_object->format('g:ia');

    $user_id = get_current_user_id() ? get_current_user_id() : 0;

    $recurring_id = !$session ? sanitize_text_field($_POST['recurring_id']) : sanitize_text_field($session->metadata->recurring_id);

    $booking_saved = $wpdb->insert($table_name, array(
        'date' => $booking_date,
        'time_slot' => $booking_time,
        'user_id' => $user_id,
        'service_id' => $service_id,
        'customer_name' => $customer_name, // Save the customer name
        'customer_phone' => $customer_phone, // Save the customer phone number
        'customer_email' => $customer_email, // Save the customer email
        'recurring_id' => $recurring_id // Add the recurring_id to each booking

    ));

    if ($booking_saved) {
        // Calculate the end time based on the service duration
        $service_duration = get_service_duration_by_id(get_option('service_booking_services', []), intval(sanitize_text_field($_POST['selected_service'])));
        $time_object = DateTime::createFromFormat('H:i', $booking_time);
        $time_object->add(new DateInterval('PT' . $service_duration . 'M'));
        $booking_time_end = $time_object->format('H:i');
    
        // Send the admin email
        $email_address = get_option('service_booking_email', get_option('admin_email'));
        $email_subject = "New Service Booking - {$service_name}";
        $email_content = generate_booking_email_content($customer_name, $booking_date, $booking_time, $booking_time_end, $service_name, $customer_phone, $customer_email);
        $email_headers = array('Content-Type: text/html; charset=UTF-8');
    
        wp_mail($email_address, $email_subject, $email_content, $email_headers);
    } else {
        // Redirect to the canceled page
        $canceled_url = home_url('canceled') . "?selected_date=" . urlencode($formatted_date) . "&selected_time_slot=" . urlencode($formatted_time) . "&service_name=" . urlencode($service_name) . "&service_name=" . urlencode($service_name) . "&duration=" . urlencode($service_duration);
        wp_send_json_error(['message' => 'Booking could not be saved.', 'redirect_url' => $canceled_url]);
    }
    

    // Redirect to the success page
    $success_url = home_url('success') . "?selected_date=" . urlencode($formatted_date) . "&selected_time_slot=" . urlencode($formatted_time) . "&service_name=" . urlencode($service_name) . "&duration=" . urlencode($service_duration);
    wp_send_json_success(['message' => 'Booking has been saved.', 'time_slots' => get_booked_time_slots($booking_date, $service_id), 'redirect_url' => $success_url]);
}
add_action('wp_ajax_service_booking_process', 'service_booking_process');
add_action('wp_ajax_nopriv_service_booking_process', 'service_booking_process');

function format_date_time_for_calendar($date, $time) {
    $date_object = DateTime::createFromFormat('Y-m-d', $date);
    $time_object = DateTime::createFromFormat('H:i', $time);

    if ($date_object === false || $time_object === false) {
        return false;
    }

    $combined_object = new DateTime($date_object->format('Y-m-d') . ' ' . $time_object->format('H:i:s'));
    
    // Get the WordPress timezone
    $wp_timezone = get_option('timezone_string');
    if (!$wp_timezone) {
        $wp_timezone = 'UTC';
    }
    
    // Set the timezone for the combined DateTime object
    $tz = new DateTimeZone($wp_timezone);
    $combined_object->setTimezone($tz);

    // Convert the local time to UTC
    $combined_object->setTimezone(new DateTimeZone('UTC'));

    return $combined_object->format('Ymd\THis');
}




function generate_booking_email_content($customer_name, $booking_date, $booking_time, $booking_time_end, $service_name, $customer_phone, $customer_email) {
    // Format the date in 'MM-DD-YYYY' format
    $date_object = DateTime::createFromFormat('Y-m-d', $booking_date);
    $formatted_date = $date_object->format('m-d-Y');

    // Format the time in 'g:ia' format
    $time_object = DateTime::createFromFormat('H:i', $booking_time);
    $formatted_time = $time_object->format('g:ia');

    // Calculate the end time based on the service duration
    $service_duration = get_service_duration_by_id(get_option('service_booking_services', []), intval(sanitize_text_field($_POST['selected_service'])));
    $time_object->add(new DateInterval('PT' . $service_duration . 'M'));
    $formatted_time_end = $time_object->format('g:ia');

    $email_content = "<h3>New Service Booking</h3>";
    $email_content .= "<p><strong>Customer Name:</strong> {$customer_name}</p>";
    $email_content .= "<p><strong>Service:</strong> {$service_name}</p>";
    $email_content .= "<p><strong>Date:</strong> {$formatted_date}</p>";
    $email_content .= "<p><strong>Time:</strong> {$formatted_time} - {$formatted_time_end}</p>";
    $email_content .= "<p><strong>Customer Phone:</strong> {$customer_phone}</p>";
    $email_content .= "<p><strong>Customer Email:</strong> {$customer_email}</p>";

    // Add to Calendar button
    $event_start = format_date_time_for_calendar($booking_date, $booking_time);
    $event_end = format_date_time_for_calendar($booking_date, $booking_time_end);
    $event_title = urlencode("Service Booking: {$service_name}");
    $event_location = urlencode("Your business location");
    $calendar_url = "https://www.google.com/calendar/render?action=TEMPLATE&text={$event_title}&dates={$event_start}/{$event_end}&details=&location={$event_location}&sf=true&output=xml";
    $email_content .= "<p><a href=\"{$calendar_url}\" target=\"_blank\" style=\"background-color: #1a73e8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;\">Add to Calendar</a></p>";

    return $email_content;
}



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
        $booked_time_slots = get_booked_time_slots($booking_date);
        $available_time_slots = get_available_time_slots($service_time_slots, $booked_time_slots, $booking_date);

        // Debugging logs
        // error_log('Service Time Slots: ' . print_r($service_time_slots, true));
        // error_log('Booked Time Slots: ' . print_r($booked_time_slots, true));
        // error_log('Available Time Slots: ' . print_r($available_time_slots, true));
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

