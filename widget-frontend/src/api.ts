declare global {
  interface Window {
    AIChatWidgetSettings?: {
      backendUrl: string;
      widgetTitle: string;
      primaryColor: string;
      welcomeMessage: string;
      nonce: string;
    };
  }
}

export interface WidgetSettings {
  backendUrl: string;
  widgetTitle: string;
  primaryColor: string;
  welcomeMessage: string;
}

export function getSettings(): WidgetSettings {
  return {
    backendUrl: window.AIChatWidgetSettings?.backendUrl || 'http://localhost:8000',
    widgetTitle: window.AIChatWidgetSettings?.widgetTitle || 'AI Assistant',
    primaryColor: window.AIChatWidgetSettings?.primaryColor || '#6366f1',
    welcomeMessage: window.AIChatWidgetSettings?.welcomeMessage || 'Hello! How can I help you today?',
  };
}

export async function* streamChat(
  message: string,
  sessionId: string,
  siteId: string
): AsyncGenerator<string> {
  const { backendUrl } = getSettings();

  const response = await fetch(`${backendUrl}/api/chat`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message, session_id: sessionId, site_id: siteId }),
  });

  if (!response.ok || !response.body) {
    throw new Error(`Request failed: ${response.status} ${response.statusText}`);
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });
    const lines = buffer.split('\n');
    buffer = lines.pop() ?? '';

    for (const line of lines) {
      if (!line.startsWith('data: ')) continue;
      const raw = line.slice(6).trim();
      if (raw === '[DONE]') return;
      try {
        const parsed = JSON.parse(raw) as { token?: string };
        if (parsed.token) yield parsed.token;
      } catch {
        // ignore malformed SSE lines
      }
    }
  }
}
