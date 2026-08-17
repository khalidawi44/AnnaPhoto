<?php
/**
 * Editeur visuel du style des articles
 *
 * Ajoute une page admin "🎨 Style articles" ou Anna choisit :
 * - Un layout parmi 4 (Editorial, Epure, Diptyque, Immersif)
 * - Une palette de couleurs (presets ou custom)
 * - Une paire typographique (4 combinaisons curées)
 * - Des options fines (lettrine, largeur colonne, alignement titre)
 *
 * Les reglages sont appliques automatiquement a TOUS les articles
 * via une classe sur <body> + variables CSS inline + enqueue de
 * la Google Font choisie.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const ANN_ART_OPT = 'annaphoto_article_style';

/* ===========================================================================
 * Referentiels
 * ========================================================================= */
function ann_art_layouts() {
	return array(
		'editorial' => array(
			'label'  => 'Editorial couverture',
			'desc'   => 'Photo pleine largeur, titre en italique dessous, texte 2 colonnes avec lettrine',
			'emoji'  => '📰',
		),
		'epure' => array(
			'label'  => 'Epure centrale',
			'desc'   => 'Colonne etroite centree, petite photo legendee, beaucoup de blanc',
			'emoji'  => '🕊️',
		),
		'diptyque' => array(
			'label'  => 'Diptyque asymetrique',
			'desc'   => 'Photo a gauche edge-to-edge + panneau texte a droite',
			'emoji'  => '🎭',
		),
		'immersif' => array(
			'label'  => 'Immersif',
			'desc'   => 'Photo remplit l\'ecran, titre en overlay blanc, texte au scroll',
			'emoji'  => '🎬',
		),
	);
}

function ann_art_palettes() {
	return array(
		'rose' => array(
			'label' => 'Rose poudre',
			'bg'    => '#fdf5f2',
			'text'  => '#3d2e2e',
			'meta'  => '#a89592',
			'accent'=> '#d4a5a5',
			'accent_strong' => '#8b6f6f',
			'line'  => 'rgba(139, 111, 111, 0.14)',
		),
		'blanc' => array(
			'label' => 'Blanc epure',
			'bg'    => '#ffffff',
			'text'  => '#1a1a1a',
			'meta'  => '#7a7a7a',
			'accent'=> '#2a2a2a',
			'accent_strong' => '#000000',
			'line'  => 'rgba(0, 0, 0, 0.1)',
		),
		'aubergine' => array(
			'label' => 'Aubergine profond',
			'bg'    => '#2a1e22',
			'text'  => '#f4e8e6',
			'meta'  => '#b8a19f',
			'accent'=> '#d4a5a5',
			'accent_strong' => '#f4e0dc',
			'line'  => 'rgba(212, 165, 165, 0.18)',
		),
		'sauge' => array(
			'label' => 'Sauge naturel',
			'bg'    => '#f5f2ec',
			'text'  => '#2a2f26',
			'meta'  => '#8a9080',
			'accent'=> '#8e9976',
			'accent_strong' => '#5a6448',
			'line'  => 'rgba(90, 100, 72, 0.15)',
		),
	);
}

/**
 * Presets complets : combinaisons pretes a l'emploi (layout + palette +
 * fonts + options). Un clic dans l'UI applique TOUTES ces valeurs d'un
 * coup et enregistre le style. Anna change de "look" en 1 clic.
 */
