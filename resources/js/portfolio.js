/*
 | Portfolio runtime — canvas scenes, theme, intro splash and scroll behaviour
 | ported from the "Portafolio Héctor Zamorano" Claude Design project.
 |
 | Every entry point is idempotent and driven by `data-hz-*` attributes, so the
 | same bundle serves the public site, the article pages and the admin panel.
 */

const config = {
    background: true,
    introDuration: 2.5,
    introAlways: true,
    sceneDuration: 8,
};

const INK = '#1f3557';
const MID = '#476b9b';
const SOFT = '#7793b8';
const PALE = '#a9bad4';
const CLAY = '#cf9877';
const CLAY_L = '#e3bda8';
const LIGHT = { x: -0.55, y: -0.5, z: 0.67 };

/** Fibonacci-sphere point cloud with a pseudo-continent mask. */
function globePoints(count) {
    const pts = [];

    for (let i = 0; i < count; i++) {
        const y = 1 - (2 * i) / (count - 1);
        const rr = Math.sqrt(Math.max(0, 1 - y * y));
        const th = i * 2.399963;
        const lat = Math.asin(y);
        const lon = th % 6.28318;
        const n =
            Math.sin(lat * 3.1) +
            Math.sin(lon * 2.3 + 1.7) * 0.9 +
            Math.sin(lat * 5.2 + lon * 1.3) * 0.6 +
            Math.sin((lat + lon) * 4.4) * 0.4;

        pts.push({ y, rr, th, land: n > 0.55, n });
    }

    return pts;
}

function offscreen(width, height) {
    const c = document.createElement('canvas');
    c.width = width;
    c.height = height;

    return c;
}

/* ── theme ────────────────────────────────────────────────────────────── */

function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

/**
 * Persist the choice under the same key the dashboard uses, so the theme
 * carries across the whole site rather than resetting per area.
 *
 * Flux owns that key when its runtime is on the page: assigning to
 * `Flux.appearance` runs its Alpine effect, which writes localStorage and
 * toggles the class. Writing the key behind its back would be undone.
 */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.classList.toggle('dark', theme === 'dark');

    if (window.Flux) {
        window.Flux.appearance = theme;

        return;
    }

    try {
        localStorage.setItem('flux.appearance', theme);
    } catch (e) {
        /* storage unavailable — the attribute alone still themes the page */
    }
}

function bindThemeToggles() {
    document.querySelectorAll('[data-hz-theme-toggle]').forEach((el) => {
        if (el.dataset.hzBound) {
            return;
        }
        el.dataset.hzBound = '1';
        el.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
            syncThemeIcons();
        });
    });

    syncThemeIcons();
}

function syncThemeIcons() {
    const dark = currentTheme() === 'dark';
    document.querySelectorAll('[data-hz-icon="sun"]').forEach((el) => {
        el.hidden = !dark;
    });
    document.querySelectorAll('[data-hz-icon="moon"]').forEach((el) => {
        el.hidden = dark;
    });
}

/* ── intro splash ─────────────────────────────────────────────────────── */
/* ── intro splash ─────────────────────────────────────────────────────── */

/**
 * A dot-globe that draws itself once and steps aside.
 *
 * The land points are a Fibonacci cloud lit from the upper left; a single hair
 * ring marks the equator. Longitude is *subtracted* from the spin so the globe
 * turns west to east — the direction the Earth actually turns as seen from
 * outside, left to right across the face.
 */
function startIntro(root) {
    const canvas = root.querySelector('[data-hz-intro-canvas]');

    if (!canvas) {
        root.remove();

        return;
    }

    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    const cx = W / 2;
    const cy = H / 2;
    const R = W * 0.24;

    const pts = globePoints(1100);
    const tilt = 0.34;
    const ct = Math.cos(tilt);
    const st = Math.sin(tilt);

    const dur = Math.max(1, Math.min(2.5, config.introDuration)) * 1000;
    const t0 = performance.now();
    const ease = (k) => 1 - Math.pow(1 - k, 3);

    const finish = () => {
        try {
            localStorage.setItem('hz_intro_seen_v1', '1');
        } catch (e) {
            /* ignore */
        }
        root.style.opacity = '0';
        setTimeout(() => root.remove(), 800);
    };

    const step = (now) => {
        const elapsed = now - t0;
        const progress = Math.min(1, elapsed / dur);
        const tt = elapsed / 1000;

        // The globe fades up over its first third rather than popping in.
        const entrance = ease(Math.min(1, progress / 0.34));
        const rot = 1.2 + tt * 0.34;

        ctx.clearRect(0, 0, W, H);
        ctx.globalAlpha = entrance;

        // Halo — a breath of warmth so the sphere is not floating on nothing.
        const halo = ctx.createRadialGradient(cx, cy, R * 0.9, cx, cy, R * 1.9);
        halo.addColorStop(0, 'rgba(207,152,119,0.10)');
        halo.addColorStop(1, 'rgba(207,152,119,0)');
        ctx.fillStyle = halo;
        ctx.beginPath();
        ctx.arc(cx, cy, R * 1.9, 0, 7);
        ctx.fill();

        // Body — near-black, just separated from the backdrop.
        const body = ctx.createRadialGradient(cx - R * 0.4, cy - R * 0.4, R * 0.1, cx, cy, R * 1.05);
        body.addColorStop(0, '#1b1f27');
        body.addColorStop(1, '#101319');
        ctx.fillStyle = body;
        ctx.beginPath();
        ctx.arc(cx, cy, R, 0, 7);
        ctx.fill();

        // Land — the only real texture. Points reveal from the top down so the
        // sphere assembles instead of appearing all at once.
        for (const p of pts) {
            if (! p.land) {
                continue;
            }

            const lon = p.th - rot;
            const x = p.rr * Math.cos(lon);
            const z = p.rr * Math.sin(lon);
            const y2 = p.y * ct - z * st;
            const z2 = p.y * st + z * ct;

            if (z2 <= 0.02) {
                continue;
            }

            const reveal = Math.max(0, Math.min(1, (progress - 0.06) * 3 - (0.5 - p.y * 0.5) * 0.5));

            if (reveal <= 0) {
                continue;
            }

            const light = Math.max(0, x * LIGHT.x + y2 * LIGHT.y + z2 * LIGHT.z);
            ctx.globalAlpha = entrance * reveal * (0.2 + light * 0.8);
            ctx.fillStyle = '#edece7';
            const size = 2.6 + light * 2.2;
            ctx.fillRect(cx + x * R - size / 2, cy + y2 * R - size / 2, size, size);
        }

        // Equator — one hair of accent, drawn on once the land is up.
        const ring = Math.max(0, Math.min(1, (progress - 0.3) / 0.5));

        if (ring > 0) {
            // Tilted, the equator projects to an exact ellipse: full radius
            // across, flattened by the tilt vertically. Sweeping the arc from
            // zero draws it on rather than switching it on.
            ctx.globalAlpha = entrance * 0.55;
            ctx.strokeStyle = 'rgba(207,152,119,0.9)';
            ctx.lineWidth = 1.4;
            ctx.beginPath();
            ctx.ellipse(cx, cy, R, R * st, 0, -Math.PI, -Math.PI + 6.28318 * ring);
            ctx.stroke();
        }

        // Limb — a thin edge that keeps the sphere from dissolving into the page.
        ctx.globalAlpha = entrance * 0.22;
        ctx.strokeStyle = '#edece7';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.arc(cx, cy, R, 0, 7);
        ctx.stroke();

        ctx.globalAlpha = 1;

        if (elapsed < dur) {
            requestAnimationFrame(step);
        } else {
            finish();
        }
    };

    requestAnimationFrame(step);
}

