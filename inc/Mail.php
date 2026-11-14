<?php
/**
 * Outgoing mail: PHP mail or SMTP, plus the From address, set under James Kellie → Mail
 * (option jk_mail). No plugin needed.
 *
 * Constants in wp-config.php override the settings (handy for keeping the password out of the
 * database). When JK_SMTP_HOST is defined, SMTP is used whatever the setting says:
 *   JK_SMTP_HOST, JK_SMTP_PORT, JK_SMTP_SECURE ('tls', 'ssl' or ''), JK_SMTP_USER, JK_SMTP_PASS,
 *   JK_MAIL_FROM, JK_MAIL_FROM_NAME
 *
 * @package jameskellie
 */

namespace JK;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Mail transport.
 */
final class Mail {

	/**
	 * Last wp_mail failure message in this request.
	 *
	 * @var string
	 */
	private static string $last_error = '';

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'phpmailer_init', [ $this, 'smtp' ] );
		add_filter( 'wp_mail_from', [ $this, 'from' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'from_name' ] );
		add_action( 'wp_mail_failed', [ self::class, 'failed' ] );
	}

	/**
	 * Effective settings: the Mail section, with wp-config constants taking priority.
	 *
	 * @return array{mailer:string, host:string, port:int, secure:string, user:string, pass:string, from:string, from_name:string}
	 */
	public static function config(): array {
		$s = Settings::section( 'mail' );

		$c = [
			'mailer'    => (string) ( $s['mailer'] ?? 'phpmail' ),
			'host'      => trim( (string) ( $s['smtp_host'] ?? '' ) ),
			'port'      => (int) ( $s['smtp_port'] ?? 587 ),
			'secure'    => (string) ( $s['smtp_secure'] ?? 'tls' ),
			'user'      => trim( (string) ( $s['smtp_user'] ?? '' ) ),
			'pass'      => (string) ( $s['smtp_pass'] ?? '' ),
			'from'      => trim( (string) ( $s['from_email'] ?? '' ) ),
			'from_name' => trim( (string) ( $s['from_name'] ?? '' ) ),
		];

		$constants = [
			'JK_SMTP_HOST'      => 'host',
			'JK_SMTP_PORT'      => 'port',
			'JK_SMTP_SECURE'    => 'secure',
			'JK_SMTP_USER'      => 'user',
			'JK_SMTP_PASS'      => 'pass',
			'JK_MAIL_FROM'      => 'from',
			'JK_MAIL_FROM_NAME' => 'from_name',
		];
		foreach ( $constants as $constant => $key ) {
			if ( defined( $constant ) ) {
				$c[ $key ] = 'port' === $key ? (int) constant( $constant ) : (string) constant( $constant );
			}
		}
		if ( defined( 'JK_SMTP_HOST' ) && '' !== (string) constant( 'JK_SMTP_HOST' ) ) {
			$c['mailer'] = 'smtp';
		}
		if ( 'none' === $c['secure'] ) {
			$c['secure'] = '';
		}
		if ( $c['port'] < 1 ) {
			$c['port'] = 587;
		}
		return $c;
	}

	/**
	 * Switch PHPMailer to SMTP when selected and a host is set.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer Mailer.
	 */
	public function smtp( $mailer ): void {
		$c = self::config();
		if ( 'smtp' !== $c['mailer'] || '' === $c['host'] ) {
			return;
		}
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer's API.
		$mailer->isSMTP();
		$mailer->Host        = $c['host'];
		$mailer->Port        = $c['port'];
		$mailer->SMTPSecure  = $c['secure'];
		$mailer->SMTPAutoTLS = '' !== $c['secure'];
		$mailer->Timeout     = 15;
		if ( '' !== $c['user'] ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = $c['user'];
			$mailer->Password = $c['pass'];
		}
		// phpcs:enable
	}

	/**
	 * From address: the setting, else the SMTP username if it is an email (when using SMTP),
	 * else WordPress's default.
	 *
	 * @param string $from Default.
	 */
	public function from( string $from ): string {
		$c = self::config();
		if ( is_email( $c['from'] ) ) {
			return $c['from'];
		}
		if ( 'smtp' === $c['mailer'] && is_email( $c['user'] ) ) {
			return $c['user'];
		}
		return $from;
	}

	/**
	 * From name: the setting, else the site title.
	 *
	 * @param string $name Default ("WordPress").
	 */
	public function from_name( string $name ): string {
		$c = self::config();
		if ( '' !== $c['from_name'] ) {
			return $c['from_name'];
		}
		$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
		return '' !== $site ? $site : $name;
	}

	/**
	 * Remember why wp_mail failed.
	 *
	 * @param WP_Error $error Error.
	 */
	public static function failed( WP_Error $error ): void {
		self::$last_error = $error->get_error_message();
	}

	/**
	 * Clear the remembered error before a send.
	 */
	public static function reset(): void {
		self::$last_error = '';
	}

	/**
	 * Last failure message, or ''.
	 */
	public static function last_error(): string {
		return self::$last_error;
	}

	/**
	 * Send a test email with the saved settings.
	 *
	 * @param string $to Recipient (valid email).
	 * @return array{to:string, mailer:string}|WP_Error
	 */
	public static function send_test( string $to ) {
		$c      = self::config();
		$mailer = 'smtp' === $c['mailer'] && '' !== $c['host']
			/* translators: 1: SMTP host, 2: port */
			? sprintf( __( 'SMTP (%1$s:%2$d)', 'jameskellie' ), $c['host'], $c['port'] )
			: __( 'PHP mail', 'jameskellie' );

		/* translators: %s: site title */
		$subject = sprintf( __( 'Test email from %s', 'jameskellie' ), wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) );
		$body    = implode(
			"\n",
			[
				__( 'This is a test email from James Kellie → Mail.', 'jameskellie' ),
				'',
				/* translators: %s: PHP mail or SMTP (host:port) */
				sprintf( __( 'Sent with: %s', 'jameskellie' ), $mailer ),
				/* translators: %s: site URL */
				sprintf( __( 'Site: %s', 'jameskellie' ), home_url( '/' ) ),
				/* translators: %s: date and time */
				sprintf( __( 'Time: %s', 'jameskellie' ), wp_date( 'j M Y, g:i:sa' ) ),
				'',
				__( 'If you can read this, enquiry notifications will arrive too.', 'jameskellie' ),
			]
		);

		self::reset();
		$sent = wp_mail( $to, $subject, $body, [ 'Content-Type: text/plain; charset=UTF-8' ] );
		if ( ! $sent ) {
			$error = '' !== self::$last_error ? self::$last_error : __( 'Unknown error.', 'jameskellie' );
			return new WP_Error(
				'jk_mail_failed',
				/* translators: 1: PHP mail or SMTP (host:port), 2: error message */
				sprintf( __( 'The test email could not be sent with %1$s: %2$s', 'jameskellie' ), $mailer, $error ),
				[ 'status' => 500 ]
			);
		}
		return [
			'to'     => $to,
			'mailer' => $mailer,
		];
	}
}
