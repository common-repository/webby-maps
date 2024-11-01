<?php

namespace WebbyMaps\Front;

class MapsRenderer
{
    private static $registeredMaps = [];

    public static function registerMap($mapData)
    {
        if (!isset($mapData['id'])) {
            return;
        }

        if (self::checkMapAlreadyRegistered($mapData) === false) {
            // add 
            array_push(self::$registeredMaps, $mapData);

            // register initialization script
            wp_enqueue_script('leaflet', webbymaps_url('dist/js/leaflet-1.7.1.min.js'), array(), '1.7.1', true);    // set to footer
            wp_enqueue_script('webbymaps-init', webbymaps_url('dist/scripts/webbymaps.min.js'), ['leaflet'], webbymaps_version(), true);
        }
    }

    public static function getRenderedMap($mapId)
    {
        $renderedMap = '';

        $mapData = null;
        foreach (self::$registeredMaps as $map) {
            if ($map['id'] === $mapId) {
                $mapData = $map;
                $renderedMap .= self::getMapsStyle($mapData);
                break;
            }
        }

        // if (self::$registeredMaps[0]['id'] === $mapId) {
        //     $renderedMap .= self::getMapsStyle($mapData);
        // }


        $htmlContainer = self::getHtmlContainer($mapData);
        $dataScript = self::getDataScript($mapData);

        // only contains html container & datascript. Initialization script is be wp_enqueued.
        $renderedMap .= sprintf('%s %s', $htmlContainer, $dataScript);
        return $renderedMap;
    }

    protected static function checkMapAlreadyRegistered($mapData)
    {
        // check if this particular map already registered
        $isRegistered = false;
        foreach (self::$registeredMaps as $map) {
            if ($map['id'] === $mapData['id']) {
                $isRegistered = true;
                break;
            }
        }
        return $isRegistered;
    }

    public static function getMapsStyle($mapData)
    {
        if (is_admin()) {
            $elementId = '#preview-map';
        } else {
            $elementId = '#webbymaps-' . $mapData['id'];
        }

        return "<style>
            $elementId a { text-decoration: none; }
            $elementId p { margin: 0 0 .5em; }
            $elementId .leaflet-popup-tip-container {z-index: 10; transform: translateY(-1px);}
        </style>";
    }

    protected static function getHtmlContainer($mapData)
    {
        return sprintf('<div id="webbymaps-%d" style="width:%d%s;height:%d%s"></div>', $mapData['id'], $mapData['config']['width'], $mapData['config']['widthUnit'], $mapData['config']['height'], $mapData['config']['heightUnit']);
    }

    protected static function getDataScript($mapData)
    {
        $mapDataScript = sprintf('<script>
            if(!window.webbymaps) {window.webbymaps = {}}
            if(!window.webbymaps.data) {window.webbymaps.data = {}}
            if(!window.webbymaps.data.maps) {window.webbymaps.data.maps = []}
            window.webbymaps.data.maps.push(%s)
        </script>', json_encode($mapData));
        return $mapDataScript;
    }
}
