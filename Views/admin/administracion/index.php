<?php include_once 'Views/template/header-admin.php';
$ivcD = $data['dashboard'] ?? [];
$ivcH = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$ivcN = static function ($v) { return number_format((float) $v, 0, '.', ','); };
$ivcMoneda = static function ($v) { return '$' . number_format((float) $v, 2, '.', ','); };
$ivcEstados = ['1' => 'Pendiente', '2' => 'En preparación', '3' => 'Finalizado'];
$ivcChart = [
    'meses' => $ivcD['meses'] ?? [],
    'ventas' => $ivcD['ventas_mensuales'] ?? [],
    'etapas' => $ivcD['pedidos_por_estado'] ?? [0,0,0],
    'productos' => array_map(static function ($p) { return (string)$p['producto']; }, $ivcD['top_productos'] ?? []),
    'unidades' => array_map(static function ($p) { return (int)$p['total']; }, $ivcD['top_productos'] ?? []),
    'categorias' => array_map(static function ($p) { return (string)$p['categoria']; }, $ivcD['productos_categoria'] ?? []),
    'conteoCategorias' => array_map(static function ($p) { return (int)$p['total']; }, $ivcD['productos_categoria'] ?? [])
]; ?>
<section class="ivc-admin-e1 ivc-dashboard-e1" aria-label="Resumen del negocio">
    <div class="ivc-page-hero">
        <div>
            <span class="ivc-eyebrow"><span class="ivc-pulse"></span> VISTA GENERAL</span>
            <h1>Panel de control</h1>
            <p>Información actualizada sobre pedidos, ventas, inventario y atención visual.</p>
        </div>
        <div class="ivc-hero-actions">
            <span class="ivc-date-badge"><i class="bx bx-calendar"></i> <?php echo $ivcH(date('d/m/Y')); ?></span>
            <a class="btn btn-light ivc-hero-button" href="<?php echo BASE_URL; ?>pedidos"><i class="bx bx-receipt"></i> Ver pedidos</a>
        </div>
    </div>
    <div class="ivc-stats-grid">
        <article class="ivc-stat-card ivc-stat-blue">
            <div class="ivc-stat-icon"><i class="bx bx-wallet"></i></div>
            <span class="ivc-stat-label">Ventas registradas · 12 meses</span>
            <strong><?php echo $ivcMoneda($ivcD['ventas_periodo'] ?? 0); ?></strong>
            <span class="ivc-stat-note">MXN · Pedidos completados</span>
        </article>
        <article class="ivc-stat-card ivc-stat-indigo">
            <div class="ivc-stat-icon"><i class="bx bx-shopping-bag"></i></div>
            <span class="ivc-stat-label">Pedidos totales</span>
            <strong><?php echo $ivcN($ivcD['total_pedidos'] ?? 0); ?></strong>
            <span class="ivc-stat-note">Todas las etapas</span>
        </article>
        <article class="ivc-stat-card ivc-stat-cyan">
            <div class="ivc-stat-icon"><i class="bx bx-glasses"></i></div>
            <span class="ivc-stat-label">Productos activos</span>
            <strong><?php echo $ivcN($ivcD['productos_activos'] ?? 0); ?></strong>
            <span class="ivc-stat-note"><?php echo $ivcN($ivcD['categorias_activas'] ?? 0); ?> categorías activas</span>
        </article>
        <article class="ivc-stat-card ivc-stat-amber">
            <div class="ivc-stat-icon"><i class="bx bx-error-circle"></i></div>
            <span class="ivc-stat-label">Productos con existencias bajas</span>
            <strong><?php echo $ivcN($ivcD['existencias_bajas'] ?? 0); ?></strong>
            <span class="ivc-stat-note">5 unidades o menos, incluye agotados</span>
        </article>
    </div>
    <div class="ivc-mini-grid">
        <a class="ivc-mini-card" href="<?php echo BASE_URL; ?>recetasAdmin"><i class="bx bx-file"></i><div><strong><?php echo $ivcN($ivcD['recetas_pendientes'] ?? 0); ?></strong><span>Recetas pendientes</span></div><i class="bx bx-chevron-right"></i></a>
        <a class="ivc-mini-card" href="<?php echo BASE_URL; ?>citas"><i class="bx bx-calendar-check"></i><div><strong><?php echo $ivcN($ivcD['citas_proximas'] ?? 0); ?></strong><span>Citas desde hoy</span></div><i class="bx bx-chevron-right"></i></a>
        <div class="ivc-mini-card"><i class="bx bx-user-check"></i><div><strong><?php echo $ivcN($ivcD['clientes'] ?? 0); ?></strong><span>Clientes registrados</span></div></div>
    </div>
    <div class="row g-4 ivc-dash-charts">
        <div class="col-12 col-xl-8">
            <article class="ivc-panel"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">DESEMPEÑO</span><h2>Ventas por mes</h2><p>Importe de pedidos completados · últimos 12 meses</p></div><span class="ivc-soft-pill">MXN</span></div><div class="ivc-chart-holder ivc-chart-wide"><canvas id="ivcVentasMensuales" role="img" aria-label="Ventas por mes"></canvas></div></article>
        </div>
        <div class="col-12 col-xl-4">
            <article class="ivc-panel"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">OPERACIÓN</span><h2>Estado de pedidos</h2><p>Distribución por etapa</p></div></div><div class="ivc-chart-holder ivc-chart-donut"><canvas id="ivcEstadosPedidos" role="img" aria-label="Estado de pedidos"></canvas></div></article>
        </div>
        <div class="col-12 col-xl-6">
            <article class="ivc-panel"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">VENTAS</span><h2>Productos más vendidos</h2><p>Unidades en pedidos completados</p></div></div><div class="ivc-chart-holder"><canvas id="ivcTopProductos" role="img" aria-label="Productos más vendidos"></canvas></div></article>
        </div>
        <div class="col-12 col-xl-6">
            <article class="ivc-panel"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">CATÁLOGO</span><h2>Productos por categoría</h2><p>Productos activos en las principales categorías</p></div></div><div class="ivc-chart-holder"><canvas id="ivcCategorias" role="img" aria-label="Productos por categoría"></canvas></div></article>
        </div>
    </div>
    <div class="row g-4 mt-0">
        <div class="col-12 col-xl-7">
            <article class="ivc-panel ivc-panel-table"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">ACTIVIDAD</span><h2>Pedidos recientes</h2></div><a href="<?php echo BASE_URL; ?>pedidos" class="ivc-link">Ver todos <i class="bx bx-right-arrow-alt"></i></a></div>
                <div class="table-responsive"><table class="table ivc-data-table"><thead><tr><th>Pedido</th><th>Fecha</th><th>Importe</th><th>Etapa</th></tr></thead><tbody>
                <?php foreach ($ivcD['pedidos_recientes'] ?? [] as $p): ?>
                  <tr><td><strong>#<?php echo (int)$p['id']; ?></strong></td><td><?php echo $ivcH($p['fecha']); ?></td><td><?php echo $ivcMoneda($p['monto']); ?></td><td><span class="ivc-state ivc-state-<?php echo (int)$p['proceso']; ?>"><?php echo $ivcH($ivcEstados[(string)$p['proceso']] ?? 'Sin clasificar'); ?></span></td></tr>
                <?php endforeach; ?>
                <?php if (empty($ivcD['pedidos_recientes'])): ?><tr><td colspan="4" class="text-center py-4">Todavía no hay pedidos registrados.</td></tr><?php endif; ?>
                </tbody></table></div>
            </article>
        </div>
        <div class="col-12 col-xl-5">
            <article class="ivc-panel ivc-panel-table"><div class="ivc-panel-head"><div><span class="ivc-eyebrow">INVENTARIO</span><h2>Atención de existencias</h2></div><a href="<?php echo BASE_URL; ?>productos" class="ivc-link">Ir a productos <i class="bx bx-right-arrow-alt"></i></a></div>
                <div class="table-responsive"><table class="table ivc-data-table"><thead><tr><th>Producto</th><th class="text-end">Disponible</th></tr></thead><tbody>
                <?php foreach ($ivcD['inventario_bajo'] ?? [] as $p): ?>
                  <tr><td><?php echo $ivcH($p['nombre']); ?></td><td class="text-end"><span class="ivc-inventory-tag <?php echo (int)$p['cantidad'] <= 0 ? 'ivc-is-empty' : ''; ?>"><?php echo (int)$p['cantidad']; ?> ud.</span></td></tr>
                <?php endforeach; ?>
                <?php if (empty($ivcD['inventario_bajo'])): ?><tr><td colspan="2" class="text-center py-4">No hay alertas de inventario.</td></tr><?php endif; ?>
                </tbody></table></div>
            </article>
        </div>
    </div>
    <p class="ivc-dashboard-disclaimer"><i class="bx bx-info-circle"></i> Los importes reflejan pedidos registrados en el sistema, incluidos los de pruebas. No equivalen a una conciliación bancaria.</p>
</section>
<script id="ivcDashboardData" type="application/json"><?php echo json_encode($ivcChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-admin-dashboard-e1.js?v=4911"></script>
</body></html>
