import './admin.css';
import { createRoot } from 'react-dom/client';
import AdminApp from './AdminApp';

const el = document.getElementById('jk-admin-root');
if (el && window.JKSettings) {
  createRoot(el).render(<AdminApp />);
}
