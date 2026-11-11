export default function NotFound({ site }) {
  const t = site.i18n;
  return (
    <section className="jk-container jk-section-y flex flex-col items-start gap-8">
      <p className="jk-eyebrow">{t.error404}</p>
      <h1 className="jk-h1">{t.notFound}</h1>
      <p className="max-w-[640px] text-[20px] leading-[1.6] text-body">{t.notFoundText}</p>
      <a href={site.home} className="jk-btn jk-btn-primary">
        {t.backHome}
      </a>
    </section>
  );
}
