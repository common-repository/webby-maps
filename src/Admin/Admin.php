<?php

namespace WebbyMaps\Admin;

use WebbyMaps\Core\DbStructure;

class Admin
{
    private static $instance;

    private function __construct()
    {
        if (is_admin()) {
            // setup admin
            $this->loadFiles();
            $this->setupMenu();

            if (Page::isOurPages()) {
                new HooksRegistration();

                // processes that happen after admin init.
                add_action('admin_init', function () {
                    $dbStructure = new DbStructure();
                    $dbStructure->ensureTablesExist();

                    // register admin settings & save process
                    SettingsHandler::register();

                    $this->setupPages();
                });
            }
        }
    }

    public static function init()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Admin();
        }
        return self::$instance;
    }

    protected function setupMenu()
    {
        add_action('admin_menu', function () {
            add_menu_page(
                'Webby Maps Settings',
                'Webby Maps',
                'manage_options',
                'webby-maps',
                array('\WebbyMaps\Admin\Page', 'htmlSkeleton'),
                'dashicons-location',
                59
            );

            add_submenu_page(
                'webby-maps',
                'All Maps',
                'All Maps',
                'manage_options',
                'webby-maps',
                array('\WebbyMaps\Admin\Page', 'htmlSkeleton'),
            );

            add_submenu_page(
                'webby-maps',
                'Create New',
                'Create New',
                'manage_options',
                'webby-maps-add',
                array('\WebbyMaps\Admin\Page', 'htmlSkeleton'),
            );
        });
    }

    protected function setupPages()
    {
        if (Page::isPage('add-map')) {
            new Page('add-map');
        } else if (Page::isPage('edit-map')) {
            new Page('edit-map');
        } else if (Page::isPage('list-maps')) { // put this last (because the URL is the subset of other page like edit-map)
            new Page('list-maps');
        }
    }

    protected function loadFiles()
    {
        require_once(webbymaps_path('src/Core/Helper.php'));
        require_once(webbymaps_path('src/Core/DbStructure.php'));
        require_once(webbymaps_path('src/Core/Collection/Maps.php'));
        require_once(webbymaps_path('src/Core/Map.php'));
        require_once(webbymaps_path('src/Admin/HooksRegistration.php'));
        require_once(webbymaps_path('src/Admin/SettingsHandler.php'));
        require_once(webbymaps_path('src/Admin/SettingsInputValidator.php'));
        require_once(webbymaps_path('src/Admin/Page.php'));
    }
}
