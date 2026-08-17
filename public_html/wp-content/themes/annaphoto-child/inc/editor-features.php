<?php
/**
 * Fonctionnalites de l'editeur Gutenberg pour Anna
 *
 * - Debloque les controles typographiques par bloc (via theme.json separement)
 * - Ajoute des STYLES DE BLOC prets a cliquer (ex: "Grande citation",
 *   "Note discrete", "Bloc rose", "Bloc aubergine")
 * - Charge un editor-style.css pour que le rendu en ecriture matche la
 *   version publiee
 * - Affiche une notice de bienvenue sur l'ecran d'edition d'article
 *   pour lui montrer ou trouver les nouveaux controles
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ===========================================================================
 * 1. Support editeur : styles + fonctionnalites
 * ========================================================================= */
add_action( 'after_setup_theme', 'annaphoto_editor_setup' );
function annaphoto_editor_setup() {
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-spacing' );
	add_theme_support( 'custom-units' );

	// Charge le CSS de l'editeur (rendu WYSIWYG)
	add_editor_style( 'assets/css/editor-style.css' );
}

/* ===========================================================================
 * 2. Styles de bloc prets a cliquer
 *    Dans l'editeur, Anna aura des variations de style pour ses blocs :
 *    - Paragraphes : "Citation grande", "Note discrete", "Lettrine forte"
 *    - Titres : "Sous-titre italique", "Titre section rose"
 *    - Images : "Cadre rose", "Sans marge"
 * ========================================================================= */
add_action( 'init', 'annaphoto_register_block_styles' );
function annaphoto_register_block_styles() {

	// --- Paragraphes ---
	register_block_style( 'core/paragraph', array(
		'name'  => 'annaphoto-lead',
		'label' => 'Chapo italique (grande accroche)',
	) );
	register_block_style( 'core/paragraph', array(
		'name'  => 'annaphoto-quote',
		'label' => 'Citation rose centree',
	) );
	register_block_style( 'core/paragraph', array(
		'name'  => 'annaphoto-note',
		'label' => 'Note discrete (petit + gris)',
	) );
	register_block_style( 'core/paragraph', array(
		'name'  => 'annaphoto-drop',
		'label' => 'Lettrine mauve',
	) );

	// --- Titres ---
	register_block_style( 'core/heading', array(
		'name'  => 'annaphoto-italic',
		'label' => 'Italique elegant',
	) );
	register_block_style( 'core/heading', array(
		'name'  => 'annaphoto-underline',
		'label' => 'Souligne rose',
	) );
	register_block_style( 'core/heading', array(
		'name'  => 'annaphoto-centered-line',
		'label' => 'Centre avec ligne',
	) );

	// --- Images ---
	register_block_style( 'core/image', array(
		'name'  => 'annaphoto-frame-rose',
		'label' => 'Cadre rose doux',
	) );
	register_block_style( 'core/image', array(
		'name'  => 'annaphoto-frame-shadow',
		'label' => 'Ombre elegante',
	) );

	// --- Citations ---
	register_block_style( 'core/quote', array(
		'name'  => 'annaphoto-quote-big',
		'label' => 'Citation grande italique',
	) );

	// --- Separateurs ---
	register_block_style( 'core/separator', array(
		'name'  => 'annaphoto-heart',
		'label' => 'Coeur rose au centre',
	) );
	register_block_style( 'core/separator', array(
		'name'  => 'annaphoto-dots',
		'label' => 'Trois points',
	) );

	// --- Groupes / conteneurs ---
	register_block_style( 'core/group', array(
		'name'  => 'annaphoto-panel-rose',
		'label' => 'Panneau rose (encadre)',
	) );
	register_block_style( 'core/group', array(
		'name'  => 'annaphoto-panel-aubergine',
		'label' => 'Panneau aubergine (encadre)',
	) );
}

/* ===========================================================================
 * 3. CSS des styles de bloc — cote FRONT (publie)
 *    Ces regles rendent visibles les styles ci-dessus sur le site publie.
 *    Sur l'editeur, editor-style.css fait la meme chose.
 * ========================================================================= */
