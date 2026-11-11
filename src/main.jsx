import './styles/app.css';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import PreviewApp from './preview/PreviewApp';

function readBoot() {
  try {
    return JSON.parse(document.getElementById('jk-boot')?.textContent || '{}');
  } catch {
    return {};
  }
}

const boot = readBoot();
const root = document.getElementById('jk-root');

if (root && boot.site && boot.preview) {
  // Theme Settings live preview (?jk_preview=hero, administrators only): one section, no router.
  createRoot(root).render(<PreviewApp site={boot.site} preview={boot.preview} />);
} else if (root && boot.site && boot.route) {
  createRoot(root).render(
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <App site={boot.site} initialRoute={boot.route} />
    </BrowserRouter>,
  );
}
