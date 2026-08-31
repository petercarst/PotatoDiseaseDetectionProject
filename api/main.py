import os
from io import BytesIO
from fastapi import FastAPI, File, UploadFile
import uvicorn
import numpy as np
from PIL import Image
import tensorflow as tf
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()

origins = [
    "http://localhost",
    "http://localhost:3000",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials = True,
    allow_methods = ["*"],
    allow_headers = ["*"],
)
# Load TensorFlow SavedModel (resolved relative to this file, not the cwd
# the server happens to be launched from)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "..", "models", "potato-disease", "1")
MODEL = tf.saved_model.load(MODEL_PATH)
infer = MODEL.signatures["serving_default"]

CLASS_NAMES = ["Early Blight", "Late Blight", "Healthy"]
IMAGE_SIZE = 256


@app.get("/ping")
async def ping():
    return {"message": "Hello World"}


def read_file_as_image(data):
    image = Image.open(BytesIO(data)).convert("RGB")

    # resize to training size
    image = image.resize((IMAGE_SIZE, IMAGE_SIZE))

    image = np.array(image).astype(np.float32)


    return image


@app.post("/predict")
async def predict(file: UploadFile = File(...)):

    image = read_file_as_image(await file.read())

    img_batch = np.expand_dims(image, 0)

    # run inference
    predictions = infer(tf.constant(img_batch))

    # extract tensor output
    output = list(predictions.values())[0].numpy()

    print("Model raw output:", output)

    predicted_class = CLASS_NAMES[np.argmax(output[0])]
    confidence = float(np.max(output[0]))

    return {
        "class": predicted_class,
        "confidence": confidence
    }


if __name__ == "__main__":
    uvicorn.run(app, host="localhost", port=8002)