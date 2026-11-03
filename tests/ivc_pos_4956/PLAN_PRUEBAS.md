# Plan de aceptación — IVC POS 4.9.5.6

**Reglas:** ejecutar en copia local XAMPP con **respaldo SQL completo y copia de archivos**. No utilizar tarjetas reales ni cobrar dos veces para probar errores. La comprobación automática de BD es de solo lectura; los procedimientos de esta lista SÍ producen registros cuando se completan desde el POS. Verificar cada caso y anotar resultado/folio/evidencia. No usar información clínica real de pacientes en pruebas.

| ID | Escenario / procedimiento | Resultado esperado | Resultado |
|---|---|---|---|
| C01 | VENDEDOR abre caja con fondo de $500 | Turno y responsable registrados | ☐ |
| C02 | Segundo vendedor intenta abrir la misma caja | Bloqueo, sin segundo turno | ☐ |
| C03 | Un vendedor intenta abrir otra caja sin cerrar turno | Bloqueo | ☐ |
| C04 | Ingreso $200, retiro $50, cierre con $650 | Arqueo exacto, diferencia $0 | ☐ |
| V01 | Venta de un armazón de prueba, efectivo total $850 y recibido $1,000 | Ticket $850, cambio $150, caja +$850, inventario -1 | ☐ |
| V02 | Venta tarjeta simulada *sin efectuar cargo bancario*, con referencia ficticia | Pedido/pago TARJETA, caja no crece; referencia registrada | ☐ |
| V03 | Pago mixto: $1,000 efectivo y $2,000 tarjeta ficticia | Un pedido, dos registros de pago, caja +$1,000 | ☐ |
| V04 | Venta de mostrador sin cuenta ni mica | Pedido sin `id_cliente`, producto descontado | ☐ |
| V05 | VENDEDOR intenta descuento sin permiso | HTTP 403/alerta, el servidor rechaza descuento | ☐ |
| V06 | ROOT aplica descuento autorizado y compra | Subtotal-descuento=total=pagos | ☐ |
| V07 | Recargar/repetir la MISMA solicitud de venta | Devuelve mismo folio, no hay segundo descuento de stock | ☐ |
| V08 | Solicitar mismo proveedor/referencia de tarjeta en venta distinta | Rechazo y conciliación, sin nuevo pedido | ☐ |
| I01 | Producto con 1 unidad, preparar dos ventas desde sesiones separadas | Solo una se confirma; la segunda rechaza stock | ☐ |
| I02 | Cliente web reserva producto y vendedor intenta vender ese stock | POS respeta disponible ya reservado | ☐ |
| I03 | Venta con dos renglones del mismo modelo | Stock descontado por suma de ambos, no por último renglón | ☐ |
| O01 | Cliente nuevo POS sin correo, venta con micas | Cliente creado y asociada receta pendiente | ☐ |
| O02 | Adjuntar receta administrativa a un pedido con micas pendientes | Se vincula al mismo pedido; no se cobra ni descuenta otra vez | ☐ |
| O03 | Vincular receta de cliente A a pedido de cliente B | Rechazo; receta ajena nunca queda asociada | ☐ |
| O04 | Cliente POS activa cuenta web con código presencial y correo | Mismo `id_cliente`, compras/recetas conservadas | ☐ |
| O05 | Código vencido, reutilizado o correo de otra persona | Rechazo; acceso no se concede | ☐ |
| D01 | Reimprimir ticket de venta | Sin cambios en pedidos/inventario/caja | ☐ |
| D02 | Venta de 2 armazones, devolver 1 sin reingreso | Reembolso correcto; stock no sube | ☐ |
| D03 | Segunda unidad de la misma venta, devolución con reingreso | Stock aumenta exactamente 1 tras inspección | ☐ |
| D04 | Intentar devolver más unidades de las vendidas | Rechazo | ☐ |
| D05 | Devolver renglón completo con micas | Estado óptico CANCELADA, no repone micas | ☐ |
| D06 | Reembolso tarjeta simulado: referencia ficticia y confirmación manual | No hay llamada bancaria automática; importe no mueve efectivo de caja | ☐ |
| D07 | Devolución en efectivo en turno diferente al de la venta | Resta al turno actual, no al histórico | ☐ |
| D08 | Repetir la MISMA solicitud de devolución | Mismo folio; inventario y caja no se alteran de nuevo | ☐ |
| P01 | VENDEDOR intenta consultar ventas ajenas | No puede verlas por URL ni API | ☐ |
| P02 | VENDEDOR sin `pos.devoluciones` intenta POST directo | HTTP 403 sin cambio en BD | ☐ |
| P03 | Sin sesión/CSRF, intentar POST de venta/devolución | Denegación, sin cambio en BD | ☐ |
| P04 | Eliminar permisos de un rol y recargar | Denegación por PHP y alerta | ☐ |
| R01 | Comparar ingresos netos POS vs ventas - devoluciones | Coinciden; no sumar tarjeta al efectivo | ☐ |
| R02 | Ejecutar auditoría de BD `auditoria_bd.php` | Cero inconsistencias, no altera datos | ☐ |
| B01 | Revisar diseño e impresión de ticket 80 mm | Sin recortes, números legibles, datos clínicos ausentes | ☐ |
| B02 | Caja cerrada: intentar realizar venta/devolución | Bloqueada, sin nuevas operaciones | ☐ |

## Criterios de salida

- **Bloqueantes:** cantidades de inventario incorrectas; doble cobro lógico; venta duplicada; venta de receta ajena; modificación no autorizada; suma de pagos != total; arqueo errado; reembolsos excedidos; información clínica expuesta.
- **Condición de aprobación:** todos los casos C/V/I/O/D/P/R/B que apliquen ejecutados, **cero bloqueantes**, auditorías de lectura sin inconsistencias, evidencias archivadas y autorización de despliegue.
- Las pruebas con tarjeta física son **manuales**, sin API. No se puede asegurar aprobación bancaria automática. Solo utilizar referencias simuladas para QA, nunca confirmarlas como transacciones reales fuera del entorno de prueba.
- Registrar fecha, usuario de prueba, producto y folios; **no anotar contraseñas, tokens, datos bancarios ni datos de salud en evidencias**.