function ann_art_presets() {
	return array(
		'magazine_rose' => array(
			'label' => 'Magazine Rose',
			'tag'   => 'prestige · magazine',
			'desc'  => 'Photo pleine largeur, titre italique elegant, texte en 2 colonnes avec lettrine.',
			'settings' => array(
				'layout' => 'editorial', 'palette' => 'rose', 'fontpair' => 'playfair_lora',
				'title_align' => 'center', 'column_width' => 'medium',
				'dropcap' => 1, 'sticky_image' => 0,
			),
		),
		'journal_intime' => array(
			'label' => 'Journal Intime',
			'tag'   => 'confidence · douceur',
			'desc'  => 'Colonne etroite centree, petite photo, beaucoup de blanc. Une lettre a une amie.',
			'settings' => array(
				'layout' => 'epure', 'palette' => 'rose', 'fontpair' => 'cormorant_inter',
				'title_align' => 'center', 'column_width' => 'narrow',
				'dropcap' => 0, 'sticky_image' => 0,
			),
		),
		'portfolio_contemporain' => array(
			'label' => 'Portfolio Contemporain',
			'tag'   => 'moderne · design',
			'desc'  => 'Photo a gauche edge-to-edge, texte a droite. Feel galerie d\'art moderne.',
			'settings' => array(
				'layout' => 'diptyque', 'palette' => 'aubergine', 'fontpair' => 'dmserif_dmsans',
				'title_align' => 'left', 'column_width' => 'medium',
				'dropcap' => 0, 'sticky_image' => 1,
			),
		),
		'nature_sauvage' => array(
			'label' => 'Nature Sauvage',
			'tag'   => 'immersif · vivant',
			'desc'  => 'Photo plein ecran, titre blanc en overlay, lead italique. Cinema, nature.',
			'settings' => array(
				'layout' => 'immersif', 'palette' => 'sauge', 'fontpair' => 'fraunces_karla',
				'title_align' => 'center', 'column_width' => 'medium',
				'dropcap' => 0, 'sticky_image' => 0,
			),
		),
		'noir_blanc_pur' => array(
			'label' => 'Noir & Blanc Pur',
			'tag'   => 'minimal · photographique',
			'desc'  => 'Minimalisme photographique absolu. Colonne etroite, blanc, serif classique.',
			'settings' => array(
				'layout' => 'epure', 'palette' => 'blanc', 'fontpair' => 'cormorant_inter',
				'title_align' => 'center', 'column_width' => 'narrow',
				'dropcap' => 1, 'sticky_image' => 0,
			),
		),
		'editorial_moderne' => array(
			'label' => 'Editorial Moderne',
			'tag'   => 'contemporain · fort',
			'desc'  => 'Titre puissant en DM Serif, couleurs aubergine, layout couverture avec lettrine.',
			'settings' => array(
				'layout' => 'editorial', 'palette' => 'aubergine', 'fontpair' => 'dmserif_dmsans',
				'title_align' => 'left', 'column_width' => 'wide',
				'dropcap' => 1, 'sticky_image' => 0,
			),
		),
		'romantique' => array(
			'label' => 'Romantique',
			'tag'   => 'poetique · doux',
			'desc'  => 'Immersif rose, typo Fraunces italique, texte aere. Feel poeme visuel.',
			'settings' => array(
				'layout' => 'immersif', 'palette' => 'rose', 'fontpair' => 'fraunces_karla',
				'title_align' => 'center', 'column_width' => 'medium',
				'dropcap' => 0, 'sticky_image' => 0,
			),
		),
		'documentaire' => array(
			'label' => 'Documentaire',
			'tag'   => 'reportage · authentique',
			'desc'  => 'Diptyque sauge, DM Sans lisible, image collante. Photojournalisme.',
			'settings' => array(
				'layout' => 'diptyque', 'palette' => 'sauge', 'fontpair' => 'dmserif_dmsans',
				'title_align' => 'left', 'column_width' => 'medium',
				'dropcap' => 0, 'sticky_image' => 1,
			),
		),
	);
}

function ann_art_fontpairs() {
	// Pairs curees. Chaque pair = 1 Google Font pour display + 1 pour body.
	// Si la police body est system-ui, on n'enqueue que le display.
	return array(
		'cormorant_inter' => array(
			'label'    => 'Cormorant + Inter (elegant, actuel)',
			'display'  => "'Cormorant Garamond', Georgia, serif",
			'body'     => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
			'gfonts'   => 'family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap',
		),
		'playfair_lora' => array(
			'label'    => 'Playfair + Lora (magazine)',
			'display'  => "'Playfair Display', Georgia, serif",
			'body'     => "'Lora', Georgia, serif",
			'gfonts'   => 'family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,500;1,400&display=swap',
		),
		'dmserif_dmsans' => array(
			'label'    => 'DM Serif + DM Sans (moderne)',
			'display'  => "'DM Serif Display', Georgia, serif",
			'body'     => "'DM Sans', -apple-system, sans-serif",
			'gfonts'   => 'family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;700&display=swap',
		),
		'fraunces_karla' => array(
			'label'    => 'Fraunces + Karla (contemporain)',
			'display'  => "'Fraunces', Georgia, serif",
			'body'     => "'Karla', -apple-system, sans-serif",
			'gfonts'   => 'family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Karla:wght@300;400;500;700&display=swap',
		),
		'system' => array(
			'label'    => 'Systeme (rapide, sans font externe)',
			'display'  => "Georgia, 'Times New Roman', serif",
			'body'     => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
			'gfonts'   => '',
		),
	);
}

/* ===========================================================================
 * Reglages : defaults + accessor
 * ========================================================================= */
function ann_art_defaults() {
	return array(
		'layout'        => 'editorial',
		'palette'       => 'rose',
		'custom_bg'     => '',
		'custom_text'   => '',
		'custom_accent' => '',
		'fontpair'      => 'cormorant_inter',
		'title_align'   => 'center',    // center | left
		'column_width'  => 'medium',    // narrow | medium | wide
		'dropcap'       => 1,           // 0 | 1
		'sticky_image'  => 1,           // 0 | 1 (utile pour diptyque)
	);
}

function ann_art_get() {
	$s = get_option( ANN_ART_OPT, array() );
	if ( ! is_array( $s ) ) { $s = array(); }
	return wp_parse_args( $s, ann_art_defaults() );
}

/* ===========================================================================
 * Menu admin
 * ========================================================================= */
add_action( 'admin_menu', 'ann_art_menu', 20 );
function ann_art_menu() {
	// Ajoute la page sous le menu "Anna Photo" si il existe, sinon Apparence
	$parent = 'ann-hub';
	if ( ! defined( 'ANN_PROSP_OPT' ) ) { $parent = 'themes.php'; }
	add_submenu_page(
		$parent,
		'Style des articles',
		'🎨 Style articles',
		'edit_theme_options',
		'ann-article-style',
		'ann_art_render'
	);
}

