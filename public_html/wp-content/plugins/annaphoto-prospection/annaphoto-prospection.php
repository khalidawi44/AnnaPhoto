<?php
/**
 * Plugin Name: Anna Photo — Prospection
 * Description: Centre de controle Anna Photo : suivi prospects, hub de recherche d'annonces, bookmarklet "capturer une annonce" en 1 clic, import auto via alertes mail IMAP (Leboncoin, Mariages.net), messages WhatsApp/SMS personnalises selon la note, rappels Telegram programmes, modules optionnels.
 * Version: 2.3.0
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
define( 'ANN_REMINDER_OPT',  'annaphoto_reminders' );        // rappels Telegram programmes
define( 'ANN_AGENT_LOG_OPT', 'annaphoto_agent_log' );
define( 'ANN_AMBASS_OPT',    'annaphoto_ambassadeurs' );
define( 'ANN_DAILY_LAST',    'annaphoto_daily_last_push' );

define( 'ANN_CRON_DAILY', 'annaphoto_cron_daily' );
define( 'ANN_CRON_AGENT', 'annaphoto_cron_agent' );
define( 'ANN_CRON_IMAP',  'annaphoto_cron_imap' );

/* ===========================================================================
 * Accesseurs donnees
 * ========================================================================= */
function ann_get_prospects() { $d = get_option( ANN_PROSP_OPT, array() ); return is_array( $d ) ? $d : array(); }
function ann_save_prospects( $list ) { update_option( ANN_PROSP_OPT, array_values( $list ) ); }
function ann_get_settings() { $d = get_option( ANN_SET_OPT, array() ); return is_array( $d ) ? $d : array(); }
function ann_setting( $key, $default = '' ) {
	$s = ann_get_settings();
	return isset( $s[ $key ] ) && '' !== $s[ $key ] ? $s[ $key ] : $default;
}
function ann_get_modules() {
	$d = get_option( ANN_MOD_OPT, null );
	if ( ! is_array( $d ) ) {
		$d = array(
			'annonces'     => 1,
			'agent'        => 1,
			'broadcast'    => 1,
			'ambassadeurs' => 0,
		);
		update_option( ANN_MOD_OPT, $d );
	}
	// Backward-compat
	if ( ! isset( $d['annonces'] ) && isset( $d['recherche'] ) ) { $d['annonces'] = $d['recherche']; }
	return $d;
}
function ann_module_on( $key ) { $m = ann_get_modules(); return ! empty( $m[ $key ] ); }
function ann_get_reminders() { $d = get_option( ANN_REMINDER_OPT, array() ); return is_array( $d ) ? $d : array(); }
function ann_save_reminders( $list ) { update_option( ANN_REMINDER_OPT, array_values( $list ) ); }
function ann_agent_log_add( $line ) {
	$l = get_option( ANN_AGENT_LOG_OPT, array() );
	if ( ! is_array( $l ) ) { $l = array(); }
	array_unshift( $l, '[' . wp_date( 'Y-m-d H:i' ) . '] ' . $line );
	if ( count( $l ) > 80 ) { $l = array_slice( $l, 0, 80 ); }
	update_option( ANN_AGENT_LOG_OPT, $l );
}
function ann_agent_log_get() { $l = get_option( ANN_AGENT_LOG_OPT, array() ); return is_array( $l ) ? $l : array(); }
function ann_get_ambassadeurs() { $d = get_option( ANN_AMBASS_OPT, array() ); return is_array( $d ) ? $d : array(); }
function ann_save_ambassadeurs( $list ) { update_option( ANN_AMBASS_OPT, array_values( $list ) ); }

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
function ann_status_color( $key ) { $s = ann_statuses(); return isset( $s[ $key ] ) ? $s[ $key ][1] : '#64748b'; }
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
		'autre'     => 'Autre',
	);
}

/* ===========================================================================
 * Mots-cles par prestation — orientes "DEMANDE" (pas offre)
 * ========================================================================= */
function ann_keywords( $prestation ) {
	$map = array(
		'mariage'   => array( 'cherche photographe mariage',   'recherche photographe mariage',   'besoin photographe mariage' ),
		'couple'    => array( 'cherche photographe couple',    'cherche photographe EVJF',        'recherche photographe couple' ),
		'famille'   => array( 'cherche photographe famille',   'cherche photographe enfants',     'besoin photographe famille' ),
		'grossesse' => array( 'cherche photographe grossesse', 'cherche photographe maternite',   'recherche shooting grossesse' ),
		'portrait'  => array( 'cherche photographe portrait',  'besoin book photo',               'recherche photographe portrait' ),
		'evenement' => array( 'cherche photographe evenement', 'cherche photographe anniversaire','besoin photographe soiree' ),
		'autre'     => array( 'cherche photographe',           'recherche photographe',           'besoin photographe' ),
	);
	return isset( $map[ $prestation ] ) ? $map[ $prestation ] : $map['autre'];
}

/**
 * Detection automatique de la prestation a partir d'une note.
 */
function ann_detect_prestation( $note ) {
	$n = strtolower( $note );
	$tests = array(
		'grossesse' => array( 'grossesse', 'enceinte', 'maternit', 'baby bump', 'attend un', 'attend une', 'futur maman' ),
		'mariage'   => array( 'mariage', 'mariee', 'marie', 'wedding', 'noce' ),
		'couple'    => array( 'evjf', 'evjg', 'enterrement vie', 'couple ' ),
		'famille'   => array( 'famille', 'enfant', 'bebe', 'naissance', 'bapteme' ),
		'portrait'  => array( 'portrait', 'book ', 'corporate', 'profil pro', 'cv ' ),
		'evenement' => array( 'anniversaire', 'evenement', 'soiree', 'gala', 'spectacle', 'concert' ),
	);
	foreach ( $tests as $key => $words ) {
		foreach ( $words as $w ) {
			if ( false !== strpos( $n, $w ) ) { return $key; }
		}
	}
	return 'autre';
}

/* ===========================================================================
 * Plateformes : URL de recherche
 * Note : ces liens ouvrent simplement la page de recherche du site.
 * Aucun scraping (interdit par les CGU). Anna voit les vraies annonces
 * dans son navigateur, identifiee a son compte si necessaire.
 * ========================================================================= */
