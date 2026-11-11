<?php
/**
 * Loads config/fields.php and config/defaults.php once per request.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Schema and defaults access.
 */
final class Schema {

	/**
	 * Cached field schema.
	 *
	 * @var array<string, array>|null
	 */
	private static ?array $fields = null;

	/**
	 * Cached defaults.
	 *
	 * @var array<string, array>|null
	 */
	private static ?array $defaults = null;

	/**
	 * Every section schema, keyed by section.
	 *
	 * @return array<string, array>
	 */
	public static function all(): array {
		if ( null === self::$fields ) {
			self::$fields = require JK_DIR . '/config/fields.php';
		}
		return self::$fields;
	}

	/**
	 * Option-backed sections (everything except post-meta sections).
	 *
	 * @return string[]
	 */
	public static function option_sections(): array {
		return array_keys(
			array_filter(
				self::all(),
				static fn( array $s ): bool => 'meta' !== ( $s['storage'] ?? 'option' )
			)
		);
	}

	/**
	 * Home sections that can be ordered and rendered on the front page.
	 *
	 * @return string[]
	 */
	public static function home_sections(): array {
		return array_keys( self::field( 'layout', 'order' )['choices'] ?? [] );
	}

	/**
	 * Whether a section exists.
	 *
	 * @param string $section Section key.
	 */
	public static function has( string $section ): bool {
		return isset( self::all()[ $section ] );
	}

	/**
	 * Field definitions for a section.
	 *
	 * @param string $section Section key.
	 * @return array<int, array>
	 */
	public static function fields( string $section ): array {
		return self::all()[ $section ]['fields'] ?? [];
	}

	/**
	 * A single field definition.
	 *
	 * @param string $section Section key.
	 * @param string $key     Field key.
	 */
	public static function field( string $section, string $key ): ?array {
		foreach ( self::fields( $section ) as $field ) {
			if ( $field['key'] === $key ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Section title.
	 *
	 * @param string $section Section key.
	 */
	public static function title( string $section ): string {
		return (string) ( self::all()[ $section ]['title'] ?? $section );
	}

	/**
	 * Defaults for one section (or all of them).
	 *
	 * @param string|null $section Section key, or null for all.
	 */
	public static function defaults( ?string $section = null ): array {
		if ( null === self::$defaults ) {
			self::$defaults = require JK_DIR . '/config/defaults.php';
		}
		if ( null === $section ) {
			return self::$defaults;
		}
		return self::$defaults[ $section ] ?? [];
	}

	/**
	 * Option name for a section.
	 *
	 * @param string $section Section key.
	 */
	public static function option_name( string $section ): string {
		return 'jk_' . $section;
	}
}
