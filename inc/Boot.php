<?php
/**
 * Builds the data React renders from: site-wide settings plus the current route.
 *
 * The same payload is inlined on a direct load (index.php) and returned as JSON
 * for SPA navigation (Spa.php), so both paths use the real WordPress main query.
 * It never contains secrets (captcha secret keys) or the Custom Code snippets.
 *
 * @package jameskellie
 */

namespace JK;

use JK\Admin\AppointmentMetaBox;
use JK\Rest\ContactController;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end data payloads.
 */
final class Boot {

	/**
	 * JSON flags that make the output safe inside a <script> tag.
	 */
	public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

	/**
	 * Print the boot JSON for React.
	 */
	public static function print_data(): void {
		$data = [
			'site'  => self::site(),
			'route' => self::route(),
		];
		echo '<script id="jk-boot" type="application/json">' . wp_json_encode( $data, self::JSON_FLAGS ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hex-encoded JSON.
	}

	/**
	 * Minimal content for visitors without JavaScript.
	 */
	public static function print_noscript(): void {
		$route = self::route();
		echo '<noscript><div class="jk-container jk-section-y flex flex-col gap-6">';
		switch ( $route['type'] ) {
			case 'home':
				foreach ( $route['sections'] as $section ) {
					$data = $section['data'];
					if ( 'hero' === $section['key'] ) {
						printf( '<h1 class="jk-h1">%s</h1>', esc_html( (string) $data['title'] ) );
						if ( ! empty( $data['intro'] ) ) {
							printf( '<p>%s</p>', esc_html( (string) $data['intro'] ) );
						}
					} elseif ( ! empty( $data['title'] ) && is_string( $data['title'] ) ) {
						printf( '<h2 class="jk-h2">%s</h2>', esc_html( $data['title'] ) );
					}
				}
				break;
			case 'page':
				printf( '<h1 class="jk-h1">%s</h1>', esc_html( $route['title'] ) );
				echo '<div class="jk-prose">' . wp_kses_post( $route['content'] ) . '</div>';
				break;
			case 'appointment':
				printf( '<h1 class="jk-h1">%s</h1>', esc_html( (string) $route['data']['title'] ) );
				if ( ! empty( $route['data']['calendly']['url'] ) ) {
					printf( '<p><a href="%s">%s</a></p>', esc_url( $route['data']['calendly']['url'] ), esc_html( $route['data']['calendly']['open'] ) );
				}
				break;
			case 'posts':
				printf( '<h1 class="jk-h1">%s</h1><ul>', esc_html( $route['title'] ) );
				foreach ( $route['items'] as $item ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( $item['url'] ), esc_html( $item['title'] ) );
				}
				echo '</ul>';
				break;
			default:
				printf( '<h1 class="jk-h1">%s</h1>', esc_html__( 'Page not found.', 'jameskellie' ) );
		}
		$phone = (string) Settings::get( 'general.phone_display' );
		if ( '' !== $phone ) {
			printf( '<p><a href="%s">%s</a></p>', esc_url( self::tel(), [ 'tel' ] ), esc_html( $phone ) );
		}
		echo '</div></noscript>' . "\n";
	}

	/* ------------------------------------------------------------------ site */

