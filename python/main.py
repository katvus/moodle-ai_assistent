from gptcache.adapter.api import get, put
from gptcache.core import Cache
from gptcache.config import Config
from gptcache.manager import get_data_manager, CacheBase, VectorBase
from gptcache.processor.pre import get_prompt
from gptcache.similarity_evaluation.distance import SearchDistanceEvaluation
from gptcache.embedding import Onnx
from fastapi import FastAPI
from pydantic import BaseModel
from typing import Optional
import uvicorn

def init_cache():
    cache = Cache()
    onnx = Onnx()
    data_manager = get_data_manager(CacheBase("sqlite"), VectorBase("chromadb", dimension=onnx.dimension))
    cache.init(embedding_func=onnx.to_embeddings,
            pre_embedding_func=get_prompt,
            data_manager=data_manager,
            similarity_evaluation=SearchDistanceEvaluation(),
            config = Config(similarity_threshold = 0.5)
    )
    return cache

app = FastAPI()
cache = init_cache()

class CacheRequest(BaseModel):
    instance_id: int
    question:str

class CacheResponse(BaseModel):
    cached: bool
    answer: Optional[str] = None

class StoreRequest(BaseModel):
    instance_id: int
    question:str
    answer:str

@app.post("/check")
async def check_cache(request: CacheRequest)->CacheResponse:
    try:
        response = get(request.question, cache_obj=cache, session_id=str(request.instance_id))
        if response is not None and response != "":
            print(f"Found in cache: '{response}'")
            return CacheResponse(cached=True, answer=response)
        else:
            print(f"Found but empty/None")
            return CacheResponse(cached=False)
    except Exception as e:
        return CacheResponse(cached=False)

@app.post("/store")
async def store_in_cache(request: StoreRequest)->None:
    put(request.question, request.answer, cache_obj=cache, session_id=str(request.instance_id))

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8000,
        reload=True
    )