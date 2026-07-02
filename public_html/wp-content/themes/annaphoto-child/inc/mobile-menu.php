<?php
/**
 * Menu mobile custom Anna Photo
 *
 * Puisque le menu mobile natif de Bard ne s'affiche pas sur ton site,
 * on injecte notre propre solution : un bouton hamburger fixe en haut a
 * droite + un drawer plein ecran qui slide depuis la droite au clic.
 *
 * Le drawer contient les items du menu WordPress ("Primary" location)
 * ou fallback sur wp_page_menu si aucun menu n'est assigne.
 *
 * Visible UNIQUEMENT sur mobile (< 901px).
 * CSS dans style.css (section 9).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', 'annaphoto_mobile_menu_html' );
function annaphoto_mobile_menu_html() {
	// On recupere le menu WP assigne a un emplacement (essai plusieurs
	// locations courantes utilisees par Bard/WP)
	$menu_locations = array( 'primary', 'main-menu', 'top-menu', 'header-menu', 'menu-1' );
	$menu_html = '';
	foreach ( $menu_locations as $loc ) {
		if ( has_nav_menu( $loc ) ) {
			ob_start();
			wp_nav_menu( array(
				'theme_location'  => $loc,
				'container'       => false,
				'menu_class'      => 'ap-mm-list',
				'items_wrap'      => '<ul class="ap-mm-list">%3$s</ul>',
				'fallback_cb'     => false,
			) );
			$menu_html = ob_get_clean();
			break;
		}
	}
	// Fallback : liste des pages publiees si aucun menu WP n'est assigne
	if ( '' === $menu_html ) {
		ob_start();
		wp_page_menu( array(
			'menu_class'  => 'ap-mm-list',
			'container'   => '',
			'echo'        => true,
			'show_home'   => true,
			'link_before' => '',
			'link_after'  => '',
		) );
		$menu_html = ob_get_clean();
		// wp_page_menu renvoie un div avec ul dedans, on nettoie
		$menu_html = preg_replace( '#</?div[^>]*>#i', '', $menu_html );
	}
	?>
	<!-- Menu mobile custom Anna Photo -->
	<button id="ap-mm-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="ap-mm-drawer">
		<span class="ap-mm-bar"></span>
		<span class="ap-mm-bar"></span>
		<span class="ap-mm-bar"></span>
	</button>
	<div id="ap-mm-backdrop" aria-hidden="true"></div>
	<nav id="ap-mm-drawer" aria-label="Menu mobile" aria-hidden="true">
		<div class="ap-mm-header">
			<span class="ap-mm-title">📸 Anna Photo</span>
			<button type="button" class="ap-mm-close" aria-label="Fermer le menu">&times;</button>
		</div>
		<div class="ap-mm-content">
			<?php echo $menu_html; // deja echape par wp_nav_menu / wp_page_menu ?>
		</div>
	</nav>
	<script>
	(function () {
		var toggle   = document.getElementById('ap-mm-toggle');
		var drawer   = document.getElementById('ap-mm-drawer');
		var backdrop = document.getElementById('ap-mm-backdrop');
		var closeBtn = drawer ? drawer.querySelector('.ap-mm-close') : null;
		if (!toggle || !drawer || !backdrop) return;

		function open() {
			toggle.classList.add('is-open');
			toggle.setAttribute('aria-expanded', 'true');
			drawer.classList.add('is-open');
			drawer.setAttribute('aria-hidden', 'false');
			backdrop.classList.add('is-open');
			document.body.style.overflow = 'hidden';
		}
		function close() {
			toggle.classList.remove('is-open');
			toggle.setAttribute('aria-expanded', 'false');
			drawer.classList.remove('is-open');
			drawer.setAttribute('aria-hidden', 'true');
			backdrop.classList.remove('is-open');
			document.body.style.overflow = '';
		}
		toggle.addEventListener('click', function () {
			if (drawer.classList.contains('is-open')) close(); else open();
		});
		backdrop.addEventListener('click', close);
		if (closeBtn) closeBtn.addEventListener('click', close);
		// Ferme le drawer quand on clique un lien du menu
		drawer.addEventListener('click', function (e) {
			if (e.target.tagName === 'A') close();
		});
		// Ferme sur Echap
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && drawer.classList.contains('is-open')) close();
		});
	})();
	</script>
	<?php
}
