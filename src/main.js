import './style.css';
import Alpine from 'alpinejs';
import gsap from 'gsap';

// Expose globally
window.Alpine = Alpine;
window.gsap = gsap;

// Toast Notification Manager with GSAP animation
const Toast = {
    container: null,
    
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed bottom-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full px-4';
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'info', duration = 4000) {
        this.init();
        
        const colors = {
            success: 'bg-white border-emerald-200 text-emerald-900 shadow-emerald-500/10 ring-1 ring-emerald-500/20',
            error:   'bg-white border-rose-200 text-rose-900 shadow-rose-500/10 ring-1 ring-rose-500/20',
            warning: 'bg-white border-amber-200 text-amber-900 shadow-amber-500/10 ring-1 ring-amber-500/20',
            info:    'bg-white border-purple-200 text-purple-900 shadow-purple-500/10 ring-1 ring-purple-500/20',
        };
        
        const badgeColors = {
            success: 'bg-emerald-50 text-emerald-600',
            error:   'bg-rose-50 text-rose-600',
            warning: 'bg-amber-50 text-amber-600',
            info:    'bg-purple-50 text-purple-600',
        };
        
        const icons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />',
            error:   '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />',
            info:    '<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />',
        };

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center justify-between p-3.5 rounded-xl border shadow-lg text-sm transition-all ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 ${badgeColors[type] || badgeColors.info}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        ${icons[type] || icons.info}
                    </svg>
                </div>
                <span class="font-medium text-xs leading-relaxed text-slate-800">${message}</span>
            </div>
            <button type="button" class="ml-3 p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        this.container.appendChild(toast);

        const closeBtn = toast.querySelector('button');
        const dismiss = () => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                toast.remove();
            } else {
                gsap.to(toast, {
                    opacity: 0,
                    x: 20,
                    duration: 0.2,
                    ease: 'power1.in',
                    onComplete: () => toast.remove()
                });
            }
        };

        closeBtn.addEventListener('click', dismiss);

        // GSAP entrance
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            gsap.from(toast, {
                opacity: 0,
                y: 16,
                scale: 0.96,
                duration: 0.25,
                ease: 'power2.out'
            });
        }

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    },
    
    success(msg) { this.show(msg, 'success'); },
    error(msg)   { this.show(msg, 'error', 5000); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg)    { this.show(msg, 'info'); }
};

window.Toast = Toast;

// Reusable micro-animations
document.addEventListener('DOMContentLoaded', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) return;

    // Smooth subtle page entrance
    const fadeElements = document.querySelectorAll('.gsap-fade-in');
    if (fadeElements.length > 0) {
        gsap.from(fadeElements, {
            opacity: 0,
            y: 8,
            duration: 0.35,
            stagger: 0.04,
            ease: 'power1.out',
            clearProps: 'all'
        });
    }

    // Staggered card entrance for dashboards
    const cards = document.querySelectorAll('.gsap-card');
    if (cards.length > 0) {
        gsap.from(cards, {
            opacity: 0,
            y: 12,
            duration: 0.3,
            stagger: 0.03,
            ease: 'power2.out',
            clearProps: 'all'
        });
    }
});

Alpine.start();
