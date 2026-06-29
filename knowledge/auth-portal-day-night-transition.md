# Auth Portal Day/Night Mode Transition Plan

## Goal

Add a light/dark toggle to the login/register portal. Dark mode = current twilight scene (moon, fireflies, stars, grass). Light mode = daytime scene (sun, falling leaves, bright sky, grass). Switching triggers a cinematic transition: moon descends below the horizon while the sun rises from it, crossfading sky and particle systems.

---

## Files to Change

| File | What changes |
|---|---|
| `resources/views/components/portal-layout.blade.php` | Toggle button; replace inline body `background` style with two sky-layer divs |
| `resources/js/auth-scene.js` | Add mode state, transition engine, sun drawing, leaf particle system, dimming for stars/fireflies |

No new files. No CSS changes needed (button uses existing Tailwind tokens).

---

## 1. Sky Background

**Problem:** Sky is currently a hardcoded inline `style` on `<body>`. Can't CSS-transition a `background` with two different radial/linear gradients reliably.

**Solution:** Replace the inline style with two absolutely-positioned full-screen `<div>` layers (z-index –1), both `fixed inset-0`. One is the night gradient, one is the day gradient. Cross-fade them by animating `opacity` with `transition-opacity duration-[2500ms]`.

```html
<!-- Night sky (always rendered) -->
<div id="sky-night" class="fixed inset-0 transition-opacity duration-[2500ms]"
     style="z-index:-1; background:
        radial-gradient(120% 80% at 50% 100%, rgba(201,123,107,0.35) 0%, rgba(201,123,107,0) 45%),
        linear-gradient(180deg, #140f2a 0%, #241a47 35%, #3a2a58 65%, #5a3a5e 100%);"></div>

<!-- Day sky (fades in on toggle) -->
<div id="sky-day" class="fixed inset-0 transition-opacity duration-[2500ms]"
     style="z-index:-1; opacity:0; background:
        radial-gradient(120% 80% at 50% 100%, rgba(255,220,150,0.4) 0%, rgba(255,220,150,0) 40%),
        linear-gradient(180deg, #5b9bd5 0%, #87ceeb 40%, #c9e8f5 70%, #fde9c0 100%);"></div>
```

Toggle JS (triggered by button click):
```js
document.getElementById('sky-night').style.opacity = isDay ? '0' : '1';
document.getElementById('sky-day').style.opacity   = isDay ? '1' : '0';
```

---

## 2. Toggle Button

Place in top-right corner of `portal-layout.blade.php`, above the canvas, fixed position so it doesn't scroll with the card:

```html
<button id="scene-toggle"
        class="fixed top-4 right-4 z-20 flex h-9 w-9 items-center justify-center
               rounded-full border border-white/20 bg-surface/40 backdrop-blur-sm
               text-content/70 hover:text-content transition-colors"
        aria-label="Toggle day/night">
    <!-- Lucide sun icon (shown in dark mode) -->
    <i data-lucide="sun" class="h-4 w-4" id="toggle-icon-sun"></i>
    <!-- Lucide moon icon (shown in light mode) -->
    <i data-lucide="moon" class="h-4 w-4 hidden" id="toggle-icon-moon"></i>
</button>
```

On click, dispatch `CustomEvent('authscene:toggle')` and swap icons. The canvas JS listens to this event.

---

## 3. Canvas Transition Engine (`auth-scene.js`)

### State

```js
let sceneMode = 'dark';          // 'dark' | 'light'
let transitionT = 0;             // 0 = full dark, 1 = full light
const TRANSITION_DURATION = 2500; // ms — matches CSS sky transition
let transitionDir = 0;           // +1 going light, -1 going dark, 0 idle
```

Each frame, advance `transitionT`:
```js
transitionT = Math.max(0, Math.min(1, transitionT + transitionDir * (dtf * 16.67 / TRANSITION_DURATION)));
```

`ease = smoothstep(transitionT)` — apply `t*t*(3-2*t)` for easing.

---

## 4. Moon (dark) → Sun (light) Transition

Both share the same screen slot: upper-right (`W*0.78, H*0.14`).

**Moon exit:** as `ease` goes 0→1, moon's Y increases from `H*0.14` to `H + moonR + 20` (sinks below horizon). Alpha fades: `1 - ease`.

**Sun entry:** sun Y goes from `H + sunR + 20` (below horizon) to `H*0.14`. Alpha: `ease`. Sun also starts with a warm sunrise tint (`rgba(255,180,80)`) blending to full bright yellow (`rgba(255,230,100)`) at ease=1.

### Sun drawing
```js
function drawSun(t, ease) {
    if (ease < 0.01) return;
    const r = moonData.r * 1.15; // slightly bigger than moon
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
```

---

## 5. Stars — fade out

In `drawStars(t)`, multiply each star's alpha by `(1 - ease)`. At ease=1, stars invisible.

---

## 6. Fireflies — fade out

