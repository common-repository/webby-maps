<?php

/**
 * Webby Maps
 *
 * @package           webby-maps
 * @author            Bagus Sasikirono
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Webby Maps
 * Description:       A free and lightweight plugin to add maps, markers, and directions to your website. Super simple! You don't even need to provide API key to use this plugin.
 * Version:           1.0.0
 * Requires at least: 4.0
 * Requires PHP:      5.6
 * Author:            Bagus Sasikirono
 * Author URI:        https://bagus.sasikirono.com
 * Text Domain:       webby-maps
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

function webbymaps_version()
{
    $version = '1.0.0';
    return $version;
}

function webbymaps_path($relativePath = '')
{
    $plugin_dir = rtrim(dirname(__FILE__), "/");
    $relativePath = '/' . ltrim($relativePath, '/');
    return $plugin_dir . $relativePath;
}

function webbymaps_url($relativePath = '')
{
    $plugin_url = rtrim(plugin_dir_url(__FILE__), "/");;
    $relativePath = '/' . ltrim($relativePath, '/');
    return $plugin_url . $relativePath;
}

function webbymaps_mainfile()
{
    return __FILE__;
}

function webbymaps_wproot_path($relativePath = '')
{
    $rootpath = rtrim(ABSPATH, '/');
    $relativePath = '/' . ltrim($relativePath, '/');
    return $rootpath . $relativePath;
}

require_once(webbymaps_path('src/Admin/Admin.php'));
WebbyMaps\Admin\Admin::init();

require_once(webbymaps_path('src/Front/Front.php'));
WebbyMaps\Front\Front::init();
