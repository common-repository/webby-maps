<?php

namespace WebbyMaps\Core\Collection;

use WebbyMaps\Core\DbStructure;
use WebbyMaps\Core\Helper;

class Maps
{
    private $allMaps;

    public function getAllMaps($includeSensitiveData = false)
    {
        global $wpdb;
        $tableName = DbStructure::getTableName('maps');
        $maps = $wpdb->get_results("SELECT * FROM $tableName", ARRAY_A);
        if (empty($maps)) {
            $maps = [];
        }
        for ($m = 0; $m < count($maps); $m++) {
            $maps[$m]['config'] = maybe_unserialize($maps[$m]['config']);
            $maps[$m]['shortcode'] = Helper::generateShortcode($maps[$m]['id']);

            // delete sensitive data
            if (!$includeSensitiveData && array_key_exists('markerIconsFilePath', $maps[$m]['config'])) {
                unset($maps[$m]['config']['markerIconsFilePath']);
            }
        }
        $this->allMaps = $maps;
        return $maps;
    }

    public function getMapsWithMarkerIconsFileFilter($filePath)
    {
        $filteredMaps = [];
        if (empty($this->allMaps)) {
            $this->getAllMaps();
        }
        foreach ($this->allMaps as $mapItem) {
            if ($mapItem['config']['markerIconsFilePath'] === $filePath) {
                array_push($filteredMaps, $mapItem);
            }
        }
        return $filteredMaps;
    }
}
