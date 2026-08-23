#!/bin/bash

# Renders every endpoint document to ../html/. `common.yaml` holds shared components only
# and is skipped — it is referenced by the endpoint documents, not read on its own.
#
# Layout: the flat files are the /api/v1 surface of this module, `v2/` documents the
# endpoints core itself ships (see ../api-stack.md) and renders to ../html/v2/.

build() {
    local source_dir="$1"
    local target_dir="$2"

    mkdir -p "$target_dir"

    for filename in "$source_dir"/*.yaml; do
        [ "$(basename "$filename")" = "common.yaml" ] && continue
        echo "--------- $filename ---------------------"
        npx @redocly/cli build-docs "$filename" -o "$target_dir/$(basename "$filename" .yaml).html"
    done
}

cd "$(dirname "$0")" || exit 1

build . ../html
build v2 ../html/v2
