#!/bin/bash

# Clear the output file
echo "#!/bin/bash" > get_links.sh

# Find all URLs in CSS files
grep -Eo 'url\([^\)]+\)|@import\s+url\([^\)]+\)' *.css | \
grep -Eo 'https?://[^)\'\"]+' | \
while read -r url; do
  # Append wget command to get_links.sh
  echo "wget \"$url\"" >> get_links.sh
done

chmod +x get_links.sh
