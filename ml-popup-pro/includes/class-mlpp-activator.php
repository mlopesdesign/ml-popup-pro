<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Activator {

	/**
	 * Schema version. Bump whenever the table structure changes so that
	 * maybe_upgrade() re-runs the migration on existing installs.
	 */
	const DB_VERSION = '6';

	/**
	 * Columns expected on the popups table, with the DDL used to (re)create
	 * a missing column. TEXT/LONGTEXT columns are declared NULL with no literal
	 * DEFAULT, because MariaDB (and MySQL < 8.0.13) reject a DEFAULT on
	 * BLOB/TEXT columns — that rejection previously caused these columns to be
	 * dropped/never created, which made every popup INSERT fail silently.
	 */
	private static function popup_columns(): array {
		return [
			'name'                => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'title'               => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'subtitle'            => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'body'                => "LONGTEXT        NULL",
			'image_url'           => "VARCHAR(2083)   NOT NULL DEFAULT ''",
			'image_attachment_id' => "BIGINT UNSIGNED NOT NULL DEFAULT 0",
			'image_alt'           => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'image_link_url'      => "VARCHAR(2083)   NOT NULL DEFAULT ''",
			'image_link_target'   => "VARCHAR(20)     NOT NULL DEFAULT '_self'",
			'image_position'      => "VARCHAR(20)     NOT NULL DEFAULT 'top'",
			'image_fit'           => "VARCHAR(20)     NOT NULL DEFAULT 'cover'",
			'image_radius'        => "VARCHAR(20)     NOT NULL DEFAULT '0px'",
			'btn_primary_text'    => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'btn_primary_url'     => "VARCHAR(2083)   NOT NULL DEFAULT ''",
			'btn_secondary_text'  => "VARCHAR(255)    NOT NULL DEFAULT ''",
			'btn_secondary_url'   => "VARCHAR(2083)   NOT NULL DEFAULT ''",
			'custom_html'         => "LONGTEXT        NULL",
			'status'              => "VARCHAR(20)     NOT NULL DEFAULT 'draft'",
			'priority'            => "INT             NOT NULL DEFAULT 10",
			'popup_type'          => "VARCHAR(50)     NOT NULL DEFAULT 'center_modal'",
			'design'              => "LONGTEXT        NULL",
			'triggers'            => "LONGTEXT        NULL",
			'rules'               => "LONGTEXT        NULL",
			'storage_cfg'         => "LONGTEXT        NULL",
			'goal_selectors'      => "LONGTEXT        NULL",
			'variant_group_id'    => "INT             NOT NULL DEFAULT 0",
			'variant_label'       => "VARCHAR(50)     NOT NULL DEFAULT ''",
			'variant_split'       => "INT             NOT NULL DEFAULT 100",
			'template_id'         => "VARCHAR(50)     NOT NULL DEFAULT ''",
		];
	}

	private static function schema(): array {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		$cols = '';
		foreach ( self::popup_columns() as $name => $ddl ) {
			$cols .= "\t\t\t{$name} {$ddl},\n";
		}

		$sql_popups = "CREATE TABLE {$p}mlpp_popups (
			id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
{$cols}			created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY variant_group_id (variant_group_id)
		) {$charset};";

		$sql_events = "CREATE TABLE {$p}mlpp_events (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			popup_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			variant_label VARCHAR(50)  NOT NULL DEFAULT '',
			event_type  VARCHAR(50)     NOT NULL DEFAULT '',
			page_url    VARCHAR(2083)   NOT NULL DEFAULT '',
			device_type VARCHAR(20)     NOT NULL DEFAULT '',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY popup_id (popup_id),
			KEY event_type (event_type),
			KEY created_at (created_at),
			KEY variant_label (variant_label)
		) {$charset};";

		$sql_meta = "CREATE TABLE {$p}mlpp_meta (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			meta_key    VARCHAR(255)    NOT NULL DEFAULT '',
			meta_value  LONGTEXT        NULL,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY meta_key (meta_key)
		) {$charset};";

		$sql_audit = "CREATE TABLE {$p}mlpp_audit (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			popup_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_login  VARCHAR(60)     NOT NULL DEFAULT '',
			action      VARCHAR(20)     NOT NULL DEFAULT '',
			meta        TEXT            NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY popup_id (popup_id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset};";

		return [ $sql_popups, $sql_events, $sql_meta, $sql_audit ];
	}

	/**
	 * Create or repair all tables. Safe to call repeatedly.
	 * Returns an array of human-readable notes about what was done.
	 *
	 * Defensive: each step (dbDelta loop, SHOW COLUMNS probe, ALTER TABLE
	 * repair, option writes) is guarded with its own try/catch. A failure
	 * in one step never aborts the rest, so a degraded host can still
	 * land in an at-least-readable schema.
	 */
	public static function ensure_schema(): array {
		global $wpdb;
		$notes = [];

		// Step 1 — wp-admin/includes/upgrade.php may be missing on sites
		// where the wp-admin directory was relocated. Promote to throw
		// instead of `require_once` fatal so we can record the note.
		try {
			$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
			if ( file_exists( $upgrade_file ) ) {
				require_once $upgrade_file;
			} else {
				throw new \RuntimeException( 'wp-admin/includes/upgrade.php não encontrado em ' . $upgrade_file );
			}
		} catch ( \Throwable $e ) {
			$notes[] = 'upgrade.php indisponível: ' . $e->getMessage();
			error_log( '[ml-popup-pro] upgrade.php include failed: ' . $e->getMessage() );
			return $notes;
		}

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			$notes[] = 'wpdb indisponível — schema não verificado.';
			return $notes;
		}

		// Step 2 — dbDelta for each declared table.
		foreach ( self::schema() as $sql ) {
			try {
				dbDelta( $sql );
			} catch ( \Throwable $e ) {
				$notes[] = 'dbDelta falhou em uma das tabelas: ' . $e->getMessage();
				error_log( '[ml-popup-pro] dbDelta failed: ' . $e->getMessage() );
			}
		}

		// Step 3 — safety-net column repair.
		$table    = $wpdb->prefix . 'mlpp_popups';
		$existing = [];
		try {
			$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
			$existing = is_array( $cols ) ? array_map( 'strval', $cols ) : [];
		} catch ( \Throwable $e ) {
			$notes[] = 'SHOW COLUMNS bloqueado: ' . $e->getMessage();
			error_log( '[ml-popup-pro] SHOW COLUMNS failed: ' . $e->getMessage() );
		}

		foreach ( self::popup_columns() as $name => $ddl ) {
			if ( in_array( $name, $existing, true ) ) {
				continue;
			}
			try {
				// phpcs:ignore WordPress.DB.PreparedSQL
				$ok = $wpdb->query( "ALTER TABLE {$table} ADD COLUMN `{$name}` {$ddl}" );
				if ( false === $ok && ! empty( $wpdb->last_error ) ) {
					$notes[] = sprintf( 'Falha ao recriar coluna %s: %s', $name, $wpdb->last_error );
					error_log( '[ml-popup-pro] ALTER TABLE failed for ' . $name . ': ' . $wpdb->last_error );
				} else {
					$notes[] = sprintf( 'Coluna ausente recriada: %s', $name );
				}
			} catch ( \Throwable $e ) {
				$notes[] = sprintf( 'Exceção ao recriar coluna %s: %s', $name, $e->getMessage() );
				error_log( '[ml-popup-pro] ALTER TABLE threw for ' . $name . ': ' . $e->getMessage() );
			}
		}

		// Step 4 — stamp db_version so subsequent loads skip ensure_schema.
		try {
			update_option( 'mlpp_db_version', self::DB_VERSION, false );
		} catch ( \Throwable $e ) {
			$notes[] = 'update_option(db_version) falhou: ' . $e->getMessage();
			error_log( '[ml-popup-pro] update_option(db_version) failed: ' . $e->getMessage() );
		}

		if ( empty( $notes ) ) {
			$notes[] = 'Estrutura do banco verificada — nenhuma correção necessária.';
		}
		return $notes;
	}

	public static function activate(): void {
		$notes = self::ensure_schema();
		// Surface diagnostic notes for the admin_notices handler.
		if ( is_array( $notes ) ) {
			try {
				set_transient( 'mlpp_activation_notes', $notes, DAY_IN_SECONDS );
			} catch ( \Throwable $e ) {
				error_log( '[ml-popup-pro] set_transient(activation_notes) failed: ' . $e->getMessage() );
			}
		}
		if ( false === get_option( 'mlpp_settings' ) ) {
			try {
				update_option( 'mlpp_settings', self::default_settings(), false );
			} catch ( \Throwable $e ) {
				error_log( '[ml-popup-pro] default_settings insert failed: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Runs on every load (cheaply). When the stored DB version is behind the
	 * current one, repairs the schema — this is what fixes existing installs
	 * after a plugin UPDATE, since activation hooks do not run on update.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'mlpp_db_version' ) === self::DB_VERSION ) {
			return;
		}
		self::ensure_schema();
	}

	public static function deactivate(): void {}

	private static function default_settings(): array {
		return [
			'storage_method'           => 'cookie',
			'default_expiration_days'  => 30,
			'consent_mode'             => 'off',
			'allow_multiple_popups'    => '0',
			'disable_analytics'        => '0',
			'delete_data_on_uninstall' => '0',
		];
	}
}
