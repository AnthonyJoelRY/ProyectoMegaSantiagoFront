
const NavPrincipal = () => {

    return (
        <nav className="nav-principal">
            <ul className="menu">
                <li className="menu-item has-mega">
                    <a href="/View/bazar.html">BAZAR ▾</a>
                </li>
                <li className="menu-item has-sub">
                    <a href="/View/papeleria.html">PAPELERÍA ▾</a>
                </li>
                <li className="menu-item has-sub">
                    <a href="/View/productos-arte.html">PRODUCTOS DE ARTE ▾</a>
                </li>
                <li className="menu-item has-mega">
                    <a href="/View/suministros-oficina.html">SUMINISTROS DE OFICINA ▾</a>
                </li>
                <li className="menu-item has-sub">
                    <a href="/View/utiles-escolares.html">ÚTILES ESCOLARES ▾</a>
                </li>
                <li className="menu-item">
                    <a href="#promociones">PROMOCIONES</a>
                </li>
            </ul>
        </nav>
    );
}
export default NavPrincipal;