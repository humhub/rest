#!/bin/bash

for filename in *.yaml; do
    echo "--------- $filename ---------------------"
    npx @redocly/cli build-docs $filename -o ../html/$(basename "$filename" .yaml).html
done