function ann_platforms() {
	return array(
		'leboncoin' => array(
			'label' => 'Leboncoin',
			'emoji' => '🟧',
			'tpl'   => 'https://www.leboncoin.fr/recherche?text={q}{loc}&sort=time',
			'loc'   => '&locations={cp}',
		),
		'fb_marketplace' => array(
			'label' => 'Facebook Marketplace',
			'emoji' => '🛒',
			'tpl'   => 'https://www.facebook.com/marketplace/search?query={q}',
			'loc'   => '',
		),
		'fb_search' => array(
			'label' => 'Facebook (recherche)',
			'emoji' => '📘',
			'tpl'   => 'https://www.facebook.com/search/top?q={q}',
			'loc'   => '',
		),
		'insta_search' => array(
			'label' => 'Instagram',
			'emoji' => '📸',
			'tpl'   => 'https://www.instagram.com/explore/search/keyword/?q={q}',
			'loc'   => '',
		),
		'google' => array(
			'label' => 'Google (phrase exacte)',
			'emoji' => '🔎',
			'tpl'   => 'https://www.google.com/search?q=%22{q}%22{loc}',
			'loc'   => '+{cp}',
		),
		'twitter' => array(
			'label' => 'X / Twitter (live)',
			'emoji' => '🐦',
			'tpl'   => 'https://twitter.com/search?q=%22{q}%22&f=live',
			'loc'   => '',
		),
		'mariagesnet' => array(
			'label' => 'Mariages.net (via Google)',
			'emoji' => '💍',
			'tpl'   => 'https://www.google.com/search?q=site%3Amariages.net+%22{q}%22',
			'loc'   => '',
		),
		'forums_fr' => array(
			'label' => 'Forums FR (aufeminin, doctissimo)',
			'emoji' => '💬',
			'tpl'   => 'https://www.google.com/search?q=%28site%3Aaufeminin.com+OR+site%3Adoctissimo.fr%29+%22{q}%22',
			'loc'   => '',
		),
	);
}
function ann_platform_url( $platform_key, $q, $cp = '' ) {
	$p = ann_platforms();
	if ( ! isset( $p[ $platform_key ] ) ) { return ''; }
	$pl  = $p[ $platform_key ];
	$url = $pl['tpl'];
	$loc = ( '' !== $cp && '' !== $pl['loc'] ) ? str_replace( '{cp}', rawurlencode( $cp ), $pl['loc'] ) : '';
	$url = str_replace( '{q}',   rawurlencode( $q ), $url );
	$url = str_replace( '{loc}', $loc, $url );
	return $url;
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
function ann_phone_local( $phone ) { return preg_replace( '/[^0-9+]/', '', (string) $phone ); }

/* ===========================================================================
 * Templates messages — supportent {prenom} {ville} {note}
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
	if ( is_array( $custom ) && ! empty( $custom ) ) { return wp_parse_args( $custom, ann_tpl_default() ); }
	return ann_tpl_default();
}
function ann_build_message( $prestation, $prenom, $ville, $note = '', $variation = 0 ) {
	$tpl = ann_tpl_data();
	$key = isset( $tpl[ $prestation ] ) ? $prestation : 'autre';
	$set = is_array( $tpl[ $key ] ) ? $tpl[ $key ] : array( (string) $tpl[ $key ] );
	$idx = ( (int) $variation ) % max( 1, count( $set ) );
	$msg = $set[ $idx ];
	$prenom = trim( (string) $prenom );
	$ville  = trim( (string) $ville );
	$note   = trim( (string) $note );
	$msg = str_replace( '{prenom}', $prenom, $msg );
	$msg = str_replace( '{ville}', '' !== $ville ? $ville : 'votre region', $msg );
	$msg = str_replace( '{note}',  $note, $msg );
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
function ann_tg_configured() { return '' !== ann_setting( 'tg_token' ) && '' !== ann_setting( 'tg_chat' ); }

/* ===========================================================================
 * Activation / desactivation cron
 * ========================================================================= */
register_activation_hook( __FILE__, 'ann_on_activate' );
register_deactivation_hook( __FILE__, 'ann_on_deactivate' );
function ann_on_activate() {
	if ( ! wp_next_scheduled( ANN_CRON_DAILY ) ) { wp_schedule_event( time() + 300, 'hourly', ANN_CRON_DAILY ); }
	if ( ! wp_next_scheduled( ANN_CRON_AGENT ) ) { wp_schedule_event( time() + 600, 'hourly', ANN_CRON_AGENT ); }
	if ( ! wp_next_scheduled( ANN_CRON_IMAP ) )  { wp_schedule_event( time() + 900, 'hourly', ANN_CRON_IMAP ); }
}
function ann_on_deactivate() {
	foreach ( array( ANN_CRON_DAILY, ANN_CRON_AGENT, ANN_CRON_IMAP ) as $hook ) {
		$ts = wp_next_scheduled( $hook );
		if ( $ts ) { wp_unschedule_event( $ts, $hook ); }
	}
}

/* ===========================================================================
 * Cron : feuille de route matin
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
	if ( 0 === $counts['nouveau'] + $counts['relance'] ) {
		$lines[] = '⚠️ Aucune vente en attente — pense a chercher de nouvelles annonces !';
	} else {
		$lines[] = 'Bonne journee ! 📸';
	}
	ann_tg_push( implode( "\n", $lines ) );
	update_option( ANN_DAILY_LAST, $today );
}

/* ===========================================================================
 * Cron : agent — envoie rappels Telegram avec liens de recherche
 * ========================================================================= */
add_action( ANN_CRON_AGENT, 'ann_cron_agent_run' );
function ann_cron_agent_run() {
	if ( ! ann_module_on( 'agent' ) ) { return; }
	if ( ! ann_tg_configured() ) { return; }
	$reminders = ann_get_reminders();
	if ( empty( $reminders ) ) { return; }
	$now = time();
	$changed = false;
	$platforms = ann_platforms();

	foreach ( $reminders as $idx => $r ) {
		if ( empty( $r['active'] ) ) { continue; }
		$freq = max( 3600, (int) ( isset( $r['freq'] ) ? $r['freq'] : 86400 ) );
		$last = (int) ( isset( $r['last_run'] ) ? $r['last_run'] : 0 );
		if ( $now - $last < $freq ) { continue; }

		$prest = isset( $r['prestation'] ) ? $r['prestation'] : 'autre';
		$cp    = isset( $r['cp'] ) ? $r['cp'] : '';
		$kw    = ann_keywords( $prest );
		$q     = $kw[0];
		$plist = isset( $r['platforms'] ) && is_array( $r['platforms'] ) ? $r['platforms'] : array( 'leboncoin', 'fb_marketplace', 'google' );
		$labels = ann_prestations();

		$msg  = '🔍 <b>Rappel prospection : ' . esc_html( isset( $labels[ $prest ] ) ? $labels[ $prest ] : '' ) . '</b>';
		if ( '' !== $cp ) { $msg .= ' (CP ' . esc_html( $cp ) . ')'; }
		$msg .= "\n\nVa chercher de nouvelles annonces :\n";
		foreach ( $plist as $pkey ) {
			if ( ! isset( $platforms[ $pkey ] ) ) { continue; }
			$url = ann_platform_url( $pkey, $q, $cp );
			if ( '' === $url ) { continue; }
			$msg .= $platforms[ $pkey ]['emoji'] . ' <a href="' . esc_url( $url ) . '">' . esc_html( $platforms[ $pkey ]['label'] ) . '</a>' . "\n";
		}
		$msg .= "\nQuand tu trouves : ajoute le prospect dans le CRM 📋";
		ann_tg_push( $msg );

		$reminders[ $idx ]['last_run'] = $now;
		$changed = true;
		ann_agent_log_add( 'Rappel envoye : ' . ( isset( $labels[ $prest ] ) ? $labels[ $prest ] : $prest ) . ( '' !== $cp ? ' CP ' . $cp : '' ) );
	}
	if ( $changed ) { ann_save_reminders( $reminders ); }
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
 * IMAP : import auto des alertes mail (Leboncoin, Mariages.net, etc.)
 * ========================================================================= */
function ann_imap_available() { return function_exists( 'imap_open' ); }
function ann_imap_configured() {
	return '' !== ann_setting( 'imap_host' ) && '' !== ann_setting( 'imap_user' ) && '' !== ann_setting( 'imap_pass' );
}
function ann_imap_connect() {
	if ( ! ann_imap_available() )  { return new WP_Error( 'ext', 'Extension PHP imap manquante sur ce serveur' ); }
	if ( ! ann_imap_configured() ) { return new WP_Error( 'cfg', 'IMAP non configure' ); }
	$host   = ann_setting( 'imap_host' );
	$port   = (int) ann_setting( 'imap_port', '993' );
	$user   = ann_setting( 'imap_user' );
	$pass   = ann_setting( 'imap_pass' );
	$folder = ann_setting( 'imap_folder', 'INBOX' );
	$mb     = '{' . $host . ':' . $port . '/imap/ssl}' . $folder;
	$s      = @imap_open( $mb, $user, $pass, 0, 1 );
	if ( ! $s ) {
		$err = imap_last_error();
		return new WP_Error( 'conn', $err ? $err : 'Connexion impossible' );
	}
	return $s;
}
/**
 * Extrait les annonces (URLs + titre) du corps d'un mail d'alerte.
 */
function ann_imap_parse_listings( $body, $subject = '' ) {
	$listings = array();
	if ( '' === trim( $body ) ) { return $listings; }
	if ( false === stripos( $body, 'http' ) ) {
		$decoded = quoted_printable_decode( $body );
		if ( false !== stripos( $decoded, 'http' ) ) { $body = $decoded; }
		else { $b64 = base64_decode( $body, true ); if ( false !== $b64 && false !== stripos( $b64, 'http' ) ) { $body = $b64; } }
	}
	// Leboncoin
	if ( preg_match_all( '#https?://(?:www\.)?leboncoin\.fr/(?:ad|vi)/[^\s"\'<>\)\]]+#i', $body, $m ) ) {
		foreach ( array_unique( $m[0] ) as $url ) {
			$listings[] = array(
				'url'    => strtok( $url, '?#' ),
				'source' => 'leboncoin',
				'title'  => $subject,
			);
		}
	}
	// Mariages.net
	if ( preg_match_all( '#https?://(?:www\.)?mariages\.net/[a-z0-9_/\-]+#i', $body, $m ) ) {
		foreach ( array_unique( $m[0] ) as $url ) {
			$listings[] = array(
				'url'    => strtok( $url, '?#' ),
				'source' => 'autre',
				'title'  => $subject,
			);
		}
	}
	// Vivastreet
	if ( preg_match_all( '#https?://(?:www\.)?vivastreet\.com/[^\s"\'<>\)\]]+#i', $body, $m ) ) {
		foreach ( array_unique( $m[0] ) as $url ) {
			$listings[] = array(
				'url'    => strtok( $url, '?#' ),
				'source' => 'autre',
				'title'  => $subject,
			);
		}
	}
	return $listings;
}
function ann_imap_run() {
	if ( empty( ann_setting( 'imap_on' ) ) ) { return; }
	$stream = ann_imap_connect();
	if ( is_wp_error( $stream ) ) {
		ann_agent_log_add( 'IMAP erreur : ' . $stream->get_error_message() );
		return;
	}
	$emails = imap_search( $stream, 'UNSEEN' );
	$found  = 0;
	if ( $emails ) {
		$list_prospects = ann_get_prospects();
		$existing_urls  = array();
		foreach ( $list_prospects as $p ) { if ( ! empty( $p['link'] ) ) { $existing_urls[] = $p['link']; } }

		foreach ( $emails as $num ) {
			$header  = imap_headerinfo( $stream, $num );
			$subject = isset( $header->subject ) ? imap_utf8( $header->subject ) : '';
			$body    = '';
			$struct  = @imap_fetchstructure( $stream, $num );
			if ( $struct && isset( $struct->parts ) && is_array( $struct->parts ) ) {
				foreach ( $struct->parts as $idx => $part ) {
					if ( strtoupper( $part->subtype ?? '' ) === 'HTML' ) {
						$body .= imap_fetchbody( $stream, $num, ( $idx + 1 ) );
					}
				}
				if ( '' === $body ) { $body = imap_fetchbody( $stream, $num, 1 ); }
			} else {
				$body = imap_body( $stream, $num );
			}

			$listings = ann_imap_parse_listings( $body, $subject );
			foreach ( $listings as $l ) {
				if ( in_array( $l['url'], $existing_urls, true ) ) { continue; }
				$prest = ann_detect_prestation( $l['title'] . ' ' . $l['url'] );
				$list_prospects[] = array(
					'id'         => uniqid( 'p_' ),
					'prenom'     => '',
					'phone'      => '',
					'link'       => $l['url'],
					'source'     => $l['source'],
					'prestation' => $prest,
					'ville'      => ann_setting( 'ville', '' ),
					'note'       => trim( '[Auto-import] ' . $l['title'] ),
					'message'    => '',
					'status'     => 'nouveau',
					'created'    => current_time( 'Y-m-d H:i' ),
				);
				$existing_urls[] = $l['url'];
				$found++;
			}
			@imap_setflag_full( $stream, $num, '\\Seen' );
		}
		ann_save_prospects( array_values( $list_prospects ) );
	}
	imap_close( $stream );
	if ( $found > 0 ) {
		ann_agent_log_add( 'IMAP : ' . $found . ' annonce(s) importee(s)' );
		if ( ann_tg_configured() ) {
			ann_tg_push( '📥 <b>' . $found . ' nouvelle(s) annonce(s)</b> importee(s) depuis tes alertes mail. Va sur le CRM pour les completer (telephone manquant).' );
		}
	} else {
		ann_agent_log_add( 'IMAP : aucune nouvelle annonce' );
	}
}
add_action( ANN_CRON_IMAP, 'ann_imap_run' );

/* ===========================================================================
 * Bookmarklet : capture d'annonce depuis n'importe quel site
 * ========================================================================= */
function ann_bookmarklet_js() {
	$target = admin_url( 'admin.php?page=ann-capture' );
	return 'javascript:(function(){'
		. 'var u=location.href,t=document.title,s=window.getSelection().toString(),'
		. 'r=/(?:(?:\\+|00)33[\\s.-]?|0)[1-9](?:[\\s.-]?\\d{2}){4}/,'
		. 'pt=document.body.innerText||"",'
		. 'm=(s||pt).match(r),p=m?m[0]:"",'
		. 'n=s||(pt.length>300?pt.slice(0,300)+"...":pt);'
		. 'window.open("' . esc_js( $target ) . '&u="+encodeURIComponent(u)+"&t="+encodeURIComponent(t)+"&p="+encodeURIComponent(p)+"&n="+encodeURIComponent(n.slice(0,500)),"_blank");'
		. '})();';
}

/* ===========================================================================
 * Menu admin
 * ========================================================================= */
add_action( 'admin_menu', 'ann_admin_menu' );
function ann_admin_menu() {
	add_menu_page( 'Anna Photo', 'Anna Photo', 'manage_options', 'ann-hub', 'ann_render_hub', 'dashicons-camera-alt', 26 );
	add_submenu_page( 'ann-hub', 'Centre de controle', 'Centre de controle', 'manage_options', 'ann-hub', 'ann_render_hub' );
	add_submenu_page( 'ann-hub', 'Prospects', 'Prospects', 'manage_options', 'ann-prospects', 'ann_render_prospects_page' );
	if ( ann_module_on( 'annonces' ) ) {
		add_submenu_page( 'ann-hub', 'Trouver des annonces', 'Trouver des annonces', 'manage_options', 'ann-annonces', 'ann_render_annonces_page' );
	}
	if ( ann_module_on( 'agent' ) ) {
		add_submenu_page( 'ann-hub', 'Rappels automatiques', 'Rappels automatiques', 'manage_options', 'ann-agent', 'ann_render_agent_page' );
	}
	if ( ann_module_on( 'ambassadeurs' ) ) {
		add_submenu_page( 'ann-hub', 'Ambassadeurs', 'Ambassadeurs', 'manage_options', 'ann-ambass', 'ann_render_ambass_page' );
	}
	add_submenu_page( 'ann-hub', 'Reglages', 'Reglages', 'manage_options', 'ann-settings', 'ann_render_settings_page' );
	// Page de capture (cachee du menu, accessible via bookmarklet)
	add_submenu_page( null, 'Capturer une annonce', 'Capturer', 'manage_options', 'ann-capture', 'ann_render_capture_page' );
}

function ann_redirect( $page, $args = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) ) );
	exit;
}
function ann_check_admin() { if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); } }

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
	$note       = sanitize_textarea_field( wp_unslash( isset( $_POST['note'] ) ? $_POST['note'] : '' ) );
	$prestation = sanitize_text_field( wp_unslash( isset( $_POST['prestation'] ) ? $_POST['prestation'] : '' ) );
	if ( '' === $prestation || 'auto' === $prestation ) { $prestation = ann_detect_prestation( $note ); }
	$prenom = sanitize_text_field( wp_unslash( isset( $_POST['prenom'] ) ? $_POST['prenom'] : '' ) );
	$ville  = sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) );
	$message = sanitize_textarea_field( wp_unslash( isset( $_POST['message'] ) ? $_POST['message'] : '' ) );
	if ( '' === $message ) { $message = ann_build_message( $prestation, $prenom, $ville, $note ); }

	$entry = array(
		'id'         => uniqid( 'p_' ),
		'prenom'     => $prenom,
		'phone'      => $phone,
		'link'       => esc_url_raw( wp_unslash( isset( $_POST['link'] ) ? $_POST['link'] : '' ) ),
		'source'     => sanitize_text_field( wp_unslash( isset( $_POST['source'] ) ? $_POST['source'] : 'autre' ) ),
		'prestation' => $prestation,
		'ville'      => $ville,
		'note'       => $note,
		'message'    => $message,
		'status'     => 'nouveau',
		'created'    => current_time( 'Y-m-d H:i' ),
	);
	array_unshift( $list, $entry );
	ann_save_prospects( $list );

	$labels = ann_prestations();
	ann_tg_push( '📋 Nouveau prospect : ' . ( '' !== $prenom ? $prenom : $phone ) . ' — ' . ( isset( $labels[ $prestation ] ) ? $labels[ $prestation ] : '' ) );
	$redir = sanitize_text_field( wp_unslash( isset( $_POST['_redir'] ) ? $_POST['_redir'] : 'ann-prospects' ) );
	ann_redirect( $redir, array( 'msg' => 'added' ) );
} );

