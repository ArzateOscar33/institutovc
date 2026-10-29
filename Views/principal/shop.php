<?php
/* Catálogo V4. Mantiene la cabecera y el pie compartidos de la versión editorial V3. */
include_once 'Views/template/header.php';

$ivcE = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$ivcIcono = static function (string $name, int $size = 20): string {
    $shapes = [
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/>',
        'heart' => '<path d="M20.8 8.6c0 4.2-8.8 10-8.8 10S3.2 12.8 3.2 8.6a4.7 4.7 0 0 1 8.8-2.1 4.7 4.7 0 0 1 8.8 2.1Z"/>',
        'bag' => '<path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'filter' => '<path d="M3 6h18M6 12h12m-9 6h6"/>',
        'chevron' => '<path d="m7 10 5 5 5-5"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($shapes[$name] ?? $shapes['arrow']) . '</svg>';
};

$filtros = $data['filtrosCatalogo'] ?? [
    'texto' => '', 'categoria' => 0, 'minimo' => null, 'maximo' => null,
    'disponible' => false, 'ordenar' => 'recientes',
];
$categorias = $data['categoriasCatalogo'] ?? [];
$productos = $data['productos'] ?? [];
$totalRegistros = (int) ($data['totalRegistros'] ?? count($productos));
$pagActual = max(1, (int) ($data['pagina'] ?? 1));
$totalPaginas = max(1, (int) ($data['total'] ?? 1));
$porPagina = (int) ($data['porPagina'] ?? 12);
$desde = $totalRegistros ? (($pagActual - 1) * $porPagina + 1) : 0;
$hasta = min($pagActual * $porPagina, $totalRegistros);

// Preserva todos los filtros y el ordenamiento cuando se navega entre páginas.
$ivcFiltrosURL = [];
if ($filtros['texto'] !== '') $ivcFiltrosURL['q'] = $filtros['texto'];
if ($filtros['categoria'] > 0) $ivcFiltrosURL['categoria'] = $filtros['categoria'];
if ($filtros['minimo'] !== null) $ivcFiltrosURL['min'] = $filtros['minimo'];
if ($filtros['maximo'] !== null) $ivcFiltrosURL['max'] = $filtros['maximo'];
if ($filtros['disponible']) $ivcFiltrosURL['disponible'] = 1;
if ($filtros['ordenar'] !== 'recientes') $ivcFiltrosURL['ordenar'] = $filtros['ordenar'];
$ivcShopURL = BASE_URL . 'principal/shop';
$ivcPaginaURL = static function (int $num) use ($ivcFiltrosURL, $ivcShopURL): string {
    $ruta = $ivcShopURL . ($num > 1 ? '/' . $num : '');
    $query = http_build_query($ivcFiltrosURL);
    return $ruta . ($query !== '' ? '?' . $query : '');
};
$nombreCategoria = '';
foreach ($categorias as $categoria) {
    if ((int) $categoria['id'] === (int) $filtros['categoria']) {
        $nombreCategoria = (string) $categoria['categoria'];
        break;
    }
}
$tieneFiltros = $filtros['texto'] !== '' || $filtros['categoria'] > 0 ||
    $filtros['minimo'] !== null || $filtros['maximo'] !== null || $filtros['disponible'];
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-catalogo.css?v=4.0.0">

<main class="ivc-shop" id="ivc-shop-content">
  <section class="ivc-shop-heading">
    <div class="container ivc-shop-heading-content">
      <div>
        <div class="ivc-shop-overline"><span class="ivc-shop-overline-line"></span> INSTITUTO VISUAL CALIFORNIA <span class="ivc-shop-overline-dot">·</span> NUESTRA COLECCIÓN</div>
        <h1>Una nueva forma de <em>ver tu estilo.</em></h1>
        <p>Encuentra el armazón que va contigo. Comodidad, diseño y una mirada que se siente propia.</p>
      </div>
      <div class="ivc-shop-heading-number" aria-hidden="true"><span>IVC /</span> COLLECTION <b>↗</b></div>
    </div>
  </section>

  <div class="container ivc-shop-main">
    <div class="ivc-shop-breadcrumb"><a href="<?php echo BASE_URL; ?>">Inicio</a><span>/</span><strong>Colección</strong></div>
    <div class="ivc-shop-layout">
      <aside class="ivc-shop-sidebar ivc-shop-enter" aria-label="Filtros del catálogo">
        <div class="ivc-shop-sidebar-intro"><span class="ivc-shop-overline">PERSONALIZA TU BÚSQUEDA</span><h2>Filtra tu <em>selección.</em></h2><p>Encuentra lo que buscas de forma sencilla.</p></div>
        <details class="ivc-shop-filter-details" open>
          <summary class="ivc-shop-mobile-filter-title"><?php echo $ivcIcono('filter'); ?> Filtros de búsqueda <?php echo $ivcIcono('chevron', 18); ?></summary>
          <form action="<?php echo $ivcE($ivcShopURL); ?>" method="get" id="ivcShopFilterForm" class="ivc-shop-filter-form">
            <div class="ivc-shop-filter-group">
              <label class="ivc-shop-label" for="ivcShopSearch">Buscar producto</label>
              <div class="ivc-shop-search-input"><input id="ivcShopSearch" name="q" type="search" maxlength="70" value="<?php echo $ivcE($filtros['texto']); ?>" placeholder="Nombre o modelo…" autocomplete="off"><?php echo $ivcIcono('search', 18); ?></div>
            </div>
            <div class="ivc-shop-filter-group">
              <label class="ivc-shop-label" for="ivcShopCategory">Categoría</label>
              <div class="ivc-shop-select-wrap"><select name="categoria" id="ivcShopCategory">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo (int) $categoria['id']; ?>" <?php echo (int) $filtros['categoria'] === (int) $categoria['id'] ? 'selected' : ''; ?>><?php echo $ivcE($categoria['categoria']); ?> (<?php echo (int) $categoria['total_productos']; ?>)</option>
                <?php endforeach; ?>
              </select><?php echo $ivcIcono('chevron', 18); ?></div>
            </div>
            <fieldset class="ivc-shop-filter-group ivc-shop-fieldset">
              <legend class="ivc-shop-label">Rango de precio <small>MXN</small></legend>
              <div class="ivc-shop-price-fields">
                <label><span>Desde</span><span class="ivc-shop-money-input"><span>$</span><input type="number" name="min" min="0" max="1000000" step="0.01" placeholder="0" value="<?php echo $filtros['minimo'] !== null ? $ivcE($filtros['minimo']) : ''; ?>" aria-label="Precio mínimo"></span></label>
                <span class="ivc-shop-price-dash" aria-hidden="true">—</span>
                <label><span>Hasta</span><span class="ivc-shop-money-input"><span>$</span><input type="number" name="max" min="0" max="1000000" step="0.01" placeholder="Máx." value="<?php echo $filtros['maximo'] !== null ? $ivcE($filtros['maximo']) : ''; ?>" aria-label="Precio máximo"></span></label>
              </div>
            </fieldset>
            <div class="ivc-shop-filter-group ivc-shop-stock-field">
              <label class="ivc-shop-checkbox"><input type="checkbox" name="disponible" value="1" <?php echo $filtros['disponible'] ? 'checked' : ''; ?>><span class="ivc-shop-checkbox-ui"></span><span>Solo disponibles</span></label>
              <span class="ivc-shop-stock-helper">Productos con existencias</span>
            </div>
            <input type="hidden" name="ordenar" value="<?php echo $ivcE($filtros['ordenar']); ?>">
            <button class="ivc-shop-apply" type="submit">Aplicar filtros <?php echo $ivcIcono('arrow', 19); ?></button>
            <a class="ivc-shop-clear" href="<?php echo $ivcE($ivcShopURL); ?>">Limpiar filtros <?php echo $ivcIcono('close', 14); ?></a>
          </form>
        </details>
        <div class="ivc-shop-sidebar-promo"><span>UNA MIRADA MÁS CLARA</span><strong>Tu siguiente estilo comienza aquí.</strong><a href="<?php echo BASE_URL; ?>principal/agendar">Agenda tu cita <span aria-hidden="true">↗</span></a></div>
      </aside>

      <section class="ivc-shop-results" aria-labelledby="ivcShopResultHeading">
        <div class="ivc-shop-toolbar ivc-shop-enter">
          <div><span class="ivc-shop-overline">LA COLECCIÓN</span><h2 id="ivcShopResultHeading"><?php echo $tieneFiltros ? 'Resultados de búsqueda' : 'Todos los armazones'; ?></h2><p><?php echo $totalRegistros > 0 ? $desde . '–' . $hasta . ' de ' : ''; ?><?php echo $totalRegistros; ?> <?php echo $totalRegistros === 1 ? 'producto' : 'productos'; ?></p></div>
          <form class="ivc-shop-sort-form" method="get" action="<?php echo $ivcE($ivcShopURL); ?>">
            <?php foreach ($ivcFiltrosURL as $clave => $valor): ?>
              <?php if ($clave !== 'ordenar'): ?><input type="hidden" name="<?php echo $ivcE($clave); ?>" value="<?php echo $ivcE($valor); ?>"><?php endif; ?>
            <?php endforeach; ?>
            <label for="ivcShopSort">Ordenar por</label>
            <div class="ivc-shop-select-wrap"><select id="ivcShopSort" name="ordenar" aria-label="Ordenar productos">
              <option value="recientes" <?php echo $filtros['ordenar'] === 'recientes' ? 'selected' : ''; ?>>Novedades</option>
              <option value="precio_asc" <?php echo $filtros['ordenar'] === 'precio_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
              <option value="precio_desc" <?php echo $filtros['ordenar'] === 'precio_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
              <option value="nombre_asc" <?php echo $filtros['ordenar'] === 'nombre_asc' ? 'selected' : ''; ?>>Nombre: A a Z</option>
            </select><?php echo $ivcIcono('chevron', 18); ?></div><button type="submit" class="ivc-shop-sort-button">Ordenar</button>
          </form>
        </div>
        <?php if ($tieneFiltros): ?>
          <div class="ivc-shop-active-filters" aria-label="Filtros aplicados">
            <?php if ($filtros['texto'] !== ''): ?><span>“<?php echo $ivcE($filtros['texto']); ?>”</span><?php endif; ?>
            <?php if ($nombreCategoria !== ''): ?><span><?php echo $ivcE($nombreCategoria); ?></span><?php endif; ?>
            <?php if ($filtros['minimo'] !== null): ?><span>Desde $<?php echo number_format((float)$filtros['minimo'], 2); ?></span><?php endif; ?>
            <?php if ($filtros['maximo'] !== null): ?><span>Hasta $<?php echo number_format((float)$filtros['maximo'], 2); ?></span><?php endif; ?>
            <?php if ($filtros['disponible']): ?><span>Solo disponibles</span><?php endif; ?>
            <a href="<?php echo $ivcE($ivcShopURL); ?>">Quitar filtros <?php echo $ivcIcono('close', 13); ?></a>
          </div>
        <?php endif; ?>

        <?php if (!empty($productos)): ?>
          <div class="ivc-shop-product-grid">
            <?php foreach ($productos as $producto):
              $id = (int) $producto['id'];
              $nombre = $ivcE($producto['nombre']);
              $descripcion = trim(html_entity_decode(strip_tags((string) $producto['descripcion']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
              $descripcion = function_exists('mb_substr') ? mb_substr($descripcion, 0, 95) : substr($descripcion, 0, 95);
              $categoria = $ivcE($producto['categoria'] ?? 'Colección');
              $enStock = (int) ($producto['cantidad'] ?? 0) > 0;
              $imagen = trim((string) ($producto['imagen'] ?? ''));
              $fallback = BASE_URL . 'assets/img/ivc-producto-sin-foto.svg';
              // La BD de demostración usa robohash.org para los productos ficticios.
              // Evitar mostrar robots como fotografías de armazones, sin alterar MySQL.
              if ($imagen === '' || stripos($imagen, 'robohash.org') !== false) {
                  $imagen = $fallback;
              } elseif (!preg_match('~^(?:https?://|//|data:|/)~i', $imagen)) {
                  $imagen = BASE_URL . ltrim($imagen, '/');
              }
              $detalleURL = BASE_URL . 'principal/detail/' . $id;
            ?>
            <article class="ivc-shop-card ivc-shop-enter" aria-label="<?php echo $nombre; ?>">
              <div class="ivc-shop-card-visual">
                <a href="<?php echo $ivcE($detalleURL); ?>" class="ivc-shop-card-image" tabindex="-1" aria-hidden="true"><img src="<?php echo $ivcE($imagen); ?>" alt="" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?php echo $ivcE($fallback); ?>';"></a>
                <div class="ivc-shop-card-pills"><span class="ivc-shop-card-collection">COLECCIÓN IVC</span><?php if (!$enStock): ?><span class="ivc-shop-card-soldout">AGOTADO</span><?php endif; ?></div>
                <div class="ivc-shop-card-actions">
                  <button type="button" class="ivc-shop-action btnAddDeseo" prod="<?php echo $id; ?>" title="Agregar a favoritos" aria-label="Agregar <?php echo $nombre; ?> a favoritos"><?php echo $ivcIcono('heart', 19); ?></button>
                  <?php if ($enStock): ?><button type="button" class="ivc-shop-action btnAddCarrito" prod="<?php echo $id; ?>" title="Agregar al carrito" aria-label="Agregar <?php echo $nombre; ?> al carrito"><?php echo $ivcIcono('bag', 19); ?></button><?php endif; ?>
                </div>
                <a class="ivc-shop-card-quick" href="<?php echo $ivcE($detalleURL); ?>">Descubrir modelo <?php echo $ivcIcono('arrow', 17); ?></a>
              </div>
              <div class="ivc-shop-card-info">
                <span class="ivc-shop-card-category"><?php echo $categoria; ?></span>
                <h3><a href="<?php echo $ivcE($detalleURL); ?>"><?php echo $nombre; ?></a></h3>
                <?php if ($descripcion !== ''): ?><p><?php echo $ivcE($descripcion); ?></p><?php endif; ?>
                <div class="ivc-shop-card-bottom"><strong>$<?php echo number_format((float)$producto['precio'], 2); ?> <small><?php echo $ivcE(MONEDA); ?></small></strong><a href="<?php echo $ivcE($detalleURL); ?>" aria-label="Ver detalles de <?php echo $nombre; ?>"><?php echo $ivcIcono('arrow', 19); ?></a></div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="ivc-shop-no-results"><div class="ivc-shop-no-results-icon"><?php echo $ivcIcono('search', 34); ?></div><h3>No encontramos resultados</h3><p>Prueba con otra categoría, cambia el precio o elimina alguno de los filtros.</p><a class="ivc-shop-apply" href="<?php echo $ivcE($ivcShopURL); ?>">Ver toda la colección <?php echo $ivcIcono('arrow', 18); ?></a></div>
        <?php endif; ?>

        <?php if ($totalPaginas > 1): ?>
          <nav class="ivc-shop-pagination" aria-label="Páginas de productos">
            <?php if ($pagActual > 1): ?><a class="ivc-shop-page-previous" href="<?php echo $ivcE($ivcPaginaURL($pagActual - 1)); ?>" aria-label="Página anterior">← <span>Anterior</span></a><?php endif; ?>
            <?php
              $paginasAMostrar = [1, $totalPaginas];
              for ($n = max(1, $pagActual - 2); $n <= min($totalPaginas, $pagActual + 2); $n++) $paginasAMostrar[] = $n;
              $paginasAMostrar = array_values(array_unique($paginasAMostrar));
              sort($paginasAMostrar);
              $ultimaPagina = 0;
              foreach ($paginasAMostrar as $n):
                if ($ultimaPagina > 0 && $n - $ultimaPagina > 1): ?><span class="ivc-shop-page-dots" aria-hidden="true">…</span><?php endif; ?>
                <a class="ivc-shop-page <?php echo $n === $pagActual ? 'is-current' : ''; ?>" href="<?php echo $ivcE($ivcPaginaURL($n)); ?>" <?php echo $n === $pagActual ? 'aria-current="page"' : ''; ?>><?php echo $n; ?></a>
                <?php $ultimaPagina = $n; endforeach; ?>
            <?php if ($pagActual < $totalPaginas): ?><a class="ivc-shop-page-next" href="<?php echo $ivcE($ivcPaginaURL($pagActual + 1)); ?>" aria-label="Página siguiente"><span>Siguiente</span> →</a><?php endif; ?>
          </nav>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>
<script defer src="<?php echo BASE_URL; ?>assets/js/ivc-catalogo.js?v=4.0.0"></script>
<?php include_once 'Views/template/footer.php'; ?>
