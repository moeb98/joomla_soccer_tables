<?php

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class mod_soccer_resultsInstallerScript {
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
        
		echo Text::_('MOD_SOCCER_RESULTS_INSTALLERSCRIPT_PREFLIGHT');
        
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
        // $db = Factory::getDbo();
		$db = Factory::getContainer()->get('DatabaseDriver');
        $query = 'DROP TABLE '.$db->quoteName('#__soccer_results');

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    private function setupDatabase() {
        // $db = Factory::getDbo();
		$db = Factory::getContainer()->get('DatabaseDriver');
        $query = 'CREATE TABLE IF NOT EXISTS '.$db->quoteName('#__soccer_results').' (ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, bezeichnung_webservice VARCHAR(100), bezeichnung_kurz VARCHAR(100), bezeichnung_mittel VARCHAR(100), dateiname_logo VARCHAR(100))';

        $db->setQuery($query);
        $db->execute();

        $query = 'TRUNCATE TABLE '.$db->quoteName('#__soccer_results');
        $db->setQuery($query);
        $db->execute();

        $query = "INSERT INTO ".$db->quoteName('#__soccer_results')." VALUES
				(1, 'VfL Wolfsburg', 'WOL', 'Wolfsburg', 'wolfsburg.png'), 
				(2, 'FC Schalke 04', 'S04', 'Schalke', 'schalke.png'),
				(3, 'TSG 1899 Hoffenheim', 'HOF', 'Hoffenheim', 'hoffenheim.png'), 
				(4, 'FC Bayern München', 'FCB', 'Bayern', 'bayern.png'),
				(5, 'Werder Bremen', 'BRE', 'Bremen', 'bremen.png'),
				(6, 'Borussia Mönchengladbach', 'BMG', 'Gladbach', 'gladbach.png'), 
				(7, 'Eintracht Frankfurt', 'FRA', 'Frankfurt', 'frankfurt.png'), 
				(8, '1. FSV Mainz 05', 'MAI', 'Mainz', 'mainz.png'),
				(9, 'SC Freiburg', 'FRE', 'Freiburg', 'freiburg.png'), 
				(10, 'Hamburger SV', 'HSV', 'Hamburg', 'hamburg.png'), 
				(11, 'Hannover 96', 'HAN', 'Hannover', 'hannover.png'), 
				(12, 'Borussia Dortmund', 'BVB', 'Dortmund', 'dortmund.png'), 
				(13, 'VfB Stuttgart', 'STU', 'Stuttgart', 'stuttgart.png'),
				(14, 'Bayer 04 Leverkusen', 'LEV', 'Leverkusen', 'leverkusen.png'), 
				(15, 'FC Augsburg', 'AUG', 'Augsburg', 'augsburg.png'),
				(16, 'Hertha BSC', 'BSC', 'Hertha', 'hertha.png'),
				(17, 'SC Paderborn 07', 'PBO', 'Paderborn', 'paderborn.png'), 
				(18, '1. FC Köln', 'KLN', 'Köln', 'koeln.png'),
				(19, 'Fortuna Düsseldorf', 'DÜS', 'Düsseldorf', 'duesseldorf.png'), 
				(20, 'Eintracht Braunschweig', 'BRA', 'Braunschw.', 'braunschweig.png'),
				(21, 'RB Leipzig', 'LPZ', 'Leipzig', 'leipzig.png'), 
				(22, 'VfR Aalen', 'AAL', 'Aalen', 'aalen.png'),
				(23, 'FC St. Pauli', 'STP', 'St. Pauli', 'pauli.png'),
				(24, 'FC Ingolstadt 04', 'ING', 'Ingolstadt', 'ingolstadt.png'), 
				(25, 'VfL Bochum', 'BOC', 'Bochum', 'bochum.png'), 
				(26, 'SpVgg Greuther Fürth', 'FÜR', 'Fürth', 'fuerth.png'),
				(27, '1. FC Heidenheim 1846', 'HEI', 'Heidenh.', 'heidenheim.png'), 
				(28, 'FSV Frankfurt', 'FRA', 'Frankfurt', 'fsvfrankfurt.png'), 
				(29, '1. FC Nürnberg', 'NÜR', 'Nürnberg', 'nuernberg.png'), 
				(30, 'SV Darmstadt 98', 'DAR', 'Darmstadt', 'darmstadt.png'), 
				(31, 'SV Sandhausen', 'SAN', 'Sandhausen', 'sandhausen.png'),
				(32, 'Karlsruher SC', 'KSC', 'Karlsruhe', 'karlsruhe.png'), 
				(33, '1. FC Union Berlin', 'BER', 'Berlin', 'berlin.png'),
				(34, '1. FC Kaiserslautern', 'FCK', 'Kaisersl.', 'kaiserslautern.png'),
				(35, 'TSV 1860 München', 'MÜN', 'Müchen', 'muenchen.png'), 
				(36, 'Erzgebirge Aue', 'AUE', 'Aue', 'aue.png'),
				(37, 'MSV Duisburg', 'DUI', 'Duisburg', 'duisburg.png'),
				(38, 'Arminia Bielefeld', 'BIE', 'Bielefeld', 'bielefeld.png'),
				(39, 'Stoke City FC', 'STK', 'Stoke City', 'stoke.png'),
				(40, 'Manchester City FC', 'MCI', 'ManCity', 'city.png'),
				(41, 'Arsenal FC', 'ARS', 'Arsenal', 'arsenal.png'),
				(42, 'Sunderland AFC', 'SUN', 'Sunderland', 'sunderland.png'),
				(43, 'Manchester United FC', 'MUN', 'ManUnited', 'manu.png'),
				(44, 'West Ham United FC', 'WHU', 'Westham', 'westham.png'),
				(45, 'FC Southampton', 'SOU', 'Southampton', 'southampton.png'),
				(46, 'Swansea City', 'SWA', 'Swansea', 'swansea.png'),
				(47, 'Leicester City', 'LEI', 'Leicester', 'leicester.png'),
				(48, 'FC Watford', 'WAT', 'Watford', 'watford.png'),
				(49, 'Norwich City', 'NOR', 'Norwich', 'norwich.png'),
				(50, 'West Bromwich Albion', 'WBA', 'Bromwich', 'bromwich.png'),
				(51, 'Tottenham Hotspur FC', 'TOT', 'Tottenham', 'tottenham.png'),
				(52, 'Chelsea FC', 'CHE', 'Chelsea', 'chelsea.png'),
				(53, 'AFC Bournemouth', 'BOU', 'Bournem.', 'bournemouth.png'),
				(54, 'Newcastle United', 'NEW', 'Newcastle', 'newcastle.png'),
				(55, 'Liverpool FC', 'LIV', 'Liverpool', 'liverpool.png'),
				(56, 'Everton FC', 'EVE', 'Everton', 'everton.png'),
				(59, 'Crystal Palace', 'CRY', 'Crystal Palace', 'palace.png'),
				(58, 'Aston Villa FC', 'AVL', 'Aston Villa', 'villa.png'),
				(60, 'Juventus Turin', 'JUV', 'Juventus', 'juventus.png'),
				(61, 'FC Empoli', 'EMP', 'FC Empoli', 'empoli.png'),
				(62, 'Inter Mailand', 'INT', 'Mailand', 'mailand.png'),
				(63, 'Chievo Verona', 'CHI', 'Verona', 'verona.png'),
				(64, 'Lazio Rom', 'LAZ', 'Lazio Rom', 'lazio.png'),
				(65, 'Carpi FC', 'CAR', 'Carpi FC', 'carpi.png'),
				(66, 'AC Florenz', 'FLO', 'AC Florenz', 'florenz.png'),
				(67, 'US Palermo', 'PAL', 'US Palermo', 'palermo.png'),
				(68, 'Udinese Calcio', 'CAL', 'Udinese', 'udinese.png'),
				(69, 'FC Genua', 'GEN', 'FC Genua', 'genua.png'),
				(70, 'Sassuolo', 'SAS', 'Sassuolo', 'calcio.png'),
				(71, 'Sampdoria Genua', 'GEN', 'Genua', 'genua.png'),
				(72, 'FC Bologna', 'BOL', 'FC Bologna', 'bologna.png'),
				(73, 'AC Mailand', 'MIL', 'AC Mailand', 'milan.png'),
				(74, 'Atalanta Bergamo', 'BER', 'Atl. Bergamo', 'bergamo.png'),
				(75, 'Frosinone Calcio', 'FRO', 'Fros. Calcio', 'frosione_calcio.png'),
				(76, 'Hellas Verona', 'VER', 'Verona', 'hellas_verona.png'),
				(77, 'SSC Neapel', 'NEP', 'Neapel', 'neapel.png'),
				(78, 'AS Rom', 'ROM', 'AS Rom', 'rom.png'),
				(79, 'FC Turin', 'TUR', 'FC Turin', 'turin.png'),
				(80, 'FC Malaga', 'MAL', 'FC Malaga', 'malaga.png'),
				(81, 'FC Barcelona', 'FCB', 'Barcelona', 'barcelona.png'),
				(82, 'Celta Vigo', 'VIG', 'Celta Vigo', 'vigo.png'),
				(83, 'Atletico Madrid', 'ATM', 'Atletico', 'atletico.png'),
				(84, 'Rayo Vallecano', 'RAY', 'Vallecano', 'vallecano.png'),
				(85, 'Levante UD', 'LEV', 'Levante', 'levante.png'),
				(86, 'FC Sevilla', 'SEV', 'FC Sevilla', 'sevilla.png'),
				(87, 'Athletic Bilbao', 'BIL', 'Bilbao', 'bilbao.png'),
				(88, 'UD Las Palmas', 'PAL', 'Palmas', 'laspalmas.png'),
				(89, 'Granada CF', 'GRA', 'Granada CF', 'granada.png'),
				(90, 'Sporting Gijon', 'GIJ', 'Gijon', 'gijon.png'),
				(91, 'FC Getafe', 'GET', 'Getafe', 'getafe.png'),
				(92, 'Real Sociedad', 'SOC', 'Sociedad', 'sociedad.png'),
				(93, 'Deportivo La Coruna', 'DLC', 'Deportivo', 'lacoruna.png'),
				(94, 'SD Eibar', 'EIB', 'SD Eibar', 'eibar.png'),
				(95, 'FC Valencia', 'VAL', 'Valencia', 'valencia.png'),
				(96, 'FC Villareal', 'VIL', 'Villareal', 'villareal.png'),
				(97, 'Betis Sevilla', 'BET', 'Betis Sevilla', 'sevilla.png'),
				(98, 'Real Madrid', 'RMA', 'Real', 'real.png'),
				(99, 'Espanyol Barcelona', 'ESP', 'Espanyol', 'esp_barcelona.png'),
				(100, 'Schachtjor Donezk', 'DON', 'Donezk', 'donezk.png'),
				(101, 'Paris St. Germain', 'PSG', 'Paris', 'paris.png'),
				(102, 'Malmö FF', 'MAL', 'Malmö FF', 'malmö.png'),
				(103, 'Benfica Lissabon', 'LIS', 'Lissabon', 'lissabon.png'),
				(104, 'KAA Gent', 'GEN', 'KAA Gent', 'gent.png'),
				(105, 'Zenit St. Petersburg', 'PET', 'Petersburg', 'zenit.png'),
				(106, 'VFL Wolfsburg', 'WOL', 'VFL Wolfsburg', 'wolfsburg.png'),
				(107, 'FC Arsenal', 'ARS', 'Arsenal', 'arsenal.png'),
				(108, 'PSV Eindhoven', 'PSV', 'Eindhoven', 'eindhoven.png'),
				(109, 'Dynamo Kiew', 'KIV', 'Kiew', 'kiew.png'),
				(110, 'Manchester City', 'MCI', 'ManCity', 'city.png'),
				(111, 'Olympique Lyon', 'LYO', 'Lyon', 'lyon.png'),
				(112, 'FC Porto', 'POR', 'Porto', 'porto.png'),
				(113, 'Maccabi Tel Aviv', 'AVI', 'Tel Aviv', 'telaviv.png'),
				(114, 'Olympiakos Piräus', 'PIR', 'Piräus', 'piräus.png'),
				(115, 'Dinamo Zagreb', 'ZAG', 'Zagreb', 'zagreb.png'),
				(116, 'FC BATE Borisov', 'BOR', 'Borisov', 'borisov.png'),
				(117, 'FK Astana', 'AST', 'FK Astana', 'astana.png'),
				(118, 'Galatasaray Istanbul', 'GAL', 'Istanbul', 'galatasaray.png'),
				(119, 'ZSKA Moskau', 'MOS', 'Moskau', 'moskau.png'),
				(120, 'Manchester United', 'MUN', 'Manchester', 'manu.png'),
				(121, 'SG Dynamo Dresden', 'DRE', 'Dresden', 'dresden.png'),
				(122, 'FC Crotone', 'CRO', 'Crotone', 'crotone.png'),
				(123, 'Cagliari Calcio', 'CAG', 'Cagliari', 'cagliari.png'),
				(124, 'Delfino Pescara 1936', 'PES', 'Pescara', 'pescara.png'),
				(125, 'CA Osasuna', 'OSA', 'Osasuna', 'osasuna.png'),
				(126, 'Real Betis', 'BET', 'Betis', 'betis.png'),
				(127, 'Deportivo Alaves', 'ALA', 'Alaves', 'alaves.png'),
				(128, 'CD Leganes', 'LEG', 'Leganes', 'leganes.png'),
				(129, 'Hull City AFC', 'HUL', 'Hull', 'hull.png'),
				(130, 'Burnley FC', 'BUR', 'Burnley', 'burnley.png'),
				(131, 'FC Middlesbrough', 'MID', 'Middlesbrough', 'middlesbrough.png'),
				(132, 'Würzburger Kickers', 'WÜB', 'Würzburg', 'wuerzburg.png'),
				(133, 'FC Basel', 'BAS', 'Basel', 'basel.png'),
				(134, 'Ludogorez Rasgrad', 'RAS', 'Rasgrad', 'rasgrad.png'),
				(135, 'Besiktas Istanbul', 'IST', 'Istanbul', 'besiktas.png'),
				(136, 'Celtic Glasgow', 'GLA', 'Glasgow', 'glasgow.png'),
				(137, 'FK Rostow', 'ROS', 'Rostow', 'rostow.png'),
				(138, 'AS Monaco', 'MON', 'Monaco', 'monaco.png'),
				(139, 'Legia Warschau', 'WAR', 'Warschau', 'warschau.png'),
				(140, 'Sporting Lissabon', 'LIS', 'Lissabon', 'lissabon.png'),
				(141, 'FC Brügge', 'BRÜ', 'Brügge', 'bruegge.png'),
				(142, 'FC Kopenhagen', 'KOP', 'Kopenhagen', 'kopenhagen.png'),
				(143, 'Holstein Kiel', 'KIE', 'Kiel', 'kiel.png'),
                (144, 'Jahn Regensburg', 'REG', 'Regensburg', 'regensburg.png'),
				(145, 'Leverkusen', 'LEV', 'Leverkusen', 'leverkusen.png'),
				(146, 'FC Bayern', 'FCB', 'Bayern', 'bayern.png'),
                (147, 'Bayer Leverkusen', 'LEV', 'Leverkusen', 'leverkusen.png'),
                (148, '1. FC Magdeburg', 'MAG', 'Magdeburg', 'magdeburg.png'),
			    (149, 'VfL Osnabrück', 'OSN', 'Osnabrück', 'osnabrueck.png'),
			    (150, 'SV Wehen Wiesbaden', 'WIS', 'Wiesbaden', 'wiesbaden.png'),
			    (151, 'Karlsruher SC', 'KSC', 'Karlsruhe', 'karlsruhe.png'),
				(152, 'FC Hansa Rostock', 'ROS', 'Rostock', 'rostock.png');
			   ";

        $db->setQuery($query);
        $db->execute();

        $cachefile = JPATH_BASE."/../modules/mod_soccer_results/cache.txt";
        if (is_readable($cachefile)) {
            unlink($cachefile);
        }
    }
}
