#!/bin/bash
echo Keeping tailwind.css up-to-date...
npx tailwindcss -i ../css/tailwind-src.css -o ../css/tailwind.css -m -w
