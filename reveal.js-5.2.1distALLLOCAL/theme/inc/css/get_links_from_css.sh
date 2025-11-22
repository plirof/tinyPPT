#!/bin/bash

# Initialize the script file
echo "#!/bin/bash" > get_links.sh

# Loop over all CSS files
for cssfile in *.css; do
  # Extract lines with src: url() or @import url()
  grep -Eo 'src:\s*url\([^\)]+\)|@import\s+url\([^\)]+\)' "$cssfile" | while read -r line; do
    # Extract URL from line using sed with double quotes and escaping inner quotes
    url=$(echo "$line" | sed -E "s/.*url\\([\'\\\"]?([^\'\\\"\\)]+)[\'\\\"]?\\).*/\\1/")
    # Check if URL is not empty
    if [ -n "$url" ]; then
      echo "wget \"$url\"" >> get_links.sh
    fi
  done
done

chmod +x get_links.sh