import { SECTIONS } from './Home';

const PROSE = 'jk-prose text-[18px] leading-relaxed text-body';
const hasH1Html = (html) => /<h1[\s>]/i.test(html || '');

function Title({ route }) {
  return (
    <header className="flex max-w-[900px] flex-col gap-6 pb-12">
      {route.date && <p className="text-sm text-muted">{route.date}</p>}
      <h1 className="jk-h1">{route.title}</h1>
    </header>
  );
}

/**
 * Generic page or single post.
 *
 * - No James Kellie blocks: the title and the block editor content (as before).
 * - With James Kellie blocks (route.blocks): each JK block renders with its Home section
 *   component, edge to edge; other content between them sits in the container.
 * - Page templates (route.template): 'default' keeps the title (unless a Hero block provides
 *   the H1) and the narrow text column; 'full-width' and 'canvas' drop both. Canvas also hides
 *   the header and footer (App.jsx). Without a visible H1 the title stays as an sr-only H1.
 */
export default function Page({ route, site }) {
  const wide = route.template === 'full-width' || route.template === 'canvas';
  const blocks = route.blocks;

  if (!blocks) {
    if (!wide) {
      return (
        <article className="jk-container jk-section-y">
          <Title route={route} />
          {/* the_content output from WordPress (already filtered for the author's capabilities). */}
          <div className={`${PROSE} max-w-[760px]`} dangerouslySetInnerHTML={{ __html: route.content }} />
        </article>
      );
    }
    return (
      <article className="jk-container jk-section-y">
        {!hasH1Html(route.content) && <h1 className="sr-only">{route.title}</h1>}
        <div className={PROSE} dangerouslySetInnerHTML={{ __html: route.content }} />
      </article>
    );
  }

  const hasH1 = blocks.some((b) => (b.type === 'section' ? b.key === 'hero' : hasH1Html(b.html)));
  const showTitle = !wide && !hasH1;

  return (
    <article>
      {showTitle && (
        <div className="jk-container pt-[72px] md:pt-[120px]">
          <Title route={route} />
        </div>
      )}
      {!showTitle && !hasH1 && <h1 className="sr-only">{route.title}</h1>}
      {blocks.map((block, i) => {
        if (block.type === 'section') {
          const Section = SECTIONS[block.key];
          return Section ? <Section key={i} data={block.data} site={site} /> : null;
        }
        return (
          <div key={i} className="jk-container py-12 md:py-16">
            <div className={wide ? PROSE : `${PROSE} max-w-[760px]`} dangerouslySetInnerHTML={{ __html: block.html }} />
          </div>
        );
      })}
    </article>
  );
}
