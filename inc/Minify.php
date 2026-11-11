<?php
/**
 * Minifies front-end HTML output: strips comments and collapses whitespace.
 *
 * Inline <style> is lightly minified (comments, whitespace). Inline JavaScript only loses
 * indentation, blank lines and whole-line comments (line breaks stay; see js()). JSON scripts
 * (React boot data, JSON-LD), <pre> and <textarea> are left byte-for-byte intact.
 * Whitespace runs in markup collapse to a single space (never removed), so rendering is
 * identical. Switched with Theme Settings → Performance → "Minify HTML", or force it with the
 * `jk_minify_html` filter. Ported from the web-of-d theme.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end HTML minifier.
 */
final class Minify {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		// Last on template_redirect: Spa::respond() (JSON) has already exited by then.
		add_action( 'template_redirect', [ $this, 'start' ], PHP_INT_MAX );
	}

	/**
	 * Buffer the page so it can be minified before it is sent.
	 */
	public function start(): void {
		if (
			is_admin()
			|| wp_doing_ajax()
			|| wp_is_json_request()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| Spa::is_spa_request()
			|| is_feed()
			|| is_robots()
			|| is_customize_preview()
		) {
			return;
		}

		/**
		 * Whether to minify front-end HTML.
		 *
		 * @param bool $minify Default: the "Minify HTML" switch in Theme Settings → Performance.
		 */
		if ( ! apply_filters( 'jk_minify_html', (bool) Settings::get( 'performance.minify_html' ) ) ) {
			return;
		}

		ob_start( [ self::class, 'html' ] );
	}

	/**
	 * Output-buffer callback. Returns the original HTML untouched if anything looks off.
	 *
	 * @param string $html Buffered page.
	 */
	public static function html( string $html ): string {
		// Only HTML documents (not XML sitemaps, JSON, files…).
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'content-type:' ) && false === stripos( $header, 'text/html' ) ) {
				return $html;
			}
		}
		if ( false === stripos( $html, '<html' ) ) {
			return $html;
		}

		// Inline CSS: drop comments and collapse whitespace. Inline JS is never rewritten.
		$out = preg_replace_callback(
			'#(<style\b[^>]*>)(.*?)(</style\s*>)#is',
			static function ( array $m ): string {
				$css = preg_replace( '#/\*.*?\*/#s', '', $m[2] );
				$css = null === $css ? $m[2] : preg_replace( '/\s+/', ' ', $css );
				$css = null === $css ? $m[2] : preg_replace( '/\s*([{};,])\s*/', '$1', $css );
				return $m[1] . trim( (string) $css ) . $m[3];
			},
			$html
		);
		if ( null === $out ) {
			return $html;
		}

		// Inline JS: line-level only (see self::js()), so line breaks and their meaning stay.
		$js  = preg_replace_callback(
			'#(<script\b([^>]*)>)(.*?)(</script\s*>)#is',
			static function ( array $m ): string {
				return $m[1] . self::js( $m[2], $m[3] ) . $m[4];
			},
			$out
		);
		$out = null === $js ? $out : $js;

		// Swap whitespace-sensitive blocks for placeholders so they survive untouched.
		$kept = [];
		$out  = preg_replace_callback(
			'#<(script|style|pre|textarea)\b[^>]*>.*?</\1\s*>#is',
			static function ( array $m ) use ( &$kept ): string {
				$kept[] = $m[0];
				return "\x00" . ( count( $kept ) - 1 ) . "\x00";
			},
			$out
		);

		// Remove comments, keeping IE conditional comments.
		$out = null === $out ? null : preg_replace( '/<!--(?!\[if|<!\[endif).*?-->/s', '', $out );

		// Collapse every whitespace run to one space.
		$out = null === $out ? null : preg_replace( '/\s+/', ' ', $out );

		// A regex failure (e.g. backtrack limit on a huge page) returns null: fall back to the original.
		if ( null === $out ) {
			return $html;
		}

		$out = preg_replace_callback(
			'/\x00(\d+)\x00/',
			static fn( array $m ): string => $kept[ (int) $m[1] ],
			$out
		);

		return null === $out ? $html : trim( $out );
	}

	/**
	 * Conservative inline-script minify: trim indentation, drop blank lines and whole-line
	 * `//` comments (including `//# sourceURL=`). Line breaks are kept, so automatic
	 * semicolon insertion and `return`/`throw` behave exactly as before.
	 *
	 * Skipped: external and non-JavaScript scripts (JSON boot data, JSON-LD, templates),
	 * and code with template literals or backslash line continuations, where a line that
	 * looks like a comment can be part of a string.
	 *
	 * @param string $attrs Attributes of the <script> tag.
	 * @param string $code  Script contents.
	 */
	private static function js( string $attrs, string $code ): string {
		if ( '' === trim( $code ) || preg_match( '/\ssrc\s*=/i', $attrs ) ) {
			return $code;
		}
		if ( preg_match( '/\stype\s*=\s*["\']?([^"\'\s>]+)/i', $attrs, $type ) ) {
			$js_types = [ 'text/javascript', 'application/javascript', 'module' ];
			if ( ! in_array( strtolower( $type[1] ), $js_types, true ) ) {
				return $code;
			}
		}
		if ( str_contains( $code, '`' ) || preg_match( '/\\\\\r?$/m', $code ) ) {
			return $code;
		}

		$lines = [];
		foreach ( preg_split( '/\r\n|\r|\n/', $code ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '//' ) ) {
				continue;
			}
			$lines[] = $line;
		}
		return implode( "\n", $lines );
	}
}
