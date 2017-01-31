<?php
/*
Plugin Name: Easy Digital Downloads - Check Item Version
Plugin URI: https://github.com/norcross/edd-check-item-version
Description: Provides an API endpoint to get the current version of a product.
Version: 0.0.1
Author: Reaktiv Studios
Author URI:  https://reaktivstudios.com
Contributors: norcross
*/

if( ! defined( 'EDD_CHKVR_DIR' ) ) {
	define( 'EDD_CHKVR_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * EDD_Check_Item_Version Class.
 *
 * Renders API returns as a JSON array.
 *
 * @since  0.0.1
 */
class EDD_Check_Item_Version {

	/**
	 * Define our API version.
	 */
	const VERSION = '0.0.1';

	/**
	 * Construct our API.
	 */
	public function __construct() {

		if ( ! function_exists( 'edd_price' ) ) {
			return; // EDD not present
		}

		add_action( 'init',                   array( __class__, 'add_endpoint'  )    );
		add_action( 'template_redirect',      array( $this,     'process_query' ), 1 );
		add_filter( 'query_vars',             array( $this,     'query_vars'    )    );
	}

	/**
	 * Our activation sequence.
	 *
	 * @return void
	 */
	public static function activate() {
		self::add_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * Registers a new rewrite endpoint for accessing the API.
	 *
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'edd-version-check', EP_ALL );
	}

	/**
	 * Registers query vars for API access.
	 *
	 * @param  array $vars  The current array of vars.
	 *
	 * @return array $vars  The updated array of vars.
	 */
	public function query_vars( $vars ) {

		// Add the item_name into the vars array.
		if ( ! isset( $vars['item_name'] ) ) {
			$vars[] = 'item_name';
		}

		// Return the variables.
		return $vars;
	}

	/**
	 * Retrieves the download ID by the name.
	 * @param  string $name  The item name passed in the API call.
	 *
	 * @return integer
	 */
	public function get_item_id( $name = '' ) {

		// Fetch our object based on the name.
		$item   = get_page_by_title( urldecode( $name ), OBJECT, 'download' );

		// Return the ID or false.
		return ! empty( $item->ID ) ? $item->ID : false;
	}

	/**
	 * Listens for the API and then processes the API requests.
	 *
	 * @return void
	 */
	public function process_query() {

		// Call the global.
		global $wp_query;

		// Check for edd-version-check var. Get out if not present.
		if ( ! isset( $wp_query->query_vars['edd-version-check'] ) ) {
			return;
		}

		// Make sure we have an item name.
		if ( ! isset( $wp_query->query_vars['item_name'] ) || empty( $wp_query->query_vars['item_name'] ) ) {

			// Set the return array.
			$return = array(
				'success'       => false,
				'error_code'    => 'NAME_MISSING',
				'message'       => 'The required item name was not provided.'
			);

			// Send the API response.
			$this->output( $return );

			// And bail.
			return false;
		}

		// Check the name.
		$name   = esc_attr( $wp_query->query_vars['item_name'] );

		// Get our download ID from the name.
		if ( false === $item_id = $this->get_item_id( $name ) ) {

			// Set the return array.
			$return = array(
				'success'       => false,
				'error_code'    => 'INVALID_ITEM_NAME',
				'message'       => 'The item name provided does not exist.'
			);

			// Send the API response.
			$this->output( $return );

			// And bail.
			return false;
		}

		// Get our version.
		$vers   = get_post_meta( absint( $item_id ), '_edd_sl_version', true );

		// Check for a version being returned.
		if ( empty( $vers ) ) {

			// Set the return array.
			$return = array(
				'success'       => false,
				'error_code'    => 'NO_VERSION',
				'message'       => 'No version information was found.'
			);

			// Send the API response.
			$this->output( $return );

			// And bail.
			return false;
		}

		// We have a version. Return it.
		$return = array(
			'success'       => true,
			'error_code'    => null,
			'version'       => esc_attr( $vers ),
			'message'       => 'The current vesion for ' . urldecode( $name ) . ' is ' . esc_attr( $vers ) . '.'
		);

		// Send out data to the output function.
		$this->output( $return );
	}

	/**
	 * Output the API request result.
	 *
	 * @param  array $data  The data array to return via JSON.
	 *
	 * @return void
	 */
	public function output( $data ) {

		// Bail with no data.
		if ( empty( $data ) ) {
			return;
		}

		// Set our content headers.
		header( 'HTTP/1.1 200' );
		header( 'Content-type: application/json; charset=utf-8' );

		// Echo out the JSON encoded data.
		echo json_encode( $data );

		// And die.
		edd_die();
	}

	// End class.
}

register_activation_hook( __FILE__, array( 'EDD_Check_Item_Version', 'activate' ) );

new EDD_Check_Item_Version();