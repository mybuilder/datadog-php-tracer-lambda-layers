#!/usr/bin/env bash

set -e

command -v aws >/dev/null || {
  echo 'ERROR: aws command is missing' >&2
  exit 1
}

declare -A PHP_VERSIONS=( [74]=20190902 )
DD_TRACE_VERSION=0.56.0
LAYERS_DIR=$(pwd)/layers

mkdir -p "${LAYERS_DIR}"

for PHP_VERSION in "${!PHP_VERSIONS[@]}"; do
  echo ""
  echo "### Building datadog-php-tracer ${DD_TRACE_VERSION} Lambda layer for PHP ${PHP_VERSION}"
  echo ""

  IMAGE="datadog-php-tracer-${DD_TRACE_VERSION}-php-${PHP_VERSION}"
  ZIP_PATH="${LAYERS_DIR}/${IMAGE}.zip"

  docker build -t "${IMAGE}" \
    --build-arg PHP_VERSION="${PHP_VERSION}" \
    --build-arg PHP_VERSION_DATE="${PHP_VERSIONS[$PHP_VERSION]}" \
    --build-arg DD_TRACE_VERSION="${DD_TRACE_VERSION}" .

  BUILD_DIR=$(pwd)/build/"${IMAGE}"
  rm -rf "${BUILD_DIR}" && mkdir -p "${BUILD_DIR}"
  docker run --rm --entrypoint tar "${IMAGE}" -ch -C /opt . | tar -x -C "${BUILD_DIR}"
  cd "${BUILD_DIR}" && zip -rX "${ZIP_PATH}" ./* && cd - >/dev/null

  echo ""
  echo "### Publishing datadog-php-tracer ${DD_TRACE_VERSION} Lambda layer for PHP ${PHP_VERSION}"
  echo ""

  LAYER_VERSION=$(
    aws lambda publish-layer-version \
      --region eu-west-1 \
      --layer-name "datadog-php-tracer-${PHP_VERSION}" \
      --zip-file "fileb://${ZIP_PATH}" \
      --compatible-runtimes provided \
      --license-info MIT \
      --output text \
      --query Version
  )

  aws lambda add-layer-version-permission \
    --region eu-west-1 \
    --layer-name "datadog-php-tracer-${PHP_VERSION}" \
    --version-number "${LAYER_VERSION}" \
    --action lambda:GetLayerVersion \
    --statement-id public \
    --principal "*"
done
