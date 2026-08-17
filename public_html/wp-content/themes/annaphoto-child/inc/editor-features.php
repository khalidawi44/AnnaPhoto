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
 * 1. Support editeur : force TOUS les controles via l'API classique
 *    (Bard est un theme classique, theme.json seul ne suffit pas)
 * ========================================================================= */
add_action( 'after_setup_theme', 'annaphoto_editor_setup', 20 );
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

	// Tailles de police custom (visible dans sidebar > Typographie > Taille)
	add_theme_support( 'editor-font-sizes', array(
		array( 'name' => 'Minuscule',  'shortName' => 'XS',   'size' => 11, 'slug' => 'tiny' ),
		array( 'name' => 'Petite',     'shortName' => 'S',    'size' => 13, 'slug' => 'small' ),
		array( 'name' => 'Normale',    'shortName' => 'M',    'size' => 17, 'slug' => 'normal' ),
		array( 'name' => 'Moyenne',    'shortName' => 'L',    'size' => 20, 'slug' => 'medium' ),
		array( 'name' => 'Grande',     'shortName' => 'XL',   'size' => 26, 'slug' => 'large' ),
		array( 'name' => 'Titre',      'shortName' => 'XXL',  'size' => 34, 'slug' => 'xlarge' ),
		array( 'name' => 'Immense',    'shortName' => 'XXXL', 'size' => 48, 'slug' => 'huge' ),
		array( 'name' => 'Enorme',     'shortName' => 'MAX',  'size' => 64, 'slug' => 'giant' ),
	) );

	// Palette de couleurs Anna Photo (visible dans sidebar > Couleur)
	add_theme_support( 'editor-color-palette', array(
		array( 'name' => 'Rose poudre', 'slug' => 'rose',      'color' => '#d4a5a5' ),
		array( 'name' => 'Mauve',       'slug' => 'mauve',     'color' => '#8b6f6f' ),
		array( 'name' => 'Aubergine',   'slug' => 'aubergine', 'color' => '#3d2e2e' ),
		array( 'name' => 'Creme',       'slug' => 'cream',     'color' => '#fdf5f2' ),
		array( 'name' => 'Rose pale',   'slug' => 'rose-pale', 'color' => '#f4e0dc' ),
		array( 'name' => 'Taupe',       'slug' => 'taupe',     'color' => '#a89592' ),
		array( 'name' => 'Sauge',       'slug' => 'sauge',     'color' => '#8e9976' ),
		array( 'name' => 'Noir',        'slug' => 'noir',      'color' => '#0f0f0f' ),
		array( 'name' => 'Blanc',       'slug' => 'blanc',     'color' => '#ffffff' ),
	) );

	// Degrades
	add_theme_support( 'editor-gradient-presets', array(
		array(
			'name'     => 'Degrade rose',
			'slug'     => 'rose-gradient',
			'gradient' => 'linear-gradient(135deg, #f4e0dc 0%, #d4a5a5 100%)',
		),
		array(
			'name'     => 'Degrade aubergine',
			'slug'     => 'aubergine-gradient',
			'gradient' => 'linear-gradient(135deg, #8b6f6f 0%, #3d2e2e 100%)',
		),
	) );
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

/* ===========================================================================
 * 5. PANNEAU SIDEBAR PERSISTANT dans l'editeur
 *    Injecte un panneau flottant en haut-a-droite avec de gros boutons
 *    cliquables. Anna selectionne un paragraphe, clique un bouton = style
 *    applique. Contournement direct pour ne pas dependre du sidebar
 *    natif Gutenberg qui peut cacher les controles.
 * ========================================================================= */
add_action( 'enqueue_block_editor_assets', 'annaphoto_editor_panel_assets' );
function annaphoto_editor_panel_assets() {
	$handle = 'annaphoto-editor-panel';
	wp_register_script( $handle, '', array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-plugins', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data' ), '1.0', true );
	wp_enqueue_script( $handle );
	wp_add_inline_script( $handle, annaphoto_editor_panel_js() );

	// CSS du panneau
	wp_register_style( $handle . '-css', false );
	wp_enqueue_style( $handle . '-css' );
	wp_add_inline_style( $handle . '-css', annaphoto_editor_panel_css() );
}

