<?php
/**
 * First-activation seed and "Reset to defaults" (HANDOFF §5.3).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds options and handles resets.
 */
final class Seeder {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'after_switch_theme', [ self::class, 'seed' ] );
		add_action( 'admin_post_jk_reset', [ $this, 'reset' ] );
	}

	/**
	 * Add every jk_* option that does not exist yet, and create the appointment page.
	 */
	public static function seed(): void {
		foreach ( Schema::option_sections() as $section ) {
			// Every option except jk_code is needed on the front page, so autoload them.
			add_option( Schema::option_name( $section ), Schema::defaults( $section ), '', 'code' !== $section );
		}
		self::ensure_appointment_page();
	}

	/**
	 * Create /appointment/ with the Book Appointment template if no such page exists.
	 */
	private static function ensure_appointment_page(): void {
		if ( get_page_by_path( 'appointment' ) ) {
			return;
		}
		$id = wp_insert_post(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => __( 'Book an Appointment', 'jameskellie' ),
				'post_name'   => 'appointment',
			]
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', SettingsRegistry::APPOINTMENT_TEMPLATE );
			update_post_meta( $id, SettingsRegistry::APPOINTMENT_META, wp_slash( Schema::defaults( 'appointment' ) ) );
		}
	}

	/**
	 * Handle admin-post.php?action=jk_reset: write a section's defaults back.
	 */
	public function reset(): void {
		$section = isset( $_REQUEST['section'] ) ? sanitize_key( wp_unslash( $_REQUEST['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
		$post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.

		check_admin_referer( 'jk_reset_' . $section . ( $post_id ? '_' . $post_id : '' ) );

		if ( 'appointment' === $section ) {
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'You are not allowed to do that.', 'jameskellie' ), 403 );
			}
			update_post_meta( $post_id, SettingsRegistry::APPOINTMENT_META, wp_slash( Schema::defaults( 'appointment' ) ) );
			wp_safe_redirect( add_query_arg( 'jk_notice', 'reset', get_edit_post_link( $post_id, 'url' ) ) );
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) || ! in_array( $section, Schema::option_sections(), true ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'jameskellie' ), 403 );
		}

		update_option( Schema::option_name( $section ), Schema::defaults( $section ), 'code' !== $section );
		Settings::flush();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'      => 'jk-' . $section,
					'jk_notice' => 'reset',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
