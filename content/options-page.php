<?php
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
        'Services',
        'Services',
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

    add_submenu_page(
        'service-booking', // Parent slug
        'Add New Booking', // Page title
        'Add New Booking', // Menu title
        'manage_options', // Capability
        'service_booking_add_booking', // Menu slug
        'service_booking_add_booking_callback' // Callback function
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
    $current_date = current_time('Y-m-d');
    $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE date >= %s ORDER BY date ASC, time_slot ASC", $current_date));


    ?>
    <div class="wrap bookingTables">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2>Filter by Date</h2>
        <input type="text" id="booking-filter-date" class="booking-filter-date" placeholder="Select date" readonly>
        <button type="button" id="show-all-bookings" class="button button-primary">Show All</button>
        <button type="button" id="show-past-bookings" class="button button-primary">Show Past Bookings</button>

        <div id="original-bookings-container">
        <table class="wp-list-table widefat fixed striped" id="all-bookings-table">
            <thead>
                <tr>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Customer Phone</th>
                    <th scope="col">Customer Email</th>
                    <th scope="col">Service</th>
                    <th scope="col">Booking Date</th>
                    <th scope="col">Booking Time</th>
                    <th scope="col">Cancel Booking</th>

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
                        <td><button type="button" class="cancel-booking-btn" data-booking-id="<?php echo $booking->id; ?>">Cancel Booking</button></td>

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
                    <th scope="col">Cancel Booking</th>

                </tr>
            </tfoot>
        </table>
    </div>



    <h2>Recurring Appointments</h2>
    <div id="recurring-bookings-container">
        <table class="wp-list-table widefat fixed striped" id="recurring-bookings-table">
            <thead>
                <tr>
                    <th scope="col">Customer Name</th>
                    <th scope="col">Customer Phone</th>
                    <th scope="col">Customer Email</th>
                    <th scope="col">Service</th>
                    <th scope="col">Booking Date</th>
                    <th scope="col">Booking Time</th>
                    <th scope="col">Cancel Recurring Booking</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recurring_bookings as $booking): ?>
                    <tr>
                        <td><?php echo esc_html($booking->customer_name); ?></td>
                        <td><?php echo esc_html($booking->customer_phone); ?></td>
                        <td><?php echo esc_html($booking->customer_email); ?></td>
                        <td><?php echo esc_html(get_service_name_by_id($services, $booking->service_id)); ?></td>
                        <?php
                        // Format the date and time
                        $date_object = DateTime::createFromFormat('Y-m-d', $booking->date);
                        $formatted_date = $date_object ? $date_object->format('m-d-Y') : $booking->date;
                        $time_object = DateTime::createFromFormat('H:i:s', $booking->time_slot);
                        $formatted_time = $time_object ? $time_object->format('g:ia') : $booking->time_slot;
                        ?>

                        <td><?php echo esc_html($formatted_date); ?></td>
                        <td><?php echo esc_html($formatted_time); ?></td>
                        <td><button type="button" class="cancel-recurring-booking-btn" data-recurring-id="<?php echo $booking->recurring_id; ?>">Cancel Recurring Booking</button></td>
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
                    <th scope="col">Cancel Recurring Booking</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php
}


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
                <th scope="col">Cancel Booking</th>
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
                        <td><button type="button" class="cancel-booking-btn" data-booking-id="<?php echo $booking->id; ?>">Cancel Booking</button></td>

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
                <th scope="col">Cancel Booking</th>
            </tr>
        </tfoot>
            
    </table>
    </div>
    <?php
    $output = ob_get_clean();
    wp_send_json_success($output);
}

add_action('wp_ajax_fetch_filtered_bookings', 'service_booking_fetch_filtered_bookings');

function service_booking_fetch_past_bookings() {
    // Similar to service_booking_fetch_filtered_bookings
    // but for past bookings

    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
        die();
    }

    // Get services
    $services = get_option('service_booking_services', []);

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $current_date = current_time('Y-m-d');
    $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE date < %s ORDER BY date ASC, time_slot ASC", $current_date));

    if (empty($bookings)) {
        $output = '<p>No bookings are found for your selected date.</p>';
        wp_send_json_success($output);
    }

    ob_start();
    ?>
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
                <th scope="col">Cancel Booking</th>
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
                        <td><button type="button" class="cancel-booking-btn" data-booking-id="<?php echo $booking->id; ?>">Cancel Booking</button></td>

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
                <th scope="col">Cancel Booking</th>
            </tr>
        </tfoot>
            
    </table>
    </div>
    <?php
    $output = ob_get_clean();
    wp_send_json_success($output);
}

add_action('wp_ajax_fetch_past_bookings', 'service_booking_fetch_past_bookings');

