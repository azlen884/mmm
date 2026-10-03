import './style.css';
import Alpine from 'alpinejs';
import gsap from 'gsap';
import ApexCharts from 'apexcharts';

// Expose on window for inline use and Alpine directives
window.Alpine = Alpine;
window.gsap = gsap;
window.ApexCharts = ApexCharts;

// GSAP Subtle Micro-animations
document.addEventListener('DOMContentLoaded', () => {
    const fadeElements = document.querySelectorAll('.gsap-fade-in');
    if (fadeElements.length > 0 && typeof gsap !== 'undefined') {
        gsap.from(fadeElements, {
            opacity: 0,
            y: 12,
            duration: 0.4,
            stagger: 0.05,
            ease: 'power2.out',
            clearProps: 'all'
        });
    }

    const cards = document.querySelectorAll('.gsap-card');
    if (cards.length > 0 && typeof gsap !== 'undefined') {
        gsap.from(cards, {
            opacity: 0,
            scale: 0.98,
            duration: 0.35,
            stagger: 0.04,
            ease: 'power1.out',
            clearProps: 'all'
        });
    }
});

Alpine.start();
