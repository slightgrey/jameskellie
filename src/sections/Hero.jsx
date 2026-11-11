import { Icon } from '../lib/icons';
import { Img } from '../lib/media';

export default function Hero({ data, site }) {
  const { anchor, eyebrow, title, intro, primary, phone, hasPhone, portrait, greyscale } = data;
  return (
    <section id={anchor || undefined} className="jk-container flex flex-wrap items-end gap-12 pt-14 pb-[72px] md:gap-16 md:pt-24 md:pb-20">
      <div className="flex min-w-0 flex-[2_1_480px] flex-col gap-8">
        {eyebrow && <p className="jk-eyebrow">{eyebrow}</p>}
        <h1 className="jk-h1">{title}</h1>
        {intro && <p className="max-w-[640px] text-[18px] leading-[1.6] text-body sm:text-[20px]">{intro}</p>}
        <div className="flex flex-wrap gap-3.5">
          {primary.label && primary.url && (
            <a href={primary.url} className="jk-btn jk-btn-primary">
              {primary.label}
              <Icon name="arrow-right" size={18} />
            </a>
          )}
          {phone && hasPhone && (
            <a href={site.tel} className="jk-btn jk-btn-outline">
              {phone}
            </a>
          )}
        </div>
      </div>
      {/* The portrait stands on the stats rule: the negative margin cancels the section bottom padding. */}
      <div className={`mx-auto aspect-[4/5] w-full max-w-[560px] min-w-0 flex-[1_1_380px] self-end overflow-hidden${portrait ? ' -mb-[72px] md:-mb-20' : ' bg-fog'}`}>
        {portrait ? (
          <Img img={portrait} className={greyscale ? 'size-full object-cover object-bottom grayscale' : 'size-full object-cover object-bottom'} fetchpriority="high" decoding="async" />
        ) : (
          <div className="flex size-full items-center justify-center" aria-hidden="true">
            <span className="font-logo text-[clamp(96px,14vw,160px)] leading-none font-bold text-line">JK</span>
          </div>
        )}
      </div>
    </section>
  );
}