add_action( 'admin_post_ann_update', function () {
	ann_check_admin();
	check_admin_referer( 'ann_update' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_prospects();
	foreach ( $list as &$p ) {
		if ( isset( $p['id'] ) && $p['id'] === $id ) {
			if ( isset( $_POST['status'] ) )  { $p['status']  = sanitize_text_field( wp_unslash( $_POST['status'] ) ); }
			if ( isset( $_POST['note'] ) )    {
				$p['note'] = sanitize_textarea_field( wp_unslash( $_POST['note'] ) );
				// Si demande, regenerer le message a partir de la note
				if ( ! empty( $_POST['regenerate'] ) ) {
					$prest = ann_detect_prestation( $p['note'] );
					if ( 'autre' !== $prest ) { $p['prestation'] = $prest; }
					$p['message'] = ann_build_message( $p['prestation'], $p['prenom'] ?? '', $p['ville'] ?? '', $p['note'] );
				}
			}
			if ( isset( $_POST['message'] ) && empty( $_POST['regenerate'] ) ) {
				$p['message'] = sanitize_textarea_field( wp_unslash( $_POST['message'] ) );
			}
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
	$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_prospects(), function ( $p ) use ( $id ) {
		return ! ( isset( $p['id'] ) && $p['id'] === $id );
	} );
	ann_save_prospects( $list );
	ann_redirect( 'ann-prospects', array( 'msg' => 'deleted' ) );
} );

/* ===========================================================================
 * Handlers rappels (anciennement "recherches auto")
 * ========================================================================= */
add_action( 'admin_post_ann_reminder_save', function () {
	ann_check_admin();
	check_admin_referer( 'ann_reminder_save' );
	$list = ann_get_reminders();
	$plats = isset( $_POST['platforms'] ) && is_array( $_POST['platforms'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['platforms'] ) ) : array();
	if ( empty( $plats ) ) { $plats = array( 'leboncoin', 'fb_marketplace', 'google' ); }
	$list[] = array(
		'id'         => uniqid( 'r_' ),
		'prestation' => sanitize_text_field( wp_unslash( isset( $_POST['prestation'] ) ? $_POST['prestation'] : 'autre' ) ),
		'cp'         => sanitize_text_field( wp_unslash( isset( $_POST['cp'] ) ? $_POST['cp'] : '' ) ),
		'platforms'  => $plats,
		'freq'       => max( 3600, (int) ( isset( $_POST['freq'] ) ? $_POST['freq'] : 86400 ) ),
		'active'     => 1,
		'last_run'   => 0,
		'created'    => current_time( 'Y-m-d H:i' ),
	);
	ann_save_reminders( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'saved' ) );
} );
add_action( 'admin_post_ann_reminder_toggle', function () {
	ann_check_admin();
	check_admin_referer( 'ann_reminder_toggle' );
	$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_reminders();
	foreach ( $list as &$r ) {
		if ( isset( $r['id'] ) && $r['id'] === $id ) { $r['active'] = empty( $r['active'] ) ? 1 : 0; break; }
	}
	unset( $r );
	ann_save_reminders( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'toggled' ) );
} );
add_action( 'admin_post_ann_reminder_delete', function () {
	ann_check_admin();
	check_admin_referer( 'ann_reminder_delete' );
	$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_reminders(), function ( $r ) use ( $id ) {
		return ! ( isset( $r['id'] ) && $r['id'] === $id );
	} );
	ann_save_reminders( $list );
	ann_redirect( 'ann-agent', array( 'msg' => 'deleted' ) );
} );
add_action( 'admin_post_ann_reminder_test', function () {
	ann_check_admin();
	check_admin_referer( 'ann_reminder_test' );
	$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_reminders();
	foreach ( $list as &$r ) {
		if ( isset( $r['id'] ) && $r['id'] === $id ) {
			$r['last_run'] = 0;
			ann_save_reminders( $list );
			ann_cron_agent_run();
			break;
		}
	}
	ann_redirect( 'ann-agent', array( 'msg' => 'tested' ) );
} );

/* ===========================================================================
 * Handlers reglages
 * ========================================================================= */
