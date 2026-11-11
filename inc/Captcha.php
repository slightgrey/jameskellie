<?php
/**
 * Contact-form captcha: Cloudflare Turnstile or Google reCAPTCHA (v2 checkbox or v3 invisible).
 * Settings: James Kellie → Captcha. The reCAPTCHA check is ported from the web-of-d theme.
 *
 * Emergency kill switch (e.g. a bad key blocks every enquiry): add
 * define( 'JK_CAPTCHA_DISABLED', true ); to wp-config.php.
 *
 * @package jameskellie
 */

namespace JK;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Provider selection, browser config and server-side verification.
 */
final class Captcha {

	/**
	 * Minimum reCAPTCHA v3 score.
	 */
	private const V3_MIN_SCORE = 0.5;

	/**
	 * The active provider: none, turnstile or recaptcha. A provider without both keys is off.
	 */
	public static function provider(): string {
		if ( defined( 'JK_CAPTCHA_DISABLED' ) && JK_CAPTCHA_DISABLED ) {
			return 'none';
		}
		$s        = Settings::section( 'captcha' );
		$provider = (string) ( $s['provider'] ?? 'none' );

		if ( 'turnstile' === $provider && '' !== (string) $s['turnstile_site'] && '' !== (string) $s['turnstile_secret'] ) {
			return 'turnstile';
		}
		if ( 'recaptcha' === $provider && '' !== (string) $s['recaptcha_site'] && '' !== (string) $s['recaptcha_secret'] ) {
			return 'recaptcha';
		}
		return 'none';
	}

	/**
	 * What the contact form needs in the browser (never a secret), or null when off.
	 *
	 * @return array{provider:string, siteKey:string, version:string}|null
	 */
	public static function client_config(): ?array {
		$provider = self::provider();
		if ( 'none' === $provider ) {
			return null;
		}
		$s = Settings::section( 'captcha' );
		return [
			'provider' => $provider,
			'siteKey'  => (string) ( 'turnstile' === $provider ? $s['turnstile_site'] : $s['recaptcha_site'] ),
			'version'  => 'recaptcha' === $provider ? ( 'v2' === $s['recaptcha_version'] ? 'v2' : 'v3' ) : '',
		];
	}

	/**
	 * Verify the token posted with the form.
	 *
	 * @param array  $params Raw (unslashed) form fields.
	 * @param string $ip     Visitor IP.
	 * @return true|WP_Error
	 */
	public static function verify( array $params, string $ip ) {
		$provider = self::provider();
		if ( 'none' === $provider ) {
			return true;
		}

		$field = 'turnstile' === $provider ? 'cf-turnstile-response' : 'g-recaptcha-response';
		$token = is_scalar( $params[ $field ] ?? null ) ? trim( (string) $params[ $field ] ) : '';
		if ( '' === $token ) {
			return new WP_Error( 'jk_captcha_missing', __( 'Please complete the spam check and try again.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		$s   = Settings::section( 'captcha' );
		$url = 'turnstile' === $provider
			? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
			: 'https://www.google.com/recaptcha/api/siteverify';

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 8,
				'body'    => [
					'secret'   => (string) ( 'turnstile' === $provider ? $s['turnstile_secret'] : $s['recaptcha_secret'] ),
					'response' => $token,
					'remoteip' => $ip,
				],
			]
		);

		$body = is_wp_error( $response ) ? null : json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			return new WP_Error( 'jk_captcha_failed', __( 'The spam check failed. Please try again.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		// v3 is invisible and scores every request; v2 tokens carry no score.
		if ( 'recaptcha' === $provider && isset( $body['score'] ) && (float) $body['score'] < self::V3_MIN_SCORE ) {
			return new WP_Error( 'jk_captcha_score', __( 'The spam check failed. Please try again, or give me a call.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		return true;
	}
}
