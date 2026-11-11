<?php
/**
 * Theme bootstrap.
 *
 * @package jameskellie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JK_VERSION', '2.0.0' );
define( 'JK_DIR', __DIR__ );

$jk_autoload = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $jk_autoload ) ) {
	require_once $jk_autoload;
} else {
	// Composer only holds dev tools, so the theme also runs without vendor/ (e.g. on live).
	spl_autoload_register(
		static function ( string $class_name ): void {
			if ( ! str_starts_with( $class_name, 'JK\\' ) ) {
				return;
			}
			$file = __DIR__ . '/inc/' . str_replace( '\\', '/', substr( $class_name, 3 ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

( new JK\Theme() )->boot();
