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
