/**
 * WORKSHIFT - Descargador de PDF A4 Calibrado
 */
async function descargarReporteDirecto(targetUserId, mes, anio, btnElement) {
    let originalHtml = '';
    if (btnElement) {
        originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';
        btnElement.disabled = true;
    }

    try {
        if (typeof html2pdf === 'undefined') {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        const url = `index.php?action=obtener_html_reporte&usuario_id=${targetUserId}&mes=${mes}&anio=${anio}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error('Error al obtener datos');
        const html = await res.text();

        // Ancho exacto imprimible A4 con margen: 720px
        const wrapper = document.createElement('div');
        wrapper.id = 'export-pdf-temp-wrapper';
        wrapper.style.position = 'fixed';
        wrapper.style.top = '0';
        wrapper.style.left = '0';
        wrapper.style.width = '720px';
        wrapper.style.backgroundColor = '#ffffff';
        wrapper.style.zIndex = '999999';
        wrapper.style.boxSizing = 'border-box';
        wrapper.innerHTML = html;
        document.body.appendChild(wrapper);

        const elementoParaPdf = wrapper.querySelector('#hoja-reporte-a4') || wrapper;
        elementoParaPdf.style.width = '720px';
        elementoParaPdf.style.padding = '12px 14px';
        elementoParaPdf.style.margin = '0 auto';
        elementoParaPdf.style.boxSizing = 'border-box';

        const inputNombre = wrapper.querySelector('#nombre-archivo-generado');
        const nombreArchivo = inputNombre ? inputNombre.value : `Certificado_${mes}_${anio}.pdf`;

        await new Promise(r => setTimeout(r, 200));

        const opciones = {
            margin: [8, 8, 8, 8],
            filename: nombreArchivo,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };

        await html2pdf().set(opciones).from(elementoParaPdf).save();
        document.body.removeChild(wrapper);

    } catch (error) {
        console.error('Error generando PDF:', error);
        alert('Hubo un error al generar la descarga del PDF.');
    } finally {
        if (btnElement) {
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        }
    }
}