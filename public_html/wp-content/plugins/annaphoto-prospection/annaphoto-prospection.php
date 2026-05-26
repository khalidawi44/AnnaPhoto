<?php
/**
 * Plugin Name: Anna Photo — Prospection
 * Description: Centre de controle Anna Photo : suivi prospects, recherche d'entreprises gratuite (API gouv.fr), agent automatique, alertes Telegram, modules optionnels (ambassadeurs, demandes recues). Tout en francais, gratuit, pense pour debuter sans connaissances techniques.
 * Version: 2.0.0
 * Author: Anna Photo
 * Text Domain: annaphoto-prospection
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===========================================================================
 * Constantes
 * ========================================================================= */
define( 'ANN_PROSP_OPT',     'annaphoto_prospects' );
define( 'ANN_SET_OPT',       'annaphoto_prospection_settings' );
define( 'ANN_MOD_OPT',       'annaphoto_modules' );
define( 'ANN_SEARCH_OPT',    'annaphoto_searches' );
define( 'ANN_HISTORY_OPT',   'annaphoto_search_history' );
define( 'ANN_AGENT_LOG_OPT', 'annaphoto_agent_log' );
define( 'ANN_AMBASS_OPT',    'annaphoto_ambassadeurs' );
define( 'ANN_DAILY_LAST',    'annaphoto_daily_last_push' );

define( 'ANN_CRON_DAILY', 'annaphoto_cron_daily' );
define( 'ANN_CRON_AGENT', 'annaphoto_cron_agent' );

/* ===========================================================================
 * Donnees : accesseurs
 * ========================================================================= */
function ann_get_prospects() {
	$d = get_option( ANN_PROSP_OPT, array() );
	return is_array( $d ) ? $d : array();
}
function ann_save_prospects( $list ) {
	update_option( ANN_PROSP_OPT, array_values( $list ) );
}
function ann_get_settings() {
	$d = get_option( ANN_SET_OPT, array() );
	return is_array( $d ) ? $d : array();
}
function ann_setting( $key, $default = '' ) {
	$s = ann_get_settings();
	return isset( $s[ $key ] ) && '' !== $s[ $key ] ? $s[ $key ] : $default;
}
function ann_get_modules() {
	$d = get_option( ANN_MOD_OPT, null );
	if ( ! is_array( $d ) ) {
		$d = array(
			'recherche'    => 1,
			'agent'        => 1,
			'broadcast'    => 1,
			'ambassadeurs' => 0,
			'demandes'     => 0,
		);
		update_option( ANN_MOD_OPT, $d );
	}
	return $d;
}
function ann_module_on( $key ) {
	$m = ann_get_modules();
	return ! empty( $m[ $key ] );
}
function ann_get_searches() {
	$d = get_option( ANN_SEARCH_OPT, array() );
	return is_array( $d ) ? $d : array();
}
function ann_save_searches( $list ) {
	update_option( ANN_SEARCH_OPT, array_values( $list ) );
}
function ann_get_history() {
	$d = get_option( ANN_HISTORY_OPT, array() );
	return is_array( $d ) ? $d : array();
}
function ann_add_history( $entry ) {
	$h = ann_get_history();
	array_unshift( $h, $entry );
	if ( count( $h ) > 20 ) { $h = array_slice( $h, 0, 20 ); }
	update_option( ANN_HISTORY_OPT, $h );
}
function ann_agent_log_add( $line ) {
	$l = get_option( ANN_AGENT_LOG_OPT, array() );
	if ( ! is_array( $l ) ) { $l = array(); }
	array_unshift( $l, '[' . wp_date( 'Y-m-d H:i' ) . '] ' . $line );
	if ( count( $l ) > 80 ) { $l = array_slice( $l, 0, 80 ); }
	update_option( ANN_AGENT_LOG_OPT, $l );
}
function ann_agent_log_get() {
	$l = get_option( ANN_AGENT_LOG_OPT, array() );
	return is_array( $l ) ? $l : array();
}
function ann_get_ambassadeurs() {
	$d = get_option( ANN_AMBASS_OPT, array() );
	return is_array( $d ) ? $d : array();
}
function ann_save_ambassadeurs( $list ) {
	update_option( ANN_AMBASS_OPT, array_values( $list ) );
}

/* ===========================================================================
 * Referentiels
 * ========================================================================= */
function ann_statuses() {
	return array(
		'nouveau'   => array( 'Nouveau',           '#3b82f6' ),
		'contacte'  => array( 'Contacte',          '#6366f1' ),
		'relance'   => array( 'A relancer',        '#f59e0b' ),
		'sans_rep'  => array( 'Sans reponse',      '#94a3b8' ),
		'interesse' => array( 'Interesse',         '#10b981' ),
		'client'    => array( 'Client',            '#059669' ),
		'stop'      => array( 'Ne plus contacter', '#ef4444' ),
	);
}
function ann_status_labels() {
	$out = array();
	foreach ( ann_statuses() as $k => $v ) { $out[ $k ] = $v[0]; }
	return $out;
}
function ann_status_color( $key ) {
	$s = ann_statuses();
	return isset( $s[ $key ] ) ? $s[ $key ][1] : '#64748b';
}
function ann_prestations() {
	return array(
		'mariage'   => 'Mariage',
		'couple'    => 'Couple / EVJF',
		'famille'   => 'Famille / Enfants',
		'grossesse' => 'Grossesse / Naissance',
		'portrait'  => 'Portrait / Book',
		'evenement' => 'Evenement',
		'autre'     => 'Autre',
	);
}
function ann_sources() {
	return array(
		'leboncoin' => 'Leboncoin',
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'forum'     => 'Forum',
		'google'    => 'Google / Annonce',
		'api'       => 'API entreprises',
		'autre'     => 'Autre',
	);
}
function ann_search_presets() {
	return array(
		'wedding'    => array( 'label' => 'Wedding planners',        'q' => 'wedding planner',         'naf' => '7022Z', 'emoji' => '💍' ),
		'salles'     => array( 'label' => 'Salles de reception',     'q' => 'salle reception mariage', 'naf' => '6820B', 'emoji' => '🏰' ),
		'traiteurs'  => array( 'label' => 'Traiteurs',               'q' => 'traiteur evenement',      'naf' => '5621Z', 'emoji' => '🍽️' ),
		'organis'    => array( 'label' => 'Organisateurs evenement', 'q' => 'organisateur evenement',  'naf' => '8230Z', 'emoji' => '🎉' ),
		'photogr'    => array( 'label' => 'Autres photographes',     'q' => 'photographe',             'naf' => '7420Z', 'emoji' => '📸' ),
		'fleuristes' => array( 'label' => 'Fleuristes',              'q' => 'fleuriste mariage',       'naf' => '4776Z', 'emoji' => '💐' ),
	);
}

/* ===========================================================================
 * Telephone
 * ========================================================================= */
function ann_phone_intl( $phone ) {
	$d = preg_replace( '/[^0-9]/', '', (string) $phone );
	if ( '' === $d ) { return ''; }
	if ( strpos( $d, '33' ) === 0 && strlen( $d ) >= 11 ) { return $d; }
	if ( strpos( $d, '0' ) === 0 ) { return '33' . substr( $d, 1 ); }
	return $d;
}
function ann_phone_local( $phone ) {
	return preg_replace( '/[^0-9+]/', '', (string) $phone );
}

/* ===========================================================================
 * Templates messages
 * ========================================================================= */
