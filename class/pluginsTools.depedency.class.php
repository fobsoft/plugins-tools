<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

class pluginsToolsDepedency {
  public function purgeLog(&$_eqLogic) {
    $maxLineLog = config::byKey('maxLineLog', __CLASS__, 500);
    $path =       dirname(__FILE__) . '/../../../../log/pluginLog/plugin' . $_eqLogic -> getId() . '.log';

    if (file_exists($path)) {
      try {
        com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $path . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $path . ')" > ' . $path);
      } 
      catch (\Exception $e) {
      }
    }
  }  

  public static function mkdirPath($_dirName, $_fileName) {
    $path = log::getPathToLog($_dirName);
  
    if (!file_exists($path))
      mkdir($path);

    return $path . '/' . $_fileName . '.log'; 
  }  
  
  public static function incLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    if ($_log != '')
      pluginsTools::addLog($_eqLogic, $_typeLog, $_log, $_level);
    
    //if ($_eqLogic -> getConfiguration('logLevel', 'default') == 'advanced')
    $_eqLogic -> _parentLog++;
  }

  public static function unIncLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    //if ($_eqLogic -> getConfiguration('logLevel', 'default') == 'advanced')
    $_eqLogic -> _parentLog--;
    
    if ($_log != '')
      pluginsTools::addLog($_eqLogic, $_typeLog, $_log, $_level);
  }

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    pluginsTools::addLog($_eqLogic, $_typeLog, $_log, $_level);
  }  
  
  public static function addLog(&$_eqLogic, $_typeLog, $_log, $_level = 'debug') {
    if ($_typeLog == 'ERROR')
      $_level = 'danger';
    elseif ($_typeLog == 'DEBUG2') {
      if ($_eqLogic -> getConfiguration('logLevel', 'default') == 'advanced')
        $_typeLog = 'DEBUG';
      else
        return;
    }
    
    $logResult = array();
    foreach ((!is_array($_log)? (array)$_log:$_log) as $log)
      $logResult = array_merge($logResult, explode("\n", $log));
      
    $_eqLogic -> _log[] = array('dateLog' => date('Y-m-d H:i:s'), 'typeLog' => $_typeLog, 'level' => $_level, 'padLog' => $_eqLogic -> _parentLog, 'log' => $logResult);
  }

  public static function addArrayLog(&$_eqLogic, $_typeLog, $_log, $_display, $_title = '', $_level = 'debug') {
    if ($_typeLog == 'ERROR')
      $_level = 'danger';
    elseif ($_typeLog == 'DEBUG2') {
      if ($_eqLogic -> getConfiguration('logLevel', 'default') == 'advanced')
        $_typeLog = 'DEBUG';
      else
        return;
    }
    
    if ($_title != '')
      pluginsTools::incLog($_eqLogic, $_typeLog, $_title);
    
    foreach ($_log as $key => $row) {
      $logResult = $_display;
      $logResult = str_replace('{key}', $key, $logResult);
      $logResult = str_replace(array_keys($row), array_values($row), $logResult);
      $_eqLogic -> _log[] = array('dateLog' => date('Y-m-d H:i:s'), 'typeLog' => $_typeLog, 'level' => $_level, 'padLog' => $_eqLogic -> _parentLog, 'log' => $logResult);
    }
    
    if ($_title != '')
      pluginsTools::incLog($_eqLogic, $_typeLog, '');    
  }  
  
  /*
   'tpiCode' =>  array ('Name' =>           __('Code', __FILE__), 
                        'IsVisible' =>      1, 
                        'IsHistorized' =>   1, 
                        'Type' =>           'info', 
                        'SubType' =>        'string', 
                        'Unite' =>          null,
                        'Value' =>          null,
                        'Alert' =>          array(),
                        'Generic_type' =>   'ALARM_STATUS_CODE', 
                        'Configuration' =>  array('minValue' => '', 'maxValue' => '', 'value' => $_tpiCode, 'repeatEventManagement' => 'always'), 
                        'Template' =>       array('dashboard' => 'core::default', 'mobile' => 'core::default'),
                        'Display' =>        array('message_placeholder' => 'code', 'title_disable' => 1, 'showStatsOnmobile' => 0, 'showStatsOndashboard' => 0, 'invertBinary' => 0, 'icon' => ''))  
  */
  
  public static function setCmdList(&$_eqLogic, $_listCmdToCreated) {
    pluginsTools::incLog($_eqLogic, 'DEBUG', 'Configuration de la liste des commandes');
    
    $eqLogicId =      $_eqLogic -> getId();
    $keyNotUpdated =  array('IsVisible', 'IsHistorized', 'Generic_type');
   
    foreach ($_listCmdToCreated as $logicalId => $configInfos) {      
      pluginsTools::incLog($_eqLogic, 'DEBUG', 'Commande '.$logicalId);
      pluginsTools::incLog($_eqLogic, 'DEBUG', 'Search commande '.$logicalId);
      
      $nbwCmd = false;
      $cmd = cmd::byEqLogicIdAndLogicalId($eqLogicId, $logicalId);// $_eqLogic -> getCmd(null, $logicalId);
      //if (!is_object($cmd)) {
      //  pluginsTools::incLog($_eqLogic, 'DEBUG','Comande not found, search commande by name:'.$configInfos['Name']);
      //  $cmd = cmd::byEqLogicIdCmdName($eqLogicId, $configInfos['Name']);
      //}
      
      if (!is_object($cmd)) {
        pluginsTools::addLog($_eqLogic, 'DEBUG','Comande not found...');
        foreach ($configInfos['ListLogicalReplace'] as $idReplace) {
          pluginsTools::incLog($_eqLogic, 'DEBUG','Search commande replace name '.$idReplace);
          $cmd = cmd::byEqLogicIdAndLogicalId($eqLogicId, $idReplace);
          if (is_object($cmd)) {
            pluginsTools::unIncLog($_eqLogic, 'DEBUG','Comande found...');
            break;
          }
          else
            pluginsTools::unIncLog($_eqLogic, 'DEBUG','Comande not found...');
        }
      }
      else
        pluginsTools::addLog($_eqLogic, 'DEBUG','Comande found...');
      
      pluginsTools::unIncLog($_eqLogic, 'DEBUG', 'Search commande '.$logicalId);

      if (isset($configInfos)) {
        if (!is_object($cmd)) {
          pluginsTools::incLog($_eqLogic, 'DEBUG','Create '.$logicalId.' cmd on eqLogicId '.$eqLogicId);
          $nbwCmd = true;
          $cmd = new cmd();
        }
        else
          pluginsTools::incLog($_eqLogic, 'DEBUG','Update '.$logicalId.' cmd');

        $cmd -> setEqLogic_id($eqLogicId);
        $cmd -> setLogicalId($logicalId);
        
        foreach ($configInfos as $key => $value) {
          if ($key == 'ListLogicalReplace')
            continue;

          if (isset($value)) {
            $key = ucfirst($key);
            
            if (is_array($value)) {
              pluginsTools::incLog($_eqLogic, 'DEBUG','set '.$key);
              foreach ($value as $arrKey => $arrValue) {
                if (isset($arrValue)) {
                  if (is_array($arrValue) || strpos($arrValue, '#') === false) {
                    pluginsTools::setLog($_eqLogic, 'DEBUG','set'.$arrKey.' with value:'.json_encode($arrValue));
                    $cmd -> {'set'.$key}($arrKey, $arrValue);
                  }
                  else {
                    if (isset($keyNotUpdated[$key]) && in_array($arrKey, $keyNotUpdated[$key]) && !$nbwCmd)
                      continue;
                    
                    pluginsTools::setLog($_eqLogic, 'DEBUG','set'.$$arrKey.' with value:'.$_listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                    $cmd -> {'set'.$key}($arrKey, $_listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                  }
                }
              }
              pluginsTools::unIncLog($_eqLogic, 'DEBUG','set '.$key);
            }
            else {
              if (in_array($key, $keyNotUpdated) && !$nbwCmd)
                continue;
          
              if (strpos($value, '#') === false) {
                pluginsTools::setLog($_eqLogic, 'DEBUG','set '.$key.' with value:'.$value);
                $cmd -> {'set'.$key}($value);
              }
              else {
                pluginsTools::setLog($_eqLogic, 'DEBUG','set '.$key.' with value:'.$_listCmdToCreated[str_replace('#', '', $value)]['id']);
                $cmd -> {'set'.$key}($_listCmdToCreated[str_replace('#', '', $value)]['id']);
              }
            }
          }
        }
        pluginsTools::setLog($_eqLogic, 'DEBUG','set EqType with value:'.$_eqLogic -> getEqType_name());
        $cmd -> setEqType($_eqLogic -> getEqType_name());
        pluginsTools::setLog($_eqLogic, 'DEBUG','set Order with value:'.$_eqLogic -> _orderCreationCmd);
        $cmd -> setOrder($_eqLogic -> _orderCreationCmd++);
        $cmd -> save();

        pluginsTools::unIncLog($_eqLogic, 'DEBUG');

        if (isset($configInfos['Configuration']['value']) && $configInfos['Configuration']['value'] != '' && $configInfos['Type'] == 'info')
          $cmd -> event($configInfos['Configuration']['value']);
        
        pluginsTools::unIncLog($_eqLogic, 'DEBUG');
        
        $_listCmdToCreated[$logicalId]['id'] = $cmd -> getId();
      }
      elseif (is_object($cmd))
        $cmd -> remove();
    }
    pluginsTools::unIncLog($_eqLogic, 'DEBUG', 'Set comment for list');
  }   
  
  public function persistLog(&$_eqLogic) {
    if (count($_eqLogic -> _log) > 0) {
      $logMessage = '';
      $path =       null;
      
      if ($_eqLogic -> getConfiguration('logmode', 'default') == 'none')
        return;   
            
      foreach ($_eqLogic -> _log as $keyDetail => $detailLog) {
        if ($detailLog['typeLog'] == 'INC_LOG')
          $logMessage .= "\n".$_eqLogic -> waitCache($detailLog['log'][0]);
        else {
          $prefixLog =  '['.$detailLog['dateLog'].']['.$detailLog['typeLog'].']'.str_pad("",8-strlen($detailLog['typeLog'])," ");
          
          foreach ($detailLog['log'] as $keyLog => $log) {
            $log = preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x\1;', is_array($log)? json_encode($log):$log);
            
            if ($detailLog['level'] != '')
              $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").'<label class="'.$detailLog['level'].'" style="margin-bottom: 4px;">'.$log.'</label>';
            else
              $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").$log;
  
            log::add($_eqLogic -> _className, $detailLog['typeLog'], str_pad("", $detailLog['padLog']*3, " ").$log);
          }
        }
      }
      
      if (isset($_eqLogic -> _externalLog) && $_eqLogic -> _externalLog)
        cache::set($_eqLogic -> _nodeGenKey, $logMessage);
      else
        file_put_contents(pluginsTools::mkdirPath('pluginLog', 'plugin'.$_eqLogic -> getId()), $logMessage, FILE_APPEND);
    }
    $_eqLogic -> _log = array();
  }

	public function fullDataObject(&$_eqLogic, $_restrictSearch = array(), $_searchOnChildObject = true, $_onlyVisible = false) {
    //pluginsTools::setLog($_eqLogic, 'DEBUG2','fullDataObject :: '.json_encode($_restrictSearch, JSON_UNESCAPED_UNICODE).' searchOnChildObject:'.$_searchOnChildObject);
    
    $_restrictSearch['level'] =               !isset($_restrictSearch['level'])?              7:$_restrictSearch['level'];    //1- Object, 2- Eq, 4- Cmd
    $_restrictSearch['object'] =              !isset($_restrictSearch['object'])?             array():$_restrictSearch['object'];
    $_restrictSearch['genericTypeObject'] =   !isset($_restrictSearch['genericTypeObject'])?  array():$_restrictSearch['genericTypeObject'];
    $_restrictSearch['eqLogic'] =             !isset($_restrictSearch['eqLogic'])?            array():$_restrictSearch['eqLogic'];
    $_restrictSearch['genericTypeEqLogic'] =  !isset($_restrictSearch['genericTypeEqLogic'])? array():$_restrictSearch['genericTypeEqLogic'];
    $_restrictSearch['plugin'] =              !isset($_restrictSearch['plugin'])?             array():$_restrictSearch['plugin'];
    $_restrictSearch['cmd'] =                 !isset($_restrictSearch['cmd'])?                array():$_restrictSearch['cmd'];
    $_restrictSearch['genericTypeCmd'] =      !isset($_restrictSearch['genericTypeCmd'])?     array():$_restrictSearch['genericTypeCmd'];
    
		$return = array();
    foreach (jeeObject::all(true, true) as $object) {
      //pluginsTools::setLog($_eqLogic, 'DEBUG2','object :: '.$object->getId().' '.$object->getName().':::'.count($_restrictSearch['object']));

      // On valide que l'on a pas de restriction au niveau de l'object ou que celui-ci figure dans la restriction
      if (count($_restrictSearch['object']) == 0 || in_array($object->getId(),$_restrictSearch['object']) || in_array(-1,$_restrictSearch['object'])) {

        // Si on doit rechercher dans les enfant et qu'il y a restriction de l'object
        if ($_searchOnChildObject && isset($_restrictSearch['object'])) {
          //pluginsTools::incLog($_eqLogic, 'DEBUG2','On recherche ses enfants');
          
          $objectChildList = $object->getChild($_onlyVisible);

          if (is_array($objectChildList) && count($objectChildList) > 0) {
            foreach ($objectChildList as $objectChild) {
              //pluginsTools::setLog($_eqLogic, 'DEBUG2','Add child '.$objectChild->getName().' ('.$objectChild->getId().') element on _restrictSearch');
              array_push($_restrictSearch['object'], $objectChild->getId());
            }
          }
          //pluginsTools::unIncLog($_eqLogic, 'DEBUG2','Search child');
        }

        if (count($_restrictSearch['genericTypeObject']) == 0 || in_array($object->getGeneric_type(), $_restrictSearch['genericTypeObject'])) {
          $current_object = utils::o2a($object);
          
          $object_return['id'] =            $current_object['id'];
          $object_return['name'] =          $current_object['name'];
          $object_return['isVisible'] =     $current_object['isVisible'];
          $object_return['isVisible'] =     $current_object['isVisible'];
          $object_return['humanName'] =     '['.$current_object['name'].']';
          $object_return['listEqLogic'] =   array();
          
          // Recherche des commandes des equipements trouvee
          if ($_restrictSearch['level'] > 1) {
            foreach ($object -> getEqLogic(false, false) as $eqLogic) {
              $eqGenericType =  $eqLogic->getGenericType();
              $eqPluggin =      $eqLogic->getEqType_name();

              // Ajouter la restriction genericTypeEqLogic
              if (count($_restrictSearch['eqLogic']) != 0 && !in_array($eqLogic->getId(), $_restrictSearch['eqLogic']))
                continue;//pluginsTools::setLog($_eqLogic, 'DEBUG2','L\équipement '.$eqLogic->getHumanName().' n\'est pas dans la liste de ceux recherchées');
              elseif (count($_restrictSearch['genericTypeEqLogic']) != 0 && !in_array($eqGenericType, $_restrictSearch['genericTypeEqLogic']))
                continue;//pluginsTools::setLog($_eqLogic, 'DEBUG2','Le type générique de l\'équipement '.$eqLogic->getHumanName().' ('.$eqGenericType.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['genericTypeEqLogic']).')');
              elseif (count($_restrictSearch['plugin']) != 0 && !in_array($eqPluggin, $_restrictSearch['plugin']))
                continue;//pluginsTools::setLog($_eqLogic, 'DEBUG2','Le plugin de l\'équipement '.$eqLogic->getHumanName().' ('.$eqPluggin.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['plugin']).')');
              else {
                //pluginsTools::setLog($_eqLogic, 'DEBUG2','Check eq '.$eqLogic->getHumanName().'('.$eqLogic->getId().')');
                
                $current_eqLogic = utils::o2a($eqLogic);
                
                $eqLogic_return['objectId'] =         $object_return['id'];
                $eqLogic_return['objectHumanName'] =  $object_return['humanName'];
                $eqLogic_return['id'] =               $current_eqLogic['id'];
                $eqLogic_return['name'] =             $current_eqLogic['name'];
                $eqLogic_return['genericType'] =      $current_eqLogic['generic_type'];
                $eqLogic_return['eqTypeName'] =       $current_eqLogic['eqType_name'];
                $eqLogic_return['isVisible'] =        $current_eqLogic['isVisible'];
                $eqLogic_return['isEnable'] =         $current_eqLogic['isEnable'];
                $eqLogic_return['humanName'] =        $eqLogic->getHumanName();
                $eqLogic_return['status'] =           $current_eqLogic['status'];
                $eqLogic_return['listCmd'] =          array();

                if ($_restrictSearch['level'] & 4) {
                  foreach ($eqLogic->getCmd() as $cmd) {
                    $cmdGenericType = $cmd->getGeneric_type();
                    
                    if (count($_restrictSearch['cmd']) != 0 && !in_array($cmd->getId(), $_restrictSearch['cmd']))
                      continue;//pluginsTools::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': La commande n\'est pas dans la liste de celles recherchées');
                    elseif (count($_restrictSearch['genericTypeCmd']) != 0 && !in_array($cmdGenericType, $_restrictSearch['genericTypeCmd']))
                      continue;//pluginsTools::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': Le type générique de la commande ('.$cmdGenericType.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['genericTypeCmd']).')');
                    else {
                      //pluginsTools::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': Ajout de la commande à la liste');
                      
                      $current_cmd = utils::o2a($cmd);
                      
                      $cmd_return['objectId']           = $object_return['id'];
                      $cmd_return['objectHumanName']    = $object_return['humanName'];
                      $cmd_return['eqLogicId']          = $eqLogic_return['id'];
                      $cmd_return['eqLogicHumanName']   = $eqLogic_return['humanName'];
                      $cmd_return['id']                 = $current_cmd['id'];
                      $cmd_return['generic_type']       = $current_cmd['generic_type'];
                      $cmd_return['eqType']             = $current_cmd['eqType'];
                      $cmd_return['name']               = $current_cmd['name'];
                      $cmd_return['type']               = $current_cmd['type'];
                      $cmd_return['subType']            = $current_cmd['subType'];
                      $cmd_return['eqLogic_id']         = $current_cmd['eqLogic_id'];
                      $cmd_return['isHistorized']       = $current_cmd['isHistorized'];
                      $cmd_return['unite']              = $current_cmd['unite'];
                      $cmd_return['value']              = $current_cmd['value'];
                      $cmd_return['isVisible']          = $current_cmd['isVisible'];
                      $cmd_return['humanName']          = $cmd -> getHumanName();
                      
                      if ($cmd_return['type'] == 'info')
                        $cmd_return['state'] = $cmd->execCmd();
                      
                      if ($_restrictSearch['level'] > 4)
                        $eqLogic_return['listCmd'][] = $cmd_return;
                      else
                        $return[] = $cmd_return;
                    }
                  }
                }

                if (!($_restrictSearch['level'] & 4) || count($eqLogic_return['listCmd']) > 0) {
                  if ($_restrictSearch['level'] & 2) {
                    if ($_restrictSearch['level'] & 1)
                      $object_return['listEqLogic'][] = $eqLogic_return;
                    else
                      $return[] = $eqLogic_return;
                  }
                  elseif ($_restrictSearch['level'] & 1 && count($eqLogic_return['listCmd']) > 0)
                    $object_return['listCmd'] = isset($object_return['listCmd'])? array_merge($object_return['listCmd'], $eqLogic_return['listCmd']):$eqLogic_return['listCmd'];
                }
              }
            }
          }

          if ($_restrictSearch['level'] & 1 
              && ($_restrictSearch['level'] == 1 || isset($object_return['listCmd']) || count($object_return['listEqLogic']) > 0))
            $return[] = $object_return;
        }      
			}
      //pluginsTools::setLog($_eqLogic, 'DEBUG2','object :: '.$object->getId().' '.$object->getName());
		}
    //pluginsTools::setLog($_eqLogic, 'DEBUG2','fullDataObject :: return'.json_encode($return, JSON_UNESCAPED_UNICODE));
    
		return $return;
	}  
}
?>