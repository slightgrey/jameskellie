import { useRef, useState } from 'react';

/**
 * Native drag-and-drop reordering for a list.
 * Returns handlers for each item and the index being dragged.
 *
 * @param {(from: number, to: number) => void} move
 */
export function useSortable(move) {
  const [dragging, setDragging] = useState(-1);
  const current = useRef(-1);

  const item = (i) => ({
    onDragOver(e) {
      if (current.current < 0) return;
      e.preventDefault();
      if (current.current === i) return;
      const r = e.currentTarget.getBoundingClientRect();
      const before = e.clientY < r.top + r.height / 2;
      const from = current.current;
      let to = before ? i : i + 1;
      if (from < to) to -= 1;
      if (to !== from) {
        move(from, to);
        current.current = to;
        setDragging(to);
      }
    },
    onDrop(e) {
      e.preventDefault();
    },
  });

  const handle = (i, dragNode) => ({
    draggable: true,
    onDragStart(e) {
      current.current = i;
      setDragging(i);
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', '');
      const node = dragNode?.();
      if (node && node !== e.currentTarget) e.dataTransfer.setDragImage(node, 20, 20);
    },
    onDragEnd() {
      current.current = -1;
      setDragging(-1);
    },
  });

  return { dragging, item, handle };
}

/** Move one array element. */
export function arrayMove(list, from, to) {
  const next = list.slice();
  const [it] = next.splice(from, 1);
  next.splice(to, 0, it);
  return next;
}