add_action( 'wp_head', 'annaphoto_block_styles_css', 100 );
function annaphoto_block_styles_css() {
	?>
	<style id="annaphoto-block-styles">
	/* Paragraphes */
	.is-style-annaphoto-lead {
		font-family: Georgia, 'Cormorant Garamond', serif;
		font-style: italic;
		font-size: 1.5em;
		line-height: 1.5;
		color: #8b6f6f;
		margin: 1.6em 0;
	}
	.is-style-annaphoto-quote {
		text-align: center;
		font-family: Georgia, serif;
		font-style: italic;
		font-size: 1.25em;
		line-height: 1.6;
		color: #d4a5a5;
		padding: 1em 2em;
		border-top: 1px solid rgba(212,165,165,.3);
		border-bottom: 1px solid rgba(212,165,165,.3);
		margin: 2em auto;
		max-width: 620px;
	}
	.is-style-annaphoto-note {
		font-size: 0.85em;
		color: #a89592;
		font-style: italic;
		padding-left: 1em;
		border-left: 3px solid #d4a5a5;
	}
	.is-style-annaphoto-drop::first-letter {
		font-family: Georgia, 'Cormorant Garamond', serif;
		font-style: italic;
		font-size: 4em;
		float: left;
		line-height: 0.9;
		padding: 4px 12px 0 0;
		color: #8b6f6f;
	}

	/* Titres */
	.is-style-annaphoto-italic {
		font-family: Georgia, 'Cormorant Garamond', serif;
		font-style: italic;
		font-weight: 400;
		color: #3d2e2e;
	}
	.is-style-annaphoto-underline {
		display: inline-block;
		border-bottom: 2px solid #d4a5a5;
		padding-bottom: 4px;
	}
	.is-style-annaphoto-centered-line {
		text-align: center;
		position: relative;
	}
	.is-style-annaphoto-centered-line::after {
		content: "";
		display: block;
		width: 32px;
		height: 1px;
		background: #d4a5a5;
		margin: 12px auto 0;
	}

	/* Images */
	.is-style-annaphoto-frame-rose img {
		border: 8px solid #fdf5f2;
		box-shadow: 0 0 0 1px #d4a5a5;
		border-radius: 4px;
	}
	.is-style-annaphoto-frame-shadow img {
		box-shadow: 0 20px 40px -20px rgba(139,111,111,.4);
		border-radius: 4px;
	}

	/* Citations */
	.wp-block-quote.is-style-annaphoto-quote-big {
		font-family: Georgia, 'Cormorant Garamond', serif;
		font-style: italic;
		font-size: 1.8em;
		line-height: 1.4;
		color: #8b6f6f;
		border-left: 4px solid #d4a5a5;
		padding-left: 1em;
		margin: 2em 0;
	}

	/* Separateurs */
	.wp-block-separator.is-style-annaphoto-heart {
		border: 0;
		text-align: center;
		height: auto;
		margin: 2em 0;
	}
	.wp-block-separator.is-style-annaphoto-heart::after {
		content: "♥";
		color: #d4a5a5;
		font-size: 1.3em;
	}
	.wp-block-separator.is-style-annaphoto-dots {
		border: 0;
		text-align: center;
		height: auto;
		margin: 2em 0;
	}
	.wp-block-separator.is-style-annaphoto-dots::after {
		content: "• • •";
		color: #d4a5a5;
		letter-spacing: 0.6em;
	}

	/* Groupes / panneaux */
	.wp-block-group.is-style-annaphoto-panel-rose {
		background: #fdf5f2;
		border: 1px solid rgba(212,165,165,.3);
		border-radius: 12px;
		padding: 24px 28px;
		margin: 24px 0;
	}
	.wp-block-group.is-style-annaphoto-panel-aubergine {
		background: #3d2e2e;
		color: #fdf5f2;
		border-radius: 12px;
		padding: 24px 28px;
		margin: 24px 0;
	}
	.wp-block-group.is-style-annaphoto-panel-aubergine * { color: inherit; }
	</style>
	<?php
}

/* ===========================================================================
 * 4. Guide dans l'editeur : notice de bienvenue explicative
 *    S'affiche en haut de l'editeur d'article, expliquant a Anna
 *    ou trouver les nouveaux controles typo. Dismissible.
 * ========================================================================= */
add_action( 'admin_notices', 'annaphoto_editor_help_notice' );
function annaphoto_editor_help_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) { return; }
	// Only on editor screen
	if ( 'post' !== $screen->base ) { return; }

	// Dismiss handling via option
	$dismissed = (bool) get_user_meta( get_current_user_id(), 'annaphoto_editor_help_dismissed', true );
	if ( $dismissed ) { return; }
	?>
	<div class="notice notice-info is-dismissible" id="annaphoto-editor-help">
		<p style="font-size:15px;">
			✨ <strong>Nouveaux outils dans ton editeur, Anna !</strong>
			Pour chaque bloc que tu ecris, tu peux maintenant regler dans le
			panneau de droite → <em>Typographie</em> :
			<strong>taille de police</strong>, <strong>interligne</strong>,
			<strong>espacement des lettres</strong>, <strong>famille de police</strong>,
			<strong>couleur</strong>.
		</p>
		<p style="font-size:14px; color:#64748b;">
			Tu peux aussi transformer un bloc en <em>chapo italique</em>,
			<em>citation rose</em>, <em>lettrine</em>, <em>panneau encadre</em>...
			Regarde dans <strong>Styles</strong> a cote de la Typographie.
		</p>
	</div>
	<script>
	document.addEventListener('click', function (e) {
		if (e.target && e.target.closest && e.target.closest('#annaphoto-editor-help .notice-dismiss')) {
			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=annaphoto_dismiss_editor_help&_wpnonce=<?php echo esc_js( wp_create_nonce( 'annaphoto_dismiss_editor_help' ) ); ?>'
			});
		}
	});
	</script>
	<?php
}

add_action( 'wp_ajax_annaphoto_dismiss_editor_help', 'annaphoto_ajax_dismiss_editor_help' );
function annaphoto_ajax_dismiss_editor_help() {
	if ( ! check_ajax_referer( 'annaphoto_dismiss_editor_help', '_wpnonce', false ) ) { wp_die(); }
	update_user_meta( get_current_user_id(), 'annaphoto_editor_help_dismissed', 1 );
	wp_die();
}
