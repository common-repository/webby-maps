<?php

namespace WebbyMaps\Admin;

use WebbyMaps\Core\Helper;

class SettingsInputValidator
{
    public function validateMapSettingsInput()
    {
        $errors = [];

        // validate not empty
        $mapNameIsValid = $this->inputMustNotEmpty($errors, 'mapName', 'Map Name');
        $mapWidthIsValid = $this->inputMustNotEmpty($errors, 'mapWidth', 'Map Width');
        $mapWidthUnitIsValid = $this->inputMustNotEmpty($errors, 'mapWidthUnit', 'Map Width Unit');
        $mapHeightIsValid = $this->inputMustNotEmpty($errors, 'mapHeight', 'Map Height');
        $mapHeightUnitIsValid = $this->inputMustNotEmpty($errors, 'mapHeightUnit', 'Map Height Unit');
        $enableInfoWindowsIsValid = $this->inputMustNotEmpty($errors, 'enableInfoWindows', 'Enable Info Windows');
        $markerIconsTypeIsValid = $this->inputMustNotEmpty($errors, 'markerIconsType', 'Marker Icons Type');
        $markerLatitudeIsValid = $this->inputMustNotEmpty($errors, 'markerLatitude', 'Marker Latitude');
        $markerLongitudeIsValid = $this->inputMustNotEmpty($errors, 'markerLongitude', 'Marker Longitude');
        if ($enableInfoWindowsIsValid && $_POST['enableInfoWindows'] === true) {
            $infoWindowsContentTypeIsValid = $this->inputMustNotEmpty($errors, 'infoWindowsContentType', 'Info Windows Content Type');
            $showInfoWindowsOnIsValid = $this->inputMustNotEmpty($errors, 'showInfoWindowsOn', 'Show Info WIndows On');
        }
        if ($markerIconsTypeIsValid && $_POST['markerIconsType'] === 'custom') {
            $markerIconsWidthIsValid = $this->inputMustNotEmpty($errors, 'markerIconsWidth', "Marker Icons Width");
            $markerIconsHeightIsValid = $this->inputMustNotEmpty($errors, 'markerIconsHeight', "Marker Icons Height");

            // additional file check - if there is no icon file exist, then upload file is mandatory
            $existingFile = get_option('webbymaps_markerIconsFile');
            if (!$existingFile) {
                $markerIconsFileIsValid = $this->inputFileMustExist($errors, 'markerIconsFile', "Marker Icons File");
            }
        }

        // validate numeric
        if ($mapWidthIsValid) {
            $mapWidthIsValid = $this->inputMustNumeric($errors, 'mapWidth', 'Map Width');
        }
        if ($mapHeightIsValid) {
            $mapHeightIsValid = $this->inputMustNumeric($errors, 'mapHeight', 'Map Height');
        }
        if ($markerLatitudeIsValid) {
            $markerLatitudeIsValid = $this->inputMustNumeric($errors, 'markerLatitude', 'Marker Latitude');
        }
        if ($markerLongitudeIsValid) {
            $markerLongitudeIsValid = $this->inputMustNumeric($errors, 'markerLongitude', 'Marker Longitude');
        }
        if (isset($markerIconsWidthIsValid) && $markerIconsWidthIsValid) {
            $markerIconsWidthIsValid = $this->inputMustNumeric($errors, 'markerIconsWidth', 'Marker Icon Width');
        }
        if (isset($markerIconsHeightIsValid) && $markerIconsHeightIsValid) {
            $markerIconsHeightIsValid = $this->inputMustNumeric($errors, 'markerIconsHeight', 'Marker Icon Height');
        }

        return $errors;
    }

    protected function inputMustNotEmpty(&$errors, $key, $label)
    {
        $isValid = true;
        if (!isset($_POST[$key]) || !Helper::hasValue($_POST[$key])) {
            array_push($errors, sprintf(__('%s could not be empty', 'webby-maps'), $label));
            $isValid = false;
        }
        return $isValid;
    }

    protected function inputMustNumeric(&$errors, $key, $label)
    {
        $isValid = true;
        if (is_array($_POST[$key])) {
            foreach ($_POST[$key] as $item) {
                if (!is_numeric($item)) {
                    array_push($errors, sprintf(__('%s should be numeric', 'webby-maps'), $label));
                    $isValid = false;
                }
            }
        } else {
            if (!is_numeric($_POST[$key])) {
                array_push($errors, sprintf(__('%s should be numeric', 'webby-maps'), $label));
                $isValid = false;
            }
        }

        return $isValid;
    }

    protected function inputFileMustExist(&$errors, $key, $label)
    {
        if (!isset($_FILES[$key])) {
            array_push($errors, sprintf(__('%s could not be empty', 'webby-maps'), $label));
        }
    }
}
