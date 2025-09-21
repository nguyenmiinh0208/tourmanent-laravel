#!/bin/bash

echo "Building production assets..."

# Install dependencies if not exists
if [ ! -d "node_modules" ]; then
    echo "Installing NPM dependencies..."
    npm install
fi

# Build assets for production
echo "Building Vite assets..."
npm run build

# Set proper permissions
echo "Setting permissions..."
chmod -R 755 public/build

echo "Assets built successfully!"