/* ── animated background lattice ──────────────────────────────────────── */

function startBackground(cv) {
    if (cv._hzStarted) {
        return;
    }
    cv._hzStarted = true;

    const ctx = cv.getContext('2d');
    const fit = () => {
        const d = Math.min(window.devicePixelRatio || 1, 1.5);
        cv.width = Math.max(1, Math.round(cv.clientWidth * d));
        cv.height = Math.max(1, Math.round(cv.clientHeight * d));
        ctx.setTransform(d, 0, 0, d, 0, 0);
    };

    fit();
    window.addEventListener('resize', fit);

    const t0 = performance.now();
    const draw = (now) => {
        if (!cv.isConnected) {
            cv._hzStarted = false;
            window.removeEventListener('resize', fit);

            return;
        }

        const w = cv.clientWidth;
        const h = cv.clientHeight;
        const t = (now - t0) / 1000;
        const dark = currentTheme() === 'dark';

        ctx.clearRect(0, 0, w, h);

        // Minimal lattice: a sparse dot grid, one slow wave of light crossing it
        const step = 52;
        const ink = dark ? '169,186,212' : '26,29,36';
        const cols = Math.ceil(w / step) + 1;
        const rows = Math.ceil(h / step) + 1;

        for (let i = 0; i < cols; i++) {
            for (let j = 0; j < rows; j++) {
                const x = i * step;
                const y = j * step;
                const wave = Math.sin((x + y) / 420 - t * 0.22);
                const a = (dark ? 0.13 : 0.11) * (0.22 + 0.78 * (0.5 + 0.5 * wave));
                ctx.fillStyle = 'rgba(' + ink + ',' + a.toFixed(3) + ')';
                ctx.fillRect(x, y, 1.5, 1.5);
            }
        }

        requestAnimationFrame(draw);
    };

    requestAnimationFrame(draw);
}

/* ── section wave dividers ────────────────────────────────────────────── */

