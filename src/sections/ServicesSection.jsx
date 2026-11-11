import Services from '../components/Services';

export default function ServicesSection({ data, site }) {
  return (
    <section id={data.anchor || undefined} className="jk-container jk-section-y flex flex-col gap-14">
      <div className="flex flex-wrap items-end justify-between gap-6">
        <div className="flex flex-col gap-3.5">
          {data.eyebrow && <p className="jk-eyebrow">{data.eyebrow}</p>}
          <h2 className="jk-h2">{data.title}</h2>
        </div>
        {data.side && <p className="max-w-[460px] text-[17px] leading-[1.6] text-body">{data.side}</p>}
      </div>

      {data.items.length > 0 && (
        <div>
          <Services
            prefix={data.prefix}
            items={data.items}
            bookLabel={data.bookLabel}
            bookUrl={site.appointmentUrl}
            hintLabel={data.hintLabel}
            hints={site.hints}
            listLabel={data.title}
            toolsLabel={site.i18n.tools}
          />
        </div>
      )}

      {data.closing && <p className="max-w-[820px] text-[18px] leading-[1.6] text-body">{data.closing}</p>}
    </section>
  );
}