function annaphoto_editor_panel_js() {
	// Styles disponibles pour paragraphes (par ordre d'affichage)
	$styles = array(
		array( 'label' => '📝 Texte normal',      'action' => 'reset',      'target' => 'paragraph' ),
		array( 'label' => '✨ Chapo italique',    'action' => 'style',      'value' => 'annaphoto-lead',  'target' => 'paragraph' ),
		array( 'label' => '💬 Citation rose',     'action' => 'style',      'value' => 'annaphoto-quote', 'target' => 'paragraph' ),
		array( 'label' => 'A Lettrine mauve',     'action' => 'style',      'value' => 'annaphoto-drop',  'target' => 'paragraph' ),
		array( 'label' => '📌 Note discrete',     'action' => 'style',      'value' => 'annaphoto-note',  'target' => 'paragraph' ),
	);
	$sizes = array(
		array( 'label' => 'Petite',   'size' => 13 ),
		array( 'label' => 'Normale',  'size' => 17 ),
		array( 'label' => 'Grande',   'size' => 24 ),
		array( 'label' => 'Immense',  'size' => 36 ),
	);
	$colors = array(
		array( 'label' => 'Aubergine', 'color' => '#3d2e2e' ),
		array( 'label' => 'Mauve',     'color' => '#8b6f6f' ),
		array( 'label' => 'Rose',      'color' => '#d4a5a5' ),
		array( 'label' => 'Sauge',     'color' => '#8e9976' ),
		array( 'label' => 'Noir',      'color' => '#0f0f0f' ),
	);
	$data = wp_json_encode( array( 'styles' => $styles, 'sizes' => $sizes, 'colors' => $colors ) );

	return <<<JS
( function ( wp ) {
	var STYLES = {$data};

	function selectedBlock() {
		var sel = wp.data.select( 'core/block-editor' );
		var id  = sel.getSelectedBlockClientId();
		return id ? sel.getBlock( id ) : null;
	}

	function applyStyle( slug ) {
		var b = selectedBlock();
		if ( ! b ) return alert( 'Selectionne d\\'abord un paragraphe.' );
		var currentClass = ( b.attributes.className || '' ).split( ' ' ).filter( function ( c ) {
			return c && c.indexOf( 'is-style-annaphoto' ) !== 0;
		} );
		if ( slug ) currentClass.push( 'is-style-' + slug );
		wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( b.clientId, {
			className: currentClass.join( ' ' ).trim(),
		} );
	}

	function applyFontSize( size ) {
		var b = selectedBlock();
		if ( ! b ) return alert( 'Selectionne d\\'abord un paragraphe.' );
		wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( b.clientId, {
			fontSize: undefined,
			style: Object.assign( {}, b.attributes.style || {}, {
				typography: Object.assign( {}, ( b.attributes.style && b.attributes.style.typography ) || {}, {
					fontSize: size + 'px',
				} ),
			} ),
		} );
	}

	function applyColor( color ) {
		var b = selectedBlock();
		if ( ! b ) return alert( 'Selectionne d\\'abord un paragraphe.' );
		wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( b.clientId, {
			style: Object.assign( {}, b.attributes.style || {}, {
				color: Object.assign( {}, ( b.attributes.style && b.attributes.style.color ) || {}, {
					text: color,
				} ),
			} ),
		} );
	}

	function buildPanel() {
		if ( document.getElementById( 'ap-editor-panel' ) ) return;
		var panel = document.createElement( 'div' );
		panel.id = 'ap-editor-panel';
		panel.innerHTML =
			'<div class="ap-ep-head">🎨 Styles Anna Photo <button type="button" class="ap-ep-min">–</button></div>' +
			'<div class="ap-ep-body">' +
				'<div class="ap-ep-hint">Selectionne un paragraphe, puis clique :</div>' +
				'<div class="ap-ep-section">' +
					'<div class="ap-ep-label">Style</div>' +
					'<div class="ap-ep-btns" data-group="style">' +
						STYLES.styles.map( function ( s ) {
							var v = s.action === 'reset' ? '' : s.value;
							return '<button type="button" data-action="style" data-value="' + v + '">' + s.label + '</button>';
						} ).join( '' ) +
					'</div>' +
				'</div>' +
				'<div class="ap-ep-section">' +
					'<div class="ap-ep-label">Taille du texte</div>' +
					'<div class="ap-ep-btns" data-group="size">' +
						STYLES.sizes.map( function ( s ) {
							return '<button type="button" data-action="size" data-value="' + s.size + '" style="font-size:' + Math.min( 16, s.size / 2 + 6 ) + 'px">' + s.label + '</button>';
						} ).join( '' ) +
					'</div>' +
				'</div>' +
				'<div class="ap-ep-section">' +
					'<div class="ap-ep-label">Couleur du texte</div>' +
					'<div class="ap-ep-btns ap-ep-colors" data-group="color">' +
						STYLES.colors.map( function ( c ) {
							return '<button type="button" data-action="color" data-value="' + c.color + '" title="' + c.label + '" style="background:' + c.color + '"><span>' + c.label + '</span></button>';
						} ).join( '' ) +
					'</div>' +
				'</div>' +
			'</div>';
		document.body.appendChild( panel );

		panel.addEventListener( 'click', function ( e ) {
			var t = e.target.closest( 'button' );
			if ( ! t ) return;
			if ( t.classList.contains( 'ap-ep-min' ) ) {
				panel.classList.toggle( 'is-min' );
				return;
			}
			var a = t.getAttribute( 'data-action' );
			var v = t.getAttribute( 'data-value' );
			if ( a === 'style' )  applyStyle( v );
			if ( a === 'size' )   applyFontSize( parseInt( v, 10 ) );
			if ( a === 'color' )  applyColor( v );
		} );
	}

	wp.domReady( function () {
		// Attend que l'editeur soit pret
		var tries = 0;
		var iv = setInterval( function () {
			tries++;
			if ( document.querySelector( '.editor-styles-wrapper, .block-editor' ) || tries > 30 ) {
				clearInterval( iv );
				buildPanel();
			}
		}, 200 );
	} );
} )( window.wp );
JS;
}

function annaphoto_editor_panel_css() {
	return "
	#ap-editor-panel {
		position: fixed;
		top: 96px; right: 20px;
		width: 260px;
		background: #fff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		box-shadow: 0 12px 30px rgba(139,111,111,.18);
		z-index: 999999;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
		font-size: 13px;
	}
	#ap-editor-panel.is-min .ap-ep-body { display: none; }
	#ap-editor-panel.is-min .ap-ep-min::before { content: '+'; }
	#ap-editor-panel .ap-ep-head {
		background: linear-gradient(135deg, #d4a5a5 0%, #8b6f6f 100%);
		color: #fff; padding: 10px 14px;
		border-radius: 12px 12px 0 0;
		font-weight: 600; font-size: 14px;
		display: flex; justify-content: space-between; align-items: center;
		cursor: move;
	}
	#ap-editor-panel .ap-ep-min {
		background: rgba(255,255,255,.2); color: #fff;
		border: 0; width: 24px; height: 24px; border-radius: 50%;
		cursor: pointer; font-size: 16px; line-height: 1;
	}
	#ap-editor-panel .ap-ep-min:hover { background: rgba(255,255,255,.35); }
	#ap-editor-panel .ap-ep-body { padding: 14px; }
	#ap-editor-panel .ap-ep-hint {
		font-size: 11px; color: #64748b;
		margin: 0 0 12px; font-style: italic;
	}
	#ap-editor-panel .ap-ep-section { margin-bottom: 14px; }
	#ap-editor-panel .ap-ep-section:last-child { margin-bottom: 0; }
	#ap-editor-panel .ap-ep-label {
		font-size: 10px; text-transform: uppercase;
		letter-spacing: 0.08em; color: #8b6f6f;
		font-weight: 700; margin-bottom: 6px;
	}
	#ap-editor-panel .ap-ep-btns {
		display: grid; grid-template-columns: 1fr 1fr;
		gap: 6px;
	}
	#ap-editor-panel .ap-ep-btns button {
		background: #f8fafc; border: 1px solid #e2e8f0;
		padding: 8px 10px; border-radius: 8px;
		cursor: pointer; font-size: 12px; color: #0f172a;
		text-align: left; transition: all .15s;
		font-family: inherit;
	}
	#ap-editor-panel .ap-ep-btns button:hover {
		background: #fdf5f2; border-color: #d4a5a5;
		transform: translateY(-1px);
	}
	#ap-editor-panel .ap-ep-colors {
		grid-template-columns: repeat(5, 1fr);
	}
	#ap-editor-panel .ap-ep-colors button {
		padding: 0; height: 34px;
		border-radius: 6px; border: 2px solid rgba(0,0,0,.08);
		position: relative;
	}
	#ap-editor-panel .ap-ep-colors button span {
		position: absolute; inset: 0;
		display: flex; align-items: center; justify-content: center;
		font-size: 0; color: transparent;
	}
	#ap-editor-panel .ap-ep-colors button:hover {
		transform: scale(1.1);
	}
	";
}
