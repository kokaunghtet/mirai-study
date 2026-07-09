// ── Shared ambient sound engine (Web Audio) ──
// Used by the timer page (pomodoroTimer) and the floating focus pill so a
// session's sound can be restarted after a full page navigation. All sounds
// are synthesised in-browser except "quietplease", which decodes a bundled
// m4a into a cached AudioBuffer.
//
// Autoplay note: a fresh page has no user gesture, so the AudioContext may
// start suspended. start() schedules everything anyway and resumes the
// context on the first pointerdown — the closest a full-reload app can get
// to "music keeps playing" across navigations.
const AmbientSound = {
    _actx: null,          // shared AudioContext (ambient + chime)
    _masterGain: null,    // volume node for ambient sound
    _nodes: [],           // live Web Audio nodes for the active sound
    _noiseBuf: null,      // cached brown-noise buffer
    _mp3Buf: null,        // cached decoded buffer for quietplease.m4a
    _forestInterval: null,
    _pianoInterval: null,
    _resumeHook: null,    // one-time gesture listener for autoplay policy

    current: null,        // active sound key, null when stopped
    volume: 65,

    _ensure() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        if (!this._actx) this._actx = new Ctx();
        if (this._actx.state === "suspended") {
            this._actx.resume().catch(() => {});
            // Still suspended (no gesture yet on this page) — resume on the
            // first interaction anywhere.
            if (!this._resumeHook) {
                this._resumeHook = () => {
                    this._resumeHook = null;
                    this._actx?.resume().catch(() => {});
                };
                document.addEventListener("pointerdown", this._resumeHook, {
                    once: true,
                    capture: true,
                });
            }
        }
        if (!this._masterGain) {
            this._masterGain = this._actx.createGain();
            this._masterGain.gain.value = this.volume / 100;
            this._masterGain.connect(this._actx.destination);
        }
        return this._actx;
    },

    isAudible() {
        return !!(this.current && this._actx && this._actx.state === "running");
    },

    _noiseBuffer(ctx) {
        if (this._noiseBuf) return this._noiseBuf;
        const len = ctx.sampleRate * 2;            // 2s loop
        const buf = ctx.createBuffer(1, len, ctx.sampleRate);
        const data = buf.getChannelData(0);
        let last = 0;                              // brown noise (integrated white)
        for (let i = 0; i < len; i++) {
            const white = Math.random() * 2 - 1;
            last = (last + 0.02 * white) / 1.02;
            data[i] = last * 3.5;
        }
        this._noiseBuf = buf;
        return buf;
    },

    setVolume(value) {
        this.volume = value;
        if (this._masterGain) this._masterGain.gain.value = value / 100;
    },

    start(name, volume = null) {
        this.stop();
        if (!name || name === "silent") return;
        if (volume !== null) this.volume = volume;
        const ctx = this._ensure();
        if (!ctx) return;
        this.setVolume(this.volume);
        this.current = name;
        const master = this._masterGain;
        const nodes = [];

        if (name === "quietplease") {
            const loadAndPlay = async () => {
                if (!this._mp3Buf) {
                    const res = await fetch("/sound/quietplease.m4a");
                    const ab = await res.arrayBuffer();
                    this._mp3Buf = await ctx.decodeAudioData(ab);
                }
                if (this.current !== "quietplease") return;
                const src = ctx.createBufferSource();
                src.buffer = this._mp3Buf;
                src.loop = true;
                const g = ctx.createGain();
                g.gain.value = 0.8;
                src.connect(g); g.connect(master);
                this._nodes = [src, g];
                src.start();
            };
            loadAndPlay();
            return;
        } else if (name === "ocean") {
            // Slow deep swell: dark lowpass, gentle LFO, low gain
            const src = ctx.createBufferSource();
            src.buffer = this._noiseBuffer(ctx);
            src.loop = true;
            const lp = ctx.createBiquadFilter();
            lp.type = "lowpass";
            lp.frequency.value = 500;
            const lp2 = ctx.createBiquadFilter();
            lp2.type = "lowpass";
            lp2.frequency.value = 500;
            const lfo = ctx.createOscillator();
            lfo.type = "sine";
            lfo.frequency.value = 0.07;
            const lfoGain = ctx.createGain();
            lfoGain.gain.value = 0.12;
            const g = ctx.createGain();
            g.gain.value = 0.35;
            lfo.connect(lfoGain);
            lfoGain.connect(g.gain);
            src.connect(lp); lp.connect(lp2); lp2.connect(g); g.connect(master);
            lfo.start(); src.start();
            nodes.push(src, lp, lp2, lfo, lfoGain, g);
        } else if (name === "forest") {
            const g = ctx.createGain();
            g.gain.value = 0.5;
            g.connect(master);
            nodes.push(g);
            const chirp = () => {
                if (!this._forestInterval) return;
                // While suspended, currentTime is frozen — scheduled chirps
                // would pile up and burst on resume. Skip until running.
                if (ctx.state !== "running") return;
                const osc = ctx.createOscillator();
                const cg = ctx.createGain();
                osc.type = "sine";
                const base = 2000 + Math.random() * 3000;
                osc.frequency.setValueAtTime(base, ctx.currentTime);
                osc.frequency.linearRampToValueAtTime(base + 800 * (Math.random() > 0.5 ? 1 : -1), ctx.currentTime + 0.08);
                cg.gain.setValueAtTime(0.0001, ctx.currentTime);
                cg.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
                cg.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.15);
                osc.connect(cg); cg.connect(g);
                osc.start(); osc.stop(ctx.currentTime + 0.2);
            };
            this._forestInterval = setInterval(chirp, 300 + Math.random() * 600);
            chirp();
            const src = ctx.createBufferSource();
            src.buffer = this._noiseBuffer(ctx);
            src.loop = true;
            const lp = ctx.createBiquadFilter();
            lp.type = "lowpass";
            lp.frequency.value = 400;
            const bg = ctx.createGain();
            bg.gain.value = 0.15;
            src.connect(lp); lp.connect(bg); bg.connect(g);
            src.start();
            nodes.push(src, lp, bg);
        } else if (name === "binaural") {
            const g = ctx.createGain();
            g.gain.value = 0.22;
            g.connect(master);
            nodes.push(g);
            [[200, -1], [207, 1]].forEach(([freq, pan]) => {
                const osc = ctx.createOscillator();
                osc.type = "sine";
                osc.frequency.value = freq;
                if (ctx.createStereoPanner) {
                    const panner = ctx.createStereoPanner();
                    panner.pan.value = pan;
                    osc.connect(panner); panner.connect(g);
                    nodes.push(panner);
                } else {
                    osc.connect(g);
                }
                osc.start();
                nodes.push(osc);
            });
        } else if (name === "lofi") {
            const g = ctx.createGain();
            g.gain.value = 0.35;
            g.connect(master);
            nodes.push(g);
            [261.63, 329.63, 392.00].forEach((freq) => {
                const osc = ctx.createOscillator();
                osc.type = "sine";
                osc.frequency.value = freq;
                const og = ctx.createGain();
                og.gain.value = 0.18;
                osc.connect(og); og.connect(g);
                osc.start();
                nodes.push(osc, og);
            });
            const src = ctx.createBufferSource();
            src.buffer = this._noiseBuffer(ctx);
            src.loop = true;
            const hp = ctx.createBiquadFilter();
            hp.type = "highpass";
            hp.frequency.value = 5000;
            const crackle = ctx.createGain();
            crackle.gain.value = 0.06;
            src.connect(hp); hp.connect(crackle); crackle.connect(g);
            src.start();
            nodes.push(src, hp, crackle);
            const lfo = ctx.createOscillator();
            lfo.type = "sine";
            lfo.frequency.value = 0.5;
            const lfoG = ctx.createGain();
            lfoG.gain.value = 0.03;
            lfo.connect(lfoG); lfoG.connect(g.gain);
            lfo.start();
            nodes.push(lfo, lfoG);
        } else if (name === "piano") {
            const g = ctx.createGain();
            g.gain.value = 0.3;
            g.connect(master);
            nodes.push(g);
            const notes = [261.63, 293.66, 329.63, 349.23, 392.00, 440.00, 493.88, 523.25];
            let idx = 0;
            const playNote = () => {
                if (!this._pianoInterval) return;
                if (ctx.state !== "running") return;
                const freq = notes[idx % notes.length];
                idx++;
                const osc = ctx.createOscillator();
                osc.type = "triangle";
                osc.frequency.value = freq;
                const ng = ctx.createGain();
                const now = ctx.currentTime;
                ng.gain.setValueAtTime(0.0001, now);
                ng.gain.exponentialRampToValueAtTime(0.25, now + 0.01);
                ng.gain.exponentialRampToValueAtTime(0.0001, now + 2.5);
                osc.connect(ng); ng.connect(g);
                osc.start(now); osc.stop(now + 2.6);
                const osc2 = ctx.createOscillator();
                osc2.type = "sine";
                osc2.frequency.value = freq * 2;
                const ng2 = ctx.createGain();
                ng2.gain.setValueAtTime(0.0001, now);
                ng2.gain.exponentialRampToValueAtTime(0.06, now + 0.01);
                ng2.gain.exponentialRampToValueAtTime(0.0001, now + 1.2);
                osc2.connect(ng2); ng2.connect(g);
                osc2.start(now); osc2.stop(now + 1.3);
            };
            this._pianoInterval = setInterval(playNote, 1800 + Math.random() * 1200);
            playNote();
        }
        this._nodes = nodes;
    },

    stop() {
        clearInterval(this._forestInterval);
        clearInterval(this._pianoInterval);
        this._forestInterval = null;
        this._pianoInterval = null;
        (this._nodes || []).forEach((n) => {
            try { if (n.stop) n.stop(); } catch (e) { /* already stopped */ }
            try { n.disconnect(); } catch (e) { /* ignore */ }
        });
        this._nodes = [];
        this.current = null;
    },

    playChime() {
        const ctx = this._ensure();
        if (!ctx) return;
        try {
            const now = ctx.currentTime;
            [880, 1320].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = "sine";
                osc.frequency.value = freq;
                const t = now + i * 0.18;
                gain.gain.setValueAtTime(0.0001, t);
                gain.gain.exponentialRampToValueAtTime(0.3, t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
                osc.connect(gain).connect(ctx.destination);
                osc.start(t);
                osc.stop(t + 0.4);
            });
        } catch (e) { /* ignore */ }
    },
};

export default AmbientSound;