function ann_tpl_default() {
	return array(
		'mariage' => array(
			"Bonjour {prenom} 😊 Je suis Anna, photographe a {ville}. J'ai vu votre annonce pour votre mariage et j'adorerais en faire partie : un reportage naturel, sans poses figees, plein d'emotion. Je peux vous envoyer mon portfolio et mes formules si vous voulez ✨ (Si vous preferez ne pas etre recontacte, dites-le moi, je retire votre numero.)",
			"Bonjour {prenom} ! Anna, photographe de mariage a {ville}. Votre projet m'a beaucoup parle : je capture les vrais moments, les rires, les larmes, sans vous faire poser pendant des heures. Envie de voir quelques mariages que j'ai photographies ? (Dites-moi si vous ne souhaitez pas etre recontacte.)",
		),
		'couple' => array(
			"Bonjour {prenom} 😊 Je suis Anna, photographe a {ville}. Une seance couple ou EVJF, c'est l'occasion de jolies photos naturelles et fun. Je peux vous montrer des exemples et mes tarifs si ca vous tente ✨ (Si vous ne voulez pas etre recontacte, dites-le moi.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. Pour une seance a deux ou un EVJF, je vous propose un moment detendu et de belles images souvenirs. Je vous envoie des exemples ? (Dites-moi si vous preferez ne pas etre recontacte.)",
		),
		'famille' => array(
			"Bonjour {prenom} 😊 Anna, photographe a {ville}. J'adore les seances famille : des photos vivantes, les enfants restent eux-memes, zero stress. Je vous envoie quelques exemples et mes formules ? (Dites-moi si vous preferez ne pas etre recontacte.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. Une seance famille, c'est de vrais sourires et des souvenirs pour des annees. Envie de voir mon travail et mes tarifs ? (Si vous ne souhaitez pas etre recontacte, dites-le moi.)",
		),
		'grossesse' => array(
			"Bonjour {prenom} 😊 Je suis Anna, photographe a {ville}, j'aime particulierement les seances grossesse et naissance. J'aimerais immortaliser ce moment unique tout en douceur. Je peux vous envoyer mon portfolio et mes tarifs ✨ (Si vous ne souhaitez pas etre recontacte, dites-le moi.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. La grossesse passe si vite : une jolie seance pour garder ce souvenir a vie, ca vous tente ? Je vous montre des exemples. (Dites-moi si vous preferez ne pas etre recontacte.)",
		),
		'portrait' => array(
			"Bonjour {prenom} 😊 Anna, photographe a {ville}. Pour un portrait ou un book, je vous mets a l'aise et on obtient des images dont vous serez fier. Envie de voir des exemples et mes tarifs ? (Dites-moi si vous preferez ne pas etre recontacte.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. Un beau portrait professionnel ou perso, naturel et soigne, ca vous interesse ? Je vous envoie mon portfolio. (Si vous ne souhaitez pas etre recontacte, dites-le moi.)",
		),
		'evenement' => array(
			"Bonjour {prenom} 😊 Je suis Anna, photographe a {ville}. Pour votre evenement, je propose un reportage discret et complet, livre rapidement. Je peux vous envoyer des exemples et un devis ✨ (Si vous ne voulez pas etre recontacte, dites-le moi.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. Anniversaire, bapteme, soiree, evenement pro : je couvre tout en toute discretion. Envie d'un devis et d'exemples ? (Dites-moi si vous preferez ne pas etre recontacte.)",
		),
		'autre' => array(
			"Bonjour {prenom} 😊 Je suis Anna, photographe a {ville}. J'ai vu votre annonce et je pense pouvoir vous aider. Je peux vous envoyer mon portfolio et mes tarifs si vous voulez ✨ (Si vous preferez ne pas etre recontacte, dites-le moi, je retire votre numero.)",
			"Bonjour {prenom} ! Anna, photographe a {ville}. Votre annonce m'a interpellee, j'aimerais vous proposer mes services. Envie de voir mon travail et mes tarifs ? (Dites-moi si vous ne souhaitez pas etre recontacte.)",
		),
	);
}
function ann_tpl_data() {
	$custom = ann_setting( 'templates' );
	if ( is_array( $custom ) && ! empty( $custom ) ) {
		return wp_parse_args( $custom, ann_tpl_default() );
	}
	return ann_tpl_default();
}
function ann_build_message( $prestation, $prenom, $ville, $variation = 0 ) {
	$tpl = ann_tpl_data();
	$key = isset( $tpl[ $prestation ] ) ? $prestation : 'autre';
	$set = is_array( $tpl[ $key ] ) ? $tpl[ $key ] : array( (string) $tpl[ $key ] );
	$idx = ( (int) $variation ) % max( 1, count( $set ) );
	$msg = $set[ $idx ];
	$prenom = trim( (string) $prenom );
	$ville  = trim( (string) $ville );
	$msg = str_replace( '{prenom}', $prenom, $msg );
	$msg = str_replace( '{ville}', '' !== $ville ? $ville : 'votre region', $msg );
	$msg = preg_replace( '/\s{2,}/', ' ', $msg );
	return trim( $msg );
}
function ann_wa_link( $prospect ) {
	$intl = ann_phone_intl( isset( $prospect['phone'] ) ? $prospect['phone'] : '' );
	if ( '' === $intl ) { return ''; }
	return 'https://wa.me/' . $intl . '?text=' . rawurlencode( isset( $prospect['message'] ) ? $prospect['message'] : '' );
}
function ann_sms_link( $prospect ) {
	$loc = ann_phone_local( isset( $prospect['phone'] ) ? $prospect['phone'] : '' );
	if ( '' === $loc ) { return ''; }
	return 'sms:' . $loc . '?body=' . rawurlencode( isset( $prospect['message'] ) ? $prospect['message'] : '' );
}

/* ===========================================================================
 * Telegram
 * ========================================================================= */
function ann_tg_push( $text ) {
	$token = ann_setting( 'tg_token' );
	$chat  = ann_setting( 'tg_chat' );
	if ( '' === $token || '' === $chat ) { return false; }
	wp_remote_post(
		'https://api.telegram.org/bot' . $token . '/sendMessage',
		array(
			'timeout'  => 10,
			'blocking' => false,
			'body'     => array(
				'chat_id'                  => $chat,
				'text'                     => $text,
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
			),
		)
	);
	return true;
}
function ann_tg_configured() {
	return '' !== ann_setting( 'tg_token' ) && '' !== ann_setting( 'tg_chat' );
}

/* ===========================================================================
 * API Recherche d'entreprises (gratuit, sans cle, officiel api.gouv.fr)
 * ========================================================================= */
function ann_api_search( $args ) {
	$args = wp_parse_args( $args, array(
		'q'        => '',
		'cp'       => '',
		'naf'      => '',
		'per_page' => 20,
		'page'     => 1,
	) );
	$params = array(
		'per_page'                       => max( 1, min( 25, (int) $args['per_page'] ) ),
		'page'                           => max( 1, (int) $args['page'] ),
		'limite_matching_etablissements' => 1,
	);
	if ( '' !== $args['q'] )   { $params['q']                   = $args['q']; }
	if ( '' !== $args['cp'] )  { $params['code_postal']         = $args['cp']; }
	if ( '' !== $args['naf'] ) { $params['activite_principale'] = $args['naf']; }

	$url  = add_query_arg( $params, 'https://recherche-entreprises.api.gouv.fr/search' );
	$resp = wp_remote_get( $url, array(
		'timeout' => 15,
		'headers' => array( 'User-Agent' => 'AnnaPhoto-Prospection/2.0' ),
	) );
	if ( is_wp_error( $resp ) ) {
		return array( 'ok' => false, 'error' => $resp->get_error_message(), 'results' => array(), 'total' => 0 );
	}
	$code = wp_remote_retrieve_response_code( $resp );
	if ( 200 !== $code ) {
		return array( 'ok' => false, 'error' => 'HTTP ' . $code, 'results' => array(), 'total' => 0 );
	}
	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( ! is_array( $body ) || ! isset( $body['results'] ) ) {
		return array( 'ok' => false, 'error' => 'Reponse API invalide', 'results' => array(), 'total' => 0 );
	}
	return array(
		'ok'      => true,
		'error'   => '',
		'results' => $body['results'],
		'total'   => isset( $body['total_results'] ) ? (int) $body['total_results'] : 0,
	);
}
function ann_api_extract( $row ) {
	$nom = '';
	if ( ! empty( $row['nom_complet'] ) )            { $nom = $row['nom_complet']; }
	elseif ( ! empty( $row['nom_raison_sociale'] ) ) { $nom = $row['nom_raison_sociale']; }
	$siege = isset( $row['siege'] ) && is_array( $row['siege'] ) ? $row['siege'] : array();
	$adr = ( isset( $siege['adresse'] ) ? $siege['adresse'] : '' ) . ' ' .
	       ( isset( $siege['code_postal'] ) ? $siege['code_postal'] : '' ) . ' ' .
	       ( isset( $siege['libelle_commune'] ) ? $siege['libelle_commune'] : ( isset( $siege['commune'] ) ? $siege['commune'] : '' ) );
	$ville = isset( $siege['libelle_commune'] ) ? $siege['libelle_commune'] : ( isset( $siege['commune'] ) ? $siege['commune'] : '' );
	$siret = isset( $siege['siret'] ) ? $siege['siret'] : ( isset( $row['siren'] ) ? $row['siren'] : '' );
	return array(
		'nom'      => $nom,
		'adresse'  => trim( preg_replace( '/\s+/', ' ', $adr ) ),
		'ville'    => $ville,
		'activite' => isset( $row['libelle_activite_principale'] ) ? $row['libelle_activite_principale'] : ( isset( $row['activite_principale'] ) ? $row['activite_principale'] : '' ),
		'siret'    => $siret,
	);
}

/* ===========================================================================
 * Activation / desactivation cron
 * ========================================================================= */
register_activation_hook( __FILE__, 'ann_on_activate' );
register_deactivation_hook( __FILE__, 'ann_on_deactivate' );
function ann_on_activate() {
	if ( ! wp_next_scheduled( ANN_CRON_DAILY ) ) {
		wp_schedule_event( time() + 300, 'hourly', ANN_CRON_DAILY );
	}
	if ( ! wp_next_scheduled( ANN_CRON_AGENT ) ) {
		wp_schedule_event( time() + 600, 'hourly', ANN_CRON_AGENT );
	}
}
function ann_on_deactivate() {
	foreach ( array( ANN_CRON_DAILY, ANN_CRON_AGENT ) as $hook ) {
		$ts = wp_next_scheduled( $hook );
		if ( $ts ) { wp_unschedule_event( $ts, $hook ); }
	}
}

/* ===========================================================================
 * Cron : feuille de route matin (entre 8h et 11h, une fois par jour)
 * ========================================================================= */
add_action( ANN_CRON_DAILY, 'ann_cron_daily_run' );
function ann_cron_daily_run() {
	if ( empty( ann_setting( 'cron_morning' ) ) ) { return; }
	if ( ! ann_tg_configured() ) { return; }
	$today = wp_date( 'Y-m-d' );
	$last  = get_option( ANN_DAILY_LAST, '' );
	if ( $last === $today ) { return; }
	$hour = (int) wp_date( 'H' );
	if ( $hour < 8 || $hour > 11 ) { return; }
	$counts = ann_counters();
	$lines  = array();
	$lines[] = '☀️ <b>Bonjour Anna</b> — feuille de route du jour';
	$lines[] = '';
	$lines[] = '📋 <b>' . $counts['nouveau']  . '</b> nouveau(x) prospect(s) a contacter';
	$lines[] = '🔁 <b>' . $counts['relance']  . '</b> a relancer';
	$lines[] = '⏳ <b>' . $counts['sans_rep'] . '</b> sans reponse';
	$lines[] = '✨ <b>' . $counts['interesse'] . '</b> interesse(s)';
	$lines[] = '';
	if ( $counts['nouveau'] + $counts['relance'] === 0 ) {
		$lines[] = '⚠️ Aucune vente en attente : pense a la prospection aujourd\'hui !';
	} else {
		$lines[] = 'Bonne journee ! 📸';
	}
	ann_tg_push( implode( "\n", $lines ) );
	update_option( ANN_DAILY_LAST, $today );
}