function startWaveDivider(cv) {
    if (cv._hzStarted) {
        return;
    }
    cv._hzStarted = true;

    const ctx = cv.getContext('2d');
    const W = cv.width;
    const H = cv.height;
    const col = '63,107,86';
    const ph = Math.random() * 20;
    const yAt = (x, tt) =>
        H * 0.5 +
        Math.sin(x * 0.008 + tt * 0.5 + ph) * H * 0.2 +
        Math.sin(x * 0.0032 - tt * 0.32 + ph) * H * 0.16;

    const step = (now) => {
        if (!cv.isConnected) {
            cv._hzStarted = false;

            return;
        }

        const tt = now / 1000;
        ctx.clearRect(0, 0, W, H);

        const g = ctx.createLinearGradient(0, 0, W, 0);
        g.addColorStop(0, 'rgba(' + col + ',0)');
        g.addColorStop(0.12, 'rgba(' + col + ',0.75)');
        g.addColorStop(0.88, 'rgba(' + col + ',0.75)');
        g.addColorStop(1, 'rgba(' + col + ',0)');
        ctx.strokeStyle = g;
        ctx.lineWidth = 1.8;
        ctx.beginPath();

        for (let x = 0; x <= W; x += 10) {
            const y = yAt(x, tt);
            if (x === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.stroke();

        const xr = (tt * 60 + ph * 40) % W;
        const yr = yAt(xr, tt);
        const ef = Math.max(0, Math.min(1, xr / (W * 0.12), (W - xr) / (W * 0.12)));

        ctx.globalAlpha = 0.25 * ef;
        ctx.fillStyle = CLAY;
        ctx.fillRect(xr - 5, yr - 5, 10, 10);
        ctx.globalAlpha = 0.95 * ef;
        ctx.fillStyle = CLAY_L;
        ctx.fillRect(xr - 2.5, yr - 2.5, 5, 5);
        ctx.globalAlpha = 1;

        requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

/* ── small nav globe ──────────────────────────────────────────────────── */

function startNavGlobe(cv) {
    if (cv._hzStarted) {
        return;
    }
    cv._hzStarted = true;

    const ctx = cv.getContext('2d');
    const W = cv.width;
    const H = cv.height;
    const cx = W / 2;
    const cy = H / 2;
    const R = W * 0.44;
    const pts = globePoints(350);
    const tilt = 0.38;
    const ct = Math.cos(tilt);
    const st = Math.sin(tilt);
    const t0 = performance.now();

    const step = (now) => {
        if (!cv.isConnected) {
            cv._hzStarted = false;

            return;
        }

        const tt = (now - t0) / 1000;
        ctx.clearRect(0, 0, W, H);

        const base = ctx.createRadialGradient(cx - R * 0.45, cy - R * 0.45, R * 0.1, cx, cy, R * 1.05);
        base.addColorStop(0, MID);
        base.addColorStop(0.55, INK);
        base.addColorStop(1, '#16253c');
        ctx.fillStyle = base;
        ctx.beginPath();
        ctx.arc(cx, cy, R, 0, 7);
        ctx.fill();

        // Subtracted, like the intro globe, so both turn west to east.
        const rot = tt * 0.6 + 1.2;
        for (const p of pts) {
            const lon = p.th - rot;
            const x = p.rr * Math.cos(lon);
            const z = p.rr * Math.sin(lon);
            const y2 = p.y * ct - z * st;
            const z2 = p.y * st + z * ct;

            if (z2 <= 0.02 || !p.land) {
                continue;
            }

            const b = Math.max(0, x * LIGHT.x + y2 * LIGHT.y + z2 * LIGHT.z);
            ctx.globalAlpha = 0.25 + b * 0.75;
            ctx.fillStyle = '#f0efec';
            const sz = 1.8 + b * 1.4;
            ctx.fillRect(cx + x * R - sz / 2, cy + y2 * R - sz / 2, sz, sz);
        }
        ctx.globalAlpha = 1;

        ctx.strokeStyle = 'rgba(207,152,119,0.55)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(cx, cy, R, 0, 7);
        ctx.stroke();

        requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

/** Highlight the active dot of a three-scene canvas. */
function markScene(cv, index) {
    const dots = cv.closest('[data-hz-scene]')?.querySelectorAll('[data-hz-dot]');

    if (!dots) {
        return;
    }

    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
}

/* ── "Sobre mí": three cross-fading scenes, cursor-reactive ───────────── */

function startAboutScenes(cv) {
    if (cv._hzStarted) {
        return;
    }
    cv._hzStarted = true;

    const ctx = cv.getContext('2d');
    const W = cv.width;
    const H = cv.height;
    const cx = W / 2;

    // Pointer state: the cursor bends the ink tides and attracts the
    // constellation nodes; holding the pointer down deepens the effect and
    // freezes the scene clock so a scene can be inspected.
    let dragging = false;
    let mx = -9999;
    let my = -9999;

    cv.addEventListener('pointerdown', (e) => {
        dragging = true;
        cv.setPointerCapture(e.pointerId);
    });
    cv.addEventListener('pointerleave', () => {
        mx = -9999;
        my = -9999;
    });
    cv.addEventListener('pointermove', (e) => {
        const rct = cv.getBoundingClientRect();
        mx = (e.clientX - rct.left) * (W / rct.width);
        my = (e.clientY - rct.top) * (H / rct.height);
    });
    const release = () => {
        dragging = false;
    };
    cv.addEventListener('pointerup', release);
    cv.addEventListener('pointercancel', release);

    let prev = performance.now();
    let sceneStart = performance.now();
    const off1 = offscreen(W, H);
    const octx1 = off1.getContext('2d');
    const off2 = offscreen(W, H);
    const octx2 = off2.getContext('2d');

    const nodes = Array.from({ length: 55 }, () => ({
        x: Math.random() * W,
        y: Math.random() * H,
        vx: (Math.random() - 0.5) * 60,
        vy: (Math.random() - 0.5) * 60,
        s: 4 + Math.random() * 6,
    }));

    const drawConstellation = (o, tt, dt) => {
        for (const n of nodes) {
            n.x += n.vx * dt;
            n.y += n.vy * dt;
            if (n.x < 0 || n.x > W) {
                n.vx *= -1;
            }
            if (n.y < 0 || n.y > H) {
                n.vy *= -1;
            }

            const dxm = mx - n.x;
            const dym = my - n.y;
            const dm = Math.hypot(dxm, dym);

            if (dm < 260 && dm > 1) {
                n.vx += (dxm / dm) * 40 * dt;
                n.vy += (dym / dm) * 40 * dt;
            }

            const sp = Math.hypot(n.vx, n.vy);
            if (sp > 110) {
                n.vx *= 110 / sp;
                n.vy *= 110 / sp;
            }
        }

        o.lineWidth = 1.6;
        const maxD = W * 0.17;
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const a = nodes[i];
                const b = nodes[j];
                const d = Math.hypot(a.x - b.x, a.y - b.y);

                if (d < maxD) {
                    o.globalAlpha = (1 - d / maxD) * 0.4;
                    o.strokeStyle = SOFT;
                    o.beginPath();
                    o.moveTo(a.x, a.y);
                    o.lineTo(b.x, b.y);
                    o.stroke();
                }
            }
        }

        for (const n of nodes) {
            o.globalAlpha = 0.92;
            o.fillStyle = CLAY;
            o.fillRect(n.x - n.s / 2, n.y - n.s / 2, n.s, n.s);
        }

        for (let k = 0; k < 3; k++) {
            const n = nodes[k * 7];
            const pr = (tt * 0.45 + k * 0.33) % 1;
            o.globalAlpha = (1 - pr) * 0.45;
            o.strokeStyle = CLAY;
            o.lineWidth = 2.5;
            o.beginPath();
            o.arc(n.x, n.y, pr * 90 + 8, 0, 7);
            o.stroke();
        }
        o.globalAlpha = 1;
    };

    const mkRidge = (seed, base, amp) => {
        const p = [];
        for (let k = 0; k <= 140; k++) {
            const x = k / 140;
            p.push({
                x: x * W,
                y:
                    base -
                    (Math.sin(x * 5.1 + seed) * 0.5 +
                        Math.sin(x * 11.3 + seed * 2.1) * 0.3 +
                        Math.sin(x * 23.7 + seed * 3.7) * 0.2 +
                        0.5) *
                        amp,
            });
        }

        return p;
    };
    const ridges = [
        { p: mkRidge(1.3, H * 0.6, H * 0.13), c: '#a3a29c', w: 3 },
        { p: mkRidge(3.7, H * 0.7, H * 0.17), c: '#6b86a8', w: 3.5 },
        { p: mkRidge(6.1, H * 0.82, H * 0.19), c: '#2b4a76', w: 4 },
    ];

    const drawMountains = (o, tt, localT) => {
        const sp2 = Math.min(1, localT / 5);

        o.fillStyle = CLAY;
        for (let k = 0; k <= 36; k++) {
            const a = Math.PI - (k / 36) * Math.PI * 0.55;
            o.globalAlpha = 0.18;
            o.fillRect(cx + Math.cos(a) * W * 0.3 - 1.5, H * 0.56 - Math.sin(a) * H * 0.36 - 1.5, 3, 3);
        }

        const sunA = Math.PI - sp2 * Math.PI * 0.55;
        const sx = cx + Math.cos(sunA) * W * 0.3;
        const sy = H * 0.56 - Math.sin(sunA) * H * 0.36;
        const gg = o.createRadialGradient(sx, sy, 5, sx, sy, 90);
        gg.addColorStop(0, 'rgba(207,152,119,0.5)');
        gg.addColorStop(1, 'rgba(207,152,119,0)');
        o.globalAlpha = 1;
        o.fillStyle = gg;
        o.beginPath();
        o.arc(sx, sy, 90, 0, 7);
        o.fill();
        o.fillStyle = CLAY_L;
        o.beginPath();
        o.arc(sx, sy, 26, 0, 7);
        o.fill();
        o.strokeStyle = CLAY;
        o.lineWidth = 2;
        o.globalAlpha = 0.8;
        o.beginPath();
        o.arc(sx, sy, 34 + Math.sin(tt * 2) * 3, 0, 7);
        o.stroke();

        ridges.forEach((r, i) => {
            const pi = Math.max(0, Math.min(1, (localT - i * 0.7) / 2.6));
            if (pi <= 0) {
                return;
            }

            const nPts = Math.max(2, Math.floor(pi * r.p.length));
            o.globalAlpha = 0.55 + i * 0.2;
            o.strokeStyle = r.c;
            o.lineWidth = r.w;
            o.lineJoin = 'round';
            o.beginPath();
            o.moveTo(r.p[0].x, r.p[0].y);
            for (let k = 1; k < nPts; k++) {
                o.lineTo(r.p[k].x, r.p[k].y);
            }
            o.stroke();

            const tip = r.p[nPts - 1];
            if (pi < 1) {
                o.globalAlpha = 0.9;
                o.fillStyle = CLAY_L;
                o.fillRect(tip.x - 3, tip.y - 3, 6, 6);
            }
        });

        const bl = Math.max(0, Math.min(1, (localT - 0.2) / 1.4));
        o.globalAlpha = 0.7;
        o.fillStyle = '#93918c';
        o.fillRect(0, H * 0.9, bl * W, 3);

        if (localT > 3.6) {
            o.strokeStyle = '#2b4a76';
            o.lineWidth = 2.5;
            o.lineCap = 'round';
            for (let i = 0; i < 3; i++) {
                const bx = W * (0.28 + i * 0.16) + Math.sin(tt * 0.7 + i) * 20;
                const by = H * (0.2 + i * 0.07) + Math.cos(tt * 0.9 + i * 2) * 10;
                const flap = Math.sin(tt * 7 + i * 1.8) * 6;
                o.globalAlpha = Math.min(1, (localT - 3.6) / 1);
                o.beginPath();
                o.moveTo(bx - 13, by + flap);
                o.lineTo(bx, by);
                o.lineTo(bx + 13, by + flap);
                o.stroke();
            }
        }
        o.globalAlpha = 1;
    };

    const drawWaves = (o, tt) => {
        const press = dragging ? 2.2 : 1;
        const rows = 11;

        for (let r = 0; r < rows; r++) {
            const y0 = H * (0.14 + r * 0.072);
            o.strokeStyle = r % 3 === 0 ? '#2b4a76' : r % 3 === 1 ? SOFT : '#a3a29c';
            o.globalAlpha = 0.45 + (r / rows) * 0.4;
            o.lineWidth = 2 + (r / rows) * 1.5;
            o.beginPath();

            for (let x = 0; x <= W; x += 8) {
                let y =
                    y0 +
                    Math.sin(x * 0.012 + tt * (0.7 + r * 0.06) + r * 1.3) * 16 +
                    Math.sin(x * 0.004 - tt * 0.45 + r * 2.1) * 26;
                const dxm = x - mx;
                const dym = y0 - my;
                const dd = dxm * dxm + dym * dym;
                y -= Math.exp(-dd / 26000) * 70 * press * (my > y0 ? 1 : -1);

                if (x === 0) {
                    o.moveTo(x, y);
                } else {
                    o.lineTo(x, y);
                }
            }
            o.stroke();
        }

        for (let r = 0; r < rows; r += 2) {
            const y0 = H * (0.14 + r * 0.072);
            const xr = (tt * 46 * (1 + r * 0.11) + r * 173) % W;
            let y =
                y0 +
                Math.sin(xr * 0.012 + tt * (0.7 + r * 0.06) + r * 1.3) * 16 +
                Math.sin(xr * 0.004 - tt * 0.45 + r * 2.1) * 26;
            const dxm = xr - mx;
            const dym = y0 - my;
            const dd = dxm * dxm + dym * dym;
            y -= Math.exp(-dd / 26000) * 70 * press * (my > y0 ? 1 : -1);

            o.globalAlpha = 0.25;
            o.fillStyle = CLAY;
            o.fillRect(xr - 6, y - 6, 12, 12);
            o.globalAlpha = 0.95;
            o.fillStyle = CLAY_L;
            o.fillRect(xr - 3.5, y - 3.5, 7, 7);
        }
        o.globalAlpha = 1;
    };

    let scene = -1;
    const step = (now) => {
        if (!cv.isConnected) {
            cv._hzStarted = false;

            return;
        }

        const dt = Math.min(0.05, (now - prev) / 1000);
        prev = now;
        const tt = now / 1000;

        if (dragging) {
            sceneStart += dt * 1000;
        }

        const DUR = Math.max(3, config.sceneDuration);
        const FADE = 1.2;
        const SC = 3;
        const cyc = Math.max(0, (now - sceneStart) / 1000);
        const idx = Math.floor(cyc / DUR) % SC;
        const local = cyc % DUR;

        if (scene !== idx) {
            scene = idx;
            markScene(cv, idx);
        }

        let aCur = 1;
        let aNext = 0;
        if (local > DUR - FADE) {
            aNext = (local - (DUR - FADE)) / FADE;
            aCur = 1 - aNext;
        }

        const render = (i, o, lt) => {
            o.clearRect(0, 0, W, H);
            if (i === 0) {
                drawWaves(o, tt);
            } else if (i === 1) {
                drawConstellation(o, tt, dt);
            } else {
                drawMountains(o, tt, lt);
            }
        };

        ctx.clearRect(0, 0, W, H);
        render(idx, octx1, local + FADE);
        ctx.globalAlpha = aCur;
        ctx.drawImage(off1, 0, 0);

        if (aNext > 0.01) {
            render((idx + 1) % SC, octx2, Math.max(0.01, local - (DUR - FADE)));
            ctx.globalAlpha = aNext;
            ctx.drawImage(off2, 0, 0);
        }
        ctx.globalAlpha = 1;

        featherEdges(ctx, W, H, W * 0.14, H * 0.1);
        cv.style.cursor = 'crosshair';

        requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

/** Fade the canvas edges out so the scenes bleed into the page. */
function featherEdges(ctx, W, H, fw, fh) {
    ctx.globalCompositeOperation = 'destination-out';

    let fg = ctx.createLinearGradient(0, 0, fw, 0);
    fg.addColorStop(0, 'rgba(0,0,0,1)');
    fg.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = fg;
    ctx.fillRect(0, 0, fw, H);

    fg = ctx.createLinearGradient(W, 0, W - fw, 0);
    fg.addColorStop(0, 'rgba(0,0,0,1)');
    fg.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = fg;
    ctx.fillRect(W - fw, 0, fw, H);

    if (fh > 0) {
        fg = ctx.createLinearGradient(0, 0, 0, fh);
        fg.addColorStop(0, 'rgba(0,0,0,1)');
        fg.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = fg;
        ctx.fillRect(0, 0, W, fh);

        fg = ctx.createLinearGradient(0, H, 0, H - fh);
        fg.addColorStop(0, 'rgba(0,0,0,1)');
        fg.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = fg;
        ctx.fillRect(0, H - fh, W, fh);
    }

    ctx.globalCompositeOperation = 'source-over';
}

/* ── experience scenes: orbits, trajectory, deployment mesh ───────────── */

function startExperienceScenes(cv) {
    if (cv._hzStarted) {
        return;
    }
    cv._hzStarted = true;

    const ctx = cv.getContext('2d');
    const W = cv.width;
    const H = cv.height;
    const off1 = offscreen(W, H);
    const off2 = offscreen(W, H);
    const o1 = off1.getContext('2d');
    const o2 = off2.getContext('2d');

    // 1 — Órbitas: sistemas en producción girando alrededor de un núcleo
    const rings = [0.3, 0.45, 0.6, 0.76].map((r, i) => ({
        r: r * W * 0.5,
        sp: 0.16 - i * 0.028,
        nodes: [0, 1, 2].slice(0, i % 2 === 0 ? 2 : 3).map((k) => ({
            a: (k / (i % 2 === 0 ? 2 : 3)) * 6.28318 + i * 0.7,
            s: 9 - i,
        })),
    }));

    const drawOrbits = (o, tt) => {
        const cx = W / 2;
        const cy = H / 2;
        o.lineWidth = 1.5;
        const pos = [];

        rings.forEach((rg, i) => {
            o.globalAlpha = 0.22;
            o.strokeStyle = i % 2 ? SOFT : MID;
            o.beginPath();
            o.arc(cx, cy, rg.r, 0, 7);
            o.stroke();

            rg.nodes.forEach((n) => {
                const a = n.a + tt * rg.sp;
                pos.push({ x: cx + Math.cos(a) * rg.r, y: cy + Math.sin(a) * rg.r, s: n.s, ring: i });
            });
        });

        for (let i = 0; i < pos.length; i++) {
            for (let j = i + 1; j < pos.length; j++) {
                const d = Math.hypot(pos[i].x - pos[j].x, pos[i].y - pos[j].y);

                if (d < W * 0.22) {
                    o.globalAlpha = (1 - d / (W * 0.22)) * 0.3;
                    o.strokeStyle = PALE;
                    o.beginPath();
                    o.moveTo(pos[i].x, pos[i].y);
                    o.lineTo(pos[j].x, pos[j].y);
                    o.stroke();
                }
            }
        }

        for (const p of pos) {
            o.globalAlpha = 0.22;
            o.fillStyle = CLAY;
            o.fillRect(p.x - p.s, p.y - p.s, p.s * 2, p.s * 2);
            o.globalAlpha = 0.95;
            o.fillStyle = p.ring % 2 ? CLAY_L : PALE;
            o.fillRect(p.x - p.s / 2, p.y - p.s / 2, p.s, p.s);
        }

        const pulse = 1 + Math.sin(tt * 1.6) * 0.08;
        o.globalAlpha = 0.9;
        o.fillStyle = INK;
        o.fillRect(cx - 16 * pulse, cy - 16 * pulse, 32 * pulse, 32 * pulse);
        o.globalAlpha = 0.35;
        o.strokeStyle = CLAY;
        o.lineWidth = 2;
        o.strokeRect(cx - 30 * pulse, cy - 30 * pulse, 60 * pulse, 60 * pulse);
        o.globalAlpha = 1;
    };

    // 2 — Trayectoria: barras de proyecto avanzando bajo un cabezal
    const bars = [
        { y: 0.16, x0: 0.06, w: 0.62, d: 0.0 },
        { y: 0.27, x0: 0.14, w: 0.5, d: 0.5 },
        { y: 0.38, x0: 0.1, w: 0.74, d: 1.0 },
        { y: 0.49, x0: 0.28, w: 0.46, d: 1.5 },
        { y: 0.6, x0: 0.2, w: 0.66, d: 2.0 },
        { y: 0.71, x0: 0.36, w: 0.44, d: 2.5 },
        { y: 0.82, x0: 0.12, w: 0.8, d: 3.0 },
    ];

    const drawBars = (o, tt, lt) => {
        o.globalAlpha = 0.12;
        o.strokeStyle = SOFT;
        o.lineWidth = 1;
        for (let i = 0; i <= 8; i++) {
            const x = W * (0.06 + i * 0.11);
            o.beginPath();
            o.moveTo(x, H * 0.1);
            o.lineTo(x, H * 0.9);
            o.stroke();
        }

        bars.forEach((b, i) => {
            const g = Math.max(0, Math.min(1, (lt - b.d) / 1.6));
            const y = H * b.y;
            const x = W * b.x0;
            const w = W * b.w * g;

            o.globalAlpha = 0.16;
            o.fillStyle = MID;
            o.fillRect(x, y - 7, W * b.w, 14);
            o.globalAlpha = 0.85;
            o.fillStyle = i % 3 === 2 ? CLAY : MID;
            o.fillRect(x, y - 7, w, 14);

            if (g > 0 && g < 1) {
                o.globalAlpha = 0.9;
                o.fillStyle = CLAY_L;
                o.fillRect(x + w - 3, y - 11, 6, 22);
            }
        });

        const hx = W * (0.06 + ((lt * 0.11) % 0.88));
        o.globalAlpha = 0.5;
        o.strokeStyle = CLAY;
        o.lineWidth = 2;
        o.beginPath();
        o.moveTo(hx, H * 0.08);
        o.lineTo(hx, H * 0.92);
        o.stroke();
        o.globalAlpha = 0.9;
        o.fillStyle = CLAY;
        o.fillRect(hx - 5, H * 0.08 - 5, 10, 10);
        o.globalAlpha = 1;
    };

    // 3 — Despliegue: pulso propagándose por una malla de nodos
    const N = 6;
    const lat = [];
    for (let i = 0; i < N; i++) {
        for (let j = 0; j < N; j++) {
            lat.push({
                x: W * (0.16 + (i / (N - 1)) * 0.68),
                y: H * (0.16 + (j / (N - 1)) * 0.68),
                i,
                j,
            });
        }
    }
    const src = { x: W * 0.16, y: H * 0.5 };

    const drawMesh = (o, tt) => {
        o.lineWidth = 1;
        for (const a of lat) {
            for (const b of lat) {
                if (b.i === a.i && b.j === a.j + 1) {
                    o.globalAlpha = 0.1;
                    o.strokeStyle = SOFT;
                    o.beginPath();
                    o.moveTo(a.x, a.y);
                    o.lineTo(b.x, b.y);
                    o.stroke();
                }
                if (b.j === a.j && b.i === a.i + 1) {
                    o.globalAlpha = 0.1;
                    o.strokeStyle = SOFT;
                    o.beginPath();
                    o.moveTo(a.x, a.y);
                    o.lineTo(b.x, b.y);
                    o.stroke();
                }
            }
        }

        const front = ((tt * 0.34) % 1.6) * W;
        for (const p of lat) {
            const d = Math.hypot(p.x - src.x, p.y - src.y);
            const hit = Math.max(0, 1 - Math.abs(d - front) / (W * 0.16));

            o.globalAlpha = 0.2 + hit * 0.75;
            o.fillStyle = hit > 0.35 ? CLAY_L : PALE;
            const s = 5 + hit * 7;
            o.fillRect(p.x - s / 2, p.y - s / 2, s, s);

            if (hit > 0.55) {
                o.globalAlpha = (hit - 0.55) * 0.9;
                o.strokeStyle = CLAY;
                o.lineWidth = 1.5;
                o.strokeRect(p.x - s, p.y - s, s * 2, s * 2);
            }
        }

        o.globalAlpha = 0.28;
        o.strokeStyle = CLAY;
        o.lineWidth = 2;
        o.beginPath();
        o.arc(src.x, src.y, front, 0, 7);
        o.stroke();
        o.globalAlpha = 0.95;
        o.fillStyle = INK;
        o.fillRect(src.x - 9, src.y - 9, 18, 18);
        o.globalAlpha = 1;
    };

    const t0 = performance.now();
    let scene = -1;

    const step = (now) => {
        if (!cv.isConnected) {
            cv._hzStarted = false;

            return;
        }

        const tt = now / 1000;
        const DUR = Math.max(3, config.sceneDuration);
        const FADE = 1.2;
        const SC = 3;
        const cyc = Math.max(0, (now - t0) / 1000);
        const idx = Math.floor(cyc / DUR) % SC;
        const local = cyc % DUR;

        if (scene !== idx) {
            scene = idx;
            markScene(cv, idx);
        }

        let aCur = 1;
        let aNext = 0;
        if (local > DUR - FADE) {
            aNext = (local - (DUR - FADE)) / FADE;
            aCur = 1 - aNext;
        }

        const render = (i, o, lt) => {
            o.clearRect(0, 0, W, H);
            if (i === 0) {
                drawOrbits(o, tt);
            } else if (i === 1) {
                drawBars(o, tt, lt);
            } else {
                drawMesh(o, tt);
            }
        };

        ctx.clearRect(0, 0, W, H);
        render(idx, o1, local + FADE);
        ctx.globalAlpha = aCur;
        ctx.drawImage(off1, 0, 0);

        if (aNext > 0.01) {
            render((idx + 1) % SC, o2, Math.max(0.01, local - (DUR - FADE)));
            ctx.globalAlpha = aNext;
            ctx.drawImage(off2, 0, 0);
        }
        ctx.globalAlpha = 1;

        featherEdges(ctx, W, H, W * 0.1, 0);

        requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

/* ── page behaviour ───────────────────────────────────────────────────── */

function bindNavScroll() {
    const nav = document.querySelector('[data-hz-nav]');

    if (!nav || nav.dataset.hzBound) {
        return;
    }
    nav.dataset.hzBound = '1';

    const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 24);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

function bindSectionLinks() {
    document.querySelectorAll('[data-hz-section-link]').forEach((link) => {
        if (link.dataset.hzBound) {
            return;
        }
        link.dataset.hzBound = '1';

        link.addEventListener('click', (e) => {
            const id = (link.getAttribute('href') || '').split('#')[1];
            const el = id ? document.getElementById(id) : null;

            if (!el) {
                return;
            }

            e.preventDefault();
            const top = id === 'inicio' ? 0 : el.getBoundingClientRect().top + window.pageYOffset - 66;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        });
    });
}

function bindReveals() {
    document.querySelectorAll('[data-hz-reveal]').forEach((el) => {
        if (el.dataset.hzBound) {
            return;
        }
        el.dataset.hzBound = '1';

        if (typeof IntersectionObserver === 'undefined' || prefersReducedMotion()) {
            el.classList.add('hz-reveal');

            return;
        }

        const obs = new IntersectionObserver(
            (entries) => {
                if (entries.some((x) => x.isIntersecting)) {
                    el.classList.add('hz-reveal');
                    obs.disconnect();
                }
            },
            { threshold: 0.08, rootMargin: '0px 0px -40px 0px' },
        );
        obs.observe(el);
    });
}

function prefersReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

/** Thin accent bar tracking how far down the page the visitor is. */
function bindScrollProgress() {
    const bar = document.querySelector('[data-hz-progress]');

    if (!bar || bar.dataset.hzBound) {
        return;
    }
    bar.dataset.hzBound = '1';

    const update = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
        bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, ratio)).toFixed(4) + ')';
    };

    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
}

/** Underline the navigation link whose section is currently in view. */
function bindActiveSection() {
    const links = [...document.querySelectorAll('[data-hz-section-link]')];
    const sections = [...document.querySelectorAll('section[id]')];

    if (links.length === 0 || sections.length === 0 || document.body.dataset.hzActiveBound) {
        return;
    }
    document.body.dataset.hzActiveBound = '1';

    const update = () => {
        let current = '';

        for (const section of sections) {
            if (window.scrollY + 120 >= section.offsetTop) {
                current = section.id;
            }
        }

        for (const link of links) {
            const target = (link.getAttribute('href') || '').split('#')[1];
            link.dataset.active = target === current ? 'true' : 'false';
        }
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
}

function bindProjectRail() {
    const rail = document.querySelector('[data-hz-rail]');

    if (!rail || rail.dataset.hzBound) {
        return;
    }
    rail.dataset.hzBound = '1';

    document.querySelectorAll('[data-hz-rail-prev]').forEach((b) =>
        b.addEventListener('click', () => rail.scrollBy({ left: -362, behavior: 'smooth' })),
    );
    document.querySelectorAll('[data-hz-rail-next]').forEach((b) =>
        b.addEventListener('click', () => rail.scrollBy({ left: 362, behavior: 'smooth' })),
    );

    rail.querySelectorAll('.hz-proj-card').forEach((card) => {
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            const x = e.clientX - r.left;
            const y = e.clientY - r.top;
            card.style.background =
                'radial-gradient(280px circle at ' +
                x +
                'px ' +
                y +
                'px, color-mix(in srgb, var(--color-accent) 10%, var(--color-surface)), var(--color-surface) 78%)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.background = 'var(--color-surface)';
        });
    });
}

function bindLightbox() {
    const box = document.querySelector('[data-hz-lightbox]');

    if (!box || box.dataset.hzBound) {
        return;
    }
    box.dataset.hzBound = '1';

    const img = box.querySelector('img');
    const open = (src) => {
        img.src = src;
        box.hidden = false;
    };
    const close = () => {
        box.hidden = true;
        img.removeAttribute('src');
    };

    document.querySelectorAll('[data-hz-zoom]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            open(btn.dataset.hzZoom);
        });
    });

    box.addEventListener('click', close);
    box.querySelector('[data-hz-lightbox-inner]')?.addEventListener('click', (e) => e.stopPropagation());
    box.querySelectorAll('[data-hz-lightbox-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !box.hidden) {
            close();
        }
    });
}

