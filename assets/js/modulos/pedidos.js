/* Instituto Visual California — 4.8.1: revisión óptica unificada desde Pedidos; autoridad en PHP. */
(() => {
    'use strict';
    const root = document.getElementById('ivc-pedidos-v47');
    if (!root) return;
    const base = (typeof base_url === 'string' ? base_url : '/institutovc/').replace(/\/?$/, '/');
    const csrf = root.dataset.csrf;
    const $ = id => document.getElementById(id);
    const modalElement = $('ivcPedModal');
    const modal = new bootstrap.Modal(modalElement);
    const money = (v, currency='MXN') => {
        const n = Number(v);
        return Number.isFinite(n) ? new Intl.NumberFormat('es-MX',{style:'currency',currency:currency||'MXN'}).format(n) : String(v??'—');
    };
    const text = (tag, value, className='') => {const el=document.createElement(tag);el.textContent=String(value??'—');if(className)el.className=className;return el;};
    const pill = (s, cls) => text('span',s,'ivc-pedidos-pildora '+cls);
    const cell=(tr, content)=>{const td=document.createElement('td');if(content instanceof Node) td.append(content);else td.textContent=String(content??'—');tr.append(td);return td;};
    function aviso(msg, isError=false) {const el=$('ivcPedAviso');el.textContent=msg;el.className='alert '+(isError?'alert-danger':'alert-info');}
    function limpiarAviso() {$('ivcPedAviso').className='alert alert-info d-none';}
    async function json(url, options={}) {
        const r=await fetch(base+url,{credentials:'same-origin',headers:{'Accept':'application/json',...(options.headers||{})},cache:'no-store',...options});
        const value=await r.json().catch(()=>null);
        if(!r.ok || !value || !value.ok) throw new Error(value?.msg || 'Error de servidor ('+r.status+').');
        return value;
    }
    const proceso=(n)=>({'1':'Pendiente','2':'En preparación','3':'Finalizado'}[String(n)]||'Desconocido');
    const estadoPago=(p)=>['COMPLETED','CAPTURADA'].includes(String(p).toUpperCase());
    let actual=null;
    let cargando=false;
    async function cargar(){
        if(cargando) return;cargando=true;
        const body=$('ivcPedFilas');body.replaceChildren();const tr=document.createElement('tr');cell(tr,'Consultando pedidos…').colSpan=8;body.append(tr);limpiarAviso();
        try{
            const query=new URLSearchParams({q:$('ivcPedBusqueda').value.trim(),proceso:$('ivcPedFiltro').value});
            const response=await json('pedidos/listar?'+query.toString());
            const rows=response.pedidos||[];
            $('ivcPedTotal').textContent=rows.length;
            $('ivcPedPendientes').textContent=rows.filter(x=>String(x.proceso)==='1').length;
            $('ivcPedFinalizados').textContent=rows.filter(x=>String(x.proceso)==='3').length;
            $('ivcPedBloqueados').textContent=rows.filter(x=>Number(x.opticas_pendientes)>0||(x.valoracion&&x.valoracion!=='REALIZADA')).length;
            body.replaceChildren();
            if(!rows.length){const empty=document.createElement('tr');cell(empty,'No se encontraron pedidos.').colSpan=8;body.append(empty);}
            rows.forEach(row=>{
                const t=document.createElement('tr');
                cell(t,'#'+row.id);
                const client=text('div',(row.nombre||'')+' '+(row.apellido||''));client.append(text('div',row.email,'text-muted small'));cell(t,client);
                cell(t,row.fecha);cell(t,money(row.monto));
                cell(t,pill(row.estado,estadoPago(row.estado)?'ok':'warn'));
                cell(t,pill(proceso(row.proceso),String(row.proceso)==='3'?'ok':String(row.proceso)==='2'?'info':'warn'));
                let optica='Sin bloqueo óptico', tipo='ok';
                if(Number(row.opticas_pendientes)>0){optica=String(row.opticas_pendientes)+' pendiente(s)';tipo='stop';}
                else if(row.valoracion&&row.valoracion!=='REALIZADA'){optica='Valoración: '+row.valoracion;tipo='stop';}
                cell(t,pill(optica,tipo));
                const btn=text('button','Ver detalle','btn btn-sm btn-outline-primary');btn.type='button';btn.addEventListener('click',()=>verDetalle(row.id));cell(t,btn);
                body.append(t);
            });
        }catch(e){body.replaceChildren();aviso(e.message,true);}
        finally{cargando=false;}
    }
    const val=(a, label, value)=>{const d=document.createElement('div');d.append(text('small',label),text('strong',value));a.append(d);};
    const sec=(title)=>{const d=text('section','','ivc-pedidos-seccion');d.append(text('h6',title));return d;};
    function render(p){
        actual=p; $('ivcPedTitulo').textContent='Pedido #'+p.id;
        const el=$('ivcPedDetalle');el.replaceChildren();
        const summary=sec('Información de la compra');const meta=text('div','','ivc-pedidos-meta');
        val(meta,'Cliente',[(p.nombre||''),(p.apellido||'')].join(' '));
        val(meta,'Correo',p.email);val(meta,'Fecha',p.fecha);val(meta,'Total',money(p.monto,p.moneda||'MXN'));
        val(meta,'Estado de pago',p.estado);val(meta,'Etapa comercial',proceso(p.proceso));
        val(meta,'ID transacción',p.id_transaccion);val(meta,'Cotización',p.id_cotizacion?'#'+p.id_cotizacion:'Compra anterior');
        if(p.estado_valoracion) val(meta,'Valoración visual',p.estado_valoracion);
        summary.append(meta);el.append(summary);
        if(p.paypal){const pago=sec('Captura PayPal');const pm=text('div','','ivc-pedidos-meta');
            val(pm,'Estado',p.paypal.estado);val(pm,'Captura',p.paypal.paypal_capture_id||'Pendiente');val(pm,'Importe capturado',money(p.paypal.monto_capturado));pago.append(pm);el.append(pago);}
        if (p.estado_valoracion) {
            const section=sec('Citas para valoración visual');
            const status=text('p','Estado: '+p.estado_valoracion,'mb-2 fw-semibold');section.append(status);
            for(const cita of (p.citas||[])) {
                const row=text('div','','ivc-citas-v48-row');
                row.append(text('span','Cita #'+cita.id_cita+' · '+cita.fecha+' '+cita.hora+' · '+
                    (String(cita.verify)==='1'?'Confirmada':'Por confirmar')+
                    (String(cita.estado)!=='1'?' (inactiva)':'')));
                if(p.estado_valoracion==='AGENDADA' && String(cita.verify)==='1' && String(cita.estado)==='1') {
                    const done=text('button','Marcar valoración realizada','btn btn-sm btn-success');done.type='button';
                    done.addEventListener('click',()=>accionValoracion('pedidos/realizarValoracion',
                        {id_pedido:p.id,id_cita:cita.id_cita},'¿Confirmas que la valoración se realizó presencialmente?'));
                    row.append(done);
                }
                section.append(row);
            }
            if(p.estado_valoracion!=='REALIZADA') {
                const select=document.createElement('select');select.className='form-select form-select-sm';
                select.append(new Option('Selecciona cita del correo registrado…',''));
                for(const cita of (p.citas_candidatas||[]))
                    select.append(new Option('#'+cita.id_cita+' · '+cita.fecha+' '+cita.hora+
                        (String(cita.verify)==='1'?' · Confirmada':' · Por confirmar'),String(cita.id_cita)));
                const btn=text('button','Vincular cita','btn btn-sm btn-primary');btn.type='button';
                btn.addEventListener('click',()=>{
                    if(!select.value){notificar('Selecciona una cita disponible del cliente.',false);return;}
                    accionValoracion('pedidos/vincularCita',{id_pedido:p.id,id_cita:select.value},
                      '¿Asociar la cita #'+select.value+' con este pedido?');
                });
                const controls=text('div','','ivc-citas-v48-control');controls.append(select,btn);section.append(controls);
                section.append(text('small','Si la cita aún no aparece, el cliente puede agendar desde la página de citas usando su correo registrado.','text-muted'));
            }
            el.append(section);
        }
        const products=sec('Artículos y configuración óptica');
        for(const d of (p.productos||[])){
            const row=text('div','','ivc-pedidos-item');
            const title=text('div',d.producto,'fw-bold');row.append(title);
            const info=text('div','','ivc-pedidos-meta mt-2');
            val(info,'Cantidad',d.cantidad);val(info,'Armazón unitario',money(d.precio));
            if(d.id_mica){
                val(info,'Micas',d.nombre_mica_snapshot||('#'+d.id_mica));val(info,'Micas por par',money(d.precio_unitario_mica));
                val(info,'Estado óptico',d.estado_optico);val(info,'Receta',d.id_receta?'#'+d.id_receta:'Pendiente');
                val(info,'Revisión de receta',d.estado_receta||'Sin receta');
                val(info,'Archivos de receta',d.archivos_receta);
            }else{val(info,'Configuración','Solo armazón');}
            row.append(info);
            if(d.id_mica){
                const action=text('div','','mt-3 d-flex flex-wrap align-items-center gap-2');
                if(d.id_receta){
                    {
                        const btn=text('button','Ver receta','btn btn-sm btn-outline-primary');btn.type='button';
                        const visor=text('div','','ivc-pedidos-visor-receta mt-2');visor.hidden=true;
                        btn.addEventListener('click',async()=>{
                            if(btn.dataset.cargado==='1'){
                                visor.hidden=!visor.hidden;
                                btn.textContent=visor.hidden?'Ver receta':'Ocultar receta';
                                return;
                            }
                            btn.disabled=true;btn.textContent='Cargando receta…';
                            try {
                                const respuesta=await json('recetasAdmin/detalle/'+encodeURIComponent(String(d.id_receta)));
                                if(Number(respuesta.receta?.id_receta)!==Number(d.id_receta)) throw new Error('La receta recibida no coincide con el pedido.');
                                if(!window.IVCRecetaPreview) throw new Error('No está disponible el visor de recetas. Actualiza la página.');
                                visor.replaceChildren();
                                const receta=respuesta.receta;
                                const datos=text('div','','ivc-pedidos-meta mb-2');
                                val(datos,'Modalidad',receta.modalidad);
                                val(datos,'Estado de revisión',receta.estado_revision);
                                val(datos,'Fecha de receta',receta.fecha_receta);
                                val(datos,'Distancia pupilar',receta.distancia_pupilar_total_mm ? receta.distancia_pupilar_total_mm+' mm' : '—');
                                for (const ojo of (receta.ojos||[])) {
                                    val(datos,'Ojo '+ojo.ojo,
                                        'ESF '+(ojo.esfera??'—')+' · CIL '+(ojo.cilindro??'—')+
                                        ' · EJE '+(ojo.eje??'—')+' · ADD '+(ojo.adicion??'—'));
                                }
                                visor.append(datos);
                                const area=text('div','','ivc-pedidos-preview-archivo');visor.append(area);
                                window.IVCRecetaPreview.montar(area,receta.archivos||[],base);
                                visor.hidden=false;btn.dataset.cargado='1';btn.textContent='Ocultar receta';
                                action.querySelectorAll('[data-ivc-revision-accion]').forEach(b=>{b.disabled=false;b.title='';});
                            }catch(e){await notificar(e.message,false);btn.textContent='Ver receta';}
                            finally{btn.disabled=false;}
                        });
                        action.append(btn);row.append(visor);
                    }
                }
                if(p.estado_valoracion==='REALIZADA' && d.estado_optico!=='APROBADA' && d.estado_optico!=='CANCELADA') {
                    const crear=text('button','Capturar graduación del Instituto','btn btn-sm btn-outline-success');crear.type='button';
                    crear.addEventListener('click',()=>capturarReceta(d.id,d.producto));action.append(crear);
                }
                if(p.estado_valoracion==='REALIZADA' && d.estado_optico!=='APROBADA' && d.estado_optico!=='CANCELADA') {
                    const picker=document.createElement('select');picker.className='form-select form-select-sm';
                    picker.append(new Option('Selecciona receta del cliente…',''));
                    for(const receta of (p.recetas_candidatas||[])) {
                        if(receta.estado_revision==='RECHAZADA')continue;
                        picker.append(new Option('#'+receta.id_receta+' · '+receta.estado_revision+' · '+receta.origen,String(receta.id_receta)));
                    }
                    const linkRx=text('button','Asociar receta','btn btn-sm btn-outline-primary');linkRx.type='button';
                    linkRx.addEventListener('click',()=>{
                        if(!picker.value){notificar('Selecciona primero una receta.',false);return;}
                        accionValoracion('pedidos/asociarReceta',
                          {id_detalle:d.id,id_receta:picker.value},'¿Asociar esta receta a las micas del artículo?');
                    });
                    const linkBlock=text('div','','ivc-citas-v48-control');linkBlock.append(picker,linkRx);action.append(linkBlock);
                }
                const pendiente = d.estado_optico!=='APROBADA' && d.estado_optico!=='CANCELADA';
                const revisable = pendiente && !!d.id_receta && String(p.proceso)==='1' &&
                    estadoPago(p.estado) && (!p.estado_valoracion || p.estado_valoracion==='REALIZADA');
                if(revisable && (d.estado_receta==='PENDIENTE' || d.estado_receta==='APROBADA')){
                    const panel=text('div','','ivc-optica481-acciones');
                    panel.append(text('div', 'Primero abre «Ver receta» y revisa el documento o la graduación.', 'ivc-optica481-ayuda'));
                    const aprobar=text('button',d.estado_receta==='APROBADA'?'Validar micas':'Aprobar receta y micas','btn btn-sm btn-success');
                    aprobar.type='button';aprobar.disabled=true;aprobar.dataset.ivcRevisionAccion='APROBAR';
                    aprobar.title='Primero abre y revisa la receta';
                    aprobar.addEventListener('click',()=>revisarOptica(d,'APROBAR'));
                    panel.append(aprobar);
                    if(d.estado_receta==='PENDIENTE'){
                        const corregir=text('button','Solicitar corrección','btn btn-sm btn-outline-warning');
                        corregir.type='button';corregir.disabled=true;corregir.dataset.ivcRevisionAccion='CORRECCION';
                        corregir.title='Primero abre y revisa la receta';
                        corregir.addEventListener('click',()=>revisarOptica(d,'CORRECCION'));
                        panel.append(corregir);
                    }
                    action.append(panel);
                }
                if(pendiente && ['REQUIERE_CORRECCION','RECHAZADA'].includes(d.estado_receta)){
                    action.append(text('div','La receta requiere sustitución o corrección. Asocia una receta nueva antes de continuar.','alert alert-warning ivc-optica481-aviso'));
                }
                row.append(action);
                const visorLocal=row.querySelector('.ivc-pedidos-visor-receta');
                if(visorLocal) row.append(visorLocal);
            }
            products.append(row);
        }
        el.append(products);
        if(p.estado_valoracion&&p.estado_valoracion!=='REALIZADA'){
            const alert=text('div','Este pedido tiene una valoración pendiente. No puede pasar a preparación hasta completarla y validar su configuración óptica.','alert alert-warning');el.append(alert);
        }
        if(Number(p.opticas_pendientes)>0){el.append(text('div','Existen elementos ópticos pendientes. Abre la receta y utiliza «Aprobar receta y micas» desde el artículo correspondiente antes de avanzar.','alert alert-warning'));}
        const next=String(p.proceso)==='1'?'Iniciar preparación':String(p.proceso)==='2'?'Marcar finalizado':'';
        const btn=$('ivcPedAvanzar');btn.hidden=!next;btn.textContent=next;
        btn.disabled=Number(p.opticas_pendientes)>0||!!(p.estado_valoracion&&p.estado_valoracion!=='REALIZADA')||!estadoPago(p.estado);
        btn.title=btn.disabled?'Debes resolver las validaciones pendientes.':'';
    }
    async function verDetalle(id){modal.show();$('ivcPedDetalle').textContent='Consultando detalle…';$('ivcPedAvanzar').hidden=true;
        try{const res=await json('pedidos/detalle/'+encodeURIComponent(id));render(res.pedido);}catch(e){$('ivcPedDetalle').textContent=e.message;}}
    async function post(path, data){const params=new URLSearchParams({csrf,...data});return json(path,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:params.toString()});}
    async function confirmar(mensaje){if(window.Swal){const res=await Swal.fire({title:'Confirmar operación',text:mensaje,icon:'question',showCancelButton:true,confirmButtonText:'Confirmar',cancelButtonText:'Cancelar'});return res.isConfirmed;}return window.confirm(mensaje);}
    async function notificar(mensaje,ok){if(window.Swal){await Swal.fire(ok?'Correcto':'No se pudo completar',mensaje,ok?'success':'error');}else window.alert(mensaje);}
    async function capturarReceta(idDetalle,nombre){
        const content=document.createElement('form');content.className='ivc-rx48-form';
        const fields=[
            ['od_esfera','OD esfera','0.00'],['od_cilindro','OD cilindro','0.00'],
            ['od_eje','OD eje','0'],['od_adicion','OD adición','0.00'],
            ['oi_esfera','OI esfera','0.00'],['oi_cilindro','OI cilindro','0.00'],
            ['oi_eje','OI eje','0'],['oi_adicion','OI adición','0.00'],
            ['dp','Distancia pupilar total (mm)','62']];
        const grid=text('div','','ivc-rx48-grid');
        for(const [key,label,placeholder] of fields){
            const wrap=text('label',label,'form-label');const input=document.createElement('input');
            input.className='form-control';input.name=key;input.type='number';input.step=key.endsWith('eje')?'1':key==='dp'?'0.5':'0.25';
            input.placeholder=placeholder;input.required=true;wrap.append(input);grid.append(wrap);
        }
        content.append(grid);
        const nota=text('label','Observaciones clínicas (opcional)','form-label');
        const textarea=document.createElement('textarea');textarea.name='observaciones';textarea.maxLength=800;
        textarea.className='form-control';textarea.rows=2;nota.append(textarea);content.append(nota);
        if(!window.Swal){await notificar('Se requiere SweetAlert2 para capturar la graduación.',false);return;}
        const res=await Swal.fire({title:'Receta nueva · '+nombre,html:content,showCancelButton:true,
            width:'min(900px,95vw)',confirmButtonText:'Guardar receta pendiente',cancelButtonText:'Cancelar',
            preConfirm:()=>{
                const data=Object.fromEntries(new FormData(content).entries());
                for(const field of fields) if(data[field[0]]===''){Swal.showValidationMessage('Captura todos los campos de graduación.');return false;}
                return data;
            }});
        if(!res.isConfirmed||!res.value)return;
        try{
            const response=await post('pedidos/crearRecetaValoracion',{id_detalle:idDetalle,...res.value});
            await verDetalle(actual.id);await cargar();
            await notificar('Receta #'+response.id_receta+' registrada. Ábrela desde este pedido y utiliza «Aprobar receta y micas».',true);
        } catch(e){await notificar(e.message,false);}
    }
    async function accionValoracion(ruta,datos,mensaje){
        if(!await confirmar(mensaje))return;
        try{await post(ruta,datos);await verDetalle(actual.id);await cargar();await notificar('Información de valoración actualizada.',true);}
        catch(e){await notificar(e.message,false);}
    }
    async function revisarOptica(detalle,accion){
        const idPedido=actual?.id;
        if(!idPedido)return;
        let observaciones='';
        if(accion==='APROBAR'){
            const mensaje='Confirmo que revisé la receta #'+detalle.id_receta+' y que las micas de «'+detalle.producto+'» corresponden a la prescripción.';
            if(!await confirmar(mensaje))return;
        } else {
            if(window.Swal){
                const res=await Swal.fire({title:'Solicitar corrección de receta',
                    input:'textarea',inputLabel:'Motivo obligatorio',
                    inputPlaceholder:'Explica qué dato o documento debe corregirse…',
                    inputAttributes:{maxLength:1500},showCancelButton:true,
                    confirmButtonText:'Solicitar corrección',cancelButtonText:'Cancelar',
                    inputValidator:value=>!value?.trim()?'Escribe el motivo de la corrección.':undefined});
                if(!res.isConfirmed)return;observaciones=String(res.value||'').trim();
            }else{
                const nota=window.prompt('Motivo de corrección (obligatorio):','');
                if(nota===null)return;observaciones=nota.trim();
                if(!observaciones){await notificar('Debes escribir el motivo de corrección.',false);return;}
            }
        }
        try{
            const res=await post('pedidos/revisarOptica',{
                id_detalle:detalle.id,accion,observaciones,
                confirmacion:accion==='APROBAR'?'1':'0'
            });
            await verDetalle(idPedido);await cargar();
            await notificar(res.msg,true);
        }catch(e){await notificar(e.message,false);}
    }
    $('ivcPedAvanzar').addEventListener('click',async()=>{
        if(!actual)return;const id=actual.id;
        if(!await confirmar('¿Avanzar el pedido #'+id+' a la siguiente etapa?'))return;
        try{const result=await post('pedidos/avanzar',{id_pedido:id});await verDetalle(id);await cargar();await notificar('Pedido actualizado a '+proceso(result.proceso)+'.',true);}
        catch(e){await notificar(e.message,false);}
    });
    $('ivcPedBuscar').addEventListener('click',cargar);$('ivcPedRefresh').addEventListener('click',cargar);
    $('ivcPedFiltro').addEventListener('change',cargar);
    $('ivcPedBusqueda').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();cargar();}});
    cargar();
})();
