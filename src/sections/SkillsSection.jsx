import Skills from '../components/Skills';

export default function SkillsSection({ data, site }) {
  return (
    <section id={data.anchor || undefined} className="jk-dark bg-ink text-paper">
      <div className="jk-container jk-section-y flex flex-col gap-12">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <div className="flex flex-col gap-3.5">
            {data.eyebrow && <p className="jk-eyebrow">{data.eyebrow}</p>}
            <h2 className="jk-h2">{data.title}</h2>
          </div>
          {data.side && <p className="max-w-[480px] text-[17px] leading-[1.6] text-muted-dark">{data.side}</p>}
        </div>

        {data.tabs.length > 0 && (
          <div>
            <Skills prefix={data.prefix} tabs={data.tabs} hintLabel={data.hintLabel} hints={site.hints} listLabel={site.i18n.skillAreas} />
          </div>
        )}

        {data.industries.length > 0 && (
          <div className="flex flex-col gap-[18px]">
            {data.industriesLabel && <p className="jk-eyebrow">{data.industriesLabel}</p>}
            <ul className="flex flex-wrap gap-2.5">
              {data.industries.map((industry, i) => (
                <li key={i} className="jk-chip-static">
                  {industry}
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </section>
  );
}
