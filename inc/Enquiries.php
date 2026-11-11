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
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
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
	 * Read-only details box.
	 */
	public function meta_box(): void {
		add_meta_box( 'jk-enquiry', __( 'Enquiry', 'jameskellie' ), [ $this, 'render' ], self::POST_TYPE, 'normal', 'high' );
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
	}

	/**
	 * Render the fields.
	 *
	 * @param \WP_Post $post Enquiry.
	 */
	public function render( \WP_Post $post ): void {
		$data   = (array) get_post_meta( $post->ID, self::META, true );
		$labels = [
			'topic'     => __( 'Topic', 'jameskellie' ),
			'name'      => __( 'Name', 'jameskellie' ),
			'email'     => __( 'Email', 'jameskellie' ),
			'phone'     => __( 'Phone', 'jameskellie' ),
			'message'   => __( 'Message', 'jameskellie' ),
			'page'      => __( 'Sent from', 'jameskellie' ),
			'mail_sent' => __( 'Email delivered', 'jameskellie' ),
		];
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $labels as $key => $label ) {
			$value = $data[ $key ] ?? '';
			if ( 'mail_sent' === $key ) {
				$value = $value ? __( 'Yes', 'jameskellie' ) : __( 'No — check the mail setup', 'jameskellie' );
			}
			if ( 'email' === $key && $value ) {
				$value = sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_html( $value ) );
				printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), wp_kses( $value, [ 'a' => [ 'href' => true ] ] ) );
				continue;
			}
			printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) $value ) ) );
		}
		echo '</tbody></table>';
	}

	/**
	 * List columns.
	 *
	 * @param array $columns Columns.
	 */
	public function columns( array $columns ): array {
		return [
			'cb'       => $columns['cb'] ?? '',
			'title'    => __( 'Enquiry', 'jameskellie' ),
			'jk_topic' => __( 'Topic', 'jameskellie' ),
			'jk_email' => __( 'Email', 'jameskellie' ),
			'date'     => $columns['date'] ?? __( 'Date', 'jameskellie' ),
		];
	}

	/**
	 * Column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function column( string $column, int $post_id ): void {
		$data = (array) get_post_meta( $post_id, self::META, true );
		if ( 'jk_topic' === $column ) {
			echo esc_html( (string) ( $data['topic'] ?? '' ) );
		} elseif ( 'jk_email' === $column ) {
			echo esc_html( (string) ( $data['email'] ?? '' ) );
		}
	}
}
