#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

IMAGE_NAME="${IMAGE_NAME:-e-warga-app:latest}"
VM_USER="${VM_USER:-pkkadmin}"
VM_HOST="${VM_HOST:-103.59.95.229}"
REMOTE_DIR="${REMOTE_DIR:-e-warga}"
ARCHIVE_NAME="${ARCHIVE_NAME:-e-warga-app.tar.gz}"

DEPLOY_STAGING="$(mktemp -d)"
trap 'rm -rf "${DEPLOY_STAGING}"' EXIT
ARCHIVE_PATH="${DEPLOY_STAGING}/${ARCHIVE_NAME}"

cd "${REPO_ROOT}"

echo "==> Build image ${IMAGE_NAME}"
docker build -t "${IMAGE_NAME}" -f docker/production/Dockerfile .

echo "==> Simpan image ke direktori sementara (${ARCHIVE_PATH})"
docker save "${IMAGE_NAME}" | gzip > "${ARCHIVE_PATH}"

echo "==> Unggah artefak ke ${VM_USER}@${VM_HOST}:~/${REMOTE_DIR}/"
ssh "${VM_USER}@${VM_HOST}" "mkdir -p ~/${REMOTE_DIR}/docker/production"
scp "${ARCHIVE_PATH}" "${REPO_ROOT}/docker-compose.prod.yml" \
    "${VM_USER}@${VM_HOST}:~/${REMOTE_DIR}/"

echo "==> Muat image dan jalankan stack di VM"
ssh "${VM_USER}@${VM_HOST}" bash -s <<EOF
set -euo pipefail
cd ~/${REMOTE_DIR}
gunzip -c ${ARCHIVE_NAME} | docker load
rm -f ${ARCHIVE_NAME}
docker compose -f docker-compose.prod.yml up -d --no-build --wait
docker compose -f docker-compose.prod.yml ps
EOF

echo "==> Deploy selesai."
