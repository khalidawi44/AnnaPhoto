<?php
/**
 * Widget Dashboard "Tous mes outils Anna Photo"
 *
 * Affiche sur l'accueil admin WordPress un widget avec des grosses
 * cartes cliquables vers TOUS les outils qu'Anna utilise :
 *   - Chercher des annonces
 *   - Mes prospects
 *   - Style des articles
 *   - Rappels Telegram
 *   - Sync GitHub
 *   - Ambassadeurs
 *   - Centre de contrôle
 *   - Reglages
 *
 * Positionne en HAUT du dashboard pour qu'elle le voie en premier.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_dashboard_setup', 'annaphoto_tools_dashboard_widget', 5 );
function annaphoto_tools_dashboard_widget() {
	if ( ! current_user_can( 'edit_posts' ) ) { return; }

	// Retire l'ancien widget "Anna Photo — Prospection" du plugin, on regroupe tout
	remove_meta_box( 'ann_widget', 'dashboard', 'normal' );
	remove_meta_box( 'ann_widget', 'dashboard', 'side' );

	wp_add_dashboard_widget(
		'annaphoto_tools_hub',
		'🛠️ Tous mes outils Anna Photo',
		'annaphoto_tools_dashboard_render'
	);

	// Force ce widget tout en haut du dashboard
	global $wp_meta_boxes;
	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['annaphoto_tools_hub'] ) ) {
		$hub = array( 'annaphoto_tools_hub' => $wp_meta_boxes['dashboard']['normal']['core']['annaphoto_tools_hub'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['annaphoto_tools_hub'] );
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $hub, $wp_meta_boxes['dashboard']['normal']['core'] );
	}
}

function annaphoto_tools_dashboard_render() {
	// Detecte quels modules sont actifs pour ne pas afficher de liens morts
	$mods = function_exists( 'ann_get_modules' ) ? ann_get_modules() : array();

	// Liste des outils. Chaque entree :
	//   url        : lien admin
	//   emoji      : icone visuelle
	//   title      : nom court
	//   desc       : description courte
	//   accent     : couleur d'accent (hex)
	//   condition  : bool pour cacher si module off (optionnel)
	$tools = array(
		'annonces' => array(
			'url'   => admin_url( 'admin.php?page=ann-annonces' ),
			'emoji' => '🎯',
			'title' => 'Chercher des annonces',
			'desc'  => 'Leboncoin, Facebook, Insta en 1 clic',
			'accent'=> '#667eea',
			'condition' => empty( $mods ) || ! empty( $mods['annonces'] ),
			'primary' => true,
		),
		'prospects' => array(
			'url'   => admin_url( 'admin.php?page=ann-prospects' ),
			'emoji' => '📋',
			'title' => 'Mes prospects',
			'desc'  => 'Liste, statuts, messages WhatsApp/SMS',
			'accent'=> '#8b6f6f',
			'condition' => function_exists( 'ann_get_prospects' ),
		),
		'style' => array(
			'url'   => admin_url( 'admin.php?page=ann-article-style' ),
			'emoji' => '🎨',
			'title' => 'Style des articles',
			'desc'  => 'Choisir la presentation de tes articles en 1 clic',
			'accent'=> '#d4a5a5',
			'condition' => true,
			'primary' => true,
		),
		'rappels' => array(
			'url'   => admin_url( 'admin.php?page=ann-agent' ),
			'emoji' => '🔔',
			'title' => 'Rappels Telegram',
			'desc'  => 'Notifs programmees avec liens',
			'accent'=> '#0088cc',
			'condition' => empty( $mods ) || ! empty( $mods['agent'] ),
		),
		'ambassadeurs' => array(
			'url'   => admin_url( 'admin.php?page=ann-ambass' ),
			'emoji' => '🤝',
			'title' => 'Ambassadeurs',
			'desc'  => 'Programme de parrainage clients',
			'accent'=> '#10b981',
			'condition' => ! empty( $mods['ambassadeurs'] ),
		),
		'centre' => array(
			'url'   => admin_url( 'admin.php?page=ann-hub' ),
			'emoji' => '📸',
			'title' => 'Centre de controle',
			'desc'  => 'Vue d\'ensemble Anna Photo',
			'accent'=> '#764ba2',
			'condition' => true,
		),
		'sync' => array(
			'url'   => admin_url( 'tools.php?page=aphoto-sync' ),
			'emoji' => '🔄',
			'title' => 'Sync GitHub',
			'desc'  => 'Mises a jour automatiques',
			'accent'=> '#24292e',
			'condition' => true,
		),
		'reglages' => array(
			'url'   => admin_url( 'admin.php?page=ann-settings' ),
			'emoji' => '⚙️',
			'title' => 'Reglages',
			'desc'  => 'Ville, Telegram, IMAP, modules',
			'accent'=> '#64748b',
			'condition' => function_exists( 'ann_get_settings' ),
		),
	);

	// Compteurs prospection pour bandeau du haut
	$counts = function_exists( 'ann_counters' ) ? ann_counters() : array();

	// Articles pour la section "Mes articles"
	$drafts = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'numberposts'    => 3,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	) );
	$recent = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'numberposts'    => 5,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	?>
	<style>
	#annaphoto_tools_hub .inside { margin: 0; padding: 0; }
	.ap-hub-header {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #fff; padding: 20px 24px 22px; text-align: center;
	}
	.ap-hub-hi {
		font-family: Georgia, serif; font-style: italic;
		font-size: 22px; margin: 0 0 4px; font-weight: 400;
	}
	.ap-hub-sub { margin: 0; font-size: 13px; color: rgba(255,255,255,.85); }
	.ap-hub-stats {
		display: flex; gap: 14px; justify-content: center;
		margin-top: 14px; padding-top: 14px;
		border-top: 1px solid rgba(255,255,255,.15);
		flex-wrap: wrap;
	}
	.ap-hub-stat {
		display: flex; align-items: center; gap: 6px;
		background: rgba(255,255,255,.18); color: #fff !important;
		padding: 5px 12px; border-radius: 20px;
		font-size: 12px; text-decoration: none;
		transition: background .15s;
	}
	.ap-hub-stat:hover { background: rgba(255,255,255,.32); color: #fff !important; }
	.ap-hub-stat b { font-size: 14px; }
	.ap-hub-stat.urgent { background: #ef4444; }

	/* Section "Mes articles" */
	.ap-hub-articles {
		background: #fff;
		padding: 18px 20px 20px;
		border-bottom: 1px solid #e2e8f0;
	}
	.ap-hub-articles h3 {
		margin: 0 0 12px;
		font-size: 13px;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: #64748b;
		font-weight: 600;
	}
	.ap-hub-articles h3 .count {
		background: #d4a5a5; color: #fff;
		font-size: 10px; padding: 2px 8px;
		border-radius: 10px; margin-left: 6px;
		text-transform: none; letter-spacing: 0;
	}
	.ap-hub-new-post {
		display: flex; align-items: center; justify-content: space-between;
		background: linear-gradient(135deg, #d4a5a5 0%, #8b6f6f 100%);
		color: #fff !important; text-decoration: none;
		padding: 16px 20px; border-radius: 10px;
		font-weight: 600; font-size: 16px;
		transition: all .2s;
		margin-bottom: 14px;
	}
	.ap-hub-new-post:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 18px rgba(212,165,165,.35);
		color: #fff !important;
	}
	.ap-hub-new-post .arrow { font-size: 22px; transition: transform .2s; }
	.ap-hub-new-post:hover .arrow { transform: translateX(4px); }

	.ap-hub-article-list {
		display: flex; flex-direction: column; gap: 4px;
		margin: 0 0 10px;
	}
	.ap-hub-article-list h4 {
		margin: 8px 0 4px; font-size: 11px; color: #a89592;
		text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;
	}
	.ap-hub-article {
		display: flex; align-items: center; justify-content: space-between;
		gap: 10px;
		background: #f8fafc; border: 1px solid #e2e8f0;
		border-radius: 8px; padding: 10px 12px;
		text-decoration: none; color: #0f172a;
		transition: all .15s;
	}
	.ap-hub-article:hover {
		background: #fdf5f2; border-color: #d4a5a5;
		color: #0f172a; transform: translateX(2px);
	}
	.ap-hub-article-title {
		flex: 1; font-size: 13px; font-weight: 500;
		overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
	}
	.ap-hub-article-date {
		font-size: 11px; color: #a89592; white-space: nowrap;
	}
	.ap-hub-article.is-draft .ap-hub-article-title::before {
		content: "✎ "; color: #f59e0b; font-weight: 700;
	}
	.ap-hub-articles-all {
		display: inline-block;
		font-size: 12px; color: #8b6f6f;
		text-decoration: none; margin-top: 6px;
	}
	.ap-hub-articles-all:hover { text-decoration: underline; }

	.ap-hub-grid {
		display: grid;
		grid-template-columns: repeat(6, 1fr);
		gap: 10px;
		padding: 16px;
		background: #f8fafc;
	}
	@media (max-width: 900px) {
		.ap-hub-grid { grid-template-columns: repeat(3, 1fr); }
	}
	@media (max-width: 500px) {
		.ap-hub-grid { grid-template-columns: repeat(2, 1fr); }
	}
	.ap-hub-tool {
		display: block; text-decoration: none;
		background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
		padding: 14px 12px; text-align: center;
		transition: all .18s cubic-bezier(.4,0,.2,1);
		position: relative;
		grid-column: span 2;
	}
	@media (max-width: 500px) {
		.ap-hub-tool { grid-column: span 1; }
	}
	.ap-hub-tool:hover {
		transform: translateY(-3px);
		box-shadow: 0 8px 18px rgba(0,0,0,.08);
		border-color: var(--tool-accent, #d4a5a5);
	}
	.ap-hub-tool.is-primary {
		background: linear-gradient(135deg, var(--tool-accent) 0%, color-mix(in srgb, var(--tool-accent) 70%, #000) 100%);
		border-color: transparent;
		grid-column: span 3;
		padding: 24px 22px;
		text-align: left;
		min-height: 120px;
		display: flex;
		flex-direction: column;
		justify-content: center;
	}
	@media (max-width: 700px) {
		.ap-hub-tool.is-primary { grid-column: 1 / -1; }
	}
	.ap-hub-tool.is-primary .ap-hub-tool-title,
	.ap-hub-tool.is-primary .ap-hub-tool-desc {
		color: #fff;
	}
	.ap-hub-tool.is-primary .ap-hub-tool-emoji { font-size: 38px; text-align: left; }
	.ap-hub-tool.is-primary .ap-hub-tool-title { font-size: 20px; margin-top: 4px; }
	.ap-hub-tool.is-primary .ap-hub-tool-desc { font-size: 13px; opacity: 0.95; margin-top: 4px; }
	.ap-hub-tool.is-primary::after {
		content: "→"; position: absolute; top: 50%; right: 22px;
		transform: translateY(-50%); color: #fff; font-size: 28px;
		transition: transform .2s; font-weight: 300;
	}
	.ap-hub-tool.is-primary:hover::after { transform: translateY(-50%) translateX(6px); }

	.ap-hub-tool-emoji {
		font-size: 24px; line-height: 1;
		display: block; margin-bottom: 6px;
	}
	.ap-hub-tool-title {
		font-size: 13px; font-weight: 600; color: #0f172a;
		margin: 0 0 3px; line-height: 1.2;
	}
	.ap-hub-tool-desc {
		font-size: 11px; color: #64748b; margin: 0;
		line-height: 1.3;
	}
	</style>

	<div class="ap-hub-header">
		<p class="ap-hub-hi">👋 Salut Anna !</p>
		<p class="ap-hub-sub">Tous tes outils au meme endroit. Clique sur celui que tu veux ouvrir.</p>

		<?php if ( ! empty( $counts ) ) : ?>
			<div class="ap-hub-stats">
				<a class="ap-hub-stat <?php echo ! empty( $counts['nouveau'] ) ? 'urgent' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=nouveau' ) ); ?>">📋 <b><?php echo (int) ( $counts['nouveau'] ?? 0 ); ?></b> à contacter</a>
				<a class="ap-hub-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=relance' ) ); ?>">🔁 <b><?php echo (int) ( $counts['relance'] ?? 0 ); ?></b> à relancer</a>
				<a class="ap-hub-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=interesse' ) ); ?>">✨ <b><?php echo (int) ( $counts['interesse'] ?? 0 ); ?></b> interessés</a>
				<a class="ap-hub-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=ann-prospects&f_status=client' ) ); ?>">💖 <b><?php echo (int) ( $counts['client'] ?? 0 ); ?></b> clients</a>
			</div>
		<?php endif; ?>
	</div>

	<div class="ap-hub-articles">
		<h3>✍️ Mes articles</h3>

		<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="ap-hub-new-post">
			<span>➕ Ecrire un nouvel article</span>
			<span class="arrow">→</span>
		</a>

		<?php if ( ! empty( $drafts ) ) : ?>
			<div class="ap-hub-article-list">
				<h4>Brouillons a continuer</h4>
				<?php foreach ( $drafts as $d ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $d->ID ) ); ?>" class="ap-hub-article is-draft">
						<span class="ap-hub-article-title"><?php echo esc_html( $d->post_title ?: '(sans titre)' ); ?></span>
						<span class="ap-hub-article-date"><?php echo esc_html( get_the_modified_date( 'd/m', $d ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $recent ) ) : ?>
			<div class="ap-hub-article-list">
				<h4>Articles recents (clic = modifier)</h4>
				<?php foreach ( $recent as $r ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $r->ID ) ); ?>" class="ap-hub-article">
						<span class="ap-hub-article-title"><?php echo esc_html( $r->post_title ); ?></span>
						<span class="ap-hub-article-date"><?php echo esc_html( get_the_date( 'd/m/Y', $r ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="ap-hub-articles-all">Voir tous les articles →</a>
	</div>

	<div class="ap-hub-grid">
		<?php foreach ( $tools as $key => $t ) :
			if ( empty( $t['condition'] ) ) { continue; } ?>
			<a href="<?php echo esc_url( $t['url'] ); ?>"
			   class="ap-hub-tool <?php echo ! empty( $t['primary'] ) ? 'is-primary' : ''; ?>"
			   style="--tool-accent: <?php echo esc_attr( $t['accent'] ); ?>;">
				<span class="ap-hub-tool-emoji"><?php echo esc_html( $t['emoji'] ); ?></span>
				<h3 class="ap-hub-tool-title"><?php echo esc_html( $t['title'] ); ?></h3>
				<p class="ap-hub-tool-desc"><?php echo esc_html( $t['desc'] ); ?></p>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
