/**
 * Book Appointment meta box (page editor): repeaters, sortable lists, media picker
 * and an unsaved-changes warning for the PHP-rendered fields. Theme Settings is src/admin/AdminApp.jsx.
 */
import './admin.css';

const cfg = window.JKAdmin || {};
const t = (key, fallback) => (cfg.i18n && cfg.i18n[key]) || fallback;
const escapeRe = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

let dirty = false;
const markDirty = () => {
  dirty = true;
  document.querySelector('[data-jk-dirty]')?.removeAttribute('hidden');
};

/* ------------------------------------------------------------ sortable */

const itemsOf = (container) => [...container.children].filter((el) => el.matches('[data-jk-item]'));

function afterMove(container) {
  const rep = container.closest('[data-jk-repeater]');
  if (rep) reindex(rep);
  markDirty();
}

function move(item, dir) {
  const container = item.parentElement;
  if (dir < 0 && item.previousElementSibling?.matches('[data-jk-item]')) {
    container.insertBefore(item, item.previousElementSibling);
  } else if (dir > 0 && item.nextElementSibling?.matches('[data-jk-item]')) {
    container.insertBefore(item.nextElementSibling, item);
  } else {
    return;
  }
  afterMove(container);
  item.querySelector(dir < 0 ? '[data-jk-up]' : '[data-jk-down]')?.focus();
}

let dragged = null;

document.addEventListener('dragstart', (e) => {
  const handle = e.target instanceof Element ? e.target.closest('[draggable="true"]') : null;
  if (!handle) return;
  dragged = handle.closest('[data-jk-item]');
  if (!dragged) return;
  dragged.classList.add('is-dragging');
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', '');
  if (handle !== dragged) e.dataTransfer.setDragImage(dragged, 20, 20);
});

document.addEventListener('dragover', (e) => {
  if (!dragged) return;
  const over = e.target instanceof Element ? e.target.closest('[data-jk-item]') : null;
  if (!over || over === dragged || over.parentElement !== dragged.parentElement) return;
  e.preventDefault();
  const r = over.getBoundingClientRect();
  const before = e.clientY < r.top + r.height / 2;
  over.parentElement.insertBefore(dragged, before ? over : over.nextElementSibling);
});

document.addEventListener('dragend', () => {
  if (!dragged) return;
  dragged.classList.remove('is-dragging');
  afterMove(dragged.parentElement);
  dragged = null;
});

/* ------------------------------------------------------------ repeaters */

function reindex(rep) {
  const name = rep.dataset.name;
  const id = rep.dataset.id;
  const nameRe = new RegExp(`^${escapeRe(name)}\\[[^\\]]*\\]`);
  const idRe = new RegExp(`^${escapeRe(id)}-[^-]+`);
  const rows = itemsOf(rep.querySelector('[data-jk-rows]'));

  rows.forEach((row, i) => {
    row.querySelectorAll('[name]').forEach((el) => {
      el.name = el.name.replace(nameRe, `${name}[${i}]`);
    });
    ['id', 'for', 'aria-controls'].forEach((attr) => {
      row.querySelectorAll(`[${attr}]`).forEach((el) => {
        const v = el.getAttribute(attr);
        if (v && idRe.test(v)) el.setAttribute(attr, v.replace(idRe, `${id}-${i}`));
      });
    });
  });

  const max = parseInt(rep.dataset.max || '0', 10);
  const add = rep.querySelector('[data-jk-add]');
  if (add) add.disabled = max > 0 && rows.length >= max;
}

function addRow(rep) {
  const max = parseInt(rep.dataset.max || '0', 10);
  const list = rep.querySelector('[data-jk-rows]');
  if (max && itemsOf(list).length >= max) {
    window.alert(t('max', 'Maximum number of rows reached.'));
    return;
  }
  const tpl = rep.querySelector(':scope > template[data-jk-template]');
  const frag = tpl.content.cloneNode(true);
  const row = frag.querySelector('[data-jk-item]');
  list.append(frag);
  reindex(rep);
  markDirty();
  row.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
}

