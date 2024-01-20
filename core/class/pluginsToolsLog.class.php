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
                              'DEBUG_SYS' =>    16, // Calcul, End part after begin
                              'HIDE' =>        999 
                             ];
                              
  const TypeLogPriority =  array('ND' => 0, 'DEBUG_ADV' => 1, 'DEBUG' => 2, 'INFO' => 3, 'WARNING' => 4, 'ERROR' => 5);
}

class pluginsToolsLog {
  public static function init(&$_eqLogic, &$_options = []) {
    // La logique de config de parametre doit se basser sur l'enfant, ceci couvre le flow d'appel sinchrone
    if (isset($_options['objectCall']))
      $objectCall = $_options['objectCall'];
    else
      $objectCall = pluginsToolsDepedency::getObjCall($_eqLogic);
    
    if (is_object($objectCall)) {
      $cacheLogToken = $objectCall -> getProtectedValue('cacheLogToken');
      
      if (!isset($cacheLogToken)) {
        if (isset($_options['cacheLogToken'])) {
          $objectCall -> setProtectedValue('cacheLogToken',  $_options['cacheLogToken']);
          unset($_options['cacheLogToken']);
        }
        else {
          $dirLog =       $objectCall -> getProtectedValue('dirLog', 'pluginLog');
          $suffixLog =    $objectCall -> getProtectedValue('suffixLog', 'plugin');

          $objectCall -> setProtectedValue('objectCallLogPath', pluginsToolsLog::mkdirPath($dirLog, $suffixLog.$objectCall -> getId()));
          $objectCall -> setProtectedValue('cacheLogToken',     pluginsToolsLog::generateCacheToken($objectCall, $objectCall));
          $objectCall -> setProtectedValue('persistLog',        1);
        }
        $objectCall -> setProtectedValue('cacheLogPath',   pluginsToolsLog::mkdirPath('cacheLog', $objectCall -> getProtectedValue('cacheLogToken')));
        //$objectCall -> setProtectedValue('persistLog',     (isset($_options['cacheLogToken'])? $_options['cacheLogToken']:1));
      }

      //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'persistLog:'.        $objectCall -> getProtectedValue('persistLog'));
      //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'objectCallLogPath:'. $objectCall -> getProtectedValue('objectCallLogPath'));
      //pluginsToolsLog::setLog($objectCall, 'DEBUG_SYS', 'cacheLogPath:'.      $objectCall -> getProtectedValue('cacheLogPath'));
    }
  } 
  
  public static function generateCacheToken($_eqLogic, $_objectToken, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_eqLogic, 'INFO', 'generateCacheToken');
    if (isset($_cacheLogToken))
      $cacheLogToken = $_cacheLogToken;
    else {
      $cacheLogToken = '';
      if (is_object($_objectToken)) {
        pluginsToolsLog::setLog($_eqLogic, 'INFO', 'for object');
        
        if (method_exists($_objectToken, 'getObject_id')) {
          pluginsToolsLog::setLog($_eqLogic, 'INFO', 'generate token for object');
          $cacheLogToken .= $_objectToken -> getEqType_name().$_objectToken -> getId();
        }
        else {
          pluginsToolsLog::setLog($_eqLogic, 'INFO', 'generate token for cmd');
          $cacheLogToken .= $_objectToken -> getEqType().'CMD'.$_objectToken -> getId();
        }
      }
      else
        $cacheLogToken .= $_objectToken;
      
      $cacheLogToken .= '_'.time().'_'.pluginsToolsDepedency::generateRandomKey();
      
    }
    
    pluginsToolsLog::setLog($_eqLogic, 'INFO', 'Token Generated: '.$cacheLogToken);
    pluginsToolsLog::setLog($_eqLogic, 'TOKEN', $cacheLogToken);

    return $cacheLogToken;
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
        
