<?php
/**
 * Renders schema fields (HANDOFF §5.2). Styled by src/admin/admin.css (Tailwind, tw: prefix).
 *
 * @package jameskellie
 */

namespace JK\Admin;

use JK\Schema;
use JK\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Field type renderer.
 */
final class FieldRenderer {

	/**
	 * Types whose label is not tied to a single input.
	 */
	private const GROUP_TYPES = [ 'repeater', 'order', 'toggle', 'link', 'image', 'editor' ];

	/**
	 * Render a list of fields as label / control rows.
	 *
	 * @param array  $fields Field definitions.
	 * @param string $base   Input name base, e.g. jk_services.
	 * @param array  $values Current values.
	 */
	public static function table( array $fields, string $base, array $values ): void {
		echo '<div class="jk-fields tw:divide-y tw:divide-neutral-200">';
		foreach ( $fields as $field ) {
			$id    = self::id( $base . '-' . $field['key'] );
			$label = $field['label'] ?? $field['key'];
			$wide  = in_array( $field['type'], [ 'repeater', 'code', 'editor' ], true );

			printf(
				'<div class="tw:grid tw:gap-x-10 tw:gap-y-3 tw:py-6 %s">',
				$wide ? '' : 'tw:md:grid-cols-[minmax(0,240px)_minmax(0,1fr)]'
			);
			echo '<div class="tw:min-w-0">';
			if ( in_array( $field['type'], self::GROUP_TYPES, true ) ) {
				printf( '<span class="tw:block tw:text-[13px] tw:font-semibold tw:text-neutral-900">%s</span>', esc_html( $label ) );
			} else {
				printf( '<label class="tw:block tw:text-[13px] tw:font-semibold tw:text-neutral-900" for="%s">%s</label>', esc_attr( $id ), esc_html( $label ) );
			}
			if ( ! empty( $field['help'] ) ) {
				printf( '<p class="tw:mt-1 tw:mb-0 tw:text-xs tw:leading-relaxed tw:text-neutral-500">%s</p>', esc_html( $field['help'] ) );
			}
			echo '</div><div class="tw:min-w-0">';
			self::field( $field, $base . '[' . $field['key'] . ']', $values[ $field['key'] ] ?? null, $id, false );
			echo '</div></div>';
		}
		echo '</div>';
	}

