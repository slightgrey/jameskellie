import CalendlyEmbed from '../components/CalendlyEmbed';
import { Img } from '../lib/media';

/** Book Appointment template: title block, Calendly embed and the sidebar. */
export default function Appointment({ data, site }) {
  return (
    <>
      <section className="jk-container flex flex-col gap-6 pt-14 pb-12 md:pt-[88px] md:pb-14">
        {data.eyebrow && <p className="jk-eyebrow">{data.eyebrow}</p>}
        <h1 className="max-w-[900px] text-[clamp(44px,6vw,84px)] leading-[0.98] font-bold tracking-[-0.045em]">{data.title}</h1>
        {data.intro && <p className="max-w-[620px] text-[18px] leading-[1.6] text-body sm:text-[19px]">{data.intro}</p>}
      </section>

      <section id="book" className="jk-container flex flex-wrap items-start gap-12 pb-[72px] md:pb-30">
        {data.calendly && (
          <div className="min-w-0 flex-[999_1_600px]">
            <CalendlyEmbed {...data.calendly} />
          </div>
        )}
        <aside className="flex min-w-0 flex-[1_1_300px] flex-col">
          {data.list.length > 0 && (
            <div className="flex flex-col gap-3.5 border-b border-line pb-7">
              {data.listTitle && <h2 className="text-[20px] font-semibold">{data.listTitle}</h2>}
              <ol className="flex flex-col gap-3 text-base text-body">
                {data.list.map((item, n) => (
                  <li key={n} className="flex gap-3.5">
                    <span className="font-display text-[#737373]">{String(n + 1).padStart(2, '0')}</span>
                    {item}
                  </li>
                ))}
              </ol>
            </div>
          )}
          {data.cardName && (
            <div className="flex items-center gap-4 border-b border-line py-7">
              <div className="size-16 flex-none overflow-hidden rounded-full bg-fog">
                <Img img={data.cardImage ? { ...data.cardImage, alt: '' } : null} className="size-full object-cover grayscale" loading="lazy" />
              </div>
              <div className="flex flex-col gap-1">
                <span className="text-[17px] font-semibold">{data.cardName}</span>
                {data.cardTagline && <span className="text-sm text-muted">{data.cardTagline}</span>}
              </div>
            </div>
          )}
          {site.phone && (
            <div className="flex flex-col gap-1.5 py-7">
              {data.callLabel && <span className="text-sm text-muted">{data.callLabel}</span>}
              <a href={site.tel} className="self-start font-display text-[30px] font-semibold tracking-[-0.02em] text-ink transition-colors hover:text-muted">
                {site.phone}
              </a>
            </div>
          )}
        </aside>
      </section>
    </>
  );
}
