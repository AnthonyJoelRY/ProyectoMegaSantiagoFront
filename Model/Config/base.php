<?php
// Model/Config/base.php
// Calcula el "base path" del proyecto para que las rutas funcionen
// tanto en local como en hosting (InfinityFree), aunque el proyecto
// esté dentro de una carpeta (ej: /MegaSantiagoFront) o en la raíz.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!function_exists('project_base_url')) {
    function project_base_url(): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($script === '') return '';

        $lower = strtolower($script);

        foreach (['/panel/', '/view/', '/controller/', '/model/'] as $seg) {
            $pos = strpos($lower, $seg);
            if ($pos !== false) {
                return rtrim(substr($script, 0, $pos), '/');
            }
        }

        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        return $dir === '/' ? '' : $dir;
    }
}

if (!defined('PROJECT_BASE')) {
    define('PROJECT_BASE', project_base_url());
}