add_action( 'admin_post_ann_settings', function () {
	ann_check_admin();
	check_admin_referer( 'ann_settings' );
	$existing = ann_get_settings();
	$new      = $existing;
	$section  = sanitize_text_field( wp_unslash( isset( $_POST['_form_section'] ) ? $_POST['_form_section'] : 'main' ) );

	if ( 'imap' === $section ) {
		// Form IMAP : ne touche QUE les champs imap_*
		$imap_pass_new = sanitize_text_field( wp_unslash( isset( $_POST['imap_pass'] ) ? $_POST['imap_pass'] : '' ) );
		$new['imap_on']     = empty( $_POST['imap_on'] ) ? 0 : 1;
		$new['imap_host']   = sanitize_text_field( wp_unslash( isset( $_POST['imap_host'] ) ? $_POST['imap_host'] : '' ) );
		$new['imap_port']   = sanitize_text_field( wp_unslash( isset( $_POST['imap_port'] ) ? $_POST['imap_port'] : '993' ) );
		$new['imap_user']   = sanitize_text_field( wp_unslash( isset( $_POST['imap_user'] ) ? $_POST['imap_user'] : '' ) );
		if ( '' !== $imap_pass_new ) { $new['imap_pass'] = $imap_pass_new; }
		$new['imap_folder'] = sanitize_text_field( wp_unslash( isset( $_POST['imap_folder'] ) ? $_POST['imap_folder'] : 'INBOX' ) );
		update_option( ANN_SET_OPT, $new );
	} else {
		// Form principal : Général + Telegram + Modules. Ne touche PAS aux champs imap_*.
		$new['ville']        = sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) );
		$new['cp']           = sanitize_text_field( wp_unslash( isset( $_POST['cp'] ) ? $_POST['cp'] : '' ) );
		$new['tg_token']     = sanitize_text_field( wp_unslash( isset( $_POST['tg_token'] ) ? $_POST['tg_token'] : '' ) );
		$new['tg_chat']      = sanitize_text_field( wp_unslash( isset( $_POST['tg_chat'] ) ? $_POST['tg_chat'] : '' ) );
		$new['cron_morning'] = empty( $_POST['cron_morning'] ) ? 0 : 1;
		update_option( ANN_SET_OPT, $new );
		// Modules : seulement si on est sur le form principal
		$mods = array(
			'annonces'     => empty( $_POST['mod_annonces'] )     ? 0 : 1,
			'agent'        => empty( $_POST['mod_agent'] )        ? 0 : 1,
			'broadcast'    => empty( $_POST['mod_broadcast'] )    ? 0 : 1,
			'ambassadeurs' => empty( $_POST['mod_ambassadeurs'] ) ? 0 : 1,
		);
		update_option( ANN_MOD_OPT, $mods );
	}
	ann_redirect( 'ann-settings', array( 'msg' => 'saved' ) );
} );

add_action( 'admin_post_ann_test_tg', function () {
	ann_check_admin();
	check_admin_referer( 'ann_test_tg' );
	$ok = ann_tg_push( '✅ Test Anna Photo : Telegram fonctionne !' );
	ann_redirect( 'ann-settings', array( 'msg' => $ok ? 'tg_ok' : 'tg_ko' ) );
} );

add_action( 'admin_post_ann_test_imap', function () {
	ann_check_admin();
	check_admin_referer( 'ann_test_imap' );
	$s = ann_imap_connect();
	if ( is_wp_error( $s ) ) {
		ann_redirect( 'ann-settings', array( 'msg' => 'imap_ko', 'err' => rawurlencode( $s->get_error_message() ) ) );
	}
	$count = @imap_num_msg( $s );
	imap_close( $s );
	ann_redirect( 'ann-settings', array( 'msg' => 'imap_ok', 'n' => (int) $count ) );
} );

add_action( 'admin_post_ann_imap_run', function () {
	ann_check_admin();
	check_admin_referer( 'ann_imap_run' );
	if ( empty( ann_setting( 'imap_on' ) ) ) {
		// Active temporairement pour ce passage manuel
		$set = ann_get_settings();
		$set['imap_on'] = 1;
		update_option( ANN_SET_OPT, $set );
		ann_imap_run();
		$set['imap_on'] = 0;
		update_option( ANN_SET_OPT, $set );
	} else {
		ann_imap_run();
	}
	ann_redirect( 'ann-settings', array( 'msg' => 'imap_run' ) );
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
		'added'    => array( 'success', 'Prospect ajoute. Clique sur WhatsApp ou SMS pour envoyer le message.' ),
		'updated'  => array( 'success', 'Modifications enregistrees.' ),
		'deleted'  => array( 'success', 'Supprime.' ),
		'saved'    => array( 'success', 'Enregistre.' ),
		'toggled'  => array( 'success', 'Etat modifie.' ),
		'tested'   => array( 'success', 'Rappel envoye sur Telegram, verifie ton telephone.' ),
		'tg_ok'    => array( 'success', 'Message de test Telegram envoye. Verifie ton telephone.' ),
		'tg_ko'    => array( 'error',   'Telegram non configure : remplis le Token et le Chat ID.' ),
		'phone'    => array( 'error',   'Numero de telephone invalide. Exemple : 06 12 34 56 78' ),
		'dup'      => array( 'error',   'Ce numero est deja dans ta liste (anti-doublon).' ),
		'bc_ok'    => array( 'success', 'Message Telegram envoye.' ),
		'bc_ko'    => array( 'error',   'Telegram non configure.' ),
		'bc_empty' => array( 'error',   'Message vide.' ),
		'imap_ok'  => array( 'success', 'IMAP : connexion OK (' . (int) ( isset( $_GET['n'] ) ? $_GET['n'] : 0 ) . ' message(s) dans la boite).' ),
		'imap_ko'  => array( 'error',   'IMAP erreur : ' . esc_html( rawurldecode( wp_unslash( isset( $_GET['err'] ) ? $_GET['err'] : '' ) ) ) ),
		'imap_run' => array( 'success', 'Import manuel termine. Va voir tes prospects ou le journal.' ),
	);
	if ( ! isset( $map[ $m ] ) ) { return; }
	$cls = 'error' === $map[ $m ][0] ? 'notice-error' : 'notice-success';
	echo '<div class="notice ' . esc_attr( $cls ) . ' is-dismissible"><p>' . wp_kses_post( $map[ $m ][1] ) . '</p></div>';
}

