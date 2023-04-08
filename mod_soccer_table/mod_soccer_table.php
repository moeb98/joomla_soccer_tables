<?php

// no direct access
defined('_JEXEC') or die('Restricted access');
header('Content-Type: text/html; charset=utf-8');

/**
 * mod_soccer_table.php 
 */

require_once 'helper.php';

  try {
      $tabelle = new modSoccerTableHelper($module);
      $strHTMLOutput = "\r\n<!-- Soccer Table -->\r\n";
      $strHTMLOutput .= '<div id="soccer_table_' . $module->id . '"> <img id="soccer_table_loading_' . $module->id . '" src="'.JURI::root().'modules/mod_soccer_table/images/ajax-loader.gif"></div>';
  } catch (Exception $e) {
      echo '<div align="left">Ein Fehler ist aufgetreten:<br>' . $e->getMessage() . '</div>';
  }

  require JModuleHelper::getLayoutPath('mod_soccer_table');
