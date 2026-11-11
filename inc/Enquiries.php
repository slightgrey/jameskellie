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
				'capability_type'     => 'post',
				'capabilities'        => [ 'create_posts' => 'do_not_allow' ],
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
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s — %s', $data['name'], wp_date( 'j M Y, g:ia' ) ),
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, self::META, wp_slash( $data + [ 'mail_sent' => $sent ] ) );
		return (int) $id;
	}

	/**
	 * The enquiry screen is read-only: no Publish box. The view itself is Admin\EnquiryView.
	 */
	public function meta_box(): void {
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
	}
}
