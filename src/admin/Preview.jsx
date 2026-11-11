import { useEffect, useRef, useState } from 'react';
import cfg, { api } from './api';

const DEVICES = {
  desktop: { label: 'Desktop', width: 1440 },
  mobile: { label: 'Mobile', width: 390 },
};
const STORE = 'jk-preview';
const DEBOUNCE_MS = 350;

const readPrefs = () => {
  try {
    return { open: true, device: 'desktop', ...JSON.parse(localStorage.getItem(STORE) || '{}') };
  } catch {
    return { open: true, device: 'desktop' };
  }
};

/**
 * Live preview of a Home section in an iframe of the real front page (?jk_preview=key).
 * Unsaved values go to jk/v1/admin/preview/{key} (sanitised, never saved); the render data
 * that comes back is posted into the frame, which redraws with the real component and CSS.
 */
export default function Preview({ section, values }) {
  const [prefs, setPrefs] = useState(readPrefs);
  const [height, setHeight] = useState(600);
  const [width, setWidth] = useState(0);
  const [state, setState] = useState('loading'); // loading | ready | updating | error
  const boxRef = useRef(null);
  const frameRef = useRef(null);
  const latest = useRef(null); // last render data, re-sent when the frame (re)loads
  const ready = useRef(false);

  const src = cfg.preview.url.replace('{key}', encodeURIComponent(section));
  const device = DEVICES[prefs.device] || DEVICES.desktop;
  const scale = width ? Math.min(1, width / device.width) : 1;

  const update = (next) => {
    setPrefs((p) => {
      const merged = { ...p, ...next };
      try {
        localStorage.setItem(STORE, JSON.stringify(merged));
      } catch {
        // Private mode: the toggle just isn't remembered.
      }
      return merged;
    });
  };

  const post = (data) => {
    latest.current = data;
    if (ready.current) frameRef.current?.contentWindow?.postMessage({ type: 'jk:preview', key: section, data }, window.location.origin);
  };

  // Messages from the frame.
  useEffect(() => {
    const onMessage = (e) => {
      if (e.origin !== window.location.origin || e.source !== frameRef.current?.contentWindow) return;
      const msg = e.data || {};
      if (msg.key !== section) return;
      if (msg.type === 'jk:preview-ready') {
        ready.current = true;
        setState('ready');
        if (latest.current) post(latest.current);
      } else if (msg.type === 'jk:preview-height' && msg.height > 0) {
        setHeight(msg.height);
      }
    };
    window.addEventListener('message', onMessage);
    return () => window.removeEventListener('message', onMessage);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [section]);

  // Panel width → scale.
  useEffect(() => {
    const el = boxRef.current;
    if (!el || typeof ResizeObserver === 'undefined') return undefined;
    const ro = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
    ro.observe(el);
    return () => ro.disconnect();
  }, [prefs.open]);

  // Values changed → fetch render data (debounced, latest request wins).
  const seq = useRef(0);
  useEffect(() => {
    if (!prefs.open || !values) return undefined;
    const my = ++seq.current;
    const t = setTimeout(async () => {
      setState((s) => (s === 'loading' ? s : 'updating'));
      try {
        const res = await api(`preview/${section}`, { method: 'POST', body: { values } });
        if (my !== seq.current) return;
        post(res.data);
        setState(ready.current ? 'ready' : 'loading');
      } catch {
        if (my === seq.current) setState('error');
      }
    }, DEBOUNCE_MS);
    return () => clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [values, prefs.open, section]);

  // A new frame (device switch, reopen) mounts before its scripts run, so it will
  // announce itself again with jk:preview-ready; until then, hold messages.
  useEffect(() => {
    ready.current = false;
    setState('loading');
  }, [prefs.device, prefs.open]);

  const status = { loading: 'Loading…', updating: 'Updating…', error: 'Preview unavailable', ready: 'Live' }[state];

  return (
    <section className="tw:mb-6 tw:overflow-hidden tw:rounded-2xl tw:border tw:border-neutral-200 tw:bg-white tw:shadow-sm" aria-label="Live preview">
      <header className="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:border-b tw:border-neutral-200 tw:px-6 tw:py-4">
        <div className="tw:flex tw:items-center tw:gap-3">
          <span className="dashicons dashicons-visibility tw:text-neutral-500" aria-hidden="true"></span>
          <div>
            <p className="tw:m-0 tw:text-sm tw:font-semibold tw:text-ink">Live preview</p>
            <p className="tw:m-0 tw:text-xs tw:text-neutral-500">Changes show here as you type, before you save.</p>
          </div>
          {prefs.open && (
            <span
              className={`tw:ml-2 tw:inline-flex tw:items-center tw:gap-1.5 tw:rounded-full tw:px-2.5 tw:py-0.5 tw:text-[11px] tw:font-semibold ${
                state === 'error' ? 'tw:bg-red-50 tw:text-red-700' : 'tw:bg-neutral-100 tw:text-neutral-600'
              }`}
              aria-live="polite"
            >
              <span className={`tw:size-1.5 tw:rounded-full ${state === 'ready' ? 'tw:bg-emerald-500' : state === 'error' ? 'tw:bg-red-500' : 'tw:bg-amber-500'}`}></span>
              {status}
            </span>
          )}
        </div>
        <div className="tw:flex tw:flex-wrap tw:items-center tw:gap-2">
          {prefs.open && (
            <div className="tw:inline-flex tw:rounded-full tw:border tw:border-neutral-200 tw:p-0.5" role="group" aria-label="Preview size">
              {Object.entries(DEVICES).map(([key, d]) => (
                <button
                  key={key}
                  type="button"
                  aria-pressed={prefs.device === key ? 'true' : 'false'}
                  className={`tw:rounded-full tw:border-0 tw:px-3 tw:py-1 tw:text-xs tw:font-semibold tw:cursor-pointer ${
                    prefs.device === key ? 'tw:bg-ink tw:text-white' : 'tw:bg-transparent tw:text-neutral-600'
                  }`}
                  onClick={() => update({ device: key })}
                >
                  {d.label}
                </button>
              ))}
            </div>
          )}
          <button type="button" className="jk-button jk-button-ghost" aria-expanded={prefs.open ? 'true' : 'false'} onClick={() => update({ open: !prefs.open })}>
            {prefs.open ? 'Hide preview' : 'Show preview'}
          </button>
        </div>
      </header>

      {prefs.open && (
        <div ref={boxRef} className="tw:bg-neutral-100 tw:p-4">
          <div className="tw:mx-auto tw:overflow-hidden tw:rounded-lg tw:bg-white tw:shadow-sm" style={{ width: device.width * scale, height: height * scale }}>
            <iframe
              key={prefs.device}
              ref={frameRef}
              src={src}
              title="Live preview"
              scrolling="no"
              style={{ width: device.width, height, border: 0, display: 'block', transform: `scale(${scale})`, transformOrigin: '0 0' }}
            />
          </div>
        </div>
      )}
    </section>
  );
}
