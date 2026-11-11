<?php
/**
 * Stops logged-out visitors from discovering usernames (a first step in password guessing).
 *
 * WordPress reveals them through:
 * - the REST API: /wp-json/wp/v2/users (and /users/{id})
 * - author archives: ?author=1 redirects to /author/{username}/
 * - the users sitemap: /wp-sitemap-users-1.xml
 * - login errors that say whether a username exists
 *
 * Logged-in users are unaffected (the block editor needs the users endpoint). The theme has
 * no author pages, so nothing visible changes for visitors. Turn it off with:
 *   add_filter( 'jk_block_user_enumeration', '__return_false' );
 *
 * @package jameskellie
 */

namespace JK;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Username enumeration hardening.
 */
final class UserEnumeration {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'boot' ] );
		// Sitemap providers are registered on init (priority 10), so this one cannot wait for boot().
		add_filter( 'wp_sitemaps_add_provider', [ $this, 'sitemap' ], 10, 2 );
	}

	/**
	 * Hook in unless switched off.
	 */
	public function boot(): void {
		/**
		 * Whether to hide usernames from logged-out visitors.
		 *
		 * @param bool $block Default true.
		 */
		if ( ! apply_filters( 'jk_block_user_enumeration', true ) ) {
			return;
		}
		add_filter( 'rest_endpoints', [ $this, 'rest_endpoints' ] );
		add_filter( 'rest_pre_dispatch', [ $this, 'rest_guard' ], 10, 3 );
		add_action( 'template_redirect', [ $this, 'author_archives' ], 1 );
		add_filter( 'login_errors', [ $this, 'login_errors' ] );
	}

	/**
	 * Remove the users routes from the REST index for logged-out visitors.
	 *
	 * @param array $endpoints REST endpoints.
	 */
	public function rest_endpoints( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( preg_match( '#^/wp/v2/users(/|$)#', (string) $route ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Belt and braces: refuse a users route even if something re-registered it.
	 *
	 * @param mixed            $result  Response so far (null to continue).
	 * @param \WP_REST_Server  $server  Server.
	 * @param \WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function rest_guard( $result, $server, $request ) {
		if ( null === $result && ! is_user_logged_in() && preg_match( '#^/wp/v2/users(/|$)#', (string) $request->get_route() ) ) {
			return new WP_Error( 'rest_no_route', __( 'No route was found matching the URL and request method.', 'jameskellie' ), [ 'status' => 404 ] );
		}
		return $result;
	}

	/**
	 * ?author=N and /author/{name}/ give a 404 to logged-out visitors (before WordPress's
	 * canonical redirect, which would reveal the username in the new URL).
	 */
	public function author_archives(): void {
		if ( is_user_logged_in() ) {
			return;
		}
		$by_id = isset( $_GET['author'] ) || isset( $_GET['author_name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
		if ( ! $by_id && ! is_author() ) {
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		remove_action( 'template_redirect', 'redirect_canonical' );
	}

	/**
	 * No users sitemap (it lists every author URL).
	 *
	 * @param mixed  $provider Sitemap provider.
	 * @param string $name     Provider name.
	 * @return mixed
	 */
	public function sitemap( $provider, string $name ) {
		if ( 'users' !== $name || ! apply_filters( 'jk_block_user_enumeration', true ) ) {
			return $provider;
		}
		return false;
	}

	/**
	 * One message for a wrong username or a wrong password, so neither confirms an account.
	 *
	 * @param string $error Login error HTML.
	 */
	public function login_errors( string $error ): string {
		// Keep other notices (e.g. "You are now logged out", captcha failures) as they are.
		$leaky = [ 'invalid_username', 'invalid_email', 'incorrect_password' ];
		global $errors;
		if ( $errors instanceof WP_Error && array_intersect( $leaky, $errors->get_error_codes() ) ) {
			return '<strong>' . esc_html__( 'Error:', 'jameskellie' ) . '</strong> ' . esc_html__( 'The username, email address or password is incorrect.', 'jameskellie' );
		}
		return $error;
	}
}
