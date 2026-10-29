/** Checkout V10 — Experiencia visual de receta. No envía ni persiste datos clínicos. */
(() => {
  'use strict';
  const root = document.querySelector('#ivcCheckoutV10');
  if (!root || root.dataset.rxReady === '1') return;
  root.dataset.rxReady = '1';
  const manualBtn = root.querySelector('#ivcRxManualBtn');
  const uploadBtn = root.querySelector('#ivcRxUploadBtn');
  const manualPanel = root.querySelector('#ivcRxManual');
  const uploadPanel = root.querySelector('#ivcRxUpload');
  if (!manualBtn || !uploadBtn || !manualPanel || !uploadPanel) return;
  const input = root.querySelector('#ivcRxFile');
  const dropzone = root.querySelector('#ivcRxDropzone');
  const result = root.querySelector('#ivcRxFileResult');
  const error = root.querySelector('#ivcRxFileError');
  const selector = root.querySelector('#ivcRxProduct');
  let currentObjectUrl = null;

  function selectMethod(method) {
    const isManual = method === 'manual';
    manualBtn.classList.toggle('is-active', isManual);
    uploadBtn.classList.toggle('is-active', !isManual);
    manualBtn.setAttribute('aria-pressed', String(isManual));
    uploadBtn.setAttribute('aria-pressed', String(!isManual));
    manualPanel.hidden = !isManual;
    uploadPanel.hidden = isManual;
  }
  manualBtn.addEventListener('click', () => selectMethod('manual'));
  uploadBtn.addEventListener('click', () => selectMethod('upload'));

  function clearObjectUrl() {
    if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
    currentObjectUrl = null;
  }
  function fileError(message) {
    error.textContent = message;
    error.hidden = !message;
  }
  function resetFile() {
    clearObjectUrl();
    input.value = '';
    result.replaceChildren();
    result.hidden = true;
    fileError('');
  }
  function setFile(file) {
    if (!file) { resetFile(); return; }
    const allowed = new Set(['image/jpeg', 'image/png', 'application/pdf']);
    const extension = (file.name.split('.').pop() || '').toLowerCase();
    const suffixAllowed = ['jpg', 'jpeg', 'png', 'pdf'].includes(extension);
    if (!allowed.has(file.type) || !suffixAllowed) {
      resetFile(); fileError('Solo se admiten fotografías JPG, PNG o documentos PDF.'); return;
    }
    if (!file.size || file.size > 10 * 1024 * 1024) {
      resetFile(); fileError('El archivo debe tener un tamaño máximo de 10 MB.'); return;
    }
    clearObjectUrl();
    currentObjectUrl = URL.createObjectURL(file);
    result.replaceChildren();
    const preview = document.createElement(file.type === 'application/pdf' ? 'span' : 'img');
    if (preview.tagName === 'IMG') {
      preview.src = currentObjectUrl;
      preview.alt = 'Vista previa local de la receta seleccionada';
    } else {
      preview.textContent = 'PDF';
      preview.style.cssText = 'color:#287CB3;font-weight:800;background:#EAF6FC;border-radius:7px;padding:15px 12px;font-size:12px;';
    }
    const meta = document.createElement('div');
    meta.className = 'ivc-checkout-file-meta';
    const name = document.createElement('strong');
    name.textContent = file.name;
    const description = document.createElement('span');
    description.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB · Seleccionado localmente, todavía no enviado`;
    const link = document.createElement('a');
    link.href = currentObjectUrl;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Previsualizar archivo ↗';
    meta.append(name, description, link);
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'ivc-checkout-remove-file';
    remove.textContent = 'Quitar';
    remove.addEventListener('click', resetFile);
    result.append(preview, meta, remove);
    result.hidden = false;
    fileError('');
  }
  input.addEventListener('change', () => setFile(input.files?.[0]));
  for (const type of ['dragenter', 'dragover']) dropzone.addEventListener(type, event => {
    event.preventDefault();
    dropzone.classList.add('is-dragover');
  });
  for (const type of ['dragleave', 'drop']) dropzone.addEventListener(type, event => {
    event.preventDefault();
    dropzone.classList.remove('is-dragover');
  });
  dropzone.addEventListener('drop', event => setFile(event.dataTransfer?.files?.[0]));
  window.addEventListener('pagehide', clearObjectUrl, { once: true });

  // Se muestra el nombre de los productos de la tabla que ya actualiza clientes.js.
  // No altera listaCarrito ni inventa un envío de receta.
  const tbody = root.querySelector('#tableListaProductos tbody');
  if (tbody && selector) {
    let scheduled = false;
    const populateProducts = () => {
      scheduled = false;
      const previous = selector.value;
      const rows = [...tbody.querySelectorAll('tr')];
      const items = rows.map((row, position) => {
        const cells = row.querySelectorAll('td');
        if (cells.length !== 5 || cells[0].hasAttribute('colspan')) return null;
        const title = cells[1]?.textContent?.trim() || '';
        return title ? { value: `producto-${position}`, title } : null;
      }).filter(Boolean);
      selector.replaceChildren(new Option(items.length ? 'Selecciona un producto del pedido' : 'No hay productos en el pedido', ''));
      for (const item of items) selector.add(new Option(item.title, item.value));
      selector.value = items.some(item => item.value === previous) ? previous : '';
      selector.disabled = items.length === 0;
    };
    const observer = new MutationObserver(() => {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(populateProducts);
    });
    observer.observe(tbody, { childList: true });
    populateProducts();
    window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
  }
})();
