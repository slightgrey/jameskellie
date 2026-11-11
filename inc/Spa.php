<?php
/**
 * Server side of SPA navigation.
 *
 * The React router requests the real URL with an X-JK-Spa: 1 header. WordPress runs
 * its normal main query (redirects, 404s, template conditionals), and instead of HTML
 * this returns the route data plus the <head> tags as JSON.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * JSON route responses.
 */
final class Spa {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'send_headers', [ $this, 'headers' ] );
		// After redirect_canonical (priority 10), so redirects still happen first.
		add_action( 'template_redirect', [ $this, 'respond' ], 99 );
	}

	/**
	 * Whether the current request came from the router.
	 */
	public static function is_spa_request(): bool {
		return isset( $_SERVER['HTTP_X_JK_SPA'] ) && '1' === $_SERVER['HTTP_X_JK_SPA'];
	}

	/**
	 * Caches must keep HTML and JSON responses apart.
	 */
	public function headers(): void {
		header( 'Vary: X-JK-Spa', false );
	}

	/**
	 * Print the JSON payload and stop.
	 */
	public function respond(): void {
		if ( ! self::is_spa_request() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		// The SEO tags, JSON-LD and title come from the same wp_head output as a full load.
		ob_start();
		wp_head();
		$head = (string) ob_get_clean();

		$payload = [
			'route'     => Boot::route(),
			'title'     => wp_get_document_title(),
			'head'      => $head,
			'bodyClass' => implode( ' ', array_map( 'sanitize_html_class', get_body_class() ) ),
		];

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON response.
		exit;
	}
}
