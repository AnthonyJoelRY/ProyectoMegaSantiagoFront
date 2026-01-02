<?php
/**
 * Model/DB/DBConnection.php
 *
 * Singleton para la conexión PDO.
 *
 * - Evita crear múltiples conexiones en cada request.
 * - No afecta a tus otros patrones (MVC/DAO/Service), porque se usa como infraestructura.
 */

class DBConnection
{
    /** @var PDO|null */
    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Retorna una instancia única de PDO.
     * @return PDO
     */
    public static function getInstance()
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        // Siempre incluye con __DIR__ para que no falle la ruta
        require __DIR__ . '/../Config/credenciales.php';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Opcional pero recomendado para MySQL
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return self::$instance;
        } catch (PDOException $e) {
            // Mantengo el mismo comportamiento de tu proyecto
            http_response_code(500);
            echo json_encode([
                'error'   => 'Error de conexión a la BD',
                'detalle' => $e->getMessage(),
            ]);
            exit;
        }
    }

    /**
     * Por si necesitas reiniciar la conexión (p.ej. pruebas o reconexión controlada).
     */
    public static function reset()
    {
        self::$instance = null;
    }
}
