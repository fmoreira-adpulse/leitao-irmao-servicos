from fastapi import FastAPI, File, UploadFile
from fastapi.responses import FileResponse
import shutil
import os
import subprocess

app = FastAPI()

UPLOAD_DIR = "/data"

@app.post("/convert")
async def convert_doc_to_pdf(file: UploadFile = File(...)):
    input_path = f"{UPLOAD_DIR}/{file.filename}"
    output_path = input_path.rsplit(".", 1)[0] + ".pdf"

    with open(input_path, "wb") as f:
        shutil.copyfileobj(file.file, f)

    result = subprocess.run([
        "libreoffice", "--headless", "--convert-to", "pdf", "--outdir", UPLOAD_DIR, input_path
    ], capture_output=True)

    if not os.path.exists(output_path):
        return {"error": result.stderr.decode()}

    return FileResponse(output_path, media_type="application/pdf", filename=os.path.basename(output_path))