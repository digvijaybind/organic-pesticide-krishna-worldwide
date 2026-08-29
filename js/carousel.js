/**
 * ============================================================
 *  HERO CAROUSEL
 *  Handles slide rotation, navigation arrows, and dots.
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.getElementById('heroCarousel');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.hero-slide');
    const prevBtn = document.getElementById('heroPrev');
    const nextBtn = document.getElementById('heroNext');
    const dotsContainer = document.getElementById('heroDots');

    if (slides.length === 0) return;

    let current = 0;
    let interval;

    // Create dots
    slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.classList.add('carousel-dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => {
            goTo(i);
            resetAutoplay();
        });
        dotsContainer.appendChild(dot);
    });

    const dots = dotsContainer.querySelectorAll('.carousel-dot');

    function goTo(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
        // regenerate particles for the active slide
        generateParticles(slides[current]);
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetAutoplay(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetAutoplay(); });

    function startAutoplay() {
        interval = setInterval(next, 5000);
    }
    function resetAutoplay() {
        clearInterval(interval);
        startAutoplay();
    }

    // Pause on hover
    carousel.addEventListener('mouseenter', () => clearInterval(interval));
    carousel.addEventListener('mouseleave', () => resetAutoplay());

    startAutoplay();
    generateParticles(slides[0]);
});

/**
 * Generate floating leaf/particle elements inside a hero slide.
 */
function generateParticles(slide) {
    const container = slide.querySelector('.hero-particles');
    if (!container) return;
    // Only generate once per slide
    if (container.dataset.generated) return;
    container.dataset.generated = '1';

    for (let i = 0; i < 24; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 10 + 4;
        p.style.width = size + 'px';
        p.style.height = size + 'px';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDuration = (Math.random() * 15 + 10) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        container.appendChild(p);
    }
}
