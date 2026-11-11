import { useEffect, useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { track } from '../lib/dataLayer';

/**
 * Calendly inline embed as a plain iframe (no widget.js).
 * The iframe is only rendered once the box is within 300px of the viewport (when lazy),
 * with a skeleton until it loads. Rendered by src/pages/Appointment.jsx.
 */
export default function CalendlyEmbed({ src, url, lazy, title, loading, open }) {
  const boxRef = useRef(null);
  const [near, setNear] = useState(!lazy);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    if (near) return undefined;
    if (typeof IntersectionObserver === 'undefined') {
      setNear(true);
      return undefined;
    }
    const io = new IntersectionObserver(
      (entries) => {
        if (entries.some((e) => e.isIntersecting)) {
          setNear(true);
          io.disconnect();
        }
      },
      { rootMargin: '300px 0px' },
    );
    io.observe(boxRef.current);
    return () => io.disconnect();
  }, [near]);

  useEffect(() => {
    const onMessage = (e) => {
      if (e.origin !== 'https://calendly.com' || !e.data || typeof e.data !== 'object') return;
      if (e.data.event === 'calendly.event_scheduled') track({ event: 'appointment_booked' });
    };
    window.addEventListener('message', onMessage);
    return () => window.removeEventListener('message', onMessage);
  }, []);

  return (
    <div ref={boxRef} className="relative h-[1000px] overflow-hidden border border-ink md:h-[720px]">
      {!loaded && (
        <div className="jk-skeleton absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 p-6 text-center text-muted">
          <Icon name="calendar" size={40} stroke={1.5} className="text-ink" />
          <span className="font-semibold text-ink">{loading}</span>
          <a href={url} className="text-sm underline underline-offset-2" target="_blank" rel="noopener">
            {open}
          </a>
        </div>
      )}
      {near && (
        <iframe
          src={src}
          title={title}
          loading="lazy"
          className="absolute inset-0 size-full border-0"
          onLoad={() => setLoaded(true)}
        ></iframe>
      )}
    </div>
  );
}
