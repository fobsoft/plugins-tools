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
    if (isset($_options['cacheLogDetail'])) {
      $cacheLogDetail = $_options['cacheLogDetail'];
      unset($_options['cacheLogDetail']);
    }
    elseif (is_object($_eqLogic)) {//elseif (is_object(($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic)))) {
      $dirLog =         $_eqLogic -> getProtectedValue('dirLog', 'pluginLog');
      $suffixLog =      $_eqLogic -> getProtectedValue('suffixLog', 'plugin');
      $cacheLogToken =  pluginsToolsLog::generateCacheToken($_eqLogic, $_eqLogic);
      
      $cacheLogDetail = ['objectCallLogPath' => pluginsToolsLog::mkdirPath($dirLog, $suffixLog.$_eqLogic -> getId()),
                         'cacheLogPath' =>      pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp',
                         'parentLog' =>         0,
                         'persistLog' =>        1,
                         'logLevel' =>          $_eqLogic -> getProtectedValue('logLevel')
                        ];                        
    }
    
    // La logique de config de parametre doit se basser sur l'enfant, ceci couvre le flow d'appel sinchrone
    //if (isset($_options['objectCall'])) {
    //  $cacheLogDetail['cacheLogToken'] =  $_options['objectCall'] -> getProtectedValue('cacheLogToken');
    //  $cacheLogDetail['parentLog'] =      $_options['objectCall'] -> getProtectedValue('parentLog');
    //  $cacheLogDetail['logLevel'] =       $_options['objectCall'] -> getProtectedValue('logLevel');
    //}
    //elseif (isset($_options['cacheLogToken'])) {
    //  $objectCall = $_eqLogic;
    //  $objectCall -> setProtectedValue('cacheLogToken', $_options['cacheLogToken']);
    //  $objectCall -> setProtectedValue('parentLog',     $_options['parentLog']);
    //  $objectCall -> setProtectedValue('persistLog',    1);
    //}
    //else
    //  $objectCall = pluginsToolsDepedency::getObjCall($_eqLogic);
    
    
    /*
    if (is_object($objectCall)) {
      $cacheLogDetail['cacheLogToken'] =  $objectCall -> getProtectedValue('cacheLogToken');
      $cacheLogDetail['parentLog'] =      $objectCall -> getProtectedValue('parentLog');
      $cacheLogDetail['logLevel'] =       $objectCall -> getProtectedValue('logLevel');
      
      if (!isset($cacheLogToken)) {
        //if (isset($_options['cacheLogToken'])) {
        //  $objectCall -> setProtectedValue('cacheLogToken',  $_options['cacheLogToken']);
        //  unset($_options['cacheLogToken']);
        //}
        //else {
          $dirLog =       $objectCall -> getProtectedValue('dirLog', 'pluginLog');
          $suffixLog =    $objectCall -> getProtectedValue('suffixLog', 'plugin');

          $objectCall -> setProtectedValue('objectCallLogPath', pluginsToolsLog::mkdirPath($dirLog, $suffixLog.$objectCall -> getId()));
          $objectCall -> setProtectedValue('cacheLogToken',     pluginsToolsLog::generateCacheToken($objectCall, $objectCall));
          $objectCall -> setProtectedValue('persistLog',        1);
        //}
        $objectCall -> setProtectedValue('cacheLogPath',   pluginsToolsLog::mkdirPath('cacheLog', $objectCall -> getProtectedValue('cacheLogToken')));
        //$objectCall -> setProtectedValue('persistLog',     (isset($_options['cacheLogToken'])? $_options['cacheLogToken']:1));
      }
    */  
      foreach ($cacheLogDetail as $cacheLogKey => $cacheLogValue)
        $_eqLogic -> setProtectedValue($cacheLogKey, $cacheLogValue);

      pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Init log parameter');
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'objectCallLogPath:'. $_eqLogic -> getProtectedValue('objectCallLogPath', ''));
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'cacheLogPath:'.      $_eqLogic -> getProtectedValue('cacheLogPath', ''));
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'parentLog:'.         $_eqLogic -> getProtectedValue('parentLog', ''));
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'logLevel:'.          $_eqLogic -> getProtectedValue('logLevel', ''));
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'persistLog:'.        $_eqLogic -> getProtectedValue('persistLog', 0));
      pluginsToolsLog::unIncLog($_eqLogic, 'NONE', 'Init log parameter');
      /*
    if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
      if (!isset($_subRecord['level']))
        $_subRecord['level'] = 'debug';
        
      if ($objectCall -> getProtectedValue('logLevel',pluginsToolsLogConst::LogLevel['DEBUG_SYS']) < pluginsToolsLogConst::LogLevel[$_subRecord['typeLog']])
        return;      
      
      pluginsToolsLog::write($objectCall, $_subRecord);

      if (method_exists($objectCall, 'increaseParentLog'))
        $objectCall -> increaseParentLog();      
      */
    //}
  } 
  
  public static function generateCacheToken($_eqLogic, $_objectToken, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'generateCacheToken');
    if (isset($_cacheLogToken))
      $cacheLogToken = $_cacheLogToken;
    elseif (method_exists($_eqLogic, 'generateCacheToken'))
      $cacheLogToken = $_eqLogic -> generateCacheToken($_objectToken);
    else
      $cacheLogToken = get_class($_eqLogic).$_eqLogic -> getId();
      /*if (is_object($_objectToken)) {
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'for object');
        
        if (method_exists($_objectToken, 'getObject_id')) {
          pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'generate token for object');
          $cacheLogToken .= $_objectToken -> getEqType_name().$_objectToken -> getId();
        }
        else {
          pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'generate token for cmd');
          $cacheLogToken .= $_objectToken -> getEqType().'CMD'.$_objectToken -> getId();
        }
      }
      else
        $cacheLogToken .= $_objectToken;*/
      
    $cacheLogToken .= '_'.time().'_'.pluginsToolsDepedency::generateRandomKey();
    
    pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Token Generated: '.$cacheLogToken);
    pluginsToolsLog::setLog($_eqLogic, 'TOKEN', $cacheLogToken);

    return $cacheLogToken;
  }

  public static function setCacheLogDetailSynchrone($_objectCall, $_objectAction, &$_options, $_cacheLogToken = null) {
    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'setCacheLogDetailSynchrone');
    
    $cacheLogToken = pluginsToolsLog::generateCacheToken($_objectCall, $_objectAction, $_cacheLogToken);
    $_options['cacheLogDetail'] = [
                                   'objectCallLogPath' => pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken),
                                   'cacheLogPath' =>      pluginsToolsLog::mkdirPath('cacheLog', $cacheLogToken).'.tmp',
                                   'parentLog' =>         $_objectCall -> getProtectedValue('parentLog', ''),
                                   'persistLog' =>        1,
                                   'logLevel' =>          $_objectCall -> getProtectedValue('logLevel')
                                  ];
  }
  
  public static function setCacheLogDetailAsynchrone($_objectCall, &$_options) {
    pluginsToolsLog::setLog($_objectCall, 'DEBUG_SYS', 'setCacheLogDetailAsynchrone');
    
    $cacheLogToken = $_objectCall -> getProtectedValue('cacheLogToken', '');
    $_options['cacheLogDetail'] = [
                                   'objectCallLogPath' => null,
                                   'cacheLogPath' =>      $_objectCall -> getProtectedValue('cacheLogPath', ''),
                                   'parentLog' =>         $_objectCall -> getProtectedValue('parentLog', ''),
                                   'persistLog' =>        0,
                                   'logLevel' =>          $_objectCall -> getProtectedValue('logLevel')
                                  ];
  }
  
  public static function getHtmlLogLevelOptions() {
    return '<select class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="logLevel">
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

  public static function setLog(&$_eqLogic, $_typeLog = '', $_log = '', $_level = 'debug') {
    return pluginsToolsLog::write($_eqLogic, ['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]);
  }  
  
  public static function incLog(&$_eqLogic, $_logLevel = '', $_log = '', $_level = '') {
    if (is_object($_eqLogic)) {
      $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
      
      if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_logLevel]
          && $configLogLevel > pluginsToolsLogConst::LogLevelId['NONE']) {
        if ($configLogLevel & pluginsToolsLogConst::LogLevelId['DEBUG_SYS'])
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_logLevel, 'log' => 'BEGIN - '.$_log, 'level' => $_level]);
        else
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_logLevel, 'log' => $_log, 'level' => $_level]);

        if (method_exists($_eqLogic, 'increaseParentLog'))
          $_eqLogic -> increaseParentLog();
      }
    }
  }

  public static function unIncLog(&$_eqLogic, $_logLevel = '', $_log = '', $_level = '') {
    if (is_object($_eqLogic)) {
      $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
      
      if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_logLevel]
          && $configLogLevel != pluginsToolsLogConst::LogLevelId['NONE']) {
        if (method_exists($_eqLogic, 'decreaseParentLog'))
          $_eqLogic -> decreaseParentLog();

        if ($configLogLevel & pluginsToolsLogConst::LogLevelId['DEBUG_SYS'])
          pluginsToolsLog::write($_eqLogic, ['typeLog' => $_logLevel, 'log' => 'END -'.$_log, 'level' => $_level]);
      }
    }    
  }  
  
  //['typeLog' => $_typeLog, 'log' => $_log, 'level' => $_level]
  public static function write(&$_eqLogic, $_logRecord) {
    if ($_logRecord['log'] != '') {
      //if (is_object($objectCall = pluginsToolsDepedency::getObjCall($_eqLogic))) {
      $objectCall = $_eqLogic;
      if (is_object($objectCall)) {
        if (!isset($_logRecord['level']))
          $_logRecord['level'] = 'debug';
        
        $configLogLevel = $_eqLogic -> getProtectedValue('logLevel');
        if ($configLogLevel & pluginsToolsLogConst::LogLevelId[$_logRecord['typeLog']]
            && $configLogLevel != pluginsToolsLogConst::LogLevelId['NONE']) {
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
                $logMessage .= '<label class="'.$_logRecord['level'].'" style="margin-bottom: 4px;">'.htmlspecialchars($_logRecord['log'], ENT_SUBSTITUTE).'</label>';
              else
                $logMessage .= htmlspecialchars($_logRecord['log'], ENT_SUBSTITUTE);
            }
            
            if (($cacheLogPath = $objectCall -> getProtectedValue('cacheLogPath', '')) != '')
              file_put_contents($cacheLogPath, $logMessage."\n", FILE_APPEND | LOCK_EX);
          }
        }
      }  
    }

    // Permet de mettre l'instruction d'ajout de log dans une conditionnel
    return true;
  }
  
  public static function persistLog(&$_eqLogic, $_timeOut = 10) {
    $objectCallLogPath =  $_eqLogic -> getProtectedValue('objectCallLogPath', null);
    $cacheLogPath =       $_eqLogic -> getProtectedValue('cacheLogPath', null);

    pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Exec persistLog');

    if (isset($objectCallLogPath) && isset($cacheLogPath) && file_exists($cacheLogPath)) {
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'objectCallLogPath:'.$objectCallLogPath);
      pluginsToolsLog::setLog($_eqLogic, 'NONE', 'cacheLogPath:'.$cacheLogPath);
      pluginsToolsLog::incLog($_eqLogic, 'NONE', 'Process persist');
      
      $lineList =     file($cacheLogPath);
      $logMessage =   "";

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
          else
            $logMessage .= "\n"."[TOKEN::".$matches[1][0]."] Introuvable";
        }
        elseif (preg_match_all("/\[NONE\](.*)/", $lineMessage, $matches, PREG_PATTERN_ORDER) && count($matches[1]) > 0 )
          continue;
        else
          $logMessage .= $lineMessage;
      }
        
/*      if (quelquechose) {
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
        
        file_put_contents($objectCallLogPath, $logMessage, FILE_APPEND | LOCK_EX);
      }
      else
      */

      file_put_contents($objectCallLogPath, $logMessage, FILE_APPEND | LOCK_EX);
    
      if ($_eqLogic -> getProtectedValue('logLevel') & pluginsToolsLogConst::LogLevel['DEBUG_SYS']) {
        pluginsToolsLog::setLog($_eqLogic, 'DEBUG_SYS', 'Rename file '.$cacheLogPath.'.tmp to '. str_replace('/cacheLog/','/cacheLogUnlink/',$cacheLogPath));
        rename($cacheLogPath, str_replace('/cacheLog/','/cacheLogUnlink/',$cacheLogPath));
      }
      //else
      //  unlink($cacheLogPath);
      
      pluginsToolsLog::unIncLog($_eqLogic, 'NONE', 'Process persist');
    }
    pluginsToolsLog::unIncLog($_eqLogic, 'NONE', 'Exec persistLog');
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