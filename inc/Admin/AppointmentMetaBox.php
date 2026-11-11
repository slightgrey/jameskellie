<?php
/**
 * "Book Appointment" meta box, shown only for the Book Appointment template (HANDOFF §5.1).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Page meta box for _jk_appointment.
 */
final class AppointmentMetaBox {

	private const NONCE = 'jk_appointment_nonce';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_page', [ $this, 'add' ] );
		add_action( 'save_post_page', [ $this, 'save' ], 10, 2 );
		add_action( 'load-post.php', [ $this, 'hide_editor' ] );
	}

	/**
	 * Whether a page uses the Book Appointment template.
	 *
	 * @param int $post_id Page ID.
	 */
	public static function is_appointment( int $post_id ): bool {
		return SettingsRegistry::APPOINTMENT_TEMPLATE === get_page_template_slug( $post_id );
	}

	/**
	 * The content area is not used on this template, so drop the editor.
	 * The page then opens in the classic screen with just the title and this box.
	 */
	public function hide_editor(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		if ( $post_id && 'page' === get_post_type( $post_id ) && self::is_appointment( $post_id ) ) {
			remove_post_type_support( 'page', 'editor' );
		}
	}

	/**
	 * Add the box (and seed defaults on first edit).
	 *
	 * @param \WP_Post $post Page.
	 */
	public function add( \WP_Post $post ): void {
		if ( ! self::is_appointment( $post->ID ) ) {
			return;
		}
		if ( ! metadata_exists( 'post', $post->ID, SettingsRegistry::APPOINTMENT_META ) ) {
			update_post_meta( $post->ID, SettingsRegistry::APPOINTMENT_META, wp_slash( Schema::defaults( 'appointment' ) ) );
		}
		add_meta_box( 'jk-appointment', __( 'Book Appointment', 'jameskellie' ), [ $this, 'render' ], 'page', 'normal', 'high' );
	}

	/**
	 * Render the fields.
	 *
	 * @param \WP_Post $post Page.
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'jk_appointment_save', self::NONCE );

		Menu::notices();
		echo '<p class="description">' . esc_html__( 'All content on this page comes from these fields. If you just switched to the Book Appointment template in the block editor, save the page and reload to see this box.', 'jameskellie' ) . '</p>';

		FieldRenderer::table( Schema::fields( 'appointment' ), 'jk_appointment', Settings::appointment( $post->ID ) );

		$reset = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'jk_reset',
					'section' => 'appointment',
					'post'    => $post->ID,
				],
				admin_url( 'admin-post.php' )
			),
			'jk_reset_appointment_' . $post->ID
		);
		printf(
			'<p class="jk-fields"><a class="jk-button jk-button-ghost" href="%s" onclick="return confirm(%s)">%s</a></p>',
			esc_url( $reset ),
			esc_attr( wp_json_encode( __( 'Reset this page to its default content? Your changes will be lost.', 'jameskellie' ) ) ),
			esc_html__( 'Reset to defaults', 'jameskellie' )
		);
	}

	/**
	 * Save the meta with nonce, capability, autosave and revision guards.
	 *
	 * @param int      $post_id Page ID.
	 * @param \WP_Post $post    Page.
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), 'jk_appointment_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) || ! self::is_appointment( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['jk_appointment'] ) || ! is_array( $_POST['jk_appointment'] ) ) {
			return;
		}

		$input = wp_unslash( $_POST['jk_appointment'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by the schema below.
		update_post_meta( $post_id, SettingsRegistry::APPOINTMENT_META, wp_slash( Sanitizer::section( 'appointment', $input ) ) );
	}
}