/* ===========================================================================
 * CSS
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
	.ann-table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;}
	.ann-table th{background:#f8fafc;padding:10px;text-align:left;font-size:12px;text-transform:uppercase;color:#475569;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;}
	.ann-table td{padding:10px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:13px;}
	.ann-table tr:hover{background:#f8fafc;}
	.ann-state-line{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:13px;}
	.ann-state-line:last-child{border:0;}
	.ann-ok{color:#059669;font-weight:600;}
	.ann-ko{color:#dc2626;font-weight:600;}
	.ann-prest{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;margin:0 6px 8px 0;cursor:pointer;font-size:14px;font-weight:500;text-decoration:none;color:#0f172a;transition:all .15s;}
	.ann-prest:hover{border-color:#3b82f6;background:#eff6ff;}
	.ann-prest.is-active{background:#3b82f6;color:#fff;border-color:#3b82f6;}
	.ann-platform{display:inline-flex;align-items:center;gap:10px;padding:18px 20px;background:#fff;border:2px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#0f172a;font-weight:600;font-size:15px;transition:all .15s;}
	.ann-platform:hover{border-color:#3b82f6;background:#eff6ff;transform:translateY(-2px);box-shadow:0 6px 14px rgba(59,130,246,.15);color:#1d4ed8;}
	.ann-platform .em{font-size:24px;}
	.ann-platform-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;}
	.ann-kw-chip{display:inline-block;padding:3px 10px;background:#f1f5f9;border-radius:12px;font-size:12px;color:#475569;margin-right:6px;}
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
	<?php $cp = ann_setting( 'cp', '' ); ?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">📸 Centre de controle — Anna Photo</h1>
		<?php ann_notice(); ?>

		<!-- MEGA CTA -->
		<style>
		.ann-mega{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:28px 32px;border-radius:16px;margin:14px 0 20px;box-shadow:0 10px 30px rgba(102,126,234,.3);}
		.ann-mega-row{display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
		.ann-mega-text{flex:1;min-width:260px;}
		.ann-mega-text h2{color:#fff!important;font-size:26px;margin:0 0 6px;font-weight:700;padding:0;border:0;}
		.ann-mega-text p{color:rgba(255,255,255,.9);font-size:15px;margin:0;}
		.ann-mega-btn{display:inline-block;padding:18px 32px;font-size:18px;font-weight:700;background:#fff;color:#667eea!important;text-decoration:none;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,.15);transition:all .15s;white-space:nowrap;}
		.ann-mega-btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.25);background:#f8f8ff;}
		.ann-mega-quick{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px;padding-top:18px;border-top:1px solid rgba(255,255,255,.2);}
		.ann-mega-quick .lbl{color:rgba(255,255,255,.85);font-size:13px;font-weight:600;margin-right:8px;align-self:center;}
		.ann-mega-quick a{background:rgba(255,255,255,.2);color:#fff!important;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;transition:background .15s;}
		.ann-mega-quick a:hover{background:rgba(255,255,255,.4);}
		</style>
		<div class="ann-mega">
			<div class="ann-mega-row">
				<div class="ann-mega-text">
					<h2>🎯 Va chercher tes prochains clients</h2>
					<p>Liens directs vers Leboncoin, Facebook, Instagram pre-remplis avec les bons mots-cles. 1 clic suffit.</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-annonces' ) ); ?>" class="ann-mega-btn">🎯 CHERCHER DES ANNONCES →</a>
			</div>
			<div class="ann-mega-quick">
				<span class="lbl">⚡ Raccourcis Leboncoin :</span>
				<?php
				foreach ( array( 'mariage' => '💍 Mariage', 'famille' => '👨‍👩‍👧 Famille', 'grossesse' => '🤰 Grossesse', 'couple' => '💑 EVJF', 'evenement' => '🎉 Evenement' ) as $pkey => $plabel ) {
					$kw  = ann_keywords( $pkey );
					$url = ann_platform_url( 'leboncoin', $kw[0], $cp );
					echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $plabel ) . '</a>';
				}
				?>
			</div>
		</div>

		<div class="ann-help">
			<strong>☀️ Ta feuille de route du jour</strong>
			<ul style="margin:8px 0 0 18px;">
				<li><b><?php echo (int) $counts['nouveau']; ?></b> prospect(s) a contacter</li>
				<li><b><?php echo (int) $counts['relance']; ?></b> a relancer</li>
				<?php if ( 0 === $counts['nouveau'] + $counts['relance'] ) : ?>
					<li style="color:#b45309;">⚠️ Aucune vente en attente — va chercher de nouvelles annonces !</li>
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
				<span class="t">📋 Mes prospects</span>
				<span class="d">Gere tes prospects, change les statuts, envoie WhatsApp/SMS en 1 clic.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php if ( ann_module_on( 'annonces' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-annonces' ) ); ?>" class="ann-action">
				<span class="t">🎯 Trouver des annonces</span>
				<span class="d">Liens directs vers Leboncoin, Facebook, Instagram, Google avec les bons mots-cles selon la prestation.</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php endif; ?>
			<?php if ( ann_module_on( 'agent' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-agent' ) ); ?>" class="ann-action">
				<span class="t">🔔 Rappels automatiques</span>
				<span class="d">Recois un rappel Telegram avec les liens de recherche (ex : tous les jours a 9h, prospection mariage).</span>
				<span class="arr">Ouvrir →</span>
			</a>
			<?php endif; ?>
			<?php if ( ann_module_on( 'ambassadeurs' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-ambass' ) ); ?>" class="ann-action">
				<span class="t">🤝 Ambassadeurs</span>
				<span class="d">Programme de parrainage clients.</span>
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
							<tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px;">Aucun prospect en attente 👌 — va chercher de nouvelles annonces !</td></tr>
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
									<?php if ( ! empty( $p['link'] ) ) : ?><br><a href="<?php echo esc_url( $p['link'] ); ?>" target="_blank" rel="noopener" style="font-size:11px;">Voir l'annonce ↗</a><?php endif; ?>
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
					<div class="ann-state-line"><span>Module Annonces</span><span class="<?php echo ! empty( $mods['annonces'] ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( $mods['annonces'] ) ? '✓' : '✗'; ?></span></div>
					<div class="ann-state-line"><span>Module Rappels</span><span class="<?php echo ! empty( $mods['agent'] ) ? 'ann-ok' : 'ann-ko'; ?>"><?php echo ! empty( $mods['agent'] ) ? '✓' : '✗'; ?></span></div>
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
				<li>Tu trouves une annonce (utilise <a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-annonces' ) ); ?>">🎯 Trouver des annonces</a> pour les liens rapides).</li>
				<li>Tu ajoutes le prospect : numero + lien + <strong>note descriptive</strong> (ex : "mariage juin 2025 La Baule, budget 1500").</li>
				<li>Le message s'auto-genere selon la note. Tu cliques <span class="ann-btn-wa">WhatsApp</span> ou <span class="ann-btn-sms">SMS</span>.</li>
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
					<div class="ann-full"><label>Lien de l'annonce</label><input type="text" name="link" placeholder="https://... (Leboncoin, Facebook, etc.)"></div>
					<div class="ann-full"><label>📝 Note descriptive (le message s'adapte automatiquement)</label>
						<textarea name="note" id="ann_note" rows="2" placeholder="Ex : cherche photographe mariage juin 2025 La Baule budget 1500€"></textarea>
					</div>
					<div><label>Prestation (auto-detectee depuis la note)</label><select name="prestation" id="ann_prestation">
						<option value="auto">🤖 Auto (depuis la note)</option>
						<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Source</label><select name="source">
						<?php foreach ( $sources as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Ville</label><input type="text" name="ville" id="ann_ville" value="<?php echo esc_attr( $ville_def ); ?>"></div>
					<div class="ann-full"><label>Message a envoyer (auto-rempli, modifiable)</label>
						<textarea name="message" id="ann_message" rows="4"></textarea>
						<button type="button" class="button" onclick="annRegen()" style="margin-top:6px;">🔄 Regenerer depuis la note</button>
						<small style="color:#64748b;display:block;margin-top:4px;">Variables : <code>{prenom}</code> <code>{ville}</code> <code>{note}</code></small>
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
			<thead><tr><th>Prospect</th><th>Note</th><th>Statut</th><th>Contacter</th><th>Date</th><th></th></tr></thead>
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
							<?php if ( ! empty( $p['link'] ) ) : ?><br><a href="<?php echo esc_url( $p['link'] ); ?>" target="_blank" rel="noopener" style="font-size:12px;">📎 Voir l'annonce ↗</a><?php endif; ?>
							<br><span class="ann-pill"><?php echo esc_html( isset( $prestations[ $p['prestation'] ] ) ? $prestations[ $p['prestation'] ] : '' ); ?></span>
							<small style="color:#94a3b8;"><?php echo esc_html( isset( $sources[ $p['source'] ] ) ? $sources[ $p['source'] ] : '' ); ?></small>
						</td>
						<td style="font-size:12px;color:#475569;max-width:300px;">
							<?php if ( ! empty( $p['note'] ) ) : ?><em><?php echo esc_html( $p['note'] ); ?></em><?php else : ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
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
								<summary style="cursor:pointer;font-size:12px;color:#64748b;">✏️ Modifier note / message</summary>
								<form method="post" action="<?php echo esc_url( $post ); ?>" style="margin-top:6px;">
									<input type="hidden" name="action" value="ann_update">
									<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
									<?php wp_nonce_field( 'ann_update' ); ?>
									<label style="font-size:11px;">Note :</label>
									<textarea name="note" rows="2" style="width:320px;"><?php echo esc_textarea( isset( $p['note'] ) ? $p['note'] : '' ); ?></textarea><br>
									<label style="font-size:11px;">Message :</label>
									<textarea name="message" rows="4" style="width:320px;"><?php echo esc_textarea( isset( $p['message'] ) ? $p['message'] : '' ); ?></textarea><br>
									<label style="font-size:11px;"><input type="checkbox" name="regenerate" value="1"> Regenerer message depuis la note</label><br>
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
	var ANN_DETECT = <?php echo wp_json_encode( array(
		'grossesse' => array( 'grossesse', 'enceinte', 'maternit', 'futur maman', 'attend un', 'attend une' ),
		'mariage'   => array( 'mariage', 'mariee', 'marie', 'wedding', 'noce' ),
		'couple'    => array( 'evjf', 'evjg', 'enterrement vie', 'couple ' ),
		'famille'   => array( 'famille', 'enfant', 'bebe', 'naissance', 'bapteme' ),
		'portrait'  => array( 'portrait', 'book ', 'corporate', 'profil pro' ),
		'evenement' => array( 'anniversaire', 'evenement', 'soiree', 'gala' ),
	) ); ?>;
	var ANN_VAR = 0;
	function annDetect(note){
		var n = (note||'').toLowerCase();
		for (var k in ANN_DETECT) {
			var ws = ANN_DETECT[k];
			for (var i=0; i<ws.length; i++) {
				if (n.indexOf(ws[i]) !== -1) return k;
			}
		}
		return 'autre';
	}
	function annCurrentPrestation(){
		var s = document.getElementById('ann_prestation');
		var v = s ? s.value : 'autre';
		if (v === 'auto') { v = annDetect((document.getElementById('ann_note')||{}).value || ''); }
		return v;
	}
	function annFill(){
		var prest  = annCurrentPrestation();
		var prenom = ((document.getElementById('ann_prenom')||{}).value || '').trim();
		var ville  = ((document.getElementById('ann_ville')||{}).value || '').trim() || 'votre region';
		var note   = ((document.getElementById('ann_note')||{}).value || '').trim();
		var set    = ANN_TPL[prest] || ANN_TPL['autre'] || [''];
		if (!Array.isArray(set)) { set = [String(set)]; }
		var msg = set[ANN_VAR % set.length] || '';
		msg = msg.split('{prenom}').join(prenom).split('{ville}').join(ville).split('{note}').join(note);
		msg = msg.replace(/\s{2,}/g,' ').trim();
		var t = document.getElementById('ann_message'); if (t) t.value = msg;
	}
	function annRegen(){ ANN_VAR++; annFill(); }
	document.addEventListener('DOMContentLoaded', function(){
		['ann_prestation','ann_prenom','ann_ville','ann_note'].forEach(function(id){
			var el = document.getElementById(id);
			if (el){ el.addEventListener('change', function(){ ANN_VAR=0; annFill(); }); el.addEventListener('keyup', annFill); }
		});
		annFill();
	});
	</script>
	<?php
}

/* ===========================================================================
 * Page : Trouver des annonces (hub de recherche)
 * ========================================================================= */
