from fastapi import FastAPI, HTTPException
from typing import Dict
from pydantic import BaseModel
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_community.document_loaders import (
    PyPDFLoader, 
    UnstructuredWordDocumentLoader,
)
from pathlib import Path
from datetime import datetime
import chromadb
import hashlib
import uvicorn
import os
import sys

sys.path.insert(0, str(Path(__file__).parent.parent))
from model.model_manager import model_manager

class RAGRequest(BaseModel):
    instance_id:int
    question:str

class RAGResponse(BaseModel):
    question:str

class FileInfo(BaseModel):
    file_id:int
    file_name: str
    file_hash: str

class AddRequest(BaseModel):
    instance_id:int
    file_dir:str
    file_info: FileInfo

class AddResponse(BaseModel):
    document_id:str

class DeleteRequest(BaseModel):
    instance_id:int
    file_id:str


class RAGService:
    def __init__(self, persist_directory: str = "./chroma_db"):
        self.persist_directory = persist_directory
        os.makedirs(persist_directory, exist_ok=True)
        
        self.chroma_client = chromadb.PersistentClient(path=persist_directory)
        self.embedding_model = model_manager.get_model()
        self.text_splitter = RecursiveCharacterTextSplitter(
            chunk_size=500,    
            chunk_overlap=50,    
            length_function=len,
            separators=["\n\n", "\n", " ", ""]
        )
        
        self.collections_cache = {}
    
    def get_collection(self, instance_id: int):
        collection_name = f"instance_{instance_id}"
        
        if collection_name not in self.collections_cache:
            try:
                collection = self.chroma_client.get_collection(collection_name)
            except:
                collection = self.chroma_client.create_collection(
                    name=collection_name,
                    metadata={"hnsw:space": "cosine"}
                )
            self.collections_cache[collection_name] = collection
        
        return self.collections_cache[collection_name]

    async def add_document(self, instance_id: int, file_dir: str, file_info: FileInfo):
        collection = self.get_collection(instance_id)
        file_path = Path(file_dir)
        
        if not file_path.exists():
            raise HTTPException(404, f"File not found: {file_path}")
        
        docs = await self.load_document(file_path, file_info.mimetype)
        chunks = self.text_splitter.split_text(docs)
        
        documents = []
        embeddings = []
        metadatas = []
        ids = []
        doc_id_base = str(file_info.file_id);
        
        for i, chunk in enumerate(chunks):
            chunk_id = f"{doc_id_base}_chunk_{i}"
            ids.append(chunk_id)
            documents.append(chunk) 
            embeddings.append(self.embedding_model.encode(chunk).tolist()) 
            metadatas.append({
                "filename": file_info.file_name,
                "filehash": file_info.file_hash,
                "chunk_index": i,
                "total_chunks": len(chunks),
                "added_at": datetime.now().isoformat()
            })
        
        collection.add(
            embeddings=embeddings,
            documents=documents, 
            metadatas=metadatas,
            ids=ids
        )
    
    async def load_document(self, file_path: Path) -> str:        
        extension = file_path.suffix.lower()
        
        try:
            if extension == ".txt":
                with open(file_path, 'r', encoding='utf-8') as f:
                    return f.read()
            
            elif extension == ".pdf":
                loader = PyPDFLoader(str(file_path))
                pages = loader.load()
                return "\n".join([page.page_content for page in pages])
            
            elif extension in [".doc", ".docx"]:
                loader = UnstructuredWordDocumentLoader(str(file_path))
                docs = loader.load()
                return "\n".join([doc.page_content for doc in docs])
            
            else:
                with open(file_path, 'r', encoding='utf-8') as f:
                    return f.read()
                    
        except Exception as e:
            raise HTTPException(500, f"Error loading document: {str(e)}")
        
    async def query(self, instance_id: int, question: str, top_k: int = 3) -> Dict:
        collection = self.get_collection(instance_id)
        question_embedding = self.embedding_model.encode([question])[0].tolist()
        
        results = collection.query(
            query_embeddings=[question_embedding],
            n_results=top_k,
            include=["documents"]
        )
        
        if not results['documents'] or len(results['documents'][0]) == 0:
            return {
                "context": "",
                "found": False
            }
        
        context = "\n\n".join(results['documents'][0])

        return {
            "context": context,
            "found": True
        }
    
    async def delete_document(self, instance_id: int, document_id: str):      
        collection = self.get_collection(instance_id)
        
        if document_id:
            collection.delete(where={"document_id": document_id})
        else:
            raise HTTPException(400, "Either document_id or filehash required")


app = FastAPI()
rag_service = RAGService(persist_directory="./chroma_db")

@app.post("/use")
async def use_rag(request: RAGRequest)->RAGResponse:
    rag_result = await rag_service.query(
        request.instance_id, 
        request.question
    )
    
    if not rag_result['found']:
        return RAGResponse(
            answer=request.question
        )
    
    enhanced_question = f"""Using the context, answer the question.
Context:
{rag_result['context']}
Question: {request.question}
"""

    return RAGResponse(
        answer=enhanced_question
    )


@app.post("/add_document")
async def add_document(request: AddRequest)->AddResponse:
    temp_dir = Path(request.file_dir)
    if not temp_dir.exists():
        raise HTTPException(404, f"Directory not found: {request.file_dir}")
    
    await rag_service.add_document(
        request.instance_id,
        request.file_dir,
        request.file_info
    )


@app.post("/delete_document")
async def delete_document(request: DeleteRequest):
    await rag_service.delete_document(
        request.instance_id,
        request.file_id,
    )


if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8001,
        reload=True
    )