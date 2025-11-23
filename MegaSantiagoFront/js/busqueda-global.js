// Buscar desde cualquier página
function realizarBusquedaGlobal() {
    const input = document.getElementById('buscador');
    if (!input) return;
    const termino = input.value.trim();
    if (!termino) return;

    // Redirigir a la página de resultados
    window.location.href = '../busqueda.html?q=' + encodeURIComponent(termino);
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
