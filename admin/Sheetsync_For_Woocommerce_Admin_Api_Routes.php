<?php

namespace Parsamirzaie\SheetsyncForWoocommerce\Admin;

/**
 * Class Sheetsync_For_Woocommerce_Admin_Api_Routes.
 *
 * Registers and handles custom REST API routes for the Sheetsync for WooCommerce plugin.
 * This class manages options updates, retrieval, and credentials file uploads.
 */
class Sheetsync_For_Woocommerce_Admin_Api_Routes {
	/**
	 * Registers all custom REST API routes for the plugin.
	 *
	 * This method hooks into the `rest_api_init` action to define the
	 * following endpoints:
	 * - `/update-options` (POST): To dynamically update plugin options.
	 * - `/get-options` (GET): To retrieve all plugin options.
	 * - `/upload-credentials` (POST): To handle the upload of a JSON credentials file.
	 * - `/get-credentials-data` (GET): To retrieve the contents of the credentials file.
	 *
	 * All routes require the user to have 'manage_options' capability.
	 *
	 * @return void
	 */
	public function register_rest_api_routes() {
		register_rest_route(
			'sheetsync/v1',
			'/update-options',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_options' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'sheetsync/v1',
			'/get-options',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_options' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'sheetsync/v1',
			'/upload-credentials',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'upload_json' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'sheetsync/v1',
			'/get-credentials-data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_json_data' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'sheetsync/v1',
			'/sync-from-sheet',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sync_from_sheet_callback' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Syncs product data from a Google Sheet to the WordPress database.
	 *
	 * This method serves as a REST API endpoint to receive product data
	 * from a Google Sheet, triggered by an onEdit event in Google Apps Script.
	 * It expects a JSON payload containing the product ID and the new data.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response A REST response object indicating the outcome of the operation.
	 * - On success: Returns a success message with a 200 status code.
	 * - On failure: Returns an error message with a 400 (if product ID is missing)
	 * or 500 (if product update fails) status code.
	 *
	 * @uses \WP_REST_Request::get_json_params() to get the JSON payload.
	 * @uses \WP_REST_Response to create the API response.
	 * @uses \WP_Error to handle internal errors during product updates.
	 */
	public function sync_from_sheet_callback( \WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( ! isset( $data['product_id'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Product ID is required.',
				),
				400
			);
		}

		$product_id = (int) $data['product_id'];
		$new_data   = $data['product_data'];

		$result = $this->update_product_from_sheet( $product_id, $new_data );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Product updated successfully.',
			),
			200
		);
	}

