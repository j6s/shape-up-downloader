#!/bin/bash
echo "========== Downloading single HTML =================="
php /app/cli.php download:single-html
echo "============ Converting to e-book ===================="
ebook-convert shape-up.html /app/output/shape-up.${1} \
    --authors "Ryan Singer" \
    --title "Shape Up: Stop Running in Circles and Ship Work that Matters" \
    --book-producer "Basecamp" \
    --publisher "Basecamp" \
    --language "en"