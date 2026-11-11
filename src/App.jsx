import { useEffect, useRef, useState } from 'react';
import { useLocation, useNavigate, useNavigationType } from 'react-router-dom';
import Header from './layout/Header';
import Footer from './layout/Footer';
import BackToTop from './layout/BackToTop';
import Home from './pages/Home';
import Appointment from './pages/Appointment';
import Page from './pages/Page';
import PostsIndex from './pages/PostsIndex';
import NotFound from './pages/NotFound';
import { fetchRoute, navigated, pathOf, saveData, scrollToHash, spaUrl, syncHead } from './lib/navigation';
import { prefersReducedMotion, track } from './lib/dataLayer';

const FADE_MS = 120;
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

function RouteView({ route, site }) {
  switch (route.type) {
    case 'home':
      return <Home sections={route.sections} site={site} />;
    case 'appointment':
      return <Appointment data={route.data} site={site} />;
    case 'page':
      return <Page route={route} />;
    case 'posts':
      return <PostsIndex route={route} site={site} />;
    default:
      return <NotFound site={site} />;
  }
}

/**
 * The whole site. Direct loads render from the inlined boot data; internal links are
 * routed client-side (when SPA navigation is on in Theme Settings → Performance) and
 * each new URL's data is fetched from WordPress as JSON.
 */
export default function App({ site, initialRoute }) {
  const location = useLocation();
  const navigate = useNavigate();
  const navType = useNavigationType();

  const [view, setView] = useState(() => ({ path: pathOf(location), route: initialRoute }));
  const [leaving, setLeaving] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const viewRef = useRef(view);
  viewRef.current = view;
  const keyRef = useRef(location.key);
  keyRef.current = location.key;
  const scrolls = useRef(new Map()); // history entry key → scrollY
  const pending = useRef(null); // what to do once the new route has rendered
  const token = useRef(0);
  const firstRun = useRef(true);
  const mainRef = useRef(null);

  // First load: the browser can't jump to a hash in markup React renders later.
  useEffect(() => {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    if (window.location.hash) requestAnimationFrame(() => scrollToHash(window.location.hash, false));
    navigated();

    let t = 0;
    const onScroll = () => {
      clearTimeout(t);
      t = setTimeout(() => scrolls.current.set(keyRef.current, window.scrollY), 100);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  // Route internal links through React Router. WordPress content (menus, post
  // content, settings URLs) is plain <a> markup, so this listens on the document.
  useEffect(() => {
    if (!site.spa || !window.history) return undefined;

    const onClick = (e) => {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const a = e.target instanceof Element ? e.target.closest('a') : null;
      const url = spaUrl(a);
      if (!url) return;
      e.preventDefault();
      scrolls.current.set(keyRef.current, window.scrollY);
      const to = url.pathname + url.search + url.hash;
      if (to === window.location.pathname + window.location.search + window.location.hash) {
        scrollToHash(url.hash || '#top', true);
        navigated();
        return;
      }
      navigate(to);
    };

    const onIntent = (e) => {
      const a = e.target instanceof Element ? e.target.closest('a') : null;
      const url = spaUrl(a);
      if (!url) return;
      const path = url.pathname + url.search;
      if (path !== viewRef.current.path) fetchRoute(path).catch(() => {});
    };

    document.addEventListener('click', onClick);
    const prefetch = site.prefetch && !saveData();
    if (prefetch) {
      document.addEventListener('pointerover', onIntent, { passive: true });
      document.addEventListener('touchstart', onIntent, { passive: true });
    }
    return () => {
      document.removeEventListener('click', onClick);
      document.removeEventListener('pointerover', onIntent);
      document.removeEventListener('touchstart', onIntent);
    };
  }, [site.spa, site.prefetch, navigate]);

  // Location changed: same page → scroll; new page → fetch, fade, swap.
  useEffect(() => {
    if (firstRun.current) {
      firstRun.current = false;
      return;
    }
    const path = pathOf(location);
    const hash = location.hash;
    const restoreY = navType === 'POP' ? (scrolls.current.get(location.key) ?? null) : null;

    if (path === viewRef.current.path) {
      if (restoreY !== null) window.scrollTo(0, restoreY);
      else scrollToHash(hash || '#top', true);
      navigated();
      return;
    }

    const my = ++token.current;
    const animate = !prefersReducedMotion();
    if (animate) setLeaving(true);

    Promise.all([fetchRoute(path), animate ? wait(FADE_MS) : null])
      .then(([data]) => {
        if (my !== token.current) return;
        syncHead(data);
        pending.current = { y: restoreY, hash };
        setView({ path: data.path, route: data.route });
        setLeaving(false);
        // WordPress redirected (e.g. a missing trailing slash): show the final URL.
        if (data.path !== path) navigate(data.path + hash, { replace: true });
      })
      .catch(() => {
        if (my === token.current) window.location.assign(path + hash);
      });
  }, [location, navType, navigate]);

  // After a new route renders: scroll, move focus, announce and track.
  useEffect(() => {
    const p = pending.current;
    if (!p) return;
    pending.current = null;

    if (p.y !== null) window.scrollTo(0, p.y);
    else if (p.hash) scrollToHash(p.hash, false);
    else window.scrollTo(0, 0);

    const h1 = mainRef.current?.querySelector('h1');
    if (h1) {
      h1.setAttribute('tabindex', '-1');
      h1.focus({ preventScroll: true });
    }
    setAnnouncement('');
    setTimeout(() => setAnnouncement(document.title), 60);
    track({
      event: 'virtual_page_view',
      page_path: view.path,
      page_title: document.title,
      page_location: window.location.href,
    });
    navigated();
  }, [view]);

  return (
    <>
      <a className="jk-skip" href="#main" data-no-spa="">
        {site.i18n.skip}
      </a>
      <Header site={site} path={view.path} />
      <main id="main" data-spa-main="" tabIndex={-1} ref={mainRef} className={leaving ? 'is-leaving' : undefined}>
        <RouteView key={view.path} route={view.route} site={site} />
      </main>
      <Footer site={site} />
      {site.backToTop && <BackToTop label={site.i18n.backToTop} />}
      <div id="jk-announcer" className="sr-only" aria-live="polite" aria-atomic="true">
        {announcement}
      </div>
    </>
  );
}
