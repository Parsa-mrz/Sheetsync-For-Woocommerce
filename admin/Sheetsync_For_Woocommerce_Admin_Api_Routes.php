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
	}

	/**
	 * Update plugin options dynamically.
	 */
	public function sheetsync_update_options( \WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$response = array();

		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				// Only allow known fields (prevent arbitrary option injection).
				$allowed = array( 'clientId', 'clientSecret', 'spreadsheetId' );
				if ( in_array( $key, $allowed, true ) ) {
					update_option( "sheetsync_{$key}", sanitize_text_field( $value ) );
					$response[ $key ] = get_option( "sheetsync_{$key}" );
				}
			}
		}

		return array(
			'success' => true,
			'data'    => $response,
		);
	}

	/**
	 * Get all plugin options dynamically.
	 */
	public function sheetsync_get_options( \WP_REST_Request $request ) {
		$allowed_keys = array( 'clientId', 'clientSecret', 'spreadsheetId' );
		$data         = array();

		foreach ( $allowed_keys as $key ) {
			$data[ $key ] = get_option( "sheetsync_{$key}", '' );
		}

		return array(
			'success' => true,
			'data'    => $data,
		);
	}
}
