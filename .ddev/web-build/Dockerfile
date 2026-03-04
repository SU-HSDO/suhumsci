# Use the default DDEV web image as base
ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install Chromium for Codeception testing (works on ARM64)
RUN apt-get update && apt-get install -y \
    chromium \
    chromium-driver \
    && rm -rf /var/lib/apt/lists/*

# Create symlinks for compatibility
RUN ln -sf /usr/bin/chromium /usr/bin/google-chrome \
    && ln -sf /usr/bin/chromedriver /usr/local/bin/chromedriver
