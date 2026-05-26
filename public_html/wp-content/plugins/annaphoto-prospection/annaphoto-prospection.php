<?php
/**
 * Plugin Name: Anna Photo — Prospection
 * Description: Mini-CRM tout simple pour trouver et suivre des clients : ajout manuel des prospects (telephone + lien de l'annonce), filtres, messages WhatsApp/SMS personnalises et notifications Telegram. Pense pour debuter sans connaissances techniques.
 * Version: 1.0.0
 * Author: Anna Photo
 * Text Domain: annaphoto-prospection
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ANN_PROSP_OPT', 'annaphoto_prospects' );
define( 'ANN_SET_OPT', 'annaphoto_prospection_settings' );

/* ---------------------------------------------------------------------------
 * Donnees
 * ------------------------------------------------------------------------- */
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

function ann_statuses() {
	return array(
		'nouveau'   => 'Nouveau',
		'contacte'  => 'Contacte',
		'relance'   => 'Relance',
		'sans_rep'  => 'Sans reponse',
		'interesse' => 'Interesse',
		'client'    => 'Client',
		'stop'      => 'Ne plus contacter',
	);
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
		'autre'     => 'Autre',
	);
}

/* Telephone : format international pour WhatsApp (wa.me) */
function ann_phone_intl( $phone ) {
	$d = preg_replace( '/[^0-9]/', '', (string) $phone );
	if ( '' === $d ) { return ''; }
	if ( strpos( $d, '33' ) === 0 && strlen( $d ) >= 11 ) { return $d; }
	if ( strpos( $d, '0' ) === 0 ) { return '33' . substr( $d, 1 ); }
	return $d;
}
/* Telephone : format local pour les liens SMS */
function ann_phone_local( $phone ) {
	return preg_replace( '/[^0-9+]/', '', (string) $phone );
}

/* ---------------------------------------------------------------------------
 * Messages personnalises (par type de prestation)
 * ------------------------------------------------------------------------- */
function ann_tpl_data() {
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
function ann_build_message( $prestation, $prenom, $ville, $variation = 0 ) {
	$tpl = ann_tpl_data();
	$key = isset( $tpl[ $prestation ] ) ? $prestation : 'autre';
	$set = $tpl[ $key ];
	$idx = ( (int) $variation ) % count( $set );
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

/* ---------------------------------------------------------------------------
 * Notifications Telegram (optionnel)
 * ------------------------------------------------------------------------- */
function ann_tg_push( $text ) {
	$token = ann_setting( 'tg_token' );
	$chat  = ann_setting( 'tg_chat' );
	if ( '' === $token || '' === $chat ) { return false; }
	wp_remote_post(
		'https://api.telegram.org/bot' . $token . '/sendMessage',
		array(
			'timeout'  => 10,
			'blocking' => false,
			'body'     => array( 'chat_id' => $chat, 'text' => $text ),
		)
	);
	return true;
}

/* ---------------------------------------------------------------------------
 * Menu admin
 * ------------------------------------------------------------------------- */
add_action( 'admin_menu', function () {
	add_menu_page( 'Prospection', 'Prospection', 'manage_options', 'ann-prospection', 'ann_render_page', 'dashicons-camera-alt', 26 );
} );

function ann_redirect( $args = array() ) {
	$url = add_query_arg( array_merge( array( 'page' => 'ann-prospection' ), $args ), admin_url( 'admin.php' ) );
	wp_safe_redirect( $url );
	exit;
}

/* ---------------------------------------------------------------------------
 * Actions (formulaires)
 * ------------------------------------------------------------------------- */
add_action( 'admin_post_ann_add', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_add' );

	$phone = sanitize_text_field( wp_unslash( isset( $_POST['phone'] ) ? $_POST['phone'] : '' ) );
	$intl  = ann_phone_intl( $phone );
	if ( '' === $intl ) { ann_redirect( array( 'msg' => 'phone' ) ); }

	$list = ann_get_prospects();
	foreach ( $list as $p ) {
		if ( ann_phone_intl( isset( $p['phone'] ) ? $p['phone'] : '' ) === $intl ) {
			ann_redirect( array( 'msg' => 'dup' ) );
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
	ann_tg_push( 'Nouveau prospect : ' . ( '' !== $prenom ? $prenom : $phone ) . ' — ' . $plabel );

	ann_redirect( array( 'msg' => 'added' ) );
} );

add_action( 'admin_post_ann_update', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_update' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = ann_get_prospects();
	foreach ( $list as &$p ) {
		if ( isset( $p['id'] ) && $p['id'] === $id ) {
			if ( isset( $_POST['status'] ) ) { $p['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) ); }
			if ( isset( $_POST['note'] ) ) { $p['note'] = sanitize_textarea_field( wp_unslash( $_POST['note'] ) ); }
			if ( isset( $_POST['message'] ) ) { $p['message'] = sanitize_textarea_field( wp_unslash( $_POST['message'] ) ); }
			break;
		}
	}
	unset( $p );
	ann_save_prospects( $list );
	ann_redirect( array( 'msg' => 'updated' ) );
} );

add_action( 'admin_post_ann_delete', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_delete' );
	$id   = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : '' ) );
	$list = array_filter( ann_get_prospects(), function ( $p ) use ( $id ) {
		return ! ( isset( $p['id'] ) && $p['id'] === $id );
	} );
	ann_save_prospects( $list );
	ann_redirect( array( 'msg' => 'deleted' ) );
} );

add_action( 'admin_post_ann_settings', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_settings' );
	update_option( ANN_SET_OPT, array(
		'ville'    => sanitize_text_field( wp_unslash( isset( $_POST['ville'] ) ? $_POST['ville'] : '' ) ),
		'tg_token' => sanitize_text_field( wp_unslash( isset( $_POST['tg_token'] ) ? $_POST['tg_token'] : '' ) ),
		'tg_chat'  => sanitize_text_field( wp_unslash( isset( $_POST['tg_chat'] ) ? $_POST['tg_chat'] : '' ) ),
	) );
	ann_redirect( array( 'tab' => 'reglages', 'msg' => 'saved' ) );
} );

