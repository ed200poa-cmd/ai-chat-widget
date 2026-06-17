import os
import json
from typing import AsyncGenerator
from langchain_anthropic import ChatAnthropic
from langchain_core.messages import HumanMessage, SystemMessage

_SYSTEM = (
    "You are a helpful AI assistant embedded on a website. "
    "Be concise, friendly, and helpful. Answer questions clearly and directly. "
    "Keep responses brief unless the user asks for detail."
)


def _get_llm() -> ChatAnthropic | None:
    key = os.getenv("ANTHROPIC_API_KEY")
    if not key:
        return None
    return ChatAnthropic(
        model="claude-sonnet-4-6",
        anthropic_api_key=key,
        max_tokens=2048,
        streaming=True,
    )


async def stream_response(message: str, session_id: str) -> AsyncGenerator[str, None]:
    llm = _get_llm()
    if not llm:
        yield json.dumps({"token": "Error: ANTHROPIC_API_KEY is not configured on the server.", "session_id": session_id})
        return

    messages = [SystemMessage(content=_SYSTEM), HumanMessage(content=message)]
    async for chunk in llm.astream(messages):
        if chunk.content:
            yield json.dumps({"token": chunk.content, "session_id": session_id})
