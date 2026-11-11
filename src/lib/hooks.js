import { useEffect, useState } from 'react';
import { attachHint } from './hint';

/** Attach a click hint to ref.current once, when enabled. */
export function useHint(ref, enabled, options) {
  useEffect(() => {
    if (!enabled || !ref.current) return undefined;
    return attachHint(ref.current, options);
    // Options are static per island.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled]);
}

/** matchMedia as state, read synchronously on first render. */
export function useMediaQuery(query) {
  const get = () => typeof window !== 'undefined' && window.matchMedia(query).matches;
  const [matches, setMatches] = useState(get);
  useEffect(() => {
    const mq = window.matchMedia(query);
    const on = () => setMatches(mq.matches);
    mq.addEventListener('change', on);
    return () => mq.removeEventListener('change', on);
  }, [query]);
  return matches;
}

/**
 * Calls fn(hash) on mount and on every navigation to a hash
 * (native hashchange and the App router's jk:navigated event).
 */
export function useHashTarget(fn) {
  useEffect(() => {
    const run = () => fn(decodeURIComponent(window.location.hash.slice(1)));
    run();
    window.addEventListener('hashchange', run);
    window.addEventListener('jk:navigated', run);
    return () => {
      window.removeEventListener('hashchange', run);
      window.removeEventListener('jk:navigated', run);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
}
