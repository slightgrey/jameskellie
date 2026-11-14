<?php
/**
 * Theme updates from GitHub releases, through WordPress's own update system.
 *
 * The style.css header declares "Update URI: https://github.com/slightgrey/jameskellie", so WordPress
 * (6.1+) asks the update_themes_github.com filter instead of WordPress.org. The update package
 * is the release asset jameskellie-vX.Y.Z.zip made by `npm run bundle` and attached by
 * `npm run release` (GitHub's own source zip has no dist/ and the wrong folder name).
 *
 * Constants (optional, wp-config.php):
 *   JK_UPDATER_DISABLED  true turns update checks off.
 *   JK_UPDATER_REPO      "owner/repo", default slightgrey/jameskellie.
 *   JK_GITHUB_TOKEN      token for the API call (higher rate limit).
 *
 * Only runs while this theme is active (inactive themes' code is not loaded).
 *
 * @package jameskellie
 */

namespace JK;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub release updater.
 */
final class Updater {

	private const REPO      = 'slightgrey/jameskellie';
	private const CACHE     = 'jk_github_release';
	private const CACHE_TTL = 6 * HOUR_IN_SECONDS;
	private const ERROR_TTL = HOUR_IN_SECONDS;

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		add_filter( 'update_themes_github.com', [ $this, 'check' ], 10, 3 );
		add_action( 'upgrader_process_complete', [ self::class, 'flush' ] );
	}

	/**
	 * Update data for this theme, or false.
	 *
	 * @param array|false $update     Update from another filter, or false.
	 * @param array       $theme_data Theme headers.
	 * @param string      $stylesheet Theme directory name.
	 * @return array|false
	 */
	public function check( $update, $theme_data, $stylesheet ) {
		if ( $update || get_template() !== $stylesheet || ( defined( 'JK_UPDATER_DISABLED' ) && constant( 'JK_UPDATER_DISABLED' ) ) ) {
			return $update;
		}

		$release = self::release();
		if ( ! $release ) {
			return false;
		}

		return [
			'theme'        => $stylesheet,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => '6.6',
			'requires_php' => '8.1',
		];
	}

	/**
	 * Latest release: {version, url, package}, or null (none, no zip asset, or request failed).
	 * Cached; Dashboard → Updates → "Check again" (force-check) bypasses the cache.
	 *
	 * @return array{version:string, url:string, package:string}|null
	 */
	public static function release(): ?array {
		$force  = ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, same flag core uses.
		$cached = $force ? false : get_site_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return ! empty( $cached['version'] ) ? $cached : null;
		}

		$release = self::fetch();
		// An empty array caches "nothing found" so a failing API is not asked on every load.
		set_site_transient( self::CACHE, $release ?? [], $release ? self::CACHE_TTL : self::ERROR_TTL );
		return $release;
	}

	/**
	 * Ask GitHub for the latest published release.
	 *
	 * @return array{version:string, url:string, package:string}|null
	 */
	private static function fetch(): ?array {
		$repo = defined( 'JK_UPDATER_REPO' ) ? (string) constant( 'JK_UPDATER_REPO' ) : self::REPO;
		if ( ! preg_match( '#^[\w.-]+/[\w.-]+$#', $repo ) ) {
			return null;
		}

		$headers = [
			'Accept'               => 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2022-11-28',
		];
		if ( defined( 'JK_GITHUB_TOKEN' ) && '' !== (string) constant( 'JK_GITHUB_TOKEN' ) ) {
			$headers['Authorization'] = 'Bearer ' . constant( 'JK_GITHUB_TOKEN' );
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . $repo . '/releases/latest',
			[
				'timeout'    => 10,
				'headers'    => $headers,
				'user-agent' => 'WordPress theme updater',
			]
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! empty( $body['draft'] ) || ! empty( $body['prerelease'] ) ) {
			return null;
		}

		$version = ltrim( (string) ( $body['tag_name'] ?? '' ), 'vV' );
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			return null;
		}

		// The bundle zip for this version, else any zip asset.
		$package = '';
		$theme   = get_template();
		foreach ( (array) ( $body['assets'] ?? [] ) as $asset ) {
			$name = (string) ( $asset['name'] ?? '' );
			$url  = (string) ( $asset['browser_download_url'] ?? '' );
			if ( '' === $url || ! str_ends_with( strtolower( $name ), '.zip' ) ) {
				continue;
			}
			if ( "{$theme}-v{$version}.zip" === $name ) {
				$package = $url;
				break;
			}
			if ( '' === $package ) {
				$package = $url;
			}
		}
		if ( '' === $package ) {
			return null;
		}

		return [
			'version' => $version,
			'url'     => esc_url_raw( (string) ( $body['html_url'] ?? 'https://github.com/' . $repo . '/releases' ) ),
			'package' => esc_url_raw( $package ),
		];
	}

	/**
	 * Forget the cached release (after any update, so the next check is fresh).
	 */
	public static function flush(): void {
		delete_site_transient( self::CACHE );
	}
}
