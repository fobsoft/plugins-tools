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
  const LogLevelId =        [
                              'TOKEN' =>                  -1,
                              'NONE' =>                   0,
                              'ERROR' =>                  1,
                              'WARNING' =>                2,
                              'INFO' =>                   4,
                              'DEBUG' =>                  8,
                              'DEBUG2' =>                 16,
                              'DEBUG_SYS' =>              16,
                              'DEBUG_SYS_WIGET_LOG' =>    32,
                              'DEBUG_SYS_COLLECT_INFO' => 64,
                              
                              'HIDE' =>        999 
                            ];  
  const LogLevel =          [
                              'TOKEN' =>                  -1,
                              'NONE' =>                   0,
                              'ERROR' =>                  1,
                              'WARNING' =>                (2+1),    // 3
                              'INFO' =>                   (4+3),    // 7
                              'DEBUG' =>                  (8+7),    // 15
                              'DEBUG2' =>                 (16+15),  // 31 
                              'DEBUG_SYS' =>              (16+15),  // 31 
                              'DEBUG_SYS_WIGET_LOG' =>    32,
                              'DEBUG_SYS_COLLECT_INFO' => 64,
                              
                              'HIDE' =>        999 
                            ];
                              
  const TypeLogPriority =   ['ND' => 0, 'DEBUG_SYS' => 1,'DEBUG2' => 1, 'DEBUG' => 2, 'INFO' => 3, 'WARNING' => 4, 'ERROR' => 5];
}

class pluginsToolsLog {
  public static function init(&$_eqLogic, &$_options = []) {
    if (isset($_options['cacheLogDetail']))
      $cacheLogDetail = $_options['cacheLogDetail'];
    elseif (is_object($_eqLogic) && $_eqLogic -> getProtectedValue('cacheLogPath', 'NULL') == 'NULL') {//elseif (is_object(($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic)))) {
      $dirLog =         $_eqLogic -> getProtectedValue('dirLog', 'pluginLog');
      $suffixLog =      $_eqLogic -> getProtectedValue('suffixLog', 'plugin');
      $cacheLogToken =  pluginsToolsLog::generateCacheToken($_eqLogic, $_eqLogic);
      
      $cacheLogDetail = ['objectCallLogPath' =>     pluginsToolsLog::mkdirPath($dirLog, $suffixLog.$_eqLogic -> getId()),
                         'cacheLogPath' =>          pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp',
                         'parentLog' =>             0,
                         'persistLog' =>            1,
                         'logLevel' =>              $_eqLogic -> getProtectedValue('logLevel'),
                         'objectSrcCallLogPath' =>  pluginsToolsLog::mkdirPath($dirLog, $suffixLog.$_eqLogic -> getId()),
                         'objectSrcId' =>           $_eqLogic -> getId()
                         //'objectSrcClass' =>        $_eqLogic -> getClassName(),
                        ];                        
    }
    
    if (isset($cacheLogDetail)) {
      unset($_options['cacheLogDetail']);
      
      foreach ($cacheLogDetail as $cacheLogKey => $cacheLogValue)
        $_eqLogic -> setProtectedValue($cacheLogKey, $cacheLogValue);

      pluginsToolsLog::incLog($_eqLogic, 'DEBUG_SYS', 'Init log parameter');
      pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', $cacheLogDetail);
      pluginsToolsLog::unIncLog($_eqLogic, 'DEBUG_SYS', 'Init log parameter');
    }
  } 
  
  public static function generateCacheToken($_eqLogic, $_objectToken, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'generateCacheToken');
    if (isset($_cacheLogToken))
      $cacheLogToken = $_cacheLogToken;
    elseif (method_exists($_eqLogic, 'generateCacheToken'))
      $cacheLogToken = $_eqLogic -> generateCacheToken($_objectToken);
    else
      $cacheLogToken = get_class($_eqLogic).$_eqLogic -> getId();
      
    return $cacheLogToken . '_' . ((string)(microtime(true)*10000)) . '_' . pluginsToolsDepedency::generateRandomKey();
  }

  public static function setCacheLogDetailSchedule($_objectCall, $_objectAction, &$_options, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'setCacheLogDetailSchedule');
    
    $cacheLogToken =  pluginsToolsLog::generateCacheToken($_objectCall, $_objectAction, $_cacheLogToken);
    $_options['cacheLogDetail'] = [
                                   'objectCallLogPath' =>     $_objectCall -> getProtectedValue('objectSrcCallLogPath', ''),
                                   'cacheLogPath' =>          pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp',
                                   'parentLog' =>             0,
                                   'persistLog' =>            1,
                                   'logLevel' =>              $_objectCall -> getProtectedValue('logLevel'),
                                   'objectSrcCallLogPath' =>  $_objectCall -> getProtectedValue('objectSrcCallLogPath', ''),
                                   'objectSrcId' =>           $_objectCall -> getProtectedValue('objectSrcId', '')
                                   //'objectSrcClass' =>        $_objectCall -> getProtectedValue('objectSrcClass', ''),
                                  ];
  }
  
  public static function setCacheLogDetailSynchrone($_objectCall, $_objectAction, &$_options, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'setCacheLogDetailSynchrone');
    
    $cacheLogToken = pluginsToolsLog::generateCacheToken($_objectCall, $_objectAction, $_cacheLogToken);

    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'Token Generated: '.$cacheLogToken);
    pluginsToolsLog::setLog($_objectCall, 'TOKEN', $cacheLogToken);
    
    $_options['cacheLogDetail'] = [
                                   'objectCallLogPath' =>     pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken),
                                   'cacheLogPath' =>          pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp',
                                   'parentLog' =>             $_objectCall -> getProtectedValue('parentLog', ''),
                                   'persistLog' =>            1,
                                   'logLevel' =>              $_objectCall -> getProtectedValue('logLevel'),
                                   'objectSrcCallLogPath' =>  $_objectCall -> getProtectedValue('objectSrcCallLogPath', ''),
                                   'objectSrcId' =>           $_objectCall -> getProtectedValue('objectSrcId', '')
                                   //'objectSrcClass' =>        $_objectCall -> getProtectedValue('objectSrcClass', ''),
                                  ];
  }
  
  public static function setCacheLogDetailAsynchrone($_objectCall, &$_options) {
    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'setCacheLogDetailAsynchrone');
    
    $cacheLogToken = $_objectCall -> getProtectedValue('cacheLogToken', '');
    $_options['cacheLogDetail'] = [
                                   'objectCallLogPath' =>     null,
                                   'cacheLogPath' =>          $_objectCall -> getProtectedValue('cacheLogPath', ''),
                                   'parentLog' =>             $_objectCall -> getProtectedValue('parentLog', ''),
                                   'persistLog' =>            0,
                                   'logLevel' =>              $_objectCall -> getProtectedValue('logLevel'),
                                   'objectSrcCallLogPath' =>  $_objectCall -> getProtectedValue('objectSrcCallLogPath', ''),
                                   'objectSrcId' =>           $_objectCall -> getProtectedValue('objectSrcId', '')
                                   //'objectSrcClass' =>        $_objectCall -> getProtectedValue('objectSrcClass', ''),
                                  ];
  }
  
  public static function getHtmlLogLevelOptions($_attributeName = 'eqLogicAttr') {
    return '<select class="form-control '.$_attributeName.'" data-l1key="configuration" data-l2key="logLevel">
              <option value="'. pluginsToolsLogConst::LogLevel['NONE']      .'">Aucun</option>
              <option value="'. pluginsToolsLogConst::LogLevel['ERROR']     .'">Error</option>
              <option value="'. pluginsToolsLogConst::LogLevel['WARNING']   .'">Warning</option>
              <option value="'. pluginsToolsLogConst::LogLevel['INFO']      .'">Info</option>
              <option value="'. pluginsToolsLogConst::LogLevel['DEBUG']     .'">Debug</option>
              <option value="'. pluginsToolsLogConst::LogLevel['DEBUG_SYS'] .'">Debug Avancé</option>                  
            </select>';
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

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = null) {
    return pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }  
  
  public static function incLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = null, $_showArray = false) {
    if (is_object($_eqLogic)) {
      $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
      
      if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_typeLog]
          || $_typeLog == 'NONE') {
            
        if (is_array($_log)) {
          $nextLine = array_values($_log)[0];
          $_log =     array_keys($_log)[0];
        }
        
        if ($configLogLevel & pluginsToolsLogConst::LogLevelId['DEBUG_SYS'] && !$_showArray)
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => 'BEGIN - '.$_log, 'level' => $_level]);
        else
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);

        if (method_exists($_eqLogic, 'getProtectedValue'))
          $_eqLogic -> setProtectedValue('parentLog', ($_eqLogic -> getProtectedValue('parentLog', 0) + 1));
        
        if (isset($nextLine))
          pluginsToolsLog::setLog($_eqLogic, $_typeLog, $nextLine, $_level);
      }
    }
  }

  public static function unIncLog(&$_eqLogic, $_logLevel = '', $_log = '', $_level = null, $_showArray = false) {
    if (is_object($_eqLogic)) {
      $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
      
      if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_logLevel]
          || $_logLevel == 'NONE') {
        if (method_exists($_eqLogic, 'getProtectedValue'))
          $_eqLogic -> setProtectedValue('parentLog', ($_eqLogic -> getProtectedValue('parentLog', 1) - 1));

        if ($configLogLevel & pluginsToolsLogConst::LogLevelId['DEBUG_SYS'] && !$_showArray)
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_logLevel, 'log' => 'END -'.$_log, 'level' => $_level]);
      }
    }    
  }  
  
  public static function write(&$_eqLogic, $_logRecord) {
    if (is_object($_eqLogic) && $_logRecord['log'] != '') {
      $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
      if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_logRecord['typeLog']]
          || $_logRecord['typeLog'] == 'NONE') {
        if ($_logRecord['typeLog'] == 'DEBUG_SYS')
          $_logRecord['typeLog'] = 'DEBUG';
        if ($_logRecord['typeLog'] == 'DEBUG_SYS_COLLECT_INFO')
          $_logRecord['typeLog'] = 'DEBUG';

        if (!isset($_logRecord['level'])) {
          switch ($_logRecord['typeLog']) {
            case 'ERROR': $_logRecord['level'] = 'danger';
                          break;
            default:      $_logRecord['level'] = strtolower($_logRecord['typeLog']);
                          break;
          }
        }
        
        if (is_array($_logRecord['log']) || is_object($_logRecord['log'])) {
          $_logRecord['log'] = (array)$_logRecord['log'];
          foreach ($_logRecord['log'] as $logKey => $logValue) {
            if (is_array($logValue) || is_object($logValue)) {   
              pluginsToolsLog::incLog($_eqLogic, $_logRecord['typeLog'], (string)$logKey, $_logRecord['level'], true);
              pluginsToolsLog::setLog($_eqLogic, $_logRecord['typeLog'], $logValue, $_logRecord['level']);
              pluginsToolsLog::unIncLog($_eqLogic, $_logRecord['typeLog'], '', $_logRecord['level'], true);
            }
            elseif (!is_numeric($logKey))
              pluginsToolsLog::setLog($_eqLogic, $_logRecord['typeLog'], $logKey.': '.$logValue, $_logRecord['level']);
            else
              pluginsToolsLog::setLog($_eqLogic, $_logRecord['typeLog'], $logValue, $_logRecord['level']);
          }
        }
        else {
          if ($_logRecord['typeLog'] == 'TOKEN') 
            $logMessage = '[TOKEN]'.$_logRecord['log'];
          else {
            $logMessage = date('Y-m-d H:i:s').' ['.$_logRecord['typeLog'].']'.str_pad("",8-strlen($_logRecord['typeLog'])," ").str_pad("", $_eqLogic -> getProtectedValue('parentLog')*3, " ");
            
            if ($_logRecord['level'] != '')
              $logMessage .= '<label class="'.$_logRecord['level'].'" style="margin-bottom: 4px;">'.htmlspecialchars($_logRecord['log'], ENT_SUBSTITUTE).'</label>';
            else
              $logMessage .= htmlspecialchars($_logRecord['log'], ENT_SUBSTITUTE);
          }
          
          if (($cacheLogPath = $_eqLogic -> getProtectedValue('cacheLogPath', '')) != '')
            file_put_contents($cacheLogPath, $logMessage."\n", FILE_APPEND | LOCK_EX);
        }
      }
    }

    // Permet de mettre l'instruction d'ajout de log dans une conditionnel
    return true;
  }
  
  public static function persistLog(&$_eqLogic, $_timeOut = 10) {
    $objectCallLogPath =  $_eqLogic -> getProtectedValue('objectCallLogPath', null);
    $cacheLogPath =       $_eqLogic -> getProtectedValue('cacheLogPath', null);
    $logLevel =           $_eqLogic -> getProtectedValue('logLevel');
    $persistLog =         $_eqLogic -> getProtectedValue('persistLog', 0);

    if ($_eqLogic -> getProtectedValue('persistLog', 0) == 1) {
      pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Process persist');

      if (isset($cacheLogPath) && isset($objectCallLogPath) && is_file($cacheLogPath)) {
        $logMessage =   "";
        $lineList =     file($cacheLogPath);
              
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'objectCallLogPath:'.(isset($objectCallLogPath)? $objectCallLogPath:''));
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'cacheLogPath:'.     (isset($cacheLogPath)? $cacheLogPath:''));
        pluginsToolsLog::setLog($_eqLogic, 'NONE', 'logLevel:'.         (isset($logLevel)? $logLevel:''));

        foreach ($lineList as $lineKey => $lineMessage) {
          if (preg_match_all("/(\[TOKEN\]|\[NONE\])(.*)/", $lineMessage, $matches, PREG_SET_ORDER) && count($matches) > 0) {
            if ($matches[0][1] == '[NONE]')
              continue;
            
            pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Detected sub token '.json_encode($matches));
            $subCacheToken =   $matches[0][2];
            $subCacheLogPath = pluginsToolsLog::mkdirPath('cacheLog', $subCacheToken);
            
            pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Detected sub token '.$subCacheToken.'('.$subCacheLogPath.')');

            if (file_exists($subCacheLogPath.'.tmp')) {
              $timeOut = 5;
              while (!file_exists($subCacheLogPath.'.tmp')
                     && $timeOut > 0) {
                pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Sub token '.$subCacheLogPath.' not exist, wait 1 seconde');
                $timeOut -= 1;
                sleep(1);
              }
            }
        
            if (file_exists($subCacheLogPath)) {
              pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Sub token '.$subCacheLogPath.' exist, incllude this on log');

              $logMessage .= file_get_contents($subCacheLogPath)."\n";
              unlink($subCacheLogPath);              
            }
            else
              $logMessage .= "[TOKEN::".$subCacheToken."] Introuvable"."\n";
          }
          else
            $logMessage .= $lineMessage;
        }
            
        /*if (strpos($objectCallLogPath, '/cacheLog/') === false) {
          $directory =          pluginsToolsLog::mkdirPath('cacheLog');
          $subFileName =        array_values(explode('_', str_replace(pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp', '', $cacheLogPath)))[0];
          $eqLogicId =          $_eqLogic -> getId();
          $objectCallLogList =  [];
          
          pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Scan directory '.$directory.' for extract subFileName '.$subFileName);
          $scanned_directory =  array_diff(scandir($directory), array('..', '.'));
          
          foreach ($scanned_directory as $fileKeyId => $fileName) {
            if (strpos($fileName, $subFileName.'_') !== false) {
              pluginsToolsLog::setLog($_eqLogic, 'NONE', '  Add file '.$fileName.' on list');

              $objectCallLogList[] = $directory.'/'.$fileName;
            }
          }
          sort($objectCallLogList);  
          pluginsToolsLog::setLog($_eqLogic, 'NONE', ['List log file' => $objectCallLogList]);
          pluginsToolsLog::unIncLog($_eqLogic, 'NONE', 'Scan directory '.$directory.' for extract subFileName '.$subFileName);
          
          pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Wait previous log is persisted');
          $timeOut = 5;
          do {
            $nextFileLog =  array_values($objectCallLogList)[0];
            $timeOut -= 1;
            pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Check if next log '.$nextFileLog.' is this log '.$cacheLogPath);
            
            if ($nextFileLog != $cacheLogPath) {
              pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Wait previous log '.$nextFileLog.' is persisted');
              if (!file_exists($nextFileLog)) {
                pluginsToolsLog::setLog($_eqLogic, 'NONE', 'File name '.$nextFileLog.' is deleted, delete record file');                
                array_shift($objectCallLogList);
              }
              pluginsToolsLog::unIncLog($_eqLogic, 'NONE');
            }
            
            pluginsToolsLog::unIncLog($_eqLogic, 'NONE');
          }
          while ($nextFileLog != $cacheLogPath
                 && $timeOut > 0);
        }*/

        pluginsToolsLog::unIncLog($_eqLogic, 'NONE', 'Write log on '.$objectCallLogPath);
        file_put_contents($objectCallLogPath, $logMessage, FILE_APPEND | LOCK_EX);

        if ($logLevel & pluginsToolsLogConst::LogLevelId['DEBUG_SYS']) {
          pluginsToolsLog::setLog($_eqLogic, 'NONE', 'Rename file '.$cacheLogPath.' to '. str_replace('/cacheLog/','/cacheLogUnlink/',$cacheLogPath));
          rename($cacheLogPath, str_replace('/cacheLog/','/cacheLogUnlink/',$cacheLogPath));
        }
        else
          unlink($cacheLogPath);
      }
    }
  }
  
  /*public static function purgeLog(&$_eqLogic) {
    $maxLineLog =         5000;//config::byKey('maxLineLog', $_eqLogic -> getProtectedValue('className'), 5000);
    $objectCallLogPath =  $objectCallLogPath =  $_eqLogic -> getProtectedValue('objectCallLogPath', null);
    $pluginPath =         log::getPathToLog('pluginLog').'/plugin' . $_eqLogic -> getId() . '.log';

    if ($objectCallLogPath == $pluginPath) {
      if (file_exists($pluginPath)) {
        try {
          com_shell::execute(system::getCmdSudo() . 'chmod 664 ' . $path . ' > /dev/null 2>&1;echo "$(tail -n ' . $maxLineLog . ' ' . $pluginPath . ')" > ' . $pluginPath);
        } 
        catch (\Exception $e) {
        }
      }
    }
  } */ 
}
?>