add_action( 'admin_post_ann_test_tg', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_test_tg' );
	$ok = ann_tg_push( 'Test Anna Photo : Telegram fonctionne !' );
	ann_redirect( array( 'tab' => 'reglages', 'msg' => $ok ? 'tg_ok' : 'tg_ko' ) );
} );

/* ---------------------------------------------------------------------------
 * Rendu de la page
 * ------------------------------------------------------------------------- */
function ann_notice( $msg ) {
	$map = array(
		'added'   => array( 'success', 'Prospect ajoute. Tu peux maintenant cliquer sur WhatsApp ou SMS pour envoyer le message.' ),
		'updated' => array( 'success', 'Modifications enregistrees.' ),
		'deleted' => array( 'success', 'Prospect supprime.' ),
		'saved'   => array( 'success', 'Reglages enregistres.' ),
		'tg_ok'   => array( 'success', 'Message de test Telegram envoye. Verifie ton telephone.' ),
		'tg_ko'   => array( 'error', 'Telegram non configure : remplis le Token et le Chat ID dans Reglages.' ),
		'phone'   => array( 'error', 'Numero de telephone invalide. Exemple : 06 12 34 56 78' ),
		'dup'     => array( 'error', 'Ce numero est deja dans ta liste (anti-doublon).' ),
	);
	if ( ! isset( $map[ $msg ] ) ) { return; }
	$type = 'error' === $map[ $msg ][0] ? 'notice-error' : 'notice-success';
	echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $map[ $msg ][1] ) . '</p></div>';
}

