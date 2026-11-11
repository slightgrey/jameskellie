/**
 * Contact-form captcha: Cloudflare Turnstile, or Google reCAPTCHA v2 (checkbox) / v3 (invisible).
 * Config comes from James Kellie → Captcha via the boot data (JK\Captcha::client_config()).
 * Scripts load on first focus of the form, not on page load.
 */
import { useCallback, useRef } from 'react';

const scripts = new Map(); // src → Promise

function loadScript(src, ready) {
  if (!scripts.has(src)) {
    scripts.set(
      src,
      new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = () => (ready ? ready(resolve) : resolve());
        s.onerror = () => {
          scripts.delete(src);
          reject(new Error(`Failed to load ${src}`));
        };
        document.head.append(s);
      }),
    );
  }
  return scripts.get(src);
}

const TURNSTILE_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
const recaptchaSrc = (cfg) =>
  cfg.version === 'v3' ? `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(cfg.siteKey)}` : 'https://www.google.com/recaptcha/api.js?render=explicit';

/**
 * @param {{provider: 'turnstile'|'recaptcha', siteKey: string, version: 'v2'|'v3'}|null} cfg
 * @returns {{
 *   enabled: boolean, needsBox: boolean, boxRef: object, field: string,
 *   ensure: () => Promise<void>, getToken: () => Promise<string>, reset: () => void
 * }}
 */
export function useCaptcha(cfg) {
  const enabled = Boolean(cfg && cfg.siteKey);
  const turnstile = enabled && cfg.provider === 'turnstile';
  const recaptcha = enabled && cfg.provider === 'recaptcha';
  const v3 = recaptcha && cfg.version === 'v3';

  const boxRef = useRef(null);
  const widget = useRef(null); // null = not rendered, false = loading, else widget id
  const token = useRef('');

  const ensure = useCallback(async () => {
    if (!enabled || widget.current !== null) return;
    widget.current = false;
    try {
      if (turnstile) {
        await loadScript(TURNSTILE_SRC);
        if (!boxRef.current) throw new Error('no box');
        widget.current = window.turnstile.render(boxRef.current, {
          sitekey: cfg.siteKey,
          appearance: 'interaction-only',
          callback: (t) => (token.current = t),
          'expired-callback': () => (token.current = ''),
          'error-callback': () => (token.current = ''),
        });
      } else if (recaptcha) {
        await loadScript(recaptchaSrc(cfg), (done) => window.grecaptcha.ready(done));
        if (v3) {
          widget.current = 'v3';
        } else {
          if (!boxRef.current) throw new Error('no box');
          widget.current = window.grecaptcha.render(boxRef.current, { sitekey: cfg.siteKey });
        }
      }
    } catch {
      // The server still enforces the check, so the visitor gets a clear error on submit.
      widget.current = null;
    }
  }, [enabled, turnstile, recaptcha, v3, cfg]);

  /** A token for this submission, or '' (the server then rejects it with a message). */
  const getToken = useCallback(async () => {
    if (!enabled) return '';
    await ensure();
    if (turnstile) {
      const until = Date.now() + 10000;
      while (!token.current && Date.now() < until) await new Promise((r) => setTimeout(r, 200));
      return token.current;
    }
    if (!window.grecaptcha) return '';
    if (v3) return window.grecaptcha.execute(cfg.siteKey, { action: 'contact' });
    return widget.current !== null && widget.current !== false ? window.grecaptcha.getResponse(widget.current) : '';
  }, [enabled, turnstile, v3, cfg, ensure]);

  /** Tokens are single-use: reset after every attempt. */
  const reset = useCallback(() => {
    if (widget.current === null || widget.current === false) return;
    if (turnstile && window.turnstile) {
      window.turnstile.reset(widget.current);
      token.current = '';
    } else if (recaptcha && !v3 && window.grecaptcha) {
      window.grecaptcha.reset(widget.current);
    }
  }, [turnstile, recaptcha, v3]);

  return {
    enabled,
    // Turnstile (interaction-only) and the v2 checkbox render into a box; v3 is invisible.
    needsBox: turnstile || (recaptcha && !v3),
    boxRef,
    field: turnstile ? 'cf-turnstile-response' : 'g-recaptcha-response',
    ensure,
    getToken,
    reset,
  };
}
