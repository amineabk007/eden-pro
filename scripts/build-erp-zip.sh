#!/usr/bin/env bash
# Construit dunerooftop-erp.zip (contenu de gestion/, sans config.php) à téléverser sur Hostinger.
set -euo pipefail
cd "$(dirname "$0")/../gestion"
OUT="${1:-../dunerooftop-erp.zip}"
rm -f "$OUT"
zip -r -q "$OUT" . -x 'config.php' '*.sqlite' '.gitignore' 'install.sql.bak'
echo "Créé : $(cd "$(dirname "$OUT")" && pwd)/$(basename "$OUT")"