/* ===========================================================================
 * Cron : agent automatique
 * ========================================================================= */
add_action( ANN_CRON_AGENT, 'ann_cron_agent_run' );
function ann_cron_agent_run() {
	if ( ! ann_module_on( 'agent' ) ) { return; }
	$searches = ann_get_searches();
	if ( empty( $searches ) ) { return; }
	$now = time();
	$changed = false;
	foreach ( $searches as $idx => $s ) {
		if ( empty( $s['active'] ) ) { continue; }
		$freq = max( 3600, (int) ( isset( $s['freq'] ) ? $s['freq'] : 86400 ) );
		$last = (int) ( isset( $s['last_run'] ) ? $s['last_run'] : 0 );
		if ( $now - $last < $freq ) { continue; }

		$res = ann_api_search( array(
			'q'   => isset( $s['q'] ) ? $s['q'] : '',
			'cp'  => isset( $s['cp'] ) ? $s['cp'] : '',
			'naf' => isset( $s['naf'] ) ? $s['naf'] : '',
		) );
		$seen      = ( isset( $s['seen'] ) && is_array( $s['seen'] ) ) ? $s['seen'] : array();
		$new       = 0;
		$new_names = array();
		if ( $res['ok'] ) {
			foreach ( $res['results'] as $row ) {
				$ex = ann_api_extract( $row );
				if ( '' === $ex['siret'] ) { continue; }
				if ( in_array( $ex['siret'], $seen, true ) ) { continue; }
				$seen[] = $ex['siret'];
				$new++;
				if ( count( $new_names ) < 5 ) { $new_names[] = $ex['nom']; }
			}
			ann_agent_log_add( 'Agent "' . ( isset( $s['label'] ) ? $s['label'] : $s['q'] ) . '" : ' . $new . ' nouveau(x) sur ' . $res['total'] );
		} else {
			ann_agent_log_add( 'Agent "' . ( isset( $s['label'] ) ? $s['label'] : $s['q'] ) . '" : ERREUR ' . $res['error'] );
		}
		if ( count( $seen ) > 500 ) { $seen = array_slice( $seen, -500 ); }
		$searches[ $idx ]['seen']     = $seen;
		$searches[ $idx ]['last_run'] = $now;
		$searches[ $idx ]['last_new'] = $new;
		$changed = true;

		if ( $new > 0 && ! empty( $s['notify_tg'] ) && ann_tg_configured() ) {
			$msg  = '🔔 <b>' . ( isset( $s['label'] ) ? $s['label'] : 'Recherche' ) . '</b> — ' . $new . ' nouveau(x)';
			if ( ! empty( $new_names ) ) {
				$msg .= "\n" . implode( "\n", array_map( function ( $n ) { return '• ' . $n; }, $new_names ) );
			}
			ann_tg_push( $msg );
		}
	}
	if ( $changed ) { ann_save_searches( $searches ); }
}

/* ===========================================================================
 * Compteurs
 * ========================================================================= */
function ann_counters() {
	$counts = array_fill_keys( array_keys( ann_statuses() ), 0 );
	$counts['total'] = 0;
	foreach ( ann_get_prospects() as $p ) {
		$st = isset( $p['status'] ) ? $p['status'] : 'nouveau';
		if ( isset( $counts[ $st ] ) ) { $counts[ $st ]++; }
		$counts['total']++;
	}
	return $counts;
}

/* ===========================================================================
 * Menu admin
 * ========================================================================= */
add_action( 'admin_menu', 'ann_admin_menu' );
function ann_admin_menu() {
	add_menu_page( 'Anna Photo', 'Anna Photo', 'manage_options', 'ann-hub', 'ann_render_hub', 'dashicons-camera-alt', 26 );
	add_submenu_page( 'ann-hub', 'Centre de controle', 'Centre de controle', 'manage_options', 'ann-hub', 'ann_render_hub' );
	add_submenu_page( 'ann-hub', 'Prospects', 'Prospects', 'manage_options', 'ann-prospects', 'ann_render_prospects_page' );
	if ( ann_module_on( 'recherche' ) ) {
		add_submenu_page( 'ann-hub', 'Recherche entreprises', 'Recherche entreprises', 'manage_options', 'ann-search', 'ann_render_search_page' );
	}
	if ( ann_module_on( 'agent' ) ) {
		add_submenu_page( 'ann-hub', 'Agent automatique', 'Agent automatique', 'manage_options', 'ann-agent', 'ann_render_agent_page' );
	}
	if ( ann_module_on( 'ambassadeurs' ) ) {
		add_submenu_page( 'ann-hub', 'Ambassadeurs', 'Ambassadeurs', 'manage_options', 'ann-ambass', 'ann_render_ambass_page' );
	}
	add_submenu_page( 'ann-hub', 'Reglages', 'Reglages', 'manage_options', 'ann-settings', 'ann_render_settings_page' );
}

function ann_redirect( $page, $args = array() ) {
	$url = add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	wp_safe_redirect( $url );
	exit;
}
function ann_check_admin() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
}

/* ===========================================================================
 * Handlers prospects
 * ========================================================================= */
add_action( 'admin_post_ann_add', function () {
	ann_check_admin();
	check_admin_referer( 'ann_add' );

	$phone = sanitize_text_field( wp_unslash( isset( $_POST['phone'] ) ? $_POST['phone'] : '' ) );
	$intl  = ann_phone_intl( $phone );
	if ( '' === $intl ) { ann_redirect( 'ann-prospects', array( 'msg' => 'phone' ) ); }

	$list = ann_get_prospects();
	foreach ( $list as $p ) {
		if ( ann_phone_intl( isset( $p['phone'] ) ? $p['phone'] : '' ) === $intl ) {
			ann_redirect( 'ann-prospects', array( 'msg' => 'dup' ) );
		}
	}
	$prestation = sanitize_text_field( wp_unslash( isset( $_POST['prestation'] ) ? $_POST['prestation'] : 'autre' ) );
	$prenom     = sanitize_text_field( wp_unslash( isset( $_POST['prenom'] ) ? $_POST['prenom'] : '' ) );
	$ville      = sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) );
	$message    = sanitize_textarea_field( wp_unslash( isset( $_POST['message'] ) ? $_POST['message'] : '' ) );
	if ( '' === $message ) { $message = ann_build_message( $prestation, $prenom, $ville ); }

	$entry = array(
		'id'         => uniqid( 'p_' ),
		'prenom'     => $prenom,
		'phone'      => $phone,
		'link'       => esc_url_raw( wp_unslash( isset( $_POST['link'] ) ? $_POST['link'] : '' ) ),
		'source'     => sanitize_text_field( wp_unslash( isset( $_POST['source'] ) ? $_POST['source'] : 'autre' ) ),
		'prestation' => $prestation,
		'ville'      => $ville,
		'note'       => sanitize_textarea_field( wp_unslash( isset( $_POST['note'] ) ? $_POST['note'] : '' ) ),
		'message'    => $message,
		'status'     => 'nouveau',
		'created'    => current_time( 'Y-m-d H:i' ),
	);
	array_unshift( $list, $entry );
	ann_save_prospects( $list );

	$labels = ann_prestations();
	$plabel = isset( $labels[ $prestation ] ) ? $labels[ $prestation ] : '';
	ann_tg_push( '📋 Nouveau prospect : ' . ( '' !== $prenom ? $prenom : $phone ) . ' — ' . $plabel );
	ann_redirect( 'ann-prospects', array( 'msg' => 'added' ) );
} );

add_action( 'admin_post_ann_update', function () {
	ann_check_admin();
	check_admin_referer( 'ann_update' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_prospects();
	foreach ( $list as &$p ) {
		if ( isset( $p['id'] ) && $p['id'] === $id ) {
			if ( isset( $_POST['status'] ) )  { $p['status']  = sanitize_text_field( wp_unslash( $_POST['status'] ) ); }
			if ( isset( $_POST['note'] ) )    { $p['note']    = sanitize_textarea_field( wp_unslash( $_POST['note'] ) ); }
			if ( isset( $_POST['message'] ) ) { $p['message'] = sanitize_textarea_field( wp_unslash( $_POST['message'] ) ); }
			break;
		}
	}
	unset( $p );
	ann_save_prospects( $list );
	ann_redirect( 'ann-prospects', array( 'msg' => 'updated' ) );
} );

add_action( 'admin_post_ann_delete', function () {
	ann_check_admin();
	check_admin_referer( 'ann_delete' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_prospects(), function ( $p ) use ( $id ) {
		return ! ( isset( $p['id'] ) && $p['id'] === $id );
	} );
	ann_save_prospects( $list );
	ann_redirect( 'ann-prospects', array( 'msg' => 'deleted' ) );
} );

add_action( 'admin_post_ann_add_from_api', function () {
	ann_check_admin();
	check_admin_referer( 'ann_add_from_api' );
	$nom   = sanitize_text_field( wp_unslash( isset( $_POST['nom'] ) ? $_POST['nom'] : '' ) );
	$ville = sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) );
	$adr   = sanitize_text_field( wp_unslash( isset( $_POST['adresse'] ) ? $_POST['adresse'] : '' ) );
	$siret = sanitize_text_field( wp_unslash( isset( $_POST['siret'] ) ? $_POST['siret'] : '' ) );

	$list = ann_get_prospects();
	foreach ( $list as $p ) {
		if ( '' !== $siret && false !== strpos( isset( $p['note'] ) ? $p['note'] : '', 'SIRET:' . $siret ) ) {
			ann_redirect( 'ann-search', array( 'msg' => 'dup_api' ) );
		}
	}
	$entry = array(
		'id'         => uniqid( 'p_' ),
		'prenom'     => $nom,
		'phone'      => '',
		'link'       => '' !== $siret ? 'https://annuaire-entreprises.data.gouv.fr/entreprise/' . $siret : '',
		'source'     => 'api',
		'prestation' => sanitize_text_field( wp_unslash( isset( $_POST['prestation'] ) ? $_POST['prestation'] : 'autre' ) ),
		'ville'      => $ville,
		'note'       => trim( $adr . ( '' !== $siret ? ' | SIRET:' . $siret : '' ) ),
		'message'    => '',
		'status'     => 'nouveau',
		'created'    => current_time( 'Y-m-d H:i' ),
	);
	array_unshift( $list, $entry );
	ann_save_prospects( $list );
	ann_redirect( 'ann-search', array( 'msg' => 'added_api' ) );
} );

