<?php
/**
 * Bouton "retour en haut" (back to top)
 *
 * - HTML injecte dans le footer
 * - JS inline (leger, pas de dependance jquery)
 * - CSS dans style.css
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', 'annaphoto_back_to_top_html' );
function annaphoto_back_to_top_html() {
	?>
	<button id="ap-back-to-top" type="button" aria-label="Retour en haut de la page" title="Retour en haut">
		<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<path d="M12 4l-8 8h5v8h6v-8h5z" fill="currentColor"/>
		</svg>
	</button>
	<script>
	(function () {
		var btn = document.getElementById('ap-back-to-top');
		if (!btn) return;
		var threshold = 300;
		function toggle() {
			if (window.scrollY > threshold) {
				btn.classList.add('is-visible');
			} else {
				btn.classList.remove('is-visible');
			}
		}
		window.addEventListener('scroll', toggle, { passive: true });
		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
		toggle();
	})();
	</script>
	<?php
}
