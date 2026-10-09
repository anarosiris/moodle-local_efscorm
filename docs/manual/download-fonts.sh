#!/bin/bash
# Descarga las fuentes (Poppins + Lato, licencia OFL) necesarias para render.sh.
cd "$(dirname "$0")"; mkdir -p fonts; B="https://github.com/google/fonts/raw/main/ofl"
curl -sSL -o fonts/Poppins-Regular.ttf  "$B/poppins/Poppins-Regular.ttf"
curl -sSL -o fonts/Poppins-Medium.ttf   "$B/poppins/Poppins-Medium.ttf"
curl -sSL -o fonts/Poppins-SemiBold.ttf "$B/poppins/Poppins-SemiBold.ttf"
curl -sSL -o fonts/Poppins-Bold.ttf     "$B/poppins/Poppins-Bold.ttf"
curl -sSL -o fonts/Lato-Regular.ttf     "$B/lato/Lato-Regular.ttf"
curl -sSL -o fonts/Lato-Bold.ttf        "$B/lato/Lato-Bold.ttf"
echo "Fuentes descargadas en ./fonts"