function ann_render_annonces_page() {
	ann_check_admin();
	if ( ! ann_module_on( 'annonces' ) ) { echo '<div class="wrap"><p>Module desactive.</p></div>'; return; }
	$prestations = ann_prestations();
	$platforms   = ann_platforms();
	$selected    = sanitize_text_field( wp_unslash( isset( $_GET['p'] ) ? $_GET['p'] : 'mariage' ) );
	if ( ! isset( $prestations[ $selected ] ) ) { $selected = 'mariage'; }
	$cp_def    = ann_setting( 'cp', '' );
	$ville_def = ann_setting( 'ville', '' );
	$cp        = sanitize_text_field( wp_unslash( isset( $_GET['cp'] ) ? $_GET['cp'] : $cp_def ) );
	$keywords  = ann_keywords( $selected );
	$post      = admin_url( 'admin-post.php' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">🎯 Trouver des annonces</h1>
		<?php ann_notice(); ?>

		<div class="ann-help">
			<strong>Comment ca marche :</strong>
			<ol style="margin:6px 0 0 18px;">
				<li>Choisis le <strong>type de prestation</strong> que tu veux prospecter.</li>
				<li>Clique sur la <strong>plateforme</strong> (Leboncoin, Facebook…) — un nouvel onglet s'ouvre avec la bonne recherche.</li>
				<li>Quand tu trouves une annonce avec un numero : copie-le, reviens ici, et utilise le formulaire ci-dessous pour <strong>l'ajouter en 1 clic</strong>.</li>
				<li>Le message WhatsApp/SMS s'auto-genere selon ta note descriptive.</li>
			</ol>
		</div>
		<div class="ann-legal">⚖️ <strong>Important :</strong> chaque site (Leboncoin, Facebook…) garde ses propres regles. Contacte uniquement les gens qui ont <strong>publie une annonce demandant un service</strong>. Pas de spam, pas d'envoi en masse.</div>

		<div class="ann-section-title">1️⃣ Choisis ta prestation</div>
		<div>
			<?php foreach ( $prestations as $k => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-annonces&p=' . $k . '&cp=' . rawurlencode( $cp ) ) ); ?>" class="ann-prest <?php echo $selected === $k ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<form method="get" class="ann-card" style="padding:12px 18px;">
			<input type="hidden" name="page" value="ann-annonces">
			<input type="hidden" name="p" value="<?php echo esc_attr( $selected ); ?>">
			<label><strong>Code postal :</strong></label>
			<input type="text" name="cp" value="<?php echo esc_attr( $cp ); ?>" placeholder="Ex : 44000" style="width:120px;">
			<button class="button">Filtrer par lieu</button>
			<span style="color:#64748b;font-size:12px;">(optionnel — vide = recherche partout)</span>
		</form>

		<div class="ann-section-title">2️⃣ Liens de recherche directs (s'ouvrent dans un nouvel onglet)</div>
		<?php foreach ( $keywords as $q ) : ?>
			<div style="margin:14px 0;">
				<div style="font-size:13px;color:#475569;margin-bottom:8px;">Mot-cle : <span class="ann-kw-chip"><?php echo esc_html( $q ); ?></span></div>
				<div class="ann-platform-grid">
					<?php foreach ( $platforms as $pkey => $pl ) :
						$url = ann_platform_url( $pkey, $q, $cp );
						if ( '' === $url ) { continue; } ?>
						<a class="ann-platform" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
							<span class="em"><?php echo esc_html( $pl['emoji'] ); ?></span>
							<span><?php echo esc_html( $pl['label'] ); ?></span>
							<span style="margin-left:auto;color:#3b82f6;font-size:12px;">↗</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="ann-section-title">3️⃣ Ajout rapide d'une annonce trouvee</div>
		<div class="ann-card">
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_add">
				<input type="hidden" name="_redir" value="ann-annonces">
				<?php wp_nonce_field( 'ann_add' ); ?>
				<div class="ann-grid-2c">
					<div><label>Prenom (si visible)</label><input type="text" name="prenom" id="ann_prenom" placeholder="Ex : Julie"></div>
					<div><label>Telephone *</label><input type="text" name="phone" required placeholder="06 12 34 56 78"></div>
					<div class="ann-full"><label>Lien de l'annonce *</label><input type="text" name="link" placeholder="https://..." required></div>
					<div class="ann-full"><label>📝 Note descriptive (le message s'adapte automatiquement)</label>
						<textarea name="note" id="ann_note" rows="2" placeholder="Ex : cherche photographe pour son mariage juin 2025 La Baule, budget 1500"></textarea>
					</div>
					<div><label>Prestation</label><select name="prestation" id="ann_prestation">
						<option value="auto">🤖 Auto (depuis la note)</option>
						<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $selected ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Source</label><select name="source">
						<?php $srcs = ann_sources(); foreach ( $srcs as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Ville</label><input type="text" name="ville" id="ann_ville" value="<?php echo esc_attr( $ville_def ); ?>"></div>
					<div class="ann-full"><label>Message (auto, modifiable)</label>
						<textarea name="message" id="ann_message" rows="4"></textarea>
						<button type="button" class="button" onclick="annRegen()" style="margin-top:6px;">🔄 Regenerer depuis la note</button>
					</div>
				</div>
				<p style="margin-top:14px;"><button type="submit" class="button button-primary button-hero">+ Ajouter au CRM</button></p>
			</form>
		</div>
	</div>

	<script>
	var ANN_TPL = <?php echo wp_json_encode( ann_tpl_data() ); ?>;
	var ANN_DETECT = <?php echo wp_json_encode( array(
		'grossesse' => array( 'grossesse', 'enceinte', 'maternit', 'futur maman' ),
		'mariage'   => array( 'mariage', 'mariee', 'marie', 'wedding' ),
		'couple'    => array( 'evjf', 'evjg', 'enterrement vie', 'couple ' ),
		'famille'   => array( 'famille', 'enfant', 'bebe', 'naissance', 'bapteme' ),
		'portrait'  => array( 'portrait', 'book ', 'corporate' ),
		'evenement' => array( 'anniversaire', 'evenement', 'soiree' ),
	) ); ?>;
	var ANN_VAR = 0;
	function annDetect(note){
		var n = (note||'').toLowerCase();
		for (var k in ANN_DETECT) { var ws = ANN_DETECT[k]; for (var i=0; i<ws.length; i++) { if (n.indexOf(ws[i]) !== -1) return k; } }
		return 'autre';
	}
	function annCurrentPrestation(){
		var s = document.getElementById('ann_prestation');
		var v = s ? s.value : 'autre';
		if (v === 'auto') { v = annDetect((document.getElementById('ann_note')||{}).value || ''); }
		return v;
	}
	function annFill(){
		var prest = annCurrentPrestation();
		var prenom = ((document.getElementById('ann_prenom')||{}).value || '').trim();
		var ville = ((document.getElementById('ann_ville')||{}).value || '').trim() || 'votre region';
		var note  = ((document.getElementById('ann_note')||{}).value || '').trim();
		var set = ANN_TPL[prest] || ANN_TPL['autre'] || [''];
		if (!Array.isArray(set)) { set = [String(set)]; }
		var msg = set[ANN_VAR % set.length] || '';
		msg = msg.split('{prenom}').join(prenom).split('{ville}').join(ville).split('{note}').join(note);
		msg = msg.replace(/\s{2,}/g,' ').trim();
		var t = document.getElementById('ann_message'); if (t) t.value = msg;
	}
	function annRegen(){ ANN_VAR++; annFill(); }
	document.addEventListener('DOMContentLoaded', function(){
		['ann_prestation','ann_prenom','ann_ville','ann_note'].forEach(function(id){
			var el = document.getElementById(id);
			if (el){ el.addEventListener('change', function(){ ANN_VAR=0; annFill(); }); el.addEventListener('keyup', annFill); }
		});
		annFill();
	});
	</script>
	<?php
}

/* ===========================================================================
 * Page : Rappels automatiques (ancien agent)
 * ========================================================================= */
function ann_render_agent_page() {
	ann_check_admin();
	if ( ! ann_module_on( 'agent' ) ) { echo '<div class="wrap"><p>Module desactive.</p></div>'; return; }
	$reminders = ann_get_reminders();
	$logs      = ann_agent_log_get();
	$post      = admin_url( 'admin-post.php' );
	$tg_ok     = ann_tg_configured();
	$prestations = ann_prestations();
	$platforms = ann_platforms();
	$cp_def    = ann_setting( 'cp', '' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">🔔 Rappels automatiques</h1>
		<?php ann_notice(); ?>
		<div class="ann-help">
			<strong>Comment ca marche ?</strong> Tu programmes des rappels (ex : "Tous les jours a 9h, prospection mariage Nantes"). A chaque rappel, tu recois sur Telegram un message avec les <strong>liens directs</strong> vers Leboncoin, Facebook, Insta… Tu cliques, tu trouves, tu ajoutes.
			<?php if ( ! $tg_ok ) : ?><br><span style="color:#dc2626;">⚠️ Configure d'abord Telegram dans les Reglages.</span><?php endif; ?>
		</div>

		<div class="ann-card">
			<h2 style="margin-top:0;">➕ Nouveau rappel</h2>
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_reminder_save">
				<?php wp_nonce_field( 'ann_reminder_save' ); ?>
				<div class="ann-grid-2c">
					<div><label>Prestation a prospecter</label><select name="prestation">
						<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Code postal (optionnel)</label><input type="text" name="cp" value="<?php echo esc_attr( $cp_def ); ?>" placeholder="44000"></div>
					<div><label>Frequence</label>
						<select name="freq">
							<option value="3600">Toutes les heures</option>
							<option value="21600">Toutes les 6h</option>
							<option value="86400" selected>Tous les jours</option>
							<option value="604800">Toutes les semaines</option>
						</select>
					</div>
					<div class="ann-full"><label>Plateformes a inclure dans le rappel</label>
						<?php foreach ( $platforms as $pkey => $pl ) : ?>
							<label style="display:inline-block;margin:4px 12px 4px 0;"><input type="checkbox" name="platforms[]" value="<?php echo esc_attr( $pkey ); ?>" <?php echo in_array( $pkey, array( 'leboncoin', 'fb_marketplace', 'google' ), true ) ? 'checked' : ''; ?>> <?php echo esc_html( $pl['emoji'] . ' ' . $pl['label'] ); ?></label>
						<?php endforeach; ?>
					</div>
				</div>
				<p style="margin-top:14px;"><button class="button button-primary" <?php echo ! $tg_ok ? 'disabled' : ''; ?>>Programmer le rappel</button></p>
			</form>
		</div>

		<div class="ann-section-title">🔄 Rappels actifs</div>
		<table class="ann-table">
			<thead><tr><th>Prestation</th><th>Lieu</th><th>Plateformes</th><th>Frequence</th><th>Dernier envoi</th><th>Etat</th><th></th></tr></thead>
			<tbody>
				<?php if ( empty( $reminders ) ) : ?>
					<tr><td colspan="7" style="text-align:center;color:#64748b;padding:24px;">Aucun rappel programme. Ajoute-en un 👆</td></tr>
				<?php endif; ?>
				<?php
				$freq_lbl = array( 3600 => '/heure', 21600 => '/6h', 86400 => '/jour', 604800 => '/semaine' );
				foreach ( $reminders as $r ) : ?>
					<tr>
						<td><strong><?php echo esc_html( isset( $prestations[ $r['prestation'] ] ) ? $prestations[ $r['prestation'] ] : $r['prestation'] ); ?></strong></td>
						<td><?php echo ! empty( $r['cp'] ) ? esc_html( $r['cp'] ) : '—'; ?></td>
						<td style="font-size:11px;">
							<?php
							$plist = isset( $r['platforms'] ) && is_array( $r['platforms'] ) ? $r['platforms'] : array();
							foreach ( $plist as $pk ) {
								if ( isset( $platforms[ $pk ] ) ) { echo esc_html( $platforms[ $pk ]['emoji'] ) . ' '; }
							}
							?>
						</td>
						<td><?php echo esc_html( isset( $freq_lbl[ (int) $r['freq'] ] ) ? $freq_lbl[ (int) $r['freq'] ] : '?' ); ?></td>
						<td style="font-size:12px;"><?php echo ! empty( $r['last_run'] ) ? esc_html( wp_date( 'd/m H:i', (int) $r['last_run'] ) ) : '—'; ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="ann_reminder_toggle">
								<input type="hidden" name="id" value="<?php echo esc_attr( $r['id'] ); ?>">
								<?php wp_nonce_field( 'ann_reminder_toggle' ); ?>
								<button class="button button-small"><?php echo empty( $r['active'] ) ? 'OFF' : 'ON'; ?></button>
							</form>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $post ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="ann_reminder_test">
								<input type="hidden" name="id" value="<?php echo esc_attr( $r['id'] ); ?>">
								<?php wp_nonce_field( 'ann_reminder_test' ); ?>
								<button class="button button-small" title="Envoyer maintenant pour test">📤 Test</button>
							</form>
							<form method="post" action="<?php echo esc_url( $post ); ?>" onsubmit="return confirm('Supprimer ?');" style="display:inline;">
								<input type="hidden" name="action" value="ann_reminder_delete">
								<input type="hidden" name="id" value="<?php echo esc_attr( $r['id'] ); ?>">
								<?php wp_nonce_field( 'ann_reminder_delete' ); ?>
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
				<?php foreach ( $logs as $line ) : ?><div><?php echo esc_html( $line ); ?></div><?php endforeach; ?>
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
		<div class="ann-help"><strong>Programme parrainage clients :</strong> tes anciens clients qui te recommandent. Note ici qui t'a envoye qui — pour pouvoir les remercier (remise, tirage offert, seance bonus).</div>
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
 * Page : Capture (cible du bookmarklet)
 * ========================================================================= */
function ann_render_capture_page() {
	ann_check_admin();
	$u  = esc_url_raw( wp_unslash( isset( $_GET['u'] ) ? $_GET['u'] : '' ) );
	$t  = sanitize_text_field( wp_unslash( isset( $_GET['t'] ) ? $_GET['t'] : '' ) );
	$p  = sanitize_text_field( wp_unslash( isset( $_GET['p'] ) ? $_GET['p'] : '' ) );
	$n  = sanitize_textarea_field( wp_unslash( isset( $_GET['n'] ) ? $_GET['n'] : '' ) );

	// Detection source d'apres l'URL
	$source = 'autre';
	if ( false !== stripos( $u, 'leboncoin' ) )      { $source = 'leboncoin'; }
	elseif ( false !== stripos( $u, 'facebook' ) )   { $source = 'facebook'; }
	elseif ( false !== stripos( $u, 'instagram' ) )  { $source = 'instagram'; }
	elseif ( false !== stripos( $u, 'twitter.com' ) || false !== stripos( $u, 'x.com' ) ) { $source = 'autre'; }
	elseif ( false !== stripos( $u, 'google' ) )     { $source = 'google'; }

	$pr = ann_detect_prestation( $n . ' ' . $t );
	$prestations = ann_prestations();
	$sources     = ann_sources();
	$ville_def   = ann_setting( 'ville', '' );
	$post        = admin_url( 'admin-post.php' );
	ann_css();
	?>
	<div class="wrap ann-wrap">
		<h1 class="ann-h1">📎 Capturer cette annonce</h1>
		<div class="ann-help">
			✅ Verifie les infos detectees (notamment le <strong>telephone</strong>), complete si besoin, puis clique <strong>Ajouter</strong>. Le message s'auto-genere.
		</div>
		<?php if ( '' !== $u ) : ?>
			<div class="ann-card" style="font-size:13px;">
				<strong>Source detectee :</strong> <?php echo esc_html( isset( $sources[ $source ] ) ? $sources[ $source ] : $source ); ?><br>
				<strong>URL :</strong> <a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" style="word-break:break-all;"><?php echo esc_html( $u ); ?></a>
			</div>
		<?php endif; ?>
		<div class="ann-card">
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_add">
				<input type="hidden" name="_redir" value="ann-prospects">
				<?php wp_nonce_field( 'ann_add' ); ?>
				<div class="ann-grid-2c">
					<div><label>Prenom (si visible)</label><input type="text" name="prenom" id="ann_prenom"></div>
					<div><label>Telephone *</label><input type="text" name="phone" required value="<?php echo esc_attr( $p ); ?>" placeholder="06 12 34 56 78"></div>
					<div class="ann-full"><label>Lien de l'annonce</label><input type="text" name="link" value="<?php echo esc_attr( $u ); ?>"></div>
					<div class="ann-full"><label>📝 Note (capturee depuis la page)</label>
						<textarea name="note" id="ann_note" rows="3"><?php echo esc_textarea( '' !== $n ? $n : $t ); ?></textarea>
					</div>
					<div><label>Prestation</label><select name="prestation" id="ann_prestation">
						<option value="auto">🤖 Auto (depuis la note)</option>
						<?php foreach ( $prestations as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $pr ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Source</label><select name="source">
						<?php foreach ( $sources as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $source ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
					</select></div>
					<div><label>Ville</label><input type="text" name="ville" id="ann_ville" value="<?php echo esc_attr( $ville_def ); ?>"></div>
					<div class="ann-full"><label>Message a envoyer (auto)</label>
						<textarea name="message" id="ann_message" rows="4"></textarea>
						<button type="button" class="button" onclick="annRegen()" style="margin-top:6px;">🔄 Regenerer depuis la note</button>
					</div>
				</div>
				<p style="margin-top:14px;">
					<button type="submit" class="button button-primary button-hero">+ Ajouter au CRM</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects' ) ); ?>" class="button">Annuler</a>
				</p>
			</form>
		</div>
	</div>
	<script>
	var ANN_TPL = <?php echo wp_json_encode( ann_tpl_data() ); ?>;
	var ANN_DETECT = <?php echo wp_json_encode( array(
		'grossesse' => array( 'grossesse', 'enceinte', 'maternit', 'futur maman' ),
		'mariage'   => array( 'mariage', 'mariee', 'marie', 'wedding' ),
		'couple'    => array( 'evjf', 'evjg', 'enterrement vie', 'couple ' ),
		'famille'   => array( 'famille', 'enfant', 'bebe', 'naissance', 'bapteme' ),
		'portrait'  => array( 'portrait', 'book ', 'corporate' ),
		'evenement' => array( 'anniversaire', 'evenement', 'soiree' ),
	) ); ?>;
	var ANN_VAR = 0;
	function annDetect(note){var n=(note||'').toLowerCase();for(var k in ANN_DETECT){var ws=ANN_DETECT[k];for(var i=0;i<ws.length;i++){if(n.indexOf(ws[i])!==-1)return k;}}return 'autre';}
	function annCurrentPrestation(){var s=document.getElementById('ann_prestation');var v=s?s.value:'autre';if(v==='auto'){v=annDetect((document.getElementById('ann_note')||{}).value||'');}return v;}
	function annFill(){var prest=annCurrentPrestation();var prenom=((document.getElementById('ann_prenom')||{}).value||'').trim();var ville=((document.getElementById('ann_ville')||{}).value||'').trim()||'votre region';var note=((document.getElementById('ann_note')||{}).value||'').trim();var set=ANN_TPL[prest]||ANN_TPL['autre']||[''];if(!Array.isArray(set))set=[String(set)];var msg=set[ANN_VAR%set.length]||'';msg=msg.split('{prenom}').join(prenom).split('{ville}').join(ville).split('{note}').join(note);msg=msg.replace(/\s{2,}/g,' ').trim();var t=document.getElementById('ann_message');if(t)t.value=msg;}
	function annRegen(){ANN_VAR++;annFill();}
	document.addEventListener('DOMContentLoaded',function(){['ann_prestation','ann_prenom','ann_ville','ann_note'].forEach(function(id){var el=document.getElementById(id);if(el){el.addEventListener('change',function(){ANN_VAR=0;annFill();});el.addEventListener('keyup',annFill);}});annFill();});
	</script>
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
			<input type="hidden" name="_form_section" value="main">
			<?php wp_nonce_field( 'ann_settings' ); ?>

			<h2 style="margin-top:0;">📍 General</h2>
			<p><label><strong>Ta ville</strong> (apparait dans les messages)</label><br>
				<input type="text" name="ville" value="<?php echo esc_attr( isset( $s['ville'] ) ? $s['ville'] : '' ); ?>" placeholder="Ex : Nantes" style="width:320px;"></p>
			<p><label><strong>Code postal par defaut</strong> (pour les filtres de recherche)</label><br>
				<input type="text" name="cp" value="<?php echo esc_attr( isset( $s['cp'] ) ? $s['cp'] : '' ); ?>" placeholder="Ex : 44000" style="width:160px;"></p>

			<hr>
			<h2>📱 Telegram (notifications & rappels)</h2>
			<p class="description">1) Ecris a <code>@BotFather</code> sur Telegram pour creer un bot → Token. 2) Demarre une conversation avec ton bot et recupere ton Chat ID via <code>@userinfobot</code>.</p>
			<p><label>Token du bot</label><br><input type="text" name="tg_token" value="<?php echo esc_attr( isset( $s['tg_token'] ) ? $s['tg_token'] : '' ); ?>" style="width:420px;" placeholder="123456:ABC-..."></p>
			<p><label>Chat ID</label><br><input type="text" name="tg_chat" value="<?php echo esc_attr( isset( $s['tg_chat'] ) ? $s['tg_chat'] : '' ); ?>" style="width:240px;" placeholder="123456789"></p>
			<p><label><input type="checkbox" name="cron_morning" value="1" <?php checked( 1, (int) ( isset( $s['cron_morning'] ) ? $s['cron_morning'] : 0 ) ); ?>> Envoyer la feuille de route automatiquement chaque matin (vers 8h)</label></p>

			<hr>
			<h2>🧩 Modules</h2>
			<p class="description">Active uniquement ce dont tu as besoin.</p>
			<p>
				<label><input type="checkbox" name="mod_annonces" value="1" <?php checked( 1, (int) $mods['annonces'] ); ?>> 🎯 Trouver des annonces (hub Leboncoin / Facebook / Insta / Google)</label><br>
				<label><input type="checkbox" name="mod_agent" value="1" <?php checked( 1, (int) $mods['agent'] ); ?>> 🔔 Rappels automatiques Telegram</label><br>
				<label><input type="checkbox" name="mod_broadcast" value="1" <?php checked( 1, (int) $mods['broadcast'] ); ?>> 📣 Diffusion Telegram depuis le hub</label><br>
				<label><input type="checkbox" name="mod_ambassadeurs" value="1" <?php checked( 1, (int) $mods['ambassadeurs'] ); ?>> 🤝 Programme ambassadeurs (parrainage)</label>
			</p>

			<p><button class="button button-primary button-hero">Enregistrer</button></p>
		</form>

		<form method="post" action="<?php echo esc_url( $post ); ?>" style="max-width:980px;">
			<input type="hidden" name="action" value="ann_test_tg">
			<?php wp_nonce_field( 'ann_test_tg' ); ?>
			<button type="submit" class="button">📤 Envoyer un message de test Telegram</button>
		</form>

		<!-- BOOKMARKLET -->
		<div class="ann-card" style="margin-top:24px;">
			<h2 style="margin-top:0;">📎 Bookmarklet « Capturer cette annonce »</h2>
			<p>Glisse le bouton ci-dessous dans ta <strong>barre de favoris</strong>. Ensuite, quand tu trouves une annonce (Leboncoin, Facebook, Insta, n'importe ou) avec un numero affiche, clique le bouton dans tes favoris : un onglet s'ouvre avec le formulaire <strong>pre-rempli</strong> (URL, telephone detecte, prestation auto). Tu confirmes, c'est ajoute.</p>
			<p>
				<a href="<?php echo esc_attr( ann_bookmarklet_js() ); ?>" class="button button-primary button-hero" style="padding:14px 22px;font-size:15px;">📎 Capturer — Anna Photo</a>
				&nbsp;<span style="color:#64748b;font-size:13px;">⬅️ Glisse ce bouton dans ta barre de favoris</span>
			</p>
			<details style="margin-top:10px;">
				<summary style="cursor:pointer;color:#64748b;">Code (pour les curieux)</summary>
				<textarea readonly style="width:100%;font-family:monospace;font-size:11px;height:80px;margin-top:8px;"><?php echo esc_textarea( ann_bookmarklet_js() ); ?></textarea>
			</details>
		</div>

		<!-- IMAP -->
		<form method="post" action="<?php echo esc_url( $post ); ?>" class="ann-card" style="margin-top:24px;">
			<input type="hidden" name="action" value="ann_settings">
			<input type="hidden" name="_form_section" value="imap">
			<?php wp_nonce_field( 'ann_settings' ); ?>
			<h2 style="margin-top:0;">📥 Import auto via alertes mail (IMAP)</h2>
			<?php if ( ! ann_imap_available() ) : ?>
				<div class="notice notice-warning inline" style="margin:0 0 10px;"><p>⚠️ L'extension PHP <code>imap</code> n'est pas installee sur ce serveur. Cette section ne fonctionnera pas. Contacte ton hebergeur (Hostinger) pour l'activer.</p></div>
			<?php endif; ?>
			<p>
				<strong>Comment ca marche :</strong><br>
				1) Sur Leboncoin, Mariages.net, etc. : cree tes alertes de recherche (« cherche photographe mariage 44 ») et coche <em>« Recevoir les nouvelles annonces par mail »</em>.<br>
				2) Configure ici les acces IMAP de la boite qui recoit ces alertes (un Gmail dedie est ideal).<br>
				3) Le plugin lit la boite chaque heure, importe automatiquement les nouvelles annonces dans le CRM (sans le telephone — clique l'annonce sur le site pour le voir, puis complete dans le CRM).
			</p>
			<p style="color:#64748b;font-size:12px;">⚠️ Pour Gmail : active <em>l'acces IMAP</em> dans les parametres + cree un <strong>mot de passe d'application</strong> (myaccount.google.com/apppasswords) au lieu de ton mot de passe principal.</p>
			<table class="form-table"><tbody>
				<tr><th>Activer l'import auto</th><td>
					<label><input type="checkbox" name="imap_on" value="1" <?php checked( 1, (int) ( $s['imap_on'] ?? 0 ) ); ?> <?php disabled( ! ann_imap_available() ); ?>> Lire les alertes mail toutes les heures et importer les annonces</label>
				</td></tr>
				<tr><th>Serveur IMAP</th><td><input type="text" name="imap_host" value="<?php echo esc_attr( $s['imap_host'] ?? '' ); ?>" placeholder="imap.gmail.com" style="width:300px;"></td></tr>
				<tr><th>Port</th><td><input type="text" name="imap_port" value="<?php echo esc_attr( $s['imap_port'] ?? '993' ); ?>" placeholder="993" style="width:80px;"> <span style="color:#64748b;">(993 = SSL/TLS)</span></td></tr>
				<tr><th>Identifiant (email)</th><td><input type="text" name="imap_user" value="<?php echo esc_attr( $s['imap_user'] ?? '' ); ?>" placeholder="prospects@gmail.com" style="width:300px;"></td></tr>
				<tr><th>Mot de passe</th><td><input type="password" name="imap_pass" value="" autocomplete="new-password" placeholder="<?php echo ! empty( $s['imap_pass'] ) ? '••••••••' : 'mot de passe ou app password'; ?>" style="width:300px;"><br><small style="color:#64748b;">Laisse vide pour garder le mot de passe actuel.</small></td></tr>
				<tr><th>Dossier</th><td><input type="text" name="imap_folder" value="<?php echo esc_attr( $s['imap_folder'] ?? 'INBOX' ); ?>" placeholder="INBOX" style="width:200px;"></td></tr>
			</tbody></table>
			<p><button class="button button-primary">Enregistrer IMAP</button></p>
		</form>

		<div style="max-width:980px;display:flex;gap:8px;flex-wrap:wrap;">
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_test_imap">
				<?php wp_nonce_field( 'ann_test_imap' ); ?>
				<button class="button">🔌 Tester la connexion IMAP</button>
			</form>
			<form method="post" action="<?php echo esc_url( $post ); ?>">
				<input type="hidden" name="action" value="ann_imap_run">
				<?php wp_nonce_field( 'ann_imap_run' ); ?>
				<button class="button">📥 Lancer un import maintenant</button>
			</form>
		</div>
	</div>
	<?php
}

/* ===========================================================================
 * Dashboard widget WP
 * ========================================================================= */
add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	// Widget mis en haut a gauche pour qu'elle le voie tout de suite
	global $wp_meta_boxes;
	wp_add_dashboard_widget( 'ann_widget', '📸 Anna Photo — Prospection', 'ann_dashboard_widget' );
	if ( isset( $wp_meta_boxes['dashboard']['normal']['core'] ) ) {
		$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$mine = array( 'ann_widget' => $wp_meta_boxes['dashboard']['normal']['core']['ann_widget'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['ann_widget'] );
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $mine, $wp_meta_boxes['dashboard']['normal']['core'] );
	}
} );
function ann_dashboard_widget() {
	$c = ann_counters();
	$cp = ann_setting( 'cp', '' );
	?>
	<style>
	.ann-hero{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:18px;border-radius:10px;margin:0 -12px 14px;text-align:center;}
	.ann-hero h2{color:#fff!important;font-size:17px;margin:0 0 4px;padding:0;font-weight:600;}
	.ann-hero p{color:rgba(255,255,255,.85);font-size:13px;margin:0 0 12px;}
	.ann-hero .mega-btn{display:block;width:100%;padding:16px;font-size:16px;font-weight:700;background:#fff;color:#667eea!important;text-decoration:none;border-radius:8px;margin:0 0 10px;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:transform .15s;}
	.ann-hero .mega-btn:hover{background:#f0f0ff;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.2);}
	.ann-quick{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:8px;}
	.ann-quick a{background:rgba(255,255,255,.2);color:#fff!important;padding:6px 12px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:500;transition:background .15s;}
	.ann-quick a:hover{background:rgba(255,255,255,.35);}
	.ann-quick .lbl{color:rgba(255,255,255,.7);font-size:11px;width:100%;margin-top:6px;text-transform:uppercase;letter-spacing:.05em;}
	.ann-w-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:0 0 10px;}
	.ann-w-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;text-decoration:none;color:inherit;display:block;text-align:center;}
	.ann-w-card:hover{background:#eff6ff;border-color:#bfdbfe;}
	.ann-w-card .n{font-size:22px;font-weight:700;line-height:1;}
	.ann-w-card .l{color:#64748b;font-size:11px;text-transform:uppercase;}
	.ann-w-card.urg{background:#fef2f2;border-color:#fecaca;}
	.ann-w-card.urg .n{color:#dc2626;}
	</style>

	<div class="ann-hero">
		<h2>👋 Salut Anna !</h2>
		<p>Prête à trouver de nouveaux clients ?</p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-annonces' ) ); ?>" class="mega-btn">
			🎯 CHERCHER DES ANNONCES MAINTENANT
		</a>
		<div class="ann-quick">
			<span class="lbl">Direct sur Leboncoin :</span>
			<?php
			foreach ( array( 'mariage' => '💍 Mariage', 'famille' => '👨‍👩‍👧 Famille', 'grossesse' => '🤰 Grossesse', 'evenement' => '🎉 Évenement' ) as $pkey => $plabel ) {
				$kw  = ann_keywords( $pkey );
				$url = ann_platform_url( 'leboncoin', $kw[0], $cp );
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $plabel ) . '</a>';
			}
			?>
		</div>
	</div>

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
	<p style="margin:0;text-align:center;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-hub' ) ); ?>" class="button">📸 Centre de controle</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects' ) ); ?>" class="button">📋 Voir mes prospects</a>
	</p>
	<?php
}
