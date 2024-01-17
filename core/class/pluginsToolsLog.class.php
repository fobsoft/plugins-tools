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

abstract class pluginsToolsLogConst {
  const LogLevel =           [
                              'TOKEN' =>        -1,
                              'NONE' =>         0,
                              'ERROR' =>        1,
                              'WARNING' =>      2,
                              'INFO' =>         4,
                              'DEBUG' =>        8,
                              'DEBUG2' =>       16, // Calcul, End part after begin
                              'DEBUG_SYS' =>    16 // Calcul, End part after begin
                             ];
                              
  const TypeLogPriority =  array('ND' => 0, 'DEBUG_ADV' => 1, 'DEBUG' => 2, 'INFO' => 3, 'WARNING' => 4, 'ERROR' => 5);
}

class pluginsToolsLog {
  
  public static function init(&$_eqLogic, $_options = []) {
    // La logique de config de parametre doit se basser sur l'enfant, ceci couvre le flow d'appel sinchrone
    $objectCall = pluginsToolsDepedency::getObjCall($_eqLogic);
    
    if (is_object($objectCall)) {
      $cacheLogToken = $objectCall -> getProtectedValue('cacheLogToken');
      
      if (!isset($cacheLogToken) && isset($_options['cacheLogToken'])) {
        $cacheLogToken = $_options['cacheLogToken'];
        
        $objectCall -> setProtectedValue('cacheLogToken',      $cacheLogToken);
        //$objectCall -> setProtectedValue('objectCallLogPath',  isset($_options['objectCallLogPath'])?  $_options['objectCallLogPath']:null);
      }

      if (!isset($cacheLogToken)) {
        $dirLog =       $objectCall -> getProtectedValue('dirLog', 'pluginLog');
        $suffixLog =    $objectCall -> getProtectedValue('suffixLog', 'plugin');
        $cacheLogToken = pluginsToolsLog::generateCacheToken($objectCall);

        $objectCall -> setProtectedValue('objectCallLogPath',  pluginsToolsDepedency::mkdirPath($objectCall, $dirLog, $suffixLog.$objectCall -> getId()));
        $objectCall -> setProtectedValue('cacheLogToken',  $cacheLogToken);      
      }

      if (isset($cacheLogToken)) {
        //$cacheLogPath = $objectCall -> getProtectedValue('cacheLogPath', null);
        
        //if (!isset($cacheLogPath)) {
          $cacheLogPath = pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken);
          
          $objectCall -> setProtectedValue('persistLog',     1);
          $objectCall -> setProtectedValue('cacheLogPath',   $cacheLogPath);
            
          //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'persistLog:'.        $objectCall -> getProtectedValue('persistLog'));
          //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'objectCallLogPath:'. $objectCall -> getProtectedValue('objectCallLogPath'));
          //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'cacheLogPath:'.      $objectCall -> getProtectedValue('cacheLogPath'));
        //}
      }
    }
  } 
  
  public static function generateCacheToken($_objectCall, $_cacheLogToken = null) {
    if (isset($_cacheLogToken))
      $cacheLogToken = $_cacheLogToken;
    else {
      pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'generate token for object '.get_class($_objectCall));
      
      $cacheLogToken = '';
      /*switch (get_class($_objectCall)) {
        case 'cmd':     $cacheLogToken = $_objectCall -> getLogicalId().$_objectCall -> getId();
        case 'eqLogic': $cacheLogToken .= $_objectCall -> getEqType_name().$_objectCall -> getId().'_';
                        break;
      }*/
      $cacheLogToken .= $_objectCall -> getEqType_name().$_objectCall -> getId().'_';
      $cacheLogToken .= pluginsToolsDepedency::generateRandomKey();
    }

    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'generate token for object '.get_class($_objectCall));
    pluginsToolsLog::setLog($_objectCall, 'TOKEN', $cacheLogToken);
    
    return $cacheLogToken;
  }   

  public static function execCmdAction($_eqLogicCall, $_cmd, $_options = []) {
    if (is_object($_cmd))
      $cmd = $_cmd;
    else
      $cmd = $_eqLogicCall -> getCmd(null, $_cmdLogicalId);
    
    if (is_object($cmd)) {
      if ($cmd -> getType() == 'action') {
        $cacheLogToken = pluginsToolsLog::generateCacheToken($_eqLogicCall, config::genKey(10));
        if (isset($cacheLogToken)) {
          $_options['cacheLogToken'] =     $cacheLogToken;
          $_options['objectCallLogPath'] = $_eqLogicCall -> getProtectedValue('cacheLogToken');
        }

        pluginsToolsLog::setLog($_eqLogicCall, 'INFO', 'Exécution de la commande ' . $cmd -> getHumanName() . ' ' . __('options', __FILE__) . ':: ' . json_encode($_options, JSON_UNESCAPED_UNICODE), 'success');
        $cmd -> execCmd($_options);
      }
    }
  }
  
  public static function execCmdInfo($_eqLogicCall, $_cmdLogicalId, $_defaultValue = null) {
    if (is_object($cmd = $_eqLogicCall -> getCmd(null, $_cmdLogicalId)) && $cmd -> getType() == 'info')
      return $cmd -> execCmd();
    
    return $_defaultValue;
  } 

  public static function mkdirPath($_dirName, $_fileName = null, $_purgeFile = false) {
    $filePath = log::getPathToLog($_dirName);
    
    if (!file_exists($filePath))
      mkdir($filePath);
    
    if (isset($_fileName)) {
      $filePath .= '/' . $_fileName . '.log';

    //  if (file_exists($filePath) && $_purgeFile)
    //    com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $filePath . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $filePath . ')" > ' . $filePath);
    }

    return $filePath;
  }  
  
  //['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]
  public static function setSub(&$_eqLogic, $_subRecord) {
    if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
      if (!isset($_subRecord['level']))
        $_subRecord['level'] = 'debug';
        
      if ($objectCall -> getProtectedValue('logLevel',0) < pluginsToolsLogConst::LogLevel[$_subRecord['typeLog']])
        return;      
      
      pluginsToolsLog::write($objectCall, $_subRecord);

      if (method_exists($objectCall, 'increaseParentLog'))
        $objectCall -> increaseParentLog();
    }
  }

  //['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]
  public static function unsetSub(&$_eqLogic, $_subRecord) {
    if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
      if (!isset($_subRecord['level']))
        $_subRecord['level'] = 'debug';
        
      if ($objectCall -> getProtectedValue('logLevel',0) < pluginsToolsLogConst::LogLevel[$_subRecord['typeLog']])
        return;      
      
      if (method_exists($objectCall, 'decreaseParentLog'))
        $objectCall -> decreaseParentLog();

      if ($objectCall -> getProtectedValue('logLevel',0) == pluginsToolsDepedencyConst::LogLevel['DebugAdvance'])
        pluginsToolsLog::write($objectCall, $_subRecord);
    }
  }  

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = 'debug') {
    return pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }  
  
  //['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]
  public static function write(&$_eqLogic, $_logRecord) {
    if ($_logRecord['log'] != '') {
      if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
        if (!isset($_logRecord['level']))
          $_logRecord['level'] = 'debug';
        
        if ($objectCall -> getProtectedValue('logLevel',0) < pluginsToolsLogConst::LogLevel[$_logRecord['typeLog']])
          return;
        
        if ($_logRecord['typeLog'] == 'DEBUG_SYS')
          $_logRecord['typeLog'] = 'DEBUG';
        
        if ($_logRecord['typeLog'] == 'ERROR')
          $_logRecord['level'] = 'danger';
       
        if (is_array($_logRecord['log']) || is_object($_logRecord['log'])) {
          $_logRecord['log'] = (array)$_logRecord['log'];
          foreach ($_logRecord['log'] as $logKey => $logValue) {
            if (is_array($logValue) || is_object($logValue)) {
              pluginsToolsDepedency::incLog($objectCall, $_logRecord['typeLog'], $logKey.' => {', $_logRecord['level']);
              pluginsToolsLog::write($objectCall, ['typeLog' => $_logRecord['typeLog'], 'log' => $logValue, 'level' => $_logRecord['level']]);
              pluginsToolsDepedency::unIncLog($objectCall, $_logRecord['typeLog'], '}', $_logRecord['level']);
            }
            else
              pluginsToolsDepedency::addLog($objectCall, $_logRecord['typeLog'], $logKey.' => '.$logValue, $_logRecord['level']);
          }
        }
        else {
          if ($_logRecord['typeLog'] == 'TOKEN') 
            $logMessage = '[TOKEN]'.$_logRecord['log'];
          else {
            $logMessage = '['.date('Y-m-d H:i:s').']['.$_logRecord['typeLog'].']'.str_pad("",8-strlen($_logRecord['typeLog'])," ").str_pad("", $objectCall -> getProtectedValue('parentLog')*3, " ");
            
            if ($_logRecord['level'] != '')
              $logMessage .= '<label class="'.$_logRecord['level'].'" style="margin-bottom: 4px;">'.$_logRecord['log'].'</label>';
            else
              $logMessage .= $_logRecord['log'];
          }
          
          //$cacheLogRes = $objectCall -> getProtectedValue('cacheLogRes');
          //if (isset($cacheLogRes))
          //  fwrite($cacheLogRes, $logMessage);
          //else {
            $fileLogPath = null;
            if (($cacheLogPath = $objectCall -> getProtectedValue('cacheLogPath', '')) != '')
              $fileLogPath = $cacheLogPath;
            //elseif (($objectCallLogPath = $objectCall -> getProtectedValue('objectCallLogPath', '')) != '')
            //  $fileLogPath = $objectCallLogPath;
            
            if (isset($fileLogPath))
              file_put_contents($fileLogPath.'.tmp', $logMessage."\n", FILE_APPEND | LOCK_EX);
            
          //}
        }
      }  
    }

    return true;
  }
  
  public static function persistLog(&$_eqLogic, $_timeOut = 10) {
    if ($_eqLogic -> getProtectedValue('persistLog', 0) == 1) {
      $objectCallLogPath =  $_eqLogic -> getProtectedValue('objectCallLogPath');
      //$cacheLogRes =        $_eqLogic -> getProtectedValue('cacheLogRes');
      $cacheLogPath =       $_eqLogic -> getProtectedValue('cacheLogPath');
      $cacheLogToken =      $_eqLogic -> getProtectedValue('cacheLogToken');  
      
      if (isset($cacheLogPath) && file_exists($cacheLogPath.'.tmp')) {
        $lineList =     file($cacheLogPath.'.tmp');
        $logMessage =   "";

        pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG_SYS', 'Exec persistLog');
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'cacheLogToken:'.$cacheLogToken);
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'objectCallLogPath:'.$objectCallLogPath);
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'cacheLogPath:'.$cacheLogPath);

        foreach ($lineList as $lineKey => $lineMessage) {
          if (preg_match_all("/\[TOKEN\](.*)/", $lineMessage, $matches, PREG_PATTERN_ORDER) && count($matches[1]) > 0 ) {
            pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Sub token '.$matches[1][0].' detected');

            $subCacheLogPath = pluginsToolsLog::mkdirPath('cacheLog', $matches[1][0]);
            $timeOut = 120;
            while (!file_exists($subCacheLogPath)
                   && $timeOut > 0) {
              pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Sub token '.$subCacheLogPath.' not exist, wait 1 seconde');
              $timeOut -= 1;
              sleep(1);
            }
        
            if (file_exists($subCacheLogPath)) {
              pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Sub token '.$subCacheLogPath.' exist, incllude this on log');

              $logMessage .= "\n".file_get_contents($subCacheLogPath);
              unlink($subCacheLogPath);
            }
          }
          else
            $logMessage .= $lineMessage;
        }
          
        if (isset($objectCallLogPath)) {
          $directory =          pluginsToolsLog::mkdirPath('cacheLog');
          $eqLogicType =        $_eqLogic -> getEqType_name();
          $eqLogicId =          $_eqLogic -> getId();
          $objectCallLogList =  [];
          
          pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Scan directory '.$directory);
          $scanned_directory =  array_diff(scandir($directory), array('..', '.'));
          
          foreach ($scanned_directory as $fileKeyId => $fileName) {
            //pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Compare '.$fileName.' with '.$eqLogicType.$eqLogicId.'_');

            if (strpos($fileName, $eqLogicType.$eqLogicId.'_') !== false) {
              pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', '  Add file '.$fileName.' on list');

              $objectCallLogList[filemtime($directory.'/'.$fileName)] = $directory.'/'.$fileName;
            }
          }
          ksort($objectCallLogList);  
          pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', ['List log file' => $objectCallLogList]);
          
          pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Wait previous log is persisted');
          $_timeOut = 120;
          while (array_values($objectCallLogList)[0] != $cacheLogPath.'.tmp'
                 && $_timeOut > 0) {
                   
            pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Wait previous log '.array_values($objectCallLogList)[0].' is persisted');
            foreach ($objectCallLogList as $fileKeyId => $fileName) {
              if (!file_exists($fileName))
                unset($objectCallLogList[$fileKeyId]);
            }
            
            $_timeOut -= 1;
            sleep(1);
          }
          
          file_put_contents($objectCallLogPath, $logMessage, FILE_APPEND | LOCK_EX);
        }
        else
          file_put_contents($cacheLogPath, $logMessage, FILE_APPEND | LOCK_EX);
        
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Rename file '.$cacheLogPath.'.tmp to '. pluginsToolsLog::mkdirPath('cacheLogUnlink', $cacheLogToken));
        //rename($cacheLogPath.'.tmp', pluginsToolsLog::mkdirPath('cacheLogUnlink', $cacheLogToken));
        unlink($cacheLogPath.'.tmp');
      }
    }
  }
  /*
  public static function replaceToken($_eqLogic) {
    $token =            $_eqLogic -> getProtectedValue('cacheLogToken');
    $filePath =         $_eqLogic -> getProtectedValue('objectCallLogPath');
    $fileTokenPath =    $_eqLogic -> getProtectedValue('cacheLogPath');
    $fileTokenContent = pluginsToolsLog::getTokenContent($_eqLogic);
    
    // Method 1
    //$token =            $_eqLogic -> getProtectedValue('cacheLogToken');
    //pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG_SYS', 'replaceToken by file_put_contents');
    //file_put_contents($filePath, str_replace("\n".'[TOKEN]'.$token, $fileTokenContent, file_get_contents($filePath)), LOCK_EX);
    
    // Method 2
    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG_SYS', 'ReplaceToken by sed');

    $fileTokenContentSplit =        str_split($fileTokenContent, 14500);
    $fileTokenContentSplitLastKey = count($fileTokenContentSplit);

    pluginsToolsDepedency::incLog($_eqLogic, 'DEBUG_SYS', 'Content split by '.$fileTokenContentSplitLastKey.' file');

    $sedCmd = 'sed -i "s/\[TOKEN\]'.$token.'/\[TOKEN\]'.$token.'0/" '.$filePath;
    com_shell::execute(system::getCmdSudo() . $sedCmd);
    
    foreach ($fileTokenContentSplit as $fileTokenContentBlockKey => $fileTokenContentBlockValue) {
      $fileTokenTmpName =     $fileTokenPath.'.tmp'.$fileTokenContentBlockKey;
      $fileTokenTmpContent =  'TOKEN::'.$fileTokenContentBlockKey."\\n".str_replace(["\n","/"],["\\n","\/"],$fileTokenContentBlockValue);
      $tokenTmp =             $token.$fileTokenContentBlockKey;
      $tokenTmpNext =         $token.($fileTokenContentBlockKey + 1);
      
      if ($fileTokenContentBlockKey < ($fileTokenContentSplitLastKey - 1))
        $fileTokenTmpContent .= "[TOKEN]".$tokenTmpNext."\\n";

      pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Create file tmp token '.$fileTokenTmpName);
      //file_put_contents($fileTokenTmpName, $fileTokenTmpContent);
      
      $fileTokenTmp = fopen($fileTokenTmpName, "w+");    
      if (flock($fileTokenTmp, LOCK_EX)) {          // acquière un verrou exclusif
        fwrite($fileTokenTmp, $fileTokenTmpContent);
        fflush($fileTokenTmp);    
        flock($fileTokenTmp, LOCK_UN);            // Enlève le verrou
      }
      fclose($fileTokenTmp);      
      
      //$_timeOut =   120;
      //while (!file_exists($fileTokenTmpName)
      //       && $_timeOut > 0) {
      //  $_timeOut -= 1;
      //  sleep(1);
      //}

      $sedCmd = 'sed -i "s/\[TOKEN\]'.$tokenTmp.'/$(cat '.$fileTokenTmpName.')/" '.$filePath;
      
      pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Replace block token '.$tokenTmp);
      pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', '  '.$sedCmd);
      com_shell::execute(system::getCmdSudo() . $sedCmd);
      
      //unlink($fileTokenTmpName);
    }
    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG_SYS');
    // END Method 2
    
    pluginsToolsDepedency::unIncLog($_eqLogic, 'DEBUG_SYS');
    
    
  }
  
  public static function getTokenContent($_eqLogic) {
    $fileTokenPath = $_eqLogic -> getProtectedValue('cacheLogPath');
    
    if (file_exists($fileTokenPath)) {
      pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Return token content on file');
      
      return file_get_contents($fileTokenPath);
    }
    return '';
  }

  public static function deleteTokenContent($_eqLogic) {
    $fileTokenPath = $_eqLogic -> getProtectedValue('cacheLogPath');
    
    pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Delete file token '.$fileTokenPath);
    if (file_exists($fileTokenPath))
      unlink($fileTokenPath);
  }
  */
}
?>