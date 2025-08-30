# services/clip_service.py
from flask import Flask, request, jsonify
import torch
import clip
from PIL import Image
import io
import numpy as np
import base64
import sqlite3
import json
from qdrant_client import QdrantClient
from qdrant_client.http import models

app = Flask(__name__)

# Load CLIP model
device = "cuda" if torch.cuda.is_available() else "cpu"
model, preprocess = clip.load("ViT-B/32", device=device)

# Initialize Qdrant client (local mode)
client = QdrantClient(path="./qdrant_data")

# Create collection if it doesn't exist
try:
    client.get_collection("products")
except Exception:
    client.create_collection(
        collection_name="products",
        vectors_config=models.VectorParams(
            size=512,  # CLIP ViT-B/32 produces 512-dim vectors
            distance=models.Distance.COSINE
        )
    )

@app.route('/embed', methods=['POST'])
def generate_embedding():
    try:
        # Get image data
        if 'image' in request.files:
            image_file = request.files['image']
            image = Image.open(io.BytesIO(image_file.read()))
        elif 'image_base64' in request.json:
            image_data = base64.b64decode(request.json['image_base64'])
            image = Image.open(io.BytesIO(image_data))
        else:
            return jsonify({'error': 'No image provided'}), 400

        # Preprocess image and generate embedding
        image_input = preprocess(image).unsqueeze(0).to(device)

        with torch.no_grad():
            image_features = model.encode_image(image_input)
            embedding = image_features.cpu().numpy().astype(np.float32).tolist()[0]

        return jsonify({'embedding': embedding})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/qdrant/upsert', methods=['POST'])
def qdrant_upsert():
    try:
        data = request.json
        points = [
            models.PointStruct(
                id=data['id'],
                vector=data['vector'],
                payload=data.get('payload', {})
            )
        ]

        operation_info = client.upsert(
            collection_name="products",
            points=points
        )

        return jsonify({'success': True, 'operation_id': operation_info.operation_id})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/qdrant/search', methods=['POST'])
def qdrant_search():
    try:
        data = request.json
        vector = data['vector']
        limit = data.get('limit', 10)

        search_result = client.search(
            collection_name="products",
            query_vector=vector,
            limit=limit
        )

        # Convert search results to a serializable format
        results = []
        for hit in search_result:
            results.append({
                'id': hit.id,
                'score': hit.score,
                'payload': hit.payload
            })

        return jsonify({'results': results})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/qdrant/delete', methods=['POST'])
def qdrant_delete():
    try:
        data = request.json
        point_id = data['id']

        client.delete(
            collection_name="products",
            points_selector=models.PointIdsList(
                points=[point_id]
            )
        )

        return jsonify({'success': True})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({'status': 'healthy', 'device': device})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
