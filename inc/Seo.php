<?php
/**
 * JSON-LD and basic SEO tags when no SEO plugin is active (HANDOFF §5.4, §10).
 *
 * @package jameskellie
 */

namespace JK;

use JK\Admin\AppointmentMetaBox;

defined( 'ABSPATH' ) || exit;

/**
 * SEO output.
 */
final class Seo {

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_action( 'wp_head', [ $this, 'meta' ], 2 );
		add_action( 'wp_head', [ $this, 'json_ld' ], 20 );
		add_action( 'template_redirect', [ $this, 'maybe_take_canonical' ] );
	}

	/**
	 * Yoast or Rank Math active.
	 */
	public static function plugin_active(): bool {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' );
	}

	/**
	 * We print our own canonical when no plugin is active, so drop core's.
	 */
	public function maybe_take_canonical(): void {
		if ( ! self::plugin_active() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	/**
	 * Description, canonical, Open Graph and Twitter tags.
	 */
	public function meta(): void {
		if ( self::plugin_active() || is_404() ) {
			return;
		}

		$description = '';
		$image_id    = 0;
		$url         = '';

		if ( is_front_page() ) {
			$description = (string) Settings::get( 'general.seo_description' );
			$image_id    = (int) Settings::get( 'general.share_image' );
			$url         = home_url( '/' );
		} elseif ( is_singular() ) {
			$post_id = (int) get_queried_object_id();
			$url     = (string) get_permalink( $post_id );
			if ( AppointmentMetaBox::is_appointment( $post_id ) ) {
				$data        = Settings::appointment( $post_id );
				$description = (string) $data['seo_description'];
				$image_id    = (int) $data['share_image'];
			} else {
				$description = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
			}
		}

		if ( ! $image_id ) {
			$image_id = (int) Settings::get( 'general.share_image' );
		}

		$title = wp_get_document_title();
		$image = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}
		if ( $url ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		}

		$og = [
			'og:type'        => is_front_page() ? 'website' : 'article',
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:image'       => $image,
			'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		];
		foreach ( array_filter( $og ) as $property => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}

		$tw = [
			'twitter:card'        => $image ? 'summary_large_image' : 'summary',
			'twitter:title'       => $title,
			'twitter:description' => $description,
			'twitter:image'       => $image,
		];
		foreach ( array_filter( $tw ) as $name => $content ) {
			printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $name ), esc_attr( $content ) );
		}
	}

	/**
	 * Person + ProfessionalService on the front page.
	 */
	public function json_ld(): void {
		if ( ! is_front_page() ) {
			return;
		}

		$home   = home_url( '/' );
		$social = array_values(
			array_filter(
				array_map(
					static fn( $row ) => is_array( $row ) ? (string) ( $row['url'] ?? '' ) : '',
					(array) Settings::get( 'general.social', [] )
				)
			)
		);
		$phone  = (string) Settings::get( 'general.phone_tel' );
		$person = [
			'@type'     => 'Person',
			'@id'       => $home . '#person',
			'name'      => 'James Kellie',
			'jobTitle'  => 'Digital Marketer',
			'url'       => $home,
			'telephone' => $phone,
		];
		if ( $social ) {
			$person['sameAs'] = $social;
		}
		$portrait = (int) Settings::get( 'hero.portrait' );
		if ( $portrait ) {
			$person['image'] = wp_get_attachment_image_url( $portrait, 'large' );
		}

		$service = [
			'@type'       => 'ProfessionalService',
			'@id'         => $home . '#business',
			'name'        => get_bloginfo( 'name' ),
			'url'         => $home,
			'telephone'   => $phone,
			'areaServed'  => 'Australia',
			'description' => (string) Settings::get( 'general.seo_description' ),
			'founder'     => [ '@id' => $home . '#person' ],
		];

		$graph = [];
		if ( ! $this->plugin_outputs_person() ) {
			$graph[] = $person;
		} else {
			unset( $service['founder'] );
		}
		$graph[] = $service;

		$graph = apply_filters( 'jk_json_ld_graph', $graph );
		if ( ! $graph ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				[
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				],
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
			)
		);
	}

	/**
	 * Whether Yoast or Rank Math is configured to output a Person already.
	 */
	private function plugin_outputs_person(): bool {
		if ( defined( 'WPSEO_VERSION' ) && class_exists( '\WPSEO_Options' ) ) {
			return 'person' === \WPSEO_Options::get( 'company_or_person' );
		}
		if ( class_exists( 'RankMath' ) && function_exists( 'rank_math_get_settings' ) ) {
			return 'person' === rank_math_get_settings( 'titles.knowledgegraph_type' );
		}
		return false;
	}
}
