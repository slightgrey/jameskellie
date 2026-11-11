<?php
/**
 * Applies the Performance tab toggles (HANDOFF §7). Never touches wp-admin.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end performance tweaks.
 */
final class Performance {

	/**
	 * Settings.
	 *
	 * @var array
	 */
	private array $opt = [];

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'apply' ], 1 );
	}

	/**
	 * Apply each enabled toggle.
	 */
	public function apply(): void {
		$this->opt = Settings::section( 'performance' );

		// Heartbeat applies to both sides: the admin interval, and front-end off.
		add_filter( 'heartbeat_settings', [ $this, 'heartbeat_settings' ] );

		if ( $this->opt['disable_xmlrpc'] ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', [ $this, 'remove_pingback_header' ] );
			add_filter( 'pings_open', '__return_false', 20 );
		}

		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( $this->opt['disable_emojis'] ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'tiny_mce_plugins', static fn( $plugins ) => is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [] );
			add_filter( 'emoji_svg_url', '__return_false' );
			add_filter( 'wp_resource_hints', [ $this, 'remove_emoji_prefetch' ], 10, 2 );
		}

		if ( $this->opt['disable_oembed'] ) {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			remove_action( 'rest_api_init', 'wp_oembed_register_route' );
			add_filter( 'embed_oembed_discover', '__return_false' );
			add_action( 'wp_footer', static fn() => wp_deregister_script( 'wp-embed' ), 1 );
		}

		if ( $this->opt['clean_head'] ) {
			remove_action( 'wp_head', 'wp_generator' );
			remove_action( 'wp_head', 'rsd_link' );
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		}

		if ( ! $this->opt['feed_links'] ) {
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}

		if ( ! $this->opt['heartbeat_front'] ) {
			add_action( 'wp_enqueue_scripts', static fn() => wp_deregister_script( 'heartbeat' ), 99 );
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue' ], 100 );
		add_filter( 'wp_resource_hints', [ $this, 'preconnect' ], 10, 2 );
	}

	/**
	 * Dequeue block CSS, Dashicons and jQuery as configured.
	 */
	public function dequeue(): void {
		if ( $this->opt['remove_block_css'] ) {
			foreach ( [ 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'core-block-supports' ] as $handle ) {
				wp_dequeue_style( $handle );
			}
		}

		if ( $this->opt['disable_dashicons'] && ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
		}

		if ( $this->opt['remove_jquery'] && ! is_admin_bar_showing() ) {
			$dependents = $this->jquery_dependents();
			if ( empty( $dependents ) ) {
				wp_dequeue_script( 'jquery' );
				wp_deregister_script( 'jquery' );
			} else {
				// Query Monitor shows this in its Logs panel; harmless without it.
				do_action( 'qm/warning', 'JK Performance: jQuery kept, needed by ' . implode( ', ', $dependents ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Query Monitor hook.
			}
		}
	}

	/**
	 * Queued script handles that depend on jQuery, directly or indirectly.
	 *
	 * @return string[]
	 */
	private function jquery_dependents(): array {
		$scripts = wp_scripts();
		$found   = [];
		$jquery  = [ 'jquery', 'jquery-core', 'jquery-migrate' ];

		$depends = static function ( string $handle, array $seen = [] ) use ( &$depends, $scripts, $jquery ): bool {
			if ( isset( $seen[ $handle ] ) || ! isset( $scripts->registered[ $handle ] ) ) {
				return false;
			}
			$seen[ $handle ] = true;
			foreach ( $scripts->registered[ $handle ]->deps as $dep ) {
				if ( in_array( $dep, $jquery, true ) || $depends( $dep, $seen ) ) {
					return true;
				}
			}
			return false;
		};

		foreach ( $scripts->queue as $handle ) {
			if ( ! in_array( $handle, $jquery, true ) && $depends( $handle ) ) {
				$found[] = $handle;
			}
		}
		return $found;
	}

	/**
	 * Heartbeat interval in admin.
	 *
	 * @param array $settings Heartbeat settings.
	 */
	public function heartbeat_settings( array $settings ): array {
		if ( is_admin() ) {
			$settings['interval'] = max( 15, min( 120, (int) $this->opt['heartbeat_admin'] ) );
		}
		return $settings;
	}

	/**
	 * Drop X-Pingback.
	 *
	 * @param array $headers Headers.
	 */
	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Remove the emoji CDN dns-prefetch.
	 *
	 * @param array  $urls Hint URLs.
	 * @param string $type Relation type.
	 */
	public function remove_emoji_prefetch( array $urls, string $type ): array {
		if ( 'dns-prefetch' !== $type ) {
			return $urls;
		}
		return array_values(
			array_filter(
				$urls,
				static fn( $url ) => ! is_string( $url ) || ! str_contains( $url, 's.w.org/images/core/emoji' )
			)
		);
	}

	/**
	 * Preconnect origins from settings.
	 *
	 * @param array  $urls Hint URLs.
	 * @param string $type Relation type.
	 */
	public function preconnect( array $urls, string $type ): array {
		if ( 'preconnect' !== $type ) {
			return $urls;
		}
		foreach ( (array) $this->opt['preconnect'] as $origin ) {
			$origin = esc_url_raw( (string) $origin );
			if ( $origin ) {
				$urls[] = [
					'href'        => $origin,
					'crossorigin' => 'anonymous',
				];
			}
		}
		return $urls;
	}
}
