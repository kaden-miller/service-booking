<?php
require_once('vendor/autoload.php'); // Make sure this path points to the autoload.php file in your vendor directory
$stripe_secret_key = get_option('stripe_secret_key');
\Stripe\Stripe::setApiKey($stripe_secret_key); // Replace 'your_secret_key_here' with your Stripe secret key
