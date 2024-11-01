<?php

namespace WebbyMaps\Front;

use WebbyMaps\Core\Map;
use WebbyMaps\Front\MapsRenderer;

class ShortcodeHandler
{
    private static $isRegistered;

    public static function registerHandler()
    {
        if (!self::$isRegistered) {
            // register main shortcode
            self::$isRegistered = true;
            add_shortcode('webbymaps', function ($atts, $content) {
                $atts = array_change_key_case((array) $atts, CASE_LOWER);

                if (isset($atts['mapid']) && is_numeric($atts['mapid'])) {
                    $mapId = intval($atts['mapid']);
                    $map = new Map($mapId);
                    $mapData = $map->getMapData();

                    // remove shortcode data, because this could make bug (shortcode value could get translated into another map)
                    unset($mapData['shortcode']);

                    if (!empty($mapData) && array_key_exists('id', $mapData) && array_key_exists('config', $mapData) && !empty($mapData['id']) && !empty($mapData['config'])) {
                        MapsRenderer::registerMap($mapData);

                        $renderedMap = MapsRenderer::getRenderedMap($mapData['id']);
                        return $renderedMap;
                    }
                }
            });
        }
    }
}