/* ── command palette ──────────────────────────────────────────────────── */

/**
 * ⌘K / Ctrl+K opens a searchable list of everything on the page: the sections,
 * the published courses and guides, and the theme / language actions.
 */
function bindCommandPalette() {
    const palette = document.querySelector('[data-hz-cmdk]');

    if (!palette || palette.dataset.hzBound) {
        return;
    }
    palette.dataset.hzBound = '1';

    const input = palette.querySelector('[data-hz-cmdk-input]');
    const list = palette.querySelector('[data-hz-cmdk-list]');
    const emptyLabel = palette.dataset.hzEmpty || 'Sin resultados';
    const entries = JSON.parse(palette.dataset.hzItems || '[]');
    let active = 0;
    let visible = [];

    const render = () => {
        const term = input.value.trim().toLowerCase();
        visible = entries.filter((item) => !term || item.label.toLowerCase().includes(term));
        active = 0;

        if (visible.length === 0) {
            list.innerHTML = '<div class="hz-cmdk-empty"></div>';
            list.firstChild.textContent = emptyLabel;

            return;
        }

        list.textContent = '';
        let lastGroup = null;

        visible.forEach((item, index) => {
            if (item.group !== lastGroup) {
                lastGroup = item.group;
                const heading = document.createElement('div');
                heading.className = 'hz-cmdk-group';
                heading.textContent = item.group;
                list.appendChild(heading);
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'hz-cmdk-item';
            button.dataset.index = String(index);
            button.dataset.active = index === 0 ? 'true' : 'false';
            button.textContent = item.label;
            button.addEventListener('click', () => run(item));
            button.addEventListener('mousemove', () => setActive(index));
            list.appendChild(button);
        });
    };

    const setActive = (index) => {
        active = index;
        list.querySelectorAll('.hz-cmdk-item').forEach((el) => {
            el.dataset.active = Number(el.dataset.index) === index ? 'true' : 'false';
        });
        list.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' });
    };

    const open = () => {
        palette.hidden = false;
        input.value = '';
        render();
        input.focus();
    };

    const close = () => {
        palette.hidden = true;
    };

    const run = (item) => {
        close();

        if (item.action === 'theme') {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
            syncThemeIcons();

            return;
        }

        if (item.href?.startsWith('#')) {
            const target = document.getElementById(item.href.slice(1));

            if (target) {
                const top = target.getBoundingClientRect().top + window.pageYOffset - 66;
                window.scrollTo({ top: Math.max(0, top), behavior: prefersReducedMotion() ? 'auto' : 'smooth' });

                return;
            }
        }

        if (item.href) {
            window.location.href = item.href;
        }
    };

    input.addEventListener('input', render);

    palette.addEventListener('click', (e) => {
        if (e.target === palette) {
            close();
        }
    });

    palette.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(active + 1, visible.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(active - 1, 0));
        } else if (e.key === 'Enter' && visible[active]) {
            e.preventDefault();
            run(visible[active]);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key?.toLowerCase() === 'k' && (e.metaKey || e.ctrlKey)) {
            e.preventDefault();
            palette.hidden ? open() : close();
        }
    });

    document.querySelectorAll('[data-hz-cmdk-open]').forEach((button) => {
        button.addEventListener('click', open);
    });
}

