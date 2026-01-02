<?php require_once __DIR__ . "/../../Model/Config/base.php"; ?>
<nav class="nav-principal">
  <ul class="menu">

    <!-- ✅ BAZAR (MEGA MENU) - CORREGIDO -->
    <li class="menu-item has-mega">
      <a data-route="bazar.html" href="<?= PROJECT_BASE ?>/View/pages/bazar.html">BAZAR ▾</a>

      <div class="mega-menu columnas-3">

        <div class="col-mega">
          <h4>ACCESORIOS</h4>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#globos">Globos</a>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#decoracion">Decoración fiestas</a>
        </div>

        <div class="col-mega">
          <h4>HOGAR &amp; FAMILIA</h4>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#organizadores">Organizadores</a>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#velas">Velas</a>
        </div>

        <div class="col-mega">
          <h4>VARIOS</h4>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#regalos">Regalos</a>
          <a href="<?= PROJECT_BASE ?>/View/pages/bazar.html#detalles">Detalles</a>
        </div>

      </div>
    </li>

    <!-- ✅ PAPELERÍA (SUBMENU SIMPLE) -->
    <li class="menu-item has-sub">
      <a data-route="papeleria.html" href="<?= PROJECT_BASE ?>/View/pages/papeleria.html">PAPELERÍA ▾</a>
      <ul class="submenu">
        <li><a href="<?= PROJECT_BASE ?>/View/pages/papeleria.html#cartulinas">Cartulinas</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/papeleria.html#papeles">Papeles especiales</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/papeleria.html#cuadernos">Cuadernos</a></li>
      </ul>
    </li>

    <!-- ✅ PRODUCTOS DE ARTE (SUBMENU SIMPLE) -->
    <li class="menu-item has-sub">
      <a data-route="productos-arte.html" href="<?= PROJECT_BASE ?>/View/pages/productos-arte.html">PRODUCTOS DE ARTE ▾</a>
      <ul class="submenu">
        <li><a href="<?= PROJECT_BASE ?>/View/pages/productos-arte.html#pinturas">Pinturas</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/productos-arte.html#pinceles">Pinceles</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/productos-arte.html#lienzos">Lienzos</a></li>
      </ul>
    </li>

    <!-- ✅ SUMINISTROS DE OFICINA (SUBMENU) -->
    <li class="menu-item has-sub">
      <a data-route="suministros-oficina.html" href="<?= PROJECT_BASE ?>/View/pages/suministros-oficina.html">SUMINISTROS DE OFICINA ▾</a>
      <ul class="submenu">
        <li><a href="<?= PROJECT_BASE ?>/View/pages/suministros-oficina.html#archivadores">Archivadores</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/suministros-oficina.html#grapadoras">Grapadoras</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/suministros-oficina.html#papeleria">Papelería oficina</a></li>
      </ul>
    </li>

    <!-- ✅ ÚTILES ESCOLARES (SUBMENU SIMPLE) -->
    <li class="menu-item has-sub">
      <a data-route="utiles-escolares.html" href="<?= PROJECT_BASE ?>/View/pages/utiles-escolares.html">ÚTILES ESCOLARES ▾</a>
      <ul class="submenu">
        <li><a href="<?= PROJECT_BASE ?>/View/pages/utiles-escolares.html#mochilas">Mochilas</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/utiles-escolares.html#escritura">Escritura</a></li>
        <li><a href="<?= PROJECT_BASE ?>/View/pages/utiles-escolares.html#geometria">Geometría</a></li>
      </ul>
    </li>

    <li class="menu-item">
      <a href="<?= PROJECT_BASE ?>/index.html#promociones">PROMOCIONES</a>
    </li>

  </ul>
</nav>
