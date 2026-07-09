<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Activator {

	/**
	 * Schema version. Bump whenever the table structure changes so that
	 * maybe_upgrade() re-runs the migration on existing installs.
	 */
	const DB_VERSION = '5';

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

		return [ $sql_popups, $sql_events, $sql_meta ];
	}

	/**
	 * Create or repair all tables. Safe to call repeatedly.
	 * Returns an array of human-readable notes about what was done.
	 */
	public static function ensure_schema(): array {
		global $wpdb;
		$notes = [];
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( self::schema() as $sql ) {
			dbDelta( $sql );
		}

		// Safety net: guarantee every expected popups column exists, even on
		// servers where dbDelta skipped a TEXT column on an earlier version.
		$table    = $wpdb->prefix . 'mlpp_popups';
		$existing = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
		$existing = is_array( $existing ) ? array_map( 'strval', $existing ) : [];

		foreach ( self::popup_columns() as $name => $ddl ) {
			if ( ! in_array( $name, $existing, true ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN `{$name}` {$ddl}" );
				$notes[] = sprintf( 'Coluna ausente recriada: %s', $name );
			}
		}

		update_option( 'mlpp_db_version', self::DB_VERSION, false );

		if ( empty( $notes ) ) {
			$notes[] = 'Estrutura do banco verificada — nenhuma correção necessária.';
		}
		return $notes;
	}

	public static function activate(): void {
		self::ensure_schema();
		if ( ! get_option( 'mlpp_settings' ) ) {
			update_option( 'mlpp_settings', self::default_settings(), false );
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
