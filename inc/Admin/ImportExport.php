<?php
/**
 * JSON export/import of every jk_* option plus appointment page content.
 * Used by the React settings app through Rest\SettingsController.
 *
 * The file format is the same as the PHP theme's, so exports move between both themes.
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Moves settings between sites (Laragon → live) without a plugin.
 */
final class ImportExport {

	/**
	 * Build the export array.
	 */
	public static function export(): array {
		$data = [
			'_meta'        => [
				'theme'    => 'jameskellie',
				'version'  => JK_VERSION,
				'site'     => home_url( '/' ),
				'exported' => gmdate( 'c' ),
			],
			'options'      => [],
			'appointments' => [],
		];

		foreach ( Schema::option_sections() as $section ) {
			$data['options'][ $section ] = Settings::raw( $section );
		}

		foreach ( self::appointment_pages() as $page ) {
			$meta = get_post_meta( $page->ID, SettingsRegistry::APPOINTMENT_META, true );
			if ( is_array( $meta ) ) {
				$data['appointments'][ get_page_uri( $page ) ] = $meta;
			}
		}

		return $data;
	}

	/**
	 * Suggested download file name.
	 */
	public static function filename(): string {
		return 'jk-settings-' . sanitize_file_name( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) . '-' . gmdate( 'Ymd-His' ) . '.json';
	}

	/**
	 * Import an export array. Values go through the schema sanitisers again.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return bool False when the data is not a James Kellie export.
	 */
	public static function import( mixed $data ): bool {
		if ( ! is_array( $data ) || ( $data['_meta']['theme'] ?? '' ) !== 'jameskellie' || ! is_array( $data['options'] ?? null ) ) {
			return false;
		}

		foreach ( Schema::option_sections() as $section ) {
			if ( isset( $data['options'][ $section ] ) && is_array( $data['options'][ $section ] ) ) {
				// update_option runs the registered sanitize_callback (Sanitizer::option).
				update_option( Schema::option_name( $section ), $data['options'][ $section ], 'code' !== $section );
			}
		}

		foreach ( (array) ( $data['appointments'] ?? [] ) as $path => $meta ) {
			$page = get_page_by_path( sanitize_text_field( (string) $path ) );
			if ( $page && is_array( $meta ) && current_user_can( 'edit_post', $page->ID ) ) {
				update_post_meta( $page->ID, SettingsRegistry::APPOINTMENT_META, wp_slash( Sanitizer::section( 'appointment', $meta ) ) );
			}
		}

		Settings::flush();
		return true;
	}

	/**
	 * Pages using the Book Appointment template.
	 *
	 * @return \WP_Post[]
	 */
	public static function appointment_pages(): array {
		return get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only export.
				'meta_value'     => SettingsRegistry::APPOINTMENT_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- admin-only export.
			]
		);
	}
}
