/* IVC 4.7.1 — visor compartido para recetas protegidas (no usa HTML de usuario). */
(() => {
  'use strict';
  function montar(destino, archivos, base) {
    if (!(destino instanceof Element)) return;
    destino.replaceChildren();
    const docs = (Array.isArray(archivos) ? archivos : []).filter(a => {
      const id = Number(a.id_archivo);
      return Number.isSafeInteger(id) && id > 0 && ['image/jpeg','image/png','application/pdf'].includes(a.tipo_mime);
    });
    if (!docs.length) {
      const notice=document.createElement('p');notice.className='ivc-rxp-vacio';
      notice.textContent='Esta receta no tiene imagen o PDF adjunto. Si fue capturada manualmente, consulta su graduación registrada.';
      destino.append(notice); return;
    }
    const root=document.createElement('section');root.className='ivc-rxp';
    const titulo=document.createElement('div');titulo.className='ivc-rxp-titulo';titulo.textContent='Vista previa de la receta';
    const tabs=document.createElement('div');tabs.className='ivc-rxp-archivos';tabs.setAttribute('role','group');tabs.setAttribute('aria-label','Archivos adjuntos');
    const panel=document.createElement('div');panel.className='ivc-rxp-panel';
    const actions=document.createElement('div');actions.className='ivc-rxp-acciones';
    const info=document.createElement('p');info.className='ivc-rxp-ayuda';info.textContent='El documento se consulta dentro del sistema. Si el navegador no puede mostrar el PDF, utiliza «Descargar original».';
    const botones=[];
    function seleccionar(archivo,indice) {
      panel.replaceChildren();actions.replaceChildren();
      botones.forEach((b,i)=>{b.classList.toggle('activo',i===indice);b.setAttribute('aria-pressed', String(i===indice));});
      const id=Number(archivo.id_archivo);
      const url=base+'recetasAdmin/vistaPrevia/'+encodeURIComponent(String(id));
      if (archivo.tipo_mime === 'application/pdf') {
        const frame=document.createElement('iframe');frame.src=url;frame.className='ivc-rxp-pdf';
        frame.title='Vista previa PDF de receta, archivo '+(indice+1);frame.loading='lazy';
        frame.referrerPolicy='no-referrer';panel.append(frame);
      } else {
        const img=document.createElement('img');img.src=url;img.className='ivc-rxp-imagen';
        img.alt='Imagen adjunta de la receta, archivo '+(indice+1);img.loading='lazy';
        img.referrerPolicy='no-referrer';panel.append(img);
      }
      const link=document.createElement('a');link.className='btn btn-sm btn-outline-primary';
      link.href=base+'recetasAdmin/archivo/'+encodeURIComponent(String(id));
      link.textContent='Descargar original';link.rel='nofollow';actions.append(link);
      const nombre=document.createElement('span');nombre.className='ivc-rxp-nombre';
      nombre.textContent=String(archivo.nombre_original||('Archivo '+(indice+1)));actions.append(nombre);
    }
    docs.forEach((doc,idx)=>{
      const button=document.createElement('button');button.type='button';button.className='ivc-rxp-tab';
      button.textContent=(doc.tipo_mime==='application/pdf'?'PDF ':'Imagen ')+(idx+1);
      button.addEventListener('click',()=>seleccionar(doc,idx));botones.push(button);tabs.append(button);
    });
    root.append(titulo,tabs,panel,actions,info);destino.append(root);seleccionar(docs[0],0);
  }
  window.IVCRecetaPreview=Object.freeze({montar});
})();