/* ===========================================================================
 * Handler save
 * ========================================================================= */
add_action( 'admin_post_ann_art_save', 'ann_art_save' );
function ann_art_save() {
	if ( ! current_user_can( 'edit_theme_options' ) ) { wp_die( 'Non autorise' ); }
	check_admin_referer( 'ann_art_save' );

	$layouts   = array_keys( ann_art_layouts() );
	$palettes  = array_keys( ann_art_palettes() );
	$fontpairs = array_keys( ann_art_fontpairs() );

	$in = wp_unslash( $_POST );
	$out = array(
		'layout'        => in_array( ( $in['layout'] ?? 'editorial' ), $layouts, true ) ? $in['layout'] : 'editorial',
		'palette'       => in_array( ( $in['palette'] ?? 'rose' ), $palettes, true ) ? $in['palette'] : 'rose',
		'custom_bg'     => sanitize_hex_color( $in['custom_bg'] ?? '' ),
		'custom_text'   => sanitize_hex_color( $in['custom_text'] ?? '' ),
		'custom_accent' => sanitize_hex_color( $in['custom_accent'] ?? '' ),
		'fontpair'      => in_array( ( $in['fontpair'] ?? 'cormorant_inter' ), $fontpairs, true ) ? $in['fontpair'] : 'cormorant_inter',
		'title_align'   => in_array( ( $in['title_align'] ?? 'center' ), array( 'center', 'left' ), true ) ? $in['title_align'] : 'center',
		'column_width'  => in_array( ( $in['column_width'] ?? 'medium' ), array( 'narrow', 'medium', 'wide' ), true ) ? $in['column_width'] : 'medium',
		'dropcap'       => empty( $in['dropcap'] ) ? 0 : 1,
		'sticky_image'  => empty( $in['sticky_image'] ) ? 0 : 1,
	);
	update_option( ANN_ART_OPT, $out );
	wp_safe_redirect( add_query_arg( array( 'page' => 'ann-article-style', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

/* ===========================================================================
 * Page admin : rendering
 * ========================================================================= */
function ann_art_render() {
	if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
	$s        = ann_art_get();
	$layouts  = ann_art_layouts();
	$palettes = ann_art_palettes();
	$fonts    = ann_art_fontpairs();
	$presets  = ann_art_presets();
	$saved    = ! empty( $_GET['saved'] );

	// Detecte quel preset (s'il y en a un) correspond aux settings actuels
	$active_preset = '';
	foreach ( $presets as $k => $p ) {
		$match = true;
		foreach ( $p['settings'] as $key => $val ) {
			if ( (string) $s[ $key ] !== (string) $val ) { $match = false; break; }
		}
		if ( $match ) { $active_preset = $k; break; }
	}
	?>
	<style>
	.ap-art-wrap { max-width: 1280px; padding-top: 20px; }
	.ap-art-hero {
		background: linear-gradient(135deg, #fdf5f2 0%, #f4e0dc 100%);
		border: 1px solid #d4a5a5; border-radius: 16px;
		padding: 32px 36px; margin: 16px 0 32px;
	}
	.ap-art-hero h1 { font-size: 28px; margin: 0 0 8px; font-family: Georgia, serif; font-style: italic; color: #3d2e2e; font-weight: 400; }
	.ap-art-hero p { margin: 0; color: #8b6f6f; font-size: 16px; line-height: 1.55; max-width: 62ch; }
	.ap-art-hero .ap-art-step {
		display: inline-block; background: #8b6f6f; color: #fff;
		font-size: 11px; padding: 4px 12px; border-radius: 12px;
		margin-bottom: 12px; letter-spacing: 0.08em; font-weight: 600; text-transform: uppercase;
	}

	.ap-art-saved {
		background: #ecfdf5; border: 1px solid #10b981; color: #065f46;
		padding: 14px 20px; border-radius: 10px; margin: 0 0 24px;
		display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 500;
	}
	.ap-art-saved::before { content: "✓"; font-size: 20px; color: #10b981; }

	/* Grille des 8 gros cards */
	.ap-art-styles {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
		gap: 20px;
		margin: 0 0 40px;
	}
	.ap-art-style {
		background: #fff; border: 3px solid #e2e8f0; border-radius: 14px;
		padding: 0; cursor: pointer; text-align: left; overflow: hidden;
		display: flex; flex-direction: column;
		transition: all .25s cubic-bezier(.4,0,.2,1);
		font-family: inherit; position: relative;
	}
	.ap-art-style:hover {
		border-color: #d4a5a5; transform: translateY(-6px);
		box-shadow: 0 20px 40px rgba(139,111,111,.18);
	}
	.ap-art-style:active { transform: translateY(-2px); }
	.ap-art-style.is-active {
		border-color: #10b981;
		box-shadow: 0 0 0 4px rgba(16,185,129,.15), 0 10px 30px rgba(139,111,111,.15);
	}
	.ap-art-style.is-active::before {
		content: "✓ EN LIGNE";
		position: absolute; top: 12px; right: 12px; z-index: 2;
		background: #10b981; color: #fff;
		font-size: 11px; padding: 5px 12px; border-radius: 20px;
		letter-spacing: 0.08em; font-weight: 700;
		box-shadow: 0 3px 8px rgba(16,185,129,.3);
	}

	.ap-art-style-preview { height: 200px; border-bottom: 1px solid #e2e8f0; overflow: hidden; }
	.mp { width: 100%; height: 100%; display: flex; }
	.mp-editorial { flex-direction: column; }
	.mp-editorial > :nth-child(1) { flex: 1.2; background: linear-gradient(135deg,#f4e0dc,#d4a5a5); }
	.mp-editorial > :nth-child(2) { flex: 0.4; background: #fdf5f2; display: flex; align-items: center; justify-content: center; }
	.mp-editorial > :nth-child(2)::before { content: ""; width: 60%; height: 10px; background: #8b6f6f; border-radius: 2px; }
	.mp-editorial > :nth-child(3) { flex: 1; background: #fdf5f2; padding: 8px 20px; }
	.mp-editorial > :nth-child(3)::before { content: ""; display: block; height: 70%; background: repeating-linear-gradient(#c9b6b6 0 2px, transparent 2px 6px); }

	.mp-epure { flex-direction: column; padding: 12px; background: #fdf5f2; align-items: center; }
	.mp-epure > * { width: 55%; }
	.mp-epure > :nth-child(1) { height: 8px; background: #8b6f6f; border-radius: 2px; margin: 8px 0; }
	.mp-epure > :nth-child(2) { height: 60px; background: linear-gradient(135deg,#f4e0dc,#d4a5a5); border-radius: 3px; margin-bottom: 12px; }
	.mp-epure > :nth-child(3) { height: 50px; background: repeating-linear-gradient(#c9b6b6 0 2px, transparent 2px 6px); }

	.mp-diptyque > :nth-child(1) { flex: 0.9; background: linear-gradient(135deg,#f4e0dc,#d4a5a5); }
	.mp-diptyque > :nth-child(2) { flex: 1.1; background: #fdf5f2; padding: 16px; display: flex; flex-direction: column; gap: 8px; justify-content: center; }
	.mp-diptyque > :nth-child(2)::before { content: ""; height: 10px; background: #8b6f6f; border-radius: 2px; width: 80%; }
	.mp-diptyque > :nth-child(2)::after { content: ""; height: 70px; background: repeating-linear-gradient(#c9b6b6 0 2px, transparent 2px 6px); }

	.mp-immersif { flex-direction: column; }
	.mp-immersif > :nth-child(1) { flex: 1.8; background: linear-gradient(180deg,#f4e0dc,#8b6f6f); display: flex; align-items: flex-end; padding: 16px; }
	.mp-immersif > :nth-child(1)::after { content: ""; width: 70%; height: 12px; background: #fff; border-radius: 2px; }
	.mp-immersif > :nth-child(2) { flex: 1; background: #fff; padding: 10px 16px; }
	.mp-immersif > :nth-child(2)::before { content: ""; display: block; height: 70%; background: repeating-linear-gradient(#c9b6b6 0 2px, transparent 2px 6px); }

	/* Palette override sur mockups */
	.ap-art-style-preview[data-palette="blanc"] .mp > :first-child { background: linear-gradient(135deg,#ececec,#a8a8a8) !important; }
	.ap-art-style-preview[data-palette="blanc"] .mp { background: #fff; }
	.ap-art-style-preview[data-palette="blanc"] .mp > :not(:first-child) { background: #fff !important; }
	.ap-art-style-preview[data-palette="aubergine"] .mp { background: #2a1e22 !important; }
	.ap-art-style-preview[data-palette="aubergine"] .mp > * { background: #3a2a2c !important; }
	.ap-art-style-preview[data-palette="aubergine"] .mp > :first-child { background: linear-gradient(135deg,#8b6f6f,#4a3739) !important; }
	.ap-art-style-preview[data-palette="sauge"] .mp { background: #f5f2ec !important; }
	.ap-art-style-preview[data-palette="sauge"] .mp > :not(:first-child) { background: #f5f2ec !important; }
	.ap-art-style-preview[data-palette="sauge"] .mp > :first-child { background: linear-gradient(135deg,#c5a68d,#8e9976) !important; }

	.ap-art-style-body { padding: 20px 22px 22px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
	.ap-art-style-tag {
		font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
		color: #d4a5a5; font-weight: 700;
	}
	.ap-art-style-name {
		font-family: Georgia, serif; font-style: italic;
		font-size: 24px; color: #3d2e2e; margin: 0; line-height: 1.1;
	}
	.ap-art-style-desc {
		font-size: 14px; color: #64748b; line-height: 1.5; margin: 0; flex: 1;
	}
	.ap-art-style-cta {
		display: flex; align-items: center; justify-content: space-between;
		padding-top: 12px; margin-top: 4px;
		border-top: 1px dashed rgba(139,111,111,.2);
		font-size: 14px; font-weight: 600; color: #8b6f6f;
	}
	.ap-art-style-cta-arrow { transition: transform .2s; font-size: 18px; }
	.ap-art-style:hover .ap-art-style-cta-arrow { transform: translateX(4px); }

	/* Bandeau apercu global en bas */
	.ap-art-actions {
		background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
		padding: 20px 24px; margin: 32px 0 0;
		display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;
	}
	.ap-art-actions-text { margin: 0; color: #64748b; font-size: 14px; flex: 1; }
	.ap-art-actions-text strong { color: #3d2e2e; font-family: Georgia, serif; font-style: italic; }
	.ap-art-view-btn {
		display: inline-flex; align-items: center; gap: 8px;
		background: #8b6f6f; color: #fff !important; padding: 12px 22px;
		border-radius: 8px; text-decoration: none; font-weight: 600;
		transition: all .2s; font-size: 15px;
	}
	.ap-art-view-btn:hover { background: #3d2e2e; transform: translateY(-2px); }

	/* Options avancees en accordeon */
	.ap-art-advanced {
		background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
		margin: 24px 0 0;
	}
	.ap-art-advanced-toggle {
		width: 100%; background: none; border: 0; padding: 18px 24px;
		display: flex; align-items: center; justify-content: space-between;
		cursor: pointer; font-size: 15px; font-weight: 600; color: #3d2e2e;
		text-align: left; font-family: inherit;
	}
	.ap-art-advanced-toggle:hover { background: #faf5f2; }
	.ap-art-advanced-toggle-icon { transition: transform .2s; color: #8b6f6f; font-size: 12px; }
	.ap-art-advanced.is-open .ap-art-advanced-toggle-icon { transform: rotate(180deg); }
	.ap-art-advanced-body {
		display: none; padding: 0 24px 24px;
		border-top: 1px solid #e2e8f0;
	}
	.ap-art-advanced.is-open .ap-art-advanced-body { display: block; }

	.ap-art-adv-section { margin: 24px 0 0; }
	.ap-art-adv-section h3 { margin: 0 0 12px; font-size: 14px; color: #3d2e2e; }
	.ap-art-adv-section p.hint { margin: 0 0 14px; color: #64748b; font-size: 13px; }
	.ap-art-adv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
	.ap-art-adv-pick {
		background: #fff; border: 2px solid #e2e8f0; border-radius: 10px;
		padding: 12px; cursor: pointer; text-align: left; display: block;
		transition: all .15s; position: relative;
	}
	.ap-art-adv-pick:hover { border-color: #c9b6b6; }
	.ap-art-adv-pick input { position: absolute; opacity: 0; pointer-events: none; }
	.ap-art-adv-pick:has(input:checked) { border-color: #d4a5a5; background: #fdf5f2; }
	.ap-art-adv-pick-name { font-weight: 600; font-size: 13px; margin: 0 0 2px; }
	.ap-art-adv-pick-sub { font-size: 11px; color: #64748b; margin: 0; }

	.ap-art-swatches { display: flex; gap: 5px; margin-top: 6px; }
	.ap-art-swatches span { display: block; width: 18px; height: 18px; border-radius: 50%; border: 1px solid rgba(0,0,0,.08); }

	.ap-art-option-row {
		display: flex; align-items: center; gap: 16px; padding: 12px 0;
		border-bottom: 1px dashed rgba(139,111,111,.15);
	}
	.ap-art-option-row:last-child { border: 0; }
	.ap-art-option-label { flex: 1; margin: 0; font-size: 14px; }
	.ap-art-btns {
		display: inline-flex; background: #f1f5f9; border-radius: 8px; padding: 3px; gap: 2px;
	}
	.ap-art-btns label {
		padding: 6px 14px; font-size: 13px; border-radius: 6px; cursor: pointer;
		transition: all .15s; color: #64748b; margin: 0;
	}
	.ap-art-btns input { position: absolute; opacity: 0; pointer-events: none; }
	.ap-art-btns:has(input[value="left"]:checked) label[data-v="left"],
	.ap-art-btns:has(input[value="center"]:checked) label[data-v="center"],
	.ap-art-btns:has(input[value="narrow"]:checked) label[data-v="narrow"],
	.ap-art-btns:has(input[value="medium"]:checked) label[data-v="medium"],
	.ap-art-btns:has(input[value="wide"]:checked) label[data-v="wide"] {
		background: #fff; color: #8b6f6f; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,.06);
	}

	.ap-toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
	.ap-toggle input { opacity: 0; width: 0; height: 0; }
	.ap-toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 24px; cursor: pointer; transition: .2s; }
	.ap-toggle-slider::before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .2s; }
	.ap-toggle input:checked + .ap-toggle-slider { background: #d4a5a5; }
	.ap-toggle input:checked + .ap-toggle-slider::before { transform: translateX(20px); }

	.ap-art-custom-colors { display: none; padding: 14px; background: #faf5f2; border-radius: 8px; margin-top: 12px; gap: 14px; }
	.ap-art-custom-colors.is-visible { display: grid; grid-template-columns: repeat(3, 1fr); }
	.ap-art-color-field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 4px; }
	.ap-art-color-field input[type=color] { width: 100%; height: 36px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; }

	.ap-art-save-adv-btn {
		background: #8b6f6f; color: #fff; border: 0; padding: 10px 22px;
		border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px;
	}
	.ap-art-save-adv-btn:hover { background: #3d2e2e; }
	</style>

	<div class="wrap ap-art-wrap">

		<?php if ( $saved ) : ?>
			<div class="ap-art-saved">Style enregistré ! Va voir un article pour tester.</div>
		<?php endif; ?>

		<div class="ap-art-hero">
			<span class="ap-art-step">Étape unique</span>
			<h1>Choisis le style de tes articles</h1>
			<p>Clique sur l'un des 8 styles ci-dessous. C'est tout. Ton choix s'applique immédiatement à tous tes articles. Tu peux changer d'avis à tout moment.</p>
		</div>

		<!-- ═══════════ 8 GROS STYLES CLIQUABLES ═══════════ -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ap-art-form">
			<input type="hidden" name="action" value="ann_art_save">
			<?php wp_nonce_field( 'ann_art_save' ); ?>

			<!-- Champs cachés qui portent l'état actuel (updated par le JS click) -->
			<input type="hidden" name="layout"       value="<?php echo esc_attr( $s['layout'] ); ?>">
			<input type="hidden" name="palette"      value="<?php echo esc_attr( $s['palette'] ); ?>">
			<input type="hidden" name="fontpair"     value="<?php echo esc_attr( $s['fontpair'] ); ?>">
			<input type="hidden" name="title_align"  value="<?php echo esc_attr( $s['title_align'] ); ?>">
			<input type="hidden" name="column_width" value="<?php echo esc_attr( $s['column_width'] ); ?>">
			<input type="hidden" name="dropcap"      value="<?php echo esc_attr( $s['dropcap'] ); ?>">
			<input type="hidden" name="sticky_image" value="<?php echo esc_attr( $s['sticky_image'] ); ?>">
			<input type="hidden" name="custom_bg"     value="<?php echo esc_attr( $s['custom_bg'] ); ?>">
			<input type="hidden" name="custom_text"   value="<?php echo esc_attr( $s['custom_text'] ); ?>">
			<input type="hidden" name="custom_accent" value="<?php echo esc_attr( $s['custom_accent'] ); ?>">

			<div class="ap-art-styles">
				<?php foreach ( $presets as $key => $p ) :
					$sett = $p['settings'];
					$is_active = ( $active_preset === $key ); ?>
					<button type="button"
						class="ap-art-style <?php echo $is_active ? 'is-active' : ''; ?>"
						data-preset='<?php echo esc_attr( wp_json_encode( $sett ) ); ?>'>
						<div class="ap-art-style-preview" data-palette="<?php echo esc_attr( $sett['palette'] ); ?>">
							<div class="mp mp-<?php echo esc_attr( $sett['layout'] ); ?>">
								<div></div><div></div><div></div>
							</div>
						</div>
						<div class="ap-art-style-body">
							<span class="ap-art-style-tag"><?php echo esc_html( $p['tag'] ); ?></span>
							<h2 class="ap-art-style-name"><?php echo esc_html( $p['label'] ); ?></h2>
							<p class="ap-art-style-desc"><?php echo esc_html( $p['desc'] ); ?></p>
							<div class="ap-art-style-cta">
								<span>Choisir ce style</span>
								<span class="ap-art-style-cta-arrow">→</span>
							</div>
						</div>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="ap-art-actions">
				<p class="ap-art-actions-text">
					<strong>Style actuel :</strong>
					<?php echo esc_html( $active_preset ? $presets[ $active_preset ]['label'] : 'Personnalisé' ); ?>
				</p>
				<?php $first_post = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'post_type' => 'post' ) );
				if ( ! empty( $first_post ) ) : ?>
					<a href="<?php echo esc_url( get_permalink( $first_post[0]->ID ) ); ?>" target="_blank" rel="noopener" class="ap-art-view-btn">
						👁️ Voir un article
					</a>
				<?php endif; ?>
			</div>
		</form>

		<!-- ═══════════ OPTIONS AVANCÉES (accordéon fermé par défaut) ═══════════ -->
		<div class="ap-art-advanced" id="ap-art-advanced">
			<button type="button" class="ap-art-advanced-toggle" id="ap-art-advanced-toggle">
				<span>🔧 Options avancées (pour bidouiller à la main)</span>
				<span class="ap-art-advanced-toggle-icon">▼</span>
			</button>
			<div class="ap-art-advanced-body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ann_art_save">
					<?php wp_nonce_field( 'ann_art_save' ); ?>

					<div class="ap-art-adv-section">
						<h3>Mise en page</h3>
						<p class="hint">Structure de l'article (image, titre, texte).</p>
						<div class="ap-art-adv-grid">
							<?php foreach ( $layouts as $key => $lay ) : ?>
								<label class="ap-art-adv-pick">
									<input type="radio" name="layout" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['layout'], $key ); ?>>
									<p class="ap-art-adv-pick-name"><?php echo esc_html( $lay['emoji'] . ' ' . $lay['label'] ); ?></p>
									<p class="ap-art-adv-pick-sub"><?php echo esc_html( $lay['desc'] ); ?></p>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ap-art-adv-section">
						<h3>Couleurs</h3>
						<div class="ap-art-adv-grid">
							<?php foreach ( $palettes as $key => $pal ) : ?>
								<label class="ap-art-adv-pick">
									<input type="radio" name="palette" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['palette'], $key ); ?>>
									<p class="ap-art-adv-pick-name"><?php echo esc_html( $pal['label'] ); ?></p>
									<div class="ap-art-swatches">
										<span style="background:<?php echo esc_attr( $pal['bg'] ); ?>;"></span>
										<span style="background:<?php echo esc_attr( $pal['text'] ); ?>;"></span>
										<span style="background:<?php echo esc_attr( $pal['accent'] ); ?>;"></span>
									</div>
								</label>
							<?php endforeach; ?>
							<label class="ap-art-adv-pick">
								<input type="radio" name="palette" value="custom" <?php checked( $s['palette'], 'custom' ); ?>>
								<p class="ap-art-adv-pick-name">🎨 Perso</p>
								<p class="ap-art-adv-pick-sub">Choisis tes couleurs.</p>
							</label>
						</div>
						<div class="ap-art-custom-colors <?php echo 'custom' === $s['palette'] ? 'is-visible' : ''; ?>" id="ap-art-custom">
							<div class="ap-art-color-field"><label>Fond</label><input type="color" name="custom_bg" value="<?php echo esc_attr( $s['custom_bg'] ?: '#fdf5f2' ); ?>"></div>
							<div class="ap-art-color-field"><label>Texte</label><input type="color" name="custom_text" value="<?php echo esc_attr( $s['custom_text'] ?: '#3d2e2e' ); ?>"></div>
							<div class="ap-art-color-field"><label>Accent</label><input type="color" name="custom_accent" value="<?php echo esc_attr( $s['custom_accent'] ?: '#d4a5a5' ); ?>"></div>
						</div>
					</div>

					<div class="ap-art-adv-section">
						<h3>Typographie</h3>
						<div class="ap-art-adv-grid">
							<?php foreach ( $fonts as $key => $f ) : ?>
								<label class="ap-art-adv-pick">
									<input type="radio" name="fontpair" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['fontpair'], $key ); ?>>
									<p class="ap-art-adv-pick-name" style="font-family:<?php echo esc_attr( $f['display'] ); ?>; font-style:italic; font-size:16px;">Anna Photo</p>
									<p class="ap-art-adv-pick-sub"><?php echo esc_html( $f['label'] ); ?></p>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ap-art-adv-section">
						<h3>Détails</h3>
						<div class="ap-art-option-row">
							<p class="ap-art-option-label"><strong>Alignement titre</strong></p>
							<div class="ap-art-btns">
								<label data-v="left"><input type="radio" name="title_align" value="left" <?php checked( $s['title_align'], 'left' ); ?>>Gauche</label>
								<label data-v="center"><input type="radio" name="title_align" value="center" <?php checked( $s['title_align'], 'center' ); ?>>Centre</label>
							</div>
						</div>
						<div class="ap-art-option-row">
							<p class="ap-art-option-label"><strong>Largeur colonne texte</strong></p>
							<div class="ap-art-btns">
								<label data-v="narrow"><input type="radio" name="column_width" value="narrow" <?php checked( $s['column_width'], 'narrow' ); ?>>Étroite</label>
								<label data-v="medium"><input type="radio" name="column_width" value="medium" <?php checked( $s['column_width'], 'medium' ); ?>>Moyenne</label>
								<label data-v="wide"><input type="radio" name="column_width" value="wide" <?php checked( $s['column_width'], 'wide' ); ?>>Large</label>
							</div>
						</div>
						<div class="ap-art-option-row">
							<p class="ap-art-option-label"><strong>Grosse 1ère lettre</strong> (lettrine)</p>
							<label class="ap-toggle">
								<input type="checkbox" name="dropcap" value="1" <?php checked( $s['dropcap'], 1 ); ?>>
								<span class="ap-toggle-slider"></span>
							</label>
						</div>
						<div class="ap-art-option-row">
							<p class="ap-art-option-label"><strong>Image collante au scroll</strong> (Diptyque)</p>
							<label class="ap-toggle">
								<input type="checkbox" name="sticky_image" value="1" <?php checked( $s['sticky_image'], 1 ); ?>>
								<span class="ap-toggle-slider"></span>
							</label>
						</div>
					</div>

					<button type="submit" class="ap-art-save-adv-btn">💾 Enregistrer mes réglages avancés</button>
				</form>
			</div>
		</div>

	</div>

	<script>
	// 1) Click sur un des 8 styles = remplit les hidden fields + submit
	(function () {
		var form = document.getElementById('ap-art-form');
		if (!form) return;
		document.querySelectorAll('.ap-art-style').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var data;
				try { data = JSON.parse(btn.getAttribute('data-preset')); }
				catch (e) { return; }
				Object.keys(data).forEach(function (name) {
					var input = form.querySelector('input[name="' + name + '"]');
					if (input) input.value = data[name];
				});
				document.querySelectorAll('.ap-art-style').forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active');
				btn.querySelector('.ap-art-style-cta').innerHTML = '<span>⏳ Enregistrement...</span>';
				setTimeout(function () { form.submit(); }, 200);
			});
		});
	})();

	// 2) Accordéon options avancées
	(function () {
		var toggle = document.getElementById('ap-art-advanced-toggle');
		var box    = document.getElementById('ap-art-advanced');
		if (toggle && box) {
			toggle.addEventListener('click', function () { box.classList.toggle('is-open'); });
		}
	})();

	// 3) Custom colors visible si palette=custom
	document.addEventListener('change', function (e) {
		if (e.target.name === 'palette') {
			var custom = document.getElementById('ap-art-custom');
			if (custom) custom.classList.toggle('is-visible', e.target.value === 'custom');
		}
	});
	</script>
	<?php
}


/* ===========================================================================
 * Front-end : enqueue Google Font + inline CSS + body class
 * ========================================================================= */
add_action( 'wp_enqueue_scripts', 'ann_art_enqueue_font', 30 );
function ann_art_enqueue_font() {
	if ( ! is_singular( 'post' ) ) { return; }
	$s     = ann_art_get();
	$fonts = ann_art_fontpairs();
	if ( ! isset( $fonts[ $s['fontpair'] ] ) ) { return; }
	$gfonts = $fonts[ $s['fontpair'] ]['gfonts'];
	if ( empty( $gfonts ) ) { return; }
	wp_enqueue_style(
		'ann-art-fonts',
		'https://fonts.googleapis.com/css2?' . $gfonts,
		array(),
		null
	);
}

add_filter( 'body_class', 'ann_art_body_class' );
function ann_art_body_class( $classes ) {
	if ( is_singular( 'post' ) ) {
		$s = ann_art_get();
		$classes[] = 'ap-layout-' . $s['layout'];
		$classes[] = 'ap-align-' . $s['title_align'];
		$classes[] = 'ap-col-' . $s['column_width'];
		if ( $s['dropcap'] )      { $classes[] = 'ap-dropcap'; }
		if ( $s['sticky_image'] ) { $classes[] = 'ap-sticky-img'; }
	}
	return $classes;
}

add_action( 'wp_head', 'ann_art_inline_css', 99 );
function ann_art_inline_css() {
	if ( ! is_singular( 'post' ) ) { return; }
	$s        = ann_art_get();
	$palettes = ann_art_palettes();
	$fonts    = ann_art_fontpairs();

	// Marqueur de debug pour verifier que le CSS s'applique
	echo "\n<!-- Anna Photo Style : layout={$s['layout']} palette={$s['palette']} font={$s['fontpair']} -->\n";

	// Palette : preset ou custom
	if ( 'custom' === $s['palette'] ) {
		$bg     = $s['custom_bg']     ?: '#fdf5f2';
		$text   = $s['custom_text']   ?: '#3d2e2e';
		$accent = $s['custom_accent'] ?: '#d4a5a5';
		$meta   = '#a89592';
		$accent_strong = '#8b6f6f';
		$line   = 'rgba(0,0,0,0.1)';
	} else {
		$p = $palettes[ $s['palette'] ] ?? $palettes['rose'];
		$bg     = $p['bg'];
		$text   = $p['text'];
		$accent = $p['accent'];
		$meta   = $p['meta'];
		$accent_strong = $p['accent_strong'];
		$line   = $p['line'];
	}

	$fp     = $fonts[ $s['fontpair'] ] ?? $fonts['cormorant_inter'];
	$fdisp  = $fp['display'];
	$fbody  = $fp['body'];

	?>
	<style id="ann-art-css">
	html, body.single-post, body.single.single-post {
		--ap-art-bg: <?php echo esc_html( $bg ); ?>;
		--ap-art-text: <?php echo esc_html( $text ); ?>;
		--ap-art-meta: <?php echo esc_html( $meta ); ?>;
		--ap-art-accent: <?php echo esc_html( $accent ); ?>;
		--ap-art-accent-strong: <?php echo esc_html( $accent_strong ); ?>;
		--ap-art-line: <?php echo esc_html( $line ); ?>;
		--ap-art-display: <?php echo $fdisp; // deja quote ?>;
		--ap-art-body: <?php echo $fbody; ?>;
	}
	/* Force background sur tous les conteneurs Bard pour que la couleur choisie soit visible */
	body.single-post,
	body.single-post #page,
	body.single-post .site,
	body.single-post .site-content,
	body.single-post .content-area,
	body.single-post main,
	body.single-post .main,
	body.single-post .post-main {
		background-color: <?php echo esc_html( $bg ); ?> !important;
	}
	</style>
	<?php
}