In `drawFlies(t)`, multiply `a` by `(1 - ease)`. Skip `updateFlies` entirely when `ease === 1`.

---

## 7. Leaf Particle System (replaces fireflies in day mode)

### Leaf data

```js
let leaves = [];

function buildLeaves() {
    const leafCount = Math.min(35, Math.round(W / 40));
    leaves = Array.from({ length: leafCount }, () => spawnLeaf(true));
}

function spawnLeaf(randomY = false) {
    return {
        x: Math.random() * (W + 100) - 50,
        y: randomY ? Math.random() * H : -20,
        vx: rand(-0.6, 0.2),
        vy: rand(0.6, 1.4),
        rot: Math.random() * Math.PI * 2,
        rotSpeed: rand(-0.018, 0.018),
        size: rand(7, 16),
        alpha: rand(0.55, 0.9),
        color: ['#e8703a','#d4a017','#8fbc6a','#c0392b','#f0a500'][Math.floor(Math.random()*5)],
        swayPhase: Math.random() * Math.PI * 2,
        swayAmp: rand(0.3, 0.9),
    };
}
```

### Leaf update

```js
function updateLeaves(t, dtf) {
    for (const lf of leaves) {
        lf.x  += (lf.vx + lf.swayAmp * Math.sin(t * 0.0008 + lf.swayPhase)) * dtf;
        lf.y  += lf.vy * dtf;
        lf.rot += lf.rotSpeed * dtf;
        if (lf.y > H + 30) Object.assign(lf, spawnLeaf(false)); // recycle at bottom
    }
}
```

### Leaf draw — simple oval rotated

```js
function drawLeaves(t, ease) {
    if (ease < 0.01) return;
    if (!reduceMotion) updateLeaves(t, 1);
    for (const lf of leaves) {
        ctx.save();
        ctx.globalAlpha = lf.alpha * ease;
        ctx.translate(lf.x, lf.y);
        ctx.rotate(lf.rot);
        ctx.fillStyle = lf.color;
        ctx.beginPath();
        ctx.ellipse(0, 0, lf.size, lf.size * 0.45, 0, 0, Math.PI * 2);
        ctx.fill();
        // Leaf midrib
        ctx.strokeStyle = 'rgba(0,0,0,0.15)';
        ctx.lineWidth = 0.7;
        ctx.beginPath();
        ctx.moveTo(-lf.size, 0);
        ctx.lineTo(lf.size, 0);
        ctx.stroke();
        ctx.restore();
    }
    ctx.globalAlpha = 1;
}
```

---

## 8. Grass — day mode tinting

In day mode (`ease > 0`), interpolate grass tip colors toward brighter greens. Achievable by overlaying a `rgba(150,255,100, ease*0.12)` fill on top of the grass layer — cheap, no rebuild needed.

---

## 9. Updated `renderFrame` order

```js
function renderFrame(t, dtf) {
    ctx.clearRect(0, 0, W, H);
    const ease = smoothstep(transitionT);

    drawStars(t, ease);        // fades out as ease→1
    drawMoon(t, ease);         // descends + fades out as ease→1
    drawSun(t, ease);          // rises + fades in as ease→1
    if (!reduceMotion) updateFlies(t, dtf);
    drawFlies(t, ease);        // fades out as ease→1
    drawLeaves(t, ease);       // fades in as ease→1
    drawGrass(t);
}
```

---

## 10. `buildScene` additions

```js
function buildScene() {
    // ... existing grass/stars/flies build ...
    buildMoon();
    buildLeaves();  // ← add
}
```

---

## 11. Event wiring

```js
// In initAuthScene(), after resize():
document.getElementById('scene-toggle')?.addEventListener('click', () => {
    sceneMode = sceneMode === 'dark' ? 'light' : 'dark';
    transitionDir = sceneMode === 'light' ? 1 : -1;

    // Sky layers
    document.getElementById('sky-night').style.opacity = sceneMode === 'light' ? '0' : '1';
    document.getElementById('sky-day').style.opacity   = sceneMode === 'light' ? '1' : '0';

    // Icon swap
    document.getElementById('toggle-icon-sun').classList.toggle('hidden', sceneMode === 'light');
    document.getElementById('toggle-icon-moon').classList.toggle('hidden', sceneMode === 'dark');
});
```

---

## Implementation Order

1. `portal-layout.blade.php` — replace body background with two sky divs + add toggle button
2. `auth-scene.js` — add `transitionT/Dir`, `smoothstep()`, `lerpColor()` helpers
3. Update `drawStars`, `drawMoon` to accept `ease` param
4. Add `drawSun()` 
5. Add leaf system (`buildLeaves`, `spawnLeaf`, `updateLeaves`, `drawLeaves`)
6. Update `renderFrame` to new signature
7. Wire event listener

## Out of Scope

- Persisting the user's day/night preference (localStorage) — add later if needed
- Matching the main app's dark/light theme tokens — portal is self-contained
- Mobile haptics or reduced-motion leaf behavior — leaf system already checks `reduceMotion`
