<?php
/**
 * Private jk_enquiry post type so leads are kept even if mail fails (HANDOFF §9).
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Enquiry storage.
 */
final class Enquiries {

	public const POST_TYPE = 'jk_enquiry';
	public const META      = '_jk_enquiry';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'meta_box' ] );
	}

	/**
	 * Register the post type (admin-only, no front end, no manual creation).
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Enquiries', 'jameskellie' ),
					'singular_name' => __( 'Enquiry', 'jameskellie' ),
					'edit_item'     => __( 'Enquiry', 'jameskellie' ),
					'not_found'     => __( 'No enquiries yet.', 'jameskellie' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'jk-general',
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => [ 'title' ],
				// Enquiries hold visitors' personal details: administrators only (manage_options,
				// as for Theme Settings), not Editors. Nobody creates them by hand.
				'capability_type'     => 'post',
				'capabilities'        => [
					'create_posts'           => 'do_not_allow',
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'delete_posts'           => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'delete_private_posts'   => 'manage_options',
					'delete_published_posts' => 'manage_options',
				],
				'map_meta_cap'        => true,
			]
		);
	}

	/**
	 * Store an enquiry.
	 *
	 * @param array $data Sanitised fields.
	 * @param bool  $sent Whether wp_mail succeeded.
	 */
	public static function store( array $data, bool $sent ): int {
		$id = wp_insert_post(
			[
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'private',
				'post_title'   => sprintf( '%s — %s', $data['name'], wp_date( 'j M Y, g:ia' ) ),
				// Plain-text search index (WordPress's admin search covers title and content).
				'post_content' => self::search_text( $data ),
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
		$data += [ 'mail_sent' => $sent ];
		update_post_meta( $id, self::META, wp_slash( $data ) );
		self::store_filters( (int) $id, $data );
		return (int) $id;
	}

	/** Meta key for the topic, for the list's Topic filter. */
	public const TOPIC_META = '_jk_enquiry_topic';

	/** Meta key for '1' / '0' email delivered, for the list's Email filter. */
	public const MAIL_META = '_jk_enquiry_mail';

	/**
	 * Plain-text index of an enquiry for the admin search: name, email, phone (as typed and as
	 * digits only, so "0411 644" and "0411644" both match), topic and message.
	 * Never shown anywhere; the post type is private and not public or in the REST API.
	 *
	 * @param array $data Enquiry fields.
	 */
	public static function search_text( array $data ): string {
		$phone = (string) ( $data['phone'] ?? '' );
		$parts = [
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['email'] ?? '' ),
			$phone,
			(string) preg_replace( '/\D/', '', $phone ),
			(string) ( $data['topic'] ?? '' ),
			(string) ( $data['message'] ?? '' ),
		];
		return sanitize_textarea_field( implode( "\n", array_filter( $parts, static fn( string $p ): bool => '' !== trim( $p ) ) ) );
	}

	/**
	 * Separate meta for the list filters (the main meta is one array, which cannot be filtered on).
	 *
	 * @param int   $id   Enquiry ID.
	 * @param array $data Enquiry fields, with mail_sent.
	 */
	public static function store_filters( int $id, array $data ): void {
		update_post_meta( $id, self::TOPIC_META, sanitize_text_field( (string) ( $data['topic'] ?? '' ) ) );
		update_post_meta( $id, self::MAIL_META, ! empty( $data['mail_sent'] ) ? '1' : '0' );
	}

	/**
	 * The enquiry screen is read-only: no Publish box. The view itself is Admin\EnquiryView.
	 */
	public function meta_box(): void {
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
	}
}
