import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import cfg, { api } from './api';
import { FieldContext, FieldTable } from './fields';
import Preview, { usePreviewPrefs } from './Preview';

const DISPLAY = { fontFamily: "'Roboto Condensed','Arial Narrow',sans-serif" };

const sectionInfo = (key) => {
  for (const group of cfg.groups) {
    const found = group.items.find((it) => it.key === key);
    if (found) return found;
  }
  return { key, title: key, icon: 'dashicons-admin-generic', description: '', home: false };
};

/** Keep wp-admin's own submenu highlight in step with the in-app section. */
function syncWpMenu(section) {
  document.querySelectorAll('#toplevel_page_jk-general .wp-submenu li').forEach((li) => {
    const a = li.querySelector('a');
    const on = Boolean(a && new URL(a.href, window.location.href).searchParams.get('page') === `jk-${section}`);
    li.classList.toggle('current', on);
    a?.classList.toggle('current', on);
    if (on) a?.setAttribute('aria-current', 'page');
    else a?.removeAttribute('aria-current');
  });
}

function Notice({ notice, onClose }) {
  if (!notice) return null;
  return (
    <div className={`notice notice-${notice.type} is-dismissible`} role={notice.type === 'error' ? 'alert' : 'status'}>
      <p>{notice.text}</p>
      <button type="button" className="notice-dismiss" onClick={onClose}>
        <span className="screen-reader-text">Dismiss this notice.</span>
      </button>
    </div>
  );
}

function Header() {
  return (
    <div className="jk-dark tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4 tw:rounded-2xl tw:bg-ink tw:px-6 tw:py-5 tw:text-white">
      <div className="tw:flex tw:items-center tw:gap-4">
        <span className="tw:flex tw:size-11 tw:items-center tw:justify-center tw:bg-white tw:text-[24px] tw:leading-none tw:font-bold tw:text-ink" style={DISPLAY} aria-hidden="true">
          JK
        </span>
        <div>
          <p className="tw:m-0 tw:text-[15px] tw:font-bold tw:tracking-[0.16em] tw:text-white" style={DISPLAY}>
            JAMES KELLIE
          </p>
          <p className="tw:m-0 tw:text-xs tw:text-neutral-400">Theme settings</p>
        </div>
      </div>
      <div className="tw:flex tw:flex-wrap tw:gap-2">
        <a className="jk-button jk-button-outline-light" href={cfg.enquiries.url}>
          Enquiries
          {cfg.enquiries.count > 0 && (
            <span className="tw:rounded-full tw:bg-white tw:px-2 tw:py-0.5 tw:text-[11px] tw:font-bold tw:text-ink">{cfg.enquiries.count.toLocaleString()}</span>
          )}
        </a>
        <a className="jk-button jk-button-light" href={cfg.homeUrl} target="_blank" rel="noopener">
          View site <span aria-hidden="true">↗</span>
        </a>
      </div>
    </div>
  );
}

function Sidebar({ current, enabled, onSelect }) {
  return (
    <nav
      className="tw:flex tw:flex-wrap tw:gap-1 tw:min-[961px]:sticky tw:min-[961px]:top-12 tw:min-[961px]:flex-col tw:min-[961px]:flex-nowrap tw:min-[961px]:gap-5"
      aria-label="Theme settings sections"
    >
      {cfg.groups.map((group) => (
        <div key={group.label} className="tw:contents tw:min-[961px]:block">
          <p className="tw:m-0 tw:mb-1.5 tw:hidden tw:px-3 tw:text-[11px] tw:font-semibold tw:tracking-[0.12em] tw:text-neutral-400 tw:uppercase tw:min-[961px]:block">{group.label}</p>
          <ul className="tw:m-0 tw:contents tw:min-[961px]:flex tw:min-[961px]:flex-col tw:min-[961px]:gap-0.5">
            {group.items.map((it) => {
              const hidden = it.home && !enabled[it.key];
              return (
                <li key={it.key} className="tw:m-0 tw:list-none">
                  <a
                    className="jk-nav-link"
                    href={`${cfg.adminUrl}?page=jk-${it.key}`}
                    aria-current={it.key === current ? 'page' : undefined}
                    onClick={(e) => {
                      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
                      e.preventDefault();
                      onSelect(it.key);
                    }}
                  >
                    <span className={`dashicons ${it.icon}`} aria-hidden="true"></span>
                    <span className="tw:flex-1">{it.title}</span>
                    {hidden && <span className="tw:rounded-full tw:bg-neutral-200 tw:px-1.5 tw:py-0.5 tw:text-[10px] tw:font-semibold tw:text-neutral-500">Hidden</span>}
                  </a>
                </li>
              );
            })}
          </ul>
        </div>
      ))}
    </nav>
  );
}

