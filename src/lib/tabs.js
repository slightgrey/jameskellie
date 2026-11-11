/**
 * Roving-tabindex keyboard helper for WAI-ARIA tabs.
 * Returns the index to move to, or null when the key isn't handled.
 *
 * @param {KeyboardEvent} e
 * @param {number} index current tab
 * @param {number} count number of tabs
 */
export function nextTabIndex(e, index, count) {
  switch (e.key) {
    case 'ArrowDown':
    case 'ArrowRight':
      return (index + 1) % count;
    case 'ArrowUp':
    case 'ArrowLeft':
      return (index - 1 + count) % count;
    case 'Home':
      return 0;
    case 'End':
      return count - 1;
    default:
      return null;
  }
}

/**
 * onKeyDown handler factory: moves selection and focus.
 *
 * @param {() => HTMLElement[]} getTabs
 * @param {(i: number) => void} select
 */
export function tabKeyHandler(getTabs, select) {
  return (e, index) => {
    const tabs = getTabs();
    const next = nextTabIndex(e, index, tabs.length);
    if (next === null) return;
    e.preventDefault();
    select(next);
    tabs[next]?.focus();
  };
}
