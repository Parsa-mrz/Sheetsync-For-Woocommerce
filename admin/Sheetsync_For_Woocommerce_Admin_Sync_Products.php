<?php
namespace Parsamirzaie\SheetsyncForWoocommerce\Admin;

use Google_Client;
use Google_Service_Sheets;
use Exception;

/**
 * Class Sheetsync_For_Woocommerce_Admin_Sync_Products.
 *
 * This class handles the synchronization of WooCommerce product data with a Google Sheet.
 * It uses the Google Sheets API to read, write, and update product information.
 */
class Sheetsync_For_Woocommerce_Admin_Sync_Products {

	/**
	 * The Google Client instance for API communication.
	 *
	 * @var Google_Client|null
	 */
	private $client;

	/**
	 * The ID of the Google Spreadsheet to sync with.
	 *
	 * @var string|false
	 */
	private $spreadsheet_id;

	/**
	 * Sheetsync_For_Woocommerce_Admin_Sync_Products constructor.
	 *
	 * Initializes the Google Client with authentication credentials. It checks for the
	 * existence of the credentials file and sets up the client with the necessary
	 * scopes for accessing Google Sheets.
	 */
	public function __construct() {
		$this->spreadsheet_id = get_option( 'sheetsync_spreadsheetId' );
		$credentials_path     = SFW_PLUGIN_DIR . '/credentials/gsheets-credentials.json';

		if ( ! file_exists( $credentials_path ) ) {
			error_log( 'GSheets Sync Error: Credentials file not found at ' . $credentials_path );
			return;
		}

		try {
			$this->client = new Google_Client();
			$this->client->setAuthConfig( $credentials_path );
			$this->client->setScopes( array( Google_Service_Sheets::SPREADSHEETS ) );
		} catch ( Exception $e ) {
			error_log( 'GSheets Sync Error during client setup: ' . $e->getMessage() );
			$this->client = null;
		}
	}