/* ===========================================================================
 * Handlers recherches auto
 * ========================================================================= */
add_action( 'admin_post_ann_search_save', function () {
	ann_check_admin();
	check_admin_referer( 'ann_search_save' );
	$list = ann_get_searches();
	$list[] = array(
		'id'        => uniqid( 's_' ),
		'label'     => sanitize_text_field( wp_unslash( isset( $_POST['label'] ) ? $_POST['label'] : 'Recherche' ) ),
		'q'         => sanitize_text_field( wp_unslash( isset( $_POST['q'] ) ? $_POST['q'] : '' ) ),
		'cp'        => sanitize_text_field( wp_unslash( isset( $_POST['cp'] ) ? $_POST['cp'] : '' ) ),
		'naf'       => sanitize_text_field( wp_unslash( isset( $_POST['naf'] ) ? $_POST['naf'] : '' ) ),
		'freq'      => max( 3600, (int) ( isset( $_POST['freq'] ) ? $_POST['freq'] : 86400 ) ),
		'active'    => 1,
		'notify_tg' => empty( $_POST['notify_tg'] ) ? 0 : 1,
		'seen'      => array(),
		'last_run'  => 0,
		'last_new'  => 0,
		'created'   => current_time( 'Y-m-d H:i' ),
	);
	ann_save_searches( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'saved' ) );
} );
add_action( 'admin_post_ann_search_toggle', function () {
	ann_check_admin();
	check_admin_referer( 'ann_search_toggle' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_searches();
	foreach ( $list as &$s ) {
		if ( isset( $s['id'] ) && $s['id'] === $id ) {
			$s['active'] = empty( $s['active'] ) ? 1 : 0;
			break;
		}
	}
	unset( $s );
	ann_save_searches( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'toggled' ) );
} );
add_action( 'admin_post_ann_search_delete', function () {
	ann_check_admin();
	check_admin_referer( 'ann_search_delete' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_searches(), function ( $s ) use ( $id ) {
		return ! ( isset( $s['id'] ) && $s['id'] === $id );
	} );
	ann_save_searches( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'deleted' ) );
} );

/* ===========================================================================
 * Handlers reglages
 * ========================================================================= */
add_action( 'admin_post_ann_settings', function () {
	ann_check_admin();
	check_admin_referer( 'ann_settings' );
	$existing = ann_get_settings();
	$new = array(
		'ville'        => sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) ),
		'tg_token'     => sanitize_text_field( wp_unslash( isset( $_POST['tg_token'] ) ? $_POST['tg_token'] : '' ) ),
		'tg_chat'      => sanitize_text_field( wp_unslash( isset( $_POST['tg_chat'] ) ? $_POST['tg_chat'] : '' ) ),
		'cron_morning' => empty( $_POST['cron_morning'] ) ? 0 : 1,
		'templates'    => isset( $existing['templates'] ) ? $existing['templates'] : array(),
	);
	update_option( ANN_SET_OPT, $new );
	$mods = array(
		'recherche'    => empty( $_POST['mod_recherche'] )    ? 0 : 1,
		'agent'        => empty( $_POST['mod_agent'] )        ? 0 : 1,
		'broadcast'    => empty( $_POST['mod_broadcast'] )    ? 0 : 1,
		'ambassadeurs' => empty( $_POST['mod_ambassadeurs'] ) ? 0 : 1,
		'demandes'     => empty( $_POST['mod_demandes'] )     ? 0 : 1,
	);
	update_option( ANN_MOD_OPT, $mods );
	ann_redirect( 'ann-settings', array( 'msg' => 'saved' ) );
} );

add_action( 'admin_post_ann_test_tg', function () {
	ann_check_admin();
	check_admin_referer( 'ann_test_tg' );
	$ok = ann_tg_push( '✅ Test Anna Photo : Telegram fonctionne !' );
	ann_redirect( 'ann-settings', array( 'msg' => $ok ? 'tg_ok' : 'tg_ko' ) );
} );

add_action( 'admin_post_ann_broadcast', function () {
	ann_check_admin();
	check_admin_referer( 'ann_broadcast' );
	$txt = sanitize_textarea_field( wp_unslash( isset( $_POST['text'] ) ? $_POST['text'] : '' ) );
	if ( '' === $txt ) { ann_redirect( 'ann-hub', array( 'msg' => 'bc_empty' ) ); }
	$ok = ann_tg_push( $txt );
	ann_redirect( 'ann-hub', array( 'msg' => $ok ? 'bc_ok' : 'bc_ko' ) );
} );

/* ===========================================================================
 * Handlers ambassadeurs
 * ========================================================================= */
add_action( 'admin_post_ann_amb_add', function () {
	ann_check_admin();
	check_admin_referer( 'ann_amb_add' );
	$list = ann_get_ambassadeurs();
	$list[] = array(
		'id'       => uniqid( 'a_' ),
		'nom'      => sanitize_text_field( wp_unslash( isset( $_POST['nom'] ) ? $_POST['nom'] : '' ) ),
		'phone'    => sanitize_text_field( wp_unslash( isset( $_POST['phone'] ) ? $_POST['phone'] : '' ) ),
		'email'    => sanitize_email( wp_unslash( isset( $_POST['email'] ) ? $_POST['email'] : '' ) ),
		'filleuls' => 0,
		'note'     => sanitize_textarea_field( wp_unslash( isset( $_POST['note'] ) ? $_POST['note'] : '' ) ),
		'created'  => current_time( 'Y-m-d H:i' ),
	);
	ann_save_ambassadeurs( $list );
	ann_redirect( 'ann-ambass', array( 'msg' => 'added' ) );
} );
add_action( 'admin_post_ann_amb_delete', function () {
	ann_check_admin();
	check_admin_referer( 'ann_amb_delete' );
	$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_ambassadeurs(), function ( $a ) use ( $id ) {
		return ! ( isset( $a['id'] ) && $a['id'] === $id );
	} );
	ann_save_ambassadeurs( $list );
	ann_redirect( 'ann-ambass', array( 'msg' => 'deleted' ) );
} );

/* ===========================================================================
 * Notices
 * ========================================================================= */
function ann_notice() {
	if ( empty( $_GET['msg'] ) ) { return; }
	$m = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
	$map = array(
		'added'     => array( 'success', 'Prospect ajoute. Tu peux cliquer sur WhatsApp ou SMS pour envoyer le message.' ),
		'added_api' => array( 'success', 'Entreprise ajoutee a tes prospects.' ),
		'updated'   => array( 'success', 'Modifications enregistrees.' ),
		'deleted'   => array( 'success', 'Supprime.' ),
		'saved'     => array( 'success', 'Enregistre.' ),
		'toggled'   => array( 'success', 'Etat modifie.' ),
		'tg_ok'     => array( 'success', 'Message de test Telegram envoye. Verifie ton telephone.' ),
		'tg_ko'     => array( 'error',   'Telegram non configure : remplis le Token et le Chat ID.' ),
		'phone'     => array( 'error',   'Numero de telephone invalide. Exemple : 06 12 34 56 78' ),
		'dup'       => array( 'error',   'Ce numero est deja dans ta liste (anti-doublon).' ),
		'dup_api'   => array( 'error',   'Cette entreprise (SIRET) est deja dans ta liste.' ),
		'bc_ok'     => array( 'success', 'Message Telegram envoye.' ),
		'bc_ko'     => array( 'error',   'Telegram non configure.' ),
		'bc_empty'  => array( 'error',   'Message vide.' ),
	);
	if ( ! isset( $map[ $m ] ) ) { return; }
	$cls = 'error' === $map[ $m ][0] ? 'notice-error' : 'notice-success';
	echo '<div class="notice ' . esc_attr( $cls ) . ' is-dismissible"><p>' . esc_html( $map[ $m ][1] ) . '</p></div>';
}

/* ===========================================================================
 * CSS commun
 * ========================================================================= */
