<?php
/**
 * Plugin Name: Anna Photo — Sync GitHub (auto-update)
 * Description: Met a jour les fichiers du site depuis un repo GitHub, en 1 clic ou automatiquement (toutes les 5 min). Reprend le systeme d'Alliance Groupe : SHA distant, telechargement, sauvegarde auto, liste blanche d'extensions, fichiers sensibles proteges. Page : Outils > Sync GitHub.
 * Version: 1.0.0
 * Author: Anna Photo
 * Text Domain: annaphoto-github-sync
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Anna_GitHub_Sync {

	const SET_OPT       = 'aphoto_sync_settings';
	const SHA_OPT       = 'aphoto_sync_sha';
	const TIME_OPT      = 'aphoto_sync_time';
	const LOG_OPT       = 'aphoto_sync_log';
	const CRON_LOG_OPT  = 'aphoto_sync_cron_log';
	const TRANSIENT_TTL = 300; // 5 min

	const CRON_HOOK     = 'aphoto_github_sync_cron';
	const CRON_INTERVAL = 'aphoto_five_minutes';

	/** Extensions autorisees a etre ecrasees par la sync. */
	const ALLOWED_EXT = array( 'php', 'css', 'js', 'json', 'md', 'mp4', 'webm', 'png', 'jpg', 'jpeg', 'svg', 'webp', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'txt', 'pot', 'po', 'mo', 'html' );

	/** Fichiers a NE JAMAIS ecraser meme s'ils sont dans le repo. */
	const PROTECTED = array( 'wp-config.php', '.env', '.htaccess', '.git', '.gitignore' );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_aphoto_sync_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_aphoto_sync_run', array( __CLASS__, 'handle_run' ) );
		add_action( 'admin_post_aphoto_sync_check', array( __CLASS__, 'handle_check' ) );

		add_filter( 'cron_schedules', array( __CLASS__, 'add_interval' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_run' ) );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, self::CRON_INTERVAL, self::CRON_HOOK );
		}
	}

	public static function on_deactivate() {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) { wp_unschedule_event( $ts, self::CRON_HOOK ); }
	}

	public static function add_interval( $schedules ) {
		if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
			$schedules[ self::CRON_INTERVAL ] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => 'Anna : toutes les 5 minutes',
			);
		}
		return $schedules;
	}

	/* ----------------------------------------------------------------------
	 * Reglages
	 * -------------------------------------------------------------------- */
	public static function settings() {
		$d = get_option( self::SET_OPT, array() );
		$d = is_array( $d ) ? $d : array();
		return wp_parse_args( $d, array(
			'repo'          => '',
			'branch'        => 'main',
			'subdir'        => '',
			'target_key'    => 'wp-content',
			'target_custom' => '',
			'token'         => '',
			'cron_on'       => 0,
		) );
	}

	public static function target_dir() {
		$s = self::settings();
		switch ( $s['target_key'] ) {
			case 'root':
				return untrailingslashit( ABSPATH );
			case 'theme':
				return untrailingslashit( get_stylesheet_directory() );
			case 'custom':
				return untrailingslashit( $s['target_custom'] );
			case 'wp-content':
			default:
				return untrailingslashit( WP_CONTENT_DIR );
		}
	}

	public static function is_configured() {
		$s = self::settings();
		return '' !== $s['repo'] && false !== strpos( $s['repo'], '/' );
	}

	/* ----------------------------------------------------------------------
	 * Etat distant / local
	 * -------------------------------------------------------------------- */
	public static function get_remote_sha( $force = false ) {
		if ( ! self::is_configured() ) { return false; }
		$s = self::settings();
		$cache_key = 'aphoto_sha_remote';
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) { return $cached; }
		}
		$url  = 'https://api.github.com/repos/' . $s['repo'] . '/commits/' . rawurlencode( $s['branch'] );
		$resp = wp_remote_get( $url, array(
			'timeout' => 12,
			'headers' => self::api_headers( $s ),
		) );
		if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
			return false;
		}
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		$sha  = isset( $body['sha'] ) ? substr( $body['sha'], 0, 12 ) : false;
		if ( $sha ) { set_transient( $cache_key, $sha, self::TRANSIENT_TTL ); }
		return $sha;
	}

	private static function api_headers( $s ) {
		$h = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'WordPress AnnaPhoto-Sync',
		);
		if ( '' !== $s['token'] ) { $h['Authorization'] = 'Bearer ' . $s['token']; }
		return $h;
	}

	public static function get_local_sha() {
		return get_option( self::SHA_OPT, '' );
	}
	public static function get_last_time() {
		return (int) get_option( self::TIME_OPT, 0 );
	}
	public static function get_last_log() {
		$l = get_option( self::LOG_OPT, array() );
		return is_array( $l ) ? $l : array();
	}
	public static function get_cron_log() {
		$l = get_option( self::CRON_LOG_OPT, array() );
		return is_array( $l ) ? $l : array();
	}

	/* ----------------------------------------------------------------------
	 * Cron auto-sync
	 * -------------------------------------------------------------------- */
	public static function cron_run() {
		$s = self::settings();
		if ( empty( $s['cron_on'] ) || ! self::is_configured() ) { return; }

		$log    = array();
		$log[]  = '[' . wp_date( 'Y-m-d H:i:s' ) . '] Cron';
		$remote = self::get_remote_sha( true );
		$local  = self::get_local_sha();

		if ( ! $remote ) {
			$log[] = 'API GitHub injoignable';
		} elseif ( $remote === $local ) {
			$log[] = 'Deja a jour (' . $remote . ')';
		} else {
			$log[]  = 'MAJ ' . substr( $local, 0, 7 ) . ' -> ' . substr( $remote, 0, 7 );
			$result = self::sync();
			$log[]  = $result['ok']
				? 'OK : ' . ( (int) $result['stats']['updated'] + (int) $result['stats']['created'] ) . ' fichiers'
				: 'ERREUR : ' . $result['error'];
		}

		$prev = self::get_cron_log();
		$merged = array_merge( $log, $prev );
		if ( count( $merged ) > 50 ) { $merged = array_slice( $merged, 0, 50 ); }
		update_option( self::CRON_LOG_OPT, $merged );
	}

	/* ----------------------------------------------------------------------
	 * Sync
	 * -------------------------------------------------------------------- */
	public static function sync() {
		$fail = function ( $err, $log ) {
			return array( 'ok' => false, 'error' => $err, 'log' => $log, 'sha' => '', 'stats' => array() );
		};

		if ( ! self::is_configured() ) {
			return $fail( 'Repo non configure', array() );
		}
		$s   = self::settings();
		$log = array();
		$log[] = '[' . wp_date( 'H:i:s' ) . '] Sync ' . $s['repo'] . '@' . $s['branch'];

		// Securite : la cible doit etre dans l'installation WordPress.
		$target = self::target_dir();
		$real_target = realpath( $target );
		$real_abs    = realpath( ABSPATH );
		if ( ! $real_target || ! $real_abs || strpos( $real_target, $real_abs ) !== 0 ) {
			return $fail( 'Dossier cible invalide (hors WordPress) : ' . $target, $log );
		}

		// 1. SHA distant
		$remote_sha = self::get_remote_sha( true );
		if ( ! $remote_sha ) {
			$log[] = 'ERREUR : API GitHub injoignable';
			return $fail( 'API GitHub injoignable', $log );
		}
		$log[] = 'SHA distant : ' . $remote_sha;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		// 2. Telechargement du tarball (.tar.gz)
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return $fail( 'Dossier uploads indisponible', $log );
		}
		$tar = trailingslashit( $upload['basedir'] ) . 'anna-sync-' . time() . '.tar.gz';
		$dl  = self::download_tarball( $s, $tar );
		if ( is_wp_error( $dl ) ) {
			$log[] = 'ERREUR telechargement : ' . $dl->get_error_message();
			return $fail( 'Telechargement echoue', $log );
		}
		$log[] = 'Archive telechargee (' . size_format( filesize( $tar ) ) . ')';

		// 3. Extraction
		$work = trailingslashit( $upload['basedir'] ) . 'anna-sync-work-' . time();
		if ( ! wp_mkdir_p( $work ) ) {
			@unlink( $tar );
			return $fail( 'Dossier de travail non creable', $log );
		}
		$ok = false;
		try {
			$phar = new PharData( $tar );
			$phar->extractTo( $work, null, true );
			$ok = true;
		} catch ( Exception $e ) {
			$log[] = 'ERREUR extraction : ' . $e->getMessage();
		}
		@unlink( $tar );
		if ( ! $ok ) {
			self::rm_recursive( $work );
			return $fail( 'Extraction echouee', $log );
		}

		// Source = REPO-SHA/<subdir>
		$dirs = glob( $work . '/*', GLOB_ONLYDIR );
		if ( empty( $dirs ) ) {
			self::rm_recursive( $work );
			return $fail( 'Structure archive inattendue', $log );
		}
		$source_root = $dirs[0];
		if ( '' !== $s['subdir'] ) { $source_root .= '/' . trim( $s['subdir'], '/' ); }
		if ( ! is_dir( $source_root ) ) {
			self::rm_recursive( $work );
			$log[] = 'ERREUR : sous-dossier introuvable : ' . $s['subdir'];
			return $fail( 'Sous-dossier introuvable', $log );
		}
		$log[] = 'Source : ' . ( '' !== $s['subdir'] ? $s['subdir'] : '<racine>' );
		$log[] = 'Cible : ' . str_replace( $real_abs, '', $real_target );

		// 4. Backup
		$backup_dir = trailingslashit( $upload['basedir'] ) . 'anna-backups/' . wp_date( 'Y-m-d_His' );
		wp_mkdir_p( $backup_dir );

		// 5. Sync recursif
		$stats = array( 'updated' => 0, 'created' => 0, 'skipped' => 0 );
		self::sync_recursive( $source_root, $target, $backup_dir, '', $stats, $log );
		$log[] = sprintf( '%d mis a jour, %d crees, %d ignores', $stats['updated'], $stats['created'], $stats['skipped'] );

		// 6. Cleanup
		self::rm_recursive( $work );

		// 7. Persist
		update_option( self::SHA_OPT, $remote_sha );
		update_option( self::TIME_OPT, time() );
		update_option( self::LOG_OPT, $log );

		// 8. Purge des caches si des fichiers ont change
		if ( $stats['updated'] + $stats['created'] > 0 ) {
			$purged = self::purge_caches();
			if ( ! empty( $purged ) ) {
				$log[] = 'Cache purge : ' . implode( ', ', $purged );
				update_option( self::LOG_OPT, $log );
			}
		}

		return array( 'ok' => true, 'error' => '', 'log' => $log, 'sha' => $remote_sha, 'stats' => $stats );
	}

	/**
	 * Purge les caches detectes. Retourne la liste des caches purges.
	 */
	private static function purge_caches() {
		$done = array();

		// Object cache WordPress (toujours)
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
			$done[] = 'object-cache';
		}

		// LiteSpeed Cache
		if ( defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\Core' ) || class_exists( 'LiteSpeed_Cache_API' ) ) {
			do_action( 'litespeed_purge_all' );
			if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
				LiteSpeed_Cache_API::purge_all();
			}
			$done[] = 'LiteSpeed';
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			$done[] = 'WP Rocket';
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$done[] = 'WP Super Cache';
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			$done[] = 'W3TC';
		}

		// WP Fastest Cache
		if ( class_exists( 'WpFastestCache' ) ) {
			do_action( 'wpfc_clear_all_cache', true );
			$done[] = 'WP Fastest Cache';
		}

		// SG Optimizer (SiteGround)
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
			$done[] = 'SG Optimizer';
		}

		// Cache Enabler
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
			$done[] = 'Cache Enabler';
		}

		// Hostinger / Hummingbird
		if ( has_action( 'wphb_clear_page_cache' ) ) {
			do_action( 'wphb_clear_page_cache' );
			$done[] = 'Hummingbird';
		}

		// OPcache PHP
		if ( function_exists( 'opcache_reset' ) ) {
			@opcache_reset();
			$done[] = 'OPcache';
		}

		// Hook generique pour permettre a d'autres plugins de purger
		do_action( 'aphoto_sync_purge_caches' );

		return $done;
	}

	private static function download_tarball( $s, $dest ) {
		$url  = 'https://api.github.com/repos/' . $s['repo'] . '/tarball/' . rawurlencode( $s['branch'] );
		$resp = wp_remote_get( $url, array(
			'timeout'     => 90,
			'redirection' => 5,
			'headers'     => self::api_headers( $s ),
		) );
		if ( is_wp_error( $resp ) ) { return $resp; }
		$code = wp_remote_retrieve_response_code( $resp );
		if ( 200 !== $code ) { return new WP_Error( 'http', 'HTTP ' . $code ); }
		$body = wp_remote_retrieve_body( $resp );
		if ( '' === $body ) { return new WP_Error( 'empty', 'Archive vide' ); }
		if ( false === file_put_contents( $dest, $body ) ) {
			return new WP_Error( 'write', 'Ecriture archive impossible' );
		}
		return true;
	}

	private static function sync_recursive( $src, $dst, $backup, $rel, &$stats, &$log ) {
		if ( ! is_dir( $src ) ) { return; }
		foreach ( scandir( $src ) as $item ) {
			if ( '.' === $item || '..' === $item ) { continue; }
			if ( in_array( $item, self::PROTECTED, true ) ) { $stats['skipped']++; continue; }

			$src_path = $src . '/' . $item;
			$dst_path = $dst . '/' . $item;
			$rel_path = '' === $rel ? $item : $rel . '/' . $item;

			if ( is_dir( $src_path ) ) {
				if ( ! is_dir( $dst_path ) ) { wp_mkdir_p( $dst_path ); }
				self::sync_recursive( $src_path, $dst_path, $backup . '/' . $item, $rel_path, $stats, $log );
				continue;
			}

			$ext = strtolower( pathinfo( $item, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, self::ALLOWED_EXT, true ) ) { $stats['skipped']++; continue; }

			$existed = file_exists( $dst_path );
			if ( $existed && md5_file( $src_path ) === md5_file( $dst_path ) ) { continue; }
			if ( $existed ) {
				wp_mkdir_p( dirname( $backup . '/' . $rel_path ) );
				@copy( $dst_path, $backup . '/' . $rel_path );
			}
			if ( @copy( $src_path, $dst_path ) ) {
				if ( $existed ) { $stats['updated']++; $log[] = 'MAJ ' . $rel_path; }
				else { $stats['created']++; $log[] = 'NEW ' . $rel_path; }
			}
		}
	}

	private static function rm_recursive( $dir ) {
		if ( ! is_dir( $dir ) ) { return; }
		foreach ( scandir( $dir ) as $item ) {
			if ( '.' === $item || '..' === $item ) { continue; }
			$p = $dir . '/' . $item;
			if ( is_dir( $p ) ) { self::rm_recursive( $p ); }
			else { @unlink( $p ); }
		}
		@rmdir( $dir );
	}

	/* ----------------------------------------------------------------------
	 * Admin : page + handlers
	 * -------------------------------------------------------------------- */
	public static function menu() {
		add_management_page( 'Sync GitHub', 'Sync GitHub', 'manage_options', 'aphoto-sync', array( __CLASS__, 'render' ) );
	}

	private static function redirect( $args = array() ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => 'aphoto-sync' ), $args ), admin_url( 'tools.php' ) ) );
		exit;
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
		check_admin_referer( 'aphoto_sync_save' );
		update_option( self::SET_OPT, array(
			'repo'          => sanitize_text_field( wp_unslash( $_POST['repo'] ?? '' ) ),
			'branch'        => sanitize_text_field( wp_unslash( $_POST['branch'] ?? 'main' ) ),
			'subdir'        => sanitize_text_field( wp_unslash( $_POST['subdir'] ?? '' ) ),
			'target_key'    => sanitize_text_field( wp_unslash( $_POST['target_key'] ?? 'wp-content' ) ),
			'target_custom' => sanitize_text_field( wp_unslash( $_POST['target_custom'] ?? '' ) ),
			'token'         => sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ),
			'cron_on'       => empty( $_POST['cron_on'] ) ? 0 : 1,
		) );
		delete_transient( 'aphoto_sha_remote' );
		self::redirect( array( 'msg' => 'saved' ) );
	}

	public static function handle_check() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
		check_admin_referer( 'aphoto_sync_check' );
		$sha = self::get_remote_sha( true );
		self::redirect( array( 'msg' => $sha ? 'checked' : 'apierr' ) );
	}

	public static function handle_run() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
		check_admin_referer( 'aphoto_sync_run' );
		$r = self::sync();
		if ( $r['ok'] ) {
			self::redirect( array( 'msg' => 'synced', 'n' => (int) $r['stats']['updated'] + (int) $r['stats']['created'] ) );
		}
		self::redirect( array( 'msg' => 'err', 'e' => rawurlencode( $r['error'] ) ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$s        = self::settings();
		$local    = self::get_local_sha();
		$remote   = self::get_remote_sha( false );
		$last     = self::get_last_time();
		$post_url = admin_url( 'admin-post.php' );
		$up2date  = $remote && $local && $remote === $local;

		echo '<div class="wrap"><h1>🔄 Sync GitHub — Anna Photo</h1>';

		if ( isset( $_GET['msg'] ) ) {
			$m = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
			$notices = array(
				'saved'   => array( 'success', 'Reglages enregistres.' ),
				'checked' => array( 'success', 'Verification effectuee.' ),
				'synced'  => array( 'success', 'Synchronisation terminee : ' . ( isset( $_GET['n'] ) ? (int) $_GET['n'] : 0 ) . ' fichier(s) mis a jour.' ),
				'apierr'  => array( 'error', 'API GitHub injoignable (repo prive ? verifie le token).' ),
				'err'     => array( 'error', 'Erreur : ' . ( isset( $_GET['e'] ) ? esc_html( rawurldecode( wp_unslash( $_GET['e'] ) ) ) : '' ) ),
			);
			if ( isset( $notices[ $m ] ) ) {
				$cls = 'error' === $notices[ $m ][0] ? 'notice-error' : 'notice-success';
				echo '<div class="notice ' . esc_attr( $cls ) . ' is-dismissible"><p>' . esc_html( $notices[ $m ][1] ) . '</p></div>';
			}
		}

		// Etat
		echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;margin:14px 0;max-width:820px;">';
		echo '<h2 style="margin-top:0;">Etat</h2>';
		echo '<p>Version locale : <code>' . esc_html( $local ? $local : '—' ) . '</code><br>';
		echo 'Version sur GitHub : <code>' . esc_html( $remote ? $remote : '—' ) . '</code> ';
		if ( $remote ) {
			echo $up2date
				? '<span style="color:#2a7d2a;font-weight:600;">✓ A jour</span>'
				: '<span style="color:#c0392b;font-weight:600;">⚠ Mise a jour disponible</span>';
		}
		echo '<br>Derniere sync : ' . esc_html( $last ? wp_date( 'd/m/Y H:i', $last ) : 'jamais' ) . '</p>';

		if ( self::is_configured() ) {
			echo '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
			echo '<form method="post" action="' . esc_url( $post_url ) . '"><input type="hidden" name="action" value="aphoto_sync_check">' . wp_nonce_field( 'aphoto_sync_check', '_wpnonce', true, false ) . '<button class="button">Verifier les MAJ</button></form>';
			echo '<form method="post" action="' . esc_url( $post_url ) . '" onsubmit="return confirm(\'Lancer la synchronisation des fichiers depuis GitHub ?\');"><input type="hidden" name="action" value="aphoto_sync_run">' . wp_nonce_field( 'aphoto_sync_run', '_wpnonce', true, false ) . '<button class="button button-primary">Synchroniser maintenant</button></form>';
			echo '</div>';
		} else {
			echo '<p style="color:#c0392b;">⚠ Configure d\'abord le repo ci-dessous.</p>';
		}
		echo '</div>';

		// Reglages
		$presets = array(
			'wp-content' => 'Tout le dossier wp-content (recommande)',
			'theme'      => 'Theme actif uniquement',
			'root'       => 'Racine du site (avance)',
			'custom'     => 'Chemin personnalise',
		);
		echo '<form method="post" action="' . esc_url( $post_url ) . '" style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;margin:14px 0;max-width:820px;">';
		echo '<input type="hidden" name="action" value="aphoto_sync_save">';
		echo wp_nonce_field( 'aphoto_sync_save', '_wpnonce', true, false );
		echo '<h2 style="margin-top:0;">Reglages</h2>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label>Repo GitHub *</label></th><td><input type="text" name="repo" value="' . esc_attr( $s['repo'] ) . '" class="regular-text" placeholder="proprietaire/nom-du-repo"><p class="description">Ex : <code>annaphoto/site</code></p></td></tr>';
		echo '<tr><th><label>Branche</label></th><td><input type="text" name="branch" value="' . esc_attr( $s['branch'] ) . '" placeholder="main"></td></tr>';
		echo '<tr><th><label>Sous-dossier (optionnel)</label></th><td><input type="text" name="subdir" value="' . esc_attr( $s['subdir'] ) . '" class="regular-text" placeholder="laisser vide si le repo = le contenu a copier"><p class="description">Si le repo contient un sous-dossier a copier (ex : <code>wp-content</code>), indique-le ici.</p></td></tr>';
		echo '<tr><th><label>Dossier cible</label></th><td><select name="target_key">';
		foreach ( $presets as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $s['target_key'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> <input type="text" name="target_custom" value="' . esc_attr( $s['target_custom'] ) . '" class="regular-text" placeholder="/chemin/absolu (si personnalise)"></td></tr>';
		echo '<tr><th><label>Token GitHub (repo prive)</label></th><td><input type="password" name="token" value="' . esc_attr( $s['token'] ) . '" class="regular-text" autocomplete="new-password"><p class="description">Laisse vide si le repo est public. Sinon : un <em>Personal Access Token</em> avec acces lecture au repo.</p></td></tr>';
		echo '<tr><th><label>Auto-sync</label></th><td><label><input type="checkbox" name="cron_on" value="1" ' . checked( 1, (int) $s['cron_on'], false ) . '> Verifier et appliquer les MAJ automatiquement toutes les 5 minutes</label></td></tr>';
		echo '</tbody></table>';
		echo '<p><button class="button button-primary">Enregistrer</button></p>';
		echo '</form>';

		// Logs
		$log = self::get_last_log();
		if ( $log ) {
			echo '<div style="background:#1e1e1e;color:#cfcfcf;border-radius:10px;padding:14px;margin:14px 0;max-width:820px;font-family:monospace;font-size:12px;max-height:260px;overflow:auto;">';
			echo '<strong style="color:#fff;">Journal de la derniere sync</strong><br>';
			foreach ( $log as $line ) { echo esc_html( $line ) . '<br>'; }
			echo '</div>';
		}
		$cron = self::get_cron_log();
		if ( $cron ) {
			echo '<details style="max-width:820px;"><summary style="cursor:pointer;">Journal de l\'auto-sync (cron)</summary>';
			echo '<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:12px;margin-top:8px;font-family:monospace;font-size:12px;max-height:240px;overflow:auto;">';
			foreach ( $cron as $line ) { echo esc_html( $line ) . '<br>'; }
			echo '</div></details>';
		}

		echo '<p style="max-width:820px;color:#666;font-size:13px;">🛡️ Securite : <code>wp-config.php</code>, <code>.env</code> et <code>.htaccess</code> ne sont jamais ecrases. Une sauvegarde des fichiers remplaces est creee dans <code>wp-content/uploads/anna-backups/</code> a chaque sync.</p>';

		echo '</div>';
	}
}

register_deactivation_hook( __FILE__, array( 'Anna_GitHub_Sync', 'on_deactivate' ) );
Anna_GitHub_Sync::init();
