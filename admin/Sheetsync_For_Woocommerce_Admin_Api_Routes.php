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

	public function sheetsync_update_options( \WP_REST_Request $request ) {
		$sheet_id   = sanitize_text_field( $request->get_param( 'sheet_id' ) );
		$sheet_name = sanitize_text_field( $request->get_param( 'sheet_name' ) );

		if ( $sheet_id ) {
			update_option( 'sheetsync_sheet_id', $sheet_id );
		}
		if ( $sheet_name ) {
			update_option( 'sheetsync_sheet_name', $sheet_name );
		}

		return array(
			'success' => true,
			'data'    => array(
				'sheet_id'   => get_option( 'sheetsync_sheet_id' ),
				'sheet_name' => get_option( 'sheetsync_sheet_name' ),
			),
		);
	}

	public function sheetsync_get_options( \WP_REST_Request $request ) {
		return array(
			'success' => true,
			'data'    => array(
				'sheet_id'   => get_option( 'sheetsync_sheet_id' ),
				'sheet_name' => get_option( 'sheetsync_sheet_name' ),
			),
		);
	}
}
