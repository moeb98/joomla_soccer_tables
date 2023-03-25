<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
header('Content-Type: text/html; charset=utf-8');

/**
 * mod_soccer_results.php 
 */

require_once 'helper.php';

    try {
        $ergebnisse = new modSoccerResultsHelper($module);
        $strHTMLOutput = "\r\n<!-- Soccer Results -->\r\n";
        $strHTMLOutput .= "<div id='spielplan_" . $module->id . "'> <img id='soccer_results_loading_" . $module->id . "' src='".JURI::root()."modules/mod_soccer_results/images/ajax-loader.gif'></div>\r\n";
    } catch (Exception $e) {
        //echo $e->getMessage();
        echo '<div align="left">'.$params->get('timeout_error').'</div>';
    }

    require JModuleHelper::getLayoutPath('mod_soccer_results');
