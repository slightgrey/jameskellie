/** Generic page or single post: title and the block editor content. */
export default function Page({ route }) {
  return (
    <article className="jk-container jk-section-y">
      <header className="flex max-w-[900px] flex-col gap-6 pb-12">
        {route.date && <p className="text-sm text-muted">{route.date}</p>}
        <h1 className="jk-h1">{route.title}</h1>
      </header>
      {/* the_content output from WordPress (already filtered for the author's capabilities). */}
      <div className="jk-prose max-w-[760px] text-[18px] leading-relaxed text-body" dangerouslySetInnerHTML={{ __html: route.content }} />
    </article>
  );
}
