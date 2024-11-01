<?php

namespace WebbyMaps\Core;

class DbStructure
{
    public function __construct()
    {
        $this->loadFiles();
    }

    public static function getTableName($objectName)
    {
        global $wpdb;
        switch ($objectName) {
            case 'maps':
            case 'map':
                $tableName = $wpdb->prefix . 'webbymaps_maps';
                break;
            case 'markers':
            case 'marker':
                $tableName = $wpdb->prefix . 'webbymaps_markers';
                break;
            default:
                $tableName = false;
                break;
        }
        return $tableName;
    }

    public function ensureTablesExist()
    {
        maybe_create_table(self::getTableName('maps'), self::getTableDDl('maps'));
        // maybe_create_table(self::getTableName('markers'), self::getTableDDl('markers'));
    }

    protected static function getTableDDl($objectName)
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        switch ($objectName) {
            case 'maps':
            case 'map':
                $tableName = self::getTableName('maps');
                $sql = "CREATE TABLE $tableName (
                    id SMALLINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NULL,
                    config MEDIUMTEXT NULL
                ) $charsetCollate";
                break;
            case 'markers':
            case 'marker':
                $tableName = self::getTableName('markers');
                $sql = "CREATE TABLE $tableName (
                    id MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    map_id SMALLINT NOT NULL,
                    name VARCHAR(255) NULL,
                    coordinate POINT NOT NULL,
                    properties TEXT NULL
                ) $charsetCollate";
                break;
            default:
                $sql = false;
                break;
        }

        return $sql;
    }

    protected function loadFiles()
    {
        require_once(webbymaps_wproot_path('wp-admin/includes/upgrade.php'));
    }
}