      if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) < pluginsToolsLogConst::LogLevel[$_subRecord['typeLog']])
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
        
      if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) < pluginsToolsLogConst::LogLevel[$_subRecord['typeLog']])
        return;      
      
      if (method_exists($objectCall, 'decreaseParentLog'))
        $objectCall -> decreaseParentLog();

      if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) == pluginsToolsLogConst::LogLevel['DebugAdvance'])
        pluginsToolsLog::write($objectCall, $_subRecord);
    }
  }  

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = 'debug') {
    return pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }  
  
  public static function incLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    pluginsToolsLog::setSub($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }

  public static function unIncLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = '') {
    pluginsToolsLog::unsetSub($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }  
  
  //['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]
  public static function write(&$_eqLogic, $_logRecord) {
    if ($_logRecord['log'] != '') {
      if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
        if (!isset($_logRecord['level']))
          $_logRecord['level'] = 'debug';
        
        if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) < pluginsToolsLogConst::LogLevel[$_logRecord['typeLog']])
          return;
        
        if ($_logRecord['typeLog'] == 'DEBUG_SYS')
          $_logRecord['typeLog'] = 'DEBUG';
        
        if ($_logRecord['typeLog'] == 'ERROR')
          $_logRecord['level'] = 'danger';
       
        if (is_array($_logRecord['log']) || is_object($_logRecord['log'])) {
          $_logRecord['log'] = (array)$_logRecord['log'];
          foreach ($_logRecord['log'] as $logKey => $logValue) {
            if (is_array($logValue) || is_object($logValue)) {
              pluginsToolsLog::incLog($objectCall, $_logRecord['typeLog'], $logKey.' => {', $_logRecord['level']);
              pluginsToolsLog::setLog($objectCall, $_logRecord['typeLog'], $logValue, $_logRecord['level']);
              pluginsToolsLog::unIncLog($objectCall, $_logRecord['typeLog'], '}', $_logRecord['level']);
            }
            else
              pluginsToolsLog::setLog($objectCall, $_logRecord['typeLog'], $logKey.' => '.$logValue, $_logRecord['level']);
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
          
          if (($cacheLogPath = $objectCall -> getProtectedValue('cacheLogPath', '')) != '')
            file_put_contents($cacheLogPath.'.tmp', $logMessage."\n", FILE_APPEND | LOCK_EX);
        }
      }  
    }

    // Permet de mettre l'instruction d'ajout de log dans une conditionnel
    return true;
  }
  
  public static function persistLog(&$_eqLogic, $_timeOut = 10) {
    if ($_eqLogic -> getProtectedValue('persistLog', 0) == 1) {
      $objectCallLogPath =  $_eqLogic -> getProtectedValue('objectCallLogPath');
      $cacheLogPath =       $_eqLogic -> getProtectedValue('cacheLogPath');
      $cacheLogToken =      $_eqLogic -> getProtectedValue('cacheLogToken');  
      
      if (isset($cacheLogPath) && file_exists($cacheLogPath.'.tmp')) {
        $lineList =     file($cacheLogPath.'.tmp');
        $logMessage =   "";

        pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Exec persistLog');
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'cacheLogToken:'.$cacheLogToken);
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'objectCallLogPath:'.$objectCallLogPath);
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'cacheLogPath:'.$cacheLogPath);
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'lineCount:'.count($lineList));

        foreach ($lineList as $lineKey => $lineMessage) {
          if (preg_match_all("/\[TOKEN\](.*)/", $lineMessage, $matches, PREG_PATTERN_ORDER) && count($matches[1]) > 0 ) {
            pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Sub token '.$matches[1][0].' detected');

            $subCacheLogPath = pluginsToolsLog::mkdirPath('cacheLog', $matches[1][0].'.tmp');
            $timeOut = 120;
            while (!file_exists($subCacheLogPath)
                   && $timeOut > 0) {
              pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Sub token '.$subCacheLogPath.' not exist, wait 1 seconde');
              $timeOut -= 1;
              sleep(1);
            }
        
            if (file_exists($subCacheLogPath)) {
              pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Sub token '.$subCacheLogPath.' exist, incllude this on log');

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
          
          pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Scan directory '.$directory);
          $scanned_directory =  array_diff(scandir($directory), array('..', '.'));
          
          foreach ($scanned_directory as $fileKeyId => $fileName) {
            if (strpos($fileName, $eqLogicType.$eqLogicId.'_') !== false) {
              pluginsToolsLog::setLog($_eqLogic, 'NONE', '  Add file '.$fileName.' on list');

              $objectCallLogList[] = $directory.'/'.$fileName;
            }
          }
          sort($objectCallLogList);  
          pluginsToolsLog::setLog($_eqLogic, 'NONE', ['List log file' => $objectCallLogList]);
          
          pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Wait previous log is persisted');
          $timeOut = 120;
          do {
            $nextFileLog =  array_values($objectCallLogList)[0];
            $timeOut -= 1;
            pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Check if next log '.$nextFileLog.' is this log '.$cacheLogPath.'.tmp');
            
            if ($nextFileLog != $cacheLogPath.'.tmp') {
              pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Wait previous log '.$nextFileLog.' is persisted');
              if (!file_exists($nextFileLog)) {
                pluginsToolsLog::setLog($_eqLogic, 'NONE', 'File name '.$nextFileLog.' is deleted, delete record file');                
                array_shift($objectCallLogList);
              }
              pluginsToolsLog::unIncLog($_eqLogic, 'NONE');
            }
            
            pluginsToolsLog::unIncLog($_eqLogic, 'NONE');
          }
          while ($nextFileLog != $cacheLogPath.'.tmp'
                 && $timeOut > 0);
          
          file_put_contents($objectCallLogPath, $logMessage, FILE_APPEND | LOCK_EX);
        }
        else
          file_put_contents($cacheLogPath, $logMessage, FILE_APPEND | LOCK_EX);
        
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Rename file '.$cacheLogPath.'.tmp to '. pluginsToolsLog::mkdirPath('cacheLogUnlink', $cacheLogToken));
        rename($cacheLogPath.'.tmp', pluginsToolsLog::mkdirPath('cacheLogUnlink', $cacheLogToken));
        //unlink($cacheLogPath.'.tmp');
      }
    }
  }
}
?>