<?php
/**
 * Boots every theme service.
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * Theme entry point.
 */
final class Theme {

	/**
	 * Register hooks for all services.
	 */
	public function boot(): void {
		add_action( 'after_setup_theme', [ $this, 'setup' ] );
		add_action( 'init', [ Admin\SettingsRegistry::class, 'register' ] );
		add_filter( 'body_class', [ $this, 'body_class' ] );
		// Before template_redirect, where the admin bar is set up.
		add_action( 'wp', [ Boot::class, 'preview_mode' ] );

		( new Assets() )->hooks();
		( new Performance() )->hooks();
		( new Minify() )->hooks();
		( new CustomCode() )->hooks();
		( new Seo() )->hooks();
		( new Spa() )->hooks();
		( new Enquiries() )->hooks();
		( new Rest\ContactController() )->hooks();
		( new Rest\SettingsController() )->hooks();
		( new Admin\Seeder() )->hooks();

		if ( is_admin() ) {
			( new Admin\Menu() )->hooks();
			( new Admin\AppointmentMetaBox() )->hooks();
			( new Admin\EnquiryView() )->hooks();
		}
	}

	/**
	 * Theme supports and menus.
	 */
	public function setup(): void {
		load_theme_textdomain( 'jameskellie', JK_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		// Feed links print only with this; the Performance tab's "Keep RSS feed links" removes them.
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		remove_theme_support( 'core-block-patterns' );

		register_nav_menus(
			[
				'primary' => __( 'Primary', 'jameskellie' ),
			]
		);
	}

	/**
	 * Body classes used by CSS.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		$classes[] = 'jk';
		if ( Settings::get( 'general.sticky_header' ) ) {
			$classes[] = 'jk-sticky';
		}
		return $classes;
	}
}
