import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * The message protocol with a ?jk_preview={key} frame (src/preview/PreviewApp.jsx), shared by
 * the Theme Settings preview (Preview.jsx) and the block editor blocks (src/blocks/editor.jsx).
 *
 * - The frame announces itself with jk:preview-ready, then reports jk:preview-height.
 * - post(data) sends render data (jk:preview); it is held until the frame is ready and
 *   re-sent when a new frame loads.
 *
 * The frame posts to its parent window. In the block editor that can be the editor canvas
 * iframe rather than the window this code runs in, so the listener goes on the frame's
 * owner window as well. `frameKey` changes whenever a new <iframe> element mounts.
 *
 * @param {string} section  Section key.
 * @param {*}      frameKey Anything that changes when the iframe element is replaced.
 */
export default function usePreviewFrame(section, frameKey) {
  const frameRef = useRef(null);
  const latest = useRef(null);
  const readyRef = useRef(false);
  const [ready, setReady] = useState(false);
  const [height, setHeight] = useState(600);

  const post = useCallback(
    (data) => {
      latest.current = data;
      if (readyRef.current) frameRef.current?.contentWindow?.postMessage({ type: 'jk:preview', key: section, data }, window.location.origin);
    },
    [section],
  );

  useEffect(() => {
    readyRef.current = false;
    setReady(false);

    const onMessage = (e) => {
      if (e.origin !== window.location.origin || !frameRef.current || e.source !== frameRef.current.contentWindow) return;
      const msg = e.data || {};
      if (msg.key !== section) return;
      if (msg.type === 'jk:preview-ready') {
        readyRef.current = true;
        setReady(true);
        if (latest.current) post(latest.current);
      } else if (msg.type === 'jk:preview-height' && msg.height > 0) {
        setHeight(msg.height);
      }
    };

    const targets = new Set([window]);
    const owner = frameRef.current?.ownerDocument?.defaultView;
    if (owner) targets.add(owner);
    targets.forEach((t) => t.addEventListener('message', onMessage));
    return () => targets.forEach((t) => t.removeEventListener('message', onMessage));
  }, [section, frameKey, post]);

  return { frameRef, ready, readyRef, height, post };
}
