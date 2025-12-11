import prod1 from "../../imagenes/Cuaderno_universitario_100_hojas.jpg";
import prod2 from "../../imagenes/Pack_12_lapices_HB.png";
import prod3 from "../../imagenes/Set_de_marcadores_permanentes.jpg";
import prod4 from "../../imagenes/Mochila_escolar_basica.jpg";
import oferta1 from "../../imagenes/Cuaderno_universitario_100_hojas.jpg";
import oferta2 from "../../imagenes/Pack_12_lapices_HB.png";
import oferta3 from "../../imagenes/Set_de_marcadores_permanentes.jpg";
import promo1 from "../../imagenes/Pack_escolar_completo.jpeg";
import promo2 from "../../imagenes/Kit_de_marcadores_surtidos.jpg";
import promo3 from "../../imagenes/Combo_hojas_carpetas.jpg";

const Home = () => {
    const irPromociones = () => {
        const el = document.getElementById("promociones");
        if (el) el.scrollIntoView({ behavior: "smooth" });
    };
    return (
        <>
            {/* BANNER */}
            <section className="banner-principal">
                <div className="banner-texto">
                    <h2>OFERTAS ESPECIALES MEGASANTIAGO</h2>
                    <p>Todo para tu oficina y tus clases al mejor precio.</p>
                    <button className="btn-primario" onClick={irPromociones}>
                        Ver promociones
                    </button>
                </div>
            </section>

            {/* PRODUCTOS MÁS VENDIDOS */}
            <section className="seccion">
                <h2 className="titulo-seccion">Productos más vendidos</h2>

                <div className="grid-categorias grid-best-sellers">

                    {/* PRODUCTO 1 */}
                    <article className="card-categoria">
                        <div className="circulo-img">
                            <img
                                src={prod1} 
                                alt="Cuaderno universitario 100 hojas"
                            />
                        </div>
                        <h3>Cuaderno universitario 100 hojas</h3>
                        <p className="best-price">$2,80 + IVA</p>
                        <button
                            className="btn-best"
                            data-id="PV001"
                            data-nombre="Cuaderno universitario 100 hojas"
                            data-precio="2.80"
                        >
                            Agregar al carrito
                        </button>
                    </article>

                    {/* PRODUCTO 2 */}
                    <article className="card-categoria">
                        <div className="circulo-img">
                            <img
                                src={prod2} 
                                alt="Pack 12 lápices HB"
                            />
                        </div>
                        <h3>Pack 12 lápices HB</h3>
                        <p className="best-price">$2,46 + IVA</p>
                        <button
                            className="btn-best"
                            data-id="PV002"
                            data-nombre="Pack 12 lápices HB"
                            data-precio="2.46"
                        >
                            Agregar al carrito
                        </button>
                    </article>

                    {/* PRODUCTO 3 */}
                    <article className="card-categoria">
                        <div className="circulo-img">
                            <img
                                src={prod3} 
                                alt="Set de marcadores permanentes"
                            />
                        </div>
                        <h3>Set de marcadores permanentes</h3>
                        <p className="best-price">$4,50 + IVA</p>
                        <button
                            className="btn-best"
                            data-id="PV003"
                            data-nombre="Set de marcadores permanentes"
                            data-precio="4.50"
                        >
                            Agregar al carrito
                        </button>
                    </article>

                    {/* PRODUCTO 4 */}
                    <article className="card-categoria">
                        <div className="circulo-img">
                            <img
                                src={prod4} 
                                alt="Mochila escolar básica"
                            />
                        </div>
                        <h3>Mochila escolar básica</h3>
                        <p className="best-price">$18,50 + IVA</p>
                        <button
                            className="btn-best"
                            data-id="PV004"
                            data-nombre="Mochila escolar básica"
                            data-precio="18.50"
                        >
                            Agregar al carrito
                        </button>
                    </article>

                </div>
            </section>

            {/* PRODUCTOS DESTACADOS */}
            <section className="seccion">
                <h2 className="titulo-seccion">Ofertas MegaSantiago</h2>

                <div className="carrusel-productos">

                    {/* PRODUCTO 1 */}
                    <article
                        className="card-producto"
                        data-id="P001"
                        data-nombre="Cuaderno universitario 100 hojas"
                        data-precio="2.80"
                    >
                        <span className="descuento">-20%</span>

                        <div className="img-producto">
                            <img
                                src={oferta1} 
                                alt="Cuaderno universitario 100 hojas"
                            />
                        </div>

                        <p className="nombre-producto">Cuaderno universitario 100 hojas</p>
                        <p className="precio-anterior">$3,50</p>
                        <p className="precio-actual">$2,80</p>
                        <button className="btn-add">+</button>
                    </article>

                    {/* PRODUCTO 2 */}
                    <article
                        className="card-producto"
                        data-id="P002"
                        data-nombre="Pack 12 lápices HB"
                        data-precio="2.46"
                    >
                        <span className="descuento">-15%</span>

                        <div className="img-producto">
                            <img
                                src={oferta2} 
                                alt="Pack 12 lápices HB"
                            />
                        </div>

                        <p className="nombre-producto">Pack 12 lápices HB</p>
                        <p className="precio-anterior">$2,90</p>
                        <p className="precio-actual">$2,46</p>
                        <button className="btn-add">+</button>
                    </article>

                    {/* PRODUCTO 3 */}
                    <article
                        className="card-producto"
                        data-id="P003"
                        data-nombre="Set de marcadores permanentes"
                        data-precio="4.50"
                    >
                        <span className="descuento">-10%</span>

                        <div className="img-producto">
                            <img
                                src={oferta3} 
                                alt="Set de marcadores permanentes"
                            />
                        </div>

                        <p className="nombre-producto">Set de marcadores permanentes</p>
                        <p className="precio-anterior">$5,00</p>
                        <p className="precio-actual">$4,50</p>
                        <button className="btn-add">+</button>
                    </article>

                </div>
            </section>

            {/* PROMOCIONES */}
            <section id="promociones" className="seccion">
                <h2 className="titulo-seccion">Promociones especiales</h2>

                <div className="carrusel-productos">

                    {/* PROMO 1 */}
                    <article
                        className="card-producto"
                        data-id="PR001"
                        data-nombre="Pack escolar completo"
                        data-precio="9.90"
                    >
                        <span className="descuento">-30%</span>
                        <div
                            className="img-producto"
                            style={{
                                backgroundImage: `url(${promo1})`,
                                backgroundSize: "cover",
                                backgroundPosition: "center"
                            }}
                        />
                        <p className="nombre-producto">Pack escolar completo</p>
                        <p className="precio-anterior">$14,20</p>
                        <p className="precio-actual">$9,90</p>
                        <button className="btn-add">+</button>
                    </article>

                    {/* PROMO 2 */}
                    <article
                        className="card-producto"
                        data-id="PR002"
                        data-nombre="Kit de marcadores surtidos"
                        data-precio="6.50"
                    >
                        <span className="descuento">-25%</span>
                        <div
                            className="img-producto"
                            style={{
                                backgroundImage: `url(${promo2})`,
                                backgroundSize: "cover",
                                backgroundPosition: "center"
                            }}
                        />
                        <p className="nombre-producto">Kit de marcadores surtidos</p>
                        <p className="precio-anterior">$8,70</p>
                        <p className="precio-actual">$6,50</p>
                        <button className="btn-add">+</button>
                    </article>

                    {/* PROMO 3 */}
                    <article
                        className="card-producto"
                        data-id="PR003"
                        data-nombre="Combo hojas + carpetas"
                        data-precio="4.75"
                    >
                        <span className="descuento">-20%</span>
                        <div
                            className="img-producto"
                            style={{
                                backgroundImage: `url(${promo3})`,
                                backgroundSize: "cover",
                                backgroundPosition: "center"
                            }}
                        />
                        <p className="nombre-producto">Combo hojas + carpetas</p>
                        <p className="precio-anterior">$5,95</p>
                        <p className="precio-actual">$4,75</p>
                        <button className="btn-add">+</button>
                    </article>

                </div>
            </section>
        </>
    )
}
export default Home;