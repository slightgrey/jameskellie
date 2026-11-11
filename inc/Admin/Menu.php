<?php
/**
 * "James Kellie" admin menu. Every screen is the same React app (src/admin),
 * which reads and saves settings through jk/v1/admin (Rest\SettingsController).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Assets;
use JK\Enquiries;
use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu, mount point and the React app's config.
 */
final class Menu {

	public const PARENT = 'jk-general';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'admin_menu', [ $this, 'register' ] );
		add_action( 'admin_menu', [ $this, 'enquiries_last' ], 999 );
		add_action( 'admin_head', [ $this, 'fonts' ] );
	}

	/**
	 * WordPress adds the Enquiries post type (show_in_menu => jk-general) before the
	 * settings pages, and the top-level link always opens the first submenu item.
	 * Move Enquiries to the end so General comes first and the top-level link opens it.
	 */
	public function enquiries_last(): void {
		global $submenu;

		if ( empty( $submenu[ self::PARENT ] ) || ! is_array( $submenu[ self::PARENT ] ) ) {
			return;
		}

		$slug  = 'edit.php?post_type=' . Enquiries::POST_TYPE;
		$items = [];
		$moved = [];
		foreach ( $submenu[ self::PARENT ] as $item ) {
			if ( isset( $item[2] ) && $slug === $item[2] ) {
				$moved[] = $item;
			} else {
				$items[] = $item;
			}
		}
		$submenu[ self::PARENT ] = array_merge( $items, $moved ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- reordering our own submenu.
	}

	/**
	 * Add the top-level menu and sub-pages.
	 */
	public function register(): void {
		add_menu_page(
			__( 'James Kellie', 'jameskellie' ),
			__( 'James Kellie', 'jameskellie' ),
			'manage_options',
			self::PARENT,
			[ $this, 'render' ],
			'dashicons-admin-customizer',
			59
		);

		foreach ( Schema::option_sections() as $section ) {
			add_submenu_page(
				self::PARENT,
				Schema::title( $section ),
				Schema::title( $section ),
				'manage_options',
				'jk-' . $section,
				[ $this, 'render' ]
			);
		}
	}

	/**
	 * The theme's fonts on its own screens.
	 */
	public function fonts(): void {
		$screen = get_current_screen();
		if ( $screen && ( str_contains( $screen->id, 'jk-' ) || 'page' === $screen->post_type ) ) {
			echo '<style id="jk-admin-fonts">' . Assets::font_faces() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from constants and escaped URLs.
		}
	}

	/**
	 * Sidebar groups: section => [dashicon, description].
	 *
	 * @return array<string, array<string, array{0:string,1:string}>>
	 */
	public static function nav(): array {
		return [
			__( 'Site', 'jameskellie' )      => [
				'general' => [ 'dashicons-admin-settings', __( 'Contact details, the header button, logo, spam protection and sharing.', 'jameskellie' ) ],
				'layout'  => [ 'dashicons-layout', __( 'Drag the Home page sections into the order you want.', 'jameskellie' ) ],
			],
			__( 'Home page', 'jameskellie' ) => [
				'hero'       => [ 'dashicons-cover-image', __( 'The first thing visitors see: heading, intro, buttons and portrait.', 'jameskellie' ) ],
				'stats'      => [ 'dashicons-chart-bar', __( 'Up to four headline numbers under the hero.', 'jameskellie' ) ],
				'services'   => [ 'dashicons-portfolio', __( 'The interactive services list and its detail panels.', 'jameskellie' ) ],
				'experience' => [ 'dashicons-id-alt', __( 'The career intro and the expandable role cards.', 'jameskellie' ) ],
				'cta'        => [ 'dashicons-megaphone', __( 'The black service strip and the big “Can I help you?” call to action.', 'jameskellie' ) ],
				'skills'     => [ 'dashicons-awards', __( 'Skill tabs, the numbered grid and the industry tags.', 'jameskellie' ) ],
				'contact'    => [ 'dashicons-email-alt', __( 'The contact heading, phone and all of the enquiry form text.', 'jameskellie' ) ],
			],
			__( 'Advanced', 'jameskellie' )  => [
				'performance' => [ 'dashicons-performance', __( 'Speed tweaks for the front end. The defaults suit most sites.', 'jameskellie' ) ],
				'captcha'     => [ 'dashicons-shield', __( 'Spam protection for the contact form: Cloudflare Turnstile or Google reCAPTCHA.', 'jameskellie' ) ],
				'code'        => [ 'dashicons-editor-code', __( 'Tracking and verification snippets, such as Google Tag Manager.', 'jameskellie' ) ],
			],
		];
	}

	/**
	 * Current section from the page slug.
	 */
	public static function current_section(): string {
		$page    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.
		$section = str_starts_with( $page, 'jk-' ) ? substr( $page, 3 ) : 'general';
		return in_array( $section, Schema::option_sections(), true ) ? $section : 'general';
	}

	/**
	 * Mount point for the React app.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div class="wrap jk-admin"><h1 class="screen-reader-text">%s</h1><div id="jk-admin-root"><p class="tw:text-sm tw:text-neutral-500">%s</p></div></div>',
			esc_html( Schema::title( self::current_section() ) ),
			esc_html__( 'Loading theme settings…', 'jameskellie' )
		);
	}

	/**
	 * Which Home sections are switched on.
	 *
	 * @return array<string, bool>
	 */
	public static function enabled(): array {
		$out = [];
		foreach ( Schema::home_sections() as $section ) {
			$out[ $section ] = (bool) Settings::get( $section . '.enabled' );
		}
		return $out;
	}

	/**
	 * Everything the React app needs that does not change while it runs.
	 */
	public static function config(): array {
		$groups = [];
		foreach ( self::nav() as $label => $items ) {
			$group = [
				'label' => $label,
				'items' => [],
			];
			foreach ( $items as $section => [ $icon, $description ] ) {
				$group['items'][] = [
					'key'         => $section,
					'title'       => Schema::title( $section ),
					'icon'        => $icon,
					'description' => $description,
					'home'        => in_array( $section, Schema::home_sections(), true ),
				];
			}
			$groups[] = $group;
		}

		$fields = [];
		foreach ( Schema::option_sections() as $section ) {
			$fields[ $section ] = Schema::fields( $section );
		}

		$enquiries = wp_count_posts( Enquiries::POST_TYPE );
		$built     = ! empty( Assets::manifest() );
		$sizes     = Assets::bundle_sizes();

		return [
			'rest'       => esc_url_raw( rest_url( 'jk/v1/admin/' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'section'    => self::current_section(),
			'groups'     => $groups,
			'fields'     => $fields,
			'enabled'    => self::enabled(),
			'adminUrl'   => admin_url( 'admin.php' ),
			'homeUrl'    => home_url( '/' ),
			'enquiries'  => [
				'url'   => admin_url( 'edit.php?post_type=' . Enquiries::POST_TYPE ),
				'count' => (int) ( $enquiries->private ?? 0 ),
			],
			'unfiltered' => current_user_can( 'unfiltered_html' ),
			'revisions'  => defined( 'WP_POST_REVISIONS' ) ? var_export( WP_POST_REVISIONS, true ) : 'true', // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- display only.
			'status'     => [
				[ __( 'PHP', 'jameskellie' ), PHP_VERSION ],
				[ __( 'Object cache', 'jameskellie' ), wp_using_ext_object_cache() ? __( 'Persistent', 'jameskellie' ) : __( 'None', 'jameskellie' ) ],
				[
					__( 'Assets', 'jameskellie' ),
					Assets::dev_origin() ? __( 'Vite dev server', 'jameskellie' ) : ( $built ? __( 'Built', 'jameskellie' ) : __( 'Missing: run npm run build', 'jameskellie' ) ),
				],
				[ __( 'JavaScript', 'jameskellie' ), $built ? size_format( $sizes['js'], 1 ) : '—' ],
				[ __( 'CSS', 'jameskellie' ), $built ? size_format( $sizes['css'], 1 ) : '—' ],
			],
		];
	}

	/**
	 * Notices for resets on the appointment meta box (admin-post.php redirect).
	 */
	public static function notices(): void {
		$notice = isset( $_GET['jk_notice'] ) ? sanitize_key( wp_unslash( $_GET['jk_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( 'reset' === $notice ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Section reset to defaults.', 'jameskellie' ) );
		}
	}
}
