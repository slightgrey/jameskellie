import Experience from '../components/Experience';

export default function ExperienceSection({ data, site }) {
  return (
    <section id={data.anchor || undefined} className="bg-mist">
      <div className="jk-container jk-section-y flex flex-wrap gap-12 md:gap-16">
        <div className="flex min-w-0 flex-[1_1_360px] flex-col gap-5">
          {data.eyebrow && <p className="jk-eyebrow">{data.eyebrow}</p>}
          <h2 className="jk-h2">{data.title}</h2>
          {/* Rich text from Theme Settings, sanitised server-side with wp_kses_post. */}
          {data.intro && <div className="jk-prose text-[17px] leading-[1.65] text-body" dangerouslySetInnerHTML={{ __html: data.intro }} />}
        </div>

        {data.roles.length > 0 && (
          <div className="relative flex min-w-0 flex-[999_1_560px] flex-col gap-4">
            <Experience prefix={data.prefix} roles={data.roles} openFirst={data.openFirst} hintLabel={data.hintLabel} hints={site.hints} />
          </div>
        )}
      </div>
    </section>
  );
}
