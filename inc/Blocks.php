<?php
/**
 * The Home sections as block editor blocks (jameskellie/hero … jameskellie/contact).
 *
 * Each block keeps its own field values in the `data` attribute (same keys as
 * config/fields.php). They are dynamic data blocks: React renders them on the front end
 * from Boot::route() → `blocks`. The render callback here is only the plain fallback for
 * feeds, excerpts and <noscript>. Block data is sanitised on read, because core does not
 * sanitise block attributes stored in post_content.
 *
 * @package jameskellie
 */

namespace JK;

use JK\Admin\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Block registration, editor config and block data helpers.
 */
final class Blocks {

	public const PREFIX = 'jameskellie/';

	private const CATEGORY = 'jameskellie';

	private const HANDLE = 'jk-blocks';

	/**
	 * Dashicon per block.
	 */
	private const ICONS = [
		'hero'       => 'cover-image',
		'stats'      => 'chart-bar',
		'services'   => 'grid-view',
		'experience' => 'list-view',
		'cta'        => 'megaphone',
		'skills'     => 'tag',
		'contact'    => 'email',
	];

	/**
	 * Inserter descriptions.
	 */
	private const DESCRIPTIONS = [
		'hero'       => 'Eyebrow, H1 title, intro, buttons and portrait.',
		'stats'      => 'Up to four big numbers with labels.',
		'services'   => 'Services as tabs (accordion on mobile).',
		'experience' => 'Roles as an accordion.',
		'cta'        => 'Service tiles and a round call-to-action button.',
		'skills'     => 'Skill tabs and an industries list.',
		'contact'    => 'Phone, booking button and the enquiry form.',
	];

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'block_categories_all', [ $this, 'category' ] );
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor' ] );
	}

	/**
	 * The "James Kellie" inserter category, first in the list.
	 *
	 * @param array $categories Block categories.
	 */
	public function category( array $categories ): array {
		array_unshift(
			$categories,
			[
				'slug'  => self::CATEGORY,
				'title' => __( 'James Kellie', 'jameskellie' ),
				'icon'  => null,
			]
		);
		return $categories;
	}

	/**
	 * Register the editor bundle and one block per Home section.
	 */
	public function register(): void {
		$js  = get_theme_file_path( 'dist/blocks/editor.js' );
		$css = get_theme_file_path( 'dist/blocks/editor.css' );

		if ( is_readable( $js ) ) {
			wp_register_script(
				self::HANDLE,
				get_theme_file_uri( 'dist/blocks/editor.js' ),
				[ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-data', 'wp-i18n', 'wp-api-fetch', 'react', 'react-jsx-runtime', 'media-editor' ],
				JK_VERSION . '-' . filemtime( $js ),
				true
			);
		}
		if ( is_readable( $css ) ) {
			wp_register_style( self::HANDLE, get_theme_file_uri( 'dist/blocks/editor.css' ), [ 'wp-components' ], JK_VERSION . '-' . filemtime( $css ) );
		}

		foreach ( Boot::PREVIEW_SECTIONS as $key ) {
			register_block_type(
				self::PREFIX . $key,
				[
					'api_version'     => 3,
					'title'           => Schema::title( $key ),
					'description'     => self::DESCRIPTIONS[ $key ] ?? '',
					'category'        => self::CATEGORY,
					'icon'            => self::ICONS[ $key ] ?? 'layout',
					'keywords'        => [ 'james kellie', 'section', $key ],
					'editor_script'   => is_readable( $js ) ? self::HANDLE : null,
					'editor_style'    => is_readable( $css ) ? self::HANDLE : null,
					'attributes'      => [
						'data' => [
							'type'    => 'object',
							'default' => [],
						],
					],
					'supports'        => [
						'html'     => false,
						'reusable' => true,
					],
					'render_callback' => static fn( array $attributes ): string => self::fallback( $key, $attributes ),
				]
			);
		}
	}

	/**
	 * Config for the editor bundle: schema, Theme Settings values to start from, image thumbnails.
	 */
	public function editor(): void {
		if ( ! wp_script_is( self::HANDLE, 'registered' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_editor();
		// The field modal lives in the editor window (editor_style only reaches the canvas iframe).
		if ( wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_enqueue_style( self::HANDLE );
		}

		$schema   = [];
		$defaults = [];
		$images   = [];

		foreach ( Boot::PREVIEW_SECTIONS as $key ) {
			$fields           = self::fields( $key );
			$schema[ $key ]   = [
				'title'  => Schema::title( $key ),
				'fields' => $fields,
			];
			$values           = array_intersect_key( Settings::section( $key ), array_flip( wp_list_pluck( $fields, 'key' ) ) );
			$defaults[ $key ] = (object) $values;

			foreach ( $fields as $field ) {
				if ( 'image' === $field['type'] && ! empty( $values[ $field['key'] ] ) ) {
					$id  = (int) $values[ $field['key'] ];
					$url = wp_get_attachment_image_url( $id, 'thumbnail' );
					if ( $url ) {
						$images[ $id ] = $url;
					}
				}
			}
		}

		$config = [
			'schema'     => $schema,
			'defaults'   => $defaults,
			'images'     => (object) $images,
			'previewUrl' => add_query_arg( 'jk_preview', '{key}', home_url( '/' ) ),
			'unfiltered' => current_user_can( 'unfiltered_html' ),
		];

		wp_add_inline_script( self::HANDLE, 'window.JKBlocks=' . wp_json_encode( $config, Boot::JSON_FLAGS ) . ';', 'before' );
	}

	/**
	 * A section's fields for blocks: everything except the "Show section" switch.
	 *
	 * @param string $key Section key.
	 * @return array<int, array>
	 */
	public static function fields( string $key ): array {
		return array_values( array_filter( Schema::fields( $key ), static fn( array $f ): bool => 'enabled' !== $f['key'] ) );
	}

	/**
	 * Whether a parsed block is one of ours, and which section it is.
	 *
	 * @param array $block Parsed block.
	 */
	public static function section_key( array $block ): string {
		$name = (string) ( $block['blockName'] ?? '' );
		if ( ! str_starts_with( $name, self::PREFIX ) ) {
			return '';
		}
		$key = substr( $name, strlen( self::PREFIX ) );
		return in_array( $key, Boot::PREVIEW_SECTIONS, true ) ? $key : '';
	}

	/**
	 * A block's values: sanitised like a Theme Settings save, then merged with the defaults.
	 *
	 * @param string $key  Section key.
	 * @param mixed  $data The block's `data` attribute.
	 */
	public static function values( string $key, mixed $data ): array {
		$clean             = Sanitizer::section( $key, is_array( $data ) ? $data : [], [] );
		$values            = Settings::merge( $key, $clean );
		$values['enabled'] = true;
		return $values;
	}

	/**
	 * Whether post content holds any James Kellie block.
	 *
	 * @param string $content Post content.
	 */
	public static function in_content( string $content ): bool {
		return str_contains( $content, '<!-- wp:' . self::PREFIX );
	}

	/**
	 * Topics of the Contact blocks on a post (the contact endpoint accepts these too).
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function contact_topics( int $post_id ): array {
		$post = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || ! self::in_content( $post->post_content ) ) {
			return [];
		}
		$topics = [];
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( 'contact' === self::section_key( $block ) ) {
				$values = self::values( 'contact', $block['attrs']['data'] ?? [] );
				$topics = array_merge( $topics, array_map( 'strval', (array) $values['topics'] ) );
			}
		}
		return array_values( array_unique( $topics ) );
	}

	/**
	 * Plain HTML for feeds, excerpts and <noscript>. React never uses it.
	 *
	 * @param string $key        Section key.
	 * @param array  $attributes Block attributes.
	 */
	private static function fallback( string $key, array $attributes ): string {
		$v     = self::values( $key, $attributes['data'] ?? [] );
		$title = (string) ( 'cta' === $key ? $v['heading'] : ( $v['title'] ?? '' ) );
		$text  = (string) ( $v['intro'] ?? $v['side'] ?? '' );
		$html  = '';

		if ( '' !== $title ) {
			$html .= 'hero' === $key ? '<h1>' . esc_html( $title ) . '</h1>' : '<h2>' . esc_html( $title ) . '</h2>';
		}
		if ( '' !== $text ) {
			$html .= '<p>' . esc_html( wp_strip_all_tags( $text ) ) . '</p>';
		}
		return '' !== $html ? '<section>' . $html . '</section>' : '';
	}
}
