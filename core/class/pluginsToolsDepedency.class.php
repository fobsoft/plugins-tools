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

abstract class pluginsToolsDepedencyConst {
  const TypeLogPriority =  array('ND' => 0, 'DEBUG' => 1, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3);
}

class pluginsToolsDepedency {
  public function purgeLog(&$_eqLogic) {
    $maxLineLog = config::byKey('maxLineLog', $_eqLogic -> getProtectedValue('className'), 5000);
    $path =       dirname(__FILE__) . '/../../../../log/pluginLog/plugin' . $_eqLogic -> getId() . '.log';

    pluginsToolsDepedency::addLog($_eqLogic, 'DEBUG', 'purgeLog off '.$path.' for max '.$maxLineLog.'line');
    if (file_exists($path)) {
      try {
        com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $path . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $path . ')" > ' . $path);
      } 
      catch (\Exception $e) {
      }
    }
  }  

  public static function mkdirPath(&$_eqLogic, $_dirName, $_fileName) {
    $path =       log::getPathToLog($_dirName);
    $filePath =   $path . '/' . $_fileName . '.log';
    $maxLineLog = config::byKey('maxLineLog', $_eqLogic -> getProtectedValue('className'), 5000);
  
    if (!file_exists($path))
      mkdir($path);
    elseif (file_exists($filePath))
      com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $filePath . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $filePath . ')" > ' . $filePath);

    return $filePath;
  } 
  
  public static function incLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    $objCall = $_eqLogic -> getProtectedValue('objCall', null);
    //if ($_eqLogic -> getProtectedValue('externalLog',0) != 0 && isset($objCall))
    if (isset($objCall) && is_object($objCall) && method_exists($objCall, 'increaseParentLog'))
      pluginsToolsDepedency::incLog($objCall, $_typeLog, $_log, $_level);
    else {
      //if ($_log != '')
        pluginsToolsDepedency::setLog($_eqLogic, $_typeLog, $_log, $_level);
    
      if (method_exists($_eqLogic, 'increaseParentLog'))
        $_eqLogic -> increaseParentLog();
    }
  }

  public static function unIncLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    $objCall = $_eqLogic -> getProtectedValue('objCall', null);
    //if ($_eqLogic -> getProtectedValue('externalLog',0) != 0 && isset($objCall))
    if (isset($objCall) && is_object($objCall) && method_exists($objCall, 'decreaseParentLog'))
      pluginsToolsDepedency::unIncLog($objCall, $_typeLog, $_log, $_level);
    else {
      if (method_exists($_eqLogic, 'decreaseParentLog'))
        $_eqLogic -> decreaseParentLog();
    
      //if ($_log != '')
        pluginsToolsDepedency::setLog($_eqLogic, $_typeLog, $_log, $_level);
    }
  }

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = 'debug') {
    if ($_log != '') {
      $objCall = $_eqLogic -> getProtectedValue('objCall');

      //if ($_eqLogic -> getProtectedValue('externalLog',0) == 0 && isset($objCall) && is_object($objCall) && method_exists($objCall, 'addLog'))
      if (isset($objCall) && is_object($objCall) && method_exists($objCall, 'addLog'))
        pluginsToolsDepedency::setLog($objCall, $_typeLog, $_log, $_level);
      elseif (method_exists($_eqLogic, 'addLog'))
        pluginsToolsDepedency::addLog($_eqLogic, $_typeLog, $_log, $_level);
    }
    return true;
  }  
  
  public static function addLog(&$_eqLogic, $_typeLog, $_log, $_level = 'debug') {
    if ($_typeLog == 'ERROR')
      $_level = 'danger';
    elseif ($_typeLog == 'DEBUG2') {
      if ($_eqLogic -> getProtectedValue('logLevel', $_eqLogic -> getConfiguration('logLevel', 'default')) == 'advanced') {
        $_typeLog = 'DEBUG';
        $_level =   '';
      }
      else
        return;
    }
   
    $logResult = array();
    foreach ((!is_array($_log)? (array)$_log:$_log) as $log)
      $logResult = array_merge($logResult, explode("\n", $log));
       
    $_eqLogic -> addLog(array('dateLog' => date('Y-m-d H:i:s'), 'typeLog' => $_typeLog, 'level' => $_level, 'padLog' => $_eqLogic -> getProtectedValue('parentLog'), 'log' => $logResult));
  
    if ($_eqLogic -> getProtectedValue('logmode', $_eqLogic -> getConfiguration('logmode', 'default')) == 'realtime')
      pluginsToolsDepedency::persistLog($_eqLogic);
  }
  
  public static function setArrayLog(&$_eqLogic, $_typeLog, $_log, $_display = null, $_title = null, $_level = 'debug') {
    $_display = !isset($_display)? '{value}':$_display;
    
    if (count($_log)) {
      if ($_typeLog == 'ERROR')
        $_level = 'danger';
      elseif ($_typeLog == 'DEBUG2') {
        if ($_eqLogic -> getProtectedValue('logLevel', $_eqLogic -> getConfiguration('logLevel', 'default')) == 'advanced') {
          $_typeLog = 'DEBUG';
          $_level =   '';
       }
        else
          return;
      }
      
      if ($_title != '')
        pluginsToolsDepedency::incLog($_eqLogic, $_typeLog, $_title, $_level);
      
      foreach ($_log as $key => $row) {
        $logResult = $_display;
        $logResult = str_replace(['{key}','{value}'], [$key,(is_array($row)? json_encode($row, JSON_UNESCAPED_UNICODE):$row)], $logResult);
        if (is_array($row)) {
          foreach ($row as $keyValue => $value)
            $logResult = str_replace('{'.$keyValue.'}', $value, $logResult);
        }
        pluginsToolsDepedency::setLog($_eqLogic, $_typeLog, $logResult, $_level);
      }
      
      if ($_title != '')
        pluginsToolsDepedency::unIncLog($_eqLogic, $_typeLog, '', $_level);

      if ($_eqLogic -> getProtectedValue('logmode', $_eqLogic -> getConfiguration('logmode', 'default')) == 'realtime')
        pluginsToolsDepedency::persistLog($_eqLogic);
    }      
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
  
  public static function setCmdList(&$_eqLogic, $_listCmdToCreated = null) {
    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Configuration de la liste des commandes');
    
    $eqLogicId =        $_eqLogic -> getId();
    $keyNotUpdated =    array('IsVisible', 'IsHistorized', 'Generic_type');
    $listCmdToCreated = isset($_listCmdToCreated)? $_listCmdToCreated:$_eqLogic -> getProtectedValue('listCmdToCreated', array());
    $orderCreationCmd = $_eqLogic -> getProtectedValue('orderCreationCmd');
    
    foreach ($listCmdToCreated as $logicalId => $configInfos) {      
      pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Commande '.$logicalId);
      pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Search commande '.$logicalId);
      
      $nbwCmd = false;
      $cmd = cmd::byEqLogicIdAndLogicalId($eqLogicId, $logicalId);// $_eqLogic -> getCmd(null, $logicalId);
      //if (!is_object($cmd)) {
      //  pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Comande not found, search commande by name:'.$configInfos['Name']);
      //  $cmd = cmd::byEqLogicIdCmdName($eqLogicId, $configInfos['Name']);
      //}
      
      if (!is_object($cmd)) {
        pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande not found...');
        foreach ($configInfos['ListLogicalReplace'] as $idReplace) {
          pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Search commande replace name '.$idReplace);
          $cmd = cmd::byEqLogicIdAndLogicalId($eqLogicId, $idReplace);
          if (is_object($cmd)) {
            pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande found...');
            break;
          }
          else
            pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande not found...');
          pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
        }
      }
      else
        pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande found...');
      
      pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');

      if (isset($configInfos)) {
        if (!is_object($cmd)) {
          pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Create '.$logicalId.' cmd on eqLogicId '.$eqLogicId);
          $nbwCmd = true;
          $cmd = new cmd();
        }
        else
          pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Update '.$logicalId.' cmd');

        $cmd -> setEqLogic_id($eqLogicId);
        $cmd -> setLogicalId($logicalId);
        
        foreach ($configInfos as $key => $value) {
          if ($key == 'ListLogicalReplace')
            continue;

          if (isset($value)) {
            $key = ucfirst($key);

            if (!method_exists($cmd, 'set'.$key))
               continue;
               
            if (is_array($value)) {
              pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','set '.$key, 'debug');
              foreach ($value as $arrKey => $arrValue) {
                if (isset($arrValue)) {
                  if (is_array($arrValue) || strpos($arrValue, '#') === false) {
                    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set'.$arrKey.' with value:'.json_encode($arrValue));
                    $cmd -> {'set'.$key}($arrKey, $arrValue);
                  }
                  else {
                    if (isset($keyNotUpdated[$key]) && in_array($arrKey, $keyNotUpdated[$key]) && !$nbwCmd)
                      continue;
                    
                    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set'.$$arrKey.' with value:'.$listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                    $cmd -> {'set'.$key}($arrKey, $listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                  }
                }
              }
              pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
            }
            else {
              if (in_array($key, $keyNotUpdated) && !$nbwCmd)
                continue;

         
              if (strpos($value, '#') === false) {
                pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set '.$key.' with value:'.$value);
                $cmd -> {'set'.$key}($value);
              }
              else {
                pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set '.$key.' with value:'.$listCmdToCreated[str_replace('#', '', $value)]['id']);
                $cmd -> {'set'.$key}($listCmdToCreated[str_replace('#', '', $value)]['id']);
              }
            }
          }
        }
        pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set EqType with value:'.$_eqLogic -> getEqType_name());
        $cmd -> setEqType($_eqLogic -> getEqType_name());
        pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set Order with value:'.$orderCreationCmd);
        $cmd -> setOrder($orderCreationCmd++);
        $cmd -> save();

        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');

        if (isset($configInfos['Configuration']['value']) && $configInfos['Configuration']['value'] != '' && $configInfos['Type'] == 'info')
          $cmd -> event($configInfos['Configuration']['value']);
        
        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
        
        $listCmdToCreated[$logicalId]['id'] = $cmd -> getId();
      }
      elseif (is_object($cmd))
        $cmd -> remove();
    }
    
    if (!isset($_listCmdToCreated))
      $_eqLogic -> setProtectedValue('listCmdToCreated', array());
    
    $_eqLogic -> setProtectedValue('orderCreationCmd', $orderCreationCmd);
    
    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
  }
  
  public function mergeLog(&$_eqLogic, &$_service) {
    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG2');
    
    $typeLog = $_eqLogic -> getProtectedValue('typeLog');
    foreach ($_service -> getProtectedValue('log') as $logKey => $logDetail) {
      $typeLog = (pluginsToolsDepedencyConst::TypeLogPriority[$logDetail['typeLog']] > pluginsToolsDepedencyConst::TypeLogPriority[$typeLog])? $logDetail['typeLog']:$typeLog;
      pluginsToolsDepedency::setLog($_eqLogic, $logDetail['typeLog'], $logDetail['log']);
    }
    $_eqLogic -> setProtectedValue('typeLog', $typeLog);
    $_service -> setProtectedValue('log', array());
    
    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG2');
  }  
  
  public function persistLog(&$_eqLogic) {
    $logList = $_eqLogic -> getProtectedValue('log', array());
    
    if (count($logList) > 0) {
      $dirLog =      $_eqLogic -> getProtectedValue('dirLog', 'pluginLog');
      $suffixLog =   $_eqLogic -> getProtectedValue('suffixLog', 'plugin');
      $logMessage =  '';

      //if (
      //     $_eqLogic -> getProtectedValue('logmode', $_eqLogic -> getConfiguration('logmode', 'default')) == 'none'
      //     || (!$_eqLogic -> getProtectedValue('externalLog',0) && isset($_eqLogic -> getProtectedValue('objCall')) && method_exists($_eqLogic -> getProtectedValue('objCall'), 'addLog'))
      //   )
      //  return;   
            
      foreach ($logList as $keyDetail => $detailLog) {
        if ($detailLog['typeLog'] == 'INC_LOG')
          $logMessage .= "\n".$_eqLogic -> waitCache($detailLog['log'][0]);
        else {
          $prefixLog =  '['.$detailLog['dateLog'].']['.$detailLog['typeLog'].']'.str_pad("",8-strlen($detailLog['typeLog'])," ");
          
          foreach ($detailLog['log'] as $keyLog => $log) {
            $log = preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x\1;', htmlentities(is_array($log)? json_encode($log):$log));
            
            if ($detailLog['level'] != '')
              $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").'<label class="'.$detailLog['level'].'" style="margin-bottom: 4px;">'.$log.'</label>';
            else
              $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").$log;
  
            //log::add($_eqLogic -> getProtectedValue('className'), $detailLog['typeLog'], str_pad("", $detailLog['padLog']*3, " ").$log);
          }
        }
      }
      
      if ($_eqLogic -> getProtectedValue('externalLog',0))
        cache::set($_eqLogic -> getProtectedValue('nodeGenKey'), $logMessage);
      else
        file_put_contents(pluginsToolsDepedency::mkdirPath($_eqLogic, $dirLog, $suffixLog.$_eqLogic -> getId()), $logMessage, FILE_APPEND);
    }
    $_eqLogic -> setProtectedValue('log', array());
  }

	public function fullDataObject(&$_eqLogic, $_restrictSearch = array(), $_searchOnChildObject = true, $_onlyVisible = false) {
    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','fullDataObject :: '.json_encode($_restrictSearch, JSON_UNESCAPED_UNICODE).' searchOnChildObject:'.$_searchOnChildObject);
    
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
      //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','object :: '.$object->getId().' '.$object->getName().':::'.count($_restrictSearch['object']));

      // On valide que l'on a pas de restriction au niveau de l'object ou que celui-ci figure dans la restriction
      if (count($_restrictSearch['object']) == 0 || in_array($object->getId(),$_restrictSearch['object']) || in_array(-1,$_restrictSearch['object'])) {

        // Si on doit rechercher dans les enfant et qu'il y a restriction de l'object
        if ($_searchOnChildObject && isset($_restrictSearch['object'])) {
          //pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG2','On recherche ses enfants');
          
          $objectChildList = $object->getChild($_onlyVisible);

          if (is_array($objectChildList) && count($objectChildList) > 0) {
            foreach ($objectChildList as $objectChild) {
              //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','Add child '.$objectChild->getName().' ('.$objectChild->getId().') element on _restrictSearch');
              array_push($_restrictSearch['object'], $objectChild->getId());
            }
          }
          //pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG2','Search child');
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
                continue;//pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','L\équipement '.$eqLogic->getHumanName().' n\'est pas dans la liste de ceux recherchées');
              elseif (count($_restrictSearch['genericTypeEqLogic']) != 0 && !in_array($eqGenericType, $_restrictSearch['genericTypeEqLogic']))
                continue;//pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','Le type générique de l\'équipement '.$eqLogic->getHumanName().' ('.$eqGenericType.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['genericTypeEqLogic']).')');
              elseif (count($_restrictSearch['plugin']) != 0 && !in_array($eqPluggin, $_restrictSearch['plugin']))
                continue;//pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','Le plugin de l\'équipement '.$eqLogic->getHumanName().' ('.$eqPluggin.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['plugin']).')');
              else {
                //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','Check eq '.$eqLogic->getHumanName().'('.$eqLogic->getId().')');
                
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
                      continue;//pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': La commande n\'est pas dans la liste de celles recherchées');
                    elseif (count($_restrictSearch['genericTypeCmd']) != 0 && !in_array($cmdGenericType, $_restrictSearch['genericTypeCmd']))
                      continue;//pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': Le type générique de la commande ('.$cmdGenericType.') n\'est pas dans la liste de ceux recherchés ('.json_encode($_restrictSearch['genericTypeCmd']).')');
                    else {
                      //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': Ajout de la commande à la liste');
                      
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
      //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','object :: '.$object->getId().' '.$object->getName());
		}
    
    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','==>'.json_encode($return, JSON_UNESCAPED_UNICODE));
    
		return $return;
	}  

  public function getElementConfig(&$_eqLogic, $_configName, $_configElementName = null, $_configElementValue = null) {
    $result = array();
    
    foreach ($_eqLogic -> getConfiguration($_configName) as $keyElement => $detailElement) {
      if (isset($_configElementValue)) {
        if ($detailElement[$_configElementName] == $_configElementValue)
          return $detailElement;
      }
      else {
        if (isset($_configElementName))
          $result[$detailElement[$_configElementName]] = $detailElement;
        else
          $result[] = $detailElement;
      }
    }
    return $result;
  }
  
  public function createUniqueCron(&$_eqLogic, $_function, $_cronOption, $_schedule, $_setOnce = 1) {
    $className =            $_eqLogic -> getProtectedValue('className');
    $crons =                cron::searchClassAndFunction($className, $_function, $_cronOption);
    $scheduleIsTimestamp =  ((string) (int) $_schedule === $_schedule) && ($_schedule <= PHP_INT_MAX) && ($_schedule >= ~PHP_INT_MAX);

    if (!is_array($crons) || count($crons) == 0) {
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Creation of cron to '.$_schedule); 

      $cron = new cron();
      $cron->setClass($className);
      $cron->setFunction($_function);
      $cron->setOption($_cronOption);
      $cron->setLastRun(date('Y-m-d H:i:s'));
      $cron->setOnce($_setOnce);
      $cron->setSchedule($scheduleIsTimestamp? cron::convertDateToCron($_schedule):$_schedule);
      $cron->save();
    }
    else {
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Re-schedule of cron to '.$_schedule); 
      
      $delete = false;
      foreach ($crons as $cron) {
        if (!$delete) {
          $cron -> setSchedule($scheduleIsTimestamp? cron::convertDateToCron($_schedule):$_schedule);
          $cron -> save();
          $delete = true;
        }
        else
          $cron -> remove();
      }
    }
  }
  
  public static function getJeedomGenericType() {
    $genericTypeConfig =  jeedom::getConfiguration('cmd::generic_type');
    foreach (plugin::listPlugin(false, true, false, true) as $key => $plugin) {
      if (method_exists($plugin, 'pluginGenericTypes'))
        $genericTypeConfig = array_merge($genericTypeConfig, $plugin::pluginGenericTypes());
    }
    return $genericTypeConfig;
  }
  
  public static function getGenericTypeConfiguration($_type = null) {
    $genericTypeList =    array();
    
    foreach (pluginsToolsDepedency::getJeedomGenericType() as $generic_type_key => $generic_type_detail) {
      if (isset($generic_type_detail['ignore']) && $generic_type_detail['ignore'] == true)
        continue;
      if (isset($_type) && $_type != 'all' && $generic_type_detail['type'] != $_type)
        continue;
      
      if (!isset($genericTypeList[$generic_type_detail['family']])) {
        $genericTypeList[$generic_type_detail['family']] = array('id' =>            $generic_type_detail['familyid'],
                                                                 'family' =>        $generic_type_detail['family'],
                                                                 'generiqueType' => array(),
                                                                 'optgroupLabel' => '<optgroup label="{{' . $generic_type_detail['family'] . '}}">',
                                                                 'userDefined' =>   isset($generic_type_detail['userDefined'])? 1:0);
      }
      $genericTypeList[$generic_type_detail['family']]['generiqueType'][$generic_type_detail['name']][] = array('familyid' =>    $generic_type_detail['familyid'],
                                                                                                                'family' =>      $generic_type_detail['family'],
                                                                                                                'key' =>         $generic_type_key,
                                                                                                                'name' =>        $generic_type_detail['name'], 
                                                                                                                'type' =>        $generic_type_detail['type'],
                                                                                                                'subtype' =>     $generic_type_detail['subtype'],
                                                                                                                'optLabel' =>    '<option value="' . $generic_type_key . '" #optLabelOption_'.$generic_type_key.'#>' .  $generic_type_detail['name'] . ($_type == 'all'? ' | ' . $generic_type_detail['type']:'') . '</option>',
                                                                                                                'userDefined' => isset($generic_type_detail['userDefined'])? 1:0);
    }
    ksort($genericTypeList, SORT_STRING);

    foreach ($genericTypeList as $family => $family_detail)
      ksort($genericTypeList[$family]['generiqueType'], SORT_STRING);
  
    return $genericTypeList;
  }

  public static function genericTypeConfigurationToPromptOption($_type = null, $_includeNone = true) {
    $_genericTypeList = pluginsToolsDepedency::getGenericTypeConfiguration($_type);

    $return = array();
    if ($_includeNone)
      $return[] = array('text' => __('Aucun', __FILE__), 'value' => '');
    
    foreach ($_genericTypeList as $family => $family_detail) {
      foreach ($family_detail['generiqueType'] as $generiqueTypeName) {
        foreach ($generiqueTypeName as $generiqueTypedetail)
          $return[] = array('text' => $generiqueTypedetail['name'] . ($_type == 'all'? ' | ' . $generiqueTypedetail['type']:''), 'value' => $generiqueTypedetail['key']);
      }
    }
    
    return $return;
  }
  
  public static function genericTypeConfigurationToHtmlOption($_type = null, $_includeNone = true) {
    $_genericTypeList = pluginsToolsDepedency::getGenericTypeConfiguration($_type);

    $return = '';
    if ($_includeNone)
      $return .= '<option value="">' . __('Aucun', __FILE__) . '</option>';
    
    foreach ($_genericTypeList as $family => $family_detail) {
      $return .= $family_detail['optgroupLabel'];
      foreach ($family_detail['generiqueType'] as $generiqueTypeName) {
        foreach ($generiqueTypeName as $generiqueTypedetail)
          $return .= $generiqueTypedetail['optLabel'];
      }
      $return .= '</optgroup>';
    }
    
    return $return;
  }  
}
?>