// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', () => {

    // ===== PRELOADER =====
    const preloader = document.getElementById('preloader');
    window.addEventListener('load', () => {
        setTimeout(() => {
            preloader.classList.add('hidden');
        }, 1500);
    });
    // Fallback: hide preloader after 3s
    setTimeout(() => {
        preloader.classList.add('hidden');
    }, 3000);

    // ===== NAVBAR SCROLL =====
    const navbar = document.getElementById('navbar');
    const backToTop = document.getElementById('backToTop');

    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;

        // Navbar background
        if (scrollY > 80) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // Back to top
        if (scrollY > 500) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }

        // Active nav link based on scroll
        updateActiveNavLink();
    });

    // ===== ACTIVE NAV LINK =====
    function updateActiveNavLink() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 120;
            if (window.scrollY >= sectionTop) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    // ===== HAMBURGER MENU =====
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });

    // Close mobile nav on link click
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        });
    });

    // Close mobile nav on outside click
    document.addEventListener('click', (e) => {
        if (!navLinks.contains(e.target) && !hamburger.contains(e.target) && !navbar.contains(e.target)) {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        }
    });

    // ===== SCROLL REVEAL =====
    const scrollRevealElements = document.querySelectorAll('.scroll-reveal');

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    scrollRevealElements.forEach(el => revealObserver.observe(el));

    // ===== COUNTER ANIMATION =====
    const counters = document.querySelectorAll('[data-count]');
    let countersAnimated = new Set();

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !countersAnimated.has(entry.target)) {
                countersAnimated.add(entry.target);
                animateCounter(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));

    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-count'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
                element.classList.add('counter-animated');
                setTimeout(() => element.classList.remove('counter-animated'), 300);
            }
            element.textContent = Math.floor(current).toLocaleString('en-IN');
        }, 16);
    }

    // ===== PRODUCT FILTER =====
    const filterBtns = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card[data-category]');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');

            productCards.forEach(card => {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.classList.remove('hidden');
                    card.style.animation = 'fadeIn 0.5s ease forwards';
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });

    // ===== TESTIMONIAL SLIDER =====
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dotsContainer = document.getElementById('testimonialDots');
    let currentTestimonial = 0;
    let testimonialInterval;

    // Create dots
    testimonialCards.forEach((_, i) => {
        const dot = document.createElement('div');
        dot.classList.add('testimonial-dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToTestimonial(i));
        dotsContainer.appendChild(dot);
    });

    const dots = document.querySelectorAll('.testimonial-dot');

    function goToTestimonial(index) {
        testimonialCards[currentTestimonial].classList.remove('active');
        dots[currentTestimonial].classList.remove('active');
        currentTestimonial = index;
        testimonialCards[currentTestimonial].classList.add('active');
        dots[currentTestimonial].classList.add('active');
    }

    function nextTestimonial() {
        const next = (currentTestimonial + 1) % testimonialCards.length;
        goToTestimonial(next);
    }

    function prevTestimonialFn() {
        const prev = (currentTestimonial - 1 + testimonialCards.length) % testimonialCards.length;
        goToTestimonial(prev);
    }

    if (nextBtn) nextBtn.addEventListener('click', () => {
        nextTestimonial();
        resetAutoplay();
    });

    if (prevBtn) prevBtn.addEventListener('click', () => {
        prevTestimonialFn();
        resetAutoplay();
    });

    function startAutoplay() {
        testimonialInterval = setInterval(nextTestimonial, 5000);
    }

    function resetAutoplay() {
        clearInterval(testimonialInterval);
        startAutoplay();
    }

    startAutoplay();

    // ===== LANGUAGE TOGGLE (EN/HI) =====
    const langToggle = document.getElementById('langToggle');
    let currentLang = 'en';

    langToggle.addEventListener('click', () => {
        currentLang = currentLang === 'en' ? 'hi' : 'en';
        document.querySelectorAll('[data-en]').forEach(el => {
            const text = el.getAttribute('data-' + currentLang);
            if (text) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    el.placeholder = text;
                } else {
                    el.innerHTML = text;
                }
            }
        });
        // Toggle language labels
        langToggle.querySelector('.lang-en').style.display = currentLang === 'en' ? 'inline' : 'none';
        langToggle.querySelector('.lang-hi').style.display = currentLang === 'hi' ? 'inline' : 'none';
    });

    // ===== CONTACT FORM =====
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            const category = document.getElementById('category').value;
            const state = document.getElementById('state').value;
            const message = document.getElementById('message').value.trim();

            // Validate
            if (!name || !phone || !email || !category || !state || !message) {
                showNotification('Please fill all fields', 'error');
                return;
            }

            if (!/^[0-9]{10}$/.test(phone)) {
                showNotification('Please enter a valid 10-digit phone number', 'error');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }

            // Success
            showNotification('Thank you! We will get back to you soon.', 'success');
            contactForm.reset();

            // Open WhatsApp with pre-filled message
            const whatsappMsg = encodeURIComponent(
                `Hello Organic Pesticide!\n\nName: ${name}\nPhone: ${phone}\nEmail: ${email}\nInterested in: ${category}\nState: ${state}\nMessage: ${message}`
            );
            // Uncomment below to auto-open WhatsApp:
            // window.open(`https://wa.me/919876543210?text=${whatsappMsg}`, '_blank');
        });
    }

    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type) {
        const existing = document.querySelector('.notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 16px 24px;
            background: ${type === 'success' ? '#22c55e' : '#ef4444'};
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            z-index: 10000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideInRight 0.5s ease, slideOutRight 0.5s ease 3s forwards;
            font-family: 'Poppins', sans-serif;
        `;
        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 4000);
    }

    // Add notification animation
    const notifStyle = document.createElement('style');
    notifStyle.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100px); opacity: 0; }
        }
    `;
    document.head.appendChild(notifStyle);

    // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = anchor.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ===== MAP INITIALIZATION =====
    initMap();

    // ===== NEWSLETTER FORM =====
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = newsletterForm.querySelector('input');
            if (emailInput.value.trim()) {
                showNotification('Thank you for subscribing!', 'success');
                emailInput.value = '';
            }
        });
    }

});

