#!/bin/bash

CSS_DIR="theme"
mkdir -p fonts

find "$CSS_DIR" -type f -name "*.css" | while read -r css_file; do
    echo "Processing $css_file..."

    # Extract URLs from @import url(...) statements
    grep -oE '@import url\([^)]+\)' "$css_file" | while read -r line; do
        # Extract the URL inside the parentheses
        google_url=$(echo "$line" | grep -oE 'https://fonts.googleapis.com/css\?[^)]+')
        if [ -n "$google_url" ]; then
            echo "Found Google Fonts URL: $google_url"

            # Fetch the CSS content
            css_content=$(curl -s "$google_url")

            # Extract all .woff URLs
            echo "$css_content" | grep -oE 'https://[^"'\'' ]+\.woff' | sort -u | while read -r woff_url; do
                filename=$(basename "$woff_url")
                output_path="fonts/$filename"

                if [ ! -f "$output_path" ]; then
                    echo "Downloading $woff_url..."
                    curl -s -L "$woff_url" -o "$output_path"
                else
                    echo "$filename already exists, skipping."
                fi
            done
        fi
    done
done

echo "Done."