	/**
	 * Site-wide data: header, footer, contact form config and UI strings.
	 */
	public static function site(): array {
		$general  = Settings::section( 'general' );
		$perf     = Settings::section( 'performance' );
		$networks = Schema::field( 'general', 'social' )['fields'][0]['choices'] ?? [];
		$social   = [];

		foreach ( (array) $general['social'] as $row ) {
			if ( is_array( $row ) && ! empty( $row['url'] ) ) {
				$network  = (string) ( $row['network'] ?? '' );
				$social[] = [
					'label' => (string) ( $networks[ $network ] ?? ucfirst( $network ) ),
					'url'   => esc_url_raw( (string) $row['url'] ),
				];
			}
		}

		$cta = is_array( $general['cta'] ) ? $general['cta'] : [];

		return [
			'home'           => home_url( '/' ),
			'name'           => get_bloginfo( 'name' ),
			'logo'           => self::image( (int) $general['logo'], 'medium' ),
			'nav'            => self::nav(),
			'cta'            => [
				'label' => (string) ( $cta['label'] ?? '' ),
				'url'   => (string) ( $cta['url'] ?? '' ),
			],
			'phone'          => (string) $general['phone_display'],
			'tel'            => self::tel(),
			'copyright'      => str_replace( '{year}', wp_date( 'Y' ), (string) $general['copyright'] ),
			'social'         => $social,
			'sticky'         => (bool) $general['sticky_header'],
			'backToTop'      => (bool) $general['back_to_top'],
			'hints'          => (bool) $general['click_hints'],
			'spa'            => (bool) $perf['spa'],
			'prefetch'       => (bool) $perf['prefetch'],
			'appointmentUrl' => self::appointment_url(),
			'contact'        => [
				'rest'      => rest_url( 'jk/v1/contact' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'action'    => admin_url( 'admin-post.php' ),
				'nojsNonce' => wp_create_nonce( ContactController::nojs_nonce_action() ),
				'ts'        => time(),
				'captcha'   => Captcha::client_config(),
			],
			'i18n'           => self::strings(),
		];
	}

	/**
	 * Top-level "primary" menu items, or links to the enabled Home sections.
	 *
	 * @return array<int, array{label:string, url:string}>
	 */
	private static function nav(): array {
		$links     = [];
		$locations = get_nav_menu_locations();

		if ( ! empty( $locations['primary'] ) ) {
			foreach ( (array) wp_get_nav_menu_items( $locations['primary'] ) as $item ) {
				if ( $item instanceof \WP_Post && 0 === (int) $item->menu_item_parent ) {
					$links[] = [
						'label' => (string) $item->title,
						'url'   => (string) $item->url,
					];
				}
			}
			return $links;
		}

		foreach ( [ 'services', 'experience', 'skills', 'contact' ] as $section ) {
			$anchor = (string) Settings::get( $section . '.anchor' );
			if ( Settings::get( $section . '.enabled' ) && '' !== $anchor ) {
				$links[] = [
					'label' => Schema::title( $section ),
					'url'   => '/#' . $anchor,
				];
			}
		}
		return $links;
	}

	/**
	 * Translatable UI strings used by the React components.
	 */
	private static function strings(): array {
		return [
			'skip'         => __( 'Skip to content', 'jameskellie' ),
			'menu'         => __( 'Menu', 'jameskellie' ),
			'main'         => __( 'Main', 'jameskellie' ),
			'logo'         => __( 'James Kellie — home', 'jameskellie' ),
			'social'       => __( 'Social', 'jameskellie' ),
			'backToTop'    => __( 'Back to top', 'jameskellie' ),
			'tools'        => __( 'Tools', 'jameskellie' ),
			'skillAreas'   => __( 'Skill areas', 'jameskellie' ),
			'coreServices' => __( 'Core services', 'jameskellie' ),
			'error404'     => __( 'Error 404', 'jameskellie' ),
			'notFound'     => __( 'Page not found.', 'jameskellie' ),
			'notFoundText' => __( 'The page you were looking for has moved or no longer exists.', 'jameskellie' ),
			'backHome'     => __( 'Back to home', 'jameskellie' ),
			'nothing'      => __( 'Nothing here yet.', 'jameskellie' ),
			'newer'        => __( 'Newer', 'jameskellie' ),
			'older'        => __( 'Older', 'jameskellie' ),
			'loadError'    => __( 'This page could not be loaded.', 'jameskellie' ),
			'contact'      => [
				'name'         => __( 'Name', 'jameskellie' ),
				'phone'        => __( 'Phone', 'jameskellie' ),
				'email'        => __( 'Email', 'jameskellie' ),
				'message'      => __( 'Message', 'jameskellie' ),
				'namePh'       => __( 'Your name', 'jameskellie' ),
				'phonePh'      => __( '04xx xxx xxx', 'jameskellie' ),
				'emailPh'      => __( 'you@business.com.au', 'jameskellie' ),
				'messagePh'    => __( 'Tell me a little about your business and goals', 'jameskellie' ),
				'website'      => __( 'Leave this field empty', 'jameskellie' ),
				'sending'      => __( 'Sending…', 'jameskellie' ),
				'invalidName'  => __( 'Please enter your name (2–100 characters).', 'jameskellie' ),
				'invalidEmail' => __( 'Please enter a valid email address.', 'jameskellie' ),
				'invalidMsg'   => __( 'Please enter a message (10–5000 characters).', 'jameskellie' ),
				'captcha'      => __( 'Please tick the “I’m not a robot” box and try again.', 'jameskellie' ),
			],
		];
	}

	/* ----------------------------------------------------------------- route */

	/**
	 * The current URL's data, from the main query.
	 */
	public static function route(): array {
		if ( is_front_page() ) {
			return [
				'type'     => 'home',
				'sections' => self::home_sections(),
			];
		}

		if ( is_404() ) {
			return [ 'type' => '404' ];
		}

		if ( is_singular() ) {
			$post_id = (int) get_queried_object_id();

			if ( 'page' === get_post_type( $post_id ) && AppointmentMetaBox::is_appointment( $post_id ) ) {
				return [
					'type' => 'appointment',
					'data' => self::appointment( $post_id ),
				];
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				return [ 'type' => '404' ];
			}

			return [
				'type'    => 'page',
				'title'   => get_the_title( $post ),
				'date'    => 'post' === $post->post_type ? get_the_date( '', $post ) : '',
				'content' => self::content( $post ),
			];
		}

		return self::posts();
	}

	/**
	 * Filtered post content (or the password form).
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function content( \WP_Post $post ): string {
		if ( post_password_required( $post ) ) {
			return get_the_password_form( $post );
		}
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the_content filters expect the global post.
		setup_postdata( $post );
		$content = (string) apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
		wp_reset_postdata();
		return $content;
	}

	/**
	 * Posts index, archives and search results.
	 */
	private static function posts(): array {
		global $wp_query;

		if ( is_search() ) {
			/* translators: %s: search query */
			$title = sprintf( __( 'Search: %s', 'jameskellie' ), get_search_query() );
		} elseif ( is_archive() ) {
			$title = wp_strip_all_tags( get_the_archive_title() );
		} else {
			$blog  = (int) get_option( 'page_for_posts' );
			$title = $blog && get_the_title( $blog ) ? get_the_title( $blog ) : __( 'Articles', 'jameskellie' );
		}

		$items = [];
		foreach ( (array) $wp_query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$items[] = [
					'title' => get_the_title( $post ),
					'date'  => get_the_date( '', $post ),
					'url'   => (string) get_permalink( $post ),
				];
			}
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		$max   = (int) $wp_query->max_num_pages;

		return [
			'type'  => 'posts',
			'title' => $title,
			'items' => $items,
			'newer' => $paged > 1 ? (string) get_pagenum_link( $paged - 1 ) : '',
			'older' => $paged < $max ? (string) get_pagenum_link( $paged + 1 ) : '',
		];
	}

	/* ------------------------------------------------------------------ home */

	/**
	 * Enabled Home sections in their saved order, each with render-ready data.
	 *
	 * @return array<int, array{key:string, data:array}>
	 */
	private static function home_sections(): array {
		$out = [];
		foreach ( (array) Settings::get( 'layout.order', [] ) as $key ) {
			$key = (string) $key;
			if ( ! Settings::get( $key . '.enabled' ) ) {
				continue;
			}
			$method = 'section_' . $key;
			if ( method_exists( self::class, $method ) ) {
				$out[] = [
					'key'  => $key,
					'data' => self::$method( Settings::section( $key ) ),
				];
			}
		}
		return $out;
	}

	/**
	 * Hero.
	 *
	 * @param array $s Section values.
	 */
	private static function section_hero( array $s ): array {
		$primary = is_array( $s['primary'] ) ? $s['primary'] : [];
		return [
			'anchor'    => (string) $s['anchor'],
			'eyebrow'   => (string) $s['eyebrow'],
			'title'     => (string) $s['title'],
			'intro'     => (string) $s['intro'],
			'primary'   => [
				'label' => (string) ( $primary['label'] ?? '' ),
				'url'   => self::link( (string) ( $primary['url'] ?? '' ) ),
			],
			'phone'     => $s['show_phone'] ? trim( $s['phone_label'] . ' ' . Settings::get( 'general.phone_display' ) ) : '',
			'portrait'  => self::image( (int) $s['portrait'], 'large', '(min-width: 1280px) 490px, (min-width: 1000px) 40vw, (min-width: 600px) 560px, 100vw' ),
			'greyscale' => (bool) $s['greyscale'],
			'hasPhone'  => '' !== (string) Settings::get( 'general.phone_display' ),
		];
	}

	/**
	 * Stats (up to 4).
	 *
	 * @param array $s Section values.
	 */
	private static function section_stats( array $s ): array {
		$items = [];
		foreach ( (array) $s['items'] as $item ) {
			if ( is_array( $item ) && ( '' !== (string) ( $item['number'] ?? '' ) || '' !== (string) ( $item['label'] ?? '' ) ) ) {
				$items[] = [
					'number' => (string) ( $item['number'] ?? '' ),
					'label'  => (string) ( $item['label'] ?? '' ),
				];
			}
		}
		return [
			'anchor' => (string) $s['anchor'],
			'label'  => (string) $s['label'],
			'items'  => array_slice( $items, 0, 4 ),
		];
	}

	/**
	 * Services.
	 *
	 * @param array $s Section values.
	 */
	private static function section_services( array $s ): array {
		$items = [];
		foreach ( (array) $s['items'] as $item ) {
			if ( ! is_array( $item ) || '' === (string) ( $item['title'] ?? '' ) ) {
				continue;
			}
			$items[] = [
				'title'     => (string) $item['title'],
				'slug'      => '' !== (string) ( $item['slug'] ?? '' ) ? (string) $item['slug'] : sanitize_title( (string) $item['title'] ),
				'stat'      => (string) ( $item['stat'] ?? '' ),
				'statLabel' => (string) ( $item['stat_label'] ?? '' ),
				'body'      => (string) ( $item['body'] ?? '' ),
				'tools'     => array_values( array_map( 'strval', (array) ( $item['tools'] ?? [] ) ) ),
			];
		}
		return [
			'anchor'    => (string) $s['anchor'],
			'prefix'    => '' !== (string) $s['anchor'] ? (string) $s['anchor'] : 'services',
			'eyebrow'   => (string) $s['eyebrow'],
			'title'     => (string) $s['title'],
			'side'      => (string) $s['side'],
			'closing'   => (string) $s['closing'],
			'bookLabel' => (string) $s['book_label'],
			'hintLabel' => (string) $s['hint_label'],
			'items'     => $items,
		];
	}

	/**
	 * Experience.
	 *
	 * @param array $s Section values.
	 */
	private static function section_experience( array $s ): array {
		$roles = [];
		foreach ( (array) $s['roles'] as $role ) {
			if ( ! is_array( $role ) || ( '' === (string) ( $role['company'] ?? '' ) && '' === (string) ( $role['role'] ?? '' ) ) ) {
				continue;
			}
			$roles[] = [
				'years'       => (string) ( $role['years'] ?? '' ),
				'company'     => (string) ( $role['company'] ?? '' ),
				'role'        => (string) ( $role['role'] ?? '' ),
				'description' => (string) ( $role['description'] ?? '' ),
			];
		}
		return [
			'anchor'    => (string) $s['anchor'],
			'prefix'    => '' !== (string) $s['anchor'] ? (string) $s['anchor'] : 'experience',
			'eyebrow'   => (string) $s['eyebrow'],
			'title'     => (string) $s['title'],
			'intro'     => $s['intro'] ? wp_kses_post( wpautop( (string) $s['intro'] ) ) : '',
			'openFirst' => (bool) $s['open_first'],
			'hintLabel' => (string) $s['hint_label'],
			'roles'     => $roles,
		];
	}

	/**
	 * CTA band.
	 *
	 * @param array $s Section values.
	 */
	private static function section_cta( array $s ): array {
		$tiles = [];
		foreach ( (array) $s['strip'] as $tile ) {
			if ( is_array( $tile ) && '' !== (string) ( $tile['name'] ?? '' ) ) {
				$tiles[] = [
					'name' => (string) $tile['name'],
					'meta' => (string) ( $tile['meta'] ?? '' ),
					'url'  => self::link( self::service_target( (string) ( $tile['target'] ?? '' ) ) ),
				];
			}
		}
		$button = is_array( $s['button'] ) ? $s['button'] : [];
		return [
			'anchor'  => (string) $s['anchor'],
			'lead'    => (string) $s['lead'],
			'heading' => (string) $s['heading'],
			'title'   => (string) $s['heading'],
			'tiles'   => array_slice( $tiles, 0, 4 ),
			'button'  => [
				'label' => (string) ( $button['label'] ?? '' ),
				'url'   => self::link( (string) ( $button['url'] ?? '' ) ),
			],
		];
	}

	/**
	 * Skills.
	 *
	 * @param array $s Section values.
	 */
	private static function section_skills( array $s ): array {
		$tabs = [];
		foreach ( (array) $s['tabs'] as $tab ) {
			if ( is_array( $tab ) && '' !== (string) ( $tab['label'] ?? '' ) ) {
				$tabs[] = [
					'label' => (string) $tab['label'],
					'items' => array_values( array_map( 'strval', (array) ( $tab['items'] ?? [] ) ) ),
				];
			}
		}
		return [
			'anchor'          => (string) $s['anchor'],
			'prefix'          => '' !== (string) $s['anchor'] ? (string) $s['anchor'] : 'skills',
			'eyebrow'         => (string) $s['eyebrow'],
			'title'           => (string) $s['title'],
			'side'            => (string) $s['side'],
			'hintLabel'       => (string) $s['hint_label'],
			'tabs'            => $tabs,
			'industriesLabel' => (string) $s['industries_label'],
			'industries'      => array_values( array_map( 'strval', (array) $s['industries'] ) ),
		];
	}

	/**
	 * Contact.
	 *
	 * @param array $s Section values.
	 */
	private static function section_contact( array $s ): array {
		return [
			'anchor'     => (string) $s['anchor'],
			'eyebrow'    => (string) $s['eyebrow'],
			'title'      => (string) $s['title'],
			'phoneLabel' => (string) $s['phone_label'],
			'bookLabel'  => (string) $s['book_label'],
			'heading'    => (string) $s['form_heading'],
			'note'       => (string) $s['required_note'],
			'legend'     => (string) $s['topic_legend'],
			'topics'     => array_values( array_map( 'strval', (array) $s['topics'] ) ),
			'submit'     => (string) $s['submit'],
			'success'    => (string) $s['success'],
			'error'      => (string) $s['error'],
			'callPrefix' => (string) $s['call_prefix'],
		];
	}

	/* ----------------------------------------------------------- appointment */

	/**
	 * Book Appointment page data.
	 *
	 * @param int $post_id Page ID.
	 */
	private static function appointment( int $post_id ): array {
		$d   = Settings::appointment( $post_id );
		$url = ! empty( $d['calendly_url'] ) ? (string) $d['calendly_url'] : (string) Settings::get( 'general.calendly_url' );

		$calendly = null;
		if ( '' !== $url ) {
			$calendly = [
				'src'     => add_query_arg(
					[
						'embed_domain'            => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
						'embed_type'              => 'Inline',
						'hide_gdpr_banner'        => '1',
						'hide_event_type_details' => '1',
						'primary_color'           => '0a0a0a',
					],
					$url
				),
				'url'     => $url,
				'lazy'    => (bool) Settings::get( 'performance.lazy_calendly' ),
				'title'   => __( 'Book a time with James Kellie (Calendly)', 'jameskellie' ),
				'loading' => __( 'Loading the booking calendar…', 'jameskellie' ),
				'open'    => __( 'Open the calendar in a new tab', 'jameskellie' ),
			];
		}

		return [
			'eyebrow'     => (string) ( $d['eyebrow'] ?? '' ),
			'title'       => '' !== (string) ( $d['title'] ?? '' ) ? (string) $d['title'] : get_the_title( $post_id ),
			'intro'       => (string) ( $d['intro'] ?? '' ),
			'listTitle'   => (string) ( $d['list_title'] ?? '' ),
			'list'        => array_values( array_map( 'strval', (array) ( $d['list'] ?? [] ) ) ),
			'cardName'    => (string) ( $d['card_name'] ?? '' ),
			'cardTagline' => (string) ( $d['card_tagline'] ?? '' ),
			'cardImage'   => self::image( (int) ( $d['card_image'] ?? 0 ), 'thumbnail' ),
			'callLabel'   => (string) ( $d['call_label'] ?? '' ),
			'calendly'    => $calendly,
		];
	}

	/* --------------------------------------------------------------- helpers */

	/**
	 * Attachment image data for <img>.
	 *
	 * @param int    $id    Attachment ID.
	 * @param string $size  Image size.
	 * @param string $sizes Optional sizes attribute.
	 * @return array{src:string, srcset:string, sizes:string, width:int, height:int, alt:string}|null
	 */
	public static function image( int $id, string $size, string $sizes = '' ): ?array {
		if ( ! $id ) {
			return null;
		}
		$src = wp_get_attachment_image_src( $id, $size );
		if ( ! $src ) {
			return null;
		}
		$srcset = (string) wp_get_attachment_image_srcset( $id, $size );
		return [
			'src'    => (string) $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'srcset' => $srcset,
			'sizes'  => '' !== $srcset ? ( '' !== $sizes ? $sizes : (string) wp_get_attachment_image_sizes( $id, $size ) ) : '',
			'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		];
	}

	/**
	 * Resolve a CTA-strip target: a service slug becomes #{services-anchor}-{slug}.
	 *
	 * @param string $target Slug or URL.
	 */
	public static function service_target( string $target ): string {
		if ( '' === $target || preg_match( '~[/#:?.]~', $target ) ) {
			return $target;
		}
		$anchor = (string) Settings::get( 'services.anchor', 'services' );
		return '#' . ( '' !== $anchor ? $anchor . '-' : '' ) . sanitize_title( $target );
	}

	/**
	 * Home-section hash links (#services) work from any page as /#services.
	 *
	 * @param string $url URL.
	 */
	public static function link( string $url ): string {
		if ( str_starts_with( $url, '#' ) && ! is_front_page() ) {
			return home_url( '/' ) . $url;
		}
		return $url;
	}

	/**
	 * The tel: href from the General phone setting.
	 */
	public static function tel(): string {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', (string) Settings::get( 'general.phone_tel' ) );
	}

	/**
	 * The appointment page URL (the header CTA, or /appointment/).
	 */
	public static function appointment_url(): string {
		$cta = (string) Settings::get( 'general.cta.url', '/appointment/' );
		return '' !== $cta ? $cta : home_url( '/appointment/' );
	}
}