	/**
	 * Update plugin options dynamically.
	 *
	 * Handles the `POST /sheetsync/v1/update-options` endpoint.
	 * It accepts a JSON body with key-value pairs of options to update.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response The response object with success status and updated data.
	 */
	public function update_options( \WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$response = array();

		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				$allowed = array( 'spreadsheetId', 'setup_complete', 'initial_setup_done' );
				if ( in_array( $key, $allowed, true ) ) {
					if ( 'setup_complete' === $key || 'initial_setup_done' === $key ) {
						update_option( "sheetsync_{$key}", boolval( $value ) );
					} else {
						update_option( "sheetsync_{$key}", sanitize_text_field( $value ) );
					}
					$response[ $key ] = get_option( "sheetsync_{$key}" );
				}
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $response,
			)
		);
	}



	/**
	 * Get all plugin options dynamically.
	 *
	 * Handles the `GET /sheetsync/v1/get-options` endpoint.
	 * Retrieves a set of predefined plugin options and the status of the credentials file.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response The response object with success status and a data array.
	 */
	public function get_options( \WP_REST_Request $request ) {
		$allowed_keys = array( 'spreadsheetId' );
		$data         = array();

		$credentials_path    = plugin_dir_path( __DIR__ ) . 'credentials/gsheets-credentials.json';
		$data['hasJsonFile'] = file_exists( $credentials_path );

		// This is a key change: get the boolean value directly
		$data['setupComplete'] = (bool) get_option( 'sheetsync_setup_complete', false );

		foreach ( $allowed_keys as $key ) {
			$data[ $key ] = get_option( "sheetsync_{$key}", '' );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}

	/**
	 * Retrieves the data from the JSON credentials file.
	 *
	 * Handles the `GET /sheetsync/v1/get-credentials-data` endpoint.
	 * Reads the `gsheets-credentials.json` file and returns its content.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response A WP_REST_Response object containing the data or an error message.
	 */
	public function get_json_data( \WP_REST_Request $request ) {
		$credentials_path = SFW_PLUGIN_DIR . '/credentials/gsheets-credentials.json';

		if ( ! file_exists( $credentials_path ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Credentials file not found.',
				),
				404
			);
		}

		$json_content = file_get_contents( $credentials_path );
		$data         = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid JSON data.',
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Handles the upload of a JSON credentials file.
	 *
	 * Handles the `POST /sheetsync/v1/upload-credentials` endpoint.
	 * It validates the uploaded file type, creates a credentials directory if it doesn't exist,
	 * and moves the uploaded file to its final destination.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response A WP_REST_Response object with the upload status.
	 */
	public function upload_json( \WP_REST_Request $request ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$uploaded_file = $_FILES['jsonFile'];

		if ( empty( $uploaded_file ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'No file uploaded.',
				),
				400
			);
		}

		if ( pathinfo( $uploaded_file['name'], PATHINFO_EXTENSION ) !== 'json' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid file type. Please upload a JSON file.',
				),
				400
			);
		}

		$upload_dir = trailingslashit( plugin_dir_path( __DIR__ ) ) . 'credentials/';
		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		$destination_path = $upload_dir . 'gsheets-credentials.json';

		if ( move_uploaded_file( $uploaded_file['tmp_name'], $destination_path ) ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Credentials file uploaded successfully.',
				),
				200
			);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Failed to move uploaded file.',
				),
				500
			);
		}
	}

	/**
	 * Updates a WooCommerce product with data from Google Sheets.
	 *
	 * @param int   $product_id The ID of the product to update.
	 * @param array $new_data   The array of new product values.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update_product_from_sheet( $product_id, $new_data ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', 'Product not found in WooCommerce.' );
		}

		$column_map = array(
			'ID',
			'Type',
			'SKU',
			'GTIN, UPC, EAN, or ISBN',
			'Name',
			'Published',
			'Is featured?',
			'Visibility in catalog',
			'Short description',
			'Description',
			'Regular price',
			'Sale price',
			'Date sale price starts',
			'Date sale price ends',
			'Tax status',
			'Tax class',
			'In stock?',
			'Stock',
			'Backorders allowed?',
			'Low stock amount',
			'Sold individually?',
			'Weight (kg)',
			'Length (cm)',
			'Width (cm)',
			'Height (cm)',
			'Categories',
			'Tags (comma separated)',
			'Tags (space separated)',
			'Shipping class',
			'Images',
			'Parent',
			'Upsells',
			'Cross-sells',
			'Grouped products',
			'External URL',
			'Button text',
			'Download ID',
			'Download name',
			'Download URL',
			'Download limit',
			'Download expiry days',
			'Attribute name',
			'Attribute value(s)',
			'Is a global attribute?',
			'Attribute visibility',
			'Default attribute',
			'Allow customer reviews?',
			'Purchase note',
			'Import as meta data',
			'Position',
			'Brands',
		);

		$mapped_data = array_combine( $column_map, $new_data );

		if ( isset( $mapped_data['Name'] ) ) {
			$product->set_name( sanitize_text_field( $mapped_data['Name'] ) );
		}
		if ( isset( $mapped_data['Regular price'] ) ) {
			$product->set_regular_price( floatval( $mapped_data['Regular price'] ) );
		}
		if ( isset( $mapped_data['Sale price'] ) ) {
			$product->set_sale_price( floatval( $mapped_data['Sale price'] ) );
		}
		if ( isset( $mapped_data['Stock'] ) ) {
			$product->set_stock_quantity( intval( $mapped_data['Stock'] ) );
			$product->set_manage_stock( true );
		}

		$product->save();

		return true;
	}
}
