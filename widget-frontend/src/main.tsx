import { createRoot } from 'react-dom/client';
import './index.css';
import ChatWidget from './ChatWidget';

function mount() {
  let container = document.getElementById('ai-chat-widget-root');
  if (!container) {
    container = document.createElement('div');
    container.id = 'ai-chat-widget-root';
    document.body.appendChild(container);
  }
  createRoot(container).render(<ChatWidget />);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
