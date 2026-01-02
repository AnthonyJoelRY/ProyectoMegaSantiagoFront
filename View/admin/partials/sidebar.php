<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse min-vh-100 p-0 shadow-lg">
    <div class="position-sticky pt-4">
        <div class="d-flex align-items-center justify-content-center mb-4 pb-2 border-bottom border-light opacity-50 mx-3">
            <h4 class="text-white fw-bolder my-0">MegaSantiago</h4>
        </div>

        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link text-white rounded-2
   <?= ($seccionActiva ?? '') === 'dashboard' ? 'active' : '' ?>"
                    href="<?= PROJECT_BASE ?>/dashboard">
                    🏠 Dashboard
                </a>

            </li>

            <li class="nav-item">
                <a class="nav-link text-white rounded-2 d-flex align-items-center
   <?= ($seccionActiva ?? '') === 'productos' ? 'bg-white bg-opacity-10 border-start border-info border-4' : '' ?>"
                    href="<?= PROJECT_BASE ?>/panel/productos">
                    📦 Productos
                </a>


            </li>

            <li class="nav-item">
                <a class="nav-link text-white rounded-2 d-flex align-items-center
   <?= ($seccionActiva ?? '') === 'usuarios' ? 'bg-white bg-opacity-10 border-start border-info border-4' : '' ?>"
                    href="<?= PROJECT_BASE ?>/panel/usuarios">
                    👥 Usuarios
                </a>

            </li>

            <li class="nav-item">
                <a class="nav-link text-white rounded-2 d-flex align-items-center
   <?= ($seccionActiva ?? '') === 'pedidos' ? 'bg-white bg-opacity-10 border-start border-info border-4' : '' ?>"
                    href="<?= PROJECT_BASE ?>/panel/pedidos">
                    🛒 Pedidos
                </a>

            </li>

            <li class="nav-item">
                <a class="nav-link text-white rounded-2 d-flex align-items-center
   <?= ($seccionActiva ?? '') === 'reportes' ? 'bg-white bg-opacity-10 border-start border-info border-4' : '' ?>"
                    href="<?= PROJECT_BASE ?>/panel/reportes">
                    📈 Reportes
                </a>

            </li>
        </ul>

        <div class="px-3 mt-5">
            <a class="nav-link text-white bg-danger bg-opacity-75 rounded-3 p-2 text-center fw-semibold"
                href="<?= PROJECT_BASE ?>/index.html">
                ↩️ Volver al sitio
            </a>
        </div>
    </div>
</nav>