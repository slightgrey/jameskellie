<?php
/**
 * Front-end settings reader (HANDOFF §5.4).
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Reads jk_* options and appointment meta, merged with config/defaults.php.
 */
final class Settings {

	/**
	 * Merged sections, cached per request.
	 *
	 * @var array<string, array>
	 */
	private static array $cache = [];

	/**
	 * Get a value by dot path, e.g. 'services.items' or 'general.cta.url'.
	 *
	 * @param string $path     Dot path starting with the section key.
	 * @param mixed  $fallback Returned when the path does not resolve.
	 * @return mixed
	 */
	public static function get( string $path, mixed $fallback = null ): mixed {
		$parts   = explode( '.', $path );
		$section = array_shift( $parts );
		$value   = self::section( $section );

		foreach ( $parts as $part ) {
			if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
				return $fallback;
			}
			$value = $value[ $part ];
		}
		return $value;
	}

	/**
	 * A whole option-backed section, merged with defaults.
	 *
	 * @param string $section Section key.
	 */
	public static function section( string $section ): array {
		if ( ! isset( self::$cache[ $section ] ) ) {
			$saved                   = get_option( Schema::option_name( $section ), [] );
			self::$cache[ $section ] = self::merge( $section, is_array( $saved ) ? $saved : [] );
		}
		return self::$cache[ $section ];
	}

	/**
	 * Appointment page content for a post, merged with defaults.
	 *
	 * @param int $post_id Page ID.
	 */
	public static function appointment( int $post_id ): array {
		$key = 'appointment:' . $post_id;
		if ( ! isset( self::$cache[ $key ] ) ) {
			$saved               = get_post_meta( $post_id, '_jk_appointment', true );
			self::$cache[ $key ] = self::merge( 'appointment', is_array( $saved ) ? $saved : [] );
		}
		return self::$cache[ $key ];
	}

	/**
	 * Raw saved option, without defaults.
	 *
	 * @param string $section Section key.
	 */
	public static function raw( string $section ): array {
		$saved = get_option( Schema::option_name( $section ), [] );
		return is_array( $saved ) ? $saved : [];
	}

	/**
	 * Drop the request cache (after a save or reset).
	 */
	public static function flush(): void {
		self::$cache = [];
	}

	/**
	 * Merge saved values over defaults using the schema rules:
	 * scalars win when non-empty, toggles and numbers win when present,
	 * repeaters and lists win once the key exists (so rows can be deleted).
	 *
	 * @param string $section Section key.
	 * @param array  $saved   Saved values.
	 */
	public static function merge( string $section, array $saved ): array {
		$defaults = Schema::defaults( $section );
		$merged   = $defaults;

		foreach ( Schema::fields( $section ) as $field ) {
			$key = $field['key'];
			if ( ! array_key_exists( $key, $saved ) ) {
				continue;
			}
			$value = $saved[ $key ];

			switch ( $field['type'] ) {
				case 'toggle':
					$merged[ $key ] = (bool) $value;
					break;
				case 'number':
				case 'image':
					$merged[ $key ] = (int) $value;
					break;
				case 'repeater':
				case 'list':
				case 'order':
					$merged[ $key ] = is_array( $value ) ? $value : [];
					break;
				case 'link':
					$default        = is_array( $defaults[ $key ] ?? null ) ? $defaults[ $key ] : [];
					$value          = is_array( $value ) ? $value : [];
					$merged[ $key ] = [
						'label' => '' !== (string) ( $value['label'] ?? '' ) ? (string) $value['label'] : (string) ( $default['label'] ?? '' ),
						'url'   => '' !== (string) ( $value['url'] ?? '' ) ? (string) $value['url'] : (string) ( $default['url'] ?? '' ),
					];
					break;
				default:
					if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
						$merged[ $key ] = $value;
					}
			}
		}

		if ( 'layout' === $section ) {
			$merged['order'] = self::complete_order( $merged['order'] ?? [] );
		}

		return $merged;
	}

	/**
	 * Keep only known sections, in saved order, and append any missing ones.
	 *
	 * @param array $order Saved order.
	 * @return string[]
	 */
	private static function complete_order( array $order ): array {
		$known = Schema::home_sections();
		$order = array_values( array_intersect( array_map( 'strval', $order ), $known ) );
		return array_values( array_unique( array_merge( $order, $known ) ) );
	}
}
