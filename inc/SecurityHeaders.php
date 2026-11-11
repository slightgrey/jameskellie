<?php
/**
 * Basic hardening headers on front-end responses.
 *
 * - X-Frame-Options: SAMEORIGIN: other sites cannot frame the site (clickjacking). The admin
 *   live preview frames it from the same origin, which is still allowed.
 * - X-Content-Type-Options: nosniff: browsers do not guess file types.
 * - Referrer-Policy: only the origin is sent to other sites, never full URLs.
 *
 * wp-admin and wp-login.php already send their own. Change or remove any with the
 * `jk_security_headers` filter; a host or CDN setting the same headers is fine too.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end security headers.
 */
final class SecurityHeaders {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'send_headers', [ $this, 'send' ] );
	}

	/**
	 * Send the headers (skipped when headers are already out, e.g. in CLI).
	 */
	public function send(): void {
		if ( headers_sent() ) {
			return;
		}

		/**
		 * Front-end security headers: name => value. Return '' for a header to skip it.
		 *
		 * @param array<string, string> $headers Headers.
		 */
		$headers = (array) apply_filters(
			'jk_security_headers',
			[
				'X-Frame-Options'        => 'SAMEORIGIN',
				'X-Content-Type-Options' => 'nosniff',
				'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			]
		);

		foreach ( $headers as $name => $value ) {
			$name  = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $name );
			$value = str_replace( [ "\r", "\n" ], '', (string) $value );
			if ( '' !== $name && '' !== $value ) {
				header( $name . ': ' . $value );
			}
		}
	}
}
