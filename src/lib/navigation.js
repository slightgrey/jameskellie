/**
 * SPA navigation helpers: which links the router handles, the route-data cache,
 * <head> sync and scrolling with the sticky header in mind.
 *
 * Route data comes from the real URL requested with `X-JK-Spa: 1` (inc/Spa.php),
 * so WordPress still resolves redirects, 404s and templates.
 */
import { prefersReducedMotion } from './dataLayer';

const TTL = 60_000;
const MAX_CACHE = 10;
const SKIP_PATH = /^\/(wp-admin|wp-login\.php|wp-json|feed)(\/|$)|\/feed\/?$/i;
const FILE_EXT = /\.(pdf|jpe?g|png|gif|webp|avif|svg|ico|zip|rar|7z|gz|mp4|webm|mov|mp3|wav|docx?|xlsx?|pptx?|csv|txt|xml|json|ics)$/i;
const HEAD_SYNC = [
  'meta[name="description"]',
  'link[rel="canonical"]',
  'meta[property^="og:"]',
  'meta[property^="article:"]',
  'meta[name^="twitter:"]',
  'meta[name="robots"]',
  'script[type="application/ld+json"]',
];

const cache = new Map(); // path → { data, time }
const inflight = new Map();

export const pathOf = (loc) => loc.pathname + loc.search;
export const saveData = () => Boolean(navigator.connection && navigator.connection.saveData);

/** The URL a link should be routed for, or null to let the browser handle it. */
export function spaUrl(a) {
  if (!(a instanceof HTMLAnchorElement) || !a.href) return null;
  if (a.hasAttribute('download') || a.closest('[data-no-spa], #wpadminbar')) return null;
  const target = a.getAttribute('target');
  if (target && target !== '_self') return null;
  let url;
  try {
    url = new URL(a.href, window.location.href);
  } catch {
    return null;
  }
  if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return null;
  if (SKIP_PATH.test(url.pathname) || FILE_EXT.test(url.pathname)) return null;
  return url;
}

function remember(key, entry) {
  cache.delete(key);
  cache.set(key, entry);
  while (cache.size > MAX_CACHE) cache.delete(cache.keys().next().value);
}

/** Seed the cache with the page that was server-loaded. */
export function seedRoute(path, data) {
  remember(path, { data, time: Date.now() });
}

/**
 * Route payload for a path: { route, title, head, bodyClass, path }.
 * `path` is the final path after any server redirect.
 */
export function fetchRoute(path) {
  const hit = cache.get(path);
  if (hit && Date.now() - hit.time < TTL) return Promise.resolve(hit.data);
  if (inflight.has(path)) return inflight.get(path);

  const p = fetch(path, {
    headers: { 'X-JK-Spa': '1', Accept: 'application/json' },
    credentials: 'same-origin',
  })
    .then(async (res) => {
      const type = res.headers.get('content-type') || '';
      if (!type.includes('application/json') || (res.status !== 200 && res.status !== 404)) {
        throw new Error(`HTTP ${res.status}`);
      }
      const finalUrl = new URL(res.url || path, window.location.href);
      if (finalUrl.origin !== window.location.origin) throw new Error('cross-origin redirect');
      const data = { ...(await res.json()), path: pathOf(finalUrl) };
      remember(path, { data, time: Date.now() });
      if (data.path !== path) remember(data.path, { data, time: Date.now() });
      return data;
    })
    .finally(() => inflight.delete(path));
  inflight.set(path, p);
  return p;
}

/** Replace the synced SEO tags with the ones from a route's wp_head output. */
export function syncHead({ title, head, bodyClass }) {
  if (title) document.title = title;
  if (typeof bodyClass === 'string') document.body.className = bodyClass;
  if (typeof head !== 'string') return;
  const doc = new DOMParser().parseFromString(`<!doctype html><html><head>${head}</head><body></body></html>`, 'text/html');
  HEAD_SYNC.forEach((sel) => {
    document.head.querySelectorAll(sel).forEach((n) => n.remove());
    doc.head.querySelectorAll(sel).forEach((n) => document.head.append(document.importNode(n, true)));
  });
}

/** Sticky header height (only when the header is sticky). */
export function headerOffset() {
  const header = document.querySelector('[data-jk-header]');
  if (!header || !document.body.classList.contains('jk-sticky')) return 0;
  return header.offsetHeight;
}

/** Scroll to #id (or the top), offsetting upward scrolls by the sticky header. */
export function scrollToHash(hash, smooth) {
  const id = decodeURIComponent((hash || '').slice(1));
  const el = id ? document.getElementById(id) : null;
  const behavior = smooth && !prefersReducedMotion() ? 'smooth' : 'auto';
  if (!el) {
    if (!id || id === 'top') window.scrollTo({ top: 0, behavior });
    return;
  }
  const y = el.getBoundingClientRect().top + window.scrollY;
  // The sticky header reappears when scrolling up, so only offset upward scrolls.
  const offset = y < window.scrollY ? headerOffset() : 0;
  window.scrollTo({ top: Math.max(0, y - offset), behavior });
}

/** Tell hash-aware components (Services deep links) that navigation finished. */
export function navigated() {
  window.dispatchEvent(new CustomEvent('jk:navigated', { detail: { url: window.location.href } }));
}
