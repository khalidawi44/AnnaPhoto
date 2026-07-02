<?php
/**
 * Sticky nav : transparent en haut, opaque au scroll, cache au scroll-down,
 * reapparait au scroll-up. Meme logique que le template Alliance Groupe :
 * classes .is-scrolled (opaque + blur) et .is-hidden (translateY -100%).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', 'annaphoto_scroll_menu_script' );
function annaphoto_scroll_menu_script() {
	?>
	<script>
	(function () {
		// Trouve le header du site (premier match dans la liste)
		// Bard utilise <nav class="top-menu-container"> pour le menu principal.
		var selectors = [
			'.top-menu-container',
			'nav.top-menu-container',
			'#site-nav',
			'.site-nav',
			'.site-header',
			'#masthead',
			'#site-header',
			'header[role="banner"]',
			'header.header',
			'.main-header',
			'body > header'
		];
		// Mobile : on ne touche pas au menu, Bard a sa propre structure
		// (hamburger + drawer) qu'on casserait en la deplacant ou en
		// forcant flex/center.
		var isMobile = window.matchMedia('(max-width: 900px)').matches;
		if (isMobile) return;

		var nav = null;
		for (var i = 0; i < selectors.length; i++) {
			nav = document.querySelector(selectors[i]);
			if (nav) break;
		}
		if (!nav) return;
		nav.classList.add('site-nav');

		// Deplacer le nav en enfant direct de <body> pour echapper aux
		// stacking contexts d'ancetres (data-sidebar-sticky, transform,
		// filter, contain, etc.). Desktop uniquement.
		if (nav.parentElement !== document.body) {
			document.body.insertBefore(nav, document.body.firstChild);
		}

		var lastY = window.pageYOffset;
		var ticking = false;

		function update() {
			var y = window.pageYOffset;
			// ETAT 1 — Opaque + flou des qu'on quitte le tout en haut
			nav.classList.toggle('is-scrolled', y > 60);
			// ETAT 2 — Sens du scroll : on descend => cacher, on remonte => montrer
			// On ne cache jamais tant qu'on est pres du haut (< 150px)
			if (y > lastY && y > 150) {
				nav.classList.add('is-hidden');
			} else {
				nav.classList.remove('is-hidden');
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
	})();
	</script>
	<?php
}
