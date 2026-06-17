import os
from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

load_dotenv()

from models.database import init_db
from routers.chat import router as chat_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    await init_db()
    api_status = "set" if os.getenv("ANTHROPIC_API_KEY") else "NOT SET"
    print(f"AI Chat Widget Backend — Claude API: {api_status}")
    yield


app = FastAPI(
    title="AI Chat Widget — Backend API",
    description="SSE-streaming Claude AI chatbot backend for WordPress plugin",
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(chat_router, prefix="/api")
