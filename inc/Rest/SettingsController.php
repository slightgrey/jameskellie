<?php
/**
 * REST API for the React Theme Settings app: /wp-json/jk/v1/admin/…
 *
 * Every route needs manage_options. WordPress cookie auth checks the X-WP-Nonce
 * header, so a request without a valid nonce runs as a logged-out user and fails.
 *
 * @package jameskellie
 */

namespace JK\Rest;

use JK\Admin\ImportExport;
use JK\Admin\Menu;
use JK\Schema;
use JK\Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Settings read, save, reset, export and import.
 */
final class SettingsController {

	private const NS = 'jk/v1';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	/**
	 * Register the routes.
	 */
	public function routes(): void {
		$section = [
			'section' => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => static fn( $value ): bool => in_array( $value, Schema::option_sections(), true ),
			],
		];

		register_rest_route(
			self::NS,
			'/admin/settings/(?P<section>[a-z]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get' ],
					'permission_callback' => [ $this, 'can_manage' ],
					'args'                => $section,
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save' ],
					'permission_callback' => [ $this, 'can_manage' ],
					'args'                => $section,
				],
			]
		);

		register_rest_route(
			self::NS,
			'/admin/settings/(?P<section>[a-z]+)/reset',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reset' ],
				'permission_callback' => [ $this, 'can_manage' ],
				'args'                => $section,
			]
		);

		register_rest_route(
			self::NS,
			'/admin/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export' ],
				'permission_callback' => [ $this, 'can_manage' ],
			]
		);

		register_rest_route(
			self::NS,
			'/admin/import',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import' ],
				'permission_callback' => [ $this, 'can_manage' ],
			]
		);
	}

	/**
	 * Only administrators.
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Section values (merged with defaults) and image previews.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function get( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( self::payload( (string) $request['section'] ), 200 );
	}

	/**
	 * Save a section. The registered sanitize_callback (Sanitizer::option) cleans it.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save( WP_REST_Request $request ) {
		$section = (string) $request['section'];
		$params  = $request->get_json_params();
		$values  = is_array( $params ) ? ( $params['values'] ?? null ) : null;

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'jk_bad_values', __( 'Nothing to save.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		update_option( Schema::option_name( $section ), $values );
		Settings::flush();

		return new WP_REST_Response( self::payload( $section ), 200 );
	}

	/**
	 * Write a section's defaults back.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function reset( WP_REST_Request $request ): WP_REST_Response {
		$section = (string) $request['section'];
		update_option( Schema::option_name( $section ), Schema::defaults( $section ), 'code' !== $section );
		Settings::flush();

		return new WP_REST_Response( self::payload( $section ), 200 );
	}

	/**
	 * The export file contents.
	 */
	public function export(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'filename' => ImportExport::filename(),
				'data'     => ImportExport::export(),
			],
			200
		);
	}

	/**
	 * Import an export file (JSON body).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import( WP_REST_Request $request ) {
		if ( strlen( (string) $request->get_body() ) > 1048576 ) {
			return new WP_Error( 'jk_import_size', __( 'Import failed: the file is too large.', 'jameskellie' ), [ 'status' => 413 ] );
		}
		if ( ! ImportExport::import( $request->get_json_params() ) ) {
			return new WP_Error( 'jk_import_invalid', __( 'Import failed: the file is not a valid James Kellie settings export.', 'jameskellie' ), [ 'status' => 400 ] );
		}
		return new WP_REST_Response( [ 'enabled' => Menu::enabled() ], 200 );
	}

	/**
	 * Values, image previews and the Home section switches.
	 *
	 * @param string $section Section key.
	 */
	private static function payload( string $section ): array {
		$values = Settings::section( $section );
		$images = [];

		foreach ( Schema::fields( $section ) as $field ) {
			if ( 'image' === $field['type'] && ! empty( $values[ $field['key'] ] ) ) {
				$id  = (int) $values[ $field['key'] ];
				$url = wp_get_attachment_image_url( $id, 'thumbnail' );
				if ( $url ) {
					$images[ $id ] = $url;
				}
			}
		}

		return [
			'values'  => $values,
			'images'  => (object) $images,
			'enabled' => Menu::enabled(),
		];
	}
}
