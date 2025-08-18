<?php
namespace Parsamirzaie\SheetsyncForWoocommerce\Admin;

use Google_Client;
use Google_Service_Sheets;
use Exception;

class Sheetsync_For_Woocommerce_Admin_Sync_Products {

	private $client;
	private $spreadsheet_id;

	public function __construct() {
		// Get the spreadsheet ID from WordPress options.
		$this->spreadsheet_id = get_option( 'sheetsync_spreadsheetId' );

		// Determine the secure path to the credentials file.
		// It's best to allow the user to upload this file via the admin panel
		// or store its path in the database. For this example, we assume it's
		// in a specific location within the plugin.
		$credentials_path = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'gsheets-credentials.json';

		// Check if the credentials file exists.
		if ( ! file_exists( $credentials_path ) ) {
			error_log( 'GSheets Sync Error: Credentials file not found at ' . $credentials_path );
			return;
		}

		try {
			// Initialize the Google Client.
			$this->client = new Google_Client();

			// Set up authentication using the Service Account's JSON key.
			$this->client->setAuthConfig( $credentials_path );

			// Set the necessary scopes (permissions) for the Sheets API.
			$this->client->setScopes( array( Google_Service_Sheets::SPREADSHEETS ) );

		} catch ( Exception $e ) {
			error_log( 'GSheets Sync Error during client setup: ' . $e->getMessage() );
			$this->client = null; // Set to null to indicate failure
		}
	}

	public function sync_to_google_sheet( $product, $data_store ) {
		// Ensure the product is published before syncing.
		if ( $product->get_status() !== 'publish' ) {
			return;
		}

		// Validate that the client and spreadsheet ID are correctly configured.
		if ( ! $this->client || empty( $this->spreadsheet_id ) ) {
			error_log( 'GSheets Sync Error: Google Client or Spreadsheet ID is not configured.' );
			return;
		}

		try {
			// Initialize the Google Sheets Service.
			$service = new Google_Service_Sheets( $this->client );

			// Prepare the product data as a nested array for a single row.
			$product_data = array(
				array(
					(string) $product->get_id(),
					(string) $product->get_sku(),
					(string) $product->get_name(),
					(string) $product->get_price(),
					(string) $product->get_stock_quantity(),
				),
			);

			// Define the range in the Google Sheet where data will be appended.
			$range = 'Sheet1!A:E';

			// Create the request body.
			$body = new \Google_Service_Sheets_ValueRange(
				array(
					'values' => $product_data,
				)
			);

			// Set a parameter for the API call to ensure values are treated as entered by a user.
			$params = array(
				'valueInputOption' => 'USER_ENTERED',
			);

			// Make the API call to append the data to the spreadsheet.
			$result = $service->spreadsheets_values->append( $this->spreadsheet_id, $range, $body, $params );

		} catch ( Exception $e ) {
			// Log any errors that occur during the API call for debugging.
			error_log( 'GSheets Sync Error: ' . $e->getMessage() );
		}
	}
}
