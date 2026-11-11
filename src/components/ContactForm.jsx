import { useEffect, useRef, useState } from 'react';
import { Icon } from '../lib/icons';
import { track } from '../lib/dataLayer';

/**
 * Contact form card (rendered by src/sections/ContactSection.jsx).
 * Sends to POST /wp-json/jk/v1/contact; the same markup also works without JS
 * (it posts to admin-post.php).
 */

const TURNSTILE_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let turnstilePromise = null;

function loadTurnstile() {
  if (window.turnstile) return Promise.resolve(window.turnstile);
  if (!turnstilePromise) {
    turnstilePromise = new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = TURNSTILE_SRC;
      s.async = true;
      s.onload = () => resolve(window.turnstile);
      s.onerror = () => {
        turnstilePromise = null;
        reject(new Error('turnstile'));
      };
      document.head.append(s);
    });
  }
  return turnstilePromise;
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

function validate(values, labels) {
  const errors = {};
  const name = values.name.trim();
  const message = values.message.trim();
  if (name.length < 2 || name.length > 100) errors.name = labels.invalidName;
  if (!EMAIL_RE.test(values.email.trim())) errors.email = labels.invalidEmail;
  if (message.length < 10 || message.length > 5000) errors.message = labels.invalidMsg;
  return errors;
}

function Field({ prefix, name, label, placeholder, type = 'text', autoComplete, required, wide, textarea, value, error, onChange, inputRef }) {
  const id = `${prefix}-${name}`;
  const common = {
    id,
    name,
    className: 'jk-field',
    placeholder,
    required,
    value,
    onChange: (e) => onChange(name, e.target.value),
    'aria-invalid': error ? 'true' : undefined,
    'aria-describedby': error ? `${id}-error` : undefined,
    ref: inputRef,
  };
  return (
    <div className={`flex flex-col gap-2${wide ? ' sm:col-span-2' : ''}`}>
      <label htmlFor={id} className="text-sm font-semibold">
        {label}
        {required && <span aria-hidden="true"> *</span>}
      </label>
      {textarea ? <textarea rows={5} {...common} /> : <input type={type} autoComplete={autoComplete} {...common} />}
      {error && (
        <p id={`${id}-error`} className="flex items-center gap-1.5 text-sm font-semibold text-ink">
          <Icon name="alert" size={16} className="flex-none" />
          {error}
        </p>
      )}
    </div>
  );
}

