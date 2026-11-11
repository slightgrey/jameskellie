import { useRef, useState } from 'react';
import { tabKeyHandler } from '../lib/tabs';
import { useHint } from '../lib/hooks';

/**
 * Skills: pill tabs over a numbered grid. Panels are stacked in one grid cell so the
 * area keeps the tallest set's height and never jumps when the item count changes.
 * Rendered by its section component in src/sections.
 */
export default function Skills({ prefix, tabs, hintLabel, hints, listLabel }) {
  const [active, setActive] = useState(0);
  const hintRef = useRef(null);
  const tabRefs = useRef([]);

  useHint(hintRef, hints, { targets: '[role="tab"]', taps: 1, ring: '#FFFFFF' });

  const onKeyDown = tabKeyHandler(() => tabRefs.current, setActive);

  return (
    <div className="flex flex-col gap-12">
      <div ref={hintRef} className="relative flex flex-wrap items-center gap-x-6 gap-y-4">
        <div role="tablist" aria-label={listLabel} className="flex flex-wrap gap-2.5">
          {tabs.map((tab, i) => {
            const on = active === i;
            return (
              <button
                key={i}
                ref={(el) => (tabRefs.current[i] = el)}
                type="button"
                role="tab"
                id={`${prefix}-tab-${i}`}
                aria-selected={on ? 'true' : 'false'}
                aria-controls={`${prefix}-panel-${i}`}
                tabIndex={on ? 0 : -1}
                data-hint-target=""
                className={`jk-skill-tab${on ? ' is-active' : ''}`}
                onClick={() => setActive(i)}
                onKeyDown={(e) => onKeyDown(e, i)}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
        {hints && hintLabel && (
          <span className="jk-hint-label" data-hint-label="">
            {hintLabel}
          </span>
        )}
      </div>
      <div className="grid">
        {tabs.map((tab, i) => (
          <div
            key={i}
            role="tabpanel"
            id={`${prefix}-panel-${i}`}
            aria-labelledby={`${prefix}-tab-${i}`}
            tabIndex={0}
            className={`jk-skill-panel${active === i ? '' : ' is-inactive'}`}
          >
            <ul className="grid grid-cols-2 border-t border-l border-line-dark sm:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
              {tab.items.map((name, n) => (
                <li key={n} className="flex min-h-32 flex-col gap-5 border-r border-b border-line-dark px-4 py-6 sm:min-h-40 sm:gap-7 sm:px-7 sm:py-8">
                  <span className="font-display text-sm text-muted-dark">{String(n + 1).padStart(2, '0')}</span>
                  <span className="font-display text-[17px] leading-[1.25] font-semibold tracking-[-0.01em] sm:text-[22px]">{name}</span>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  );
}
