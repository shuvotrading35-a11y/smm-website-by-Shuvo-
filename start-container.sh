#!/bin/bash
set -e

# Shuvo SMM Panel — Railway/Railpack startup override.
# The app's .env expects JWT_PRIVATE_KEY_PATH / JWT_PUBLIC_KEY_PATH to exist
# on disk (see src/Core, firebase/php-jwt). On a VPS these live at
# /etc/ssl/smm/*.pem (see README). On Railway's ephemeral filesystem we
# generate them into the app directory on boot instead. Point the Railway
# service's env vars at these same paths:
#   JWT_PRIVATE_KEY_PATH=/app/storage/certs/private.pem
#   JWT_PUBLIC_KEY_PATH=/app/storage/certs/public.pem
# Note: without a mounted volume these regenerate on every redeploy/restart,
# which invalidates existing JWTs/sessions. Fine for testing; attach a
# Railway Volume at /app/storage if you need keys to persist.

mkdir -p /app/storage/certs
if [ ! -f /app/storage/certs/private.pem ]; then
    echo "Generating JWT keypair for this container..."
    openssl genrsa -out /app/storage/certs/private.pem 2048
    openssl rsa -in /app/storage/certs/private.pem -pubout -out /app/storage/certs/public.pem
fi

mkdir -p /app/public/assets/uploads/avatars /app/public/assets/uploads/proofs

# Start the FrankenPHP server (same as Railpack's default PHP start-container.sh)
docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1
