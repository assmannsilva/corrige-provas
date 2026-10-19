from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, ConfigDict

from pipeline import Pipeline

app = FastAPI(
    title="Corrige Provas Computer Vision API",
    version="0.1.0",
)


class Region(BaseModel):
    page: int = 1
    x: float
    y: float
    width: float
    height: float
    key: str | None = None
    extraction_type: str = "handwritten_text"


class Question(BaseModel):
    id: int
    regions: list[Region]


class ProcessRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    files: list[str]
    template: str | None = None
    questions: list[Question]
    submission_id: int | None = None


# Health check básico da raiz
@app.get("/")
async def root():
    return {
        "message": "API is running",
        "status": "ok",
    }


# Status de saúde do serviço
@app.get("/health")
async def health():
    return {
        "status": "healthy",
    }


# Processa a submissão e devolve a extração
@app.post("/process")
def process(request: ProcessRequest):
    pipeline = Pipeline(
        files=request.files,
        template=request.template,
        questions=[q.model_dump() for q in request.questions],
        submission_id=request.submission_id,
    )

    try:
        return pipeline.run()
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc
