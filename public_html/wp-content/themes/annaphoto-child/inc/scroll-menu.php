<?php
/**
 * Header transparent + hide-on-scroll + show-on-scroll-up
 *
 * - Attache un JS leger dans le footer qui detecte la direction du scroll
 *   et ajoute des classes CSS sur le header du site.
 * - Le CSS correspondant est dans style.css (section 6).
 *
 * Selecteurs cibles pour trouver le header : essaie plusieurs selecteurs
 * courants pour etre robuste vis-a-vis de Bard et de futurs changements.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', 'annaphoto_scroll_menu_script' );
function annaphoto_scroll_menu_script() {
	?>
	<script>
	(function () {
		// Recherche du header du site (premier match)
		var selectors = [
			'.site-header',
			'#masthead',
			'#site-header',
			'header[role="banner"]',
			'header.header',
			'.main-header',
			'body > header'
		];
		var header = null;
		for (var i = 0; i < selectors.length; i++) {
			header = document.querySelector(selectors[i]);
			if (header) break;
		}
		if (!header) return;
		header.classList.add('ap-scroll-header');

		var lastY = window.scrollY;
		var showAfter = 80;   // scroll depuis le haut avant de considerer "scrolled"
		var topZone   = 30;   // sous ce seuil = "en haut" (menu transparent)
		var ticking = false;

		function update() {
			var y = window.scrollY;
			var goingDown = y > lastY;
			var atTop = y < topZone;

			header.classList.toggle('ap-at-top', atTop);

			if (!atTop && y > showAfter) {
				if (goingDown) {
					header.classList.add('ap-hidden');
					header.classList.remove('ap-visible');
				} else {
					header.classList.remove('ap-hidden');
					header.classList.add('ap-visible');
				}
			} else {
				header.classList.remove('ap-hidden');
				header.classList.add('ap-visible');
			}

			lastY = y;
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(update);
				ticking = true;
			}
		}, { passive: true });

		update();
	})();
	</script>
	<?php
}
