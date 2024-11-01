<?php

namespace WebbyMaps\Front;

use WebbyMaps\Front\ShortcodeHandler;
use WebbyMaps\Front\MapsRenderer;

class Front
{
    private static $instance;

    private function __construct()
    {
        // only in front
        if (!is_admin()) {
            $this->loadFiles();
            ShortcodeHandler::registerHandler();
            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_style('leaflet', webbymaps_url('dist/css/leaflet-1.7.1.min.css'), array(), '1.7.1');
            });
        }
    }

    public static function init()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Front();
        }
        return self::$instance;
    }

    protected function loadFiles()
    {
        require_once(webbymaps_path('src/Front/ShortcodeHandler.php'));
        require_once(webbymaps_path('src/Front/MapsRenderer.php'));
        require_once(webbymaps_path('src/Core/Map.php'));
        require_once(webbymaps_path('src/Core/Collection/Maps.php'));
        require_once(webbymaps_path('src/Core/DbStructure.php'));
        require_once(webbymaps_path('src/Core/Helper.php'));
    }
}
