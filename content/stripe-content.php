<?php


function service_booking_create_stripe_checkout_session() {
    check_ajax_referer('service_booking_nonce', 'security');

    $stripe_publishable_key = get_option('stripe_publishable_key');
    $stripe_secret_key = get_option('stripe_secret_key');

    $serviceName = isset($_POST['service_name']) ? sanitize_text_field($_POST['service_name']) : '';
    $serviceID = isset($_POST['service_id']) ? sanitize_text_field($_POST['service_id']) : '';

    $selectedDate = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : '';
    $selectedTime = isset($_POST['selected_time_slot']) ? sanitize_text_field($_POST['selected_time_slot']) : '';

    $customerName = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
    $customerEmail = isset($_POST['customer_email']) ? sanitize_email(urldecode($_POST['customer_email'])) : '';
    $customerPhone = isset($_POST['customer_phone']) ? preg_replace('/[^0-9+]/', '', urldecode($_POST['customer_phone'])) : '';

    // error_log("Service Name: " . $serviceName);
    // error_log("Service ID: " . $serviceID);
    // error_log("Selected Date: " . $selectedDate);
    // error_log("Selected Time: " . $selectedTime);
    // error_log("Customer Name: " . $customerName);
    // error_log("Customer Email: " . $customerEmail);
    // error_log("Customer Phone: " . $customerPhone);
    

    // Convert date format from 'm-d-y' to 'Y-m-d'
    $date_object = DateTime::createFromFormat('m-d-y', $selectedDate);
    $formatted_date = $date_object->format('Y-m-d');

    // Convert time format from 'g:ia' to 'H:i'
    $time_object = DateTime::createFromFormat('g:ia', $selectedTime);
    $formatted_time = $time_object->format('H:i');
    
    $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0;

    if ($cost <= 0) {
        wp_send_json_error(['message' => 'Invalid cost']);
    }

    require_once 'vendor/autoload.php';

    \Stripe\Stripe::setApiKey($stripe_secret_key);

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $serviceName,
                        'description' => 'Date and Time: ' . $selectedDate . ' - ' . $selectedTime,
                    ],
                    'unit_amount' => $cost * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [ // Add metadata to the session
                'selected_date' => $formatted_date,
                'selected_time_slot' => $formatted_time,
                'customer_name' => $customerName,
                'customer_phone' => isset($customerPhone) ? $customerPhone : '',
                'customer_email' => isset($customerEmail) ? $customerEmail : '',
                'selected_service' => isset($serviceName) ? $serviceName : '',
                'service_id' => isset($serviceID) ? $serviceID : '',
            ],
            'mode' => 'payment',
            'success_url' => 'https://kadenm1.sg-host.com/success?session_id={CHECKOUT_SESSION_ID}&selected_date=' . urlencode($selectedDate) . '&selected_time_slot=' . urlencode($selectedTime) . '&service_name=' . urlencode($serviceName) . '&cost=' . urlencode($cost),

            'cancel_url' => 'https://kadenm1.sg-host.com/canceled',
        ]);

        wp_send_json_success(['sessionId' => $checkout_session->id]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_create_stripe_checkout_session', 'service_booking_create_stripe_checkout_session');
add_action('wp_ajax_nopriv_create_stripe_checkout_session', 'service_booking_create_stripe_checkout_session');


function service_booking_stripe_webhook() {
    $stripe_secret_key = get_option('stripe_secret_key');
    $payload = @file_get_contents('php://input');
    $event = null;

    try {
        $event = \Stripe\Event::constructFrom(
            json_decode($payload, true)
        );
    } catch(\UnexpectedValueException $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }

    if ($event->type === 'checkout.session.completed') {
        // Extract the data from the webhook payload
        $session = $event->data->object;

        // Call the service_booking_process() function with the required data
        service_booking_process($session);

        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported event type']);
    }
}
add_action('wp_ajax_stripe_webhook', 'service_booking_stripe_webhook');
add_action('wp_ajax_nopriv_stripe_webhook', 'service_booking_stripe_webhook');
