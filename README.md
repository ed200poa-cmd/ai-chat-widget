# AI Chat Widget — WordPress Plugin + Claude AI Backend

A production-ready WordPress plugin that embeds a streaming Claude AI chatbot on any page. One shortcode, instant chat. Zero configuration on the page side.

**Built by Edward Kim — AI Developer**

---

## What This Is

A two-part system:

1. **FastAPI Backend** (deployed to Railway) — receives chat messages, streams Claude AI responses token-by-token via SSE, and logs all conversations to PostgreSQL
2. **WordPress Plugin** (installed on any WP site) — renders a floating chat button with a React widget that streams the AI response in real time

The widget appears instantly without page reload. No REST API configuration on WordPress. No JS framework conflicts. One enqueued JS file, one shortcode.

---

## Architecture

```
WordPress Page
    └── [ai_chat_widget] shortcode
        └── React Widget (IIFE bundle)
            └── SSE fetch → FastAPI on Railway
                └── langchain-anthropic → Claude claude-sonnet-4-6
                    └── PostgreSQL (conversation history)
```

---

## What is SSE (Server-Sent Events)?

Instead of waiting for the entire AI response, SSE lets the server push tokens to the browser as they're generated — the same "typing" effect you see in ChatGPT. The browser opens one HTTP connection and reads a stream of `data: {...}` lines. Each line contains one token. The React widget appends each token to the message in real time.

---

## What is pgvector?

pgvector is a PostgreSQL extension that stores and searches vector embeddings. This backend installs it on startup. Conversation history is currently stored as plain text, but the extension is ready for RAG (Retrieval-Augmented Generation) — for example, searching a knowledge base by semantic similarity before answering.

---

## Quick Start (Local)

### 1. Backend

```bash
cd ai-chat-widget
cp .env.example .env
# Fill in ANTHROPIC_API_KEY

docker-compose up
# → PostgreSQL on :5432
# → Backend API on :8000
```

Test it:
```bash
curl -N -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello!","session_id":"test","site_id":"local"}'
```

### 2. Build the React Widget

```bash
cd widget-frontend
npm install
npm run build
# → outputs plugin/assets/chat-widget.iife.js (CSS injected)
```

### 3. Install WordPress Plugin

1. Copy the `plugin/` folder to `wp-content/plugins/ai-chat-widget/`
2. Activate in WordPress admin → Plugins
3. Go to Settings → AI Chat Widget
4. Enter your Backend API URL (local: `http://localhost:8000`)
5. Add `[ai_chat_widget]` to any page

---

## Deploy to Railway

### Backend

1. Create a Railway project, connect this repo
2. Set **Root Directory** to `backend/`
3. Railway auto-detects the `Dockerfile`
4. Add environment variables:
   - `ANTHROPIC_API_KEY` = your key
5. Add a PostgreSQL plugin to the project (Railway manages it)
6. Railway sets `DATABASE_URL` automatically

Then update the WordPress plugin settings with your Railway URL.

---

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/chat` | Stream a chat response (SSE) |
| GET | `/api/health` | Health check |

### POST /api/chat

**Request:**
```json
{
  "message": "What are your hours?",
  "session_id": "user-abc-123",
  "site_id": "mysite.com"
}
```

**Response:** `text/event-stream`
```
data: {"token": "Our", "session_id": "user-abc-123"}
data: {"token": " hours", "session_id": "user-abc-123"}
data: {"token": " are...", "session_id": "user-abc-123"}
data: [DONE]
```

---

## WordPress Plugin Settings

| Field | Description |
|-------|-------------|
| Backend API URL | Your Railway URL, e.g. `https://ai-chat-widget.up.railway.app` |
| Anthropic API Key | Stored server-side only, never sent to browser |
| Widget Title | Text shown in chat header (default: AI Assistant) |
| Primary Color | Hex color for button and header |
| Welcome Message | First message the bot shows |

---

## Shortcode

```
[ai_chat_widget]
```

Add to any page, post, or widget area. The floating chat button appears in the bottom-right corner of the page.

---

## Database Schema

```sql
-- Conversation history
CREATE TABLE conversations (
    id         SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    site_id    VARCHAR(255) NOT NULL,
    role       VARCHAR(50)  NOT NULL,  -- 'user' | 'assistant'
    content    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Site registry
CREATE TABLE sites (
    id         SERIAL PRIMARY KEY,
    site_id    VARCHAR(255) UNIQUE NOT NULL,
    site_name  VARCHAR(255) NOT NULL DEFAULT 'Unknown Site',
    created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- pgvector installed (ready for RAG/embeddings)
CREATE EXTENSION IF NOT EXISTS vector;
```

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| AI | Claude claude-sonnet-4-6 via langchain-anthropic |
| Backend | Python 3.12 + FastAPI + Uvicorn |
| Streaming | Server-Sent Events (SSE) |
| Database | PostgreSQL 16 + pgvector |
| ORM | SQLAlchemy 2.0 async + asyncpg |
| Widget | React 18 + TypeScript + Tailwind CSS |
| Build | Vite (IIFE bundle, CSS injected) |
| Plugin | WordPress PHP 8.1 |
| Hosting | Railway |

---

## Environment Variables

```env
ANTHROPIC_API_KEY=sk-ant-...     # Required — Claude API access
DATABASE_URL=postgresql+asyncpg://... # Auto-set by Railway
PORT=8000                         # Auto-set by Railway
```
