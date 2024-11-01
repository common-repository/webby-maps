<?php

namespace WebbyMaps\Core;

use WebbyMaps\Core\DbStructure;

class Map
{
    private $mapId;
    private $mapData;
    private $isLoaded = false;

    public function __construct($mapId = null, $isPopulateData = false)
    {
        if ($mapId) {
            $this->mapId = $mapId;
        }

        $this->mapData = [];
        if ($isPopulateData) {
            $this->getMapData();
        }
    }

    public function setMapData($mapData)
    {
        $this->mapData = Helper::mergeMapData($this->mapData, $mapData);
    }

    public function setMarkerIconsFile($filePath, $fileUrl, $fileName)
    {
        if (!array_key_exists('config', $this->mapData)) {
            $this->mapData = array();
        }
        $this->mapData['config']['markerIconsFilePath'] = $filePath;
        $this->mapData['config']['markerIconsFileUrl'] = $fileUrl;
        $this->mapData['config']['markerIconsFileName'] = $fileName;
    }

    public function deleteMarkerIconsFile()
    {
    }

    public function saveMap()
    {
        global $wpdb;
        $tableName = DbStructure::getTableName('maps');

        if (empty($this->mapData['name']) || empty($this->mapData['config'])) {
            return;
        }

        // if updating, load from database first so that we could "merge" old data and new data
        if ($this->mapId) {
            $oldMapData = $this->loadFromDb();
            $this->mapData = Helper::mergeMapData($oldMapData, $this->mapData);
        }

        // save to database
        if (!$this->mapId) {
            // insert new map
            $stmt = $wpdb->prepare("INSERT INTO $tableName (name, config) VALUES (%s, %s)", $this->mapData['name'], maybe_serialize($this->mapData['config']));
            $wpdb->query($stmt);
            $this->mapId = $wpdb->insert_id;
        } else {
            // update map
            $stmt = $wpdb->prepare("UPDATE $tableName SET name = %s, config = %s WHERE id = %d", $this->mapData['name'], maybe_serialize($this->mapData['config']), $this->mapId);
            $wpdb->query($stmt);
        }
        return $this->mapId;
    }

    public function deleteMap()
    {
        global $wpdb;
        $tableName = DbStructure::getTableName('maps');

        // mapId not set, unable to delete something
        if ($this->mapId) {
            return;
        }

        $stmt = $wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $this->mapId);
        $wpdb->query($stmt);
    }

    public function getMapData($includeSensitiveData = false)
    {
        if ($this->mapId && !$this->isLoaded) {
            // load from database, store in mapData, and return
            $storedMapData = $this->loadFromDb();
            $this->mapData = Helper::mergeMapData($storedMapData, $this->mapData);
        }

        $returnedMapData = $this->mapData;
        // delete sensitive data
        if (!$includeSensitiveData && array_key_exists('markerIconsFilePath', $returnedMapData['config'])) {
            unset($returnedMapData['config']['markerIconsFilePath']);
        }
        return $returnedMapData;
    }

    /**
     * Get blank mapData, without markers.
     * This serves the correct formatting.
     *
     * @return array $blankMapDataData
     */
    public static function getBlankMapData()
    {
        $blankMapData = array();
        $blankMapData['name'] = '';
        $blankMapData['config'] = array();
        $blankMapData['config']['width'] = 100;
        $blankMapData['config']['widthUnit'] = '%';
        $blankMapData['config']['height'] = 500;
        $blankMapData['config']['heightUnit'] = 'px';
        $blankMapData['config']['enableInfoWindows'] = true;
        $blankMapData['config']['infoWindowsContentType'] = 'default';  // "default" or "custom"
        $blankMapData['config']['showInfoWindowsOn'] = 'start';  // "start" or "click"
        $blankMapData['config']['infoWindowsCustomClass'] = ''; // additional CSS Class
        $blankMapData['config']['markerIconsType'] = 'default'; // "default" or "custom"
        $blankMapData['config']['markerIconsFile'] = null;
        $blankMapData['config']['markerIconsFileName'] = '';
        $blankMapData['config']['markerIconsWidth'] = null;
        $blankMapData['config']['markerIconsHeight'] = null;
        $blankMapData['config']['markers'] = [];
        return $blankMapData;
    }

    public static function getBlankMarker()
    {
        $blankMarker = array();
        $blankMarker['latitude'] = null;
        $blankMarker['longitude'] = null;
        $blankMarker['infoWindowCustomContent'] = '';
        $blankMarker['infoWindowLocationName'] = '';
        $blankMarker['infoWindowLocationAddress'] = '';
        $blankMarker['infoWindowDirectionText'] = '';
        return $blankMarker;
    }

    /**
     * Get default value
     *
     * @param string $flatKey Key like in the POST
     * @return mixed
     */
    public static function getDefaultValue($flatKey)
    {
        $blankMapData = self::getBlankMapData();
        $blankMarker = self::getBlankMarker();
        switch ($flatKey) {
            case 'mapName':
                return $blankMapData['name'];
            case 'mapWidth':
                return $blankMapData['config']['width'];
            case 'mapWidthUnit':
                return $blankMapData['config']['widthUnit'];
            case 'mapHeight':
                return $blankMapData['config']['height'];
            case 'mapHeightUnit':
                return $blankMapData['config']['heightUnit'];
            case 'enableInfoWindows':
                return $blankMapData['config']['enableInfoWindows'];
            case 'infoWindowsContentType':
                return $blankMapData['config']['infoWindowsContentType'];
            case 'showInfoWindowsOn':
                return $blankMapData['config']['showInfoWindowsOn'];
            case 'infoWindowsCustomClass':
                return $blankMapData['config']['infoWindowsCustomClass'];
            case 'markerIconsType':
                return $blankMapData['config']['markerIconsType'];
            case 'markerIconsFile':
                return $blankMapData['config']['markerIconsFile'];
            case 'markerIconsWidth':
                return $blankMapData['config']['markerIconsWidth'];
            case 'markerIconsHeight':
                return $blankMapData['config']['markerIconsHeight'];
            case 'markerLatitude':
                return $blankMarker['latitude'];
            case 'markerLongitude':
                return $blankMarker[''];
            case 'infoWindowLocationName':
                return $blankMarker['infoWindowLocationName'];
            case 'infoWindowLocationAddress':
                return $blankMarker['infoWindowLocationAddress'];
            case 'infoWindowDirectionText':
                return $blankMarker['infoWindowDirectionText'];
            case 'infoWindowCustomContent':
                return $blankMarker['infoWindowCustomContent'];
        }
    }

    protected function loadFromDb()
    {
        global $wpdb;
        $tableName = DbStructure::getTableName('maps');

        $stmt = $wpdb->prepare("SELECT * FROM $tableName WHERE id = %d", $this->mapId);
        $results = $wpdb->get_results($stmt, ARRAY_A);
        if (count($results) > 0) {
            $results[0]['config'] = maybe_unserialize($results[0]['config']);
            $results[0]['shortcode'] = Helper::generateShortcode($results[0]['id']);
            return $results[0];
        } else {
            return null;
        }
    }
}
