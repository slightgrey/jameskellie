/** Site footer: JK mark, copyright and social links. */
export default function Footer({ site }) {
  return (
    <footer className="jk-dark border-t border-[#262626] bg-ink text-muted-dark">
      <div className="jk-container flex flex-wrap items-center justify-between gap-4 py-8 text-sm">
        <span className="inline-flex items-center gap-4">
          <span aria-hidden="true" className="flex size-9 items-center justify-center border border-[#525252] font-logo text-[19px] leading-none font-bold text-paper">
            JK
          </span>
          {site.copyright}
        </span>
        {site.social.length > 0 && (
          <nav aria-label={site.i18n.social} className="flex flex-wrap gap-6">
            {site.social.map((row, i) => (
              <a key={i} href={row.url} className="text-body-dark transition-colors hover:text-paper" rel="me noopener" data-no-spa="">
                {row.label}
              </a>
            ))}
          </nav>
        )}
      </div>
    </footer>
  );
}
