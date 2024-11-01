<?php

namespace WebbyMaps\Admin;

use WebbyMaps\Core\Map;
use WebbyMaps\Core\Collection\Maps as MapsCollection;
use WebbyMaps\Core\Helper;
use WebbyMaps\Admin\SettingsInputValidator;

/**
 * Handle settings (add/edit/delete maps)
 */
class SettingsHandler
{
    private static $mapId;
    private static $submittedMapData;
    private static $errors;
    private static $instance;

    public static function register()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        // setting up the mapId if available
        self::$instance = new SettingsHandler();

        // the request is POST, and we need to handle it.
        if (self::needHandleSettings()) {
            //fill the submittedMapData
            self::$submittedMapData = self::$instance->getStructuredPostData();

            if (Page::isPage('add-map')) {
                self::$instance->handleAddMap();
            } else if (Page::isPage('edit-map')) {
                self::$instance->handleEditMap();
            } else if (Page::isPage('delete-map')) {
                self::$instance->handleDeleteMap();
            }
        }

        return self::$instance;
    }

    public static function needHandleSettings()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' && Page::isOurPages();
    }

    public static function getSubmittedMapData()
    {
        if (!isset(self::$submittedMapData)) {
            self::$submittedMapData = self::$instance->getStructuredPostData();
        }
        return self::$submittedMapData;
    }

    public static function getMapId()
    {
        if (!isset(self::$mapId)) {
            self::register();
        }
        return self::$mapId;
    }

    public static function getErrors()
    {
        if (!isset(self::$errors)) {
            self::register();
        }
        return self::$errors;
    }

    private function __construct()
    {
        $mapId = 0;
        if (!empty($_POST['mapId'])) {
            $mapId = intval($_POST['mapId']);
        } else if (!empty($_POST['mapid'])) {
            $mapId = intval($_POST['mapid']);
        } else if (!empty($_GET['mapid'])) {
            $mapId = intval($_GET['mapid']);
        }

        if ($mapId > 0) {
            self::$mapId = $mapId;
        }
    }

    protected function handleAddMap()
    {
        self::$errors = $this->validatePostData();
        if (empty(self::$errors)) {
            $map = new Map();
            $map->setMapData(self::$submittedMapData);
            $this->resolveFileUpload($map);
            $map->saveMap();

            // redirect to list maps page (only when successfully added map)
            $url = Page::getPageUrl('list-maps') . "&success=1&action=add&mapname=" . self::$submittedMapData['name'];
            wp_redirect($url);
            exit;
        }
    }

    protected function handleEditMap()
    {
        self::$errors = $this->validatePostData();
        if (empty(self::$errors)) {
            $map = new Map(self::$mapId, true);
            $map->setMapData(self::$submittedMapData);

            $this->resolveFileUpload($map);
            $map->saveMap();

            // redirect to list maps page (only when successfully update map)
            $url = Page::getPageUrl('list-maps') . "&success=1&action=edit&mapname=" . self::$submittedMapData['name'];
            wp_redirect($url);
            exit;
        }
    }

    protected function handleDeleteMap()
    {
        $map = new Map(self::$mapId);
        $mapData = $map->getMapData();

        if (isset(self::$mapId)) {
            $map->deleteMap();
        }

        // redirect to list maps page (whether successfully delete or not)
        $url = Page::getPageUrl('list-maps') . "&success=1&action=delete&mapname=" . $mapData['name'];
        wp_redirect($url);
        exit;
    }

    /**
     * Get post data in structured format, but excluding File related properties
     *
     * @return array $mapData
     */
    protected function getStructuredPostData()
    {
        $mapData = array();
        $mapData['config'] = array();
        $mapData['config']['markers'] = [];

        $mapData['name'] = $this->sanitizePostToString('mapName');
        $mapData['config']['width'] = $this->sanitizePostToInt('mapWidth');
        $mapData['config']['widthUnit'] = $this->sanitizePostToString('mapWidthUnit');
        $mapData['config']['height'] = $this->sanitizePostToInt('mapHeight');
        $mapData['config']['heightUnit'] = $this->sanitizePostToString('mapHeightUnit');
        $mapData['config']['enableInfoWindows'] = $this->sanitizePostToBool('enableInfoWindows');
        $mapData['config']['infoWindowsContentType'] = $this->sanitizePostToString('infoWindowsContentType');
        $mapData['config']['showInfoWindowsOn'] = $this->sanitizePostToString('showInfoWindowsOn');
        $mapData['config']['infoWindowsCustomClass'] = $this->sanitizePostToString('infoWindowsCustomClass');
        $mapData['config']['markerIconsType'] = $this->sanitizePostToString('markerIconsType');
        $mapData['config']['markerIconsWidth'] = $this->sanitizePostToInt('markerIconsWidth');
        $mapData['config']['markerIconsHeight'] = $this->sanitizePostToInt('markerIconsHeight');

        // markers
        $totalMarkers = $this->getTotalMarkers();
        for ($m = 0; $m < $totalMarkers; $m++) {
            $mapData['config']['markers'][$m]['id'] = $this->sanitizeArrayPostToString('markerId', $m);
            $mapData['config']['markers'][$m]['latitude'] = $this->sanitizeArrayPostToFloat('markerLatitude', $m);
            $mapData['config']['markers'][$m]['longitude'] = $this->sanitizeArrayPostToFloat('markerLongitude', $m);
            $mapData['config']['markers'][$m]['infoWindowLocationName'] = $this->sanitizeArrayPostToString('infoWindowLocationName', $m);
            $mapData['config']['markers'][$m]['infoWindowLocationAddress'] = $this->sanitizeArrayPostToString('infoWindowLocationAddress', $m);
            $mapData['config']['markers'][$m]['infoWindowDirectionText'] = $this->sanitizeArrayPostToString('infoWindowDirectionText', $m);
            // Because this contains HTML, we don't need to sanitize it

            if ($mapData['config']['infoWindowsContentType'] === 'custom') {
                $mapData['config']['markers'][$m]['infoWindowCustomContent'] = $_POST['infoWindowCustomContent'][$m];
            }
        }
        return $mapData;
    }

    /**
     * Resolves file upload for custom icon
     *
     * @return void
     */
    protected function resolveFileUpload(Map &$map)
    {
        // if there is new icon uploaded
        if ($this->getPostValue('markerIconsType') === 'custom' && $_FILES['markerIconsFile'] && !empty($_FILES['markerIconsFile']['tmp_name'])) {
            $mapData = $map->getMapData();

            // Maybe delete old file. if it's an update process and if exist old file, then delete the old file
            if (self::$mapId && array_key_exists('config', $mapData) && !empty($mapData['config']['markerIconsFilePath'])) {
                // check whether there is another maps using the same file
                $mapsColl = new MapsCollection();
                $mapsWithSameFile = $mapsColl->getMapsWithMarkerIconsFileFilter($mapData['config']['markerIconsFilePath']);
                if (count($mapsWithSameFile) === 1 || (self::$mapId && $mapsWithSameFile[0]['id'] === self::$mapId)) {
                    // delete in filesystem
                    unlink($mapData['config']['markerIconsFilePath']);
                }
            }

            // upload to filesystem
            $uploadDir = wp_upload_dir();
            $fileName = basename($_FILES['markerIconsFile']['name']);
            $filePath = rtrim($uploadDir['path'], "/") . '/' . $fileName;
            $fileUrl = rtrim($uploadDir['url'], "/") . '/' . $fileName;
            $file = file_get_contents($_FILES['markerIconsFile']['tmp_name']);
            file_put_contents($filePath, $file);

            // update mapData
            $mapData['config']['markerIconsFilePath'] = $filePath;
            $mapData['config']['markerIconsFileUrl'] = $fileUrl;
            $mapData['config']['markerIconsFileName'] = $fileName;
            $mapData['config']['markerIconsFileOriginalWidth'] = $this->sanitizePostToInt('markerIconsFileOriginalWidth');
            $mapData['config']['markerIconsFileOriginalHeight'] = $this->sanitizePostToInt('markerIconsFileOriginalHeight');
            $map->setMapData($mapData);
            self::$submittedMapData = Helper::mergeMapData($mapData, self::$submittedMapData);
        }
    }

    protected function sanitizePostToString($postKey)
    {
        return $this->hasPostValue($postKey) ? Helper::sanitizeToString($_POST[$postKey]) : Map::getDefaultValue($postKey);
    }

    protected function sanitizePostToInt($postKey)
    {
        return $this->hasPostValue($postKey) ? Helper::sanitizeToInt($_POST[$postKey]) : Map::getDefaultValue($postKey);
    }

    protected function sanitizePostToFloat($postKey)
    {
        return $this->hasPostValue($postKey) ? Helper::sanitizeToFloat($_POST[$postKey]) : Map::getDefaultValue($postKey);
    }

    protected function sanitizePostToBool($postKey)
    {
        return $this->hasPostValue($postKey) ? Helper::sanitizeToBool($_POST[$postKey]) : Map::getDefaultValue($postKey);
    }

    protected function sanitizeArrayPostToString($postKey, $index)
    {
        return $this->hasArrayPostValue($postKey, $index) ? Helper::sanitizeToString($_POST[$postKey][$index]) : Map::getDefaultValue($postKey);
    }

    protected function sanitizeArrayPostToFloat($postKey, $index)
    {
        return $this->hasArrayPostValue($postKey, $index) ? Helper::sanitizeToFloat($_POST[$postKey][$index]) : Map::getDefaultValue($postKey);
    }

    protected function getTotalMarkers()
    {
        if (empty($_POST['markerLatitude']) || !is_array($_POST['markerLatitude'])) {
            return false;
        }
        return count($_POST['markerLatitude']);
    }

    protected function getPostValue($key, $arrayIndex = false)
    {
        if (empty($_POST[$key])) {
            return false;
        }
        if (is_string($_POST[$key])) {
            $value = sanitize_text_field($_POST[$key]);
            return strtolower($value) === 'yes' || strtolower($value) === 'true' ? true : $value;
        }
        if (is_array($_POST[$key])) {
            if ($arrayIndex !== false) {
                return $_POST[$key][$arrayIndex];
            } else {
                return $_POST[$key];
            }
        }
    }

    protected function hasPostValue($key)
    {
        return isset($_POST[$key]) && Helper::hasValue($_POST[$key]);
    }

    protected function hasArrayPostValue($key, $index)
    {
        return isset($_POST[$key]) && Helper::hasValue($_POST[$key][$index]);
    }

    protected function validatePostData()
    {
        $validator = new SettingsInputValidator();
        $errors = $validator->validateMapSettingsInput();
        return $errors;
    }
}