export default function ContactForm(props) {
  const { prefix, heading, note, legend, topics, submit, success, error, callPrefix, phone, tel, rest, nonce, action, nojsNonce, ts, turnstile, labels } = props;

  const [status, setStatus] = useState(props.status || ''); // '' | 'sending' | 'success' | 'error'
  const [serverMessage, setServerMessage] = useState('');
  const [values, setValues] = useState({ name: '', phone: '', email: '', message: '' });
  const [topic, setTopic] = useState(topics[0] || '');
  const [errors, setErrors] = useState({});
  const [submitted, setSubmitted] = useState(false);

  const mountedAt = useRef(Date.now());
  const honeypot = useRef(null);
  const tsBox = useRef(null);
  const tsWidget = useRef(null);
  const tsToken = useRef('');
  const successRef = useRef(null);
  const fieldRefs = { name: useRef(null), email: useRef(null), message: useRef(null) };

  useEffect(() => {
    if (status === 'success') successRef.current?.focus();
  }, [status]);

  const ensureTurnstile = async () => {
    if (!turnstile || !tsBox.current || tsWidget.current !== null) return;
    tsWidget.current = false; // loading
    try {
      const ts = await loadTurnstile();
      tsWidget.current = ts.render(tsBox.current, {
        sitekey: turnstile,
        appearance: 'interaction-only',
        callback: (token) => (tsToken.current = token),
        'expired-callback': () => (tsToken.current = ''),
        'error-callback': () => (tsToken.current = ''),
      });
    } catch {
      tsWidget.current = null;
    }
  };

  const waitForToken = async () => {
    await ensureTurnstile();
    const until = Date.now() + 10000;
    while (!tsToken.current && Date.now() < until) {
      await new Promise((r) => setTimeout(r, 200));
    }
    return tsToken.current;
  };

  const onChange = (name, value) => {
    const next = { ...values, [name]: value };
    setValues(next);
    if (submitted) setErrors(validate(next, labels));
  };

  const onSubmit = async (e) => {
    e.preventDefault();
    if (status === 'sending') return;
    setSubmitted(true);
    setServerMessage('');

    const found = validate(values, labels);
    setErrors(found);
    const firstInvalid = ['name', 'email', 'message'].find((k) => found[k]);
    if (firstInvalid) {
      fieldRefs[firstInvalid].current?.focus();
      return;
    }

    setStatus('sending');
    try {
      const token = turnstile ? await waitForToken() : '';
      const res = await fetch(rest, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({
          ...values,
          topic,
          website: honeypot.current?.value || '',
          elapsed: (Date.now() - mountedAt.current) / 1000,
          page: window.location.href,
          'cf-turnstile-response': token,
        }),
      });
      const json = await res.json().catch(() => ({}));

      if (res.ok && json.ok) {
        track({ event: 'generate_lead', form: 'contact', topic });
        setStatus('success');
        return;
      }
      if (res.status === 422 && json.data && json.data.fields) {
        setErrors(json.data.fields);
        setStatus('');
        const first = ['name', 'email', 'message'].find((k) => json.data.fields[k]);
        if (first) fieldRefs[first].current?.focus();
        return;
      }
      // 400 (spam/nonce) and 429 (rate limit) carry a visitor-friendly reason. Anything else,
      // including core's 403 cookie-nonce failure, falls back to the settings error message.
      setServerMessage(res.status === 400 || res.status === 429 ? json.message || '' : '');
      setStatus('error');
    } catch {
      setStatus('error');
    } finally {
      if (tsWidget.current && window.turnstile) {
        window.turnstile.reset(tsWidget.current);
        tsToken.current = '';
      }
    }
  };

  if (status === 'success') {
    return (
      <div ref={successRef} className="jk-card jk-light flex flex-col items-start gap-5" role="status" tabIndex={-1}>
        <span aria-hidden="true" className="flex size-[52px] items-center justify-center rounded-full bg-ink text-paper">
          <Icon name="check" size={24} />
        </span>
        <p className="text-[20px] leading-[1.6] font-medium">{success}</p>
      </div>
    );
  }

  const sending = status === 'sending';

  return (
    <form
      className="jk-card jk-light flex flex-col gap-7"
      action={action}
      method="post"
      noValidate
      onSubmit={onSubmit}
      onFocus={turnstile ? ensureTurnstile : undefined}
    >
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col gap-1.5">
          <h3 className="text-[26px] font-bold tracking-[-0.03em] sm:text-[30px]">{heading}</h3>
          <p className="text-[15px] text-muted">{note}</p>
        </div>
        <span aria-hidden="true" className="flex size-[52px] flex-none items-center justify-center rounded-full bg-ink text-paper">
          <Icon name="message" size={22} />
        </span>
      </div>
      {topics.length > 0 && (
        <fieldset className="flex flex-col">
          <legend className="mb-3 text-sm font-semibold">{legend}</legend>
          <div className="flex flex-wrap gap-2.5">
            {topics.map((t) => (
              <label key={t} className="jk-chip">
                <input type="radio" name="topic" value={t} checked={topic === t} onChange={() => setTopic(t)} />
                <span>{t}</span>
              </label>
            ))}
          </div>
        </fieldset>
      )}
      <div className="grid grid-cols-1 gap-[18px] sm:grid-cols-2">
        <Field prefix={prefix} name="name" label={labels.name} placeholder={labels.namePh} autoComplete="name" required value={values.name} error={errors.name} onChange={onChange} inputRef={fieldRefs.name} />
        <Field prefix={prefix} name="phone" label={labels.phone} placeholder={labels.phonePh} type="tel" autoComplete="tel" value={values.phone} onChange={onChange} />
        <Field prefix={prefix} name="email" label={labels.email} placeholder={labels.emailPh} type="email" autoComplete="email" required wide value={values.email} error={errors.email} onChange={onChange} inputRef={fieldRefs.email} />
        <Field prefix={prefix} name="message" label={labels.message} placeholder={labels.messagePh} required wide textarea value={values.message} error={errors.message} onChange={onChange} inputRef={fieldRefs.message} />
      </div>
      <div className="jk-hp" aria-hidden="true">
        <label htmlFor={`${prefix}-website`}>{labels.website}</label>
        <input ref={honeypot} type="text" id={`${prefix}-website`} name="website" tabIndex={-1} autoComplete="off" defaultValue="" />
      </div>
      <input type="hidden" name="action" value="jk_contact" />
      <input type="hidden" name="_jk_nonce" value={nojsNonce} />
      <input type="hidden" name="jk_ts" value={String(ts)} />
      {turnstile && <div ref={tsBox} className="jk-turnstile" data-jk-turnstile=""></div>}
      {status === 'error' && (
        <p role="alert" className="flex items-start gap-2.5 rounded-xl border border-ink px-4 py-3 text-[15px] font-medium">
          <Icon name="alert" size={20} className="mt-0.5 flex-none" />
          <span>{serverMessage || error}</span>
        </p>
      )}
      <button type="submit" className="jk-submit" disabled={sending} aria-busy={sending ? 'true' : undefined}>
        {sending ? (
          <>
            <span className="jk-spinner" aria-hidden="true"></span>
            {labels.sending}
          </>
        ) : (
          <>
            {submit}
            <Icon name="arrow-right" size={20} />
          </>
        )}
      </button>
      {phone && (
        <p className="text-center text-[13px] text-muted">
          {callPrefix}{' '}
          <a href={tel} className="font-semibold text-ink underline-offset-2 hover:underline">
            {phone}
          </a>
        </p>
      )}
    </form>
  );
}
