<?php

namespace Parsamirzaie\SheetsyncForWoocommerce\Admin;

class Sheetsync_For_Woocommerce_Admin_Api_Routes {
	public function register_rest_api_routes() {
		register_rest_route(
			'sheetsync/v1',
			'/update-options',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sheetsync_update_options' ),
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
				'callback'            => array( $this, 'sheetsync_get_options' ),
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
				'callback'            => array( $this, 'sheetsync_upload_json' ),
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
				'callback'            => array( $this, 'sheetsync_get_json_data' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Update plugin options dynamically.
	 */
	public function sheetsync_update_options( \WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$response = array();

		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				$allowed = array( 'spreadsheetId', 'setup_complete' );
				if ( in_array( $key, $allowed, true ) ) {
					// Correct sanitization based on the key
					if ( 'setup_complete' === $key ) {
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
	 */
	public function sheetsync_get_options( \WP_REST_Request $request ) {
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

	public function sheetsync_get_json_data( \WP_REST_Request $request ) {
		$credentials_path = trailingslashit( plugin_dir_path( __DIR__ ) ) . 'credentials/gsheets-credentials.json';

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

	public function sheetsync_upload_json( \WP_REST_Request $request ) {
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
}
