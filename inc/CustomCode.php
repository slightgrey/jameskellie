<?php
/**
 * Custom Code injection: head, after <body>, footer.
 *
 * Only users with unfiltered_html can save these fields (see Sanitizer), so the
 * output is printed raw by design.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the Custom Code settings.
 */
final class CustomCode {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_head', [ $this, 'head' ], 99 );
		add_action( 'wp_body_open', [ $this, 'body_open' ], 1 );
		add_action( 'wp_footer', [ $this, 'footer' ], 99 );
	}

	/**
	 * Head code.
	 */
	public function head(): void {
		echo $this->code( 'head' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored code (unfiltered_html only).
	}

	/**
	 * After <body> code.
	 */
	public function body_open(): void {
		echo $this->code( 'body_open' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored code (unfiltered_html only).
	}

	/**
	 * Footer code, optionally delayed until the first interaction.
	 */
	public function footer(): void {
		$code = $this->code( 'footer' );
		if ( '' === $code ) {
			return;
		}
		if ( Settings::get( 'performance.delay_code' ) ) {
			$code = self::delay( $code );
		}
		echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored code (unfiltered_html only).
	}

	/**
	 * A Custom Code field, trimmed.
	 *
	 * @param string $key Field key.
	 */
	private function code( string $key ): string {
		$code = trim( (string) Settings::get( 'code.' . $key, '' ) );
		return '' !== $code ? $code . "\n" : '';
	}

	/**
	 * Turn executable <script> tags into inert ones, plus a loader that runs them on
	 * the first scroll, click or keydown, or after 5 seconds.
	 *
	 * @param string $code HTML.
	 */
	public static function delay( string $code ): string {
		$code = preg_replace_callback(
			'/<script\b([^>]*)>/i',
			static function ( array $m ): string {
				$attrs = $m[1];
				if ( preg_match( '/\btype\s*=\s*([\'"])(?!text\/javascript|module|application\/javascript)[^\'"]*\1/i', $attrs ) ) {
					return $m[0]; // JSON-LD, templates and other non-JS types run as-is.
				}
				$attrs = preg_replace( '/\btype\s*=\s*([\'"])[^\'"]*\1/i', '', $attrs );
				return '<script type="text/jk-delay"' . $attrs . '>';
			},
			$code
		);

		$loader = '<script>(function(){var done=false,ev=["scroll","click","keydown","touchstart","pointermove"];function run(){if(done)return;done=true;ev.forEach(function(e){removeEventListener(e,run,{passive:true})});document.querySelectorAll(\'script[type="text/jk-delay"]\').forEach(function(old){var s=document.createElement("script");for(var i=0;i<old.attributes.length;i++){var a=old.attributes[i];if(a.name!=="type")s.setAttribute(a.name,a.value)}s.text=old.text;old.replaceWith(s)})}ev.forEach(function(e){addEventListener(e,run,{passive:true,once:true})});setTimeout(run,5000)})();</script>';

		return $code . $loader . "\n";
	}
}
