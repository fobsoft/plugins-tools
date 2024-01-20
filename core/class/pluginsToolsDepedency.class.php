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
  const LogLevel =           [
                              'Aucun' =>        0,
                              'Error' =>        1,
                              'Warning' =>      2,
                              'Info' =>         4,
                              'Debug' =>        8,
                              'DebugAdvance' => 16 // Calcul, End part after begin
                              ];
                              
  const TypeLogPriority =  array('ND' => 0, 'DEBUG_ADV' => 1, 'DEBUG' => 2, 'INFO' => 3, 'WARNING' => 4, 'ERROR' => 5);
}

class pluginsToolsDepedency {
  public function purgeLog(&$_eqLogic) {
    //$maxLineLog = config::byKey('maxLineLog', $_eqLogic -> getProtectedValue('className'), 5000);
    //$path =       dirname(__FILE__) . '/../../../../log/pluginLog/plugin' . $_eqLogic -> getId() . '.log';

    //pluginsToolsDepedency::addLog($_eqLogic, 'DEBUG', 'purgeLog off '.$path.' for max '.$maxLineLog.'line');
    //if (file_exists($path)) {
    //  try {
    //    com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $path . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $path . ')" > ' . $path);
    //  } 
    //  catch (\Exception $e) {
    //  }
    //}
    //pluginsToolsDepedency::persistLog($_eqLogic);
  }  

  public static function mkdirPath(&$_eqLogic, $_dirName, $_fileName, $_purgeFile = false) {
    $path =       log::getPathToLog($_dirName);
    $filePath =   $path . '/' . $_fileName . '.log';
    $maxLineLog = config::byKey('maxLineLog', $_eqLogic -> getProtectedValue('className'), 5000);
  
    if (!file_exists($path))
      mkdir($path);
    elseif (file_exists($filePath) && $_purgeFile)
      com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $filePath . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $filePath . ')" > ' . $filePath);

