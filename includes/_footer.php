<footer id="footer">
    <div class="container">

        <div class="footer-couple">
            <p class="footer-names"><?= NOIVA ?> &amp; <?= NOIVO ?></p>
            <div class="footer-divider">
                <span></span>
                <i class="bi bi-heart-fill"></i>
                <span></span>
            </div>
            <p class="footer-date"><?= DIA_SEMANA ?>, <?= DIA ?> de <?= MES ?> de <?= ANO ?></p>
        </div>

        <nav class="footer-nav">
            <a href="<?= BASE_URL ?>/">Início</a>
            <a href="<?= BASE_URL ?>/presentes">Presentes</a>
            <a href="<?= BASE_URL ?>/local">Local</a>
            <a href="<?= BASE_URL ?>/confirmacao">Confirmar Presença</a>
            <a href="<?= BASE_URL ?>/mensagens">Mensagens</a>
        </nav>

        <p class="footer-copy">
            &copy; <?= ANO ?> &middot; <?= NOIVA ?> &amp; <?= NOIVO ?> &middot; Feito com <i class="bi bi-heart-fill" style="color:var(--blue4);font-size:.75rem;"></i>
        </p>

    </div>
</footer>

<!-- YouTube IFrame (oculto) -->
<div id="mbar-yt-container" style="position:absolute;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;"></div>

<!-- Music Bar (desktop) -->
<div id="mbar" class="mbar">

    <!-- Esquerda: capa + info -->
    <div class="mbar-left">
        <div class="mbar-thumb-wrap">
            <img class="mbar-thumb" src="https://img.youtube.com/vi/XH9AkFPHPmM/mqdefault.jpg" alt="Capa">
            <div id="mbar-eq" class="mbar-eq" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>
        <div class="mbar-info">
            <p id="mbar-title" class="mbar-title">Carregando...</p>
            <p class="mbar-artist">Nossa Música</p>
        </div>
    </div>

    <!-- Centro: controles + progresso -->
    <div class="mbar-center">
        <div class="mbar-controls">
            <button id="mbar-loop" class="mbar-ctrl-btn active" title="Loop ativado">
                <i class="bi bi-repeat"></i>
            </button>
            <button id="mbar-play" class="mbar-ctrl-btn mbar-play-btn" title="Play">
                <i class="bi bi-play-fill"></i>
            </button>
            <button id="mbar-mute" class="mbar-ctrl-btn" title="Silenciar">
                <i class="bi bi-volume-up-fill"></i>
            </button>
        </div>
        <div class="mbar-progress">
            <span id="mbar-cur" class="mbar-time">0:00</span>
            <div class="mbar-track-wrap">
                <div class="mbar-track-bg"></div>
                <div id="mbar-track-fill" class="mbar-track-fill" style="width:0%"></div>
                <input id="mbar-seek" class="mbar-range" type="range" min="0" max="1000" value="0" step="1">
            </div>
            <span id="mbar-dur" class="mbar-time">0:00</span>
        </div>
    </div>

    <!-- Direita: volume -->
    <div class="mbar-right">
        <i class="bi bi-volume-up mbar-vol-icon"></i>
        <div class="mbar-track-wrap mbar-vol-wrap">
            <div class="mbar-track-bg"></div>
            <div id="mbar-vol-fill" class="mbar-track-fill" style="width:80%"></div>
            <input id="mbar-vol" class="mbar-range" type="range" min="0" max="100" value="80" step="1">
        </div>
    </div>

</div>

<!-- Player Mobile (botão flutuante) -->
<div id="mbar-mobile" class="mbar-mobile">

    <div id="mbar-mobile-panel" class="mbar-mobile-panel">
        <div class="mbar-mobile-panel-inner">
            <img class="mbar-mobile-thumb" src="https://img.youtube.com/vi/XH9AkFPHPmM/mqdefault.jpg" alt="Capa">
            <div class="mbar-mobile-info">
                <p id="mbar-mobile-title" class="mbar-mobile-title">Nossa Música</p>
                <div class="mbar-mobile-track-wrap">
                    <div class="mbar-track-bg"></div>
                    <div id="mbar-mobile-fill" class="mbar-track-fill" style="width:0%"></div>
                    <input id="mbar-mobile-seek" class="mbar-range" type="range" min="0" max="1000" value="0" step="1">
                </div>
            </div>
            <button id="mbar-mobile-play" class="mbar-mobile-play" title="Play">
                <i class="bi bi-play-fill"></i>
            </button>
        </div>
    </div>

    <button id="mbar-mobile-btn" class="mbar-mobile-btn" title="Nossa música">
        <div id="mbar-mobile-eq" class="mbar-mobile-eq" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <i class="bi bi-music-note-beamed mbar-mobile-icon"></i>
    </button>

</div>