// ===== MAP FUNCTION =====
function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Show a static map placeholder with service area info
    mapContainer.innerHTML = `
        <div style="width:100%;height:100%;position:relative;overflow:hidden;background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;z-index:2;">
                <i class="fas fa-map-marked-alt" style="font-size:4rem;color:#16a34a;margin-bottom:16px;display:block;"></i>
                <h3 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:8px;">Our Service Areas</h3>
                <p style="color:#64748b;max-width:400px;margin:0 auto;">Maharashtra | Gujarat | Madhya Pradesh</p>
                <div style="margin-top:24px;display:flex;gap:24px;justify-content:center;flex-wrap:wrap;">
                    <div style="padding:12px 20px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        <strong style="color:#16a34a;">3,500+</strong><br><small style="color:#64748b;">Maharashtra</small>
                    </div>
                    <div style="padding:12px 20px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        <strong style="color:#16a34a;">4,000+</strong><br><small style="color:#64748b;">Gujarat</small>
                    </div>
                    <div style="padding:12px 20px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        <strong style="color:#16a34a;">2,500+</strong><br><small style="color:#64748b;">Madhya Pradesh</small>
                    </div>
                </div>
            </div>
            <svg style="position:absolute;bottom:0;left:0;width:100%;height:80px;" viewBox="0 0 1200 80" preserveAspectRatio="none">
                <path d="M0,80 L0,40 Q300,0 600,40 Q900,80 1200,40 L1200,80 Z" fill="rgba(22,163,74,0.1)"/>
            </svg>
        </div>
    `;
}
