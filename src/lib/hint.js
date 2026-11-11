/**
 * Click hints (HANDOFF §4.1): an animated pointer that glides to each target and taps it.
 *
 * attachHint(container, { targets, taps, ring }) → detach()
 * - starts once per page view when the container is ≥60% visible
 * - measures real target centres (getBoundingClientRect), re-measures on resize
 * - stops for good on the first click, key selection or Tab focus inside the container
 * - pauses while the tab is hidden
 * - reduced motion: no pointer, only the label ([data-hint-label] in the container)
 */
import { prefersReducedMotion } from './dataLayer';

const POINTER_SVG =
  '<svg width="36" height="40" viewBox="0 0 36 40" aria-hidden="true" focusable="false" style="position:relative;display:block;filter:drop-shadow(0 4px 10px rgba(0,0,0,.35))"><path d="M4 2 L4 30 L11 24 L16 36 L21.5 33.5 L16.5 22 L26 22 Z" fill="#FFFFFF" stroke="#0A0A0A" stroke-width="2" stroke-linejoin="round"/></svg>';
const EASE = 'cubic-bezier(.45,0,.25,1)';
const STOP_KEYS = new Set(['Enter', ' ', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End']);

/** Keyframes for the pointer and its tap ring, from target centres. */
function buildKeyframes(points, taps) {
  const T = (p, s = 1) => `translate(${p.x - 4}px, ${p.y - 2}px) scale(${s})`;
  const first = points[0];
  const last = points[points.length - 1];
  const start = { x: first.x + 64, y: first.y + 64 };
  const pointer = [];
  const ring = [{ offset: 0, opacity: 0, transform: 'translate(-50%,-50%) scale(.4)' }];

  // Timeline: lists arrive at 16%…72% and tap once; a single target taps `taps` times.
  const multi = taps > 1;
  const firstAt = multi ? 0.3 : 0.16;
  const step = points.length > 1 ? 0.56 / (points.length - 1) : 0;
  const tapDown = multi ? 0.06 : 0.04;
  const tapUp = multi ? 0.12 : 0.08;
  const tapGap = 0.18;

  pointer.push({ offset: 0, transform: T(start), opacity: 0, easing: EASE });
  pointer.push({
    offset: firstAt / 2,
    transform: T({ x: (start.x + first.x) / 2, y: (start.y + first.y) / 2 }),
    opacity: 1,
    easing: EASE,
  });

  let cursor = 0;
  points.forEach((p, i) => {
    const at = firstAt + i * step;
    pointer.push({ offset: at, transform: T(p), opacity: 1, easing: EASE });
    for (let t = 0; t < taps; t++) {
      const down = at + tapDown + t * tapGap;
      const up = at + tapUp + t * tapGap;
      pointer.push({ offset: down, transform: T(p, 0.82), opacity: 1, easing: EASE });
      pointer.push({ offset: up, transform: T(p), opacity: 1, easing: EASE });
      const before = Math.max(cursor, down - 0.03);
      ring.push({ offset: before, opacity: 0, transform: 'translate(-50%,-50%) scale(.4)' });
      ring.push({ offset: down, opacity: 0.9, transform: 'translate(-50%,-50%) scale(1)' });
      ring.push({ offset: up + 0.01, opacity: 0, transform: 'translate(-50%,-50%) scale(.4)' });
      cursor = up + 0.01;
    }
  });

  const fadeAt = Math.max(cursor, multi ? 0.85 : 0.92);
  pointer.push({ offset: fadeAt, transform: T(last), opacity: 1, easing: EASE });
  pointer.push({ offset: 1, transform: T(multi ? { x: last.x + 38, y: last.y + 38 } : last), opacity: 0 });
  ring.push({ offset: 1, opacity: 0, transform: 'translate(-50%,-50%) scale(.4)' });

  // WAAPI needs non-decreasing offsets.
  const clamp = (frames) => {
    let prev = 0;
    return frames.map((f) => {
      const offset = Math.min(1, Math.max(prev, f.offset));
      prev = offset;
      return { ...f, offset };
    });
  };
  return { pointer: clamp(pointer), ring: clamp(ring) };
}

export function attachHint(container, { targets = '[data-hint-target]', taps = 1, ring = '#FFFFFF', duration } = {}) {
  if (!container || typeof IntersectionObserver === 'undefined') return () => {};

  const total = duration || (taps > 1 ? 3600 : 4800);
  const label = container.querySelector('[data-hint-label]');
  const reduced = prefersReducedMotion();
  const canAnimate = !reduced && typeof Element.prototype.animate === 'function';

  let pointer = null;
  let ringEl = null;
  let anims = [];
  let ro = null;
  let started = false;
  let stopped = false;
  let resizeTimer = 0;

  const visibleTargets = () =>
    [...container.querySelectorAll(targets)].filter((el) => el.getClientRects().length > 0);

  function play() {
    anims.forEach((a) => a.cancel());
    anims = [];
    const list = visibleTargets();
    if (!list.length || !pointer) return;
    const box = container.getBoundingClientRect();
    const points = list.map((el) => {
      const r = el.getBoundingClientRect();
      return { x: r.left - box.left + r.width / 2, y: r.top - box.top + r.height / 2 };
    });
    const kf = buildKeyframes(points, taps);
    const timing = { duration: total, iterations: Infinity, delay: 400, fill: 'both' };
    anims.push(pointer.animate(kf.pointer, timing));
    anims.push(ringEl.animate(kf.ring, { ...timing, easing: 'linear' }));
    if (document.hidden) anims.forEach((a) => a.pause());
  }

  function start() {
    if (started || stopped) return;
    started = true;
    if (label) label.setAttribute('data-active', '');
    if (!canAnimate) return;

    pointer = document.createElement('div');
    pointer.className = 'jk-pointer';
    pointer.setAttribute('aria-hidden', 'true');
    ringEl = document.createElement('span');
    ringEl.className = 'jk-pointer-ring';
    ringEl.style.color = ring;
    if (taps > 1) {
      ringEl.style.width = '56px';
      ringEl.style.height = '56px';
    }
    pointer.append(ringEl);
    pointer.insertAdjacentHTML('beforeend', POINTER_SVG);
    container.append(pointer);
    play();

    if (typeof ResizeObserver !== 'undefined') {
      ro = new ResizeObserver(() => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(play, 120);
      });
      ro.observe(container);
    }
  }

  const io = new IntersectionObserver(
    (entries) => {
      const e = entries[0];
      if (!e || !e.isIntersecting) return;
      const vh = e.rootBounds ? e.rootBounds.height : window.innerHeight;
      // ≥60% of the container visible, or the container fills ≥60% of a short viewport.
      if (e.intersectionRatio >= 0.6 || e.intersectionRect.height >= vh * 0.6) {
        io.disconnect();
        start();
      }
    },
    { threshold: [0, 0.2, 0.4, 0.6, 0.8, 1] },
  );
  io.observe(container);

  const onVisibility = () => anims.forEach((a) => (document.hidden ? a.pause() : a.play()));
  const onPointer = () => detach();
  const onKey = (e) => {
    if (STOP_KEYS.has(e.key)) detach();
  };
  const onFocus = (e) => {
    if (e.target instanceof Element && e.target.matches('button, a, [role="tab"], [tabindex]')) detach();
  };

  document.addEventListener('visibilitychange', onVisibility);
  container.addEventListener('click', onPointer, true);
  container.addEventListener('keydown', onKey, true);
  container.addEventListener('focusin', onFocus, true);

  function detach() {
    if (stopped) return;
    stopped = true;
    io.disconnect();
    ro?.disconnect();
    clearTimeout(resizeTimer);
    anims.forEach((a) => a.cancel());
    anims = [];
    pointer?.remove();
    pointer = null;
    label?.removeAttribute('data-active');
    document.removeEventListener('visibilitychange', onVisibility);
    container.removeEventListener('click', onPointer, true);
    container.removeEventListener('keydown', onKey, true);
    container.removeEventListener('focusin', onFocus, true);
  }

  return detach;
}

/** Static hints (the CTA band's round button). Re-run by the router after each swap. */
export function bootStaticHints(root = document) {
  const detachers = [];
  if (window.JK && window.JK.hints === false) return () => {};
  root.querySelectorAll('[data-hint="cta"]').forEach((el) => {
    detachers.push(attachHint(el, { targets: '[data-hint-target]', taps: 2, ring: '#FFFFFF' }));
  });
  return () => detachers.forEach((d) => d());
}