/* ── boot ─────────────────────────────────────────────────────────────── */

function boot() {
    const root = document.querySelector('[data-hz-config]');

    if (root) {
        Object.assign(config, JSON.parse(root.dataset.hzConfig));
    }

    bindThemeToggles();
    bindNavScroll();
    bindSectionLinks();
    bindReveals();
    bindScrollProgress();
    bindActiveSection();
    bindCommandPalette();
    bindProjectRail();
    bindLightbox();

    // Flips the hero from its pre-render state into view.
    requestAnimationFrame(() => document.body.classList.add('is-ready'));

    const intro = document.querySelector('[data-hz-intro]');
    if (intro) {
        let seen = false;
        try {
            seen = !!localStorage.getItem('hz_intro_seen_v1');
        } catch (e) {
            /* ignore */
        }

        if (config.introAlways || !seen) {
            intro.hidden = false;
            startIntro(intro);
        } else {
            intro.remove();
        }
    }

    if (config.background) {
        document.querySelectorAll('[data-hz-bg]').forEach(startBackground);
    } else {
        document.querySelectorAll('[data-hz-bg]').forEach((el) => el.remove());
    }

    document.querySelectorAll('[data-hz-wave]').forEach(startWaveDivider);
    document.querySelectorAll('[data-hz-nav-globe]').forEach(startNavGlobe);
    document.querySelectorAll('[data-hz-about-scenes]').forEach(startAboutScenes);
    document.querySelectorAll('[data-hz-exp-scenes]').forEach(startExperienceScenes);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', boot);
