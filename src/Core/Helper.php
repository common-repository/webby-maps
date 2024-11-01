<?php

namespace WebbyMaps\Core;

class Helper
{
    public static function generateShortcode($mapId)
    {
        return sprintf('[webbymaps mapid=%d]', $mapId);
    }

    public static function getNoncePhrase($identifier, $mapId = null)
    {
        return sprintf('webbymaps-%s-%d', $identifier, $mapId);
    }

    public static function acquireMapId()
    {
        $mapId = 0;
        if (!empty($_POST['mapId'])) {
            $mapId = intval($_POST['mapId']);
        } else if (!empty($_POST['mapid'])) {
            $mapId = intval($_POST['mapid']);
        } else if (!empty($_GET['mapid'])) {
            $mapId = intval($_GET['mapid']);
        }
        return $mapId;
    }

    public static function mergeMapData($mapData1, $mapData2)
    {
        if (empty($mapData1)) {
            $mapData1 = array();
        }
        if (empty($mapData2)) {
            $mapData2 = array();
        }
        if (!array_key_exists('config', $mapData1)) {
            $mapData1['config'] = [];
        }
        if (!array_key_exists('config', $mapData2)) {
            $mapData2['config'] = [];
        }
        $mapData2['config'] = array_merge($mapData1['config'], $mapData2['config']);
        $mergedMapData = array_merge($mapData1, $mapData2);
        return $mergedMapData;
    }

    public static function hasValue($variable)
    {
        $hasValue = true;
        if (empty($variable) || (is_string($variable) && empty(trim($variable)))) {
            $hasValue = false;
        } else if (is_array($variable)) {
            foreach ($variable as $value) {
                if (!self::hasValue($value)) {
                    $hasValue = false;
                    break;
                }
            }
        }
        return $hasValue;
    }

    public static function sanitizeToString($inputString)
    {
        return sanitize_text_field($inputString);
    }

    public static function sanitizeToInt($inputString)
    {
        return intval(sanitize_text_field($inputString));
    }

    public static function sanitizeToFloat($inputString)
    {
        return floatval(sanitize_text_field($inputString));
    }

    public static function sanitizeToBool($inputString)
    {
        $value = sanitize_text_field($inputString);
        return strtolower($value) === 'yes' || strtolower($value) === 'true' ? true : false;
    }
}
