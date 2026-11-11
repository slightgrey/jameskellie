import { useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { useHint } from '../lib/hooks';

/**
 * Experience: one role open at a time; clicking the open role closes it.
 * Rendered by its section component in src/sections.
 */
export default function Experience({ prefix, roles, openFirst, hintLabel, hints }) {
  const [open, setOpen] = useState(openFirst ? 0 : -1);
  const listRef = useRef(null);
  // The island wrapper (position: relative, printed by PHP) is the hint container.
  const hintRoot = useRef({
    get current() {
      return listRef.current?.parentElement ?? null;
    },
  }).current;

  useHint(hintRoot, hints, { targets: '[data-hint-target]', taps: 1, ring: '#737373' });

  return (
    <>
      <ol ref={listRef} className="flex flex-col gap-3">
        {roles.map((role, i) => {
          const isOpen = open === i;
          return (
            <li key={i} className={`jk-role${isOpen ? ' is-open' : ''}`}>
              <button
                type="button"
                id={`${prefix}-role-${i}`}
                aria-expanded={isOpen ? 'true' : 'false'}
                aria-controls={`${prefix}-desc-${i}`}
                className="jk-role-btn"
                data-hint-target={isOpen ? undefined : ''}
                onClick={() => setOpen(isOpen ? -1 : i)}
              >
                <span className="jk-role-years">{role.years}</span>
                <span className="flex flex-[1_1_240px] flex-col gap-1">
                  <span className="jk-role-company">{role.company}</span>
                  <span className="jk-role-role">{role.role}</span>
                </span>
                <Icon name={isOpen ? 'minus' : 'plus'} size={22} />
              </button>
              <div
                id={`${prefix}-desc-${i}`}
                role="region"
                aria-labelledby={`${prefix}-role-${i}`}
                className="jk-collapse"
                data-open={isOpen ? '' : undefined}
                {...(isOpen ? {} : { inert: '' })}
              >
                <div>
                  <div className="flex flex-wrap gap-x-8 gap-y-2 px-6 pb-8 sm:px-8">
                    <span className="hidden flex-[0_0_140px] sm:block" aria-hidden="true"></span>
                    <p className="min-w-0 flex-[1_1_320px] text-[17px] leading-[1.6] text-body-dark">{role.description}</p>
                  </div>
                </div>
              </div>
            </li>
          );
        })}
      </ol>
      {hints && hintLabel && (
        <span className="jk-hint-label" data-hint-label="">
          {hintLabel}
        </span>
      )}
    </>
  );
}
