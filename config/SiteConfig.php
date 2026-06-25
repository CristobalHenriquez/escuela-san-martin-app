<?php
/**
 * Clase helper SiteConfig
 * Provee métodos estáticos utilizados por el admin y el front
 */
class SiteConfig
{
    // Devuelve la URL base del sitio (sin barra final)
    public static function getBaseDir()
    {
        if (function_exists('obtenerUrlCompleta')) {
            return rtrim(obtenerUrlCompleta(), '/');
        }
        // Fallback al valor de SITE_URL si existe
        if (defined('SITE_URL')) {
            return rtrim(SITE_URL, '/');
        }
        return '';
    }

    // Devuelve la URL pública completa para un path relativo dentro del sitio
    public static function getUploadUrl($relativePath = '')
    {
        $relativePath = ltrim($relativePath, '/');
        if (function_exists('obtenerUrlCompleta')) {
            return obtenerUrlCompleta($relativePath);
        }
        if (defined('SITE_URL')) {
            return rtrim(SITE_URL, '/') . ($relativePath ? '/' . $relativePath : '');
        }
        return $relativePath;
    }

    // Genera la configuración JS que esperan los scripts (TinyMCE, uploadUrl, etc.)
    public static function getJsConfig()
    {
        $tiny = [
            'convert_urls' => false,
            'relative_urls' => true,
            'remove_script_host' => true,
            // API key para TinyMCE Cloud / builds comerciales (añadida por el desarrollador)
            'apiKey' => 'fs267l4ojip2i5cyjtdpr6dlxhzyarkx01qktriyt9gr98q3',
            'document_base_url' => (function_exists('obtenerUrlCompleta') ? obtenerUrlCompleta() : (defined('SITE_URL') ? SITE_URL : '')) . '/'
        ];

        $cfg = [
            'isLocal' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development'),
            'baseUrl' => function_exists('obtenerUrlCompleta') ? obtenerUrlCompleta() : (defined('SITE_URL') ? SITE_URL : ''),
            'baseDir' => self::getBaseDir(),
            'uploadUrl' => (function_exists('obtenerUrlCompleta') ? obtenerUrlCompleta('admin/upload.php') : (defined('ADMIN_URL') ? rtrim(ADMIN_URL, '/') . '/upload.php' : 'admin/upload.php')),
            'tinyMCE' => $tiny
        ];

        // Retornar JSON seguro para ser inyectado en JS
        return json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