function service_booking_cancel_booking() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
        die();
    }

    $bookingId = intval($_POST['booking_id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $result = $wpdb->delete($table_name, array('id' => $bookingId));

    if ($result) {
        wp_send_json_success('Booking cancelled successfully');
    } else {
        wp_send_json_error('Error cancelling booking');
    }
}

add_action('wp_ajax_cancel_booking', 'service_booking_cancel_booking');


function service_booking_options_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check for success or error messages
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
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
        <h1><?php echo esc_html(get_admin_page_title()); ?> Test</h1>
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

        // Check for success or error messages
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
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



function service_booking_add_booking_callback() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get services
    $services = get_option('service_booking_services', []);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form id="add-booking-form" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <!-- Nonce field for security -->
            <?php wp_nonce_field('service_booking_nonce', 'security'); ?>
            <input type="hidden" name="action" value="add_service_booking">
            <!-- Input fields for booking details -->
            <select name="service_id" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr($service['id']); ?>">
                        <?php echo esc_html($service['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Date Selection -->
            <input type="date" name="selected_date" required>

            <!-- Time Slot Selection -->
            <input type="text" name="selected_time_slot" placeholder="Time Slot" required>

            <!-- Customer Details -->
            <input type="text" name="name" placeholder="Customer Name" required>
            <input type="email" name="email" placeholder="Customer Email" required>
            <input type="tel" name="phone" placeholder="Customer Phone" pattern="\d{3}-\d{3}-\d{4}" required>

            <button type="submit">Add Booking</button>
        </form>
    </div>
    <?php
}

function handle_admin_service_booking() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_die('Invalid nonce');
    }

    $service_id = intval(sanitize_text_field($_POST['service_id']));
    $booking_date = sanitize_key($_POST['selected_date']);
    $booking_time = sanitize_text_field($_POST['selected_time_slot']);
    $customer_name = sanitize_text_field($_POST['name']);
    $customer_phone = sanitize_text_field($_POST['phone']);
    $customer_email = sanitize_text_field($_POST['email']);

    service_booking_process([
        'metadata' => (object) [
            'selected_date' => $booking_date,
            'selected_time_slot' => $booking_time,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'service_id' => $service_id,
            'recurring_id' => '', // Set this to the appropriate value if necessary
        ],
    ]);

    // Redirect back to the form page
    wp_redirect(add_query_arg('booking_added', '1', wp_get_referer()));
    exit;
}
add_action('admin_post_add_service_booking', 'handle_admin_service_booking');














function service_booking_add_booking_ajax() {
    // Check and verify the nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'service_booking_nonce')) {
        wp_send_json_error('Invalid security token sent.');
        wp_die();
    }

    // Sanitize and validate the input data
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $service_id = intval(sanitize_text_field($_POST['service_id']));
    $date = sanitize_text_field($_POST['date']);
    $time_slot = sanitize_text_field($_POST['time_slot']);
    $recurring = isset($_POST['recurring']);
    $recurring_id = uniqid();

    // Prepare a mock session data object
    $session = new stdClass();
    $session->metadata = new stdClass();
    $session->metadata->selected_date = $date;
    $session->metadata->selected_time_slot = $time_slot;
    $session->metadata->customer_name = $customer_name;
    $session->metadata->customer_phone = $customer_phone;
    $session->metadata->customer_email = $customer_email;
    $session->metadata->service_id = $service_id;
    $session->metadata->recurring_id = $recurring_id;

    // Call the existing booking function
    service_booking_process($session);

    if ($recurring) {
        // If the booking is recurring, add 3 more bookings 1 week apart
        for ($i = 1; $i <= 3; $i++) {
            $next_date = date('Y-m-d', strtotime("$date +$i week"));
            $session->metadata->selected_date = $next_date;
            service_booking_process($session);
        }
    }

    // Return a successful response
    wp_send_json_success([
        'message' => 'Booking(s) added successfully',
    ]);
}

add_action('wp_ajax_add_booking', 'service_booking_add_booking_ajax');


function service_booking_add_recurring_bookings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';

    // Get all recurring IDs
    $recurring_ids = $wpdb->get_col("SELECT DISTINCT recurring_id FROM $table_name WHERE recurring_id IS NOT NULL");

    // Loop through each recurring ID
    foreach ($recurring_ids as $recurring_id) {
        // Get the latest booking for this recurring ID
        $latest_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE recurring_id = %s ORDER BY date DESC, time_slot DESC LIMIT 1", $recurring_id));

        // If this booking is due to occur in 4 weeks, add a new booking 1 week later
        $four_weeks_from_now = date('Y-m-d', strtotime('+4 weeks'));
        if ($latest_booking->date == $four_weeks_from_now) {
            $next_date = date('Y-m-d', strtotime("$latest_booking->date +1 week"));

            // Prepare a mock session data object
            $session = new stdClass();
            $session->metadata = new stdClass();
            $session->metadata->selected_date = $next_date;
            $session->metadata->selected_time_slot = $latest_booking->time_slot;
            $session->metadata->customer_name = $latest_booking->customer_name;
            $session->metadata->customer_phone = $latest_booking->customer_phone;
            $session->metadata->customer_email = $latest_booking->customer_email;
            $session->metadata->service_id = $latest_booking->service_id;
            $session->metadata->recurring_id = $recurring_id;

            // Call the existing booking function
            service_booking_process($session);
        }
    }
}
add_action('service_booking_daily_task', 'service_booking_add_recurring_bookings');

if (!wp_next_scheduled('service_booking_daily_task')) {
    wp_schedule_event(time(), 'daily', 'service_booking_daily_task');
}


function service_booking_cancel_recurring_ajax() {
    // Check and verify the nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'cancel_recurring_nonce')) {
        wp_send_json_error('Invalid security token sent.');
        wp_die();
    }

    // Sanitize the recurring_id
    $recurring_id = sanitize_text_field($_POST['recurring_id']);

    // Delete all bookings with this recurring_id
    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';
    $wpdb->delete($table_name, ['recurring_id' => $recurring_id]);

    wp_send_json_success(['message' => 'Recurring booking cancelled']);
}
add_action('wp_ajax_cancel_recurring', 'service_booking_cancel_recurring_ajax');



function cancel_recurring() {
    check_ajax_referer('your_nonce', 'security');

    $recurring_id = intval($_POST['recurring_id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'service_booking';

    $wpdb->update(
        $table_name,
        array('status' => 'cancelled'), // Set status to 'cancelled'
        array('recurring_id' => $recurring_id), // Where clause
        array('%s'), // Value type, string
        array('%d') // Where clause type, integer
    );

    wp_send_json_success();
}

add_action('wp_ajax_cancel_recurring', 'cancel_recurring');