function updateRowTitle(input) {
  const row = input.closest('.jk-row');
  const rep = input.closest('[data-jk-repeater]');
  if (!row || !rep || !rep.dataset.rowTitle) return;
  if (!new RegExp(`\\[${escapeRe(rep.dataset.rowTitle)}\\]$`).test(input.name)) return;
  const title = row.querySelector('[data-jk-row-title]');
  const text = input instanceof HTMLSelectElement ? input.options[input.selectedIndex]?.text : input.value;
  if (title) title.textContent = text || '(new row)';
}

/* ------------------------------------------------------------ media */

function chooseImage(box) {
  if (!window.wp || !window.wp.media) return;
  const frame = window.wp.media({
    title: t('choose', 'Choose image'),
    button: { text: t('use', 'Use this image') },
    library: { type: 'image' },
    multiple: false,
  });
  frame.on('select', () => {
    const a = frame.state().get('selection').first().toJSON();
    box.querySelector('input[type="hidden"]').value = a.id;
    const src = (a.sizes && (a.sizes.thumbnail || a.sizes.medium) && (a.sizes.thumbnail || a.sizes.medium).url) || a.url;
    const preview = box.querySelector('[data-jk-image-preview]');
    preview.innerHTML = '';
    const img = document.createElement('img');
    img.src = src;
    img.alt = '';
    preview.append(img);
    box.querySelector('[data-jk-image-remove]').hidden = false;
    markDirty();
  });
  frame.open();
}

function removeImage(box) {
  box.querySelector('input[type="hidden"]').value = '';
  box.querySelector('[data-jk-image-preview]').innerHTML = '';
  box.querySelector('[data-jk-image-remove]').hidden = true;
  markDirty();
}

/* ------------------------------------------------------------ events */

document.addEventListener('click', (e) => {
  const el = e.target instanceof Element ? e.target.closest('button') : null;
  if (!el) return;

  if (el.matches('[data-jk-add]')) {
    e.preventDefault();
    addRow(el.closest('[data-jk-repeater]'));
  } else if (el.matches('[data-jk-remove]')) {
    e.preventDefault();
    if (!window.confirm(t('confirm', 'Remove this row?'))) return;
    const rep = el.closest('[data-jk-repeater]');
    el.closest('[data-jk-item]').remove();
    reindex(rep);
    markDirty();
  } else if (el.matches('[data-jk-up]')) {
    e.preventDefault();
    move(el.closest('[data-jk-item]'), -1);
  } else if (el.matches('[data-jk-down]')) {
    e.preventDefault();
    move(el.closest('[data-jk-item]'), 1);
  } else if (el.matches('[data-jk-toggle]')) {
    e.preventDefault();
    const row = el.closest('.jk-row');
    const collapsed = row.classList.toggle('is-collapsed');
    el.setAttribute('aria-expanded', String(!collapsed));
  } else if (el.matches('[data-jk-image-choose]')) {
    e.preventDefault();
    chooseImage(el.closest('[data-jk-image]'));
  } else if (el.matches('[data-jk-image-remove]')) {
    e.preventDefault();
    removeImage(el.closest('[data-jk-image]'));
  }
});

document.addEventListener('input', (e) => {
  if (e.target instanceof HTMLElement && e.target.closest('[data-jk-form], #jk-appointment')) {
    markDirty();
    updateRowTitle(e.target);
  }
});
document.addEventListener('change', (e) => {
  if (e.target instanceof HTMLElement && e.target.closest('[data-jk-form], #jk-appointment')) {
    markDirty();
    updateRowTitle(e.target);
  }
});

/* ------------------------------------------------------------ boot */

document.querySelectorAll('[data-jk-repeater]').forEach(reindex);

if (window.wp && window.wp.codeEditor && cfg.codeEditor) {
  document.querySelectorAll('[data-jk-code]').forEach((textarea) => {
    const editor = window.wp.codeEditor.initialize(textarea, cfg.codeEditor);
    editor.codemirror.on('change', () => {
      editor.codemirror.save();
      markDirty();
    });
  });
}

// Unsaved-changes warning on the settings screens (the page editor has its own).
const form = document.querySelector('[data-jk-form]');
if (form) {
  form.addEventListener('submit', () => {
    dirty = false;
  });
  window.addEventListener('beforeunload', (e) => {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = t('unsaved', 'You have unsaved changes.');
  });
}