function ann_css() {
	?>
	<style>
	.ann-wrap{max-width:1280px;}
	.ann-h1{display:flex;align-items:center;gap:10px;font-size:24px;margin:14px 0 8px;}
	.ann-help,.ann-card,.ann-legal{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;margin:14px 0;}
	.ann-help{background:#eff6ff;border-color:#bfdbfe;}
	.ann-legal{background:#fffbeb;border-color:#fde68a;font-size:13px;}
	.ann-grid{display:grid;gap:14px;}
	.ann-grid-3{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}
	.ann-grid-5{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));}
	.ann-grid-2c{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
	.ann-grid-2c label{display:block;font-weight:600;margin-bottom:4px;}
	.ann-grid-2c input[type=text],.ann-grid-2c input[type=email],.ann-grid-2c select,.ann-grid-2c textarea{width:100%;}
	.ann-full{grid-column:1/-1;}
	.ann-stat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;transition:transform .15s;text-decoration:none;color:inherit;}
	.ann-stat:hover{transform:translateY(-2px);}
	.ann-stat .n{font-size:28px;font-weight:700;line-height:1;}
	.ann-stat .l{color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:.04em;}
	.ann-stat .ico{position:absolute;top:12px;right:12px;font-size:22px;opacity:.7;}
	.ann-stat.urgent{background:#fef2f2;border-color:#fecaca;}
	.ann-stat.urgent .n{color:#dc2626;}
	.ann-action{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:6px;transition:all .15s;}
	.ann-action:hover{border-color:#3b82f6;transform:translateY(-2px);box-shadow:0 4px 12px rgba(59,130,246,.1);}
	.ann-action .t{font-weight:600;font-size:15px;color:#0f172a;display:flex;align-items:center;gap:8px;}
	.ann-action .d{color:#64748b;font-size:13px;line-height:1.4;}
	.ann-action .arr{color:#3b82f6;font-size:13px;margin-top:auto;font-weight:600;}
	.ann-section-title{font-size:14px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.06em;margin:20px 0 8px;display:flex;align-items:center;gap:6px;}
	.ann-pill{display:inline-block;padding:2px 10px;border-radius:12px;font-size:12px;background:#f1f5f9;color:#475569;}
	.ann-status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;vertical-align:middle;}
	.ann-btn-wa{display:inline-block;background:#25D366;color:#fff!important;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;font-size:12px;}
	.ann-btn-sms{display:inline-block;background:#0a66c2;color:#fff!important;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;font-size:12px;}
	.ann-btn-add{display:inline-block;background:#10b981;color:#fff!important;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;font-size:12px;border:0;cursor:pointer;}
	.ann-table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;}
	.ann-table th{background:#f8fafc;padding:10px;text-align:left;font-size:12px;text-transform:uppercase;color:#475569;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;}
	.ann-table td{padding:10px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:13px;}
	.ann-table tr:hover{background:#f8fafc;}
	.ann-preset{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;margin:0 6px 6px 0;text-decoration:none;color:inherit;font-size:13px;transition:all .15s;}
	.ann-preset:hover{border-color:#3b82f6;background:#eff6ff;color:#1d4ed8;}
	.ann-state-line{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:13px;}
	.ann-state-line:last-child{border:0;}
	.ann-ok{color:#059669;font-weight:600;}
	.ann-ko{color:#dc2626;font-weight:600;}
	</style>
	<?php
}

/* ===========================================================================
 * Page : Centre de controle
 * ========================================================================= */
function ann_render_hub() {
	ann_check_admin();
	$counts = ann_counters();
	$mods   = ann_get_modules();
	$ville  = ann_setting( 'ville', '—' );
	$tg_ok  = ann_tg_configured();
	$post   = admin_url( 'admin-post.php' );
	$next_prospects = array_filter( ann_get_prospects(), function ( $p ) {
		return in_array( isset( $p['status'] ) ? $p['status'] : 'nouveau', array( 'nouveau', 'relance' ), true );
	} );
	$next_prospects = array_slice( $next_prospects, 0, 10 );
	$labels = ann_prestations();
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">📸 Centre de controle — Anna Photo</h1>
		<?php ann_notice(); ?>

		<div class="ann-help">
			<strong>☀️ Ta feuille de route du jour</strong>
			<ul style="margin:8px 0 0 18px;">
				<li><b><?php echo (int) $counts['nouveau']; ?></b> prospect(s) a contacter</li>
				<li><b><?php echo (int) $counts['relance']; ?></b> a relancer</li>
				<?php if ( 0 === $counts['nouveau'] + $counts['relance'] ) : ?>
					<li style="color:#b45309;">⚠️ Aucune vente en attente — pense a la prospection aujourd'hui !</li>
				<?php endif; ?>
			</ul>
			<p style="margin:10px 0 0;color:#64748b;font-size:13px;">
				Envoi auto sur Telegram chaque matin 8h :
				<?php echo ( ! empty( ann_setting( 'cron_morning' ) ) && $tg_ok ) ? '<span class="ann-ok">✓ Active</span>' : '<span class="ann-ko">✗ Inactif</span>'; ?>
			</p>
		</div>

		<div class="ann-grid ann-grid-5">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=nouveau' ) ); ?>" class="ann-stat <?php echo $counts['nouveau'] > 0 ? 'urgent' : ''; ?>">
				<span class="ico">📋</span><span class="n"><?php echo (int) $counts['nouveau']; ?></span><span class="l">A contacter</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=relance' ) ); ?>" class="ann-stat">
				<span class="ico">🔁</span><span class="n"><?php echo (int) $counts['relance']; ?></span><span class="l">A relancer</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=sans_rep' ) ); ?>" class="ann-stat">
				<span class="ico">⏳</span><span class="n"><?php echo (int) $counts['sans_rep']; ?></span><span class="l">Sans reponse</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=interesse' ) ); ?>" class="ann-stat">
				<span class="ico">✨</span><span class="n"><?php echo (int) $counts['interesse']; ?></span><span class="l">Interesses</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=client' ) ); ?>" class="ann-stat">
				<span class="ico">💖</span><span class="n"><?php echo (int) $counts['client']; ?></span><span class="l">Clients</span>
			</a>
		</div>

		<div class="ann-section-title">⚡ Tout piloter d'ici</div>
		<div class="ann-grid ann-grid-3">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects' ) ); ?>" class="ann-action">
				<span class="t">📋 Prospection / CRM</span>
				<span class="d">Gere tes prospects, change les statuts, envoie WhatsApp/SMS en 1 clic.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php if ( ann_module_on( 'recherche' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-search' ) ); ?>" class="ann-action">
				<span class="t">🔍 Recherche entreprises</span>
				<span class="d">Trouve des partenaires (wedding planners, salles, traiteurs…) gratuitement via l'API officielle.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php endif; ?>
			<?php if ( ann_module_on( 'agent' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-agent' ) ); ?>" class="ann-action">
				<span class="t">🤖 Agent automatique</span>
				<span class="d">Cherche tout seul toutes les X heures et t'alerte sur Telegram quand il trouve du nouveau.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php endif; ?>
			<?php if ( ann_module_on( 'ambassadeurs' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-ambass' ) ); ?>" class="ann-action">
				<span class="t">🤝 Ambassadeurs</span>
				<span class="d">Programme de parrainage clients : suis qui t'a recommande qui.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-settings' ) ); ?>" class="ann-action">
				<span class="t">⚙️ Reglages</span>
				<span class="d">Ville, Telegram, modules a activer.</span>
				<span class="arr">Ouvrir →</span>
			</a>
		</div>

		<div class="ann-grid" style="grid-template-columns:2fr 1fr;align-items:start;">
			<div>
				<div class="ann-section-title">📞 Prochains prospects a contacter</div>
				<table class="ann-table">
					<thead><tr><th>Prospect</th><th>Prestation</th><th>Statut</th><th>Action</th></tr></thead>
					<tbody>
						<?php if ( empty( $next_prospects ) ) : ?>
							<tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px;">Aucun prospect en attente 👌 — c'est le moment d'aller en chercher de nouveaux !</td></tr>
						<?php endif; ?>
						<?php foreach ( $next_prospects as $p ) :
							$wa  = ann_wa_link( $p );
							$sms = ann_sms_link( $p );
							$st  = isset( $p['status'] ) ? $p['status'] : 'nouveau';
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( ! empty( $p['prenom'] ) ? $p['prenom'] : '(sans nom)' ); ?></strong>
									<?php if ( ! empty( $p['phone'] ) ) : ?><br><small><?php echo esc_html( $p['phone'] ); ?></small><?php endif; ?>
								</td>
								<td><span class="ann-pill"><?php echo esc_html( isset( $labels[ $p['prestation'] ] ) ? $labels[ $p['prestation'] ] : '' ); ?></span></td>
								<td><span class="ann-status-dot" style="background:<?php echo esc_attr( ann_status_color( $st ) ); ?>;"></span><?php $sl = ann_status_labels(); echo esc_html( isset( $sl[ $st ] ) ? $sl[ $st ] : $st ); ?></td>
								<td>
									<?php if ( '' !== $wa ) : ?><a class="ann-btn-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
									<?php if ( '' !== $sms ) : ?> <a class="ann-btn-sms" href="<?php echo esc_url( $sms ); ?>">SMS</a><?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div>
				<?php if ( ann_module_on( 'broadcast' ) && $tg_ok ) : ?>
					<div class="ann-section-title">📣 Diffuser sur Telegram</div>
					<form method="post" action="<?php echo esc_url( $post ); ?>" class="ann-card">
						<input type="hidden" name="action" value="ann_broadcast">
						<?php wp_nonce_field( 'ann_broadcast' ); ?>
						<textarea name="text" rows="3" style="width:100%;" placeholder="Ex : ✨ Offre flash -15% sur le pack mariage !"></textarea>
						<p style="margin:8px 0 0;"><button class="button button-primary">Envoyer maintenant</button></p>
					</form>
				<?php endif; ?>

				<div class="ann-section-title">🩺 Etat des reglages</div>
				<div class="ann-card">
					<div class="ann-state-line"><span>Ville</span><span><?php echo esc_html( $ville ); ?></span></div>
					<div class="ann-state-line"><span>Telegram</span><span class="<?php echo $tg_ok ? 'ann-ok' : 'ann-ko'; ?>"><?php echo $tg_ok ? '✓ Connecte' : '✗ A configurer'; ?></span></div>
					<div class="ann-state-line"><span>Auto matin 8h</span><span class="<?php echo ! empty( ann_setting( 'cron_morning' ) ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( ann_setting( 'cron_morning' ) ) ? '✓ Active' : '✗ Inactif'; ?></span></div>
					<div class="ann-state-line"><span>Module Recherche</span><span class="<?php echo ! empty( $mods['recherche'] ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( $mods['recherche'] ) ? '✓' : '✗'; ?></span></div>
					<div class="ann-state-line"><span>Module Agent auto</span><span class="<?php echo ! empty( $mods['agent'] ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( $mods['agent'] ) ? '✓' : '✗'; ?></span></div>
					<div class="ann-state-line"><span>Module Ambassadeurs</span><span class="<?php echo ! empty( $mods['ambassadeurs'] ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( $mods['ambassadeurs'] ) ? '✓' : '✗'; ?></span></div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/* ===========================================================================
 * Page : Prospects
 * ========================================================================= */
function ann_render_prospects_page() {
	ann_check_admin();
	$statuses    = ann_status_labels();
	$prestations = ann_prestations();
	$sources     = ann_sources();
	$list        = ann_get_prospects();
	$f_status     = sanitize_text_field( wp_unslash( isset( $_GET['f_status'] ) ? $_GET['f_status'] : '' ) );
	$f_source     = sanitize_text_field( wp_unslash( isset( $_GET['f_source'] ) ? $_GET['f_source'] : '' ) );
	$f_prestation = sanitize_text_field( wp_unslash( isset( $_GET['f_prestation'] ) ? $_GET['f_prestation'] : '' ) );
	$search       = sanitize_text_field( wp_unslash( isset( $_GET['s'] ) ? $_GET['s'] : '' ) );
	$filtered = array_filter( $list, function ( $p ) use ( $f_status, $f_source, $f_prestation, $search ) {
		if ( '' !== $f_status && ( ! isset( $p['status'] ) || $p['status'] !== $f_status ) ) { return false; }
		if ( '' !== $f_source && ( ! isset( $p['source'] ) || $p['source'] !== $f_source ) ) { return false; }
		if ( '' !== $f_prestation && ( ! isset( $p['prestation'] ) || $p['prestation'] !== $f_prestation ) ) { return false; }
		if ( '' !== $search ) {
			$hay = strtolower( ( isset( $p['prenom'] ) ? $p['prenom'] : '' ) . ' ' . ( isset( $p['phone'] ) ? $p['phone'] : '' ) . ' ' . ( isset( $p['note'] ) ? $p['note'] : '' ) );
			if ( false === strpos( $hay, strtolower( $search ) ) ) { return false; }
		}
		return true;
	} );
	$ville_def = ann_setting( 'ville', 'votre region' );
	$base = admin_url( 'admin.php?page=ann-prospects' );
	$post = admin_url( 'admin-post.php' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">📋 Prospects</h1>
		<?php ann_notice(); ?>

		<div class="ann-help">
			<strong>Comment ca marche :</strong>
			<ol style="margin:6px 0 0 18px;">
				<li>Tu trouves une annonce (Leboncoin, Facebook, Insta, forum...).</li>
				<li>Tu ajoutes le prospect : numero + lien. Le message se remplit tout seul.</li>
				<li>Tu cliques <span class="ann-btn-wa">WhatsApp</span> ou <span class="ann-btn-sms">SMS</span> : ton appli s'ouvre avec le message deja ecrit.</li>
			</ol>
		</div>
		<div class="ann-legal">⚖️ <strong>A respecter :</strong> contacte uniquement des personnes qui ont publie une annonce. Pas d'envoi automatique en masse. Si quelqu'un dit non, mets-le sur <em>Ne plus contacter</em>.</div>

		<div class="ann-card">
			<h2 style="margin-top:0;">➕ Ajouter un prospect</h2>
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_add">
				<?php wp_nonce_field( 'ann_add' ); ?>
				<div class="ann-grid-2c">
					<div><label>Prenom</label><input type="text" name="prenom" id="ann_prenom" placeholder="Ex : Julie"></div>
					<div><label>Telephone *</label><input type="text" name="phone" required placeholder="06 12 34 56 78"></div>
					<div><label>Prestation</label><select name="prestation" id="ann_prestation">
						<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Source</label><select name="source">
						<?php foreach ( $sources as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div class="ann-full"><label>Lien de l'annonce</label><input type="text" name="link" placeholder="https://..."></div>
					<div><label>Ville</label><input type="text" name="ville" id="ann_ville" value="<?php echo esc_attr( $ville_def ); ?>"></div>
					<div><label>Note perso</label><input type="text" name="note" placeholder="Ex : mariage en juin"></div>
					<div class="ann-full"><label>Message a envoyer</label>
						<textarea name="message" id="ann_message" rows="4"></textarea>
						<button type="button" class="button" onclick="annRegen()" style="margin-top:6px;">🔄 Regenerer</button>
					</div>
				</div>
				<p style="margin-top:14px;"><button type="submit" class="button button-primary button-hero">Ajouter</button></p>
			</form>
		</div>

		<form method="get" class="ann-card" style="padding:12px 18px;">
			<input type="hidden" name="page" value="ann-prospects">
			<strong>Filtrer :</strong>
			<select name="f_status"><option value="">Tous statuts</option>
				<?php foreach ( $statuses as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_status, $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
			<select name="f_prestation"><option value="">Toutes prestations</option>
				<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_prestation, $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
			<select name="f_source"><option value="">Toutes sources</option>
				<?php foreach ( $sources as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_source, $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
			<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Rechercher">
			<button type="submit" class="button">Filtrer</button>
			<a href="<?php echo esc_url( $base ); ?>" class="button">Reset</a>
		</form>

		<table class="ann-table">
			<thead><tr><th>Prospect</th><th>Prestation</th><th>Statut</th><th>Contacter</th><th>Date</th><th></th></tr></thead>
			<tbody>
				<?php if ( empty( $filtered ) ) : ?>
					<tr><td colspan="6" style="text-align:center;color:#64748b;padding:24px;">Aucun prospect 👆 ajoute-en un.</td></tr>
				<?php endif; ?>
				<?php foreach ( $filtered as $p ) :
					$pid    = isset( $p['id'] ) ? $p['id'] : '';
					$status = isset( $p['status'] ) ? $p['status'] : 'nouveau';
					$wa     = ann_wa_link( $p );
					$sms    = ann_sms_link( $p );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( ! empty( $p['prenom'] ) ? $p['prenom'] : '(sans nom)' ); ?></strong><br>
							<?php echo esc_html( isset( $p['phone'] ) ? $p['phone'] : '' ); ?>
							<?php if ( ! empty( $p['link'] ) ) : ?><br><a href="<?php echo esc_url( $p['link'] ); ?>" target="_blank" rel="noopener">Voir l'annonce ↗</a><?php endif; ?>
							<?php if ( ! empty( $p['note'] ) ) : ?><br><em style="color:#64748b;"><?php echo esc_html( $p['note'] ); ?></em><?php endif; ?>
						</td>
						<td>
							<span class="ann-pill"><?php echo esc_html( isset( $prestations[ $p['prestation'] ] ) ? $prestations[ $p['prestation'] ] : '' ); ?></span><br>
							<small><?php echo esc_html( isset( $sources[ $p['source'] ] ) ? $sources[ $p['source'] ] : '' ); ?></small>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>">
								<input type="hidden" name="action" value="ann_update">
								<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
								<?php wp_nonce_field( 'ann_update' ); ?>
								<select name="status" onchange="this.form.submit()">
									<?php foreach ( $statuses as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $status, $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
								</select>
							</form>
						</td>
						<td>
							<?php if ( '' !== $wa ) : ?><a class="ann-btn-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a><br><?php endif; ?>
							<?php if ( '' !== $sms ) : ?><a class="ann-btn-sms" href="<?php echo esc_url( $sms ); ?>">SMS</a><?php endif; ?>
							<details style="margin-top:6px;">
								<summary style="cursor:pointer;font-size:12px;color:#64748b;">✏️ Modifier message</summary>
								<form method="post" action="<?php echo esc_url( $post ); ?>" style="margin-top:6px;">
									<input type="hidden" name="action" value="ann_update">
									<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
									<?php wp_nonce_field( 'ann_update' ); ?>
									<textarea name="message" rows="4" style="width:320px;"><?php echo esc_textarea( isset( $p['message'] ) ? $p['message'] : '' ); ?></textarea><br>
									<input type="text" name="note" value="<?php echo esc_attr( isset( $p['note'] ) ? $p['note'] : '' ); ?>" placeholder="Note" style="width:320px;margin-top:4px;"><br>
									<button type="submit" class="button button-small" style="margin-top:4px;">Enregistrer</button>
								</form>
							</details>
						</td>
						<td><small><?php echo esc_html( isset( $p['created'] ) ? $p['created'] : '' ); ?></small></td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" onsubmit="return confirm('Supprimer ?');">
								<input type="hidden" name="action" value="ann_delete">
								<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
								<?php wp_nonce_field( 'ann_delete' ); ?>
								<button type="submit" class="button button-small">🗑️</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<script>
	var ANN_TPL = <?php echo wp_json_encode( ann_tpl_data() ); ?>;
	var ANN_VAR = 0;
	function annFill(){
		var prest  = (document.getElementById('ann_prestation')||{}).value || 'autre';
		var prenom = ((document.getElementById('ann_prenom')||{}).value || '').trim();
		var ville  = ((document.getElementById('ann_ville')||{}).value || '').trim() || 'votre region';
		var set    = ANN_TPL[prest] || ANN_TPL['autre'] || [''];
		if (!Array.isArray(set)) { set = [String(set)]; }
		var msg = set[ANN_VAR % set.length] || '';
		msg = msg.split('{prenom}').join(prenom).split('{ville}').join(ville);
		msg = msg.replace(/\s{2,}/g,' ').trim();
		var t = document.getElementById('ann_message'); if (t) t.value = msg;
	}
	function annRegen(){ ANN_VAR++; annFill(); }
	document.addEventListener('DOMContentLoaded', function(){
		['ann_prestation','ann_prenom','ann_ville'].forEach(function(id){
			var el = document.getElementById(id);
			if (el){ el.addEventListener('change', function(){ ANN_VAR=0; annFill(); }); el.addEventListener('keyup', annFill); }
		});
		annFill();
	});
	</script>
	<?php
}

/* ===========================================================================
 * Page : Recherche entreprises
 * ========================================================================= */
function ann_render_search_page() {
	ann_check_admin();
	if ( ! ann_module_on( 'recherche' ) ) { echo '<div class="wrap"><p>Module desactive.</p></div>'; return; }

	$q   = sanitize_text_field( wp_unslash( isset( $_GET['q'] ) ? $_GET['q'] : '' ) );
	$cp  = sanitize_text_field( wp_unslash( isset( $_GET['cp'] ) ? $_GET['cp'] : '' ) );
	$naf = sanitize_text_field( wp_unslash( isset( $_GET['naf'] ) ? $_GET['naf'] : '' ) );
	$results = null;
	$total   = 0;
	$err     = '';
	if ( '' !== $q || '' !== $naf ) {
		$res = ann_api_search( array( 'q' => $q, 'cp' => $cp, 'naf' => $naf ) );
		if ( $res['ok'] ) {
			$results = $res['results'];
			$total   = $res['total'];
			ann_add_history( array( 'q' => $q, 'cp' => $cp, 'naf' => $naf, 'total' => $total, 'when' => current_time( 'Y-m-d H:i' ) ) );
		} else {
			$err = $res['error'];
		}
	}
	$post    = admin_url( 'admin-post.php' );
	$presets = ann_search_presets();
	$history = ann_get_history();
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">🔍 Recherche entreprises</h1>
		<?php ann_notice(); ?>

		<div class="ann-help">
			<strong>A quoi ca sert ?</strong> Trouver gratuitement des partenaires potentiels : wedding planners, salles, traiteurs, organisateurs d'evenement, autres photographes pour ton reseau.
			<br><span style="color:#64748b;font-size:13px;">Donnees officielles INSEE via <code>recherche-entreprises.api.gouv.fr</code> — gratuit, sans cle. ⚠️ Les telephones ne sont pas fournis par l'API (donnees protegees).</span>
		</div>

		<div class="ann-card">
			<strong>Presets photographe :</strong><br>
			<?php foreach ( $presets as $key => $p ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-search&q=' . rawurlencode( $p['q'] ) . '&naf=' . rawurlencode( $p['naf'] ) . '&cp=' . rawurlencode( $cp ) ) ); ?>" class="ann-preset"><?php echo esc_html( $p['emoji'] . ' ' . $p['label'] ); ?></a>
			<?php endforeach; ?>
		</div>

		<form method="get" class="ann-card">
			<input type="hidden" name="page" value="ann-search">
			<div class="ann-grid-2c">
				<div class="ann-full"><label>Mots-cles</label><input type="text" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Ex : wedding planner, salle reception..."></div>
				<div><label>Code postal (5 chiffres)</label><input type="text" name="cp" value="<?php echo esc_attr( $cp ); ?>" placeholder="Ex : 44000"></div>
				<div><label>Code NAF (optionnel)</label><input type="text" name="naf" value="<?php echo esc_attr( $naf ); ?>" placeholder="Ex : 7022Z"></div>
			</div>
			<p style="margin-top:14px;"><button class="button button-primary">Rechercher</button></p>
		</form>

		<?php if ( '' !== $err ) : ?>
			<div class="notice notice-error"><p>Erreur API : <?php echo esc_html( $err ); ?></p></div>
		<?php endif; ?>

		<?php if ( null !== $results ) : ?>
			<div class="ann-section-title">📊 Resultats (<?php echo (int) $total; ?> entreprise(s))</div>
			<table class="ann-table">
				<thead><tr><th>Nom</th><th>Adresse</th><th>Activite</th><th>Action</th></tr></thead>
				<tbody>
					<?php if ( empty( $results ) ) : ?>
						<tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px;">Aucun resultat — essaie d'autres mots-cles ou enleve le code postal.</td></tr>
					<?php endif; ?>
					<?php foreach ( $results as $row ) :
						$ex   = ann_api_extract( $row );
						$link = '' !== $ex['siret'] ? 'https://annuaire-entreprises.data.gouv.fr/entreprise/' . rawurlencode( $ex['siret'] ) : '';
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $ex['nom'] ); ?></strong>
								<?php if ( $link ) : ?><br><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" style="font-size:12px;">Annuaire officiel ↗</a><?php endif; ?>
							</td>
							<td style="font-size:12px;color:#475569;"><?php echo esc_html( $ex['adresse'] ); ?></td>
							<td style="font-size:12px;"><?php echo esc_html( $ex['activite'] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( $post ); ?>">
									<input type="hidden" name="action" value="ann_add_from_api">
									<input type="hidden" name="nom" value="<?php echo esc_attr( $ex['nom'] ); ?>">
									<input type="hidden" name="ville" value="<?php echo esc_attr( $ex['ville'] ); ?>">
									<input type="hidden" name="adresse" value="<?php echo esc_attr( $ex['adresse'] ); ?>">
									<input type="hidden" name="siret" value="<?php echo esc_attr( $ex['siret'] ); ?>">
									<input type="hidden" name="prestation" value="autre">
									<?php wp_nonce_field( 'ann_add_from_api' ); ?>
									<button class="ann-btn-add">+ Ajouter au CRM</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $history ) ) : ?>
			<div class="ann-section-title">🕓 Historique recent</div>
			<div class="ann-card">
				<?php foreach ( array_slice( $history, 0, 8 ) as $h ) : ?>
					<div style="padding:6px 0;border-bottom:1px dashed #e2e8f0;font-size:13px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-search&q=' . rawurlencode( $h['q'] ) . '&cp=' . rawurlencode( $h['cp'] ) . '&naf=' . rawurlencode( $h['naf'] ) ) ); ?>"><?php echo esc_html( ! empty( $h['q'] ) ? $h['q'] : $h['naf'] ); ?></a>
						<span style="color:#94a3b8;"> · <?php echo (int) $h['total']; ?> resultats · <?php echo esc_html( $h['when'] ); ?><?php if ( '' !== $h['cp'] ) : ?> · CP <?php echo esc_html( $h['cp'] ); ?><?php endif; ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/* ===========================================================================
 * Page : Agent automatique
 * ========================================================================= */
function ann_render_agent_page() {
	ann_check_admin();
	if ( ! ann_module_on( 'agent' ) ) { echo '<div class="wrap"><p>Module desactive.</p></div>'; return; }
	$searches = ann_get_searches();
	$logs     = ann_agent_log_get();
	$post     = admin_url( 'admin-post.php' );
	$tg_ok    = ann_tg_configured();
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">🤖 Agent automatique</h1>
		<?php ann_notice(); ?>
		<div class="ann-help">
			<strong>Comment ca marche ?</strong> Tu sauvegardes une recherche (ex : wedding planners a Nantes). L'agent l'execute toutes les X heures, garde la trace des entreprises deja vues, et t'alerte sur Telegram <em>seulement</em> quand il en trouve des nouvelles.
		</div>

		<div class="ann-card">
			<h2 style="margin-top:0;">➕ Nouvelle recherche auto</h2>
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_search_save">
				<?php wp_nonce_field( 'ann_search_save' ); ?>
				<div class="ann-grid-2c">
					<div><label>Libelle</label><input type="text" name="label" placeholder="Ex : Wedding planners Nantes"></div>
					<div><label>Mots-cles</label><input type="text" name="q" placeholder="wedding planner"></div>
					<div><label>Code postal</label><input type="text" name="cp" placeholder="44000"></div>
					<div><label>Code NAF (optionnel)</label><input type="text" name="naf" placeholder="7022Z"></div>
					<div><label>Frequence</label>
						<select name="freq">
							<option value="3600">Toutes les heures</option>
							<option value="21600">Toutes les 6h</option>
							<option value="86400" selected>Tous les jours</option>
							<option value="604800">Toutes les semaines</option>
						</select>
					</div>
					<div><label><input type="checkbox" name="notify_tg" value="1" <?php echo $tg_ok ? 'checked' : 'disabled'; ?>> Alerte Telegram <?php if ( ! $tg_ok ) : ?><small style="color:#dc2626;">(configure Telegram d'abord)</small><?php endif; ?></label></div>
				</div>
				<p style="margin-top:14px;"><button class="button button-primary">Sauvegarder</button></p>
			</form>
		</div>

		<div class="ann-section-title">🔄 Recherches actives</div>
		<table class="ann-table">
			<thead><tr><th>Libelle</th><th>Criteres</th><th>Frequence</th><th>Derniere execution</th><th>Etat</th><th></th></tr></thead>
			<tbody>
				<?php if ( empty( $searches ) ) : ?>
					<tr><td colspan="6" style="text-align:center;color:#64748b;padding:24px;">Aucune recherche sauvegardee. Ajoute-en une 👆</td></tr>
				<?php endif; ?>
				<?php
				$freq_lbl = array( 3600 => '/heure', 21600 => '/6h', 86400 => '/jour', 604800 => '/semaine' );
				foreach ( $searches as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( isset( $s['label'] ) ? $s['label'] : '' ); ?></strong></td>
						<td style="font-size:12px;">
							<?php if ( ! empty( $s['q'] ) ) : ?>🔎 <?php echo esc_html( $s['q'] ); ?><?php endif; ?>
							<?php if ( ! empty( $s['cp'] ) ) : ?><br>📍 CP <?php echo esc_html( $s['cp'] ); ?><?php endif; ?>
							<?php if ( ! empty( $s['naf'] ) ) : ?><br>NAF <?php echo esc_html( $s['naf'] ); ?><?php endif; ?>
							<?php if ( ! empty( $s['notify_tg'] ) ) : ?><br>🔔 Telegram<?php endif; ?>
						</td>
						<td><?php echo esc_html( isset( $freq_lbl[ (int) $s['freq'] ] ) ? $freq_lbl[ (int) $s['freq'] ] : '?' ); ?></td>
						<td style="font-size:12px;">
							<?php echo ! empty( $s['last_run'] ) ? esc_html( wp_date( 'd/m H:i', (int) $s['last_run'] ) ) : '—'; ?>
							<?php if ( ! empty( $s['last_new'] ) ) : ?><br><span class="ann-ok">+<?php echo (int) $s['last_new']; ?> nouveau(x)</span><?php endif; ?>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="ann_search_toggle">
								<input type="hidden" name="id" value="<?php echo esc_attr( $s['id'] ); ?>">
								<?php wp_nonce_field( 'ann_search_toggle' ); ?>
								<button class="button button-small"><?php echo empty( $s['active'] ) ? 'OFF' : 'ON'; ?></button>
							</form>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" onsubmit="return confirm('Supprimer ?');" style="display:inline;">
								<input type="hidden" name="action" value="ann_search_delete">
								<input type="hidden" name="id" value="<?php echo esc_attr( $s['id'] ); ?>">
								<?php wp_nonce_field( 'ann_search_delete' ); ?>
								<button class="button button-small">🗑️</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $logs ) ) : ?>
			<div class="ann-section-title">📜 Journal</div>
			<div class="ann-card" style="font-family:monospace;font-size:12px;max-height:300px;overflow:auto;background:#0f172a;color:#cbd5e1;">
				<?php foreach ( $logs as $line ) : ?>
					<div><?php echo esc_html( $line ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/* ===========================================================================
 * Page : Ambassadeurs
 * ========================================================================= */
function ann_render_ambass_page() {
	ann_check_admin();
	if ( ! ann_module_on( 'ambassadeurs' ) ) { echo '<div class="wrap"><p>Module desactive.</p></div>'; return; }
	$list = ann_get_ambassadeurs();
	$post = admin_url( 'admin-post.php' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">🤝 Ambassadeurs</h1>
		<?php ann_notice(); ?>
		<div class="ann-help">
			<strong>Programme parrainage clients :</strong> tes anciens clients qui te recommandent. Note ici qui t'a envoye qui — pour pouvoir les remercier (remise, tirage offert, seance bonus).
		</div>

		<div class="ann-card">
			<h2 style="margin-top:0;">➕ Ajouter un ambassadeur</h2>
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_amb_add">
				<?php wp_nonce_field( 'ann_amb_add' ); ?>
				<div class="ann-grid-2c">
					<div><label>Nom *</label><input type="text" name="nom" required></div>
					<div><label>Telephone</label><input type="text" name="phone"></div>
					<div><label>Email</label><input type="email" name="email"></div>
					<div><label>Note</label><input type="text" name="note" placeholder="Ex : mariage juin 2024"></div>
				</div>
				<p style="margin-top:14px;"><button class="button button-primary">Ajouter</button></p>
			</form>
		</div>

		<table class="ann-table">
			<thead><tr><th>Nom</th><th>Contact</th><th>Filleuls</th><th>Depuis</th><th></th></tr></thead>
			<tbody>
				<?php if ( empty( $list ) ) : ?>
					<tr><td colspan="5" style="text-align:center;color:#64748b;padding:24px;">Aucun ambassadeur pour le moment.</td></tr>
				<?php endif; ?>
				<?php foreach ( $list as $a ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $a['nom'] ); ?></strong><?php if ( ! empty( $a['note'] ) ) : ?><br><small style="color:#64748b;"><?php echo esc_html( $a['note'] ); ?></small><?php endif; ?></td>
						<td style="font-size:12px;"><?php echo esc_html( isset( $a['phone'] ) ? $a['phone'] : '' ); ?><?php if ( ! empty( $a['email'] ) ) : ?><br><?php echo esc_html( $a['email'] ); ?><?php endif; ?></td>
						<td><span class="ann-pill"><?php echo (int) ( isset( $a['filleuls'] ) ? $a['filleuls'] : 0 ); ?></span></td>
						<td><small><?php echo esc_html( isset( $a['created'] ) ? $a['created'] : '' ); ?></small></td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" onsubmit="return confirm('Supprimer ?');">
								<input type="hidden" name="action" value="ann_amb_delete">
								<input type="hidden" name="id" value="<?php echo esc_attr( $a['id'] ); ?>">
								<?php wp_nonce_field( 'ann_amb_delete' ); ?>
								<button class="button button-small">🗑️</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/* ===========================================================================
 * Page : Reglages
 * ========================================================================= */
function ann_render_settings_page() {
	ann_check_admin();
	$s    = ann_get_settings();
	$mods = ann_get_modules();
	$post = admin_url( 'admin-post.php' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">⚙️ Reglages</h1>
		<?php ann_notice(); ?>

		<form method="post" action="<?php echo esc_url( $post ); ?>" class="ann-card">
			<input type="hidden" name="action" value="ann_settings">
			<?php wp_nonce_field( 'ann_settings' ); ?>

			<h2 style="margin-top:0;">📍 General</h2>
			<p><label><strong>Ta ville / region</strong> (apparait dans les messages)</label><br>
				<input type="text" name="ville" value="<?php echo esc_attr( isset( $s['ville'] ) ? $s['ville'] : '' ); ?>" placeholder="Ex : Nantes" style="width:320px;"></p>

			<hr>
			<h2>📱 Telegram (notifications)</h2>
			<p class="description">1) Ecris a <code>@BotFather</code> sur Telegram pour creer un bot → Token. 2) Demarre une conversation avec ton bot et recupere ton Chat ID via <code>@userinfobot</code>.</p>
			<p><label>Token du bot</label><br><input type="text" name="tg_token" value="<?php echo esc_attr( isset( $s['tg_token'] ) ? $s['tg_token'] : '' ); ?>" style="width:420px;" placeholder="123456:ABC-..."></p>
			<p><label>Chat ID</label><br><input type="text" name="tg_chat" value="<?php echo esc_attr( isset( $s['tg_chat'] ) ? $s['tg_chat'] : '' ); ?>" style="width:240px;" placeholder="123456789"></p>
			<p><label><input type="checkbox" name="cron_morning" value="1" <?php checked( 1, (int) ( isset( $s['cron_morning'] ) ? $s['cron_morning'] : 0 ) ); ?>> Envoyer la feuille de route automatiquement chaque matin (vers 8h)</label></p>

			<hr>
			<h2>🧩 Modules</h2>
			<p class="description">Active uniquement ce dont tu as besoin.</p>
			<p>
				<label><input type="checkbox" name="mod_recherche" value="1" <?php checked( 1, (int) $mods['recherche'] ); ?>> 🔍 Recherche entreprises (API gouv.fr)</label><br>
				<label><input type="checkbox" name="mod_agent" value="1" <?php checked( 1, (int) $mods['agent'] ); ?>> 🤖 Agent automatique</label><br>
				<label><input type="checkbox" name="mod_broadcast" value="1" <?php checked( 1, (int) $mods['broadcast'] ); ?>> 📣 Diffusion Telegram depuis le hub</label><br>
				<label><input type="checkbox" name="mod_ambassadeurs" value="1" <?php checked( 1, (int) $mods['ambassadeurs'] ); ?>> 🤝 Programme ambassadeurs (parrainage)</label><br>
				<label><input type="checkbox" name="mod_demandes" value="1" <?php checked( 1, (int) $mods['demandes'] ); ?>> 📨 Suivi demandes recues (placeholder)</label>
			</p>

			<p><button class="button button-primary button-hero">Enregistrer</button></p>
		</form>

		<form method="post" action="<?php echo esc_url( $post ); ?>" style="max-width:980px;">
			<input type="hidden" name="action" value="ann_test_tg">
			<?php wp_nonce_field( 'ann_test_tg' ); ?>
			<button type="submit" class="button">📤 Envoyer un message de test Telegram</button>
		</form>
	</div>
	<?php
}

/* ===========================================================================
 * Dashboard widget WordPress
 * ========================================================================= */
add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	wp_add_dashboard_widget( 'ann_widget', 'Anna Photo — Prospection', 'ann_dashboard_widget' );
} );
function ann_dashboard_widget() {
	$c = ann_counters();
	?>
	<style>
	.ann-w-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:0 0 12px;}
	.ann-w-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;text-decoration:none;color:inherit;display:block;}
	.ann-w-card:hover{background:#eff6ff;border-color:#bfdbfe;}
	.ann-w-card .n{font-size:22px;font-weight:700;}
	.ann-w-card .l{color:#64748b;font-size:11px;text-transform:uppercase;}
	.ann-w-card.urg .n{color:#dc2626;}
	</style>
	<div class="ann-w-grid">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=nouveau' ) ); ?>" class="ann-w-card <?php echo $c['nouveau'] > 0 ? 'urg' : ''; ?>">
			<div class="n"><?php echo (int) $c['nouveau']; ?></div><div class="l">📋 A contacter</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=relance' ) ); ?>" class="ann-w-card">
			<div class="n"><?php echo (int) $c['relance']; ?></div><div class="l">🔁 A relancer</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=interesse' ) ); ?>" class="ann-w-card">
			<div class="n"><?php echo (int) $c['interesse']; ?></div><div class="l">✨ Interesses</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=client' ) ); ?>" class="ann-w-card">
			<div class="n"><?php echo (int) $c['client']; ?></div><div class="l">💖 Clients</div>
		</a>
	</div>
	<p style="margin:0;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects' ) ); ?>" class="button button-primary">Ouvrir la prospection</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-hub' ) ); ?>" class="button">📸 Centre de controle</a>
	</p>
	<?php
}
