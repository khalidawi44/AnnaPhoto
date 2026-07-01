<?php
/**
 * Anna Photo — Child theme
 *
 * Fichier de fonctions PHP du child theme. Ajoute ici les hooks, filtres,
 * shortcodes et enqueue de scripts specifiques a annaphoto.eu.
 *
 * @package AnnaPhotoChild
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Version du child theme (utilisee pour cache-busting des CSS/JS).
 * A incrementer a chaque modif importante pour forcer le rechargement.
 */
if ( ! defined( 'ANNAPHOTO_CHILD_VERSION' ) ) {
	define( 'ANNAPHOTO_CHILD_VERSION', '1.0.0' );
}

/* ===========================================================================
 * 0. Heritage automatique des reglages Customizer du parent (Bard)
 *
 * Au moment ou l'utilisatrice active le child theme pour la premiere fois,
 * on copie tous les theme_mods (Customizer, menus, header, colors...) du
 * parent Bard vers le child. Comme ca elle demarre EXACTEMENT avec le
 * meme visuel que Bard customise, sans rien reperdre.
 *
 * Peut etre force manuellement via : Apparence > "Copier reglages du parent"
 * ========================================================================= */
add_action( 'after_switch_theme', 'annaphoto_child_seed_from_parent' );
function annaphoto_child_seed_from_parent() {
	$flag = 'annaphoto_child_seeded_from_parent';
	if ( get_option( $flag ) ) {
		return; // deja fait, on ne rejoue pas automatiquement
	}
	annaphoto_child_do_seed();
	update_option( $flag, current_time( 'Y-m-d H:i:s' ) );
}
function annaphoto_child_do_seed() {
	$parent_slug = get_template(); // 'bard'
	$child_slug  = get_stylesheet(); // 'annaphoto-child'
	$parent_mods = get_option( 'theme_mods_' . $parent_slug, array() );
	if ( ! is_array( $parent_mods ) || empty( $parent_mods ) ) {
		return false;
	}
	// On preserve d'eventuels reglages deja definis pour le child (au cas ou)
	$existing = get_option( 'theme_mods_' . $child_slug, array() );
	if ( ! is_array( $existing ) ) { $existing = array(); }
	$merged = array_replace( $parent_mods, $existing );
	update_option( 'theme_mods_' . $child_slug, $merged );
	return true;
}

/**
 * Bouton admin pour forcer une nouvelle copie des reglages du parent
 * (utile si Anna change quelque chose dans Bard et veut le repercuter).
 */
add_action( 'admin_menu', function () {
	add_theme_page(
		'Copier reglages du parent',
		'Copier reglages parent',
		'switch_themes',
		'annaphoto-reseed',
		'annaphoto_child_reseed_page'
	);
} );
function annaphoto_child_reseed_page() {
	if ( ! current_user_can( 'switch_themes' ) ) { return; }
	$done = false;
	if ( isset( $_POST['annaphoto_reseed'] ) && check_admin_referer( 'annaphoto_reseed' ) ) {
		$done = annaphoto_child_do_seed();
		update_option( 'annaphoto_child_seeded_from_parent', current_time( 'Y-m-d H:i:s' ) );
	}
	$last  = get_option( 'annaphoto_child_seeded_from_parent', '' );
	$post  = admin_url( 'admin.php' );
	$parent = get_template();
	?>
	<div class="wrap">
		<h1>🔄 Copier les reglages depuis <?php echo esc_html( $parent ); ?></h1>
		<?php if ( $done ) : ?>
			<div class="notice notice-success"><p>✅ Reglages copies. Va voir ton site : le child theme herite maintenant du look actuel de <?php echo esc_html( $parent ); ?>.</p></div>
		<?php endif; ?>
		<p>Ce bouton copie <strong>tous les reglages Customizer</strong> du parent (<?php echo esc_html( $parent ); ?>) vers ce child theme : bannieres, couleurs, header, menus, options du theme.</p>
		<p style="color:#666;">Derniere copie : <?php echo esc_html( $last ? $last : 'jamais' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'annaphoto_reseed' ); ?>
			<p><button type="submit" name="annaphoto_reseed" value="1" class="button button-primary button-hero" onclick="return confirm('Copier tous les reglages du parent ' + <?php echo wp_json_encode( $parent ); ?> + ' vers le child ? Les reglages existants du child seront ecrases.');">Copier maintenant depuis <?php echo esc_html( $parent ); ?></button></p>
		</form>
	</div>
	<?php
}

/* ===========================================================================
 * 1. Enqueue des styles et scripts
 * ========================================================================= */
add_action( 'wp_enqueue_scripts', function () {
	// Style du parent 1001photo
	wp_enqueue_style(
		'annaphoto-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);

	// Style du child (surcharges)
	wp_enqueue_style(
		'annaphoto-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'annaphoto-parent-style' ),
		ANNAPHOTO_CHILD_VERSION
	);

	// CSS additionnels (dans assets/css/)
	$extra_css = get_stylesheet_directory() . '/assets/css/custom.css';
	if ( file_exists( $extra_css ) ) {
		wp_enqueue_style(
			'annaphoto-child-custom',
			get_stylesheet_directory_uri() . '/assets/css/custom.css',
			array( 'annaphoto-child-style' ),
			ANNAPHOTO_CHILD_VERSION
		);
	}

	// JS additionnels (dans assets/js/)
	$extra_js = get_stylesheet_directory() . '/assets/js/custom.js';
	if ( file_exists( $extra_js ) ) {
		wp_enqueue_script(
			'annaphoto-child-custom',
			get_stylesheet_directory_uri() . '/assets/js/custom.js',
			array( 'jquery' ),
			ANNAPHOTO_CHILD_VERSION,
			true // in footer
		);
	}
}, 20 );

/* ===========================================================================
 * 2. Nettoyage : retirer emojis et scripts inutiles
 * (Decommente si tu veux alleger les pages)
 * ========================================================================= */
/*
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
} );
*/

/* ===========================================================================
 * 3. Personnalisations diverses — decommente selon les besoins
 * ========================================================================= */

/*
// Retirer "Site propulse par WordPress" du footer
add_action( 'wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'wp-logo' );
} );

// Ajouter un lien menu programmatiquement
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
	if ( 'primary' === $args->theme_location ) {
		$items .= '<li><a href="/mon-lien-custom">Mon lien</a></li>';
	}
	return $items;
}, 10, 2 );

// Modifier la longueur de l'extrait
add_filter( 'excerpt_length', function () { return 30; } );

// Retirer le "..." des extraits
add_filter( 'excerpt_more', function () { return ' →'; } );
*/

/* ===========================================================================
 * 4. Charger les fichiers inclus (inc/)
 * ========================================================================= */
$inc_dir = get_stylesheet_directory() . '/inc';
if ( is_dir( $inc_dir ) ) {
	foreach ( glob( $inc_dir . '/*.php' ) as $file ) {
		require_once $file;
	}
}
