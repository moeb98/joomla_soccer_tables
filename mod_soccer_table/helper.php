<?php
/**
 * helper.php 
 */

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class modSoccerTableHelper
{
    /**
     * Constructor
     */
    public function __construct($module)
    {

    // Load JQuery
        JHtml::_('jquery.framework');     

        $app = Factory::getApplication();
        $document = Factory::getDocument();
        $document->addScriptDeclaration('
      jQuery(document).ready(function() {
        load_soccer_table_' . $module->id . '();
      });
        
      function load_soccer_table_' . $module->id . '() {
        jQuery("#soccer_table_loading_' . $module->id . '").show();
        jQuery.post( "' . JURI::base() . 'index.php",
            {
              option: "com_ajax",
              module: "soccer_table",
              Itemid: "' . $app->getMenu()->getActive()->id . '",
              method: "get",
              format: "json",
              titel: "' . $module->title . '"
            },
            function(data){
              jQuery("#soccer_table_loading_' . $module->id . '").hide();
              if (data.success == false) {
                jQuery("#soccer_table_' . $module->id . '").html(data.message);
              } else {
                jQuery("#soccer_table_' . $module->id . '").html(data.data);
              }
            }
        ).fail(function(xhr) {
		  try {
			// Ungewollten Output von anderen Plugins wie GoogleAnalytics oder PHP Meldungen wegschneiden
			data = jQuery.parseJSON(xhr.responseText.substring(xhr.responseText.indexOf("success")-2));
		  }
		  catch (e) {
			alert("Fehlerhafter JSON Response - Doku pruefen!");
		  };
          jQuery("#soccer_table_loading_' . $module->id . '").hide();
          if (data.success == false) {
            jQuery("#soccer_table_' . $module->id . '").html(data.message);
          } else {
            jQuery("#soccer_table_' . $module->id . '").html(data.data);
          }
        });
      };
    ');
    }

    /**
     * fetch data from api using curl or file_get_contents
     */
    public static function fetchdata($url, $timeout)
    {
        if (function_exists('curl_version')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            $content = curl_exec($curl);
            curl_close($curl);

            return $content;
        } elseif (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
        'http' => [ 'timeout' => $timeout ]
      ]);

            return file_get_contents($url, 0, $context);
        }

        return false;
    }

    /**
     * AJAX Endpoint
     */
    public static function getAjax()
    {
        $jinput = Factory::getApplication()->input;
        $module = JModuleHelper::getModule('soccer_table', $jinput->get('titel', 'default_value', 'filter'));
        $db = Factory::getContainer()->get('DatabaseDriver');

        $jparams = new JRegistry();
        $jparams->loadString($module->params);

        $context = stream_context_create([
      'http' => [ 'timeout' => $jparams->get('timeout') ]
    ]);

        $liga = $jparams->get('league');

        // Tabelle aus der Joomla Tabelle holen
        $query = 'SELECT '.$db->quoteName('team').', '.$db->quoteName('spiele').', '.$db->quoteName('tore').', '.$db->quoteName('gegentore').', '.$db->quoteName('punkte') .' FROM '.$db->quoteName('#__soccer_table') . 'WHERE modul_id = ' . $module->id . ' ORDER BY punkte DESC, tore-gegentore DESC, tore DESC';

        $db->setQuery($query);
        $tabelle = $db->loadAssocList();

        // Aktuellen Spieltag ermitteln
        $spieltag = self::fetchdata('https://api.openligadb.de/getcurrentgroup/' . $liga, $jparams->get('timeout'));

        if ($spieltag === false) {
            // Kein Spieltag vom Webservice -> den vom letzten Mal nehmen
            if ($jparams->get('lastCurrentMatchday') != '') {
                $spieltag = $jparams->get('lastCurrentMatchday');
            } else {
                $spieltag = 1;
            }
        } else {
            $spieltag = json_decode($spieltag);
            $spieltag = $spieltag->groupOrderID;

            // Aktueller Spieltag hat sich geändert -> Als Parameter lastCurrentMatchday speichern
            if ($jparams->get('lastCurrentMatchday') != $spieltag) {
                $jparams->set('lastCurrentMatchday', $spieltag);
                $module->params = $jparams->toString();
                $dbtable = JTable::getInstance('module');
                $dbtable->save((array)$module);
            }
        }

        // Tabelle aktualisieren falls Refresh-Intervall erreicht
        if (count($tabelle) == 0 || $jparams->get('lastupdate') == '' || ($jparams->get('lastupdate') + ($jparams->get('refresh') * 60) < time())) {
            $paarungen = self::fetchdata('https://api.openligadb.de/getmatchdata/' . $liga . '/' . $jparams->get('season'), $jparams->get('timeout'));

            if ($paarungen != false && stristr($paarungen, 'Maximale Abfrageanzahl von 1000 Abfragen pro Tag erreicht!') == false && stristr($paarungen, 'An error has occurred') == false) {
                $paarungen = json_decode($paarungen);
                $tabelle = [];
                $i = 0;
                foreach ($paarungen as $partie) {
                    $i++;
                    $alle_ergebnisse = $partie->matchResults;
                    if ($alle_ergebnisse[0] instanceof stdClass) {
                        foreach ($alle_ergebnisse as $ergebnis) {
                            if ($ergebnis->resultName == 'Endergebnis') {
                                $tore_team1 = $ergebnis->pointsTeam1;
                                $tore_team2 = $ergebnis->pointsTeam2;

                                break;
                            }
                        }

                        if ($tore_team1 == $tore_team2) {
                            $punkte_team1 = 1;
                            $punkte_team2 = 1;
                        } elseif ($tore_team1 > $tore_team2) {
                            $punkte_team1 = 3;
                            $punkte_team2 = 0;
                        } elseif ($tore_team1 < $tore_team2) {
                            $punkte_team1 = 0;
                            $punkte_team2 = 3;
                        }

                        $tabelle[$partie->team1->teamName] = ['spiele' => $tabelle[$partie->team1->teamName]['spiele'] + 1,
              'punkte' => $tabelle[$partie->team1->teamName]['punkte'] + $punkte_team1,
              'tore' => $tabelle[$partie->team1->teamName]['tore'] + $tore_team1,
              'gegentore' => $tabelle[$partie->team1->teamName]['gegentore'] + $tore_team2];
                        $tabelle[$partie->team2->teamName] = ['spiele' => $tabelle[$partie->team2->teamName]['spiele'] + 1,
              'punkte' => $tabelle[$partie->team2->teamName]['punkte'] + $punkte_team2,
              'tore' => $tabelle[$partie->team2->teamName]['tore'] + $tore_team2,
              'gegentore' => $tabelle[$partie->team2->teamName]['gegentore'] + $tore_team1];
                    } elseif ($i<10) {
                        $tabelle[$partie->team1->teamName] = ['spiele' => 0, 'punkte' => 0, 'tore' => 0, 'gegentore' => 0];
                        $tabelle[$partie->team2->teamName] = ['spiele' => 0, 'punkte' => 0, 'tore' => 0, 'gegentore' => 0];
                        if ($i == 9) {
                            break;
                        }
                    }
                }

                if ($module->id) {
                    $sql = 'DELETE FROM '.$db->quoteName('#__soccer_table') . ' WHERE modul_id = ' . $module->id;
                    $db->setQuery($sql);
                    $db->execute();
                }

                foreach ($tabelle as $name=>$team) {
                    $sql = sprintf("INSERT INTO " .$db->quoteName('#__soccer_table'). "(team, spiele, tore, gegentore, punkte, modul_id) VALUES('%s',%s,%s,%s,%s,%s)", $name, $team['spiele'], $team['tore'], $team['gegentore'], $team['punkte'], $module->id);
                    $db->setQuery($sql);
                    $db->execute();
                }

                if (isset($jparams) && isset($module)) {
                    // set last update param
                    $jparams->set('lastupdate', time());
                    $module->params = $jparams->toString();
                    $table = JTable::getInstance('module');
                    $table->save((array)$module);
                }

                $db->setQuery($query);
                $tabelle = $db->loadAssocList();
            }
        }

        // Live Spiele laden
        if ($jparams->get('live') == '1') {

      // Paarungen abrufen
            $saison = $jparams->get('season');

            // Cache lesen
            $cachefile = JPATH_BASE."/modules/mod_soccer_table/cache.txt";
            if (is_readable($cachefile)) {
                $cache = file_get_contents($cachefile);
            } else {
                $timeout = stream_context_create([
          'http' => [ 'timeout' => 10 ]
        ]);

                $cache = self::fetchdata('https://www.fcb-lippach.de/modules/mod_soccer_table/cache.txt', $jparams->get('timeout'));

                if ($cache != false) {
                    file_put_contents($cachefile, $cache);
                }
            }
            $paarungen_cache = unserialize($cache);

            // Letzte Änderung ermitteln
            $lastchange = self::fetchdata('https://api.openligadb.de/getlastchangedate/' . $liga . '/' . $saison . '/' . $spieltag, $jparams->get('timeout'));

            if ($lastchange === false) {
                // Kein Datum vom Webservice -> Datum aus dem Cache holen
                if ($paarungen_cache[$spieltag . $liga . $saison]) {
                    $lastchange = array_keys($paarungen_cache[$spieltag . $liga . $saison]);
                    $lastchange = $lastchange[0];
                }
            } else {
                $lastchange = strtotime(json_decode($lastchange));
            }

            // Spieltag mit diesem Stand schon im Cache?
            if ($paarungen_cache[$spieltag . $liga . $saison][$lastchange]) {
                $paarungen = $paarungen_cache[$spieltag . $liga . $saison][$lastchange];
            } else {
                // Daten abrufen und in den Cache schreiben
                $paarungen = self::fetchdata('https://api.openligadb.de/getmatchdata/' . $liga . '/' . $saison . '/' . $spieltag, $jparams->get('timeout'));

                if ($paarungen != false && stristr($paarungen, 'Maximale Abfrageanzahl von 1000 Abfragen pro Tag erreicht!') == false) {
                    $paarungen = json_decode($paarungen);
                    unset($paarungen_cache[$spieltag . $liga . $saison]);
                    $paarungen_cache[$spieltag . $liga . $saison][$lastchange] = $paarungen;
                    file_put_contents($cachefile, serialize($paarungen_cache));
                }
            }

            // LIVE Spiele ermitteln
            $liveteams = [];
            foreach ($paarungen as $partie) {
                if (isset($partie->matchResults[0])) {
                    $ergebnisse = $partie->matchResults[0];
                    if ($ergebnisse instanceof stdClass) {
                        if ($partie->matchIsFinished == false) {
                            $liveteams[] = $partie->team1->teamName;
                            $liveteams[] = $partie->team2->teamName;
                        }
                    }
                }
            }
        }

        // Bezeichnung Webservice => Bezeichnung in Tabelle
        $ersetzen = [
      'FC Bayern München' => 'Bayern',
      'Bayer 04 Leverkusen' => 'Leverkusen',
      'FC Bayern' => 'Bayern',
      'Bayer Leverkusen' => 'Leverkusen',
      'Borussia Dortmund' => 'Dortmund',
      'FC Schalke 04' => 'Schalke',
      'Borussia Mönchengladbach' => 'Gladbach',
      'VfL Wolfsburg' => 'Wolfsburg',
      '1. FSV Mainz 05' => 'Mainz',
      'Hertha BSC' => 'Hertha',
      'FC Augsburg' => 'Augsburg',
      'Hannover 96' => 'Hannover',
      'TSG 1899 Hoffenheim' => 'Hoffenheim',
      'TSG Hoffenheim' => 'Hoffenheim',
      'Eintracht Frankfurt' => 'Frankfurt',
      'Werder Bremen' => 'Bremen',
      'SV Werder Bremen' => 'Bremen',
      'VfB Stuttgart' => 'Stuttgart',
      'SC Freiburg' => 'Freiburg',
      '1. FC Nürnberg' => 'Nürnberg',
      'Hamburger SV' => 'Hamburg',
      'Eintracht Braunschweig' => 'Braunschweig',
      'Energie Cottbus' => 'Cottbus',
      'Arminia Bielefeld' => 'Bielefeld',
      'Karlsruher SC' => 'Karlsruhe',
      '1. FC Kaiserslautern' => 'Lautern',
      'VfL Bochum' => 'Bochum',
      'SG Dynamo Dresden' => 'Dresden',
      '1. FC Köln' => 'Köln',
      'Erzgebirge Aue' => 'Aue',
      'FC Ingolstadt 04' => 'Ingolstadt',
      'SC Paderborn 07' =>  'Paderborn',
      'SV Sandhausen' => 'Sandhausen',
      'VfR Aalen' => 'Aalen',
      'Fortuna Düsseldorf' => 'Düsseldorf',
      'FC St. Pauli' => 'St. Pauli',
      'SpVgg Greuther Fürth' => 'Fürth',
      '1. FC Union Berlin' => 'Berlin',
      'FSV Frankfurt' => 'FSV Frankfurt',
      'SV Darmstadt 98' => 'Darmstadt',
      '1. FC Heidenheim 1846' => 'Heidenheim',
      'RB Leipzig' => 'Leipzig',
      'MSV Duisburg' => 'Duisburg',
      'Arminia Bielefeld' => 'Bielefeld',
      'Jahn Regensburg' => 'Regensburg',
      'Holstein Kiel' => 'Kiel',
      'SG Dynamo Dresden' => 'Dresden',
      '1. FC Magdeburg' => 'Magdeburg',
      'VfL Osnabrück' => 'Osnabrück',
      'SV Wehen Wiesbaden' => 'Wiesbaden',
      'Würzburger Kickers' => 'Würzburg',
      'FC Hansa Rostock' => 'Rostock',
    ];

        if (count($tabelle) == 0) {
            throw new Exception('Zurzeit können keine Daten vom Webservice abgerufen werden :-(');
        }

        $platz = 1;
        $style = 'text-align:right; vertical-align:middle; margin-right:2px;';
        $htmloutput = '<table style="width: 100%; border-collapse: collapse;"><thead><tr><th style="'.$style.'">Pl.</th><th colspan=2" style="text-align:left; vertical-align: middle; margin-right:2px;">Team</th><th style="'.$style.'">Sp.</th><th style="'.$style.'">Tore</th><th style="'.$style.'">Pkt</th></tr></thead><tbody>';

        foreach ($tabelle as $row) {
            $diff = (int) $row['tore'] - (int) $row['gegentore'];

            if ($jparams->get('live') == '1' && in_array($row['team'], $liveteams)) {
                $tdstyle = 'text-align:right; color:red; vertical-align:middle; margin-right:2px;';
            } else {
                $tdstyle = 'text-align:right; vertical-align:middle; margin-right:2px;';
            }

            if ($row['team'] == $jparams->get('meinVerein')) {
                $trstyle = $jparams->get('meinVereinCSS');
            } else {
                $trstyle = '';
            }

            if ($jparams->get('league') == 'bl1' && ($platz == 4 || $platz == 5 || $platz ==  6 || $platz == 15 || $platz == 16) ||
        $jparams->get('league') == 'bl2' && ($platz == 2 || $platz == 3 || $platz ==  15 || $platz == 16)) {
                $tdstyle .= ' border-bottom: 1px solid #A6A6A6;';
            }

            $htmloutput .= '<tr style="' . $trstyle . '"><td style="'.$tdstyle.'"><b>' .$platz . '&nbsp;</b></td><td style="'.$tdstyle.'"><img style="padding-right:5px;" border="0" title="'.$ersetzen[$row['team']].'" alt="'.$ersetzen[$row['team']].'" src="'.JURI::root().'modules/mod_soccer_table/images/' . strtolower(str_replace(['ü', 'ä', 'ö', ' '], ['ue', 'ae', 'oe', ''], $ersetzen[$row['team']])) . '.png"></td><td style="'.$tdstyle.' text-align:left !important;">' . $ersetzen[$row['team']] . '</td><td style="'.$tdstyle.'">' . $row['spiele'] . '</td><td style="'.$tdstyle.'">' . $diff . '</td><td style="'.$tdstyle.'">' . $row['punkte'] . '</td></tr>';

            $platz++;
        }

        $htmloutput .= '</tbody></table>';

        return $htmloutput;
    }
}