    return $filePath;
  } 
  
  public static function getObjCall(&$_eqLogic) {
    $objCallSrc = $_eqLogic;
    
    if (method_exists($objCallSrc, 'getProtectedValue')) {
      $objCall = $objCallSrc -> getProtectedValue('objCall');

      if (isset($objCall) && is_object($objCall) && method_exists($objCall, 'getProtectedValue'))
        $objCallSrc = pluginsToolsDepedency::getObjCall($objCall);
    }

    return $objCallSrc;
  }
  
  public static function callPluginMethod($_option) {
		try {
      $validAction =  true;
      $lockListener = isset($_option['lockListener'])? $_option['lockListener']:0;
      
      if ($lockListener) {
        unset($_option['lockListener']);
        if (($validAction = (!isset($_option['state']) || $_option['state'] == 1))) {
          $listener = listener::byId($_option['listener_id']);
          $listener -> setOption('state', 0);
          $listener -> save();
        }
      }        
        
      if ($validAction) {
        if (isset($_option['class'])) {
          $class =    $_option['class'];
          $function = $_option['function'];
          
          unset($_option['class']);
          unset($_option['function']);
          if (class_exists($class) && method_exists($class, $function))
            $class::$function($_option);
          else
            log::add('listener', 'debug', __('[Erreur] Classe ou fonction non trouvée ', __FILE__) . json_encode($_option));
        } 
        elseif (isset($_option['function'])) {
          $function = $_option['function'];
          unset($_option['function']);
          if (function_exists($function))
            $function($_option);
          else
            log::add('listener', 'error', __('[Erreur] Function non trouvée ', __FILE__) . json_encode($_option));
        }
      }
        
      if ($lockListener) {
        $listener -> setOption('state', 1);
        $listener -> save();
      }
		} 
    catch (Exception $e) {
			log::add('listener', 'error', $e->getMessage());
		}    
  }
  
  public static function callMethod(&$_eqLogic, $_method, $_parameter = array(), $_default = null) {
    $objCall = pluginsToolsDepedency::getObjCall($_eqLogic);
    if (method_exists($objCall, $_method))
      return call_user_func_array(array($objCall, $_method), $_parameter);
    else
      return $_default;
  }
  
  public static function incLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    pluginsToolsLog::incLog($_eqLogic, $_typeLog, $_log, $_level);
  }

  public static function unIncLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    pluginsToolsLog::unIncLog($_eqLogic, $_typeLog, $_log, $_level);
  }
  
  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = 'debug') {
    //return pluginsToolsLog::setLog($_eqLogic, $_typeLog, $_log, $_level);

    if ($_log != '') {
      if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
        if (!isset($_level))
          $_level = 'debug';
        
        if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) < pluginsToolsLogConst::LogLevel[$_typeLog])
          return;
        
        if ($_typeLog == 'DEBUG_SYS')
          $_typeLog = 'DEBUG';
        
        if ($_typeLog == 'ERROR')
          $_level = 'danger';
       
        if (is_array($_log) || is_object($_log)) {
          $_log = (array)$_log;
          foreach ($_log as $logKey => $logValue) {
            if (is_array($logValue) || is_object($logValue)) {
              pluginsToolsDepedency::incLog($objectCall, $_typeLog, $logKey.' => {', $_level);
              pluginsToolsDepedency::setLog($objectCall, $_typeLog, $logValue, $_level);
              pluginsToolsDepedency::unIncLog($objectCall, $_typeLog, '}', $_level);
            }
            else
              pluginsToolsDepedency::setLog($objectCall, $_typeLog, $logKey.' => '.$logValue, $_level);
          }
        }
        else {
          $logMessage = '['.date('Y-m-d H:i:s').']['.$_typeLog.']'.str_pad("",8-strlen($_typeLog)," ").str_pad("", $objectCall -> getProtectedValue('parentLog')*3, " ");
          
          if ($_level != '')
            $logMessage .= '<label class="'.$_level.'" style="margin-bottom: 4px;">'.$_log.'</label>';
          else
            $logMessage .= $_log;
          
          $dirLog =       $objectCall -> getProtectedValue('dirLog', 'pluginLog');
          $suffixLog =    $objectCall -> getProtectedValue('suffixLog', 'plugin');

          file_put_contents(pluginsToolsDepedency::mkdirPath($objectCall, $dirLog, $suffixLog.$objectCall -> getId()), $logMessage."\n", FILE_APPEND | LOCK_EX);
        }
      }  
    }
    
    // Permet de mettre l'instruction d'ajout de log dans une conditionnel
    return true; 
  }  
  
  public static function addLog(&$_eqLogic, $_typeLog, $_log, $_level = 'debug') {
    return pluginsToolsLog::setLog($_eqLogic, $_typeLog, $_log, $_level);
  }
  
  public static function setArrayLog(&$_eqLogic, $_typeLog, $_log, $_display = null, $_title = null, $_level = 'debug') {
    return pluginsToolsLog::setLog($_eqLogic, $_typeLog, [$_title => $_log], $_level);
  }  
  
  public static function convertCmdConfigValue($_eqLogic, $_listCmdToCreated, $value) {
    if (is_array($value)) {
      foreach ($value as $prevArrKey => $prevArrValue) {
        $arrKey = pluginsToolsDepedency::convertCmdConfigValue($_eqLogic, $_listCmdToCreated, $prevArrKey);
        $arrValue = pluginsToolsDepedency::convertCmdConfigValue($_eqLogic, $_listCmdToCreated, $prevArrValue);
        unset($value[$prevArrKey]);
        $value[$arrKey] = $arrValue;
      }
    }
    else {
      if (preg_match_all("/#(.*?)#/", $value, $matches, PREG_SET_ORDER) && count($matches) > 0) {
        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Convert cmd config value:'.json_encode($value));
        foreach ($matches as $match) {
          if (isset($_listCmdToCreated[$match[1]]))
            $value = '#'.$_listCmdToCreated[$match[1]]['id'].'#';
          else
            pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Commande non trouvé');
          
          pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', ' -> '.$value);
        }
        pluginsToolsDepedency::unIncLog($_eqLogic, $_typeLog, '', $_level);
      }
    }
    
    return $value;
  }
  
  // 'tpiCode' =>  array ('Name' =>           __('Code', __FILE__), 
  //                      'IsVisible' =>      1, 
  //                      'IsHistorized' =>   1, 
  //                      'Type' =>           'info', 
  //                      'SubType' =>        'string', 
  //                      'Unite' =>          null,
  //                      'Value' =>          null,
  //                      'Alert' =>          array(),
  //                      'Generic_type' =>   'ALARM_STATUS_CODE', 
  //                      'Configuration' =>  array('minValue' => '', 'maxValue' => '', 'value' => $_tpiCode, 'repeatEventManagement' => 'always'), 
  //                      'Template' =>       array('dashboard' => 'core::default', 'mobile' => 'core::default'),
  //                      'Display' =>        array('message_placeholder' => 'code', 'title_disable' => 1, 'showStatsOnmobile' => 0, 'showStatsOndashboard' => 0, 'invertBinary' => 0, 'icon' => ''))  
  public static function setEqLogicCmdList(&$_eqLogic, $_options = []) {
    pluginsToolsDepedency::setCmdList($_eqLogic, $_eqLogic -> getProtectedValue('listCmdToCreated', array()), $_options);
    
    $_eqLogic -> setProtectedValue('listCmdToCreated', array());    
  }
    
  public static function setCmdList(&$_eqLogic, $_listCmdToCreated = null, $_options = []) {
    $eqLogicId =        $_eqLogic -> getId();
    $keyNotUpdated =    array('IsVisible', 'IsHistorized', 'Generic_type', 'Name');
    $listCmdToCreated = $_listCmdToCreated;
    $orderCreationCmd = $_eqLogic -> getProtectedValue('orderCreationCmd', 1);

    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Configuration de la liste des commandes:: '.json_encode($listCmdToCreated));
    
    if (isset($listCmdToCreated)) {
      foreach ($listCmdToCreated as $logicalId => $configInfos) {      
        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Commande '.$logicalId.' => '.json_encode($configInfos));
        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Search commande '.$logicalId);
        
        $defaultValue = null;
        $newCmd =       false;
        $cmd =          cmd::byEqLogicIdAndLogicalId($eqLogicId, $logicalId);// $_eqLogic -> getCmd(null, $logicalId);
        
        if (!is_object($cmd) && isset($configInfos))
          $cmd = cmd::byEqLogicIdCmdName($eqLogicId, $configInfos['Name']);
        
        if (!is_object($cmd)) {
          pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande not found...');
          if (isset($configInfos['ListLogicalReplace'])) {
            foreach ($configInfos['ListLogicalReplace'] as $idReplace) {
              pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Search commande replace name '.$idReplace);
              $cmd = cmd::byEqLogicIdAndLogicalId($eqLogicId, $idReplace);
              
              if (is_object($cmd)) {
                pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande found...');
                pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
                break;
              }

              pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande not found...');
              pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
            }
          }
        }
        else
          pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Comande found...');
        
        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG'); //Search commande

        if (isset($configInfos)) {
          unset($configInfos['ListLogicalReplace']['value']);
          if (isset($configInfos['Configuration']['value'])) {
            $defaultValue = $configInfos['Configuration']['value'];
            //unset($configInfos['Configuration']['value']);
          }        
          
          if (!is_object($cmd)) {
            pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Create '.$logicalId.' cmd on eqLogicId '.$eqLogicId);
            $newCmd = true;
            $cmd = new cmd();
          }
          else
            pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Update '.$logicalId.' cmd');

          $cmd -> setEqLogic_id($eqLogicId);
          $cmd -> setLogicalId($logicalId);
          
          foreach ($configInfos as $key => $value) {
            $key = ucfirst($key);

            if (in_array($key, $keyNotUpdated) && !$newCmd)
              continue;
            elseif (!method_exists($cmd, 'set'.$key)) {
              pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Method set'.$key.' n\'existe pas', 'debug');
              continue;
            }
                
            if (isset($value) && is_array($value)) {
              pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG','Set '.$key, 'debug');
              foreach ($value as $arrKey => $arrValue) {
                $arrValue = pluginsToolsDepedency::convertCmdConfigValue($_eqLogic, $listCmdToCreated, $arrValue);
                
                if (!is_array($arrValue) && strpos($arrValue, '#') !== false) {
                  if (isset($keyNotUpdated[$key]) && in_array($arrKey, $keyNotUpdated[$key]) && !$newCmd)
                    continue;
                  
                  if (isset($listCmdToCreated[str_replace('#', '', $arrValue)]))
                    $arrValue = $listCmdToCreated[str_replace('#', '', $arrValue)]['id'];
                }                
                pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set '.$arrKey.' with value:'.json_encode($arrValue));
                $cmd -> {'set'.$key}($arrKey, $arrValue);
                
                /*if (isset($arrValue)) {
                  if (preg_match_all("/#\[(.*?)\]#/", $arrValue, $matches, PREG_SET_ORDER) && count($matches) > 0) {
                    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Valeur dynamique trouvé');
                    foreach ($matches as $match) {
                      pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Recherche l\'id de la commande '.$match[1]);
                      if (isset($listCmdToCreated[$match[1]]))
                        $arrValue = str_replace($match[0], $listCmdToCreated[$match[1]]['id'], $arrValue);
                      else
                        pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Commande non trouvé');
                      pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
                    }
                  }
                  pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set '.$arrKey.' with value:'.json_encode($arrValue));
                  $cmd -> {'set'.$key}($arrKey, $arrValue);
                  
                  if (is_array($arrValue) || strpos($arrValue, '#') === false) {
                    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set'.$arrKey.' with value:'.json_encode($arrValue));
                    $cmd -> {'set'.$key}($arrKey, $arrValue);
                  }
                  else {
                    if (isset($keyNotUpdated[$key]) && in_array($arrKey, $keyNotUpdated[$key]) && !$newCmd)
                      continue;
                    
                    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set'.$$arrKey.' with value:'.$listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                    $cmd -> {'set'.$key}($arrKey, $listCmdToCreated[str_replace('#', '', $arrValue)]['id']);
                  }
                  
                }*/
              }
              pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
            }
            else {
              if (isset($value))
                $value = pluginsToolsDepedency::convertCmdConfigValue($_eqLogic, $listCmdToCreated, $value);

              pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set '.$key.' with value:'.json_encode($value));
              $cmd -> {'set'.$key}($value);
                /*
                if (preg_match_all("/#\[(.*?)\]#/", $value, $matches, PREG_SET_ORDER) && count($matches) > 0) {
                  pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Valeur dynamique trouvé');
                  foreach ($matches as $match) {
                    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Recherche l\'id de la commande '.$match[1]);
                    if (isset($listCmdToCreated[$match[1]]))
                      $value = str_replace($match[0], $listCmdToCreated[$match[1]]['id'], $value);
                    else
                      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Commande non trouvé');
                    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
                  }
                }
                
                pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','1. Set '.$key.' with value:'.$value);
                $cmd -> {'set'.$key}($value);
                */
                /*
                if (strpos($value, '#') === false) {
                  pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','1. Set '.$key.' with value:'.$value);
                  $cmd -> {'set'.$key}($value);
                }
                else {
                  pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','2. Set '.$key.' with value:'.$listCmdToCreated[str_replace('#', '', $value)]['id']);
                  $cmd -> {'set'.$key}($listCmdToCreated[str_replace('#', '', $value)]['id']);
                }
                */
            }
          }
          pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','Set EqType with value:'.$_eqLogic -> getEqType_name());
          $cmd -> setEqType($_eqLogic -> getEqType_name());
          
          if (isset($_options['setOrder']) && $_options['setOrder'] == 1) {
            pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG','set Order with value:'.$orderCreationCmd);
            $cmd -> setOrder($orderCreationCmd);
            $orderCreationCmd += 1;
          }
          
          $cmd -> save();

          //if ($newCmd && isset($defaultValue) && $configInfos['Type'] == 'info')
          if (isset($defaultValue) && $configInfos['Type'] == 'info')
            $_eqLogic -> checkAndUpdateCmd($logicalId, $defaultValue);        //$cmd -> event($defaultValue);
          
          pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG'); // Create vs Modify
          
          $listCmdToCreated[$logicalId]['id'] = $cmd -> getId();
        }
        elseif (is_object($cmd))
          $cmd -> remove();
          
        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG'); // Commande
      }
      
      if (isset($_options['autoRemouve']) && $_options['autoRemouve'] == 1) {
        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Suppression des commandes qui ne figure pas dans la liste');
        
        foreach (cmd::byEqLogicId($_eqLogic -> getId()) as $cmd) {
          //pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG', 'Vérification de la commande ' . $cmd -> getHumanName());
          if (!isset($listCmdToCreated[$cmd -> getLogicalId()]) || $listCmdToCreated[$cmd -> getLogicalId()]['id'] != $cmd -> getId()) {
            pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Suppression de la commande ' . $cmd -> getHumanName());
            $cmd -> remove();
          }
          //pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
        }
        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG');
      }
      
      if (isset($_options['setOrder']) && $_options['setOrder'] == 1)
        $_eqLogic -> setProtectedValue('orderCreationCmd', $orderCreationCmd);
    }
    
    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG'); //Configuration
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
  
  public function waitCacheLog(&$_eqLogic, $_nodeGenKey, $_timeOut = 10) {
    //pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2', 'waitCache');
    while (!cache::exist($_nodeGenKey) && $_timeOut > 0) {
      $_timeOut -= 1;
      sleep(1);
    }
    
    if (cache::exist($_nodeGenKey)) {
      $cacheLog = cache::byKey($_nodeGenKey);
      if (is_object($cacheLog)) {
        $content = $cacheLog -> getValue();
        $cacheLog -> remove();
      }
    }
    else
      $content = 'Node '.$_nodeGenKey.' not found';
    return $content;
  }
  
  public function persistLog(&$_eqLogic) {
    //$eqLogicCall = pluginsToolsDepedency::getObjCall($_eqLogic);
    $eqLogicCall = $_eqLogic;
    
    if (is_object($eqLogicCall)) {
      $logList =      $eqLogicCall -> getProtectedValue('log', array());
      
      if (count($logList) > 0) {
        $logMessage =  "";
              
        foreach ($logList as $keyDetail => $detailLog) {
          if ($detailLog['typeLog'] == 'INC_LOG')
            $logMessage .= "\n".pluginsToolsDepedency::waitCacheLog($eqLogicCall, $detailLog['log']);
          else {
            $prefixLog =  '['.$detailLog['dateLog'].']['.$detailLog['typeLog'].']'.str_pad("",8-strlen($detailLog['typeLog'])," ");
            
            $log = $detailLog['log'];
            //foreach ($detailLog['log'] as $keyLog => $log) {
              //$log = preg_replace('/\\\\u([\da-fA-F]{4})/', '&#x\1;', htmlentities(is_array($log)? json_encode($log):$log));
              
              if ($detailLog['level'] != '')
                $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").'<label class="'.$detailLog['level'].'" style="margin-bottom: 4px;">'.$log.'</label>';
              else
                $logMessage .= "\n".$prefixLog.str_pad("", $detailLog['padLog']*3, " ").$log;
    
              //log::add($eqLogicCall -> getProtectedValue('className'), $detailLog['typeLog'], str_pad("", $detailLog['padLog']*3, " ").$log);
            //}
          }
        }
        
        if ($eqLogicCall -> getProtectedValue('externalLog',0)) {
          //file_put_contents(pluginsToolsDepedency::mkdirPath($eqLogicCall, 'nodeLog', $eqLogicCall -> getProtectedValue('nodeGenKey')), $logMessage, FILE_APPEND);
          cache::set($eqLogicCall -> getProtectedValue('nodeGenKey'), $logMessage, 500);
        }
        else {
          $dirLog =     $_eqLogic -> getProtectedValue('dirLog', 'pluginLog');
          $suffixLog =  $_eqLogic -> getProtectedValue('suffixLog', 'plugin');
          $filePath =   pluginsToolsDepedency::mkdirPath($_eqLogic, $dirLog, $suffixLog.$_eqLogic -> getId());
          
          $file = fopen($filePath,'a+');
          if (flock($file, LOCK_EX)) {          // acquière un verrou exclusif
            fwrite($file, $logMessage."\n");
            fflush($file);                    // libère le contenu avant d'enlever le verrou
            flock($file, LOCK_UN);            // Enlève le verrou
          } 

          fclose($file);
          
        }
      }
      $eqLogicCall -> setProtectedValue('log', []);
    }
  }

  public function generateRandomKey() {
		$randHexStr = strtoupper(implode('', array_map(function () {
			return dechex(mt_rand(0, 15));
		}, array_fill(0, 32, null))));
		
		return $randHexStr;
	}
