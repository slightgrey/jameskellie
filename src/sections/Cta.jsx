import { useEffect, useRef } from 'react';
import { Icon } from '../lib/icons';
import { attachHint } from '../lib/hint';

export default function Cta({ data, site }) {
  const hintRef = useRef(null);

  // The round button gets a two-tap click hint.
  useEffect(() => {
    if (!site.hints || !hintRef.current) return undefined;
    return attachHint(hintRef.current, { targets: '[data-hint-target]', taps: 2, ring: '#FFFFFF' });
  }, [site.hints]);

  return (
    <section id={data.anchor || undefined} aria-labelledby="jk-cta-heading" className="overflow-hidden bg-paper">
      {data.tiles.length > 0 && (
        <nav aria-label={site.i18n.coreServices} className="jk-dark bg-ink text-paper">
          <div className="jk-container grid grid-cols-1 md:auto-cols-fr md:grid-flow-col">
            {data.tiles.map((tile, i) => (
              <a key={i} href={tile.url} className="jk-strip-tile">
                <span className="flex flex-col gap-2.5">
                  {tile.meta && <span className="font-display text-sm text-muted-dark">{tile.meta}</span>}
                  <span className="font-display text-[32px] leading-none font-bold tracking-[-0.03em] md:text-[36px]">{tile.name}</span>
                </span>
                <Icon name="arrow-up-right" size={26} stroke={1.8} className="jk-strip-arrow" />
              </a>
            ))}
          </div>
        </nav>
      )}

      <div className="jk-container jk-section-y flex flex-wrap items-center justify-between gap-10 md:gap-14">
        <div className="flex min-w-0 flex-[999_1_640px] flex-col gap-5">
          {data.lead && (
            <p className="max-w-[760px] font-display text-[clamp(24px,2.6vw,36px)] leading-[1.2] font-medium tracking-[-0.02em] text-[#737373]">{data.lead}</p>
          )}
          <h2 id="jk-cta-heading" className="text-[clamp(64px,10vw,152px)] leading-[0.9] font-extrabold tracking-[-0.055em]">
            {data.heading}
          </h2>
        </div>
        {data.button.label && data.button.url && (
          <div ref={hintRef} className="relative flex-none" data-hint={site.hints ? 'cta' : undefined}>
            <a href={data.button.url} className="jk-cta-btn" data-hint-target="">
              <Icon name="arrow-up-right" size={32} stroke={1.8} />
              {data.button.label}
            </a>
          </div>
        )}
      </div>
    </section>
  );
}
