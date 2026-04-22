(function () {
    'use strict';

    class Carousel {
        constructor(root) {
            this.root = root;
            this.slides = Array.from(root.querySelectorAll('.carousel__slide'));
            this.prevBtn = root.querySelector('.carousel__btn--prev');
            this.nextBtn = root.querySelector('.carousel__btn--next');
            this.dotsWrap = root.querySelector('.carousel__dots');
            this.progressBar = root.querySelector('.carousel__progress-bar');
            this.counterCurrent = root.querySelector('.carousel__counter-current');
            this.counterTotal = root.querySelector('.carousel__counter-total');

            this.index = 0;
            this.total = this.slides.length;
            this.autoplay = root.dataset.autoplay === 'true';
            this.interval = parseInt(root.dataset.interval, 10) || 5000;
            this.timer = null;
            this.progressStart = 0;
            this.progressRaf = null;
            this.isHover = false;

            this.init();
        }

        init() {
            if (this.total === 0) return;
            if (this.counterTotal) this.counterTotal.textContent = String(this.total).padStart(2, '0');
            this.buildDots();
            this.goTo(0, true);
            this.bindEvents();
            if (this.autoplay) this.start();
        }

        buildDots() {
            if (!this.dotsWrap) return;
            this.slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'carousel__dot';
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', `Aller à la diapositive ${i + 1}`);
                dot.addEventListener('click', () => this.goTo(i));
                this.dotsWrap.appendChild(dot);
            });
            this.dots = Array.from(this.dotsWrap.children);
        }

        bindEvents() {
            this.prevBtn && this.prevBtn.addEventListener('click', () => this.prev());
            this.nextBtn && this.nextBtn.addEventListener('click', () => this.next());

            document.addEventListener('keydown', (e) => {
                if (!this.isInViewport()) return;
                if (e.key === 'ArrowLeft') this.prev();
                if (e.key === 'ArrowRight') this.next();
            });

            this.root.addEventListener('mouseenter', () => { this.isHover = true; this.pause(); });
            this.root.addEventListener('mouseleave', () => { this.isHover = false; if (this.autoplay) this.start(); });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.pause();
                else if (this.autoplay && !this.isHover) this.start();
            });

            let startX = 0, startY = 0, deltaX = 0, deltaY = 0, tracking = false;
            const viewport = this.root.querySelector('.carousel__viewport');
            viewport.addEventListener('touchstart', (e) => {
                const t = e.touches[0];
                startX = t.clientX; startY = t.clientY; deltaX = 0; deltaY = 0; tracking = true;
                this.pause();
            }, { passive: true });
            viewport.addEventListener('touchmove', (e) => {
                if (!tracking) return;
                const t = e.touches[0];
                deltaX = t.clientX - startX;
                deltaY = t.clientY - startY;
            }, { passive: true });
            viewport.addEventListener('touchend', () => {
                if (!tracking) return;
                tracking = false;
                if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > Math.abs(deltaY)) {
                    if (deltaX < 0) this.next(); else this.prev();
                } else if (this.autoplay && !this.isHover) {
                    this.start();
                }
            });
        }

        isInViewport() {
            const rect = this.root.getBoundingClientRect();
            return rect.bottom > 0 && rect.top < window.innerHeight;
        }

        goTo(i, silent = false) {
            const next = (i + this.total) % this.total;
            this.slides.forEach((s, idx) => s.classList.toggle('is-active', idx === next));
            if (this.dots) this.dots.forEach((d, idx) => {
                d.classList.toggle('is-active', idx === next);
                d.setAttribute('aria-selected', idx === next ? 'true' : 'false');
            });
            if (this.counterCurrent) this.counterCurrent.textContent = String(next + 1).padStart(2, '0');
            this.index = next;
            if (!silent) this.restart();
        }

        next() { this.goTo(this.index + 1); }
        prev() { this.goTo(this.index - 1); }

        start() {
            if (!this.autoplay) return;
            this.pause();
            this.progressStart = performance.now();
            this.tickProgress();
            this.timer = setTimeout(() => this.next(), this.interval);
        }

        pause() {
            if (this.timer) { clearTimeout(this.timer); this.timer = null; }
            if (this.progressRaf) { cancelAnimationFrame(this.progressRaf); this.progressRaf = null; }
            if (this.progressBar) this.progressBar.style.width = '0%';
        }

        restart() {
            if (this.autoplay && !this.isHover) this.start();
        }

        tickProgress() {
            if (!this.progressBar) return;
            const elapsed = performance.now() - this.progressStart;
            const pct = Math.min(100, (elapsed / this.interval) * 100);
            this.progressBar.style.width = pct + '%';
            if (pct < 100) this.progressRaf = requestAnimationFrame(() => this.tickProgress());
        }
    }

    const init = () => {
        document.querySelectorAll('.carousel').forEach((el) => new Carousel(el));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
