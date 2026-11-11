/**
 * Theme Settings field controls. One component per field type in config/fields.php,
 * using the same markup and classes as the PHP FieldRenderer so admin.css applies.
 * Values are plain JSON; the server sanitises them again (JK\Admin\Sanitizer).
 */
import { createContext, useContext, useEffect, useRef, useState } from 'react';
import { arrayMove, useSortable } from './useSortable';

export const FieldContext = createContext({ images: {}, setImage() {}, enabled: {}, cfg: {} });

/** Types whose label is not tied to a single input. */
const GROUP_TYPES = ['repeater', 'order', 'toggle', 'link', 'image', 'editor'];
const WIDE_TYPES = ['repeater', 'code', 'editor'];

const htmlId = (raw) => raw.replace(/[^A-Za-z0-9_-]+/g, '-').toLowerCase();

/** Label / control rows for a section. */
export function FieldTable({ fields, values, onChange, base }) {
  return (
    <div className="jk-fields tw:divide-y tw:divide-neutral-200">
      {fields.map((field) => {
        const id = htmlId(`${base}-${field.key}`);
        const label = field.label ?? field.key;
        const wide = WIDE_TYPES.includes(field.type);
        return (
          <div key={field.key} className={`tw:grid tw:gap-x-10 tw:gap-y-3 tw:py-6 ${wide ? '' : 'tw:md:grid-cols-[minmax(0,240px)_minmax(0,1fr)]'}`}>
            <div className="tw:min-w-0">
              {GROUP_TYPES.includes(field.type) ? (
                <span className="tw:block tw:text-[13px] tw:font-semibold tw:text-neutral-900">{label}</span>
              ) : (
                <label className="tw:block tw:text-[13px] tw:font-semibold tw:text-neutral-900" htmlFor={id}>
                  {label}
                </label>
              )}
              {field.help && <p className="tw:mt-1 tw:mb-0 tw:text-xs tw:leading-relaxed tw:text-neutral-500">{field.help}</p>}
            </div>
            <div className="tw:min-w-0">
              <Field field={field} id={id} value={values[field.key]} onChange={(v) => onChange(field.key, v)} />
            </div>
          </div>
        );
      })}
    </div>
  );
}

/** One control by type. */
export function Field({ field, id, value, onChange, withHelp = false }) {
  const ctx = useContext(FieldContext);
  let control = null;

  switch (field.type) {
    case 'text':
    case 'slug':
    case 'tel':
    case 'email':
      control = (
        <input
          type="text"
          id={id}
          value={value ?? ''}
          inputMode={field.type === 'tel' ? 'tel' : field.type === 'email' ? 'email' : undefined}
          onChange={(e) => onChange(e.target.value)}
        />
      );
      break;

    case 'url':
      control = <input type="text" inputMode="url" className="code" id={id} value={value ?? ''} placeholder="https://" onChange={(e) => onChange(e.target.value)} />;
      break;

    case 'number':
      control = (
        <input
          type="number"
          id={id}
          value={value ?? ''}
          min={field.min}
          max={field.max}
          onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
        />
      );
      break;

    case 'textarea':
      control = <textarea rows={4} id={id} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
      break;

    case 'list':
      control = (
        <>
          <textarea rows={5} id={id} value={Array.isArray(value) ? value.join('\n') : ''} onChange={(e) => onChange(e.target.value.split(/\r\n|\r|\n/))} />
          <p className="description">One item per line.</p>
        </>
      );
      break;

    case 'code':
      control = (
        <>
          <CodeField id={id} value={value ?? ''} onChange={onChange} settings={ctx.cfg.codeEditor} />
          {!ctx.cfg.unfiltered && <p className="description">Your account cannot save raw HTML, so changes here will be ignored.</p>}
        </>
      );
      break;

    case 'editor':
      control = <EditorField id={id.replace(/-/g, '_')} value={value ?? ''} onChange={onChange} />;
      break;

    case 'toggle':
      control = (
        <label className="jk-switch" htmlFor={id}>
          <input type="checkbox" id={id} checked={Boolean(value)} onChange={(e) => onChange(e.target.checked)} />
          <span className="jk-switch-ui" aria-hidden="true"></span>
          <span className="jk-switch-text" data-on="On" data-off="Off" aria-hidden="true"></span>
        </label>
      );
      break;

    case 'select':
      control = (
        <select id={id} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
          {Object.entries(field.choices || {}).map(([key, label]) => (
            <option key={key} value={key}>
              {label}
            </option>
          ))}
        </select>
      );
      break;

    case 'link': {
      const v = value && typeof value === 'object' ? value : {};
      control = (
        <div className="jk-link">
          <label>
            <span>Label</span>
            <input type="text" value={v.label ?? ''} onChange={(e) => onChange({ ...v, label: e.target.value })} />
          </label>
          <label>
            <span>Link</span>
            <input type="text" inputMode="url" className="code" value={v.url ?? ''} placeholder="/page/ or #section" onChange={(e) => onChange({ ...v, url: e.target.value })} />
          </label>
        </div>
      );
      break;
    }

    case 'image':
      control = <ImageField id={id} value={Number(value) || 0} onChange={onChange} />;
      break;

    case 'order':
      control = <OrderField field={field} value={Array.isArray(value) ? value : []} onChange={onChange} />;
      break;

    case 'repeater':
      control = <RepeaterField field={field} id={id} rows={Array.isArray(value) ? value : []} onChange={onChange} />;
      break;

    default:
      control = null;
  }

  return (
    <>
      {control}
      {withHelp && field.help && <p className="description">{field.help}</p>}
    </>
  );
}

