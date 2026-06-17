import json
from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from services.claude_service import stream_response
from models.database import save_message

router = APIRouter()


class ChatRequest(BaseModel):
    message: str
    session_id: str = "default"
    site_id: str = "default"


@router.post("/chat")
async def chat(req: ChatRequest) -> StreamingResponse:
    async def event_stream():
        full_response = ""
        await save_message(req.session_id, req.site_id, "user", req.message)
        try:
            async for data in stream_response(req.message, req.session_id):
                yield f"data: {data}\n\n"
                chunk = json.loads(data)
                full_response += chunk.get("token", "")
        finally:
            if full_response:
                await save_message(req.session_id, req.site_id, "assistant", full_response)
            yield "data: [DONE]\n\n"

    return StreamingResponse(
        event_stream(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "X-Accel-Buffering": "no",
            "Connection": "keep-alive",
        },
    )


@router.get("/health")
async def health() -> dict:
    return {"status": "ok"}