	/**
	 * Syncs product data to a Google Sheet.
	 *
	 * This is the main method for syncing. It checks for a valid client and spreadsheet ID,
	 * performs an initial setup (writing headers) if needed, and then either updates an
	 * existing row or appends a new row for the product data.
	 *
	 * @param \WC_Product $product    The product object to sync.
	 * @param object      $data_store The product data store. This parameter is currently not used
	 * but is kept for compatibility with WooCommerce hooks.
	 * @return void
	 */
	public function sync_to_google_sheet( $product, $data_store ) {
		if ( $product->get_status() !== 'publish' ) {
			return;
		}

		if ( ! $this->client || empty( $this->spreadsheet_id ) ) {
			error_log( 'GSheets Sync Error: Google Client or Spreadsheet ID is not configured.' );
			return;
		}

		$initial_setup_done = get_option( 'sheetsync_initial_setup_done', false );
		if ( ! $initial_setup_done ) {
			$this->perform_initial_setup();
		}

		try {
			$service = new Google_Service_Sheets( $this->client );

			$product_data = $this->get_product_data_for_sheet( $product );

			$row_number = $this->get_row_by_product_id( $service, $product->get_id() );

			if ( $row_number ) {
				$range = 'Sheet1!A' . $row_number . ':AZ' . $row_number;
				$body  = new \Google_Service_Sheets_ValueRange(
					array(
						'values' => array( $product_data ),
					)
				);
				$service->spreadsheets_values->update( $this->spreadsheet_id, $range, $body );
			} else {
				$range  = 'Sheet1!A:AZ';
				$body   = new \Google_Service_Sheets_ValueRange(
					array(
						'values' => array( $product_data ),
					)
				);
				$params = array( 'valueInputOption' => 'USER_ENTERED' );
				$service->spreadsheets_values->append( $this->spreadsheet_id, $range, $body, $params );
			}
		} catch ( Exception $e ) {
			error_log( 'GSheets Sync Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Helper function to get product data in the correct format.
	 *
	 * This method maps various properties from a WooCommerce product object to an
	 * array suitable for a Google Sheet row. The array elements are cast to strings
	 * to ensure proper data types for the API call.
	 *
	 * @param \WC_Product $product The product object to extract data from.
	 * @return array An array of product data formatted for a Google Sheet row.
	 */
	private function get_product_data_for_sheet( $product ) {
		$product_id        = $product->get_id();
		$product_type      = $product->get_type();
		$sku               = $product->get_sku();
		$name              = $product->get_name();
		$published         = $product->get_status() === 'publish' ? 1 : 0;
		$is_featured       = $product->is_featured() ? 1 : 0;
		$visibility        = $product->get_catalog_visibility();
		$short_description = $product->get_short_description();
		$description       = $product->get_description();

		$regular_price          = $product->get_regular_price();
		$sale_price             = $product->get_sale_price();
		$date_sale_price_starts = $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->format( 'Y-m-d H:i:s' ) : '';
		$date_sale_price_ends   = $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->format( 'Y-m-d H:i:s' ) : '';

		$tax_status         = $product->get_tax_status();
		$tax_class          = $product->get_tax_class();
		$in_stock           = $product->is_in_stock() ? 1 : 0;
		$stock_quantity     = $product->get_stock_quantity();
		$backorders_allowed = $product->get_backorders();
		$sold_individually  = $product->is_sold_individually() ? 1 : 0;
		$weight             = $product->get_weight();
		$length             = $product->get_length();
		$width              = $product->get_width();
		$height             = $product->get_height();

		$categories        = wc_get_product_category_list( $product_id, ', ', '', '' );
		$tags              = wc_get_product_tag_list( $product_id, ', ', '', '' );
		$shipping_class_id = $product->get_shipping_class_id();
		$shipping_class    = $shipping_class_id ? get_term( $shipping_class_id, 'product_shipping_class' )->name : '';

		$images = implode( ', ', array_map( 'wp_get_attachment_url', $product->get_gallery_image_ids() ) );
		if ( $product->get_image_id() ) {
			$images = wp_get_attachment_url( $product->get_image_id() ) . ( ! empty( $images ) ? ',' . $images : '' );
		}

		$parent_id        = $product->get_parent_id();
		$upsell_ids       = implode( ',', $product->get_upsell_ids() );
		$cross_sell_ids   = implode( ',', $product->get_cross_sell_ids() );
		$grouped_products = implode( ',', $product->get_children() );

		$external_url = $product->get_type() === 'external' ? $product->get_product_url() : '';
		$button_text  = $product->get_type() === 'external' ? $product->get_button_text() : '';

		$reviews_allowed = $product->get_reviews_allowed() ? 1 : 0;
		$purchase_note   = $product->get_purchase_note();

		return array(
			(string) $product_id,
			(string) $product_type,
			(string) $sku,
			(string) '', // GTIN/UPC/EAN/ISBN
			(string) $name,
			(string) $published,
			(string) $is_featured,
			(string) $visibility,
			(string) $short_description,
			(string) $description,
			(string) $regular_price,
			(string) $sale_price,
			(string) $date_sale_price_starts,
			(string) $date_sale_price_ends,
			(string) $tax_status,
			(string) $tax_class,
			(string) $in_stock,
			(string) $stock_quantity,
			(string) $backorders_allowed,
			(string) '', // Low stock amount
			(string) $sold_individually,
			(string) $weight,
			(string) $length,
			(string) $width,
			(string) $height,
			(string) $categories,
			(string) $tags,
			(string) '', // Tags (space separated)
			(string) $shipping_class,
			(string) $images,
			(string) $parent_id,
			(string) $upsell_ids,
			(string) $cross_sell_ids,
			(string) $grouped_products,
			(string) $external_url,
			(string) $button_text,
			(string) '', // Download fields
			(string) '',
			(string) '',
			(string) '',
			(string) '',
			(string) '', // Attribute fields
			(string) '',
			(string) '',
			(string) '',
			(string) '',
			(string) $reviews_allowed,
			(string) $purchase_note,
			(string) '', // Import as meta data
			(string) $product->get_menu_order(),
		);
	}

	/**
	 * Finds the row number for a given product ID.
	 *
	 * This method searches the first column of the 'Sheet1' for a matching product ID.
	 * It's used to determine if a product already exists in the sheet and needs to be updated.
	 *
	 * @param Google_Service_Sheets $service The Sheets service client.
	 * @param int                   $product_id The product ID to search for.
	 * @return int|false The 1-based row number if the product ID is found, otherwise false.
	 */
	private function get_row_by_product_id( $service, $product_id ) {
		try {
			$range    = 'Sheet1!A:A';
			$response = $service->spreadsheets_values->get( $this->spreadsheet_id, $range );
			$values   = $response->getValues();

			if ( empty( $values ) ) {
				return false;
			}

			foreach ( $values as $row_index => $row ) {
				if ( isset( $row[0] ) && (string) $row[0] === (string) $product_id ) {
					return $row_index + 1;
				}
			}
		} catch ( Exception $e ) {
			error_log( 'GSheets Row Lookup Error: ' . $e->getMessage() );
		}

		return false;
	}

	/**
	 * Performs the initial setup of the Google Sheet.
	 *
	 * This method writes the header row to the Google Sheet. It is called only once
	 * when the `sheetsync_initial_setup_done` option is not set or is false.
	 *
	 * @return void
	 */
	public function perform_initial_setup() {
		try {
			if ( ! $this->client || empty( $this->spreadsheet_id ) ) {
				return;
			}
			$service     = new Google_Service_Sheets( $this->client );
			$header_data = array(
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
			$body        = new \Google_Service_Sheets_ValueRange( array( 'values' => array( $header_data ) ) );
			$range       = 'Sheet1!A1:AZ1';
			$params      = array( 'valueInputOption' => 'USER_ENTERED' );
			$service->spreadsheets_values->update( $this->spreadsheet_id, $range, $body, $params );
			update_option( 'sheetsync_initial_setup_done', true );
		} catch ( Exception $e ) {
			error_log( 'GSheets Header Error: ' . $e->getMessage() );
		}
	}
}
