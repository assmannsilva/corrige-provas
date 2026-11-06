import io
import json

import numpy as np
import pytest
from fastapi.testclient import TestClient

import pipeline
import vision_llm
from main import app

# Campos de identificação não fazem parte do contrato interno de processamento.
@pytest.mark.parametrize("field", ["students", "name_region", "turma_region", "student_name", "student_class"])
def test_process_rejects_identification(field):
    response = TestClient(app).post("/process", json={
        "files": [], "questions": [], field: "synthetic",
    })
    assert response.status_code == 422


# Uma página sintética distingue o cabeçalho da região enviada para transcrição.
def test_pipeline_sends_only_exact_answer_crop(monkeypatch):
    page = np.full((100, 100, 3), 255, dtype=np.uint8)
    page[:20] = [0, 0, 255]
    page[50:70, 20:80] = [20, 30, 40]
    sent = []
    monkeypatch.setattr(pipeline, "render_pages", lambda _: [page])
    monkeypatch.setattr(pipeline.Pipeline, "resolve_path", lambda self, path: path)
    monkeypatch.setattr(pipeline, "density", lambda _: 1.0)
    monkeypatch.setattr(pipeline, "transcribe_consistent", lambda image: (sent.append(image.copy()) or "Resposta", False))
    worker = pipeline.Pipeline(files=["synthetic.png"], template=None, questions=[{
        "id": 1, "regions": [{"page": 1, "x": .2, "y": .5, "width": .6,
        "height": .2, "key": "essay", "extraction_type": "handwritten_text"}],
    }])
    result = worker.run()
    assert set(result) == {"results"}
    assert result["results"][0]["raw_text"] == "Resposta"
    assert len(sent) == 1
    np.testing.assert_array_equal(sent[0], page[50:70, 20:80])


# OMR continua local e nunca precisa de transcrição externa.
def test_choice_processing_does_not_call_vision(monkeypatch):
    monkeypatch.setattr(pipeline, "transcribe_consistent", lambda _: pytest.fail("OMR called vision"))
    worker = pipeline.Pipeline(files=[], template=None, questions=[])
    worker.aligned_pages = [np.full((100, 100, 3), 255, dtype=np.uint8)]
    result = worker.process_question({"id": 1, "regions": [{
        "page": 1, "x": .2, "y": .5, "width": .1, "height": .1,
        "key": "choice:0", "extraction_type": "choice",
    }]})
    assert result["raw_text"] == "UNCHECKED"


# Intercepta a requisição HTTP: usa chave fictícia, sem acessar o provedor.
@pytest.mark.parametrize("override", [None, "custom/model"])
def test_vision_payload_model_and_image(monkeypatch, override):
    monkeypatch.setenv("OPENROUTER_API_KEY", "synthetic-test-key")
    monkeypatch.delenv("OPENROUTER_MODEL", raising=False)
    if override:
        monkeypatch.setenv("OPENROUTER_MODEL", override)
    sent = []
    def respond(request, timeout):
        sent.append(json.loads(request.data))
        return io.BytesIO(json.dumps({"choices": [{"message": {
            "content": '{"text": "Resposta", "legibility": 5}'
        }}]}).encode())
    monkeypatch.setattr(vision_llm.urllib.request, "urlopen", respond)
    image = np.zeros((20, 30, 3), dtype=np.uint8)
    assert vision_llm.transcribe(image) == ("Resposta", 5)
    assert sent[0]["model"] == (override or "google/gemini-3.7-flash")
    assert set(sent[0]) == {"model", "messages", "temperature"}
    content = sent[0]["messages"][0]["content"]
    assert len(content) == 2
    assert content[1]["image_url"]["url"].startswith("data:image/png;base64,")