/*  
  public static function execCmdAction($_eqLogicCall, $_cmd, $_options = []) {
    $cmd = is_object($_cmd)? $_cmd:$_eqLogicCall -> getCmd(null, $_cmd);
    
    if (is_object($cmd)) {
      
      if ($cmd -> getType() == 'action') {    
        $cacheLogToken = pluginsToolsLog::generateCacheToken($cmd);
        if (isset($cacheLogToken))
          $_options['cacheLogToken'] =     $cacheLogToken;

        pluginsToolsLog::setLog($_eqLogicCall, 'INFO', 'Exécution de la commande ' . $cmd -> getHumanName() . ' ' . __('options', __FILE__) . ':: ' . json_encode($_options, JSON_UNESCAPED_UNICODE), 'success');
        $cmd -> execCmd($_options);
      }
      else
        pluginsToolsLog::setLog($_eqLogicCall, 'INFO', 'Recherche de la valeur de la commande ' . $cmd -> getHumanName() . ' ' . __('options', __FILE__) . ':: ' . json_encode($_options, JSON_UNESCAPED_UNICODE), 'success');
        return $cmd -> execCmd($_options);
      
    }
  }*/
  
  public static function getCmdInfo($_eqLogicCall, $_cmd, $_returnValue = null) {
    $cmd = is_object($_cmd)? $_cmd:$_eqLogicCall -> getCmd(null, $_cmd);
    
    if (is_object($cmd) && $cmd -> getType() == 'info')
      $returnValue = $cmd -> execCmd();
    
    return $returnValue;
  }
  
  public static function execCmdEvent($_eqLogicCall, $_cmd, $_value) {
    $cmd = is_object($_cmd)? $_cmd:$_eqLogicCall -> getCmd(null, $_cmd);
    if (is_object($cmd) && $cmd -> getType() == 'info') {
      pluginsToolsLog::setLog($_eqLogicCall, 'INFO', 'Configuration de la commande ' . $cmd -> getHumanName() . '  à ' . $_value, 'success');
      $cmd -> event($_value);
    }
  }
  
  public static function execCmdAction($_eqLogicCall, $_cmd, $_value) {
    
  }  
  
  public static function execAction($_eqLogic, $_cmd, $_options) {
    if (isset($_cmd) && is_numeric(str_replace('#', '',$_cmd))) {
      $cmdAction =    cmd::byId(str_replace('#', '', $_cmd));
      $cmdHumanName = $cmdAction -> getHumanName();
    }
    else
      $cmdHumanName = $_cmd;

    if (class_exists('advancedScenario')) {
      pluginsToolsLog::incLog($_eqLogic, 'INFO', 'Exécution de '.$cmdHumanName.' via advancedScenario '. __(" avec comme option(s) : ", __FILE__) . json_encode($_options));
      
      if (is_object($cmdAction)) {
        pluginsToolsLog::setLog($_eqLogic, 'INFO', 'Test before call #cmd'.$cmdAction -> getId().'#');
        advancedScenario::byNode($_eqLogic, 'equipement_exec', 'action', '#cmd'.$cmdAction -> getId().'#', $_options);        
      }
      else {
        if (!isset($_options['tags']))
          $_options['tags'] = '';
                            
        $_options['nodeList'] = [];
        $_options['nodeList'][1] = ["nodeId" => 1, "type" => "start", "subtype" => "trigger", "options" => [], "subelements" => ["GO" => ["type" => "", "subtype" => "action", "expression" => "", "linkTo" => [2]]], "title" => "Départ"];
        switch ($_cmd) {
          case 'scenario':  $_options['nodeList'][2] = ["nodeId" => 2, "type" => "advancedScenario", "subtype" => "action", "title" => "", "scenarioId" => $_options["scenario_id"], "options" => ["enable" => 1, "action" => "inc"], "subelements" => ["OK" => ["linkTo" => []]]];
                            break;
          default:          $_options['nodeList'][2] = ["nodeId" => 2, "type" => $_cmd, "subtype" => "action", "title" => "", "options" => array_merge($_options, ["enable" => 1]), "subelements" => ["OK" => ["linkTo" => []]]];
                            break;
        }
        advancedScenario::byNodelist($_eqLogic, $_options);
      }
      pluginsToolsLog::unIncLog($_eqLogic, 'INFO');
    }
    else {
      pluginsToolsLog::incLog($_eqLogic, 'INFO', 'Exécution de '.$cmdHumanName.' via les classes par défault');

      // Si c'est une commande appartenant a l'equipement
      if (is_object($cmdAction)) {
        $_options['cacheLogToken'] = pluginsToolsLog::generateCacheToken($_eqLogic, $cmdAction);
        
        if ($cmdAction -> getSubtype() == 'slider' && isset($_options['slider']))
          $_options['slider'] = evaluate($_options['slider']);

        pluginsToolsLog::setlog($_eqLogic, 'INFO', __('Exécution de la commande ', __FILE__) . $cmdHumanName . __(" avec comme option(s) : ", __FILE__) . json_encode($_options), 'success');
                
        $cmdAction -> execCmd($_options);      
      }
      else {
        $_options['cacheLogToken'] = pluginsToolsLog::generateCacheToken($_eqLogic, $_eqLogic);
        
        pluginsToolsLog::setlog($_eqLogic, 'INFO', __('Exécution de la commande ', __FILE__) . $cmdHumanName . __(" avec comme option(s) : ", __FILE__) . json_encode($_options), 'success');
        scenarioExpression::createAndExec('action', $_cmd, $_options);
      }
      pluginsToolsLog::unIncLog($_eqLogic, 'INFO');
    }
  }
  
	public function fullDataObject(&$_eqLogic, $_options) {
    pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','fullDataObject :: ');

		$return = array();
    
    if (isset($_options['restrictSearch'])) {
      $_restrictSearch =        isset($_options['restrictSearch'])?       $_options['restrictSearch']:array();
      $_searchOnChildObject =   isset($_options['searchOnChildObject'])?  $_options['searchOnChildObject']:1;
      $_searchOnParentObject =  isset($_options['searchOnParentObject'])? $_options['searchOnParentObject']:0;
      $_onlyVisible =           isset($_options['onlyVisible'])?          $_options['onlyVisible']:0;
      
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','  restrictSearch :: '.json_encode($_restrictSearch, JSON_UNESCAPED_UNICODE));
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','  searchOnChildObject :: '.json_encode($_searchOnChildObject, JSON_UNESCAPED_UNICODE));
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','  searchOnParentObject :: '.json_encode($_searchOnParentObject, JSON_UNESCAPED_UNICODE));
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG2','  onlyVisible :: '.$onlyVisible);
    
      $_restrictSearch['level'] =               !isset($_restrictSearch['level'])?              7:$_restrictSearch['level'];    //1- Object, 2- Eq, 4- Cmd
      $_restrictSearch['object'] =              !isset($_restrictSearch['object'])?             array():$_restrictSearch['object'];
      $_restrictSearch['genericTypeObject'] =   !isset($_restrictSearch['genericTypeObject'])?  array():$_restrictSearch['genericTypeObject'];
      $_restrictSearch['eqLogic'] =             !isset($_restrictSearch['eqLogic'])?            array():$_restrictSearch['eqLogic'];
      $_restrictSearch['genericTypeEqLogic'] =  !isset($_restrictSearch['genericTypeEqLogic'])? array():$_restrictSearch['genericTypeEqLogic'];
      $_restrictSearch['plugin'] =              !isset($_restrictSearch['plugin'])?             array():$_restrictSearch['plugin'];
      $_restrictSearch['cmd'] =                 !isset($_restrictSearch['cmd'])?                array():$_restrictSearch['cmd'];
      $_restrictSearch['genericTypeCmd'] =      !isset($_restrictSearch['genericTypeCmd'])?     array():$_restrictSearch['genericTypeCmd'];
      
      foreach (jeeObject::all(true, true) as $object) {
        //pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG2','object :: '.$object->getId().' '.$object->getName().':::'.count($_restrictSearch['object']));

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
                  //pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG2','Check eq '.$eqLogic->getHumanName().'('.$eqLogic->getId().')');
                  
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
                        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG2','CMD '.$cmd -> getHumanName().': Ajout de la commande à la liste');
                        
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

                        pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG2');
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
                  //pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG2');
                }
              }
            }

            if ($_restrictSearch['level'] & 1 
                && ($_restrictSearch['level'] == 1 || isset($object_return['listCmd']) || count($object_return['listEqLogic']) > 0))
              $return[] = $object_return;
          }      
        }
        //pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG2');
      }
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
  
  function isValidTimeStamp($timestamp) {
    return (is_int($timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX));
  }

  public function createUniqueCron(&$_eqLogic, $_function, $_key, $_options, $_schedule, $_setOnce = 1) {
    pluginsToolsDepedency::cronCreateUnique($_eqLogic, $_function, $_key, $_options, $_schedule, $_setOnce);
  }
  
  public function cronCreateUnique(&$_eqLogic, $_function, $_key, $_options, $_schedule, $_setOnce = 1) {
    $className =            get_class($_eqLogic);
    $crons =                cron::searchClassAndFunction($className, $_function, $_key);
    $scheduleIsTimestamp =  pluginsToolsDepedency::isValidTimeStamp($_schedule);
    $directExecution =      false;
    
    pluginsToolsDepedency::incLog($_eqLogic, 'INFO', 'Set cron '.$_schedule);
    
    if ($scheduleIsTimestamp) {
      $currentTimestamp = new DateTime("now");
      $startEvent =       (new DateTime()) -> setTimestamp($_schedule);
      $_schedule =        cron::convertDateToCron($_schedule);
    }
      
    if (($directExecution = (isset($startEvent) && $currentTimestamp > $startEvent))) {
      pluginsToolsDepedency::setLog($_eqLogic, 'INFO', __(" Timestamp dans le passé, on exécute la commande ", __FILE__) . $_function . __(" avec comme option(s) : ", __FILE__) . json_encode($_options), 'success');
      
      if (method_exists($_eqLogic, 'getProtectedValue'))
        $_options['objectCall'] = pluginsToolsDepedency::getObjCall($_eqLogic); //$_options['cacheLogToken'] = $_eqLogic -> getProtectedValue('cacheLogToken');
      
      $className::{$_function}($_options);
      
      foreach ($crons as $cron)
        $cron -> remove();      
    }
    else {  
      if (!is_array($crons) || count($crons) == 0) {
        pluginsToolsDepedency::setLog($_eqLogic, 'INFO', 'Creation of cron with '.($scheduleIsTimestamp? 'timestamp ('.date_format($startEvent).')':'schedule'), 'success'); 

        $cron = new cron();
        $cron-> setClass($className);
        $cron-> setFunction($_function);
        $cron-> setOption($_options);
        $cron-> setLastRun(date('Y-m-d H:i:s'));
        $cron-> setOnce($_setOnce);
        $cron-> setSchedule($_schedule);
        $cron-> save();
      }
      else {
        pluginsToolsDepedency::setLog($_eqLogic, 'INFO','Re-schedule of cron with '.($scheduleIsTimestamp? 'timestamp ('.date_format($startEvent).')':'schedule'), 'success'); 
        
        $delete = false;
        foreach ($crons as $cron) {
          if (!$delete) {
            $cron -> setOption($_options);
            $cron -> setSchedule($_schedule);
            $cron -> save();
            $delete = true;
          }
          else
            $cron -> remove();
        }
      }
    }
    pluginsToolsDepedency::unIncLog($_eqLogic, 'INFO');
  }
  
  public function cronDelete(&$_eqLogic, $_function, $_key) {
    $className =            $_eqLogic -> getProtectedValue('className');
    $crons =                cron::searchClassAndFunction($className, $_function, $_key);

    foreach ($crons as $cron) {
      pluginsToolsDepedency::setLog($_eqLogic, 'DEBUG', 'Deleted cron '.$cron -> getSchedule()); 
      $cron -> remove();
    }

    return true;
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
  /*
	public static function publishMqttApi(&$_eqLogic, $_api_name, $_args = array()) {
		// log::add($_eqLogic::class, 'debug', '[' . __FUNCTION__ . '] ' . 'Publication Mqtt Api ' . $_api_name . ' ' . json_encode($_args));
		mqtt2::publish(config::byKey('prefix', __CLASS__, 'zwave') . '/_CLIENTS/ZWAVE_GATEWAY-Jeedom/api/' . $_api_name . '/set', $_args);
	}

	public static function publishMqttValue(&$_eqLogic, $_node, $_path, $_args = array()) {
		// log::add($c::class, 'debug', '[' . __FUNCTION__ . '] ' . 'Publication Mqtt Value' . $_node . ' ' . $_path . ' ' . json_encode($_args));
		mqtt2::publish(config::byKey('prefix', $c::class, 'zwave') . '/' . $_node . '/' . $_path . '/set', $_args);
	}  */
}
?>