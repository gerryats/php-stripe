# UPDATE
====================

I will be working on updating this library over the next few Months to get it on par with all of Stripes newest features.

Big thanks to @bcessa for starting & sharing this project, as many of you I have used this library countless times.

## I NOW HAVE SOME TIME TO WORK ON THIS PROJECT AND YOU CAN EXPECT UPDATES BY THE END OF THE MONTH STARTING WIL THE MOST BASIC FUNCTIONALITIES AND ENDING WITH STRIPE CONNECT, RELAY ETC (June 2017)
# in the meantime please note that this library may not work

## PHP Stripe Library
====================

Recently I received an invitation for stripe.com, is a fantastic payment gateway/processor service.
Their main focus, as far as I can tell at least, is precisely developers and they have a pretty amazing API and
several languages bindigs.

The PHP binding however was a little bit too complex for me and adding 13 additional files to the project
just to access the service is not ideal, so, I decided to came up with this library.

Usage
-----
The main idea here is to be as simple as possible, basically you just instantiate the library and execute
any of the methods in it, I've implemented all the public API methods available for the moment.

	// Configuration options
	$config['stripe_key_test_public']         = '';
	$config['stripe_key_test_secret']         = '';
	$config['stripe_key_live_public']         = '';
	$config['stripe_key_live_secret']         = '';
	$config['stripe_test_mode']               = TRUE;
	$config['stripe_verify_ssl']              = FALSE;

	// Create the library object
	$stripe = new Stripe( $config );

	// Run the required operations
	echo $stripe->customer_list();

That's it! Have fun.

Codeigniter
-----------
Oh by the way! This library is completely functional as standalone but I developed it as a Codeigniter library,
to use it that way you simply create a config file in: {APPLICATION}/config/stripe.php to store the config array.
Then is just the usual deal that you already know and love!

	$this->load->library( 'stripe' );
	echo $this->stripe->customer_list();
