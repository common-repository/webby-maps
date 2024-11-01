<?php

namespace WebbyMaps\Admin;

use WebbyMaps\Core\Map;
use WebbyMaps\Core\Collection\Maps as MapsCollection;
use WebbyMaps\Core\Helper;

class Page
{
    private $pageIdentifier;

    public static function isOurPages()
    {
        return self::isPage('list-maps') || self::isPage('add-map') || self::isPage('edit-map') || self::isPage('delete-map');
    }

    /**
     * Check whether this URL is our specific page
     *
     * @param string $pageIdentifier
     * @return boolean
     */
    public static function isPage($pageIdentifier)
    {
        switch ($pageIdentifier) {

            case 'add-map':
                return isset($_GET['page']) && $_GET['page'] === 'webby-maps-add';
            case 'edit-map':
                return isset($_GET['page']) && $_GET['page'] === 'webby-maps' &&
                    isset($_GET['subpage']) && $_GET['subpage'] === 'edit-map' &&
                    isset($_GET['mapid']);
            case 'delete-map':
                return isset($_GET['page']) && $_GET['page'] === 'webby-maps' &&
                    isset($_GET['subpage']) && $_GET['subpage'] === 'delete-map' &&
                    isset($_GET['mapid']);
            case 'list-maps':   // put this last (just for precaution)
                return isset($_GET['page']) && $_GET['page'] === 'webby-maps';
        }
    }

    public static function getPageUrl($pageIdentifier, $mapId = null)
    {
        switch ($pageIdentifier) {
            case 'add-map':
                return admin_url('admin.php?page=webby-maps-add');
                break;
            case 'list-maps':
                return admin_url('admin.php?page=webby-maps');
                break;
            case 'edit-map':
                return admin_url("admin.php?page=webby-maps&subpage=edit-map&mapid=$mapId");
                break;
        }
    }

    public function __construct($pageIdentifier)
    {
        $this->pageIdentifier = $pageIdentifier;
        switch ($pageIdentifier) {
            case 'list-maps':
                $this->setupListMapsPage();
                break;
            case 'add-map':
                $this->setupAddMapPage();
                break;
            case 'edit-map':
                $this->setupEditMapPage();
                break;
        }
    }

