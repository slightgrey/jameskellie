import { useEffect, useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { prefersReducedMotion } from '../lib/dataLayer';

/**
 * Back to top: shown after 600px of scroll (IntersectionObserver on a 1px sentinel);
 * inert and untabbable while hidden.
 */
export default function BackToTop({ label }) {
  const [visible, setVisible] = useState(false);
  const sentinel = useRef(null);

  useEffect(() => {
    if (typeof IntersectionObserver === 'undefined' || !sentinel.current) return undefined;
    const io = new IntersectionObserver(([e]) => setVisible(!e.isIntersecting && e.boundingClientRect.top < 0));
    io.observe(sentinel.current);
    return () => io.disconnect();
  }, []);

  const onClick = (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    document.querySelector('[data-jk-logo]')?.focus({ preventScroll: true });
  };

  return (
    <>
      <div
        ref={sentinel}
        aria-hidden="true"
        style={{ position: 'absolute', top: '600px', left: 0, width: '1px', height: '1px', pointerEvents: 'none' }}
      />
      <a
        href="#top"
        className={`jk-btt${visible ? ' is-visible' : ''}`}
        aria-label={label}
        data-no-spa=""
        onClick={onClick}
        tabIndex={visible ? undefined : -1}
        {...(visible ? {} : { inert: '' })}
      >
        <Icon name="arrow-up" size={20} />
      </a>
    </>
  );
}
