FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

# Install LibreOffice + Python + build tools for pip packages
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libreoffice-core \
        libreoffice-writer \
        libreoffice-common \
        fonts-dejavu \
        ca-certificates \
        curl \
        python3 \
        python3-pip \
        python3-setuptools \
        python3-wheel && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy requirements file first to leverage Docker layer cache
COPY requirements.txt .

# Install Python dependencies
RUN pip3 install --break-system-packages --no-cache-dir -r requirements.txt

# Copy the app code
COPY main.py .

# Create shared data volume
RUN mkdir -p /data
VOLUME /data

# Run the FastAPI server
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]