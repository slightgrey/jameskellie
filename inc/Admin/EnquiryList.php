<?php
/**
 * Enquiries list (edit.php?post_type=jk_enquiry), styled like the single enquiry view.
 *
 * WordPress's list table still does the work (search, All/Trash views, bulk trash,
 * pagination, sorting); this adds the JK header bar, friendlier columns and row links,
 * and admin.css (.jk-enquiries-screen) gives the table the card look.
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Enquiries;

defined( 'ABSPATH' ) || exit;

/**
 * Branded enquiries list.
 */
final class EnquiryList {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		$type = Enquiries::POST_TYPE;
		add_filter( "manage_{$type}_posts_columns", [ $this, 'columns' ] );
		add_action( "manage_{$type}_posts_custom_column", [ $this, 'column' ], 10, 2 );
		add_filter( "manage_edit-{$type}_sortable_columns", [ $this, 'sortable' ] );
		add_filter( 'list_table_primary_column', [ $this, 'primary_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
		add_action( 'all_admin_notices', [ $this, 'header' ] );
		add_filter( "bulk_actions-edit-{$type}", [ $this, 'bulk_actions' ] );
		add_filter( "views_edit-{$type}", [ $this, 'views' ] );
	}

	/**
	 * Every enquiry is stored as private, so the "Private" view just repeats "All".
	 *
	 * @param array $views View links.
	 */
	public function views( array $views ): array {
		unset( $views['private'] );
		return $views;
	}

	/**
	 * Whether the current screen is the enquiries list.
	 */
	private static function is_list_screen(): bool {
		$screen = get_current_screen();
		return $screen && 'edit' === $screen->base && Enquiries::POST_TYPE === $screen->post_type;
	}

	/**
	 * Body class that scopes the list styling in admin.css.
	 *
	 * @param string $classes Space-separated classes.
	 */
	public function body_class( string $classes ): string {
		return self::is_list_screen() ? $classes . ' jk-enquiries-screen' : $classes;
	}

	/**
	 * The JK header bar, above the views and the table (admin notices print inside .wrap).
	 */
	public function header(): void {
		if ( ! self::is_list_screen() ) {
			return;
		}
		$counts = wp_count_posts( Enquiries::POST_TYPE );
		$total  = (int) ( $counts->private ?? 0 );
		echo '<div class="jk-admin jk-enquiries-head">';
		EnquiryView::header_bar(
			__( 'Enquiries', 'jameskellie' ),
			static function () use ( $total ): void {
				printf(
					'<span class="tw:mr-2 tw:text-sm tw:text-neutral-300">%s</span>',
					esc_html(
						/* translators: %s: number of enquiries */
						sprintf( _n( '%s enquiry', '%s enquiries', $total, 'jameskellie' ), number_format_i18n( $total ) )
					)
				);
				printf( '<a class="jk-button jk-button-outline-light" href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=jk-general' ) ), esc_html__( 'Theme settings', 'jameskellie' ) );
				printf( '<a class="jk-button jk-button-light" href="%s" target="_blank" rel="noopener">%s <span aria-hidden="true">↗</span></a>', esc_url( home_url( '/' ) ), esc_html__( 'View site', 'jameskellie' ) );
			}
		);
		echo '</div>';
	}

	/**
	 * Columns: who, topic, message preview, mail status, received.
	 *
	 * @param array $columns Columns.
	 */
	public function columns( array $columns ): array {
		return [
			'cb'          => $columns['cb'] ?? '',
			'jk_from'     => __( 'From', 'jameskellie' ),
			'jk_topic'    => __( 'Topic', 'jameskellie' ),
			'jk_message'  => __( 'Message', 'jameskellie' ),
			'jk_mail'     => __( 'Email', 'jameskellie' ),
			'jk_received' => __( 'Received', 'jameskellie' ),
		];
	}

	/**
	 * Received sorts by post date (newest first is the default order).
	 *
	 * @param array $columns Sortable columns.
	 */
	public function sortable( array $columns ): array {
		$columns['jk_received'] = [ 'date', true ];
		return $columns;
	}

	/**
	 * Row actions sit under the name.
	 *
	 * @param string $column Default primary column.
	 * @param string $screen Screen ID.
	 */
	public function primary_column( string $column, string $screen ): string {
		return 'edit-' . Enquiries::POST_TYPE === $screen ? 'jk_from' : $column;
	}

	/**
	 * Enquiries are read-only: "Open" instead of Edit, no Quick Edit.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Post.
	 */
	public function row_actions( array $actions, \WP_Post $post ): array {
		if ( Enquiries::POST_TYPE !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'], $actions['edit'], $actions['view'] );
		if ( 'trash' !== $post->post_status ) {
			$actions = [
				'jk_open' => sprintf( '<a href="%s">%s</a>', esc_url( (string) get_edit_post_link( $post->ID ) ), esc_html__( 'Open', 'jameskellie' ) ),
			] + $actions;
		}
		return $actions;
	}

	/**
	 * No "Edit" bulk action (there is nothing to edit).
	 *
	 * @param array $actions Bulk actions.
	 */
	public function bulk_actions( array $actions ): array {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function column( string $column, int $post_id ): void {
		$data = (array) get_post_meta( $post_id, Enquiries::META, true );

		switch ( $column ) {
			case 'jk_from':
				$name  = (string) ( $data['name'] ?? '' );
				$email = (string) ( $data['email'] ?? '' );
				$phone = (string) ( $data['phone'] ?? '' );
				$link  = 'trash' === get_post_status( $post_id ) ? '' : (string) get_edit_post_link( $post_id );
				$label = '' !== $name ? $name : __( '(no name)', 'jameskellie' );
				echo $link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the sprintf below.
					? sprintf( '<a class="jk-enquiry-name row-title" href="%s">%s</a>', esc_url( $link ), esc_html( $label ) )
					: sprintf( '<strong class="jk-enquiry-name">%s</strong>', esc_html( $label ) );
				$contact = array_filter( [ $email, $phone ] );
				if ( $contact ) {
					printf( '<span class="jk-enquiry-contact">%s</span>', esc_html( implode( ' · ', $contact ) ) );
				}
				break;

			case 'jk_topic':
				$topic = (string) ( $data['topic'] ?? '' );
				if ( '' !== $topic ) {
					printf( '<span class="jk-chip">%s</span>', esc_html( $topic ) );
				}
				break;

			case 'jk_message':
				$message = trim( (string) ( $data['message'] ?? '' ) );
				printf( '<span class="jk-enquiry-excerpt">%s</span>', esc_html( wp_trim_words( $message, 16, '…' ) ) );
				break;

			case 'jk_mail':
				$sent = ! empty( $data['mail_sent'] );
				printf(
					'<span class="jk-status %s"><span class="jk-status-dot" aria-hidden="true"></span>%s</span>',
					$sent ? 'is-ok' : 'is-warn',
					$sent ? esc_html__( 'Delivered', 'jameskellie' ) : esc_html__( 'Not delivered', 'jameskellie' )
				);
				break;

			case 'jk_received':
				$time = (int) get_post_time( 'U', true, $post_id );
				printf(
					'<time class="jk-enquiry-time" datetime="%1$s" title="%2$s">%3$s<span>%2$s</span></time>',
					esc_attr( gmdate( 'c', $time ) ),
					esc_attr( wp_date( 'j M Y, g:ia', $time ) ),
					esc_html(
						/* translators: %s: human time difference, e.g. "3 hours" */
						sprintf( __( '%s ago', 'jameskellie' ), human_time_diff( $time ) )
					)
				);
				break;
		}
	}
}