<script>
(function () {
    const VIDEO_ID = 'XH9AkFPHPmM';
    let yt = null, playing = false, looping = true, muted = false, vol = 80;
    let progressTimer = null, seeking = false, soundUnlocked = false;

    const playBtn   = document.getElementById('mbar-play');
    const loopBtn   = document.getElementById('mbar-loop');
    const muteBtn   = document.getElementById('mbar-mute');
    const seekInput = document.getElementById('mbar-seek');
    const seekFill  = document.getElementById('mbar-track-fill');
    const volInput  = document.getElementById('mbar-vol');
    const volFill   = document.getElementById('mbar-vol-fill');
    const curEl     = document.getElementById('mbar-cur');
    const durEl     = document.getElementById('mbar-dur');
    const titleEl   = document.getElementById('mbar-title');
    const eq        = document.getElementById('mbar-eq');

    function fmtTime(s) {
        s = Math.floor(s || 0);
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    function setPlayState(val) {
        playing = val;
        playBtn.querySelector('i').className = val ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        eq.classList.toggle('playing', val);
    }

    function updateProgress() {
        if (!yt || seeking) return;
        try {
            const cur = yt.getCurrentTime() || 0;
            const dur = yt.getDuration() || 0;
            if (!dur) return;
            const pct = cur / dur;
            seekInput.value = Math.round(pct * 1000);
            seekFill.style.width = (pct * 100) + '%';
            curEl.textContent = fmtTime(cur);
        } catch (e) {}
    }

    function startProgress() {
        clearInterval(progressTimer);
        progressTimer = setInterval(updateProgress, 500);
    }

    /* YouTube IFrame API */
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(tag);

    function showToast() {
        const t = document.createElement('div');
        t.className = 'mbar-toast';
        t.innerHTML = '<i class="bi bi-music-note-beamed"></i> Toque em qualquer lugar para ouvir a música';
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('show'); });
        setTimeout(function () {
            t.classList.remove('show');
            setTimeout(function () { t.remove(); }, 400);
        }, 4000);
    }

    function unlockSound() {
        if (soundUnlocked) return;
        if (!yt) return; /* player ainda não pronto — mantém listener ativo para tentar de novo */
        soundUnlocked = true;
        document.removeEventListener('click',      unlockSound);
        document.removeEventListener('touchstart', unlockSound);
        document.removeEventListener('keydown',    unlockSound);
        yt.unMute();
        yt.setVolume(vol);
        yt.playVideo();
        muted = false;
        muteBtn.querySelector('i').className = 'bi bi-volume-up-fill';
        muteBtn.classList.remove('mbar-muted');
    }

    window.onYouTubeIframeAPIReady = function () {
        yt = new YT.Player('mbar-yt-container', {
            videoId: VIDEO_ID,
            width: 1, height: 1,
            playerVars: {
                autoplay: 1, mute: 1,
                controls: 0, disablekb: 1,
                fs: 0, iv_load_policy: 3,
                modestbranding: 1, rel: 0,
                loop: 1, playlist: VIDEO_ID
            },
            events: {
                onReady: function (e) {
                    e.target.setVolume(vol);
                    const data = e.target.getVideoData();
                    if (data && data.title) titleEl.textContent = data.title;
                    e.target.playVideo();

                    var isMobile = navigator.maxTouchPoints > 0 || 'ontouchstart' in window;

                    if (isMobile) {
                        /* Mobile: sempre aguarda interação real — sem { once } para não perder o evento se o player ainda não estava pronto */
                        showToast();
                        document.addEventListener('touchstart', unlockSound);
                        document.addEventListener('click',      unlockSound);
                    } else {
                        /* Desktop: usa AudioContext para detectar se áudio está bloqueado */
                        setTimeout(function () {
                            var AC = window.AudioContext || window.webkitAudioContext;
                            var blocked = false;
                            if (AC) {
                                try { var ctx = new AC(); blocked = ctx.state === 'suspended'; ctx.close(); } catch (_) { blocked = true; }
                            }
                            if (blocked) {
                                showToast();
                                document.addEventListener('click',   unlockSound);
                                document.addEventListener('keydown', unlockSound);
                            } else {
                                e.target.unMute();
                                e.target.setVolume(vol);
                                soundUnlocked = true;
                                muted = false;
                                muteBtn.querySelector('i').className = 'bi bi-volume-up-fill';
                                muteBtn.classList.remove('mbar-muted');
                            }
                        }, 800);
                    }
                },
                onStateChange: function (e) {
                    if (e.data === YT.PlayerState.PLAYING) {
                        setPlayState(true);
                        durEl.textContent = fmtTime(yt.getDuration());
                        startProgress();
                    } else if (e.data === YT.PlayerState.PAUSED) {
                        setPlayState(false);
                        clearInterval(progressTimer);
                    } else if (e.data === YT.PlayerState.ENDED) {
                        looping ? yt.playVideo() : setPlayState(false);
                    }
                }
            }
        });
    };

    /* Play / Pause */
    playBtn.addEventListener('click', function () {
        if (!yt) return;
        playing ? yt.pauseVideo() : yt.playVideo();
    });

    /* Seek */
    seekInput.addEventListener('mousedown',  function () { seeking = true; });
    seekInput.addEventListener('touchstart', function () { seeking = true; });
    seekInput.addEventListener('input', function () {
        const pct = this.value / 1000;
        seekFill.style.width = (pct * 100) + '%';
        if (yt) curEl.textContent = fmtTime((yt.getDuration() || 0) * pct);
    });
    seekInput.addEventListener('change', function () {
        seeking = false;
        if (!yt) return;
        yt.seekTo((yt.getDuration() || 0) * (this.value / 1000), true);
    });

    /* Mute */
    muteBtn.addEventListener('click', function () {
        if (!yt) return;
        muted = !muted;
        muted ? yt.mute() : (yt.unMute(), yt.setVolume(vol));
        muteBtn.querySelector('i').className = muted ? 'bi bi-volume-mute-fill' : 'bi bi-volume-up-fill';
        muteBtn.classList.toggle('mbar-muted', muted);
    });

    /* Volume */
    volInput.addEventListener('input', function () {
        vol = parseInt(this.value);
        volFill.style.width = vol + '%';
        if (yt) yt.setVolume(vol);
        if (muted && vol > 0) {
            muted = false;
            if (yt) yt.unMute();
            muteBtn.querySelector('i').className = 'bi bi-volume-up-fill';
            muteBtn.classList.remove('mbar-muted');
        }
    });

    /* Loop */
    loopBtn.addEventListener('click', function () {
        looping = !looping;
        loopBtn.classList.toggle('active', looping);
        loopBtn.title = looping ? 'Loop ativado' : 'Loop desativado';
        if (yt) yt.setLoop(looping);
    });

    /* ── Mobile: sincroniza botão flutuante ── */
    const mobileBtn      = document.getElementById('mbar-mobile-btn');
    const mobilePanel    = document.getElementById('mbar-mobile-panel');
    const mobilePlayBtn  = document.getElementById('mbar-mobile-play');
    const mobileTitleEl  = document.getElementById('mbar-mobile-title');
    const mobileFill     = document.getElementById('mbar-mobile-fill');
    const mobileSeek     = document.getElementById('mbar-mobile-seek');
    const mobileEq       = document.getElementById('mbar-mobile-eq');
    const mobileCont     = document.getElementById('mbar-mobile');

    mobileBtn.addEventListener('click', function () {
        mobileCont.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!mobileCont.contains(e.target)) mobileCont.classList.remove('open');
    });

    mobilePlayBtn.addEventListener('click', function () {
        if (!yt) return;
        playing ? yt.pauseVideo() : yt.playVideo();
    });

    mobileSeek.addEventListener('mousedown',  function () { seeking = true; });
    mobileSeek.addEventListener('touchstart', function () { seeking = true; }, { passive: true });
    mobileSeek.addEventListener('input', function () {
        const pct = this.value / 1000;
        mobileFill.style.width = (pct * 100) + '%';
    });
    mobileSeek.addEventListener('change', function () {
        seeking = false;
        if (!yt) return;
        yt.seekTo((yt.getDuration() || 0) * (this.value / 1000), true);
    });

    /* Mantém mobile em sincronia com o estado do player */
    const _origSetPlayState = setPlayState;
    setPlayState = function (val) {
        _origSetPlayState(val);
        mobilePlayBtn.querySelector('i').className = val ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        mobileEq.classList.toggle('playing', val);
    };

    const _origUpdateProgress = updateProgress;
    updateProgress = function () {
        _origUpdateProgress();
        if (!yt || seeking) return;
        try {
            const cur = yt.getCurrentTime() || 0;
            const dur = yt.getDuration() || 0;
            if (!dur) return;
            mobileSeek.value = Math.round((cur / dur) * 1000);
            mobileFill.style.width = ((cur / dur) * 100) + '%';
        } catch (e) {}
    };

    /* Sincroniza título no mobile */
    const _titleObserver = new MutationObserver(function () {
        if (mobileTitleEl) mobileTitleEl.textContent = titleEl.textContent;
    });
    _titleObserver.observe(titleEl, { childList: true, characterData: true, subtree: true });
}());
</script>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/src/js/base.js"></script>
<?php foreach ($pageJS ?? [] as $js):
    $jsPath = ROOT_DIR . '/src/js/' . $js;
    $jsV    = is_file($jsPath) ? filemtime($jsPath) : time();
?>
<script src="<?= BASE_URL . '/src/js/' . htmlspecialchars($js) . '?v=' . $jsV ?>"></script>
<?php endforeach; ?>
</body>
</html>
