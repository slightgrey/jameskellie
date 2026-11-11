<?php
/**
 * POST /wp-json/jk/v1/contact (HANDOFF §9), plus an admin-post.php fallback for no-JS.
 *
 * @package jameskellie
 */

namespace JK\Rest;

use JK\Captcha;
use JK\Enquiries;
use JK\Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Contact form handler.
 */
final class ContactController {

	private const RATE_LIMIT    = 5;  // Sent messages per IP per window.
	private const ATTEMPT_LIMIT = 15; // Attempts (incl. failed validation or captcha) per IP per window.
	private const RATE_WINDOW   = 600;
	private const MIN_SECONDS   = 3;
	private const NOJS_NONCE    = 'jk_contact';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
		add_action( 'admin_post_nopriv_jk_contact', [ $this, 'no_js' ] );
		add_action( 'admin_post_jk_contact', [ $this, 'no_js' ] );
	}

	/**
	 * Register the route.
	 */
	public function routes(): void {
		register_rest_route(
			'jk/v1',
			'/contact',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest' ],
				// Public form: protected by the nonce check, honeypot, timing, captcha and rate limit.
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest( WP_REST_Request $request ) {
		if ( ! wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'jk_bad_nonce', __( 'Your session expired. Please reload the page and try again.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		$result = self::handle( is_array( $params ) ? $params : [], (float) ( $params['elapsed'] ?? 0 ) );

		return is_wp_error( $result ) ? $result : new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	/**
	 * No-JS fallback: the form posts to admin-post.php and is redirected back.
	 */
	public function no_js(): void {
		$back = wp_get_referer();
		$back = $back ? remove_query_arg( [ 'jk_sent', 'jk_error' ], $back ) : home_url( '/' );

		if ( ! isset( $_POST['_jk_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_jk_nonce'] ) ), self::NOJS_NONCE ) ) {
			wp_safe_redirect( add_query_arg( 'jk_error', '1', $back ) . '#contact' );
			exit;
		}

		$params  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in handle().
		$elapsed = time() - (int) ( $params['jk_ts'] ?? time() );
		$result  = self::handle( $params, (float) $elapsed );

		wp_safe_redirect( add_query_arg( is_wp_error( $result ) ? 'jk_error' : 'jk_sent', '1', $back ) . '#contact' );
		exit;
	}

	/**
	 * Nonce action for the no-JS form.
	 */
	public static function nojs_nonce_action(): string {
		return self::NOJS_NONCE;
	}

	/**
	 * Validate, spam-check, rate-limit, mail and store.
	 *
	 * @param array $params  Raw (unslashed) fields.
	 * @param float $elapsed Seconds the visitor spent on the form.
	 * @return true|WP_Error
	 */
	public static function handle( array $params, float $elapsed ) {
		$str = static fn( string $key ): string => is_scalar( $params[ $key ] ?? null ) ? (string) $params[ $key ] : '';

		// Spam: honeypot and minimum fill time.
		if ( '' !== trim( $str( 'website' ) ) || $elapsed < self::MIN_SECONDS ) {
			return new WP_Error( 'jk_spam', __( 'Your message could not be sent.', 'jameskellie' ), [ 'status' => 400 ] );
		}

		// Rate limits per IP per 10 minutes: 5 sent messages, and ATTEMPT_LIMIT attempts of any kind.
		// Counting attempts too stops a bot from failing the captcha endlessly, since each failed
		// try would otherwise cost an outbound call to Cloudflare/Google.
		$ip_hash     = md5( self::client_ip() . wp_salt( 'nonce' ) );
		$ip_key      = 'jk_rl_' . $ip_hash;
		$attempt_key = 'jk_ra_' . $ip_hash;
		$count       = (int) get_transient( $ip_key );
		$attempts    = (int) get_transient( $attempt_key );
		if ( $count >= self::RATE_LIMIT || $attempts >= self::ATTEMPT_LIMIT ) {
			return new WP_Error( 'jk_rate_limited', __( 'Too many messages. Please try again in a few minutes, or give me a call.', 'jameskellie' ), [ 'status' => 429 ] );
		}
		set_transient( $attempt_key, $attempts + 1, self::RATE_WINDOW );

		$topics = array_values( (array) Settings::get( 'contact.topics', [] ) );
		$topic  = sanitize_text_field( $str( 'topic' ) );
		if ( ! in_array( $topic, $topics, true ) ) {
			$topic = (string) ( $topics[0] ?? '' );
		}

		$data = [
			'topic'   => $topic,
			'name'    => sanitize_text_field( $str( 'name' ) ),
			'email'   => sanitize_email( $str( 'email' ) ),
			'phone'   => mb_substr( trim( (string) preg_replace( '/[^0-9 +]/', '', $str( 'phone' ) ) ), 0, 32 ),
			'message' => sanitize_textarea_field( $str( 'message' ) ),
			// Only a page of this site, and not absurdly long; anything else is dropped.
			'page'    => self::own_url( $str( 'page' ) ),
		];

		$errors = [];
		$len    = static fn( string $s ): int => mb_strlen( $s );
		if ( $len( $data['name'] ) < 2 || $len( $data['name'] ) > 100 ) {
			$errors['name'] = __( 'Please enter your name (2–100 characters).', 'jameskellie' );
		}
		if ( ! is_email( $data['email'] ) ) {
			$errors['email'] = __( 'Please enter a valid email address.', 'jameskellie' );
		}
		if ( $len( $data['message'] ) < 10 || $len( $data['message'] ) > 5000 ) {
			$errors['message'] = __( 'Please enter a message (10–5000 characters).', 'jameskellie' );
		}
		if ( $errors ) {
			return new WP_Error(
				'jk_invalid',
				__( 'Please check the highlighted fields.', 'jameskellie' ),
				[
					'status' => 422,
					'fields' => $errors,
				]
			);
		}

		// Turnstile or reCAPTCHA, as selected under James Kellie → Captcha.
		$verified = Captcha::verify( $params, self::client_ip() );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		set_transient( $ip_key, $count + 1, self::RATE_WINDOW );

		$sent = self::send( $data );
		Enquiries::store( $data, $sent );

		return true;
	}

	/**
	 * Send the notification email.
	 *
	 * @param array $data Sanitised fields.
	 */
	private static function send( array $data ): bool {
		$to = array_filter( array_map( 'trim', explode( ',', (string) Settings::get( 'general.emails' ) ) ), 'is_email' );
		if ( ! $to ) {
			$to = [ (string) get_option( 'admin_email' ) ];
		}

		/* translators: 1: topic, 2: name */
		$subject = sprintf( __( 'New enquiry — %1$s — %2$s', 'jameskellie' ), $data['topic'], $data['name'] );
		$body    = implode(
			"\n",
			[
				__( 'Topic:', 'jameskellie' ) . ' ' . $data['topic'],
				__( 'Name:', 'jameskellie' ) . ' ' . $data['name'],
				__( 'Email:', 'jameskellie' ) . ' ' . $data['email'],
				__( 'Phone:', 'jameskellie' ) . ' ' . ( '' !== $data['phone'] ? $data['phone'] : '—' ),
				__( 'Page:', 'jameskellie' ) . ' ' . ( '' !== $data['page'] ? $data['page'] : home_url( '/' ) ),
				'',
				$data['message'],
			]
		);
		$headers = [
			'Content-Type: text/plain; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', str_replace( [ "\r", "\n", '<', '>' ], '', $data['name'] ), $data['email'] ),
		];

		return (bool) wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * The "sent from" URL, kept only when it is a page of this site (it comes from the visitor's
	 * browser, so it could otherwise point anywhere, e.g. a phishing page shown in the admin).
	 *
	 * @param string $url Raw URL.
	 */
	private static function own_url( string $url ): string {
		$url = esc_url_raw( trim( $url ), [ 'http', 'https' ] );
		if ( '' === $url || strlen( $url ) > 500 ) {
			return '';
		}
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		return strtolower( $host ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ? $url : '';
	}

	/**
	 * Visitor IP (REMOTE_ADDR; filterable for proxies such as Cloudflare).
	 */
	private static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return (string) apply_filters( 'jk_client_ip', $ip );
	}
}
