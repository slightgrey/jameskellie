import { useCallback, useEffect, useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { Img } from '../lib/media';

const trailing = (p) => p.replace(/\/?$/, '/');

/** Whether a nav link points at the current page (hash links never do). */
function isCurrent(url, path) {
  if (!url || url.includes('#')) return false;
  try {
    const u = new URL(url, window.location.href);
    if (u.origin !== window.location.origin) return false;
    return trailing(u.pathname) === trailing(path.split('?')[0]);
  } catch {
    return false;
  }
}

function Logo({ site }) {
  return (
    <a href={site.home} className="inline-flex items-center gap-3.5 text-ink" aria-label={site.i18n.logo} data-jk-logo="">
      {site.logo ? (
        <Img img={{ ...site.logo, alt: '' }} className="h-12 w-auto" />
      ) : (
        <>
          <span aria-hidden="true" className="flex size-12 items-center justify-center bg-ink font-logo text-[27px] leading-none font-bold tracking-[-0.01em] text-paper">
            JK
          </span>
          <span aria-hidden="true" className="inline-flex flex-col gap-1.5">
            <span className="-mr-[0.2em] font-logo text-[17px] leading-none font-bold tracking-[0.2em]">JAMES KELLIE</span>
            <span className="h-0.5 bg-ink"></span>
          </span>
        </>
      )}
    </a>
  );
}

/**
 * Site header: logo, primary nav, the Book-a-call CTA, the mobile menu (<900px)
 * and the sticky hide-on-scroll-down behaviour.
 */
export default function Header({ site, path }) {
  const [open, setOpen] = useState(false);
  const [hidden, setHidden] = useState(false);
  const headerRef = useRef(null);
  const btnRef = useRef(null);
  const menuRef = useRef(null);
  const openRef = useRef(false);
  openRef.current = open;

  const setBottom = useCallback(() => {
    const header = headerRef.current;
    if (!header) return;
    document.documentElement.style.setProperty('--jk-header-bottom', `${Math.max(0, Math.round(header.getBoundingClientRect().bottom))}px`);
  }, []);

  const close = useCallback((returnFocus = false) => {
    setOpen(false);
    if (returnFocus) btnRef.current?.focus();
  }, []);

  // Open state → html class, focus the first link.
  useEffect(() => {
    document.documentElement.classList.toggle('jk-menu-open', open);
    if (open) {
      setHidden(false);
      setBottom();
      menuRef.current?.querySelector('a[href]')?.focus();
    }
  }, [open, setBottom]);

  // Close on navigation.
  useEffect(() => setOpen(false), [path]);

  // Escape, focus trap, desktop breakpoint and resize.
  useEffect(() => {
    const desktop = window.matchMedia('(min-width: 900px)');
    const onKey = (e) => {
      if (!openRef.current) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        close(true);
        return;
      }
      if (e.key === 'Tab') {
        const items = [btnRef.current, ...(menuRef.current?.querySelectorAll('a[href]') ?? [])].filter(Boolean);
        const first = items[0];
        const last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };
    const onDesktop = () => desktop.matches && setOpen(false);
    const onResize = () => openRef.current && setBottom();
    document.addEventListener('keydown', onKey);
    desktop.addEventListener('change', onDesktop);
    window.addEventListener('resize', onResize, { passive: true });
    return () => {
      document.removeEventListener('keydown', onKey);
      desktop.removeEventListener('change', onDesktop);
      window.removeEventListener('resize', onResize);
    };
  }, [close, setBottom]);

  // Sticky: hide on scroll down after 120px, show on scroll up.
  useEffect(() => {
    if (!site.sticky) return undefined;
    let lastY = window.scrollY;
    let ticking = false;
    const onScroll = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        ticking = false;
        const y = window.scrollY;
        if (!openRef.current) {
          if (y > 120 && y > lastY + 4) setHidden(true);
          else if (y < lastY - 4 || y <= 120) setHidden(false);
        }
        lastY = y;
      });
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [site.sticky]);

  const cta = site.cta;

  return (
    <header
      id="top"
      ref={headerRef}
      className={`jk-header${hidden ? ' is-hidden' : ''}`}
      data-jk-header=""
      onFocus={() => setHidden(false)}
    >
      <div className="jk-container flex items-center justify-between gap-4 py-6">
        <Logo site={site} />
        <button
          ref={btnRef}
          type="button"
          className="jk-menu-btn"
          aria-expanded={open ? 'true' : 'false'}
          aria-controls="jk-menu"
          onClick={() => setOpen((o) => !o)}
        >
          <span className="sr-only">{site.i18n.menu}</span>
          <Icon name="menu" size={22} className="jk-icon-menu" />
          <Icon name="close" size={22} className="jk-icon-close" />
        </button>
        <nav
          id="jk-menu"
          ref={menuRef}
          aria-label={site.i18n.main}
          className={`jk-nav${open ? ' is-open' : ''}`}
          onClick={(e) => e.target instanceof Element && e.target.closest('a') && setOpen(false)}
        >
          {site.nav.map((link, i) => (
            <a key={i} href={link.url} className="jk-nav-link" aria-current={isCurrent(link.url, path) ? 'page' : undefined}>
              {link.label}
            </a>
          ))}
          {cta.label && cta.url && (
            <a href={cta.url} className="jk-nav-cta" aria-current={isCurrent(cta.url, path) ? 'page' : undefined}>
              {cta.label}
            </a>
          )}
        </nav>
      </div>
    </header>
  );
}
