<?php
/**
 * Schema-driven sanitising (HANDOFF §5.2).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitises a whole section against its field schema.
 */
final class Sanitizer {

	/**
	 * Sanitise a section's submitted values.
	 *
	 * @param string $section  Section key.
	 * @param mixed  $input    Submitted values.
	 * @param array  $previous Previously stored values (used for `code` fields).
	 */
	public static function section( string $section, mixed $input, array $previous = [] ): array {
		$input = is_array( $input ) ? $input : [];
		$out   = [];

		foreach ( Schema::fields( $section ) as $field ) {
			$key = $field['key'];

			if ( 'code' === $field['type'] ) {
				$out[ $key ] = current_user_can( 'unfiltered_html' )
					? (string) ( $input[ $key ] ?? '' )
					: (string) ( $previous[ $key ] ?? '' );
				continue;
			}

			// Toggles post nothing when unchecked; everything else keeps "missing" as missing.
			if ( ! array_key_exists( $key, $input ) && 'toggle' !== $field['type'] ) {
				continue;
			}

			$out[ $key ] = self::field( $field, $input[ $key ] ?? null );
		}

		return $out;
	}

	/**
	 * Option sanitize_callback: keeps previous `code` values for users without unfiltered_html.
	 *
	 * @param string $section Section key.
	 * @param mixed  $input   Submitted values.
	 */
	public static function option( string $section, mixed $input ): array {
		$clean = self::section( $section, $input, Settings::raw( $section ) );
		Settings::flush();
		return $clean;
	}

	/**
	 * Sanitise one value by field type.
	 *
	 * @param array $field Field definition.
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public static function field( array $field, mixed $value ): mixed {
		switch ( $field['type'] ) {
			case 'text':
				return sanitize_text_field( self::str( $value ) );

			case 'slug':
				return sanitize_title( self::str( $value ) );

			case 'tel':
				return preg_replace( '/[^0-9+]/', '', self::str( $value ) );

			case 'textarea':
				return sanitize_textarea_field( self::str( $value ) );

			case 'editor':
				return wp_kses_post( self::str( $value ) );

			case 'url':
				return esc_url_raw( trim( self::str( $value ) ) );

			case 'email':
				$emails = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', self::str( $value ) ) ) ) );
				return implode( ', ', array_filter( $emails, 'is_email' ) );

			case 'toggle':
				return ! empty( $value ) && '0' !== $value;

			case 'number':
				$num = intval( $value );
				if ( isset( $field['min'] ) ) {
					$num = max( (int) $field['min'], $num );
				}
				if ( isset( $field['max'] ) ) {
					$num = min( (int) $field['max'], $num );
				}
				return $num;

			case 'image':
				return absint( $value );

			case 'link':
				$value = is_array( $value ) ? $value : [];
				return [
					'label' => sanitize_text_field( self::str( $value['label'] ?? '' ) ),
					'url'   => esc_url_raw( trim( self::str( $value['url'] ?? '' ) ) ),
				];

			case 'list':
				$items = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', self::str( $value ) );
				$items = array_map( static fn( $item ) => sanitize_text_field( self::str( $item ) ), (array) $items );
				return array_values( array_filter( $items, static fn( string $item ): bool => '' !== $item ) );

			case 'select':
				$value = self::str( $value );
				return array_key_exists( $value, $field['choices'] ?? [] ) ? $value : (string) array_key_first( $field['choices'] ?? [ '' => '' ] );

			case 'order':
				$known = array_keys( $field['choices'] ?? [] );
				$value = array_map( array( self::class, 'str' ), is_array( $value ) ? $value : [] );
				$order = array_values( array_unique( array_intersect( $value, $known ) ) );
				return array_values( array_unique( array_merge( $order, $known ) ) );

			case 'repeater':
				$rows = [];
				foreach ( is_array( $value ) ? $value : [] as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$clean = [];
					foreach ( $field['fields'] as $sub ) {
						$clean[ $sub['key'] ] = self::field( $sub, $row[ $sub['key'] ] ?? null );
					}
					$rows[] = $clean;
				}
				if ( ! empty( $field['max'] ) ) {
					$rows = array_slice( $rows, 0, (int) $field['max'] );
				}
				return array_values( $rows );

			case 'password':
				// Keep secrets as typed (sanitize_text_field would strip "<" or "%xx" from a password);
				// only drop control characters and surrounding whitespace.
				return trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', self::str( $value ) ) );

			default:
				return sanitize_text_field( self::str( $value ) );
		}
	}

	/**
	 * Cast scalars to string; anything else becomes ''.
	 * Input is expected to be unslashed already (options.php and the meta box both unslash).
	 *
	 * @param mixed $value Value.
	 */
	public static function str( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}
