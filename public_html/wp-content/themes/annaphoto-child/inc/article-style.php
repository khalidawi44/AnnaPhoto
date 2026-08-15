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
	$s = ann_art_get();
	$layouts  = ann_art_layouts();
	$palettes = ann_art_palettes();
	$fonts    = ann_art_fontpairs();
	$saved    = ! empty( $_GET['saved'] );
	?>
	<style>
	.ap-art-wrap { max-width: 1180px; }
	.ap-art-wrap h1 { display:flex; align-items:center; gap:10px; font-size:24px; margin:12px 0 4px; }
	.ap-art-wrap .ap-art-lead { color:#64748b; margin:0 0 24px; font-size:14px; }
	.ap-art-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px 24px; margin:16px 0; }
	.ap-art-card > h2 { margin:0 0 6px; font-size:17px; display:flex; align-items:center; gap:8px; }
	.ap-art-card > p { color:#64748b; margin:0 0 18px; font-size:13px; }
	.ap-art-grid { display:grid; gap:14px; }
	.ap-art-grid-2 { grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); }
	.ap-art-grid-4 { grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
	.ap-art-pick {
		display:block; cursor:pointer; background:#fff; border:2px solid #e2e8f0;
		border-radius:12px; padding:16px; transition:all .18s ease; position:relative;
	}
	.ap-art-pick:hover { border-color:#c9b6b6; transform:translateY(-2px); box-shadow:0 6px 16px rgba(139,111,111,.12); }
	.ap-art-pick input[type=radio] { position:absolute; opacity:0; pointer-events:none; }
	.ap-art-pick.is-checked, .ap-art-pick input:checked ~ * { }
	.ap-art-pick:has(input:checked) { border-color:#d4a5a5; background:#fdf5f2; box-shadow:0 0 0 3px rgba(212,165,165,.2); }
	.ap-art-pick-title { font-weight:600; font-size:15px; margin:0 0 4px; display:flex; align-items:center; gap:6px; }
	.ap-art-pick-desc { font-size:12.5px; color:#64748b; line-height:1.5; margin:0; }
	.ap-art-preview { margin-top:12px; height:100px; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden; }

	/* Mini mockups pour le layout picker */
	.mp { width:100%; height:100%; display:flex; }
	.mp-editorial { flex-direction:column; }
	.mp-editorial > :nth-child(1) { flex:1.2; background:linear-gradient(135deg,#f4e0dc,#d4a5a5); }
	.mp-editorial > :nth-child(2) { flex:0.5; background:#fdf5f2; display:flex; align-items:center; justify-content:center; }
	.mp-editorial > :nth-child(2)::before { content:""; width:60%; height:8px; background:#8b6f6f; border-radius:1px; }
	.mp-editorial > :nth-child(3) { flex:1; background:#fdf5f2; padding:4px 8px; }
	.mp-editorial > :nth-child(3)::before { content:""; display:block; height:60%; background:repeating-linear-gradient(#c9b6b6 0 2px,transparent 2px 5px); }

	.mp-epure { flex-direction:column; padding:8px; background:#fdf5f2; align-items:center; }
	.mp-epure > * { width:60%; }
	.mp-epure > :nth-child(1) { height:6px; background:#8b6f6f; border-radius:1px; margin:6px 0; }
	.mp-epure > :nth-child(2) { height:36px; background:linear-gradient(135deg,#f4e0dc,#d4a5a5); border-radius:2px; margin-bottom:8px; }
	.mp-epure > :nth-child(3) { height:32px; background:repeating-linear-gradient(#c9b6b6 0 2px,transparent 2px 5px); }

	.mp-diptyque > :nth-child(1) { flex:0.9; background:linear-gradient(135deg,#f4e0dc,#d4a5a5); }
	.mp-diptyque > :nth-child(2) { flex:1.1; background:#fdf5f2; padding:8px; display:flex; flex-direction:column; gap:4px; }
	.mp-diptyque > :nth-child(2)::before { content:""; height:6px; background:#8b6f6f; border-radius:1px; width:80%; }
	.mp-diptyque > :nth-child(2)::after { content:""; height:38px; background:repeating-linear-gradient(#c9b6b6 0 2px,transparent 2px 5px); }

	.mp-immersif { flex-direction:column; }
	.mp-immersif > :nth-child(1) { flex:1.5; background:linear-gradient(180deg,#f4e0dc,#8b6f6f); display:flex; align-items:flex-end; padding:8px; }
	.mp-immersif > :nth-child(1)::after { content:""; width:70%; height:8px; background:#fff; border-radius:1px; }
	.mp-immersif > :nth-child(2) { flex:1; background:#fff; padding:6px 10px; }
	.mp-immersif > :nth-child(2)::before { content:""; display:block; height:70%; background:repeating-linear-gradient(#c9b6b6 0 2px,transparent 2px 5px); }

	/* Palette picker */
	.ap-art-swatches { display:flex; gap:6px; margin-top:8px; }
	.ap-art-swatches span { display:block; width:22px; height:22px; border-radius:50%; border:1px solid rgba(0,0,0,.1); }

	/* Font picker */
	.ap-art-fontrow { display:flex; align-items:center; gap:14px; }
	.ap-art-fontrow-label { font-weight:600; font-size:15px; margin:0; flex:1; }
	.ap-art-fontrow-sample { font-size:26px; line-height:1; color:#3d2e2e; }
	.ap-art-fontrow-sub { font-size:13px; color:#64748b; }

	.ap-art-options-row {
		display:flex; align-items:center; gap:16px; padding:12px 0;
		border-bottom:1px dashed rgba(139,111,111,.15);
	}
	.ap-art-options-row:last-child { border:0; }
	.ap-art-options-label { flex:1; margin:0; font-size:14px; }
	.ap-art-btns {
		display:inline-flex; background:#f1f5f9; border-radius:8px; padding:3px; gap:2px;
	}
	.ap-art-btns label {
		padding:6px 14px; font-size:13px; border-radius:6px; cursor:pointer;
		transition:all .15s; color:#64748b;
	}
	.ap-art-btns input { position:absolute; opacity:0; pointer-events:none; }
	.ap-art-btns:has(input[value="left"]:checked) label[data-v="left"],
	.ap-art-btns:has(input[value="center"]:checked) label[data-v="center"],
	.ap-art-btns:has(input[value="narrow"]:checked) label[data-v="narrow"],
	.ap-art-btns:has(input[value="medium"]:checked) label[data-v="medium"],
	.ap-art-btns:has(input[value="wide"]:checked) label[data-v="wide"] {
		background:#fff; color:#8b6f6f; font-weight:600; box-shadow:0 1px 3px rgba(0,0,0,.06);
	}

	/* Toggle switch */
	.ap-toggle { position:relative; display:inline-block; width:44px; height:24px; }
	.ap-toggle input { opacity:0; width:0; height:0; }
	.ap-toggle-slider { position:absolute; inset:0; background:#cbd5e1; border-radius:24px; cursor:pointer; transition:.2s; }
	.ap-toggle-slider::before { content:""; position:absolute; height:18px; width:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
	.ap-toggle input:checked + .ap-toggle-slider { background:#d4a5a5; }
	.ap-toggle input:checked + .ap-toggle-slider::before { transform:translateX(20px); }

	/* Custom colors */
	.ap-art-custom-colors { display:none; padding:14px; background:#faf5f2; border-radius:8px; margin-top:12px; gap:14px; }
	.ap-art-custom-colors.is-visible { display:grid; grid-template-columns:repeat(3,1fr); }
	.ap-art-color-field label { display:block; font-size:12px; color:#64748b; margin-bottom:4px; }
	.ap-art-color-field input[type=color] { width:100%; height:36px; border:1px solid #e2e8f0; border-radius:6px; cursor:pointer; }

	/* Submit bar */
	.ap-art-submit {
		position:sticky; bottom:0; background:#fdf5f2;
		padding:16px 24px; border:1px solid #d4a5a5;
		border-radius:12px; margin-top:24px;
		display:flex; align-items:center; justify-content:space-between; gap:16px;
		box-shadow:0 -4px 20px rgba(139,111,111,.1);
	}
	.ap-art-submit-note { margin:0; color:#8b6f6f; font-size:13px; }
	.ap-art-submit .button-primary { background:#8b6f6f; border-color:#8b6f6f; padding:8px 24px; height:auto; }
	.ap-art-submit .button-primary:hover { background:#3d2e2e; border-color:#3d2e2e; }

	/* Preview link */
	.ap-art-preview-link {
		display:inline-flex; align-items:center; gap:6px;
		background:#fff; border:1px solid #d4a5a5; color:#8b6f6f;
		padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:500;
		transition:all .15s;
	}
	.ap-art-preview-link:hover { background:#d4a5a5; color:#fff; }
	</style>

	<div class="wrap ap-art-wrap">
		<h1>🎨 Style des articles</h1>
		<p class="ap-art-lead">Choisis en 1 clic l'apparence de tes articles. Layout, couleurs, typographie, options. Les changements s'appliquent immediatement a TOUS tes articles.</p>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p>✓ Style enregistre. Va voir un article pour tester.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ann_art_save">
			<?php wp_nonce_field( 'ann_art_save' ); ?>

			<!-- ═══════════ LAYOUT ═══════════ -->
			<div class="ap-art-card">
				<h2>1. Mise en page</h2>
				<p>Choisis la structure d'un article : ou l'image, le titre, le texte.</p>
				<div class="ap-art-grid ap-art-grid-4">
					<?php foreach ( $layouts as $key => $lay ) : ?>
						<label class="ap-art-pick">
							<input type="radio" name="layout" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['layout'], $key ); ?>>
							<p class="ap-art-pick-title"><?php echo esc_html( $lay['emoji'] . ' ' . $lay['label'] ); ?></p>
							<p class="ap-art-pick-desc"><?php echo esc_html( $lay['desc'] ); ?></p>
							<div class="ap-art-preview">
								<div class="mp mp-<?php echo esc_attr( $key ); ?>">
									<div></div><div></div><div></div>
								</div>
							</div>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- ═══════════ COULEURS ═══════════ -->
			<div class="ap-art-card">
				<h2>2. Couleurs</h2>
				<p>Palette a appliquer aux articles. Choisis un preset ou passe en mode personnalise.</p>
				<div class="ap-art-grid ap-art-grid-4">
					<?php foreach ( $palettes as $key => $pal ) : ?>
						<label class="ap-art-pick">
							<input type="radio" name="palette" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['palette'], $key ); ?>>
							<p class="ap-art-pick-title"><?php echo esc_html( $pal['label'] ); ?></p>
							<div class="ap-art-swatches">
								<span style="background:<?php echo esc_attr( $pal['bg'] ); ?>;"></span>
								<span style="background:<?php echo esc_attr( $pal['text'] ); ?>;"></span>
								<span style="background:<?php echo esc_attr( $pal['accent'] ); ?>;"></span>
								<span style="background:<?php echo esc_attr( $pal['accent_strong'] ); ?>;"></span>
							</div>
						</label>
					<?php endforeach; ?>
					<label class="ap-art-pick">
						<input type="radio" name="palette" value="custom" id="ap-art-palette-custom" <?php checked( $s['palette'], 'custom' ); ?>>
						<p class="ap-art-pick-title">🎨 Personnalise</p>
						<p class="ap-art-pick-desc">Choisis tes propres couleurs ci-dessous.</p>
					</label>
				</div>
				<div class="ap-art-custom-colors <?php echo 'custom' === $s['palette'] ? 'is-visible' : ''; ?>" id="ap-art-custom">
					<div class="ap-art-color-field">
						<label>Fond</label>
						<input type="color" name="custom_bg" value="<?php echo esc_attr( $s['custom_bg'] ?: '#fdf5f2' ); ?>">
					</div>
					<div class="ap-art-color-field">
						<label>Texte</label>
						<input type="color" name="custom_text" value="<?php echo esc_attr( $s['custom_text'] ?: '#3d2e2e' ); ?>">
					</div>
					<div class="ap-art-color-field">
						<label>Accent</label>
						<input type="color" name="custom_accent" value="<?php echo esc_attr( $s['custom_accent'] ?: '#d4a5a5' ); ?>">
					</div>
				</div>
			</div>

			<!-- ═══════════ TYPO ═══════════ -->
			<div class="ap-art-card">
				<h2>3. Typographie</h2>
				<p>Paire de polices : une pour les titres, une pour le texte.</p>
				<div class="ap-art-grid ap-art-grid-2">
					<?php foreach ( $fonts as $key => $f ) :
						$sample_style = 'font-family:' . esc_attr( $f['display'] ) . ';font-style:italic;';
						$sub_style    = 'font-family:' . esc_attr( $f['body'] ) . ';'; ?>
						<label class="ap-art-pick">
							<input type="radio" name="fontpair" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['fontpair'], $key ); ?>>
							<div class="ap-art-fontrow">
								<div style="flex:1;">
									<p class="ap-art-pick-title"><?php echo esc_html( $f['label'] ); ?></p>
									<p class="ap-art-fontrow-sample" style="<?php echo $sample_style; ?>">Anna Photo</p>
									<p class="ap-art-fontrow-sub" style="<?php echo $sub_style; ?>">Photographe de femmes, guidee par la douceur.</p>
								</div>
							</div>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- ═══════════ OPTIONS ═══════════ -->
			<div class="ap-art-card">
				<h2>4. Options fines</h2>
				<p>Petits reglages qui font la difference.</p>

				<div class="ap-art-options-row">
					<p class="ap-art-options-label"><strong>Alignement du titre</strong></p>
					<div class="ap-art-btns">
						<label data-v="left"><input type="radio" name="title_align" value="left" <?php checked( $s['title_align'], 'left' ); ?>>Gauche</label>
						<label data-v="center"><input type="radio" name="title_align" value="center" <?php checked( $s['title_align'], 'center' ); ?>>Centre</label>
					</div>
				</div>

				<div class="ap-art-options-row">
					<p class="ap-art-options-label"><strong>Largeur de la colonne de texte</strong></p>
					<div class="ap-art-btns">
						<label data-v="narrow"><input type="radio" name="column_width" value="narrow" <?php checked( $s['column_width'], 'narrow' ); ?>>Etroite</label>
						<label data-v="medium"><input type="radio" name="column_width" value="medium" <?php checked( $s['column_width'], 'medium' ); ?>>Moyenne</label>
						<label data-v="wide"><input type="radio" name="column_width" value="wide" <?php checked( $s['column_width'], 'wide' ); ?>>Large</label>
					</div>
				</div>

				<div class="ap-art-options-row">
					<p class="ap-art-options-label"><strong>Lettrine</strong> (grosse premiere lettre du 1er paragraphe)</p>
					<label class="ap-toggle">
						<input type="checkbox" name="dropcap" value="1" <?php checked( $s['dropcap'], 1 ); ?>>
						<span class="ap-toggle-slider"></span>
					</label>
				</div>

				<div class="ap-art-options-row">
					<p class="ap-art-options-label"><strong>Image collante</strong> au scroll (uniquement Diptyque)</p>
					<label class="ap-toggle">
						<input type="checkbox" name="sticky_image" value="1" <?php checked( $s['sticky_image'], 1 ); ?>>
						<span class="ap-toggle-slider"></span>
					</label>
				</div>
			</div>

			<!-- ═══════════ SUBMIT ═══════════ -->
			<div class="ap-art-submit">
				<p class="ap-art-submit-note">Les changements s'appliquent a <strong>tous les articles</strong> immediatement apres l'enregistrement.</p>
				<div style="display:flex; gap:12px; align-items:center;">
					<?php
					$first_post = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'post_type' => 'post' ) );
					if ( ! empty( $first_post ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $first_post[0]->ID ) ); ?>" target="_blank" rel="noopener" class="ap-art-preview-link">
							👁️ Voir un article
						</a>
					<?php endif; ?>
					<button type="submit" class="button button-primary">💾 Enregistrer le style</button>
				</div>
			</div>
		</form>
	</div>

	<script>
	// Toggle affichage des couleurs custom quand on choisit "Personnalise"
	document.addEventListener('change', function (e) {
		if (e.target.name === 'palette') {
			var custom = document.getElementById('ap-art-custom');
			if (custom) {
				custom.classList.toggle('is-visible', e.target.value === 'custom');
			}
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
	.single-post {
		--ap-art-bg: <?php echo esc_html( $bg ); ?>;
		--ap-art-text: <?php echo esc_html( $text ); ?>;
		--ap-art-meta: <?php echo esc_html( $meta ); ?>;
		--ap-art-accent: <?php echo esc_html( $accent ); ?>;
		--ap-art-accent-strong: <?php echo esc_html( $accent_strong ); ?>;
		--ap-art-line: <?php echo esc_html( $line ); ?>;
		--ap-art-display: <?php echo $fdisp; // deja quote ?>;
		--ap-art-body: <?php echo $fbody; ?>;
	}
	</style>
	<?php
}
