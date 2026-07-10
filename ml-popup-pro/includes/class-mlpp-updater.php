<?php
/**
 * ML Popup Pro – GitHub Release Updater
 *
 * Makes WordPress detect plugin updates from GitHub Releases.
 *
 * @package ML_Popup_Pro
 * @since   1.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Updater {

	const GITHUB_OWNER    = 'mlopesdesign';
	const GITHUB_REPO     = 'ml-popup-pro';
	const PLUGIN_SLUG     = 'ml-popup-pro';
	const PLUGIN_BASENAME = 'ml-popup-pro/ml-popup-pro.php';
	const CACHE_KEY       = 'mlpp_github_update_cache';
	const CACHE_TTL       = 6 * HOUR_IN_SECONDS;
	// When the pre-flight reachability probe fails (GH Releases CDN 504
	// storm, transient network blip) we still cache the metadata but
	// with a much shorter TTL so the next operator "Verificar agora"
	// picks up the recovery without waiting 6h.
	const CACHE_TTL_ERROR = 5 * MINUTE_IN_SECONDS;
	const API_TIMEOUT     = 15;

	/**
	 * Register updater hooks as early as the plugin file is loaded.
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'inject_update_into_transient' ], 20 );
		add_filter( 'site_transient_update_plugins',         [ $this, 'inject_update_into_transient' ], 20 );
		add_filter( 'transient_update_plugins',              [ $this, 'inject_update_into_transient' ], 20 );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );

		// WordPress 5.8+ custom Update URI support. This is used when the plugin header has Update URI.
		add_filter( 'update_plugins_github.com', [ $this, 'custom_update_uri_response' ], 20, 4 );

		add_action( 'upgrader_process_complete', [ $this, 'clear_cache_after_update' ], 10, 2 );
	}

	/**
	 * GitHub latest release API.
	 */
	public function get_latest_release( bool $force = false ): ?array {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
				return $cached;
			}
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			self::GITHUB_OWNER,
			self::GITHUB_REPO
		);

		$response = wp_remote_get(
			$url,
			[
				'timeout'     => self::API_TIMEOUT,
				'redirection' => 5,
				'user-agent'  => 'ML-Popup-Pro-Updater/' . MLPP_VERSION . ' WordPress/' . get_bloginfo( 'version' ),
				'headers'     => [
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->store_last_error( $response->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$this->store_last_error( 'GitHub API HTTP ' . $code );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			$this->store_last_error( 'Resposta inválida da API GitHub.' );
			return null;
		}

		$version = $this->normalize_version( (string) $body['tag_name'] );
		if ( '' === $version ) {
			$this->store_last_error( 'Tag de release sem versão válida.' );
			return null;
		}

		$zip_url = $this->find_zip_asset( $body, $version );

		// Deterministic fallback. Works when the asset name follows the project standard.
		if ( '' === $zip_url ) {
			$zip_url = sprintf(
				'https://github.com/%s/%s/releases/download/v%s/%s-v%s.zip',
				self::GITHUB_OWNER,
				self::GITHUB_REPO,
				$version,
				self::PLUGIN_SLUG,
				$version
			);
		}

		// Pre-flight reachability check: if the GH Releases CDN is
		// returning 504 (known to happen), the release is real but
		// WP's auto-update will silently fail. Cache the metadata with
		// a short TTL so the next user-side "Verificar agora" picks up
		// the recovery quickly. Without this, the operator is stuck
		// waiting 6h for the cache to expire even after CDN recovers.
		$reachable = $this->url_reachable( $zip_url );
		$ttl       = $reachable ? self::CACHE_TTL : self::CACHE_TTL_ERROR;

		// NOTE: Per project security policy, we explicitly do NOT fall back
		// to GitHub's auto-generated `archive/refs/tags/<tag>.zip` (the
		// "zipball"). That archive contains the repository's source tree
		// with the folder named `<repo>-<version>/`, which won't match the
		// plugin's intended `<plugin-slug>/` layout and would land unsafe
		// debug artifacts in production installs. If the official release
		// asset is unreachable, we surface an error and let the operator
		// retry — never silently install a source archive.

		$release = [
			'version'      => $version,
			'tag_name'     => (string) $body['tag_name'],
			'name'         => (string) ( $body['name'] ?? $body['tag_name'] ),
			'changelog'    => (string) ( $body['body'] ?? '' ),
			'zip_url'      => esc_url_raw( $zip_url ),
			'release_url'  => esc_url_raw( (string) ( $body['html_url'] ?? '' ) ),
			'published_at' => (string) ( $body['published_at'] ?? '' ),
			'zip_fallbacks'=> [], // filled by apply_zip_fallbacks() if theme/addon adds mirrors
		];

		delete_site_option( 'mlpp_github_update_last_error' );
		set_site_transient( self::CACHE_KEY, $release, $ttl );

		return $release;
	}

	private function normalize_version( string $tag ): string {
		$version = preg_replace( '/^[^0-9]*/', '', trim( $tag ) );
		return is_string( $version ) ? $version : '';
	}

	private function find_zip_asset( array $release_body, string $version ): string {
		if ( empty( $release_body['assets'] ) || ! is_array( $release_body['assets'] ) ) {
			return '';
		}

		$expected = self::PLUGIN_SLUG . '-v' . $version . '.zip';
		$fallback = '';

		foreach ( $release_body['assets'] as $asset ) {
			$name = (string) ( $asset['name'] ?? '' );
			$url  = (string) ( $asset['browser_download_url'] ?? '' );
			if ( '' === $name || '' === $url ) {
				continue;
			}

			if ( $expected === $name ) {
				return $url;
			}

			if ( preg_match( '/^ml-popup-pro-v[0-9]+(?:\.[0-9]+)*\.zip$/', $name ) ) {
				$fallback = $url;
			}
		}

		return $fallback;
	}

	/**
	 * Inject update info into the standard WP update transient.
	 *
	 * @param mixed $transient
	 * @return mixed
	 */
	public function inject_update_into_transient( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$item = $this->build_update_object( $release );
		if ( ! $item ) {
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = [];
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = [];
		}

		if ( version_compare( $release['version'], MLPP_VERSION, '>' ) ) {
			$transient->response[ self::PLUGIN_BASENAME ] = $item;
			unset( $transient->no_update[ self::PLUGIN_BASENAME ] );
		} else {
			$transient->no_update[ self::PLUGIN_BASENAME ] = $item;
			unset( $transient->response[ self::PLUGIN_BASENAME ] );
		}

		return $transient;
	}

	/**
	 * WordPress 5.8+ Update URI response.
	 *
	 * @param mixed  $update
	 * @param array  $plugin_data
	 * @param string $plugin_file
	 * @param array  $locales
	 * @return mixed
	 */
	public function custom_update_uri_response( $update, $plugin_data, string $plugin_file, array $locales ) {
		if ( self::PLUGIN_BASENAME !== $plugin_file ) {
			return $update;
		}

		$release = $this->get_latest_release();
		if ( ! $release || ! version_compare( $release['version'], MLPP_VERSION, '>' ) ) {
			return false;
		}

		$item = $this->build_update_object( $release );
		return $item ?: false;
	}

	private function build_update_object( array $release ): ?object {
		if ( empty( $release['version'] ) ) {
			return null;
		}

		// Pick the first URL that is actually reachable. GitHub Releases CDN
		// (releases/download) frequently returns 504/503/timed-out; the
		// deterministic URL and the archive zipball are reliable fallbacks.
		$package = $this->pick_working_zip_url( $release );

		if ( '' === $package ) {
			$this->store_last_error( 'Nenhuma URL de download acessível para a versão ' . $release['version'] . '. Tente novamente em alguns minutos.' );
			return null;
		}

		return (object) [
			'id'             => 'github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'slug'           => self::PLUGIN_SLUG,
			'plugin'         => self::PLUGIN_BASENAME,
			'new_version'    => (string) $release['version'],
			'url'            => (string) $release['release_url'],
			'package'        => (string) $package,
			'tested'         => get_bloginfo( 'version' ),
			'requires'       => '6.0',
			'requires_php'   => '8.1',
			'icons'          => [],
			'banners'        => [],
			'banners_rtl'    => [],
			'upgrade_notice' => '',
		];
	}

	/**
	 * Build the ordered list of candidate ZIP URLs to probe. The first one
	 * that returns HTTP 200 from the server hosting the WP install wins.
	 * Allow themes/addons to inject mirrors via `mlpp_zip_url_mirrors`.
	 *
	 * @return array<int, string>
	 */
	private function candidate_zip_urls( array $release ): array {
		$urls = [];

		// Only the official release asset ever reaches WP Upgrader. We
		// never accept source archives / zipballs / path-tagged tags.
		if ( ! empty( $release['zip_url'] ) ) {
			$urls[] = (string) $release['zip_url'];
		}

		$mirrors = (array) apply_filters( 'mlpp_zip_url_mirrors', [], $release );
		foreach ( $mirrors as $mirror ) {
			if ( is_string( $mirror ) && '' !== $mirror ) {
				$urls[] = esc_url_raw( $mirror );
			}
		}

		return array_values( array_unique( array_filter( $urls ) ) );
	}

	/**
	 * Probe candidate URLs in order. Returns the first one that responds
	 * with HTTP 200 within the timeout, or an empty string when all fail.
	 *
	 * Uses a single GET with `Range: bytes=0-0` when possible — faster and
	 * cheaper than downloading the full ZIP, and still returns 200/206
	 * vs the 504 the server actually gives when the asset is unreachable.
	 */
	private function pick_working_zip_url( array $release ): string {
		$candidates = $this->candidate_zip_urls( $release );
		if ( empty( $candidates ) ) {
			return '';
		}

		foreach ( $candidates as $url ) {
			if ( $this->url_reachable( $url ) ) {
				return $url;
			}
		}
		return '';
	}

	/**
	 * Quick reachability check. Sends a short GET; treats any HTTP 2xx/3xx
	 * as reachable. Caches the result in a per-request static to avoid
	 * probing the same URL multiple times during one update check.
	 *
	 * @return bool
	 */
	private function url_reachable( string $url ): bool {
		static $cache = [];
		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ];
		}

		$response = wp_remote_request( $url, [
			'method'      => 'HEAD',
			'timeout'     => 15,
			'redirection' => 5,
			'user-agent'  => 'ML-Popup-Pro-Updater/' . MLPP_VERSION . ' WordPress/' . get_bloginfo( 'version' ),
			'sslverify'   => true,
		] );

		if ( is_wp_error( $response ) ) {
			$cache[ $url ] = false;
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$ok   = $code >= 200 && $code < 400;
		$cache[ $url ] = $ok;
		return $ok;
	}

	/**
	 * Plugin information modal.
	 */
	public function plugin_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) [
			'name'              => 'ML Popup Pro',
			'slug'              => self::PLUGIN_SLUG,
			'version'           => $release['version'],
			'author'            => '<a href="https://mlopesdesign.com.br">ML Lopes Design</a>',
			'homepage'          => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'requires'          => '6.0',
			'requires_php'      => '8.1',
			'tested'            => get_bloginfo( 'version' ),
			'download_link'     => $release['zip_url'],
			'trunk'             => $release['zip_url'],
			'last_updated'      => $release['published_at'],
			'short_description' => 'Gerenciador premium de popups para WordPress com cookies, analytics e templates.',
			'sections'          => [
				'description' => '<p>ML Popup Pro — gerenciador comercial de popups.</p>',
				'changelog'   => '<pre>' . esc_html( $release['changelog'] ) . '</pre>',
			],
		];
	}

	public function clear_cache_after_update( $upgrader, array $options ): void {
		if (
			'update' === ( $options['action'] ?? '' ) &&
			'plugin' === ( $options['type'] ?? '' ) &&
			! empty( $options['plugins'] )
		) {
			foreach ( (array) $options['plugins'] as $plugin ) {
				if ( self::PLUGIN_BASENAME === $plugin ) {
					$this->clear_cache();
					break;
				}
			}
		}
	}

	public function clear_cache(): void {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		delete_site_option( 'mlpp_github_update_last_error' );
		if ( function_exists( 'wp_clean_plugins_cache' ) ) {
			wp_clean_plugins_cache( true );
		}
	}

	private function store_last_error( string $message ): void {
		update_site_option( 'mlpp_github_update_last_error', sanitize_text_field( $message ) );
	}

	public function get_status(): array {
		$release    = $this->get_latest_release();
		$last_error = (string) get_site_option( 'mlpp_github_update_last_error', '' );

		if ( null === $release ) {
			return [
				'installed'      => MLPP_VERSION,
				'remote_version' => '',
				'status'         => 'error_api',
				'zip_url'        => '',
				'release_url'    => '',
				'error'          => $last_error,
			];
		}

		if ( empty( $release['zip_url'] ) ) {
			return [
				'installed'      => MLPP_VERSION,
				'remote_version' => $release['version'],
				'status'         => 'no_asset',
				'zip_url'        => '',
				'release_url'    => $release['release_url'],
				'error'          => '',
			];
		}

		$update_available = version_compare( $release['version'], MLPP_VERSION, '>' );

		return [
			'installed'      => MLPP_VERSION,
			'remote_version' => $release['version'],
			'status'         => $update_available ? 'update_available' : 'up_to_date',
			'zip_url'        => $release['zip_url'],
			'release_url'    => $release['release_url'],
			'error'          => '',
		];
	}
}