/* ------------------------------------------------------------- code (CodeMirror) */

function CodeField({ id, value, onChange, settings }) {
  const ref = useRef(null);
  const cm = useRef(null);
  const latest = useRef(onChange);
  latest.current = onChange;

  useEffect(() => {
    const wp = window.wp;
    if (!settings || !wp?.codeEditor || !ref.current) return undefined;
    const editor = wp.codeEditor.initialize(ref.current, settings);
    cm.current = editor.codemirror;
    cm.current.on('change', (inst) => latest.current(inst.getValue()));
    return () => {
      cm.current?.toTextArea();
      cm.current = null;
    };
  }, [settings]);

  // Without CodeMirror the textarea is controlled directly.
  if (!settings || !window.wp?.codeEditor) {
    return <textarea className="code jk-code" rows={8} id={id} value={value} onChange={(e) => onChange(e.target.value)} />;
  }
  return <textarea ref={ref} className="code jk-code" rows={8} id={id} defaultValue={value} />;
}

/* -------------------------------------------------------- editor (TinyMCE teeny) */

function EditorField({ id, value, onChange }) {
  const ref = useRef(null);
  const latest = useRef(onChange);
  latest.current = onChange;

  useEffect(() => {
    const wp = window.wp;
    const textarea = ref.current;
    if (!textarea) return undefined;
    const onInput = () => latest.current(textarea.value);
    textarea.addEventListener('input', onInput);

    if (wp?.editor?.initialize) {
      wp.editor.initialize(id, {
        tinymce: {
          wpautop: true,
          toolbar1: 'bold,italic,link,bullist,numlist,undo,redo',
          setup(ed) {
            ed.on('change input keyup undo redo', () => latest.current(ed.getContent()));
          },
        },
        quicktags: { buttons: 'strong,em,link,ul,ol,li' },
        mediaButtons: false,
      });
    }
    return () => {
      textarea.removeEventListener('input', onInput);
      if (wp?.editor?.remove) wp.editor.remove(id);
    };
    // The editor owns the content after mount; the parent re-keys it to reload.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  return <textarea ref={ref} id={id} rows={8} defaultValue={value} className="tw:w-full" />;
}

/* ------------------------------------------------------------- image (wp.media) */

function ImageField({ id, value, onChange }) {
  const { images, setImage } = useContext(FieldContext);
  const frame = useRef(null);

  const choose = () => {
    const wp = window.wp;
    if (!wp?.media) return;
    if (!frame.current) {
      frame.current = wp.media({ title: 'Choose image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
    }
    frame.current.off('select');
    frame.current.on('select', () => {
      const att = frame.current.state().get('selection').first().toJSON();
      const thumb = att.sizes?.thumbnail?.url || att.sizes?.medium?.url || att.url;
      setImage(att.id, thumb);
      onChange(att.id);
    });
    frame.current.open();
  };

  const preview = value ? images[value] : '';

  return (
    <div className="jk-image">
      <div className="jk-image-preview" id={id}>
        {preview ? <img src={preview} alt="" /> : null}
      </div>
      <div className="tw:flex tw:flex-wrap tw:gap-2">
        <button type="button" className="jk-button jk-button-ghost" onClick={choose}>
          Choose image
        </button>
        {value > 0 && (
          <button type="button" className="jk-button jk-button-ghost jk-button-danger" onClick={() => onChange(0)}>
            Remove
          </button>
        )}
      </div>
    </div>
  );
}

/* ----------------------------------------------------------------- shared bits */

function MoveButtons({ label, onUp, onDown }) {
  return (
    <span className="jk-move">
      <button type="button" className="jk-icon-btn" aria-label={`Move ${label} up`} onClick={onUp}>
        <span className="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
      </button>
      <button type="button" className="jk-icon-btn" aria-label={`Move ${label} down`} onClick={onDown}>
        <span className="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
      </button>
    </span>
  );
}

/* ------------------------------------------------------------ order (Home layout) */

function OrderField({ field, value, onChange }) {
  const { enabled } = useContext(FieldContext);
  const choices = field.choices || {};
  const known = Object.keys(choices);
  const keys = [...new Set([...value.filter((k) => known.includes(k)), ...known])];
  const move = (from, to) => to >= 0 && to < keys.length && onChange(arrayMove(keys, from, to));
  const { dragging, item, handle } = useSortable(move);

  return (
    <ul className="jk-order">
      {keys.map((key, i) => (
        <li key={key} className={`jk-order-item${dragging === i ? ' is-dragging' : ''}`} {...item(i)} {...handle(i)}>
          <span className="jk-handle dashicons dashicons-menu" aria-hidden="true"></span>
          <span className="jk-order-num" aria-hidden="true"></span>
          <span className="jk-order-label">{choices[key]}</span>
          {key in enabled && !enabled[key] && <span className="jk-order-tag">Hidden</span>}
          <MoveButtons label={choices[key]} onUp={() => move(i, i - 1)} onDown={() => move(i, i + 1)} />
        </li>
      ))}
    </ul>
  );
}

/* --------------------------------------------------------------------- repeater */

let rowSeq = 0;
const newKey = () => `r${++rowSeq}`;

function rowTitle(field, row) {
  const key = field.row_title;
  if (!key || row[key] === undefined || row[key] === null || typeof row[key] === 'object') return '';
  const sub = field.fields.find((f) => f.key === key);
  const raw = String(row[key]);
  if (sub && sub.type === 'select' && sub.choices && sub.choices[raw]) return sub.choices[raw];
  return raw;
}

function emptyRow(field) {
  const row = {};
  field.fields.forEach((sub) => {
    if (sub.type === 'toggle') row[sub.key] = false;
    else if (sub.type === 'list') row[sub.key] = [];
    else if (sub.type === 'link') row[sub.key] = { label: '', url: '' };
    else if (sub.type === 'image' || sub.type === 'number') row[sub.key] = 0;
    else if (sub.type === 'select') row[sub.key] = Object.keys(sub.choices || {})[0] ?? '';
    else row[sub.key] = '';
  });
  return row;
}

function RepeaterField({ field, id, rows, onChange }) {
  const max = Number(field.max || 0);
  // Stable React keys and open/closed state, kept alongside the plain row values.
  const [meta, setMeta] = useState(() => rows.map(() => ({ key: newKey(), open: false })));
  const rowRefs = useRef({});

  // Keep meta in step if rows arrive from outside (load, reset, import).
  const synced = meta.length === rows.length ? meta : rows.map((_, i) => meta[i] ?? { key: newKey(), open: false });
  if (synced !== meta) setMeta(synced);

  const move = (from, to) => {
    if (to < 0 || to >= rows.length) return;
    setMeta((m) => arrayMove(m, from, to));
    onChange(arrayMove(rows, from, to));
  };
  const { dragging, item, handle } = useSortable(move);

  const add = () => {
    if (max && rows.length >= max) return;
    setMeta((m) => [...m, { key: newKey(), open: true }]);
    onChange([...rows, emptyRow(field)]);
  };
  const remove = (i) => {
    if (!window.confirm('Remove this row?')) return;
    setMeta((m) => m.filter((_, j) => j !== i));
    onChange(rows.filter((_, j) => j !== i));
  };
  const toggle = (i) => setMeta((m) => m.map((x, j) => (j === i ? { ...x, open: !x.open } : x)));
  const setSub = (i, key, v) => onChange(rows.map((row, j) => (j === i ? { ...row, [key]: v } : row)));

  return (
    <div className="jk-repeater">
      <div className="jk-rows">
        {rows.map((row, i) => {
          const m = synced[i];
          const title = rowTitle(field, row);
          const bodyId = `${id}-${m.key}-body`;
          return (
            <div key={m.key} ref={(el) => (rowRefs.current[m.key] = el)} className={`jk-row${m.open ? '' : ' is-collapsed'}${dragging === i ? ' is-dragging' : ''}`} {...item(i)}>
              <div className="jk-row-head">
                <span className="jk-handle dashicons dashicons-menu" aria-hidden="true" {...handle(i, () => rowRefs.current[m.key])}></span>
                <button type="button" className="jk-row-toggle" aria-expanded={m.open ? 'true' : 'false'} aria-controls={bodyId} onClick={() => toggle(i)}>
                  <span className="jk-row-title">{title !== '' ? title : '(new row)'}</span>
                </button>
                <MoveButtons label={title !== '' ? title : 'row'} onUp={() => move(i, i - 1)} onDown={() => move(i, i + 1)} />
                <button type="button" className="jk-remove" onClick={() => remove(i)}>
                  Remove
                </button>
              </div>
              <div className="jk-row-body" id={bodyId}>
                {field.fields.map((sub) => {
                  const subId = `${id}-${m.key}-${sub.key}`;
                  return (
                    <div key={sub.key} className="jk-sub">
                      {GROUP_TYPES.includes(sub.type) ? (
                        <span className="jk-sub-label">{sub.label ?? sub.key}</span>
                      ) : (
                        <label className="jk-sub-label" htmlFor={subId}>
                          {sub.label ?? sub.key}
                        </label>
                      )}
                      <Field field={sub} id={subId} value={row[sub.key]} onChange={(v) => setSub(i, sub.key, v)} withHelp />
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
      <p className="jk-repeater-foot">
        <button type="button" className="jk-button jk-add" onClick={add} disabled={Boolean(max) && rows.length >= max}>
          <span className="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
          Add row
        </button>
        <span>{max ? `Up to ${max}. Drag to reorder.` : 'Drag to reorder.'}</span>
      </p>
    </div>
  );
}
