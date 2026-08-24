#!/bin/bash

# Renders every endpoint document to ../html/. `common.yaml` holds shared components only
# and is skipped — it is referenced by the endpoint documents, not read on its own.
#
# This module documents its own `/api/v1` surface here. The endpoints core ships (`/api/v2`)
# are documented by core itself, in `docs/api/` of the core repository (see ../api-stack.md).

cd "$(dirname "$0")" || exit 1

mkdir -p ../html

for filename in *.yaml; do
    [ "$filename" = "common.yaml" ] && continue
    echo "--------- $filename ---------------------"
    npx @redocly/cli build-docs "$filename" -o "../html/$(basename "$filename" .yaml).html"
done
