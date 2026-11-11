<?php
/**
 * Single enquiry screen (post.php for the jk_enquiry post type), styled like Theme Settings:
 * the JK header bar, a message card with reply/call actions, and a details sidebar.
 * Read-only: enquiries are never edited, only read, answered or trashed.
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Enquiries;

defined( 'ABSPATH' ) || exit;

/**
 * Branded read-only view of one enquiry.
 */
final class EnquiryView {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'edit_form_after_title', [ $this, 'render' ] );
		add_action( 'admin_head-post.php', [ $this, 'hide_core_ui' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * Whether the current screen edits an enquiry.
	 */
	private static function is_enquiry_screen(): bool {
		$screen = get_current_screen();
		return $screen && 'post' === $screen->base && Enquiries::POST_TYPE === $screen->post_type;
	}

	/**
	 * Body class so the CSS below only touches this screen.
	 *
	 * @param string $classes Space-separated classes.
	 */
	public function body_class( string $classes ): string {
		return self::is_enquiry_screen() ? $classes . ' jk-enquiry-screen' : $classes;
	}

	/**
	 * Hide the parts of the post editor an enquiry does not use.
	 */
	public function hide_core_ui(): void {
		if ( ! self::is_enquiry_screen() ) {
			return;
		}
		echo '<style id="jk-enquiry-screen">'
			. '.jk-enquiry-screen .wrap>h1.wp-heading-inline,.jk-enquiry-screen .wrap>.page-title-action,.jk-enquiry-screen .wrap>hr.wp-header-end,'
			. '.jk-enquiry-screen #titlediv,.jk-enquiry-screen #postbox-container-1,.jk-enquiry-screen #postbox-container-2,'
			. '.jk-enquiry-screen #screen-meta-links{display:none!important}'
			. '.jk-enquiry-screen #poststuff #post-body.columns-2{margin-right:0}'
			. '.jk-enquiry-screen #poststuff{padding-top:0}'
			. '.jk-enquiry-screen .jk-admin{margin:12px 0 0;padding-bottom:40px}'
			. '</style>' . "\n";
	}

	/**
	 * The enquiry view.
	 *
	 * @param \WP_Post $post Enquiry.
	 */
	public function render( \WP_Post $post ): void {
		if ( Enquiries::POST_TYPE !== $post->post_type ) {
			return;
		}

		$data    = (array) get_post_meta( $post->ID, Enquiries::META, true );
		$name    = (string) ( $data['name'] ?? '' );
		$email   = (string) ( $data['email'] ?? '' );
		$phone   = (string) ( $data['phone'] ?? '' );
		$topic   = (string) ( $data['topic'] ?? '' );
		$message = (string) ( $data['message'] ?? '' );
		$page    = (string) ( $data['page'] ?? '' );
		$sent    = ! empty( $data['mail_sent'] );
		$time    = (int) get_post_time( 'U', true, $post );
		$tel     = 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
		$list    = admin_url( 'edit.php?post_type=' . Enquiries::POST_TYPE );
		$reply   = 'mailto:' . rawurlencode( $email ) . '?subject=' . rawurlencode(
			/* translators: %s: enquiry topic */
			'' !== $topic ? sprintf( __( 'Re: your enquiry about %s', 'jameskellie' ), $topic ) : __( 'Re: your enquiry', 'jameskellie' )
		);
		[ $newer, $older ] = self::neighbours( $post );

		$label = 'tw:m-0 tw:text-[11px] tw:font-semibold tw:tracking-[0.12em] tw:text-neutral-400 tw:uppercase';
		$card  = 'tw:rounded-2xl tw:border tw:border-neutral-200 tw:bg-white tw:shadow-sm';
		?>
		<div class="jk-admin">
			<?php
			self::header_bar(
				__( 'Enquiry', 'jameskellie' ),
				static function () use ( $list, $newer, $older ): void {
					?>
					<a class="jk-button jk-button-outline-light" href="<?php echo esc_url( $list ); ?>"><span aria-hidden="true">←</span> <?php esc_html_e( 'All enquiries', 'jameskellie' ); ?></a>
					<?php
					self::nav_button( $newer, __( 'Newer', 'jameskellie' ), 'prev' );
					self::nav_button( $older, __( 'Older', 'jameskellie' ), 'next' );
				}
			);
		?>

			<div class="tw:mt-6 tw:grid tw:items-start tw:gap-6 tw:min-[1100px]:grid-cols-[minmax(0,1fr)_340px]">
				<?php // The message. ?>
				<article class="<?php echo esc_attr( $card ); ?>" aria-labelledby="jk-enquiry-name">
					<header class="tw:border-b tw:border-neutral-200 tw:px-8 tw:py-7">
						<div class="tw:flex tw:flex-wrap tw:items-center tw:gap-3">
							<?php if ( '' !== $topic ) : ?>
								<span class="tw:rounded-full tw:bg-ink tw:px-3 tw:py-1 tw:text-xs tw:font-semibold tw:text-white"><?php echo esc_html( $topic ); ?></span>
							<?php endif; ?>
							<time class="tw:text-sm tw:text-neutral-500" datetime="<?php echo esc_attr( gmdate( 'c', $time ) ); ?>" title="<?php echo esc_attr( wp_date( 'j F Y, g:ia', $time ) ); ?>">
								<?php
								/* translators: %s: human time difference, e.g. "3 hours" */
								echo esc_html( sprintf( __( '%s ago', 'jameskellie' ), human_time_diff( $time ) ) );
								?>
							</time>
						</div>
						<h2 id="jk-enquiry-name" class="tw:mt-4 tw:mb-0 tw:p-0 tw:text-3xl tw:leading-tight tw:font-bold tw:tracking-tight tw:text-ink tw:font-display"><?php echo esc_html( '' !== $name ? $name : __( '(no name)', 'jameskellie' ) ); ?></h2>
						<p class="tw:mt-2 tw:mb-0 tw:flex tw:flex-wrap tw:gap-x-4 tw:gap-y-1 tw:text-sm">
							<?php if ( '' !== $email ) : ?>
								<a class="tw:font-medium tw:text-ink tw:underline-offset-2 tw:hover:underline" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
							<?php endif; ?>
							<?php if ( '' !== $phone ) : ?>
								<a class="tw:font-medium tw:text-ink tw:underline-offset-2 tw:hover:underline" href="<?php echo esc_url( $tel, [ 'tel' ] ); ?>"><?php echo esc_html( $phone ); ?></a>
							<?php endif; ?>
						</p>
					</header>

					<div class="tw:px-8 tw:py-7">
						<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Message', 'jameskellie' ); ?></p>
						<div class="tw:mt-3 tw:rounded-xl tw:bg-neutral-50 tw:px-6 tw:py-5 tw:text-[15px] tw:leading-relaxed tw:whitespace-pre-wrap tw:break-words tw:text-neutral-800"><?php echo esc_html( '' !== $message ? $message : __( '(empty)', 'jameskellie' ) ); ?></div>
					</div>

					<footer class="tw:flex tw:flex-wrap tw:items-center tw:gap-2 tw:rounded-b-2xl tw:border-t tw:border-neutral-200 tw:bg-neutral-50 tw:px-8 tw:py-5">
						<?php if ( '' !== $email ) : ?>
							<a class="jk-button jk-button-primary" href="<?php echo esc_url( $reply ); ?>">
								<span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php esc_html_e( 'Reply by email', 'jameskellie' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( '' !== $phone ) : ?>
							<a class="jk-button jk-button-ghost" href="<?php echo esc_url( $tel, [ 'tel' ] ); ?>">
								<span class="dashicons dashicons-phone" aria-hidden="true"></span>
								<?php
								/* translators: %s: phone number */
								echo esc_html( sprintf( __( 'Call %s', 'jameskellie' ), $phone ) );
								?>
							</a>
						<?php endif; ?>
					</footer>
				</article>

				<?php // Details and actions. ?>
				<aside class="tw:flex tw:flex-col tw:gap-6">
					<section class="<?php echo esc_attr( $card ); ?> tw:px-6 tw:py-6" aria-labelledby="jk-enquiry-details">
						<h3 id="jk-enquiry-details" class="tw:m-0 tw:p-0 tw:text-base tw:font-bold tw:text-ink tw:font-display"><?php esc_html_e( 'Details', 'jameskellie' ); ?></h3>
						<dl class="tw:mt-4 tw:mb-0 tw:flex tw:flex-col tw:gap-4">
							<?php
							self::detail( $label, __( 'Received', 'jameskellie' ), esc_html( wp_date( 'j F Y, g:ia', $time ) ) );
							if ( '' !== $topic ) {
								self::detail( $label, __( 'Topic', 'jameskellie' ), esc_html( $topic ) );
							}
							if ( '' !== $email ) {
								self::detail( $label, __( 'Email', 'jameskellie' ), sprintf( '<a class="tw:break-all tw:text-ink" href="%s">%s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) ) );
							}
							self::detail( $label, __( 'Phone', 'jameskellie' ), '' !== $phone ? sprintf( '<a class="tw:text-ink" href="%s">%s</a>', esc_url( $tel, [ 'tel' ] ), esc_html( $phone ) ) : '<span class="tw:text-neutral-400">' . esc_html__( 'Not given', 'jameskellie' ) . '</span>' );
							if ( '' !== $page ) {
								// The URL came from the visitor's browser: only link it when it is this site.
								$own  = strtolower( (string) wp_parse_url( $page, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
								$path = (string) wp_parse_url( $page, PHP_URL_PATH );
								$text = '' === $path || '/' === $path ? __( 'Home page', 'jameskellie' ) : $path;
								self::detail(
									$label,
									__( 'Sent from', 'jameskellie' ),
									$own
										? sprintf( '<a class="tw:break-all tw:text-ink" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $page ), esc_html( $text ) )
										: sprintf( '<span class="tw:break-all tw:text-neutral-500">%s</span>', esc_html( $page ) )
								);
							}
							?>
						</dl>
					</section>

					<section class="<?php echo esc_attr( $card ); ?> tw:px-6 tw:py-6" aria-labelledby="jk-enquiry-mail">
						<h3 id="jk-enquiry-mail" class="tw:m-0 tw:p-0 tw:text-base tw:font-bold tw:text-ink tw:font-display"><?php esc_html_e( 'Email notification', 'jameskellie' ); ?></h3>
						<?php if ( $sent ) : ?>
							<p class="tw:mt-3 tw:mb-0 tw:inline-flex tw:items-center tw:gap-2 tw:rounded-full tw:bg-emerald-50 tw:px-3 tw:py-1 tw:text-xs tw:font-semibold tw:text-emerald-700">
								<span class="tw:size-1.5 tw:rounded-full tw:bg-emerald-500" aria-hidden="true"></span><?php esc_html_e( 'Delivered', 'jameskellie' ); ?>
							</p>
							<p class="tw:mt-2 tw:mb-0 tw:text-xs tw:leading-relaxed tw:text-neutral-500"><?php esc_html_e( 'WordPress handed the notification to the mail server.', 'jameskellie' ); ?></p>
						<?php else : ?>
							<p class="tw:mt-3 tw:mb-0 tw:inline-flex tw:items-center tw:gap-2 tw:rounded-full tw:bg-amber-50 tw:px-3 tw:py-1 tw:text-xs tw:font-semibold tw:text-amber-800">
								<span class="tw:size-1.5 tw:rounded-full tw:bg-amber-500" aria-hidden="true"></span><?php esc_html_e( 'Not delivered', 'jameskellie' ); ?>
							</p>
							<p class="tw:mt-2 tw:mb-0 tw:text-xs tw:leading-relaxed tw:text-neutral-500"><?php esc_html_e( 'The notification email could not be sent, so this enquiry is only here. Check the site’s mail setup (an SMTP plugin usually fixes this).', 'jameskellie' ); ?></p>
						<?php endif; ?>
					</section>

					<?php $trash = current_user_can( 'delete_post', $post->ID ) ? get_delete_post_link( $post->ID ) : ''; ?>
					<?php if ( $trash ) : ?>
						<a class="jk-button jk-button-ghost jk-button-danger tw:self-start" href="<?php echo esc_url( $trash ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span><?php esc_html_e( 'Move to Trash', 'jameskellie' ); ?>
						</a>
					<?php endif; ?>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * The black JK header bar used on Theme Settings, the enquiry list and single enquiries.
	 *
	 * @param string   $subtitle Line under JAMES KELLIE.
	 * @param callable $actions  Prints the buttons on the right.
	 */
	public static function header_bar( string $subtitle, callable $actions ): void {
		$logo = "font-family:'Roboto Condensed','Arial Narrow',sans-serif";
		?>
		<div class="jk-dark tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4 tw:rounded-2xl tw:bg-ink tw:px-6 tw:py-5 tw:text-white">
			<div class="tw:flex tw:items-center tw:gap-4">
				<span class="tw:flex tw:size-11 tw:items-center tw:justify-center tw:bg-white tw:text-[24px] tw:leading-none tw:font-bold tw:text-ink" style="<?php echo esc_attr( $logo ); ?>" aria-hidden="true">JK</span>
				<div>
					<p class="tw:m-0 tw:text-[15px] tw:font-bold tw:tracking-[0.16em] tw:text-white" style="<?php echo esc_attr( $logo ); ?>">JAMES KELLIE</p>
					<p class="tw:m-0 tw:text-xs tw:text-neutral-400"><?php echo esc_html( $subtitle ); ?></p>
				</div>
			</div>
			<nav class="tw:flex tw:flex-wrap tw:items-center tw:gap-2" aria-label="<?php esc_attr_e( 'Enquiries', 'jameskellie' ); ?>">
				<?php $actions(); ?>
			</nav>
		</div>
		<?php
	}

	/**
	 * One label / value pair in the Details card.
	 *
	 * @param string $label_class Classes for the label.
	 * @param string $label       Label.
	 * @param string $value_html  Value, already escaped.
	 */
	private static function detail( string $label_class, string $label, string $value_html ): void {
		printf(
			'<div><dt class="%s">%s</dt><dd class="tw:m-0 tw:mt-1 tw:text-sm tw:text-neutral-800">%s</dd></div>',
			esc_attr( $label_class ),
			esc_html( $label ),
			wp_kses(
				$value_html,
				[
					'a'    => [
						'href'   => true,
						'class'  => true,
						'target' => true,
						'rel'    => true,
					],
					'span' => [ 'class' => true ],
				]
			)
		);
	}

	/**
	 * Previous / next button in the header (disabled look at either end).
	 *
	 * @param int    $id    Target enquiry, 0 for none.
	 * @param string $label Button text.
	 * @param string $dir   prev|next.
	 */
	private static function nav_button( int $id, string $label, string $dir ): void {
		$arrow = 'prev' === $dir ? '↑' : '↓';
		if ( ! $id ) {
			printf( '<span class="jk-button jk-button-outline-light tw:opacity-40" aria-disabled="true"><span aria-hidden="true">%s</span> %s</span>', esc_html( $arrow ), esc_html( $label ) );
			return;
		}
		printf(
			'<a class="jk-button jk-button-outline-light" href="%s" rel="%s"><span aria-hidden="true">%s</span> %s</a>',
			esc_url( (string) get_edit_post_link( $id, 'url' ) ),
			esc_attr( $dir ),
			esc_html( $arrow ),
			esc_html( $label )
		);
	}

	/**
	 * The newer and older enquiries around this one (list order is newest first).
	 *
	 * @param \WP_Post $post Enquiry.
	 * @return array{0:int, 1:int} [newer ID, older ID], 0 when none.
	 */
	private static function neighbours( \WP_Post $post ): array {
		$find = static function ( string $compare, string $order ) use ( $post ): int {
			$ids = get_posts(
				[
					'post_type'        => Enquiries::POST_TYPE,
					'post_status'      => 'private',
					'posts_per_page'   => 1,
					'fields'           => 'ids',
					'orderby'          => [
						'date' => $order,
						'ID'   => $order,
					],
					'date_query'       => [
						[
							'column'    => 'post_date',
							$compare    => $post->post_date,
							'inclusive' => false,
						],
					],
					'suppress_filters' => true,
					'no_found_rows'    => true,
				]
			);
			return (int) ( $ids[0] ?? 0 );
		};
		return [ $find( 'after', 'ASC' ), $find( 'before', 'DESC' ) ];
	}
}
