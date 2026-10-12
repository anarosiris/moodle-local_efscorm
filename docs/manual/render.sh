#!/bin/bash
# Regenera el PDF de la guía tras editar guia.html (requiere weasyprint y ./fonts).
cd "$(dirname "$0")"
python3 -c "from weasyprint import HTML; HTML('guia.html').write_pdf('Guia_local_efscorm.pdf'); print('PDF regenerado: Guia_local_efscorm.pdf')"
python3 -c "from weasyprint import HTML; HTML('guide_en.html').write_pdf('Guide_local_efscorm_en.pdf'); print('PDF regenerated: Guide_local_efscorm_en.pdf')"
