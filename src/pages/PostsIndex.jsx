/** Posts index, archives and search results. */
export default function PostsIndex({ route, site }) {
  return (
    <section className="jk-container jk-section-y flex flex-col gap-12">
      <h1 className="jk-h1">{route.title}</h1>

      {route.items.length > 0 ? (
        <>
          <ul className="flex flex-col border-t border-ink">
            {route.items.map((item) => (
              <li key={item.url} className="border-b border-line">
                <a href={item.url} className="flex flex-col gap-2 py-8 transition-colors hover:text-muted">
                  <span className="text-sm text-muted">{item.date}</span>
                  <span className="font-display text-[28px] font-semibold tracking-[-0.02em]">{item.title}</span>
                </a>
              </li>
            ))}
          </ul>
          {(route.newer || route.older) && (
            <nav className="flex justify-between gap-6 text-[17px] font-semibold">
              {route.newer ? (
                <a href={route.newer} className="jk-link-u">
                  ← {site.i18n.newer}
                </a>
              ) : (
                <span />
              )}
              {route.older && (
                <a href={route.older} className="jk-link-u">
                  {site.i18n.older} →
                </a>
              )}
            </nav>
          )}
        </>
      ) : (
        <p className="text-[18px] text-body">{site.i18n.nothing}</p>
      )}
    </section>
  );
}
