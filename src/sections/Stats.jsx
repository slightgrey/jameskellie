export default function Stats({ data }) {
  if (!data.items.length) return null;
  return (
    <section id={data.anchor || undefined} aria-label={data.label} className="border-y border-ink">
      <dl className="jk-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        {data.items.map((item, i) => (
          <div key={i} className="flex flex-col-reverse justify-end py-7 pr-6 sm:py-9">
            <dt className="mt-1.5 text-[15px] text-muted">{item.label}</dt>
            <dd className="font-display text-[44px] leading-[1.1] font-bold tracking-[-0.03em] sm:text-[52px]">{item.number}</dd>
          </div>
        ))}
      </dl>
    </section>
  );
}
