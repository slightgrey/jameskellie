/**
 * James Kellie blocks for the block editor: one block per Home section (jameskellie/hero …).
 *
 * Built as a classic IIFE (vite.blocks.config.js) so the blocks are registered before the
 * editor parses the post. React and the editor packages come from WordPress (window.React,
 * window.wp.*). PHP (inc/Blocks.php) registers the block types, the category and the
 * render fallback, and passes window.JKBlocks: { schema, defaults, images, previewUrl }.
 *
 * Each block stores its own values in `data` (same keys as config/fields.php). A new block
 * starts as a copy of the section's current Theme Settings. The canvas shows the real
 * section in a ?jk_preview frame; the fields (the Theme Settings controls) open in a modal.
 */
import './editor.css';
import { useEffect, useMemo, useRef, useState } from 'react';
import { FieldContext, FieldTable } from '../admin/fields';
import usePreviewFrame from '../admin/usePreviewFrame';

const wp = window.wp;
const { registerBlockType } = wp.blocks;
const { BlockControls, InspectorControls, useBlockProps, store: blockEditorStore } = wp.blockEditor;
const { Button, Modal, Notice, PanelBody, Spinner, TextControl, ToolbarButton, ToolbarGroup } = wp.components;
const { useDispatch, useSelect } = wp.data;
const apiFetch = wp.apiFetch;

const cfg = window.JKBlocks || { schema: {}, defaults: {}, images: {}, previewUrl: '' };
const DESKTOP = 1440;
const DEBOUNCE_MS = 350;

const isEmpty = (data) => !data || typeof data !== 'object' || Object.keys(data).length === 0;
const clone = (v) => JSON.parse(JSON.stringify(v ?? {}));

/** ResizeObserver from the element's own window (the editor canvas may be an iframe). */
function useWidth(ref) {
  const [width, setWidth] = useState(0);
  useEffect(() => {
    const el = ref.current;
    const RO = el?.ownerDocument?.defaultView?.ResizeObserver;
    if (!el || !RO) return undefined;
    const ro = new RO(([entry]) => setWidth(entry.contentRect.width));
    ro.observe(el);
    return () => ro.disconnect();
  }, [ref]);
  return width;
}

/**
 * The real section, rendered by the front end in a ?jk_preview frame and scaled to fit.
 * Values go to jk/v1/blocks/preview/{key} (sanitised, never saved) and the render data is
 * posted into the frame.
 */
