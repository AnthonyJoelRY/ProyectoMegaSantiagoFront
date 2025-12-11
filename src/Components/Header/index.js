import logo from "../../imagenes/Logo_MegaSantiago.png";
const Header = () => {
    const realizarBusqueda = () => {
        console.log("Ejecutar búsqueda...");
        // aquí pones lo que quieras luego
    };
    return (

        <header className="main-header">

            <div className="header-left">
                <a href="../">
                    <img
                        src={logo} alt="MegaSantiago"
                        className="logo-mega"
                    />
                </a>
            </div>

            <div className="header-center">
                <div className="search-box">
                    <input id="buscador" type="text" placeholder="Busca tu producto" />

                    <button
                        className="btn-search"
                        type="button"
                        onClick={realizarBusqueda}
                    >
                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24">
                            <path d="M21 20l-5.8-5.8a7 7 0 10-1.4 1.4L20 21zM5 11a6 6 0 1112 0A6 6 0 015 11z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div className="header-right">

                <a href="../Login" className="link-header">Acceder / Registrarse</a>
                <a href="../Cart" className="link-header">🛒 Carrito</a>
            </div>
        </header>
    );
}
export default Header;