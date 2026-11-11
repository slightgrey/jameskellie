<?php
/**
 * Registers every jk_* option and the appointment post meta (HANDOFF §5.1).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Settings API registration.
 */
final class SettingsRegistry {

	public const APPOINTMENT_TEMPLATE = 'templates/page-appointment.php';
	public const APPOINTMENT_META     = '_jk_appointment';

	/**
	 * Register settings and meta.
	 */
	public static function register(): void {
		foreach ( Schema::option_sections() as $section ) {
			register_setting(
				Schema::option_name( $section ),
				Schema::option_name( $section ),
				[
					'type'              => 'array',
					'sanitize_callback' => static fn( $value ) => Sanitizer::option( $section, $value ),
					'default'           => Schema::defaults( $section ),
					'show_in_rest'      => false,
				]
			);
		}

		register_post_meta(
			'page',
			self::APPOINTMENT_META,
			[
				'single'            => true,
				'type'              => 'object',
				'show_in_rest'      => false,
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_pages' ),
				'sanitize_callback' => static fn( $value ) => Sanitizer::section( 'appointment', $value ),
			]
		);
	}
}