function FramePreview({ section, values, interactive = false }) {
  const boxRef = useRef(null);
  const width = useWidth(boxRef);
  const { frameRef, ready, height, post } = usePreviewFrame(section, 'frame');
  const [error, setError] = useState(false);
  const scale = width ? Math.min(1, width / DESKTOP) : 1;
  const src = cfg.previewUrl.replace('%7Bkey%7D', encodeURIComponent(section)).replace('{key}', encodeURIComponent(section));

  const seq = useRef(0);
  useEffect(() => {
    const my = ++seq.current;
    const t = setTimeout(async () => {
      try {
        const res = await apiFetch({ path: `/jk/v1/blocks/preview/${section}`, method: 'POST', data: { values } });
        if (my !== seq.current) return;
        setError(false);
        post(res.data);
      } catch {
        if (my === seq.current) setError(true);
      }
    }, DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [values, section, post]);

  return (
    <div ref={boxRef} className="jk-block-preview">
      <div className="jk-block-preview-box" style={{ height: Math.max(80, height * scale) }}>
        <iframe
          ref={frameRef}
          src={src}
          title={`${cfg.schema[section]?.title || section} preview`}
          tabIndex={interactive ? 0 : -1}
          scrolling="no"
          loading="lazy"
          style={{ width: DESKTOP, height, transform: `scale(${scale})`, pointerEvents: interactive ? 'auto' : 'none' }}
        />
        {!ready && (
          <div className="jk-block-preview-loading">
            <Spinner />
          </div>
        )}
      </div>
      {error && <p className="jk-block-preview-error">Preview unavailable. The block still saves.</p>}
    </div>
  );
}

/** The section's fields (the Theme Settings controls) beside a live preview. */
function ContentModal({ section, clientId, values, onChange, onClose }) {
  const [images, setImages] = useState(() => ({ ...(cfg.images || {}) }));
  const ctx = useMemo(
    () => ({
      images,
      enabled: {},
      cfg: { codeEditor: false, unfiltered: cfg.unfiltered },
      setImage: (id, url) => setImages((imgs) => ({ ...imgs, [id]: url })),
    }),
    [images],
  );
  const fields = cfg.schema[section]?.fields || [];

  return (
    <Modal title={`${cfg.schema[section]?.title || section}: edit content`} onRequestClose={onClose} isFullScreen className="jk-block-modal" shouldCloseOnClickOutside={false}>
      <FieldContext.Provider value={ctx}>
        <div className="jk-admin jk-block-modal-body">
          <div className="jk-block-modal-fields">
            <FieldTable fields={fields} values={values} onChange={onChange} base={`jk_block_${clientId.slice(0, 8)}_${section}`} compact />
          </div>
          <div className="jk-block-modal-preview">
            <p className="jk-block-modal-label">Live preview</p>
            <FramePreview section={section} values={values} interactive />
          </div>
        </div>
      </FieldContext.Provider>
      <div className="jk-block-modal-actions">
        <Button variant="primary" onClick={onClose}>
          Done
        </Button>
      </div>
    </Modal>
  );
}

function makeEdit(section) {
  return function Edit({ attributes, setAttributes, clientId }) {
    const values = attributes.data;
    const [open, setOpen] = useState(false);
    const blockProps = useBlockProps({ className: 'jk-block' });
    const { __unstableMarkNextChangeAsNotPersistent: notPersistent } = useDispatch(blockEditorStore);

    // A new block starts as a copy of the section's Theme Settings (not an undo step).
    useEffect(() => {
      if (isEmpty(values)) {
        notPersistent?.();
        setAttributes({ data: clone(cfg.defaults[section]) });
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const onChange = (key, value) => setAttributes({ data: { ...(attributes.data || {}), [key]: value } });

    // Another James Kellie block on this post with the same anchor.
    const anchor = (values && values.anchor) || '';
    const duplicate = useSelect(
      (select) => {
        if (!anchor) return false;
        const others = select(blockEditorStore)
          .getBlocks()
          .filter((b) => b.clientId !== clientId && b.name.startsWith('jameskellie/'));
        return others.some((b) => (b.attributes.data || {}).anchor === anchor);
      },
      [anchor, clientId],
    );

    const hasAnchor = (cfg.schema[section]?.fields || []).some((f) => f.key === 'anchor');

    return (
      <div {...blockProps}>
        <BlockControls>
          <ToolbarGroup>
            <ToolbarButton icon="edit" label="Edit content" onClick={() => setOpen(true)}>
              Edit content
            </ToolbarButton>
          </ToolbarGroup>
        </BlockControls>
        <InspectorControls>
          <PanelBody title="Content">
            <p>This block keeps its own copy of the {cfg.schema[section]?.title || section} content. Theme Settings changes don’t affect it.</p>
            <Button variant="primary" onClick={() => setOpen(true)}>
              Edit content
            </Button>
          </PanelBody>
          {hasAnchor && (
            <PanelBody title="Anchor">
              <TextControl label="Anchor ID" help="For in-page links, e.g. #services. Leave empty for none." value={anchor} onChange={(v) => onChange('anchor', v)} __nextHasNoMarginBottom />
              {duplicate && (
                <Notice status="warning" isDismissible={false}>
                  Another James Kellie block on this page uses the same anchor. Give each one its own.
                </Notice>
              )}
            </PanelBody>
          )}
        </InspectorControls>

        {isEmpty(values) ? <Spinner /> : <FramePreview section={section} values={values} />}

        {open && !isEmpty(values) && <ContentModal section={section} clientId={clientId} values={values} onChange={onChange} onClose={() => setOpen(false)} />}
      </div>
    );
  };
}

Object.keys(cfg.schema || {}).forEach((section) => {
  registerBlockType(`jameskellie/${section}`, {
    edit: makeEdit(section),
    save: () => null,
  });
});