function StatusPanel() {
  return (
    <div className="tw:grid tw:grid-cols-2 tw:gap-3 tw:border-b tw:border-neutral-200 tw:bg-neutral-50 tw:px-8 tw:py-6 tw:sm:grid-cols-3 tw:xl:grid-cols-5">
      {cfg.status.map(([label, value]) => (
        <div key={label} className="tw:rounded-xl tw:border tw:border-neutral-200 tw:bg-white tw:px-4 tw:py-3">
          <p className="tw:m-0 tw:text-[11px] tw:font-semibold tw:tracking-[0.1em] tw:text-neutral-400 tw:uppercase">{label}</p>
          <p className="tw:mt-1 tw:mb-0 tw:text-base tw:font-semibold tw:text-ink">{value}</p>
        </div>
      ))}
    </div>
  );
}

function ImportExport({ onImported, notify }) {
  const fileRef = useRef(null);
  const [busy, setBusy] = useState('');
  const card = 'tw:flex tw:flex-col tw:gap-3 tw:rounded-xl tw:border tw:border-neutral-200 tw:p-5';
  const title = 'tw:m-0 tw:text-sm tw:font-semibold tw:text-ink';
  const text = 'tw:m-0 tw:text-xs tw:leading-relaxed tw:text-neutral-500';

  const exportFile = async () => {
    setBusy('export');
    try {
      const { filename, data } = await api('export');
      const blob = new Blob([JSON.stringify(data, null, 4)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.append(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (err) {
      notify('error', err.message);
    } finally {
      setBusy('');
    }
  };

  const importFile = async (e) => {
    e.preventDefault();
    const file = fileRef.current?.files?.[0];
    if (!file) return;
    if (!window.confirm('Importing replaces the current settings. Continue?')) return;
    setBusy('import');
    try {
      let data;
      try {
        data = JSON.parse(await file.text());
      } catch {
        throw new Error('Import failed: the file is not a valid James Kellie settings export.');
      }
      const res = await api('import', { method: 'POST', body: data });
      fileRef.current.value = '';
      onImported(res.enabled);
      notify('success', 'Settings imported.');
    } catch (err) {
      notify('error', err.message);
    } finally {
      setBusy('');
    }
  };

  return (
    <section className="tw:mt-6 tw:rounded-2xl tw:border tw:border-neutral-200 tw:bg-white tw:px-8 tw:py-7 tw:shadow-sm">
      <p className="tw:m-0 tw:text-lg tw:font-bold tw:text-ink tw:font-display">Export / Import settings</p>
      <p className="tw:mt-1 tw:mb-5 tw:text-sm tw:text-neutral-500">Move every theme setting, and the Book Appointment page content, between sites (e.g. Laragon → live).</p>
      <div className="tw:grid tw:gap-4 tw:md:grid-cols-2">
        <div className={card}>
          <p className={title}>Export</p>
          <p className={text}>Downloads a .json file of the current settings.</p>
          <span>
            <button type="button" className="jk-button jk-button-ghost" onClick={exportFile} disabled={busy !== ''}>
              {busy === 'export' ? 'Preparing…' : 'Download export'}
            </button>
          </span>
        </div>
        <form className={card} onSubmit={importFile}>
          <p className={title}>Import</p>
          <label className={text} htmlFor="jk-import-file">
            Choose an export file. It replaces the current settings.
          </label>
          <input ref={fileRef} type="file" id="jk-import-file" accept="application/json,.json" required className="tw:text-xs" />
          <span>
            <button type="submit" className="jk-button jk-button-ghost" disabled={busy !== ''}>
              {busy === 'import' ? 'Importing…' : 'Import settings'}
            </button>
          </span>
        </form>
      </div>
    </section>
  );
}

/**
 * Theme Settings: one screen per jk_* section, switched in-app.
 */
export default function AdminApp() {
  const [section, setSection] = useState(cfg.section);
  const [values, setValues] = useState(null);
  const [saved, setSaved] = useState('');
  const [images, setImages] = useState({});
  const [enabled, setEnabled] = useState(cfg.enabled);
  const [status, setStatus] = useState('loading'); // loading | ready | saving | error
  const [notice, setNotice] = useState(null);
  const [version, setVersion] = useState(0); // re-keys the fields after load/reset/import
  const [previewPrefs, updatePreview] = usePreviewPrefs();

  const info = sectionInfo(section);
  const fields = cfg.fields[section] || [];
  const dirty = values !== null && JSON.stringify(values) !== saved;
  const dirtyRef = useRef(false);
  dirtyRef.current = dirty;

  const notify = useCallback((type, text) => setNotice({ type, text }), []);

  const apply = useCallback((res) => {
    setValues(res.values);
    setSaved(JSON.stringify(res.values));
    setImages((imgs) => ({ ...imgs, ...res.images }));
    setEnabled(res.enabled);
    setVersion((v) => v + 1);
  }, []);

  const load = useCallback(
    async (key) => {
      setStatus('loading');
      try {
        apply(await api(`settings/${key}`));
        setStatus('ready');
      } catch (err) {
        setStatus('error');
        notify('error', err.message);
      }
    },
    [apply, notify],
  );

  useEffect(() => {
    load(section);
    syncWpMenu(section);
  }, [section, load]);

  // Browser back/forward between sections.
  useEffect(() => {
    const onPop = () => {
      const page = new URLSearchParams(window.location.search).get('page') || 'jk-general';
      const key = page.replace(/^jk-/, '');
      if (cfg.fields[key]) setSection(key);
    };
    window.addEventListener('popstate', onPop);
    return () => window.removeEventListener('popstate', onPop);
  }, []);

  // Unsaved-changes warning when leaving wp-admin.
  useEffect(() => {
    const onBeforeUnload = (e) => {
      if (!dirtyRef.current) return;
      e.preventDefault();
      e.returnValue = '';
    };
    window.addEventListener('beforeunload', onBeforeUnload);
    return () => window.removeEventListener('beforeunload', onBeforeUnload);
  }, []);

  // wp-admin's own James Kellie submenu links switch sections in-app too.
  useEffect(() => {
    const onClick = (e) => {
      const a = e.target instanceof Element ? e.target.closest('#toplevel_page_jk-general a') : null;
      if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
      const key = (new URL(a.href, window.location.href).searchParams.get('page') || '').replace(/^jk-/, '');
      if (!cfg.fields[key]) return;
      e.preventDefault();
      select(key);
    };
    document.addEventListener('click', onClick);
    return () => document.removeEventListener('click', onClick);
  });

  const select = (key) => {
    if (key === section) return;
    if (dirtyRef.current && !window.confirm('You have unsaved changes. Leave this section without saving?')) return;
    setNotice(null);
    window.history.pushState({}, '', `${cfg.adminUrl}?page=jk-${key}`);
    setSection(key);
    window.scrollTo(0, 0);
  };

  const onChange = (key, v) => setValues((vals) => ({ ...vals, [key]: v }));

  const save = async (e) => {
    e?.preventDefault();
    setStatus('saving');
    try {
      apply(await api(`settings/${section}`, { method: 'POST', body: { values } }));
      notify('success', 'Settings saved.');
      setStatus('ready');
    } catch (err) {
      setStatus('ready');
      notify('error', err.message);
    }
  };

  const reset = async () => {
    if (!window.confirm('Reset this section to its default content? Your changes will be lost.')) return;
    setStatus('saving');
    try {
      apply(await api(`settings/${section}/reset`, { method: 'POST' }));
      notify('success', 'Section reset to defaults.');
      setStatus('ready');
    } catch (err) {
      setStatus('ready');
      notify('error', err.message);
    }
  };

  const ctx = useMemo(
    () => ({ images, enabled, cfg, setImage: (id, url) => setImages((imgs) => ({ ...imgs, [id]: url })) }),
    [images, enabled],
  );

  const visible = !info.home || (values ? Boolean(values.enabled) : enabled[section]);
  const busy = status === 'loading' || status === 'saving';
  const hasPreview = Boolean(values) && Boolean(cfg.preview?.sections?.includes(section));
  // ≥1280px: preview (sticky) on the left, fields in a column on the right.
  const split = hasPreview && previewPrefs.open;

  return (
    <FieldContext.Provider value={ctx}>
      <Notice notice={notice} onClose={() => setNotice(null)} />
      <Header />

      <div className="tw:mt-6 tw:grid tw:items-start tw:gap-6 tw:min-[961px]:grid-cols-[232px_minmax(0,1fr)]">
        <Sidebar current={section} enabled={enabled} onSelect={select} />

        <div className={split ? 'tw:min-w-0 tw:min-[1280px]:grid tw:min-[1280px]:grid-cols-[minmax(0,1fr)_400px] tw:min-[1280px]:items-start tw:min-[1280px]:gap-6' : 'tw:min-w-0'}>
          {hasPreview && (
            <div className={split ? 'tw:min-w-0 tw:min-[1280px]:sticky tw:min-[1280px]:top-12' : undefined}>
              <Preview key={section} section={section} values={values} prefs={previewPrefs} update={updatePreview} split={split} />
            </div>
          )}

          <div className="tw:min-w-0">
          <section className="tw:overflow-hidden tw:rounded-2xl tw:border tw:border-neutral-200 tw:bg-white tw:shadow-sm">
            <header className="tw:flex tw:flex-wrap tw:items-start tw:justify-between tw:gap-4 tw:border-b tw:border-neutral-200 tw:px-8 tw:py-7">
              <div className="tw:flex tw:items-start tw:gap-4">
                <span className="tw:flex tw:size-11 tw:flex-none tw:items-center tw:justify-center tw:rounded-xl tw:bg-neutral-100 tw:text-ink" aria-hidden="true">
                  <span className={`dashicons ${info.icon}`}></span>
                </span>
                <div>
                  <p className="tw:m-0 tw:text-2xl tw:leading-tight tw:font-bold tw:tracking-tight tw:text-ink tw:font-display">{info.title}</p>
                  {info.description && <p className="tw:mt-1 tw:mb-0 tw:max-w-xl tw:text-sm tw:text-neutral-500">{info.description}</p>}
                </div>
              </div>
              {info.home && (
                <span
                  className={`tw:inline-flex tw:items-center tw:gap-2 tw:rounded-full tw:px-3 tw:py-1 tw:text-xs tw:font-semibold ${
                    visible ? 'tw:bg-ink tw:text-white' : 'tw:bg-neutral-100 tw:text-neutral-500'
                  }`}
                >
                  <span className={`tw:size-1.5 tw:rounded-full ${visible ? 'tw:bg-white' : 'tw:bg-neutral-400'}`}></span>
                  {visible ? 'Showing on Home' : 'Hidden'}
                </span>
              )}
            </header>

            {section === 'performance' && <StatusPanel />}

            <form id="jk-settings-form" onSubmit={save} aria-busy={busy ? 'true' : 'false'} className={status === 'loading' ? 'tw:opacity-50' : undefined}>
              <div className="tw:px-8">
                {values ? (
                  <FieldTable key={`${section}-${version}`} fields={fields} values={values} onChange={onChange} base={`jk_${section}`} compact={split} />
                ) : (
                  <p className="tw:py-8 tw:text-sm tw:text-neutral-500">{status === 'error' ? 'The settings could not be loaded.' : 'Loading…'}</p>
                )}
              </div>
              {section === 'performance' && (
                <p className="tw:mx-8 tw:mb-6 tw:mt-0 tw:rounded-xl tw:bg-neutral-50 tw:px-4 tw:py-3 tw:text-xs tw:text-neutral-500">
                  Post revisions (WP_POST_REVISIONS, set in wp-config.php): <code>{cfg.revisions}</code>
                </p>
              )}
            </form>

            <div className="tw:sticky tw:bottom-0 tw:z-10 tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:border-t tw:border-neutral-200 tw:bg-white/90 tw:px-8 tw:py-4 tw:backdrop-blur">
              <span className="tw:inline-flex tw:items-center tw:gap-2 tw:text-xs tw:font-medium tw:text-neutral-500" hidden={!dirty}>
                <span className="tw:size-2 tw:rounded-full tw:bg-amber-500"></span>Unsaved changes
              </span>
              <span className="tw:ml-auto tw:flex tw:flex-wrap tw:gap-2">
                <button type="button" className="jk-button jk-button-ghost" onClick={reset} disabled={busy || !values}>
                  Reset to defaults
                </button>
                <button type="submit" form="jk-settings-form" className="jk-button jk-button-primary" disabled={busy || !values}>
                  {status === 'saving' ? 'Saving…' : 'Save changes'}
                </button>
              </span>
            </div>
          </section>

          {section === 'general' && (
            <ImportExport
              notify={notify}
              onImported={(en) => {
                setEnabled(en);
                load(section);
              }}
            />
          )}
          </div>
        </div>
      </div>
    </FieldContext.Provider>
  );
}
