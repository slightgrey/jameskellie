/** Push an event to GTM's dataLayer (created if missing). */
export function track(payload) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
}

export const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
