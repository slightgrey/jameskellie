import { useLocation } from 'react-router-dom';
import ContactForm from '../components/ContactForm';

export default function ContactSection({ data, site }) {
  const { search } = useLocation();
  const params = new URLSearchParams(search);
  // Flags from the no-JS form redirect (admin-post.php → ?jk_sent=1 / ?jk_error=1).
  const status = params.has('jk_sent') ? 'success' : params.has('jk_error') ? 'error' : '';

  return (
    <section id={data.anchor || undefined} className="jk-dark border-t border-[#262626] bg-ink text-paper">
      <div className="jk-container jk-section-y flex flex-wrap gap-12 md:gap-18">
        <div className="flex min-w-0 flex-[1_1_380px] flex-col gap-7">
          {data.eyebrow && <p className="jk-eyebrow">{data.eyebrow}</p>}
          <h2 className="text-[clamp(48px,6vw,88px)] leading-[0.95] font-bold tracking-[-0.045em]">{data.title}</h2>
          {site.phone && (
            <div className="flex flex-col gap-1.5 pt-3">
              <span className="text-sm tracking-[0.14em] text-muted-dark uppercase">{data.phoneLabel}</span>
              <a href={site.tel} className="self-start font-display text-[34px] font-semibold tracking-[-0.02em] text-paper transition-colors hover:text-body-dark">
                {site.phone}
              </a>
            </div>
          )}
          {data.bookLabel && (
            <a href={site.appointmentUrl} className="jk-btn jk-btn-light self-start">
              {data.bookLabel}
            </a>
          )}
        </div>

        <div className="min-w-0 flex-[1_1_520px]">
          <ContactForm
            prefix="jk-contact"
            heading={data.heading}
            note={data.note}
            legend={data.legend}
            topics={data.topics}
            submit={data.submit}
            success={data.success}
            error={data.error}
            callPrefix={data.callPrefix}
            phone={site.phone}
            tel={site.tel}
            rest={site.contact.rest}
            nonce={site.contact.nonce}
            action={site.contact.action}
            nojsNonce={site.contact.nojsNonce}
            ts={site.contact.ts}
            turnstile={site.contact.turnstile}
            status={status}
            labels={site.i18n.contact}
          />
        </div>
      </div>
    </section>
  );
}
