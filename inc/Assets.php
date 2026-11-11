<?php
/**
 * Vite asset loading: dev server via /hot, production via the manifest.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the React front-end and admin bundles, fonts and preloads.
 */
final class Assets {

	private const MODULE_HANDLES = [ 'jk-vite-client', 'jk-app', 'jk-admin', 'jk-metabox' ];

	/**
	 * Critical fonts that get a preload.
	 */
	private const PRELOAD_FONTS = [ 'inter-latin-400-normal.woff2', 'inter-tight-latin-700-normal.woff2' ];

	/**
	 * Font faces: [family, file prefix, weights].
	 */
	private const FONTS = [
		[ 'Inter', 'inter', [ 400, 500, 600 ] ],
		[ 'Inter Tight', 'inter-tight', [ 500, 600, 700, 800 ] ],
		[ 'Roboto Condensed', 'roboto-condensed', [ 700 ] ],
	];

	/**
	 * Cached manifest.
	 *
	 * @var array|null
	 */
	private static ?array $manifest = null;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'front' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin' ] );
		add_filter( 'script_loader_tag', [ $this, 'module_tag' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'head_early' ], 1 );
		add_action( 'wp_head', [ $this, 'modulepreload' ], 8 );
		add_action( 'admin_head', [ $this, 'admin_preamble' ], 1 );
	}

	/**
	 * Dev server origin when /hot exists.
	 */
	public static function dev_origin(): ?string {
		$hot = get_theme_file_path( 'hot' );
		if ( ! is_readable( $hot ) ) {
			return null;
		}
		$origin = trim( (string) file_get_contents( $hot ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		return '' !== $origin ? untrailingslashit( $origin ) : null;
	}

	/**
	 * Parsed dist/.vite/manifest.json.
	 */
	public static function manifest(): array {
		if ( null === self::$manifest ) {
			$file           = get_theme_file_path( 'dist/.vite/manifest.json' );
			$json           = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			self::$manifest = is_array( $json ) ? $json : [];
		}
		return self::$manifest;
	}

	/**
	 * Front-end React app. Its data is inlined by index.php (Boot::print_data).
	 */
	public function front(): void {
		$this->enqueue_entry( 'src/main.jsx', 'jk-app' );
	}

	/**
	 * React Theme Settings app on the theme's screens; the meta box script in the page editor.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin( string $hook ): void {
		$screen  = get_current_screen();
		$is_jk   = str_contains( $hook, 'jk-' );
		$is_page = $screen && 'page' === $screen->post_type && in_array( $hook, [ 'post.php', 'post-new.php' ], true );

		if ( $is_jk ) {
			wp_enqueue_media();
			wp_enqueue_editor();
			$code = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );
			if ( $this->enqueue_entry( 'src/admin/main.jsx', 'jk-admin' ) ) {
				$config               = Admin\Menu::config();
				$config['codeEditor'] = $code;
				wp_add_inline_script( 'jk-admin', 'window.JKSettings=' . wp_json_encode( $config, Boot::JSON_FLAGS ) . ';', 'before' );
			}
			return;
		}

		if ( ! $is_page ) {
			return;
		}

		wp_enqueue_media();
		if ( $this->enqueue_entry( 'src/admin/metabox.js', 'jk-metabox' ) ) {
			wp_add_inline_script(
				'jk-metabox',
				'window.JKAdmin=' . wp_json_encode(
					[
						'codeEditor' => false,
						'i18n'       => [
							'choose'  => __( 'Choose image', 'jameskellie' ),
							'use'     => __( 'Use this image', 'jameskellie' ),
							'confirm' => __( 'Remove this row?', 'jameskellie' ),
							'max'     => __( 'Maximum number of rows reached.', 'jameskellie' ),
							'unsaved' => __( 'You have unsaved changes.', 'jameskellie' ),
						],
					]
				) . ';',
				'before'
			);
		}
	}

	/**
	 * React Fast Refresh preamble for the admin app while the Vite dev server runs.
	 */
	public function admin_preamble(): void {
		$dev    = self::dev_origin();
		$screen = get_current_screen();
		if ( $dev && $screen && str_contains( $screen->id, 'jk-' ) ) {
			self::print_preamble( $dev );
		}
	}

	/**
	 * Enqueue a Vite entry from the dev server or the manifest.
	 *
	 * @param string $entry  Source entry path, e.g. src/main.jsx.
	 * @param string $handle Script handle.
	 */
	private function enqueue_entry( string $entry, string $handle ): bool {
		$dev = self::dev_origin();

		if ( $dev ) {
			wp_enqueue_script( 'jk-vite-client', $dev . '/@vite/client', [], null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- dev server.
			wp_enqueue_script( $handle, $dev . '/' . $entry, [], null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- dev server.
			return true;
		}

		$manifest = self::manifest();
		if ( empty( $manifest[ $entry ]['file'] ) ) {
			return false;
		}

		$chunk = $manifest[ $entry ];
		$base  = get_theme_file_uri( 'dist/' );

		foreach ( self::entry_css( $entry ) as $i => $css ) {
			wp_enqueue_style( $handle . '-' . $i, $base . $css, [], null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- hashed filename.
		}
		// Module scripts are deferred by the browser, so they can sit in <head>.
		wp_enqueue_script( $handle, $base . $chunk['file'], [], null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- hashed filename.

		return true;
	}

	/**
	 * CSS files of an entry and its static imports.
	 *
	 * @param string $entry Manifest key.
	 * @return string[]
	 */
	private static function entry_css( string $entry ): array {
		$manifest = self::manifest();
		$css      = [];
		$seen     = [];
		$walk     = static function ( string $key ) use ( &$walk, &$css, &$seen, $manifest ): void {
			if ( isset( $seen[ $key ] ) || empty( $manifest[ $key ] ) ) {
				return;
			}
			$seen[ $key ] = true;
			foreach ( $manifest[ $key ]['css'] ?? [] as $file ) {
				$css[] = $file;
			}
			foreach ( $manifest[ $key ]['imports'] ?? [] as $import ) {
				$walk( $import );
			}
		};
		$walk( $entry );
		return array_values( array_unique( $css ) );
	}

	/**
	 * Print Vite bundles as ES modules.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 */
	public function module_tag( string $tag, string $handle ): string {
		if ( ! in_array( $handle, self::MODULE_HANDLES, true ) ) {
			return $tag;
		}
		$tag = preg_replace( '/\stype=([\'"])text\/javascript\1/', '', $tag );
		return preg_replace( '/<script(?![^>]*-js-(?:before|after|extra))(?=[^>]*\ssrc=)/', '<script type="module" crossorigin', $tag, 1 );
	}

	/**
	 * Swap no-js → js, React refresh preamble (dev), font faces and preloads.
	 */
	public function head_early(): void {
		echo "<script>document.documentElement.classList.replace('no-js','js');</script>\n";

		$dev = self::dev_origin();
		if ( $dev ) {
			self::print_preamble( $dev );
		}

		if ( Settings::get( 'performance.fonts_preload' ) ) {
			foreach ( self::PRELOAD_FONTS as $font ) {
				printf(
					'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
					esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) )
				);
			}
		}

		echo '<style id="jk-fonts">' . self::font_faces() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from constants and escaped URLs.
	}

	/**
	 * Print the @vitejs/plugin-react preamble.
	 *
	 * @param string $dev Dev server origin.
	 */
	private static function print_preamble( string $dev ): void {
		printf(
			"<script type=\"module\">import RefreshRuntime from '%s/@react-refresh';RefreshRuntime.injectIntoGlobalHook(window);window.\$RefreshReg\$=()=>{};window.\$RefreshSig\$=()=>(t)=>t;window.__vite_plugin_react_preamble_installed__=true;</script>\n",
			esc_url( $dev )
		);
	}

	/**
	 * @font-face rules for the self-hosted fonts.
	 */
	public static function font_faces(): string {
		$css = '';
		foreach ( self::FONTS as [ $family, $prefix, $weights ] ) {
			foreach ( $weights as $weight ) {
				$url  = esc_url( get_theme_file_uri( "assets/fonts/{$prefix}-latin-{$weight}-normal.woff2" ) );
				$css .= "@font-face{font-family:\"{$family}\";font-style:normal;font-weight:{$weight};font-display:swap;src:url({$url}) format(\"woff2\");}";
			}
		}
		return $css;
	}

	/**
	 * Print modulepreload for the front-end entry's static imports (production only).
	 */
	public function modulepreload(): void {
		if ( self::dev_origin() || Spa::is_spa_request() ) {
			return;
		}
		$manifest = self::manifest();
		$seen     = [];
		$walk     = static function ( string $key ) use ( &$walk, &$seen, $manifest ): void {
			foreach ( $manifest[ $key ]['imports'] ?? [] as $import ) {
				if ( isset( $seen[ $import ] ) || empty( $manifest[ $import ]['file'] ) ) {
					continue;
				}
				$seen[ $import ] = $manifest[ $import ]['file'];
				$walk( $import );
			}
		};
		$walk( 'src/main.jsx' );

		foreach ( $seen as $file ) {
			printf( '<link rel="modulepreload" href="%s" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'dist/' . $file ) ) );
		}
	}

	/**
	 * Built bundle sizes in bytes (for the Performance status panel).
	 *
	 * @return array{js:int, css:int}
	 */
	public static function bundle_sizes(): array {
		$sizes = [
			'js'  => 0,
			'css' => 0,
		];
		$files = [];
		foreach ( self::manifest() as $chunk ) {
			if ( ! empty( $chunk['file'] ) ) {
				$files[] = $chunk['file'];
			}
			foreach ( $chunk['css'] ?? [] as $css ) {
				$files[] = $css;
			}
		}
		foreach ( array_unique( $files ) as $file ) {
			$path = get_theme_file_path( 'dist/' . $file );
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$type = str_ends_with( $file, '.css' ) ? 'css' : 'js';
			if ( 'js' === $type && ! str_ends_with( $file, '.js' ) ) {
				continue;
			}
			$sizes[ $type ] += (int) filesize( $path );
		}
		return $sizes;
	}
}
