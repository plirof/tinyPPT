#I have a folder with CSS that calls google fonts files. The google url then downloads woff files. Can you wrote a bash script that will scan the folder themes for *.css files, locate inside them the google URL (eg https://fonts.googleapis.com/css?family=Quicksand:400,700,400italic,700italic ) and then from this URL extract and download all the *.woff files?
#NOT working 

#!/bin/bash

# Directory containing your CSS files
CSS_DIR="theme"

# Create a folder to store downloaded fonts
mkdir -p fonts

# Loop through all CSS files in the directory
find "$CSS_DIR" -type f -name "*.css" | while read -r css_file; do
    echo "Processing $css_file..."

    # Extract Google Fonts URL(s) from the CSS file
    # Assuming the URL is within url(...) in CSS files
    grep -oP grep -oE 'https://fonts.googleapis.com/css\?[^"'\'' ]+' "$css_file" | sort -u | while read -r google_url; do
        echo "Found Google Fonts URL: $google_url"

        # Fetch the CSS content from Google Fonts
        css_content=$(curl -s "$google_url")

        # Extract all woff file URLs from the CSS content
        echo "$css_content" | grep -oP 'https://[^"]+\.woff' | sort -u | while read -r woff_url; do
            filename=$(basename "$woff_url")
            output_path="fonts/$filename"

            # Download the font file if not already downloaded
            if [ ! -f "$output_path" ]; then
                echo "Downloading $woff_url..."
                curl -s -L "$woff_url" -o "$output_path"
            else
                echo "$filename already exists, skipping download."
            fi
        done
    done
done

echo "Done."