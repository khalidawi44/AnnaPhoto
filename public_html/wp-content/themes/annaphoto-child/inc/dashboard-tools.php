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
			'desc'  => 'Choisir une presentation en 1 clic',
			'accent'=> '#d4a5a5',
			'condition' => true,
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

	.ap-hub-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
		gap: 10px;
		padding: 16px;
		background: #f8fafc;
	}
	.ap-hub-tool {
		display: block; text-decoration: none;
		background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
		padding: 14px 12px; text-align: center;
		transition: all .18s cubic-bezier(.4,0,.2,1);
		position: relative;
	}
	.ap-hub-tool:hover {
		transform: translateY(-3px);
		box-shadow: 0 8px 18px rgba(0,0,0,.08);
		border-color: var(--tool-accent, #d4a5a5);
	}
	.ap-hub-tool.is-primary {
		background: linear-gradient(135deg, var(--tool-accent) 0%, color-mix(in srgb, var(--tool-accent) 70%, #000) 100%);
		border-color: transparent;
		grid-column: 1 / -1;
		padding: 22px 18px;
	}
	.ap-hub-tool.is-primary .ap-hub-tool-title,
	.ap-hub-tool.is-primary .ap-hub-tool-desc {
		color: #fff;
	}
	.ap-hub-tool.is-primary .ap-hub-tool-emoji { font-size: 32px; }
	.ap-hub-tool.is-primary .ap-hub-tool-title { font-size: 17px; }
	.ap-hub-tool.is-primary::after {
		content: "→"; position: absolute; top: 50%; right: 20px;
		transform: translateY(-50%); color: #fff; font-size: 22px;
		transition: transform .2s;
	}
	.ap-hub-tool.is-primary:hover::after { transform: translateY(-50%) translateX(5px); }

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