	/**
	 * Render one field control.
	 *
	 * @param array  $field     Field definition.
	 * @param string $name      Input name.
	 * @param mixed  $value     Current value.
	 * @param string $id        Element id.
	 * @param bool   $with_help Print the help text under the control.
	 */
	public static function field( array $field, string $name, mixed $value, string $id, bool $with_help = true ): void {
		$type = $field['type'];

		switch ( $type ) {
			case 'text':
			case 'slug':
			case 'tel':
			case 'email':
				printf(
					'<input type="text" id="%s" name="%s" value="%s"%s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					'tel' === $type ? ' inputmode="tel"' : ( 'email' === $type ? ' inputmode="email"' : '' )
				);
				break;

			case 'url':
				printf( '<input type="text" inputmode="url" class="code" id="%s" name="%s" value="%s" placeholder="https://">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) (int) $value ),
					esc_attr( (string) ( $field['min'] ?? '' ) ),
					esc_attr( (string) ( $field['max'] ?? '' ) )
				);
				break;

			case 'textarea':
				printf( '<textarea rows="4" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				break;

			case 'list':
				printf(
					'<textarea rows="5" id="%s" name="%s">%s</textarea><p class="description">%s</p>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( implode( "\n", is_array( $value ) ? $value : [] ) ),
					esc_html__( 'One item per line.', 'jameskellie' )
				);
				break;

			case 'code':
				printf( '<textarea class="code jk-code" rows="8" id="%s" name="%s" data-jk-code>%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				if ( ! current_user_can( 'unfiltered_html' ) ) {
					echo '<p class="description">' . esc_html__( 'Your account cannot save raw HTML, so changes here will be ignored.', 'jameskellie' ) . '</p>';
				}
				break;

			case 'editor':
				wp_editor(
					(string) $value,
					str_replace( '-', '_', $id ),
					[
						'textarea_name' => $name,
						'teeny'         => true,
						'media_buttons' => false,
						'textarea_rows' => 8,
						'quicktags'     => [ 'buttons' => 'strong,em,link,ul,ol,li' ],
					]
				);
				break;

			case 'toggle':
				printf(
					'<input type="hidden" name="%1$s" value="0"><label class="jk-switch" for="%2$s"><input type="checkbox" id="%2$s" name="%1$s" value="1"%3$s><span class="jk-switch-ui" aria-hidden="true"></span><span class="jk-switch-text" data-on="%4$s" data-off="%5$s" aria-hidden="true"></span></label>',
					esc_attr( $name ),
					esc_attr( $id ),
					checked( (bool) $value, true, false ),
					esc_attr__( 'On', 'jameskellie' ),
					esc_attr__( 'Off', 'jameskellie' )
				);
				break;

			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['choices'] ?? [] as $key => $label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $key ), selected( (string) $value, (string) $key, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'link':
				$value = is_array( $value ) ? $value : [];
				printf(
					'<div class="jk-link"><label><span>%1$s</span><input type="text" name="%2$s[label]" value="%3$s"></label><label><span>%4$s</span><input type="text" inputmode="url" class="code" name="%2$s[url]" value="%5$s" placeholder="/page/ or #section"></label></div>',
					esc_html__( 'Label', 'jameskellie' ),
					esc_attr( $name ),
					esc_attr( (string) ( $value['label'] ?? '' ) ),
					esc_html__( 'Link', 'jameskellie' ),
					esc_attr( (string) ( $value['url'] ?? '' ) )
				);
				break;

			case 'image':
				self::image( $name, (int) $value, $id );
				break;

			case 'order':
				self::order( $field, $name, is_array( $value ) ? $value : [] );
				break;

			case 'repeater':
				self::repeater( $field, $name, is_array( $value ) ? $value : [], $id );
				break;
		}

		if ( $with_help && ! empty( $field['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $field['help'] ) );
		}
	}

	/**
	 * Media-library image picker.
	 *
	 * @param string $name  Input name.
	 * @param int    $value Attachment ID.
	 * @param string $id    Element id.
	 */
	private static function image( string $name, int $value, string $id ): void {
		$preview = $value ? wp_get_attachment_image( $value, 'thumbnail', false, [ 'alt' => '' ] ) : '';
		printf( '<div class="jk-image" data-jk-image><input type="hidden" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ? (string) $value : '' ) );
		echo '<div class="jk-image-preview" data-jk-image-preview>' . wp_kses_post( $preview ) . '</div>';
		echo '<div class="tw:flex tw:flex-wrap tw:gap-2">';
		printf( '<button type="button" class="jk-button jk-button-ghost" data-jk-image-choose>%s</button>', esc_html__( 'Choose image', 'jameskellie' ) );
		printf( '<button type="button" class="jk-button jk-button-ghost jk-button-danger" data-jk-image-remove%s>%s</button>', $value ? '' : ' hidden', esc_html__( 'Remove', 'jameskellie' ) );
		echo '</div></div>';
	}

	/**
	 * Sortable list of section keys.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name.
	 * @param array  $value Ordered keys.
	 */
	private static function order( array $field, string $name, array $value ): void {
		$choices = $field['choices'] ?? [];
		$keys    = array_values( array_unique( array_merge( array_intersect( $value, array_keys( $choices ) ), array_keys( $choices ) ) ) );

		echo '<ul class="jk-order" data-jk-sortable>';
		foreach ( $keys as $key ) {
			$hidden = Schema::has( $key ) && ! Settings::get( $key . '.enabled' );
			printf(
				'<li class="jk-order-item" draggable="true" data-jk-item><span class="jk-handle dashicons dashicons-menu" aria-hidden="true"></span><span class="jk-order-num" aria-hidden="true"></span><span class="jk-order-label">%1$s</span>%2$s<input type="hidden" name="%3$s[]" value="%4$s">%5$s</li>',
				esc_html( $choices[ $key ] ),
				$hidden ? '<span class="jk-order-tag">' . esc_html__( 'Hidden', 'jameskellie' ) . '</span>' : '',
				esc_attr( $name ),
				esc_attr( $key ),
				self::move_buttons( $choices[ $key ] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in move_buttons().
			);
		}
		echo '</ul>';
	}

	/**
	 * Up/down buttons for sortable items.
	 *
	 * @param string $label Item label for screen readers.
	 */
	private static function move_buttons( string $label ): string {
		return sprintf(
			'<span class="jk-move"><button type="button" class="jk-icon-btn" data-jk-up aria-label="%1$s"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button><button type="button" class="jk-icon-btn" data-jk-down aria-label="%2$s"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button></span>',
			/* translators: %s: row label */
			esc_attr( sprintf( __( 'Move %s up', 'jameskellie' ), $label ) ),
			/* translators: %s: row label */
			esc_attr( sprintf( __( 'Move %s down', 'jameskellie' ), $label ) )
		);
	}

	/**
	 * Repeater: rows with add, remove, drag and up/down, collapsible, with a max.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name base.
	 * @param array  $rows  Rows.
	 * @param string $id    Element id base.
	 */
	private static function repeater( array $field, string $name, array $rows, string $id ): void {
		$max = (int) ( $field['max'] ?? 0 );
		printf(
			'<div class="jk-repeater" data-jk-repeater data-name="%s" data-id="%s" data-max="%d" data-row-title="%s">',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( (string) $max ),
			esc_attr( (string) ( $field['row_title'] ?? '' ) )
		);
		echo '<div class="jk-rows" data-jk-rows data-jk-sortable>';
		foreach ( array_values( $rows ) as $i => $row ) {
			self::row( $field, $name, $id, (string) $i, is_array( $row ) ? $row : [] );
		}
		echo '</div>';

		echo '<template data-jk-template>';
		self::row( $field, $name, $id, '__i__', [], false );
		echo '</template>';

		printf(
			'<p class="jk-repeater-foot"><button type="button" class="jk-button jk-add" data-jk-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>%s</button><span>%s</span></p>',
			esc_html__( 'Add row', 'jameskellie' ),
			$max ? esc_html(
				/* translators: %d: maximum rows */
				sprintf( __( 'Up to %d. Drag to reorder.', 'jameskellie' ), $max )
			) : esc_html__( 'Drag to reorder.', 'jameskellie' )
		);
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param array  $field     Repeater definition.
	 * @param string $name      Name base.
	 * @param string $id        Id base.
	 * @param string $index     Row index or __i__.
	 * @param array  $row       Row values.
	 * @param bool   $collapsed Start collapsed.
	 */
	private static function row( array $field, string $name, string $id, string $index, array $row, bool $collapsed = true ): void {
		$title_key = $field['row_title'] ?? '';
		$title     = $title_key && isset( $row[ $title_key ] ) && is_scalar( $row[ $title_key ] ) ? (string) $row[ $title_key ] : '';
		foreach ( $field['fields'] as $sub ) {
			if ( $sub['key'] === $title_key && 'select' === $sub['type'] && isset( $sub['choices'][ $title ] ) ) {
				$title = (string) $sub['choices'][ $title ];
			}
		}
		$body_id = $id . '-' . $index . '-body';

		printf( '<div class="jk-row%s" data-jk-item>', $collapsed ? ' is-collapsed' : '' );
		echo '<div class="jk-row-head">';
		echo '<span class="jk-handle dashicons dashicons-menu" aria-hidden="true" draggable="true"></span>';
		printf(
			'<button type="button" class="jk-row-toggle" aria-expanded="%1$s" aria-controls="%2$s" data-jk-toggle><span class="jk-row-title" data-jk-row-title>%3$s</span></button>',
			$collapsed ? 'false' : 'true',
			esc_attr( $body_id ),
			esc_html( '' !== $title ? $title : __( '(new row)', 'jameskellie' ) )
		);
		echo self::move_buttons( '' !== $title ? $title : __( 'row', 'jameskellie' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in move_buttons().
		printf( '<button type="button" class="jk-remove" data-jk-remove>%s</button>', esc_html__( 'Remove', 'jameskellie' ) );
		echo '</div>';

		printf( '<div class="jk-row-body" id="%s">', esc_attr( $body_id ) );
		foreach ( $field['fields'] as $sub ) {
			$sub_id = $id . '-' . $index . '-' . $sub['key'];
			echo '<div class="jk-sub">';
			if ( in_array( $sub['type'], self::GROUP_TYPES, true ) ) {
				printf( '<span class="jk-sub-label">%s</span>', esc_html( $sub['label'] ?? $sub['key'] ) );
			} else {
				printf( '<label class="jk-sub-label" for="%s">%s</label>', esc_attr( $sub_id ), esc_html( $sub['label'] ?? $sub['key'] ) );
			}
			self::field( $sub, $name . '[' . $index . '][' . $sub['key'] . ']', $row[ $sub['key'] ] ?? null, $sub_id );
			echo '</div>';
		}
		echo '</div></div>';
	}

	/**
	 * HTML id from a name-ish string.
	 *
	 * @param string $raw Raw string.
	 */
	public static function id( string $raw ): string {
		return strtolower( (string) preg_replace( '/[^A-Za-z0-9_-]+/', '-', $raw ) );
	}
}
