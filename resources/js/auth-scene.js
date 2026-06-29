/*
 | Auth twilight scene — procedural grass + interactive fireflies.
 | ---------------------------------------------------------------
 | Loaded only on the guest auth portal (components/portal-layout.blade.php).
 | Vanilla <canvas>, no dependencies. Draws (back→front): twinkling stars,
 | moon/sun transition, additive-glow fireflies that steer away from the cursor,
 | realistic tumbling leaves (day mode, also cursor-interactive), and layered
 | bezier-blade grass swaying with traveling wind gusts.
 |
 | Supports day/night toggle with cinematic cross-fade transition.
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
    let leaves = [];
    let leafSprites = [];
    let glowSprite = null;

    const LEAF_SPRITE_SIZE = 22; // px — sprites pre-rendered at this size, scaled per leaf
    let moonSprite = null;
    let moonData = null;

    // Pointer in CSS pixels; inactive until the user actually moves.
    const pointer = { x: -9999, y: -9999, active: false };

    // ── Day/Night transition state ──
    let sceneMode = 'dark';          // 'dark' | 'light'
    let themeTransTimer = null;
    let transitionT = 0;             // 0 = full dark, 1 = full light
    const TRANSITION_DURATION = 2500; // ms — matches CSS sky transition
    let transitionDir = 0;           // +1 going light, -1 going dark, 0 idle

    // Grass depth layers: far (cool, short) → near (warm, tall, thick).
    // Roots sit BELOW the viewport (`drop`) so the planting line never shows.
    const LAYERS = [
        { drop: 16, hMin: 70,  hMax: 120, wMin: 3.5, wMax: 5.5, spacing: 5, amp: 10, lean: 10, root: '#0c1a16', tip: '#23463a', alpha: 0.85 },
        { drop: 30, hMin: 105, hMax: 175, wMin: 5.0, wMax: 8.0, spacing: 6, amp: 16, lean: 14, root: '#102019', tip: '#315f44', alpha: 0.92 },
        { drop: 48, hMin: 150, hMax: 240, wMin: 7.5, wMax: 12,  spacing: 8, amp: 24, lean: 18, root: '#16271c', tip: '#4a7a4e', alpha: 1.0 },
    ];

    const rand = (a, b) => a + Math.random() * (b - a);

    // ── Helpers ──
    function smoothstep(t) {
        return t * t * (3 - 2 * t);
    }

    function lerpColor(a, b, t) {
        return [
            Math.round(a[0] + (b[0] - a[0]) * t),
            Math.round(a[1] + (b[1] - a[1]) * t),
            Math.round(a[2] + (b[2] - a[2]) * t),
        ];
    }

    // ── Moon ──
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

    // ── Leaf system ──
    const LEAF_DODGE_R = 115;
    const LEAF_COLORS = [
        '#c0392b', '#e74c3c',  // crimson / rose
        '#e67e22', '#d35400',  // burnt orange
        '#f39c12', '#e8b84b',  // amber
        '#8e9b3a', '#52b788',  // olive / sage (fresh)
        '#7d5a3c', '#a0714f',  // brown
    ];

    function spawnLeaf(randomY) {
        return {
            x:         Math.random() * (W + 120) - 60,
            y:         randomY ? Math.random() * H : rand(-35, -5),
            vx:        rand(-0.35, 0.38),
            vy:        rand(0.55, 1.25),
            rot:       Math.random() * Math.PI * 2,
            rotSpeed:  rand(-0.024, 0.024),
            tilt:      Math.random() * Math.PI * 2,  // 3-D tumble angle
            tiltSpeed: rand(-0.018, 0.018),
            size:      rand(9, 20),
            alpha:     rand(0.72, 0.96),
            colorIdx:  Math.floor(Math.random() * LEAF_COLORS.length),
            swayPhase: Math.random() * Math.PI * 2,
            swayAmp:   rand(0.4, 1.1),
        };
    }

    function buildLeaves() {
        const count = Math.min(40, Math.round(W / 36));
        leaves = Array.from({ length: count }, () => spawnLeaf(true));
    }

    function updateLeaves(t, dtf) {
        for (const lf of leaves) {
            // Wind sway
            lf.vx += lf.swayAmp * 0.008 * Math.sin(t * 0.0009 + lf.swayPhase) * dtf;

            // Gravity — face-on leaf catches more air → floats more
            const face = Math.abs(Math.cos(lf.tilt));
            lf.vy += (0.011 + face * 0.009) * dtf;

            // Cursor dodge + flutter (same force model as fireflies)
            if (pointer.active) {
                const dx = lf.x - pointer.x, dy = lf.y - pointer.y;
                const d2 = dx * dx + dy * dy;
                if (d2 < LEAF_DODGE_R * LEAF_DODGE_R) {
                    const d = Math.sqrt(d2) || 1;
                    const force = (1 - d / LEAF_DODGE_R) * 2.4;
                    lf.vx += (dx / d) * force;
                    lf.vy += (dy / d) * force * 0.55;
                    lf.rotSpeed  += rand(-0.045, 0.045);
                    lf.tiltSpeed += rand(-0.035, 0.035);
                }
            }

            // Air drag: face-on = more resistance
            lf.vx *= 0.976 - face * 0.007;
            lf.vy *= 0.981 - face * 0.005;

            // Speed cap
            const sp = Math.hypot(lf.vx, lf.vy);
            if (sp > 4.2) { lf.vx = lf.vx / sp * 4.2; lf.vy = lf.vy / sp * 4.2; }

            lf.x        += lf.vx        * dtf;
            lf.y        += lf.vy        * dtf;
            lf.rot      += lf.rotSpeed  * dtf;
            lf.tilt     += lf.tiltSpeed * dtf;

            if (lf.y > H + 55 || lf.x < -110 || lf.x > W + 110) {
                Object.assign(lf, spawnLeaf(false));
            }
        }
    }

    // Draws a leaf centered at (0,0) onto the given 2D context — used for both
    // offscreen sprite baking and (if needed) direct canvas drawing.
    function drawLeafShape(c, size, color) {
        // Body: pointed tip → wide belly → base notch
        c.fillStyle = color;
        c.beginPath();
        c.moveTo(0, -size);
        c.bezierCurveTo( size * 0.54, -size * 0.56,  size * 0.88,  size * 0.09,  size * 0.09,  size * 0.53);
        c.lineTo(0,  size * 0.64);
        c.lineTo(-size * 0.09,  size * 0.53);
        c.bezierCurveTo(-size * 0.88,  size * 0.09, -size * 0.54, -size * 0.56, 0, -size);
        c.fill();

        // Specular highlight (upper-left lobe)
        c.fillStyle = 'rgba(255,255,255,0.14)';
        c.beginPath();
        c.moveTo(0, -size);
        c.bezierCurveTo( size * 0.22, -size * 0.54,  size * 0.28, -size * 0.04, 0,  size * 0.26);
        c.bezierCurveTo(-size * 0.16, -size * 0.06, -size * 0.12, -size * 0.50, 0, -size);
        c.fill();

        // Midrib vein
        c.strokeStyle = 'rgba(0,0,0,0.20)';
        c.lineWidth   = size * 0.058;
        c.lineCap     = 'round';
        c.beginPath();
        c.moveTo(0, -size * 0.86);
        c.lineTo(0,  size * 0.56);
        c.stroke();

        // 3 pairs of side veins (curved, fanning outward)
        c.lineWidth   = size * 0.030;
        c.strokeStyle = 'rgba(0,0,0,0.13)';
        for (let i = 0; i < 3; i++) {
            const nt = (i + 1) / 4.2;
            const vy = -size + size * 1.45 * nt;
            const vx = size * (0.56 - nt * 0.14);
            const ey = vy + size * 0.24;
            c.beginPath();
            c.moveTo(0, vy);
            c.quadraticCurveTo( vx * 0.45, vy + size * 0.09,  vx, ey);
            c.moveTo(0, vy);
            c.quadraticCurveTo(-vx * 0.45, vy + size * 0.09, -vx, ey);
            c.stroke();
        }

        // Short stem
        c.strokeStyle = 'rgba(0,0,0,0.28)';
        c.lineWidth   = size * 0.065;
        c.beginPath();
        c.moveTo(0, size * 0.62);
        c.lineTo(0, size * 0.88);
        c.stroke();
    }

    // Pre-bake one offscreen sprite per color at LEAF_SPRITE_SIZE.
    // Called once at init — sprites don't depend on W/H.
    function buildLeafSprites() {
        const s = LEAF_SPRITE_SIZE;
        leafSprites = LEAF_COLORS.map(color => {
            const oc = document.createElement('canvas');
            oc.width  = s * 2;
            oc.height = s * 2;
            const og  = oc.getContext('2d');
            og.translate(s, s); // leaf origin at canvas centre
            drawLeafShape(og, s, color);
            return oc;
        });
    }

    // 40 drawImage calls instead of 280 path/stroke calls.
    function drawLeaves(t, ease) {
        if (ease < 0.01 || !leafSprites.length) return;
        const SD = LEAF_SPRITE_SIZE * 2; // sprite canvas dimension
        for (const lf of leaves) {
            const scaleXY = lf.size / LEAF_SPRITE_SIZE;
            const sw = SD * scaleXY * Math.abs(Math.cos(lf.tilt));
            if (sw < 0.5) continue; // edge-on — invisible, skip drawImage
            const sh = SD * scaleXY;
            ctx.save();
            ctx.globalAlpha = lf.alpha * ease;
            ctx.translate(lf.x, lf.y);
            ctx.rotate(lf.rot);
            ctx.drawImage(leafSprites[lf.colorIdx], -sw / 2, -sh / 2, sw, sh);
            ctx.restore();
        }
        ctx.globalAlpha = 1;
    }

    // ── Build scene ──
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
        buildLeaves();
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

    function drawGrass(t, ease) {
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
        // Day-mode grass tint overlay
        if (ease > 0) {
            ctx.globalCompositeOperation = 'source-over';
            ctx.fillStyle = `rgba(150,255,100,${ease * 0.12})`;
            for (const L of LAYERS) {
                const rootY = H + L.drop;
                ctx.fillRect(0, rootY - L.hMax, W, L.hMax);
            }
        }
        ctx.globalAlpha = 1;
        ctx.globalCompositeOperation = 'source-over';
    }

    function drawStars(t, ease) {
        ctx.fillStyle = '#fff';
        const alpha = 1 - ease;
        for (const s of stars) {
            const tw = s.base * (0.6 + 0.4 * Math.sin(t * s.tw + s.ph));
            ctx.globalAlpha = tw * alpha;
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    function drawMoon(t, ease) {
        if (!moonData || !moonSprite) return;
        const { x, r } = moonData;
        const yFull = H * 0.14;
        const y = yFull + (H - yFull + r + 20) * ease; // sinks below horizon
        const pulse = 0.88 + 0.12 * Math.sin(t * 0.00035);
        const alpha = 1 - ease;

        // Atmospheric halo (additive so it blends into the sky glow)
        ctx.globalCompositeOperation = 'lighter';
        const haloR = r * 4.5;
        const halo = ctx.createRadialGradient(x, y, r * 0.2, x, y, haloR);
        halo.addColorStop(0,    `rgba(255,248,210,${0.15 * pulse * alpha})`);
        halo.addColorStop(0.35, `rgba(220,205,165,${0.06 * pulse * alpha})`);
        halo.addColorStop(1,    'rgba(170,160,120,0)');
        ctx.fillStyle = halo;
        ctx.beginPath();
        ctx.arc(x, y, haloR, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';

        // Crescent sprite
        const half = moonSprite.width / 2;
        ctx.globalAlpha = (0.90 + 0.08 * pulse) * alpha;
        ctx.drawImage(moonSprite, x - half, y - half);
        ctx.globalAlpha = 1;
    }

    function drawSun(t, ease) {
        if (ease < 0.01 || !moonData) return;
        const r = moonData.r * 1.15;
        const x = moonData.x;
        const yFull = H * 0.14;
        const y = yFull + (H - yFull + r + 20) * (1 - ease); // rises from below

        // Outer glow — warm at sunrise, bright at noon
        const glowColor = lerpColor([255,160,60], [255,240,120], ease);

        ctx.globalCompositeOperation = 'lighter';
        const haloR = r * 5;
        const halo = ctx.createRadialGradient(x, y, r * 0.3, x, y, haloR);
        halo.addColorStop(0,    `rgba(${glowColor},${0.28 * ease})`);
        halo.addColorStop(0.4,  `rgba(${glowColor},${0.10 * ease})`);
        halo.addColorStop(1,    'rgba(255,200,80,0)');
        ctx.fillStyle = halo;
        ctx.beginPath(); ctx.arc(x, y, haloR, 0, Math.PI*2); ctx.fill();
        ctx.globalCompositeOperation = 'source-over';

        // Sun disc
        const disc = ctx.createRadialGradient(x - r*0.15, y - r*0.15, r*0.05, x, y, r);
        disc.addColorStop(0, '#fffde0');
        disc.addColorStop(0.6, '#ffe680');
        disc.addColorStop(1, '#ffc040');
        ctx.fillStyle = disc;
        ctx.globalAlpha = ease;
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI*2); ctx.fill();
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

    function drawFlies(t, ease) {
        const alpha = 1 - ease;
        if (alpha < 0.01) return;
        ctx.globalCompositeOperation = 'lighter';
        for (const f of flies) {
            const pulse = 0.5 + 0.5 * Math.sin(t * f.pulseSpeed + f.pulsePhase);
            const a = f.baseAlpha * (0.35 + 0.65 * pulse) * alpha;
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
        const ease = smoothstep(transitionT);

        drawStars(t, ease);
        drawMoon(t, ease);
        drawSun(t, ease);
        if (!reduceMotion && ease < 1) updateFlies(t, dtf);
        drawFlies(t, ease);
        if (!reduceMotion) updateLeaves(t, dtf);
        drawLeaves(t, ease);
        drawGrass(t, ease);
    }

    // Loop with delta time, paused while the tab is hidden.
    let last = performance.now();
    let running = false;

    function loop(now) {
        if (!running) return;
        const dtf = Math.min(2.5, (now - last) / 16.67); // normalize to ~60fps steps
        last = now;

        // Advance transition
        if (transitionDir !== 0) {
            transitionT = Math.max(0, Math.min(1, transitionT + transitionDir * (dtf * 16.67 / TRANSITION_DURATION)));
            if (transitionT <= 0 || transitionT >= 1) transitionDir = 0;
        }

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

    // ── Toggle event wiring ──
    document.getElementById('scene-toggle')?.addEventListener('click', () => {
        sceneMode = sceneMode === 'dark' ? 'light' : 'dark';
        transitionDir = sceneMode === 'light' ? 1 : -1;

        // Sky layers
        document.getElementById('sky-night').style.opacity = sceneMode === 'light' ? '0' : '1';
        document.getElementById('sky-day').style.opacity   = sceneMode === 'light' ? '1' : '0';

        // Flip theme tokens with a crossfade window
        document.documentElement.classList.add('theme-transition');
        clearTimeout(themeTransTimer);
        themeTransTimer = setTimeout(() => document.documentElement.classList.remove('theme-transition'), 700);
        document.documentElement.classList.toggle('dark', sceneMode === 'dark');

        // Icon swap
        document.getElementById('toggle-icon-sun').classList.toggle('hidden', sceneMode === 'light');
        document.getElementById('toggle-icon-moon').classList.toggle('hidden', sceneMode === 'dark');
    });

    makeGlowSprite();
    buildLeafSprites();
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