    protected function setupListMapsPage()
    {
        $this->setupContainerVariable();
        // setup Data
        add_action('admin_head', function () {
            $data['page'] = $this->pageIdentifier;
            $data['adminUrl'] = get_admin_url(null, 'admin.php');
            // maps data
            $mapsCollection = new MapsCollection();
            $maps = $mapsCollection->getAllMaps();
            $data['maps'] = $maps;
            // success notifications
            if (isset($_GET['success']) && $_GET['success'] === '1' && isset($_GET['action']) && isset($_GET['mapname'])) {
                $action = sanitize_key($_GET['action']);
                $mapName = sanitize_text_field($_GET['mapname']);
                switch ($action) {
                    case 'edit':
                        $data['successNotifications'] = array(sprintf(__('Map "%s" updated successfully', 'webby-maps'), $mapName));
                        break;
                    case 'add':
                        $data['successNotifications'] = array(sprintf(__('Map "%s" created successfully', 'webby-maps'), $mapName));
                        break;
                    case 'delete':
                        $data['successNotifications'] = array(sprintf(__('Map "%s" deleted successfully', 'webby-maps'), $mapName));
                        break;
                }
            }

            // echo data
            printf("<script>webbymaps.data = %s;</script>", json_encode($data));
        });

        //setup Scripts
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('roboto-font', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap');
            wp_enqueue_style('webbymaps-listmaps', webbymaps_url('dist/templates/ListMaps.css'));
            // view template
            wp_enqueue_script('webbymaps-listmaps', webbymaps_url('dist/templates/ListMaps.js'), array(), false, true);
        });
    }

    protected function setupAddMapPage()
    {
        $this->setupContainerVariable();

        // change title
        add_filter('admin_title', function ($adminTitle, $title) {
            return "Create New Map" . ' &lsaquo; WebbyMaps';
        }, 10, 2);

        // setup Data
        add_action('admin_head', function () {
            // from submission
            if (SettingsHandler::needHandleSettings()) {
                $submittedMapData = SettingsHandler::getSubmittedMapData();
                $errorNotifications = SettingsHandler::getErrors();
                if (!empty($submittedMapData)) {
                    $data['mapData'] = $submittedMapData;
                    $data['errorNotifications'] = $errorNotifications;
                }
            }

            $data['page'] = $this->pageIdentifier;
            $data['blankMapData'] = Map::getBlankMapData();    // mapData filled with defaultMapData
            $data['blankMarker'] = Map::getBlankMarker();
            // echo data
            printf("<script>webbymaps.data = %s;</script>", json_encode($data));
        });

        //setup Scripts
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('roboto-font', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap');
            wp_enqueue_style('leaflet', webbymaps_url('dist/css/leaflet-1.7.1.min.css'), array(), '1.7.1');
            wp_enqueue_style('webbymaps-editmap', webbymaps_url('dist/templates/EditMap.css'));
            // view template
            wp_enqueue_script('webbymaps-addmap', webbymaps_url('dist/templates/AddMap.js'), array(), false, true);
        });
    }

    protected function setupEditMapPage()
    {
        $this->setupContainerVariable();
        $mapId = Helper::acquireMapId();
        if ($mapId > 0) {
            $map = new Map($mapId);
            $mapData = $map->getMapData();
        } else {
            // redirect to list maps if no map Id found
            wp_redirect(Page::getPageUrl('list-maps'));
            exit;
        }

        // change title
        add_filter('admin_title', function ($adminTitle, $title) use ($mapId, $mapData) {
            $mapName = 'Map';
            if ($mapId > 0) {
                $mapName = $mapData['name'];
            }
            return 'Edit Map "' . $mapName . '" &lsaquo; WebbyMaps';
        }, 10, 2);

        // setup Data
        add_action('admin_head', function () use ($mapId, $mapData) {
            $errorNotifications = [];
            // if after submit
            if (SettingsHandler::needHandleSettings()) {
                // serve submitted data
                $submittedMapData = SettingsHandler::getSubmittedMapData();
                $errorNotifications = SettingsHandler::getErrors();
                $data['mapData'] = Helper::mergeMapData($mapData, $submittedMapData);
                if (!empty($errorNotifications)) {
                    $data['errorNotifications'] = $errorNotifications;
                }
            } else {    // if not submission (just opening the edit page)
                $data['mapData'] = $mapData;
            }

            // remove sensitive data
            if (array_key_exists('markerIconsFilePath', $data['mapData']['config'])) {
                unset($data['mapData']['config']['markerIconsFilePath']);
            }


            $data['page'] = $this->pageIdentifier;
            $data['blankMapData'] = Map::getBlankMapData();    // mapData filled with defaultMapData
            $data['blankMarker'] = Map::getBlankMarker();
            // echo data
            printf("<script>webbymaps.data = %s;</script>", json_encode($data));
        });

        //setup Scripts
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('roboto-font', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap');
            wp_enqueue_style('leaflet', webbymaps_url('dist/css/leaflet-1.7.1.min.css'), array(), '1.7.1');
            wp_enqueue_style('webbymaps-editmap', webbymaps_url('dist/templates/EditMap.css'));
            // view template
            wp_enqueue_script('webbymaps-editmap', webbymaps_url('dist/templates/EditMap.js'), array(), false, true);
        });
    }

    protected function setupContainerVariable()
    {
        add_action('admin_head', function () {
            printf("<script>if(!window.webbymaps) {window.webbymaps = {};}</script>");
        }, 5);
    }

    public static function htmlSkeleton()
    {
?>
        <div class="wrap">
            <div id="root"></div>
        </div>
<?php
    }
}
