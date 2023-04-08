<?php

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class mod_soccer_tableInstallerScript {
    /**
     * Extension script constructor.
     *
     * @return  void
     */
    public function __construct() {
        $this->minimumJoomla = '4.0';
    }  
    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     */
    function preflight($type, $parent) {
        // Check for the minimum Joomla version before continuing
        if (!empty($this->minimumJoomla) && version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla), Log::WARNING, 'jerror');

            return false;
        }
        
		echo Text::_('MOD_SOCCER_TABLE_INSTALLERSCRIPT_PREFLIGHT');
        
        echo $this->minimumJoomla;

        return true;
    }
	
    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     */
    function postflight($type, $parent) {
        return true;
    }

    /**
     * Method to install the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     */
    function install($parent) {
        $this->setupDatabase();
        return true;
    }    

    /**
     * Method to update the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     */
    function update($parent) {
        $this->setupDatabase();
        return true;
    }  

    /**
     * Method to uninstall the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     */
    function uninstall($parent) {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = 'DROP TABLE '.$db->quoteName('#__soccer_table');

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    private function setupDatabase()
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = 'CREATE TABLE IF NOT EXISTS '.$db->quoteName('#__soccer_table').' (ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, team VARCHAR(100), spiele INT, tore INT, gegentore INT, punkte INT, modul_id INT)';
        $db->setQuery($query);
        $db->execute();

        $query = 'TRUNCATE TABLE '.$db->quoteName('#__soccer_table');
        $db->setQuery($query);
        $db->execute();

        $cachefile = JPATH_BASE."/../modules/mod_soccer_table/cache.txt";
        if (is_readable($cachefile)) {
            unlink($cachefile);
        }
    }
}
