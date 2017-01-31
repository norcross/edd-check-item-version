EDD Check Item Version
======================

Provides an API endpoint to get the current version of a product.

### Example Call

```php
function rkv_check_version( $name = '' ) {

	// Don't fire when saving settings.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	// Data to send in our API request.
	$args   = array(
		'item_name' => urlencode( ITEM_NAME ), // The name of our product in EDD.
	);

	// Call the custom API.
	$response = wp_remote_get( 'http://YOUR-DOMAIN/edd-version-check', array( 'timeout' => 35, 'sslverify' => false, 'body' => $args ) );

	if ( is_wp_error( $response ) ) {
		print_r( $response, true );
	}

	// extract the data
	$data   = json_decode( wp_remote_retrieve_body( $response ) );

	print_r( $data, true );
}
```