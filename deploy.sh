#!/usr/bin/env sh

echo "Stopping and removing existing container..."
docker stop apple-refund-assistant 2>/dev/null || true
docker rm apple-refund-assistant 2>/dev/null || true

echo "Building image..."
docker build -t apple-refund-assistant-image .

echo "Starting container with auto-restart..."
docker run -d \
  -p 8080:8080 \
  --name apple-refund-assistant \
  --restart=always \
  apple-refund-assistant-image

echo "Container started successfully!"
echo "Access the application at: http://localhost:8080"
