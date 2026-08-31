import os
from io import BytesIO
from fastapi import FastAPI, File, HTTPException, UploadFile
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

# The model was trained only on potato leaf photos, so it has no notion of
# "not a leaf" — it will always confidently pick one of the 3 classes above,
# even for a photo of a car or a cat. These two gates catch obviously wrong
# input before it ever reaches the model (or after, if the model itself is
# unsure), rather than pretending a diagnosis is meaningful either way.
LEAF_PIXEL_THRESHOLD = 0.08     # min fraction of foliage/lesion-like pixels
CONFIDENCE_THRESHOLD = 0.70     # min softmax confidence to trust the call


@app.get("/ping")
async def ping():
    return {"message": "Hello World"}


def read_file_as_image(data):
    image = Image.open(BytesIO(data)).convert("RGB")

    # resize to training size
    image = image.resize((IMAGE_SIZE, IMAGE_SIZE))

    image = np.array(image).astype(np.float32)


    return image


def leaf_pixel_fraction(image: np.ndarray) -> float:
    """Rough, model-free estimate of how much of the image looks like
    foliage or leaf-lesion material (green, or brown/desaturated spots),
    using the Excess Green Index plus a simple brown-tone heuristic."""
    r, g, b = image[..., 0], image[..., 1], image[..., 2]

    exg = 2 * g - r - b
    green_like = exg > 15

    max_c = np.maximum(np.maximum(r, g), b)
    min_c = np.minimum(np.minimum(r, g), b)
    brown_like = (r >= g) & (g >= b) & (max_c < 200) & ((max_c - min_c) > 10)

    leaf_like = green_like | brown_like
    return float(np.count_nonzero(leaf_like)) / leaf_like.size


@app.post("/predict")
async def predict(file: UploadFile = File(...)):

    image = read_file_as_image(await file.read())

    if leaf_pixel_fraction(image) < LEAF_PIXEL_THRESHOLD:
        raise HTTPException(status_code=422, detail={
            "error": "not_a_leaf",
            "message": "This doesn't look like a potato leaf photo. Please "
                       "upload a clear, close-up photo of a single leaf.",
        })

    img_batch = np.expand_dims(image, 0)

    # run inference
    predictions = infer(tf.constant(img_batch))

    # extract tensor output
    output = list(predictions.values())[0].numpy()

    print("Model raw output:", output)

    predicted_class = CLASS_NAMES[np.argmax(output[0])]
    confidence = float(np.max(output[0]))

    if confidence < CONFIDENCE_THRESHOLD:
        raise HTTPException(status_code=422, detail={
            "error": "low_confidence",
            "message": "The AI engine isn't confident this is a potato "
                       "leaf. Please try a clearer, closer photo of a "
                       "single leaf.",
        })

    return {
        "class": predicted_class,
        "confidence": confidence
    }


if __name__ == "__main__":
    uvicorn.run(app, host="localhost", port=8002)