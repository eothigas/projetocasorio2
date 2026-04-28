/* base.js — comportamentos globais + navegação AJAX (pjax) */

/* ============================================================
   ANIMAÇÕES DE ENTRADA (Intersection Observer)
   ============================================================ */
function initAnimations() {
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            const animations = (entry.target.dataset.animation || '')
                .split(',').map(function (a) { return a.trim(); }).filter(Boolean);
            if (entry.isIntersecting) {
                animations.forEach(function (a) { entry.target.classList.add(a); });
                entry.target.classList.remove('hidden');
            } else {
                animations.forEach(function (a) { entry.target.classList.remove(a); });
                entry.target.classList.add('hidden');
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.hidden').forEach(function (el) { observer.observe(el); });
}

/* ============================================================
   NAVBAR MOBILE — blur no main
   ============================================================ */
function initNavbar() {
    const toggler = document.querySelector('.navbar-toggler');
    const main    = document.querySelector('main');
    if (!toggler || !main) return;

    toggler.addEventListener('click', function () {
        main.classList.toggle('blur-active');
    });

    const nav = document.getElementById('navbarNav');
    if (nav) {
        nav.addEventListener('hidden.bs.collapse', function () {
            main.classList.remove('blur-active');
        });
    }
}

/* ============================================================
   NAVBAR MOBILE — fechar ao navegar
   ============================================================ */
function closeNavbar() {
    var navEl = document.getElementById('navbarNav');
    if (!navEl) return;
    var bsCollapse = bootstrap.Collapse.getInstance(navEl);
    if (bsCollapse) bsCollapse.hide();
    document.querySelector('main')?.classList.remove('blur-active');
}

/* ============================================================
   PJAX — navegação sem recarregar (mantém o player tocando)
   ============================================================ */
var pjax = (function () {

    var PAGE_CSS_PATTERN = /\/css\/paginas\//;
    var PAGE_JS_PATTERN  = /\/src\/js\//;
    var SKIP_JS          = ['base.js', 'bootstrap', 'jquery', 'youtube'];
    var SKIP_LINKS       = ['/admin', '/api'];

    function shouldIntercept(href) {
        if (!href) return false;
        if (/^(#|mailto:|tel:|http|\/\/)/.test(href)) return false;
        if (SKIP_LINKS.some(function (s) { return href.includes(s); })) return false;
        return true;
    }

    function getPageCSS(doc) {
        return Array.from(doc.querySelectorAll('link[rel="stylesheet"]'))
            .filter(function (l) { return PAGE_CSS_PATTERN.test(l.getAttribute('href') || ''); });
    }

    function getPageJS(doc) {
        return Array.from(doc.querySelectorAll('script[src]'))
            .filter(function (s) {
                var src = s.getAttribute('src') || '';
                return PAGE_JS_PATTERN.test(src) && !SKIP_JS.some(function (k) { return src.includes(k); });
            });
    }

    function syncCSS(doc) {
        /* Remove CSS de página anterior */
        document.querySelectorAll('link[data-pjax]').forEach(function (l) { l.remove(); });

        /* Adiciona CSS da nova página */
        getPageCSS(doc).forEach(function (link) {
            var el = document.createElement('link');
            el.rel  = 'stylesheet';
            el.href = new URL(link.getAttribute('href'), window.location.href).href;
            el.dataset.pjax = '1';
            document.head.appendChild(el);
        });
    }

    function syncJS(doc) {
        /* Remove JS de página anterior */
        document.querySelectorAll('script[data-pjax]').forEach(function (s) { s.remove(); });

        /* Adiciona JS da nova página (cache-bust força re-execução) */
        getPageJS(doc).forEach(function (oldScript) {
            var el  = document.createElement('script');
            el.src  = new URL(oldScript.getAttribute('src'), window.location.href).href + '?_=' + Date.now();
            el.dataset.pjax = '1';
            document.body.appendChild(el);
        });
    }

    function updateNavActive(url) {
        document.querySelectorAll('.nav-link, .footer-nav a').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            var match = href === url || href === url.replace(/\/$/, '') ||
                        (href !== '/' && url.endsWith(href));
            a.classList.toggle('active', match);
        });
    }

    function navigate(url, pushState) {
        fetch(url, { headers: { 'X-PJAX': '1' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');

                var newMain = doc.querySelector('main');
                var curMain = document.querySelector('main');
                if (newMain && curMain) curMain.innerHTML = newMain.innerHTML;

                document.title = doc.title;

                if (pushState !== false) {
                    history.pushState({ url: url }, '', url);
                }

                syncCSS(doc);
                syncJS(doc);
                updateNavActive(url);
                initAnimations();
                closeNavbar();
                window.scrollTo({ top: 0, behavior: 'instant' });
            })
            .catch(function () {
                window.location.href = url;
            });
    }

    function init() {
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (shouldIntercept(href)) {
                e.preventDefault();
                navigate(href);
            }
        });

        window.addEventListener('popstate', function (e) {
            navigate(e.state && e.state.url ? e.state.url : window.location.href, false);
        });

        /* Marca estado inicial para o popstate funcionar no back */
        history.replaceState({ url: window.location.href }, '', window.location.href);
    }

    return { init: init };
}());

/* ============================================================
   BOOT
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    initAnimations();
    initNavbar();
    pjax.init();
});
