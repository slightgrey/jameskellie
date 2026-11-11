// Inline SVG icons (decorative).
const PATHS = {
  menu: [<path key="a" d="M4 7h16" />, <path key="b" d="M4 12h16" />, <path key="c" d="M4 17h16" />],
  close: [<path key="a" d="M6 6l12 12" />, <path key="b" d="M18 6L6 18" />],
  'arrow-right': [<path key="a" d="M5 12h14" />, <path key="b" d="M13 6l6 6-6 6" />],
  'arrow-up-right': [<path key="a" d="M7 17L17 7" />, <path key="b" d="M8 7h9v9" />],
  'arrow-up': [<path key="a" d="M12 19V5" />, <path key="b" d="M6 11l6-6 6 6" />],
  minus: [<path key="a" d="M5 12h14" />],
  plus: [<path key="a" d="M5 12h14" />, <path key="b" d="M12 5v14" />],
  message: [<path key="a" d="M4 4h16v12H5.2L4 17.2V4z" />, <path key="b" d="M8 9h8" />, <path key="c" d="M8 12h5" />],
  calendar: [
    <rect key="a" x="3" y="5" width="18" height="16" rx="1" />,
    <path key="b" d="M3 10h18" />,
    <path key="c" d="M8 3v4" />,
    <path key="d" d="M16 3v4" />,
  ],
  check: [<path key="a" d="M5 12.5l4.5 4.5L19 7.5" />],
  alert: [<circle key="a" cx="12" cy="12" r="9" />, <path key="b" d="M12 7.5v5.5" />, <path key="c" d="M12 16.5h.01" />],
};

export function Icon({ name, size = 24, stroke = 2, className }) {
  return (
    <svg
      className={className}
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={stroke}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
    >
      {PATHS[name]}
    </svg>
  );
}
