/* home.js — lógica exclusiva da página inicial */

// Countdown até a data do casamento
(function initCountdown() {
    const targetISO = document.getElementById('countdown')?.dataset.target;
    if (!targetISO) return;

    const target = new Date(targetISO).getTime();

    function update() {
        const now  = Date.now();
        const diff = target - now;

        if (diff <= 0) {
            document.getElementById('cd-days').textContent    = '00';
            document.getElementById('cd-hours').textContent   = '00';
            document.getElementById('cd-minutes').textContent = '00';
            document.getElementById('cd-seconds').textContent = '00';
            return;
        }

        const days    = Math.floor(diff / 86400000);
        const hours   = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000)  / 60000);
        const seconds = Math.floor((diff % 60000)    / 1000);

        document.getElementById('cd-days').textContent    = String(days).padStart(2, '0');
        document.getElementById('cd-hours').textContent   = String(hours).padStart(2, '0');
        document.getElementById('cd-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('cd-seconds').textContent = String(seconds).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
})();
