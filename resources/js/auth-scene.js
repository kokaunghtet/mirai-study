/*
 | Auth twilight scene — procedural grass + interactive fireflies.
 | ---------------------------------------------------------------
 | Loaded only on the guest auth portal (components/portal-layout.blade.php).
 | Vanilla <canvas>, no dependencies. Draws (back→front): twinkling stars,
 | additive-glow fireflies that steer away from the cursor, and layered
 | bezier-blade grass swaying with traveling wind gusts.
 |
 | Respects prefers-reduced-motion (renders one calm static frame) and pauses
 | the loop while the tab is hidden.
 */
function initAuthScene() {
    const canvas = document.getElementById('auth-scene');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let W = 0, H = 0, dpr = 1;
    let blades = [];   // grass, grouped by depth layer
    let stars = [];
    let flies = [];
    let glowSprite = null;
    let moonSprite = null;
    let moonData = null;

    // Pointer in CSS pixels; inactive until the user actually moves.
    const pointer = { x: -9999, y: -9999, active: false };

    // Grass depth layers: far (cool, short) → near (warm, tall, thick).
    // Roots sit BELOW the viewport (`drop`) so the planting line never shows.
    const LAYERS = [
        { drop: 16, hMin: 70,  hMax: 120, wMin: 3.5, wMax: 5.5, spacing: 5, amp: 10, lean: 10, root: '#0c1a16', tip: '#23463a', alpha: 0.85 },
        { drop: 30, hMin: 105, hMax: 175, wMin: 5.0, wMax: 8.0, spacing: 6, amp: 16, lean: 14, root: '#102019', tip: '#315f44', alpha: 0.92 },
        { drop: 48, hMin: 150, hMax: 240, wMin: 7.5, wMax: 12,  spacing: 8, amp: 24, lean: 18, root: '#16271c', tip: '#4a7a4e', alpha: 1.0 },
    ];

    const rand = (a, b) => a + Math.random() * (b - a);

    function buildMoon() {
        const r = Math.max(24, Math.min(44, W * 0.033));
        moonData = { x: W * 0.78, y: H * 0.14, r };

        const size = Math.ceil((r + 12) * 2);
        const mc = document.createElement('canvas');
        mc.width = mc.height = size;
        const mg = mc.getContext('2d');
        const cx = size / 2, cy = size / 2;

        const grad = mg.createRadialGradient(cx - r * 0.22, cy - r * 0.22, r * 0.08, cx, cy, r);
        grad.addColorStop(0,   '#fffef4');
        grad.addColorStop(0.5, '#f6e9cf');
        grad.addColorStop(1,   '#ddc898');
        mg.fillStyle = grad;
        mg.beginPath();
        mg.arc(cx, cy, r, 0, Math.PI * 2);
        mg.fill();

        // Crescent cut
        mg.globalCompositeOperation = 'destination-out';
        mg.beginPath();
        mg.arc(cx + r * 0.42, cy - r * 0.04, r * 0.82, 0, Math.PI * 2);
        mg.fill();

        moonSprite = mc;
    }

    function buildScene() {
        // Grass
        blades = LAYERS.map((L) => {
            const arr = [];
            for (let x = -20; x < W + 20; x += L.spacing) {
                arr.push({
                    x: x + rand(-L.spacing * 0.4, L.spacing * 0.4),
                    h: rand(L.hMin, L.hMax),
                    w: rand(L.wMin, L.wMax),
                    lean: rand(-L.lean, L.lean),
                    phase: Math.random() * Math.PI * 2,
                    stiff: rand(0.7, 1.25),
                    a: rand(L.alpha * 0.8, L.alpha),
                });
            }
            return arr;
        });

        // Stars (upper half only)
        stars = [];
        const count = Math.round((W * H) / 14000);
        for (let i = 0; i < count; i++) {
            stars.push({
                x: Math.random() * W,
                y: Math.random() * H * 0.55,
                r: rand(0.4, 1.4),
                base: rand(0.2, 0.7),
                tw: rand(0.001, 0.003),
                ph: Math.random() * Math.PI * 2,
            });
        }

        // Fireflies
        flies = [];
        const flyCount = Math.min(30, Math.round(W / 46));
        for (let i = 0; i < flyCount; i++) {
            flies.push({
                x: Math.random() * W,
                y: rand(H * 0.25, H * 0.9),
                vx: rand(-0.3, 0.3),
                vy: rand(-0.3, 0.3),
                size: rand(1.6, 3.2),
                baseAlpha: rand(0.5, 1),
                pulseSpeed: rand(0.0012, 0.0028),
                pulsePhase: Math.random() * Math.PI * 2,
            });
        }

        buildMoon();
    }

    // Pre-rendered radial glow sprite (drawn additively for bloom).
    function makeGlowSprite() {
        const s = 64;
        const c = document.createElement('canvas');
        c.width = c.height = s;
        const g = c.getContext('2d');
        const grad = g.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
        grad.addColorStop(0.0, 'rgba(231,255,150,0.95)');
        grad.addColorStop(0.25, 'rgba(200,240,120,0.55)');
        grad.addColorStop(0.6, 'rgba(150,200,90,0.16)');
        grad.addColorStop(1.0, 'rgba(150,200,90,0)');
        g.fillStyle = grad;
        g.fillRect(0, 0, s, s);
        glowSprite = c;
    }

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = Math.round(W * dpr);
        canvas.height = Math.round(H * dpr);
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0); // draw in CSS pixels
        buildScene();
    }

    // Wind: summed sines (cheap, organic) with gusts that travel across x.
    function windAt(t, x, phase) {
        const travel = Math.sin(t * 0.0011 - x * 0.006) * 0.6
                     + Math.sin(t * 0.0021 - x * 0.011 + 1.3) * 0.4;
        const local = Math.sin(t * 0.002 + phase) * 0.35;
        const gust = 0.65 + 0.35 * Math.sin(t * 0.00033)
                   + 0.5 * Math.pow(Math.max(0, Math.sin(t * 0.00017 - x * 0.0012)), 3);
        return (travel + local) * gust;
    }

    function drawGrass(t) {
        LAYERS.forEach((L, li) => {
            const rootY = H + L.drop; // base sits below the viewport — roots hidden
            const grad = ctx.createLinearGradient(0, rootY - L.hMax, 0, rootY);
            grad.addColorStop(0, L.tip);
            grad.addColorStop(1, L.root);
            ctx.fillStyle = grad;
            for (const b of blades[li]) {
                const sway = windAt(t, b.x, b.phase) * L.amp * b.stiff;
                const tipX = b.x + b.lean + sway;
                const tipY = rootY - b.h;
                const ctrlX = b.x + (b.lean + sway) * 0.35;
                const ctrlY = rootY - b.h * 0.55;
                const hw = b.w / 2;
                ctx.globalAlpha = b.a;
                ctx.beginPath();
                ctx.moveTo(b.x - hw, rootY);
                ctx.quadraticCurveTo(ctrlX - hw * 0.6, ctrlY, tipX, tipY);
                ctx.quadraticCurveTo(ctrlX + hw * 0.6, ctrlY, b.x + hw, rootY);
                ctx.closePath();
                ctx.fill();
            }
        });
        ctx.globalAlpha = 1;
    }

    function drawStars(t) {
        ctx.fillStyle = '#fff';
        for (const s of stars) {
            const tw = s.base * (0.6 + 0.4 * Math.sin(t * s.tw + s.ph));
            ctx.globalAlpha = tw;
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    function drawMoon(t) {
        if (!moonData || !moonSprite) return;
        const { x, y, r } = moonData;
        const pulse = 0.88 + 0.12 * Math.sin(t * 0.00035);

        // Atmospheric halo (additive so it blends into the sky glow)
        ctx.globalCompositeOperation = 'lighter';
        const haloR = r * 4.5;
        const halo = ctx.createRadialGradient(x, y, r * 0.2, x, y, haloR);
        halo.addColorStop(0,    `rgba(255,248,210,${0.15 * pulse})`);
        halo.addColorStop(0.35, `rgba(220,205,165,${0.06 * pulse})`);
        halo.addColorStop(1,    'rgba(170,160,120,0)');
        ctx.fillStyle = halo;
        ctx.beginPath();
        ctx.arc(x, y, haloR, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';

        // Crescent sprite
        const half = moonSprite.width / 2;
        ctx.globalAlpha = 0.90 + 0.08 * pulse;
        ctx.drawImage(moonSprite, x - half, y - half);
        ctx.globalAlpha = 1;
    }

    const DODGE_R = 130; // px radius the cursor pushes fireflies
    function updateFlies(t, dtf) {
        for (const f of flies) {
            // gentle wander
            f.vx += (Math.random() - 0.5) * 0.06;
            f.vy += (Math.random() - 0.5) * 0.06;

            // dodge the cursor
            if (pointer.active) {
                const dx = f.x - pointer.x, dy = f.y - pointer.y;
                const d2 = dx * dx + dy * dy;
                if (d2 < DODGE_R * DODGE_R) {
                    const d = Math.sqrt(d2) || 1;
                    const force = (1 - d / DODGE_R) * 1.1;
                    f.vx += (dx / d) * force;
                    f.vy += (dy / d) * force;
                }
            }

            // damping + speed clamp
            f.vx *= 0.96; f.vy *= 0.96;
            const sp = Math.hypot(f.vx, f.vy), max = 1.6;
            if (sp > max) { f.vx = f.vx / sp * max; f.vy = f.vy / sp * max; }

            f.x += f.vx * dtf;
            f.y += f.vy * dtf;

            // soft wrap with margin
            const m = 40;
            if (f.x < -m) f.x = W + m; else if (f.x > W + m) f.x = -m;
            if (f.y < -m) f.y = H + m; else if (f.y > H + m) f.y = -m;
        }
    }

    function drawFlies(t) {
        ctx.globalCompositeOperation = 'lighter';
        for (const f of flies) {
            const pulse = 0.5 + 0.5 * Math.sin(t * f.pulseSpeed + f.pulsePhase);
            const a = f.baseAlpha * (0.35 + 0.65 * pulse);
            const r = f.size * (3.4 + 1.2 * pulse); // glow radius
            ctx.globalAlpha = a;
            ctx.drawImage(glowSprite, f.x - r, f.y - r, r * 2, r * 2);
            ctx.globalAlpha = Math.min(1, a + 0.2);
            ctx.beginPath();
            ctx.arc(f.x, f.y, f.size * 0.5, 0, Math.PI * 2);
            ctx.fillStyle = '#f7ffe0';
            ctx.fill();
        }
        ctx.globalAlpha = 1;
        ctx.globalCompositeOperation = 'source-over';
    }

    function renderFrame(t, dtf) {
        ctx.clearRect(0, 0, W, H);
        drawStars(t);
        drawMoon(t);
        if (!reduceMotion) updateFlies(t, dtf);
        drawFlies(t);
        drawGrass(t);
    }

    // Loop with delta time, paused while the tab is hidden.
    let last = performance.now();
    let running = false;

    function loop(now) {
        if (!running) return;
        const dtf = Math.min(2.5, (now - last) / 16.67); // normalize to ~60fps steps
        last = now;
        renderFrame(now, dtf);
        requestAnimationFrame(loop);
    }

    function start() {
        if (running) return;
        running = true;
        last = performance.now();
        requestAnimationFrame(loop);
    }
    function stop() { running = false; }

    window.addEventListener('resize', resize);
    window.addEventListener('pointermove', (e) => {
        pointer.x = e.clientX; pointer.y = e.clientY; pointer.active = true;
    });
    window.addEventListener('pointerleave', () => { pointer.active = false; });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop(); else if (!reduceMotion) start();
    });

    makeGlowSprite();
    resize();

    if (reduceMotion) {
        renderFrame(0, 0); // single calm static frame
    } else {
        start();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthScene);
} else {
    initAuthScene();
}
