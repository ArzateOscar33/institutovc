(()=>{'use strict';
const app=document.getElementById('pvPostventa');if(!app)return;
const base=app.dataset.base, csrf=app.dataset.csrf, puedeDevolver=app.dataset.devolver==='1';
const el=id=>document.getElementById(id);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=cents=>(cents/100).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
const cents=s=>{const m=String(s??'').match(/^(\d+)(?:\.(\d{1,2}))?$/);return m?Number(m[1])*100+Number((m[2]??'').padEnd(2,'0')):NaN;};
const cstr=c=>`${Math.floor(c/100)}.${String(c%100).padStart(2,'0')}`;
const fechaHoy=()=>{const d=new Date();const parts=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Tijuana',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(d);const x=Object.fromEntries(parts.map(p=>[p.type,p.value]));return `${x.year}-${x.month}-${x.day}`;};
function aviso(msg,icon='info'){if(window.Swal?.fire)Swal.fire({icon,title:icon==='error'?'No se pudo completar':'Aviso',text:msg,confirmButtonColor:'#1568a6'});else alert(msg);}
async function api(path,params=null){const opt={credentials:'same-origin',headers:{Accept:'application/json'}};
 if(params){opt.method='POST';const fd=new FormData();fd.set('csrf',csrf);for(const[k,v]of Object.entries(params))fd.set(k,v);opt.body=fd;}
 const r=await fetch(base+'pos/'+path,opt);let x;try{x=await r.json();}catch{throw new Error('Respuesta inesperada del servidor.');}if(!r.ok||!x.ok)throw new Error(x.msg||'Operación rechazada.');return x.datos;}
let pagina=1,seleccionado=null,solicitud=null,guardando=false,detalle={};
const hoy=fechaHoy();el('pvpHasta').value=hoy;const d=new Date(hoy+'T12:00:00');d.setDate(d.getDate()-30);el('pvpDesde').value=[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
async function buscar(){el('pvpEstado').textContent='Consultando…';
 const q=new URLSearchParams({desde:el('pvpDesde').value,hasta:el('pvpHasta').value,q:el('pvpQ').value.trim(),pagina:String(pagina)});
 try{const x=await api('historialdatos?'+q);el('pvpVentas').innerHTML=x.ventas.length?x.ventas.map(v=>`<tr><td><strong>#${Number(v.id_venta)}</strong><br><small>Pedido #${Number(v.id_pedido)}</small></td><td>${esc(v.creada_en)}</td><td>${esc(v.cliente)}</td><td>${esc(v.vendedor)}<br><small>${esc(v.caja)}</small></td><td>${money(cents(v.total))}</td><td>${money(cents(v.reembolsado))}</td><td><span class="pvp-chip">${esc(v.estado)}</span></td><td><button class="pvp-btn pvp-secondary" type="button" data-detalle="${Number(v.id_venta)}">Detalle</button> <a class="pvp-btn pvp-secondary" href="${esc(base)}pos/ticket?id=${Number(v.id_venta)}" target="_blank" rel="noopener">Ticket</a></td></tr>`).join(''):'<tr><td colspan="8">No hay ventas para esos filtros.</td></tr>';
 el('pvpEstado').textContent=x.ventas.length+' venta(s)';el('pvpPagina').textContent='Página '+pagina;el('pvpAnterior').disabled=pagina===1;el('pvpSiguiente').disabled=!x.hay_mas;
 }catch(e){el('pvpEstado').textContent='Error';aviso(e.message,'error');}}
function unidadesDev(l){return Math.max(0,Number(l.disponibles_devolucion));}
function calcTotal(){let total=0;for(const tr of el('pvpDevolverLineas').querySelectorAll('tr[data-id]')){
 const id=Number(tr.dataset.id),l=detalle.lineas.find(l=>Number(l.id)===id);if(!l)continue;
 const qty=Number(tr.querySelector('.pvp-qty').value);if(!Number.isInteger(qty)||qty<0||qty>unidadesDev(l))continue;
 const n=Number(l.cantidad),net=cents(l.neto_linea),prev=Number(l.devueltas);
 const acum=k=>Math.floor((net*k+Math.floor(n/2))/n);
 total+=acum(prev+qty)-acum(prev);
 }el('pvpTotalDev').textContent=money(total);el('pvpConfirmar').disabled=total<=0;return total;
}
function lineasElegidas(){const resultado=[];for(const tr of el('pvpDevolverLineas').querySelectorAll('tr[data-id]')){
 const cantidad=Number(tr.querySelector('.pvp-qty').value);const l=detalle.lineas.find(l=>Number(l.id)===Number(tr.dataset.id));
 if(cantidad>0){if(!Number.isInteger(cantidad)||cantidad>unidadesDev(l))throw new Error('Cantidad inválida.');
 if(l.nombre_mica_snapshot && cantidad!==unidadesDev(l))throw new Error('Las micas se devuelven por renglón completo.');
 resultado.push({id:Number(tr.dataset.id),cantidad,reingresa_stock:tr.querySelector('.pvp-restock').checked});}}
 return resultado;}
function tarjetaCampos(){el('pvpTarjetaCampos').hidden=!(cents(el('pvpTarjeta').value)>0);}
async function cargarDetalle(id){try{
 const x=await api('detalleventa?id='+id);detalle=x;seleccionado=id;solicitud=null;
 el('pvpDetalleSeccion').hidden=false;el('pvpDetalleTitulo').textContent='Venta #'+id+' · Pedido #'+x.venta.id_pedido;
 el('pvpDetalle').innerHTML=`<div class="pvp-metrics"><div><small>Venta original</small><strong>${money(cents(x.venta.total))}</strong></div><div><small>Reembolsado</small><strong>${money(cents(x.reembolsado))}</strong></div><div><small>Importe restante</small><strong>${money(cents(x.pendiente))}</strong></div></div><p><strong>Cliente:</strong> ${esc(x.venta.cliente_nombre)} ${esc(x.venta.cliente_apellido)} · <strong>Fecha:</strong> ${esc(x.venta.creada_en)} · <strong>Estado:</strong> ${esc(x.venta.estado)}</p><div class="pvp-scroll"><table class="pvp-table"><thead><tr><th>Producto</th><th>Cantidad vendida</th><th>Mica</th><th>Precio bruto por unidad</th><th>Importe neto de línea</th><th>Devueltas</th></tr></thead><tbody>${x.lineas.map(l=>`<tr><td>${esc(l.producto)}</td><td>${Number(l.cantidad)}</td><td>${esc(l.nombre_mica_snapshot||'Sin graduación')}</td><td>${money(cents(l.precio)+cents(l.precio_unitario_mica||'0'))}</td><td>${money(cents(l.neto_linea))}</td><td>${Number(l.devueltas)}</td></tr>`).join('')}</tbody></table></div><p><strong>Pagos originales:</strong> ${x.pagos.map(p=>esc(p.metodo)+' '+money(cents(p.importe))+(p.proveedor?' ('+esc(p.proveedor)+')':'')).join(' · ')}</p><p><a class="pvp-btn pvp-secondary" target="_blank" rel="noopener" href="${esc(base)}pos/ticket?id=${id}">Imprimir ticket</a></p><h3>Devoluciones realizadas</h3>${x.devoluciones.length?x.devoluciones.map(z=>`<p>#${Number(z.id_devolucion)} · ${esc(z.creada_en)} · ${money(cents(z.total))} · ${esc(z.motivo)} · <a target="_blank" rel="noopener" href="${esc(base)}pos/comprobantedevolucion?id=${Number(z.id_devolucion)}">Comprobante</a></p>`).join(''):'<p>No se han registrado devoluciones.</p>'}`;
 if(puedeDevolver){el('pvpDevolucion').hidden=cents(x.pendiente)===0||!x.lineas.some(l=>unidadesDev(l)>0);
 el('pvpDevolverLineas').innerHTML=x.lineas.filter(l=>unidadesDev(l)>0).map(l=>`<tr data-id="${Number(l.id)}"><td>${esc(l.producto)}${l.nombre_mica_snapshot?'<br><small>Micas: '+esc(l.nombre_mica_snapshot)+' · devolver renglón completo</small>':''}</td><td>${unidadesDev(l)}</td><td><input aria-label="Cantidad a devolver" type="number" class="pvp-qty" min="0" max="${unidadesDev(l)}" step="${l.nombre_mica_snapshot?unidadesDev(l):1}" value="0" ${l.nombre_mica_snapshot?'data-optica="1"':''}></td><td><input type="checkbox" class="pvp-restock" title="Solo si el armazón fue recibido e inspeccionado"></td></tr>`).join('');
 el('pvpMotivo').value='';el('pvpEfectivo').value='0.00';el('pvpTarjeta').value='0.00';el('pvpReferencia').value='';el('pvpTarjetaConfirmada').checked=false;
 el('pvpMetodoDisponible').textContent='Máximo en efectivo: '+money(cents(x.disponible_efectivo))+' · Máximo a tarjeta: '+money(cents(x.disponible_tarjeta));
 el('pvpProveedor').value=x.pagos.find(p=>p.metodo==='TARJETA')?.proveedor||'';
 tarjetaCampos();calcTotal();}
 el('pvpDetalleSeccion').scrollIntoView({behavior:'smooth',block:'start'});
 }catch(e){aviso(e.message,'error');}}
el('pvpFiltros').addEventListener('submit',e=>{e.preventDefault();pagina=1;buscar();});
el('pvpAnterior').addEventListener('click',()=>{if(pagina>1){pagina--;buscar();}});
el('pvpSiguiente').addEventListener('click',()=>{pagina++;buscar();});
el('pvpVentas').addEventListener('click',e=>{const b=e.target.closest('[data-detalle]');if(b)cargarDetalle(Number(b.dataset.detalle));});
el('pvpCerrarDetalle').addEventListener('click',()=>{el('pvpDetalleSeccion').hidden=true;seleccionado=null;});
if(puedeDevolver){
 el('pvpDevolverLineas').addEventListener('input',e=>{if(e.target.classList.contains('pvp-qty')&&e.target.dataset.optica){const q=Number(e.target.value),max=Number(e.target.max);if(q>0&&q<max)e.target.value=max;}calcTotal();});
 el('pvpTarjeta').addEventListener('input',tarjetaCampos);
 el('pvpProponer').addEventListener('click',()=>{const total=calcTotal(),cash=Math.min(total,cents(detalle.disponible_efectivo));el('pvpEfectivo').value=cstr(cash);el('pvpTarjeta').value=cstr(Math.max(0,total-cash));tarjetaCampos();});
 el('pvpDevolverForm').addEventListener('submit',async e=>{e.preventDefault();if(guardando)return;
 let datos;try{
 const lineas=lineasElegidas(),total=calcTotal();if(lineas.length===0||total<1)throw new Error('Selecciona artículos para devolver.');
 const efectivo=cents(el('pvpEfectivo').value),tarjeta=cents(el('pvpTarjeta').value);
 if(!Number.isInteger(efectivo)||!Number.isInteger(tarjeta)||efectivo<0||tarjeta<0||efectivo+tarjeta!==total)throw new Error('La distribución debe coincidir exactamente con el importe calculado.');
 if(efectivo>cents(detalle.disponible_efectivo)||tarjeta>cents(detalle.disponible_tarjeta))throw new Error('La distribución supera los cobros originales disponibles.');
 if(tarjeta>0&&(!el('pvpTarjetaConfirmada').checked||el('pvpReferencia').value.trim().length<4))throw new Error('Confirma el reembolso aprobado en terminal e introduce su referencia.');
 if(el('pvpMotivo').value.trim().length<8)throw new Error('Indica el motivo de devolución.');
 if(!solicitud)solicitud=Array.from(crypto.getRandomValues(new Uint8Array(16)),x=>x.toString(16).padStart(2,'0')).join('');
 datos={solicitud,id_venta:seleccionado,lineas,motivo:el('pvpMotivo').value.trim(),efectivo:cstr(efectivo),tarjeta:cstr(tarjeta),proveedor:tarjeta>0?el('pvpProveedor').value.trim():'',referencia_reembolso:tarjeta>0?el('pvpReferencia').value.trim():'',tarjeta_confirmada:tarjeta>0?'1':'0'};
 }catch(err){aviso(err.message,'error');return;}
 const confirmar=window.Swal?.fire?await Swal.fire({icon:'warning',title:'Confirmar devolución',text:`Se registrará un reembolso de ${money(calcTotal())}. Confirma que cualquier reembolso a tarjeta YA fue procesado en terminal. Esta acción no puede deshacerse.`,showCancelButton:true,confirmButtonText:'Confirmar',cancelButtonText:'Cancelar',confirmButtonColor:'#bc465a'}).then(x=>x.isConfirmed):confirm('¿Registrar definitivamente esta devolución?');
 if(!confirmar)return;
 guardando=true;el('pvpConfirmar').disabled=true;
 try{const x=await api('devolver',{datos:JSON.stringify(datos)});solicitud=null;aviso(`Devolución #${x.id_devolucion} registrada correctamente.`,'success');await cargarDetalle(seleccionado);await buscar();window.open(base+'pos/comprobantedevolucion?id='+x.id_devolucion,'_blank','noopener');}
 catch(err){aviso(err.message+' Si ya se devolvió dinero en terminal, revisa el historial antes de intentar nuevamente.','error');}
 finally{guardando=false;calcTotal();}
 });
}
buscar();
})();
