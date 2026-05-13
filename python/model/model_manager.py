from gptcache.embedding import Huggingface
import threading

class ModelManager:
    
    _instance = None
    _lock = threading.Lock()
    
    def __new__(cls):
        if cls._instance is None:
            with cls._lock:
                if cls._instance is None:
                    cls._instance = super().__new__(cls)
                    cls._instance._initialize()
        return cls._instance
    
    def _initialize(self):
        model_name = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
        self.model = Huggingface(model=model_name)
    
    def get_model(self):
        return self.model
    
model_manager = ModelManager()