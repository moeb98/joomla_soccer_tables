<?php
/**
 * helper.php 
 */

use Joomla\CMS\Factory;

class modSoccerResultsHelper
{
    /**
     * Constructor
     */
    public function __construct($module)
    {
        // Load Bootstrap and JQuery
        JHtml::_('bootstrap.framework');

        $app = Factory::getApplication();
        $document = $app->getDocument();

        $document->addScriptDeclaration('
      jQuery(document).ready(function() {
        change_spieltag_' . $module->id . '();
        jQuery(document).on("change", "#spielplan_' . $module->id . '", change_spieltag_' . $module->id . ');
      });
        
      function change_spieltag_' . $module->id . '() {
        jQuery("#soccer_results_loading_' . $module->id . '").show();
        jQuery.post( "' . JURI::base() . 'index.php",
            {
              option: "com_ajax",
              module: "soccer_results",
              Itemid: "' . $app->getMenu()->getActive()->id . '",
              method: "getErgebnisse",
              format: "json",
              titel: "' . $module->title . '",
              spieltag: jQuery("#spieltag_' . $module->id . ' option:selected").text(),
            },
            function(data){
              jQuery("#soccer_results_loading_' . $module->id . '").hide();
              if (data.success == false) {
                jQuery("#spielplan_' . $module->id . '").html(data.message);
              } else {
                jQuery("#spielplan_' . $module->id . '").html(data.data);
                jQuery(".hasTooltip").tooltip({html: "true"});
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
	      jQuery("#soccer_results_loading_' . $module->id . '").hide();
          if (data.success == false) {
            jQuery("#spielplan_' . $module->id . '").html(data.message);
          } else {
            jQuery("#spielplan_' . $module->id . '").html(data.data);
            jQuery(".hasTooltip").tooltip({html: "true"});
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
        } else {
            return false;
        }
    }

    /**
     * AJAX Endpoint
     */
    public static function getErgebnisseAjax()
    {
        $jinput = Factory::getApplication()->input;
        $module = JModuleHelper::getModule('soccer_results', $jinput->get('titel', 'default_value', 'filter'));
        $db = Factory::getContainer()->get('DatabaseDriver');
        // $db = Factory::getDbo();

        $jparams = new JRegistry();
        $jparams->loadString($module->params);

        $tage = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
        $clrunden = ['', 'Gruppe A', 'Gruppe B', 'Gruppe C', 'Gruppe D', 'Gruppe E', 'Gruppe F', 'Gruppe G', 'Gruppe H', 'Achtelfinale', 'Viertelfinale', 'Halbfinale', 'Finale'];

        // Teams aus der Joomla Tabelle holen
        $query = 'SELECT '.$db->quoteName('bezeichnung_webservice').', '.$db->quoteName('bezeichnung_kurz').', '.$db->quoteName('bezeichnung_mittel').', '.$db->quoteName('dateiname_logo').' FROM '.$db->quoteName('#__soccer_results');
        $db->setQuery($query);
        $teams = $db->loadAssocList('bezeichnung_webservice');
        $liga = $jparams->get('league');

        // Spieltag ermitteln
        if ($jinput->get('spieltag', 'default_value', 'filter') != '') {
            $spieltag = $jinput->get('spieltag', 'default_value', 'filter');
            if (! is_numeric($spieltag)) {
                $spieltag = array_search($spieltag, $clrunden);
            }
        } elseif ($jparams->get('matchday') != 0 && $jparams->get('matchday') != -1) {
            $spieltag = $jparams->get('matchday');
        } else {
            $spieltag = self::fetchdata('https://api.openligadb.de/getcurrentgroup/' .$liga, $jparams->get('timeout'));

            if ($spieltag === false) {
                // Kein Spieltag vom Webservice -> den vom letzten Mal nehmen
                if ($jparams->get('lastCurrentMatchday') != '') {
                    $spieltag = $jparams->get('lastCurrentMatchday');
                } else {
                    $spieltag = 1;
                }
            } else {
                $spieltag = json_decode($spieltag);
                $spieltagsname = $spieltag->groupName;
                $spieltag = $spieltag->groupOrderID;

                if ($liga == 'cl1617') {
                    $spieltag = array_search($spieltagsname, $clrunden);
                    if (! $spieltag || $spieltag < 9) {
                        $spieltag = 1;
                    }
                }

                // Aktueller Spieltag hat sich geändert -> Als Parameter lastCurrentMatchday speichern
                if ($jparams->get('lastCurrentMatchday') != $spieltag) {
                    $jparams->set('lastCurrentMatchday', $spieltag);
                    $module->params = $jparams->toString();
                    $dbtable = JTable::getInstance('module');
                    $dbtable->save((array)$module);
                }
            }
        }

        // Wenn -1 eingestellt dann vorherigen Spieltag anzeigen
        if ($jparams->get('matchday') == '-1' && $spieltag > 1) {
            $spieltag -= 1;
        }

        $saison = $jparams->get('season');

        // Cache lesen
        $cachefile = JPATH_BASE."/modules/mod_soccer_results/cache.txt";
        if (is_readable($cachefile)) {
            $cache = file_get_contents($cachefile);
        } else {
            $cache = self::fetchdata('https://www.fcb-lippach.de/modules/mod_soccer_results/cache.txt', 10);

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
        if (isset($paarungen_cache[$spieltag . $liga . $saison][$lastchange])) {
            $paarungen = $paarungen_cache[$spieltag . $liga . $saison][$lastchange];
        } else {
            // Daten abrufen und in den Cache schreiben
            $paarungen = self::fetchdata('https://api.openligadb.de/getmatchdata/' . $liga . '/' . $saison . '/' . $spieltag, $jparams->get('timeout'));

            if ($paarungen === false || stristr($paarungen, 'Maximale Abfrageanzahl von 1000 Abfragen pro Tag erreicht!') != false) {
                // Webservice nicht erreichbar, prüfen ob Spieltag mit älterem Stand im Cache ist
                if (is_array($paarungen_cache[$spieltag . $liga . $saison])) {
                    $paarungen = reset($paarungen_cache[$spieltag . $liga . $saison]);
                }
            } else {
                $paarungen = json_decode($paarungen);
                unset($paarungen_cache[$spieltag . $liga . $saison]);
                $paarungen_cache[$spieltag . $liga . $saison][$lastchange] = $paarungen;
                file_put_contents($cachefile, serialize($paarungen_cache));
            }
        }

        // Prüfen wie viele Ergebnisse zu diesem Spieltag vorliegen
        $anzahl_ergebnisse = 0;
        $anzahl_live = 0;
        if ($paarungen) {
            $bezeichnung = 'bezeichnung_mittel';
            foreach ($paarungen as $partie) {
                if (isset($partie->matchResults[0])) {
                    $ergebnisse = $partie->matchResults[0];
                    if ($ergebnisse instanceof stdClass) {
                        $anzahl_ergebnisse++;
                        if ($jparams->get('longnames') == '1') {
                            $bezeichnung = 'bezeichnung_mittel';
                        } else {
                            $bezeichnung = 'bezeichnung_kurz';
                        }

                        if ($partie->matchIsFinished == false) {
                            $anzahl_live++;
                        }
                    }
                }
            }
        }

        // Start HTML OUTPUT
        $table = "<table border='0' cellpadding='1' cellspacing='1'>\r\n";

        // Spieltag Dropdown
        if ($liga == 'cl1617') {
            $breite = '100';
        } else {
            $breite = '50';
        }
        $table .= "<tr>\r\n<td align='left' valign='middle' colspan='7' style='padding-bottom:10px;'><nobr>Spieltag:&nbsp;<select style='min-width: " . $breite . "px;' id='spieltag_" . $module->id . "'>";

        if ($liga == 'pl' || $liga == 'sa' || $liga == 'pd') {
            $spieltage = 38;
        } elseif ($liga == 'cl1617') {
            $spieltage = 12;
        } else {
            $spieltage = 34;
        }

        for ($i=1;$i<=$spieltage;$i++) {
            if ($liga == 'cl1617') {
                $anzeige = $clrunden[$i];
            } else {
                $anzeige = $i;
            }

            if ($i == $spieltag && $i == $jparams->get('lastCurrentMatchday')) {
                $table .= "<option value='$i' style='font-weight:bold;' selected='selected'>$anzeige</option>";
            } elseif ($i == $spieltag) {
                $table .= "<option value='$i' selected='selected'>$anzeige</option>";
            } elseif ($i == $jparams->get('lastCurrentMatchday')) {
                $table .= "<option value='$i' style='font-weight:bold;'>$anzeige</option>";
            } else {
                $table .= "<option value='$i'>$anzeige</option>";
            }
        }

        $table .= "</select>&nbsp;&nbsp;&nbsp;<img id='soccer_results_loading_" . $module->id . "' src='".JURI::root()."modules/mod_soccer_results/images/ajax-loader.gif' style='display:none;'>";
        $table .= "</nobr></td>\r\n</tr>\r\n";

        // Live Spiele anzeigen
        if ($anzahl_ergebnisse > 0 && $anzahl_live == 1) {
            $table .= "<tr>\r\n<td align='center' valign='middle' colspan='7'><font color='red'><b>JETZT EIN SPIEL LIVE!</b></font></td>\r\n</tr>\r\n";
        } elseif ($anzahl_ergebnisse > 0 && $anzahl_live > 1) {
            $table .= "<tr>\r\n<td align='center' valign='middle' colspan='7'><font color='red'><b>JETZT ".$anzahl_live." SPIELE LIVE!</b></font></td>\r\n</tr>\r\n";
        }

        if (!$paarungen) {
            $table .= '</table>' . $jparams->get('timeout_error');

            return $table;
        }

        $i = 0;
        $termin = '';
        foreach ($paarungen as $partie) {
            $i++;

            if (trim($partie->team1->teamName) == $jparams->get('meinVerein') ||  trim($partie->team2->teamName) == $jparams->get('meinVerein')) {
                $style = $jparams->get('meinVereinCSS');
            } else {
                $style = '';
            }

            if ($termin != $partie->matchDateTime && !$jparams->get('kompakt')) {
                if ($i==1) {
                    $table .= "<tr>\r\n<td align='left' valign='middle' colspan='7'><b><i>".$tage[date("w", strtotime($partie->matchDateTime))]." ".date("d.m. H:i", strtotime($partie->matchDateTime))." Uhr</i></b></td>\r\n</tr>\r\n";
                } else {
                    $table .= "<tr>\r\n<td align='left' valign='middle' colspan='7' style='padding-top:10px;'><b><i>".$tage[date("w", strtotime($partie->matchDateTime))]." ".date("d.m. H:i", strtotime($partie->matchDateTime))." Uhr</i></b></td>\r\n</tr>\r\n";
                }
            }

            $table .= "<tr style='$style'>\r\n";

            if ($jparams->get('kompakt')) {
                if ($liga == 'cl1617') {
                    $table .= "<td align='left' valign='middle'>".date("d.m.", strtotime($partie->matchDateTime))."</td>\r\n";
                } else {
                    $table .= "<td align='left' valign='middle'>".$tage[date("w", strtotime($partie->matchDateTime))]."</td>\r\n";
                }
            }

            $termin = $partie->matchDateTime;

            // Team 1
            $table .= "<td align='left' valign='middle'><img style='width:20px; height:20px;' title='".$teams[trim($partie->team1->teamName)]['bezeichnung_mittel']."' alt='".$teams[trim($partie->team1->teamName)]['bezeichnung_mittel']."' src='".JURI::root()."modules/mod_soccer_results/images/".$teams[trim($partie->team1->teamName)]['dateiname_logo']."' /></td>\r\n";
            $table .= "<td align='left' valign='middle'>".$teams[trim($partie->team1->teamName)][$bezeichnung]."</td>\r\n";

            $table .= "<td align='left' valign='middle'>-&nbsp;</td>\r\n";

            // Team 2
            $table .= "<td align='left' valign='middle'><img style='width:20px; height:20px;' title='".$teams[trim($partie->team2->teamName)]['bezeichnung_mittel']."' alt='".$teams[trim($partie->team2->teamName)]['bezeichnung_mittel']."' src='".JURI::root()."modules/mod_soccer_results/images/".$teams[trim($partie->team2->teamName)]['dateiname_logo']."' /></td>\r\n";
            $table .= "<td align='left' valign='middle'>".$teams[trim($partie->team2->teamName)][$bezeichnung]."</td>\r\n";

            $tootip_text = "";
            $endergebnis = "";
            $halbzeitergebnis = "";
            if ($anzahl_ergebnisse > 0) {
                $table .= "<td align='left' valign='middle'>";
                $alle_ergebnisse = $partie->matchResults;
                if (isset($alle_ergebnisse[0])) {
                    if (!$partie->matchIsFinished && $alle_ergebnisse[0] instanceof stdClass) {
                        $tootip_text .= "<font color=red>";
                    }
                }
                $table .= "<nobr>&nbsp;";

                if (! is_array($alle_ergebnisse) || count($alle_ergebnisse) == 0) {
                    $tootip_text .= '-:- (-:-)';
                } else {
                    // Halbzeitergebnis / Endergebnis ermitteln
                    foreach ($alle_ergebnisse as $ergebnis) {
                        if ($ergebnis->resultName == 'Endergebnis') {
                            $endergebnis = $ergebnis->pointsTeam1.":".$ergebnis->pointsTeam2;
                        } elseif ($ergebnis->resultName == 'Halbzeitergebnis' || $ergebnis->resultName == 'Halbzeit') {
                            $halbzeitergebnis = " (".$ergebnis->pointsTeam1.":".$ergebnis->pointsTeam2.")";
                        }
                    }
                    if ($endergebnis == '') {
                        $endergebnis = '0:0';
                    }
                    $tootip_text .= $endergebnis . $halbzeitergebnis;

                    $goals = '';
                    foreach ($partie->goals as $goal) {
                        if ($goal->goalGetterName) {
                            if ($goal->matchMinute) {
                                $goals .= '<b>' . $goal->scoreTeam1 . ':' . $goal->scoreTeam2 . '</b>&nbsp;&nbsp;' . $goal->goalGetterName . ' (' . $goal->matchMinute . '.)<br>';
                            } else {
                                $goals .= '<b>' . $goal->scoreTeam1 . ':' . $goal->scoreTeam2 . '</b>&nbsp;&nbsp;' . $goal->goalGetterName . '<br>';
                            }
                        }
                    }
                }
                if (isset($partie->matchIsFinished)) {
                    if (!$partie->matchIsFinished && $alle_ergebnisse[0] instanceof stdClass) {
                        $tootip_text .= "</font>";
                    }
                }

                $tootip_text .= "</nobr>";

                if ($goals <> '') {
                    $table .= JHtml::_('tooltip', $goals, 'Tore', '', $tootip_text);
                } else {
                    $table .= $tootip_text;
                }

                $table .= "</td>\r\n";
            } elseif ($jparams->get('longnames') == '1') {
                $table .= "<td>-:- (-:-)</td>\r\n";
            }
            $table .= "</tr>\r\n";
        }

        $table .= "</table>\r\n";

        return $table;
    }
}
