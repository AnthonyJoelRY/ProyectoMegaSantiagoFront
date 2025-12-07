// Buscar desde cualquier página
function realizarBusquedaGlobal() {
    const input = document.getElementById('buscador');
    if (!input) return;

    const termino = input.value.trim();
    if (!termino) return;

    // Ruta actual normalizada
    const path = window.location.pathname.replace(/\\/g, '/');

    // Si la URL contiene "/View/" significa que ya estoy dentro de la carpeta View
    const estoyEnView = path.includes('/View/');

    // Desde index.html (raíz) necesito "View/busqueda.html"
    // Desde cualquier página dentro de View necesito solo "busqueda.html"
    const prefijo = estoyEnView ? '' : 'View/';

    // Redirigir a la página de resultados
    window.location.href = prefijo + 'busqueda.html?q=' + encodeURIComponent(termino);
}

// Habilitar búsqueda con Enter
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('buscador');
    if (input) {
        input.addEventListener('keyup', function (e) {
            if (e.key === 'Enter') {
                realizarBusquedaGlobal();
            }
        });
    }
});
