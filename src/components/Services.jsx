import { Fragment, useCallback, useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { tabKeyHandler } from '../lib/tabs';
import { useHashTarget, useHint, useMediaQuery } from '../lib/hooks';

/**
 * Services: WAI-ARIA tabs on ≥768px, an accordion below.
 * Rendered by its section component in src/sections.
 */
function PanelBody({ item, bookLabel, bookUrl, toolsLabel }) {
  return (
    <>
      <div className="flex flex-wrap items-baseline justify-between gap-4">
        <span className="jk-svc-stat">{item.stat}</span>
        <span className="text-sm tracking-[0.14em] text-muted-dark uppercase">{item.statLabel}</span>
      </div>
      <h3 className="text-[28px] font-semibold tracking-[-0.02em] sm:text-[32px]">{item.title}</h3>
      <p className="max-w-[560px] text-[18px] leading-[1.65] text-body-dark">{item.body}</p>
      {item.tools.length > 0 && (
        <ul className="flex flex-wrap gap-2.5" aria-label={toolsLabel}>
          {item.tools.map((tool, i) => (
            <li key={i} className="rounded-full border border-[#525252] px-4 py-2 text-sm text-[#E5E5E5]">
              {tool}
            </li>
          ))}
        </ul>
      )}
      <a href={bookUrl} className="jk-link-u mt-auto self-start text-paper hover:text-paper">
        {`${bookLabel} ${item.title} →`}
      </a>
    </>
  );
}

export default function Services({ prefix, items, bookLabel, bookUrl, hintLabel, hints, listLabel, toolsLabel }) {
  const [active, setActive] = useState(0); // -1 = all collapsed (mobile only)
  const [interacted, setInteracted] = useState(false);
  const mobile = useMediaQuery('(max-width: 767.98px)');
  const hintRef = useRef(null);
  const tabRefs = useRef([]);

  useHint(hintRef, hints, { targets: '[data-hint-target]', taps: 1, ring: '#737373' });

  // Deep link: #services-google-ads selects that tab.
  useHashTarget(
    useCallback(
      (hash) => {
        const i = items.findIndex((it) => `${prefix}-${it.slug}` === hash);
        if (i >= 0) {
          setActive(i);
          setInteracted(true);
        }
      },
      [items, prefix],
    ),
  );

  const selected = active >= 0 ? active : 0;

  const select = (i) => {
    setInteracted(true);
    setActive((cur) => (mobile && cur === i ? -1 : i));
  };
  const onKeyDown = tabKeyHandler(
    () => tabRefs.current,
    (i) => {
      setInteracted(true);
      setActive(i);
    },
  );

  const shared = { bookLabel, bookUrl, toolsLabel };

  return (
    <div className={`flex flex-wrap items-stretch gap-10${interacted ? ' is-interactive' : ''}`}>
      <div ref={hintRef} className="relative flex min-w-0 flex-[1_1_400px] flex-col gap-4">
        <div
          role={mobile ? undefined : 'tablist'}
          aria-label={mobile ? undefined : listLabel}
          aria-orientation={mobile ? undefined : 'vertical'}
          className="flex flex-col border-t border-ink"
        >
          {items.map((item, i) => {
            const on = mobile ? active === i : selected === i;
            const id = `${prefix}-${item.slug}`;
            return (
              <Fragment key={item.slug}>
                <button
                  ref={(el) => (tabRefs.current[i] = el)}
                  type="button"
                  role={mobile ? undefined : 'tab'}
                  id={id}
                  aria-selected={mobile ? undefined : on ? 'true' : 'false'}
                  aria-expanded={mobile ? (on ? 'true' : 'false') : undefined}
                  aria-controls={mobile ? `${prefix}-mpanel-${item.slug}` : `${prefix}-panel-${item.slug}`}
                  tabIndex={mobile || on ? 0 : -1}
                  data-hint-target=""
                  className={`jk-svc-tab${on ? ' is-active' : ''}`}
                  onClick={() => select(i)}
                  onKeyDown={mobile ? undefined : (e) => onKeyDown(e, i)}
                >
                  <span className="jk-svc-num">{String(i + 1).padStart(2, '0')}</span>
                  <span className="jk-svc-title">{item.title}</span>
                  <Icon name="arrow-right" size={24} stroke={1.8} />
                </button>
                <div
                  id={`${prefix}-mpanel-${item.slug}`}
                  role="region"
                  aria-labelledby={id}
                  className="jk-svc-panel md:hidden"
                  hidden={!(mobile ? active === i : selected === i)}
                >
                  <PanelBody item={item} {...shared} />
                </div>
              </Fragment>
            );
          })}
        </div>
        {hints && hintLabel && (
          <span className="jk-hint-label" data-hint-label="">
            {hintLabel}
          </span>
        )}
      </div>
      <div className="hidden min-w-0 flex-[999_1_480px] md:flex">
        {items.map((item, i) => (
          <div
            key={item.slug}
            role="tabpanel"
            id={`${prefix}-panel-${item.slug}`}
            aria-labelledby={`${prefix}-${item.slug}`}
            tabIndex={0}
            className="jk-svc-panel flex-1"
            hidden={selected !== i}
          >
            <PanelBody item={item} {...shared} />
          </div>
        ))}
      </div>
    </div>
  );
}
