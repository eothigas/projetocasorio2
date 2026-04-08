/* base.js — comportamentos globais */

// Blur no main quando menu mobile abre
document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.querySelector('.navbar-toggler');
    const main    = document.querySelector('main');
    if (toggler && main) {
        toggler.addEventListener('click', () => {
            main.classList.toggle('blur-active');
        });
        document.getElementById('navbarNav')?.addEventListener('hidden.bs.collapse', () => {
            main.classList.remove('blur-active');
        });
    }
});

// Intersection Observer para animações de entrada
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const animations = (entry.target.dataset.animation || '').split(',').map(a => a.trim()).filter(Boolean);
            if (entry.isIntersecting) {
                animations.forEach(a => entry.target.classList.add(a));
                entry.target.classList.remove('hidden');
            } else {
                animations.forEach(a => entry.target.classList.remove(a));
                entry.target.classList.add('hidden');
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.hidden').forEach(el => observer.observe(el));
});