function ann_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$tab        = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'prospects';
	$statuses   = ann_statuses();
	$prestations = ann_prestations();
	$sources    = ann_sources();
	$base_url   = admin_url( 'admin.php?page=ann-prospection' );
	?>
	<div class="wrap">
		<h1 style="display:flex;align-items:center;gap:8px;">📸 Prospection — Anna Photo</h1>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab <?php echo 'reglages' !== $tab ? 'nav-tab-active' : ''; ?>">Mes prospects</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'reglages', $base_url ) ); ?>" class="nav-tab <?php echo 'reglages' === $tab ? 'nav-tab-active' : ''; ?>">Reglages</a>
		</h2>

		<?php if ( isset( $_GET['msg'] ) ) { ann_notice( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ); } ?>

		<?php if ( 'reglages' === $tab ) { ann_render_settings(); } else { ann_render_prospects( $statuses, $prestations, $sources, $base_url ); } ?>
	</div>

	<style>
		.ann-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px;margin:16px 0;max-width:980px;}
		.ann-help{background:#f0f6fc;border:1px solid #c5d9ed;border-radius:10px;padding:14px 18px;margin:12px 0;max-width:980px;}
		.ann-legal{background:#fcf9e8;border:1px solid #e6dca0;border-radius:10px;padding:12px 18px;margin:12px 0;max-width:980px;font-size:13px;}
		.ann-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
		.ann-grid label{display:block;font-weight:600;margin-bottom:4px;}
		.ann-grid input[type=text],.ann-grid select,.ann-grid textarea{width:100%;}
		.ann-full{grid-column:1/3;}
		.ann-count{display:inline-block;background:#f6f7f7;border:1px solid #dcdcde;border-radius:20px;padding:4px 12px;margin:0 6px 6px 0;font-size:13px;}
		.ann-table{width:100%;border-collapse:collapse;background:#fff;}
		.ann-table th,.ann-table td{border-bottom:1px solid #eee;padding:8px;text-align:left;vertical-align:top;font-size:13px;}
		.ann-btn-wa{display:inline-block;background:#25D366;color:#fff!important;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;margin:2px 0;}
		.ann-btn-sms{display:inline-block;background:#0a66c2;color:#fff!important;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;margin:2px 0;}
		.ann-pill{display:inline-block;padding:2px 8px;border-radius:12px;font-size:12px;background:#eef;}
	</style>
	<?php
}

function ann_render_settings() {
	?>
	<div class="ann-help">
		<strong>A quoi sert cette page ?</strong> Mets ta ville (pour les messages) et, si tu veux recevoir une alerte sur ton telephone a chaque nouveau prospect, connecte Telegram. Tout est optionnel sauf la ville.
	</div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ann-card">
		<input type="hidden" name="action" value="ann_settings">
		<?php wp_nonce_field( 'ann_settings' ); ?>
		<p>
			<label><strong>Ta ville / region</strong> (apparait dans les messages)</label><br>
			<input type="text" name="ville" value="<?php echo esc_attr( ann_setting( 'ville' ) ); ?>" placeholder="Ex : Nantes" style="width:320px;">
		</p>
		<hr>
		<p style="font-weight:600;">Alertes Telegram (optionnel)</p>
		<p class="description">1) Sur Telegram, ecris a <code>@BotFather</code> pour creer un bot et obtenir un <strong>Token</strong>. 2) Demarre une conversation avec ton bot, puis recupere ton <strong>Chat ID</strong> (via <code>@userinfobot</code>).</p>
		<p>
			<label>Token du bot</label><br>
			<input type="text" name="tg_token" value="<?php echo esc_attr( ann_setting( 'tg_token' ) ); ?>" style="width:420px;" placeholder="123456:ABC-...">
		</p>
		<p>
			<label>Chat ID</label><br>
			<input type="text" name="tg_chat" value="<?php echo esc_attr( ann_setting( 'tg_chat' ) ); ?>" style="width:240px;" placeholder="123456789">
		</p>
		<p><button type="submit" class="button button-primary">Enregistrer</button></p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:980px;">
		<input type="hidden" name="action" value="ann_test_tg">
		<?php wp_nonce_field( 'ann_test_tg' ); ?>
		<button type="submit" class="button">Envoyer un message de test Telegram</button>
	</form>
	<?php
}

function ann_render_prospects( $statuses, $prestations, $sources, $base_url ) {
	$list  = ann_get_prospects();

	$counts = array_fill_keys( array_keys( $statuses ), 0 );
	foreach ( $list as $p ) {
		$st = isset( $p['status'] ) ? $p['status'] : 'nouveau';
		if ( isset( $counts[ $st ] ) ) { $counts[ $st ]++; }
	}

	$f_status     = isset( $_GET['f_status'] ) ? sanitize_text_field( wp_unslash( $_GET['f_status'] ) ) : '';
	$f_source     = isset( $_GET['f_source'] ) ? sanitize_text_field( wp_unslash( $_GET['f_source'] ) ) : '';
	$f_prestation = isset( $_GET['f_prestation'] ) ? sanitize_text_field( wp_unslash( $_GET['f_prestation'] ) ) : '';
	$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

	$filtered = array_filter( $list, function ( $p ) use ( $f_status, $f_source, $f_prestation, $search ) {
		if ( '' !== $f_status && ( ! isset( $p['status'] ) || $p['status'] !== $f_status ) ) { return false; }
		if ( '' !== $f_source && ( ! isset( $p['source'] ) || $p['source'] !== $f_source ) ) { return false; }
		if ( '' !== $f_prestation && ( ! isset( $p['prestation'] ) || $p['prestation'] !== $f_prestation ) ) { return false; }
		if ( '' !== $search ) {
			$hay = strtolower( ( isset( $p['prenom'] ) ? $p['prenom'] : '' ) . ' ' . ( isset( $p['phone'] ) ? $p['phone'] : '' ) . ' ' . ( isset( $p['note'] ) ? $p['note'] : '' ) );
			if ( strpos( $hay, strtolower( $search ) ) === false ) { return false; }
		}
		return true;
	} );

	$ville_def = ann_setting( 'ville', 'votre region' );
	?>
	<div class="ann-help">
		<strong>Comment ca marche, en 3 etapes :</strong>
		<ol style="margin:6px 0 0 18px;">
			<li>Tu trouves une annonce (Leboncoin, Facebook, Insta, forum...).</li>
			<li>Tu ajoutes le prospect ci-dessous : colle son <strong>numero</strong> et le <strong>lien de l'annonce</strong>. Le message se remplit tout seul (tu peux le modifier).</li>
			<li>Tu cliques sur <span class="ann-btn-wa">WhatsApp</span> ou <span class="ann-btn-sms">SMS</span> : ton appli s'ouvre avec le message deja ecrit. Tu n'as plus qu'a envoyer.</li>
		</ol>
	</div>

	<div class="ann-legal">
		⚖️ <strong>A respecter :</strong> contacte uniquement des personnes qui ont <strong>publie une annonce</strong> (elles cherchent un service). Pas d'envoi automatique en masse. Si quelqu'un dit non, mets son statut sur <em>Ne plus contacter</em> : il n'apparaitra plus comme a relancer.
	</div>

	<div style="margin:10px 0;">
		<?php foreach ( $statuses as $k => $label ) : ?>
			<span class="ann-count"><strong><?php echo (int) $counts[ $k ]; ?></strong> <?php echo esc_html( $label ); ?></span>
		<?php endforeach; ?>
	</div>

	<!-- Formulaire d'ajout -->
	<div class="ann-card">
		<h2 style="margin-top:0;">➕ Ajouter un prospect</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ann_add">
			<?php wp_nonce_field( 'ann_add' ); ?>
			<div class="ann-grid">
				<div>
					<label>Prenom (si tu le connais)</label>
					<input type="text" name="prenom" id="ann_prenom" placeholder="Ex : Julie">
				</div>
				<div>
					<label>Telephone *</label>
					<input type="text" name="phone" required placeholder="06 12 34 56 78">
				</div>
				<div>
					<label>Type de prestation</label>
					<select name="prestation" id="ann_prestation">
						<?php foreach ( $prestations as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label>Ou as-tu trouve l'annonce ?</label>
					<select name="source">
						<?php foreach ( $sources as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="ann-full">
					<label>Lien de l'annonce</label>
					<input type="text" name="link" placeholder="Colle ici l'adresse de l'annonce (https://...)">
				</div>
				<div>
					<label>Ville du prospect</label>
					<input type="text" name="ville" id="ann_ville" value="<?php echo esc_attr( $ville_def ); ?>">
				</div>
				<div>
					<label>Note perso (facultatif)</label>
					<input type="text" name="note" placeholder="Ex : mariage en juin, budget serre">
				</div>
				<div class="ann-full">
					<label>Message a envoyer (modifiable)</label>
					<textarea name="message" id="ann_message" rows="4"></textarea>
					<button type="button" class="button" onclick="annRegen()" style="margin-top:6px;">🔄 Regenerer le message</button>
				</div>
			</div>
			<p style="margin-top:14px;"><button type="submit" class="button button-primary button-hero">Ajouter le prospect</button></p>
		</form>
	</div>

	<!-- Filtres -->
	<form method="get" class="ann-card" style="padding:12px 18px;">
		<input type="hidden" name="page" value="ann-prospection">
		<strong>Filtrer :</strong>
		<select name="f_status">
			<option value="">Tous les statuts</option>
			<?php foreach ( $statuses as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_status, $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="f_prestation">
			<option value="">Toutes prestations</option>
			<?php foreach ( $prestations as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_prestation, $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="f_source">
			<option value="">Toutes sources</option>
			<?php foreach ( $sources as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_source, $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Rechercher (nom, tel, note)">
		<button type="submit" class="button">Filtrer</button>
		<a href="<?php echo esc_url( $base_url ); ?>" class="button">Reinitialiser</a>
	</form>

	<!-- Liste -->
	<table class="ann-table widefat">
		<thead>
			<tr>
				<th>Prospect</th>
				<th>Prestation / Source</th>
				<th>Statut</th>
				<th>Contacter</th>
				<th>Date</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $filtered ) ) : ?>
				<tr><td colspan="6" style="text-align:center;padding:24px;color:#666;">Aucun prospect pour le moment. Ajoute-en un avec le formulaire ci-dessus 👆</td></tr>
			<?php endif; ?>
			<?php foreach ( $filtered as $p ) :
				$pid    = isset( $p['id'] ) ? $p['id'] : '';
				$prenom = isset( $p['prenom'] ) ? $p['prenom'] : '';
				$phone  = isset( $p['phone'] ) ? $p['phone'] : '';
				$prest  = isset( $p['prestation'] ) ? $p['prestation'] : 'autre';
				$src    = isset( $p['source'] ) ? $p['source'] : 'autre';
				$status = isset( $p['status'] ) ? $p['status'] : 'nouveau';
				$link   = isset( $p['link'] ) ? $p['link'] : '';
				$note   = isset( $p['note'] ) ? $p['note'] : '';
				$msg    = isset( $p['message'] ) ? $p['message'] : '';
				$wa     = ann_wa_link( $p );
				$sms    = ann_sms_link( $p );
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( '' !== $prenom ? $prenom : '(sans nom)' ); ?></strong><br>
						<?php echo esc_html( $phone ); ?>
						<?php if ( '' !== $link ) : ?><br><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Voir l'annonce ↗</a><?php endif; ?>
						<?php if ( '' !== $note ) : ?><br><em style="color:#666;"><?php echo esc_html( $note ); ?></em><?php endif; ?>
					</td>
					<td>
						<span class="ann-pill"><?php echo esc_html( isset( $prestations[ $prest ] ) ? $prestations[ $prest ] : $prest ); ?></span><br>
						<small><?php echo esc_html( isset( $sources[ $src ] ) ? $sources[ $src ] : $src ); ?></small>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="ann_update">
							<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
							<?php wp_nonce_field( 'ann_update' ); ?>
							<select name="status" onchange="this.form.submit()">
								<?php foreach ( $statuses as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $status, $k ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</form>
					</td>
					<td>
						<?php if ( '' !== $wa ) : ?><a class="ann-btn-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a><br><?php endif; ?>
						<?php if ( '' !== $sms ) : ?><a class="ann-btn-sms" href="<?php echo esc_url( $sms ); ?>">SMS</a><?php endif; ?>
						<details style="margin-top:6px;">
							<summary style="cursor:pointer;">✏️ Voir / modifier le message</summary>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:6px;">
								<input type="hidden" name="action" value="ann_update">
								<input type="hidden" name="id" value="<?php echo esc_attr( $pid ); ?>">
								<?php wp_nonce_field( 'ann_update' ); ?>
								<textarea name="message" rows="4" style="width:320px;"><?php echo esc_textarea( $msg ); ?></textarea><br>
								<input type="text" name="note" value="<?php echo esc_attr( $note ); ?>" placeholder="Note" style="width:320px;margin-top:4px;"><br>
								<button type="submit" class="button button-small" style="margin-top:4px;">Enregistrer</button>
							</form>
						</details>
					</td>
					<td><small><?php echo esc_html( isset( $p['created'] ) ? $p['created'] : '' ); ?></small></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Supprimer ce prospect ?');">
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

	<script>
		var ANN_TPL = <?php echo wp_json_encode( ann_tpl_data() ); ?>;
		var ANN_VAR = 0;
		function annFill() {
			var prest = document.getElementById('ann_prestation').value;
			var prenom = document.getElementById('ann_prenom').value.trim();
			var ville = document.getElementById('ann_ville').value.trim() || 'votre region';
			var set = ANN_TPL[prest] || ANN_TPL['autre'];
			var msg = set[ANN_VAR % set.length];
			msg = msg.split('{prenom}').join(prenom).split('{ville}').join(ville);
			msg = msg.replace(/\s{2,}/g, ' ').trim();
			document.getElementById('ann_message').value = msg;
		}
		function annRegen() { ANN_VAR++; annFill(); }
		document.addEventListener('DOMContentLoaded', function () {
			var ids = ['ann_prestation', 'ann_prenom', 'ann_ville'];
			ids.forEach(function (id) {
				var el = document.getElementById(id);
				if (el) { el.addEventListener('change', function () { ANN_VAR = 0; annFill(); }); el.addEventListener('keyup', function () { annFill(); }); }
			});
			annFill();
		});
	</script>
	<?php
}
