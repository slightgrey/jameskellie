import { useEffect, useRef, useState } from 'react';
import { SECTIONS } from '../pages/Home';

/**
 * Theme Settings live preview. Runs inside the admin's iframe (?jk_preview=hero) and renders
 * one Home section with the real component and CSS. The admin posts unsaved section data
 * ({ type: 'jk:preview', key, data }); this page reports its height back so the frame fits.
 */
export default function PreviewApp({ site, preview }) {
  const [data, setData] = useState(preview.data);
  const rootRef = useRef(null);
  const Section = SECTIONS[preview.key];

  // Data from the settings screen (same origin, parent window only).
  useEffect(() => {
    const onMessage = (e) => {
      if (e.origin !== window.location.origin || e.source !== window.parent) return;
      const msg = e.data;
      if (msg && msg.type === 'jk:preview' && msg.key === preview.key && msg.data) setData(msg.data);
    };
    window.addEventListener('message', onMessage);
    window.parent.postMessage({ type: 'jk:preview-ready', key: preview.key }, window.location.origin);
    return () => window.removeEventListener('message', onMessage);
  }, [preview.key]);

  // Report the content height whenever it changes (images loading, text wrapping).
  useEffect(() => {
    const el = rootRef.current;
    if (!el || typeof ResizeObserver === 'undefined') return undefined;
    const send = () => window.parent.postMessage({ type: 'jk:preview-height', key: preview.key, height: Math.ceil(el.getBoundingClientRect().height) }, window.location.origin);
    const ro = new ResizeObserver(send);
    ro.observe(el);
    send();
    return () => ro.disconnect();
  }, [preview.key]);

  // A preview, not a page: links do nothing, and the contact form never sends.
  const blockLinks = (e) => {
    if (e.target instanceof Element && e.target.closest('a')) e.preventDefault();
  };
  const blockSubmit = (e) => {
    // Capture phase + stopPropagation: the form's own onSubmit (which would POST) never runs.
    e.preventDefault();
    e.stopPropagation();
  };

  return (
    <div ref={rootRef} onClickCapture={blockLinks} onSubmitCapture={blockSubmit} className="jk-preview">
      <main id="main" data-spa-main="">
        {Section ? <Section data={data} site={site} /> : null}
        {/* The Hero portrait stands on the Stats rule, so show the rule it sits on. */}
        {preview.key === 'hero' && <div className="border-t border-ink" aria-hidden="true" />}
      </main>
    </div>
  );
}
