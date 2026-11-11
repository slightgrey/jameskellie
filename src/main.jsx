import './styles/app.css';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';

function readBoot() {
  try {
    return JSON.parse(document.getElementById('jk-boot')?.textContent || '{}');
  } catch {
    return {};
  }
}

const boot = readBoot();
const root = document.getElementById('jk-root');

if (root && boot.site && boot.route) {
  createRoot(root).render(
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <App site={boot.site} initialRoute={boot.route} />
    </BrowserRouter>,
  );
}
