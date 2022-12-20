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

// KEY DOWN
  document.onkeydown = function(event) {
    if (jeedomUtils.getOpenedModal()) return

    if ((event.ctrlKey || event.metaKey) && event.which == 83) { //s
      event.preventDefault()
      if ($('.eqLogicAction[data-action=save]').is(':visible')) {
        $(".eqLogicAction[data-action=save]").click()
        return
      }
    }

    if ((event.ctrlKey || event.metaKey) && event.which == 76) { //l
      console.log("show log");
      event.preventDefault()
      if ($('#btShowLog').is(':visible')) {
        $('#md_modal').dialog({title: "{{Log d'exécution}}"}).load('index.php?v=d&p=dashboard&modal=pluginsToolsDepedencyLog.execution&eqType=' + eqType + '&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
        return
      }      
    }
  }
  
// EQUIPEMENT GENERIC FUNCTION
  // Affichage des log
  $('.eqLogicAction[data-action=showLog]').off('click').on('click', function() {
    $('#md_modal').dialog({
      title: "{{Log d'exécution}}"
    }).load('index.php?v=d&p=dashboard&modal=pluginsToolsDepedencyLog.execution&eqType=' + eqType + '&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
  })
  
  // Effacer un block
  $("body").off('click','.bt_remove').on('click','.bt_remove',function () {
    var dataMsg =       $(this).attr('data-msg');
    var dataLiClosest = $(this).attr('data-li-closest');
    var dataClosest =   $(this).attr('data-closest');
    var dataConfirm =   $(this).attr('data-confirm');
    var confirmRemove = true;
    
    if (isset(dataConfirm) && isset(dataMsg) && dataConfirm == 1) {
      bootbox.confirm(dataMsg, function (result) {
        confirmRemove = (result !== null);
      });
    }
    
    if (confirmRemove) {
      if (isset(dataLiClosest))
        $(dataLiClosest).remove();
      $(this).closest('.' + dataClosest).remove();
    }
  });
  
  // Renommer un block
  $('body').off('click','.bt_rename').on('click','.bt_rename',  function () {
    var dataClosest =   $(this).attr('data-closest');
    var formatFnct =    $(this).attr('data-formatFnct');
    var el =            $(this);
    bootbox.prompt("{{Nouveau nom ?}}", function (result) {
      if (result !== null && result != '') {
        var previousName = el.text();
        
        if (isset(formatFnct)) {
          result = callFunct(formatFnct, [result], {});
        }
        
        if (result != '') {
          el.text(result);
          el.closest('.panel.panel-default').find('span.name').text(result);
          if (el.hasClass('zoneAttr')) {
            $('.modeAttr[data-l1key=' + dataClosest + ']').each(function () {
              if ($(this).text() == previousName) {
                $(this).text(result);
              }
            });
          }
        }
      }
    });
  });  

// AJOUTER UN BLOCK DE CONFIGURATION D'UN EQUIPEMENT
function callFunct(_fctName, _defaultArguments, _arrayCallArguments) {
  var fn = window[_fctName];
  if (typeof fn !== 'function')
    return;

  if (_arrayCallArguments.length > 0) {
    for (var i in _arrayCallArguments)
      fn.apply(window, _defaultArguments.concat([_arrayCallArguments[i]]));
  }
  else
    fn.apply(window, _defaultArguments);
} 

// FUNCTION POUR L'AFFICHAGE DES LOGS
  if (isset(jeedom.log.colorScReplacement)) {
    jeedom.log.colorScReplacement['Begin'] =                    {'txt': ' -- BEGIN',     'replace': '<strong> -- BEGIN : </strong>'}
    jeedom.log.colorScReplacement['End'] =                      {'txt': ' -- END',       'replace': '<strong> -- END : </strong>'}

    if (jeedom.log.colorScReplacement.hasOwnProperty('execCmd')) {
      delete jeedom.log.colorScReplacement['execCmd'];
    }
  }  
      
  jeedom.log.autoupdate = function(_params) {
    if (!isset(_params['once'])) {
      _params['once'] = 0
    }
    if (!isset(_params.callNumber)) {
      _params.callNumber = 0
    }
    if (!isset(_params.log)) {
      console.log('[jeedom.log.autoupdate] No logfile')
      return
    }
    if (!isset(_params.display)) {
      console.log('[jeedom.log.autoupdate] No display')
      return
    }
    if (!_params['display'].is(':visible')) {
      return
    }
    if (_params.callNumber > 0 && isset(_params['control']) && _params['control'].attr('data-state') != 1) {
      return
    }
    if (_params.callNumber > 0 && isset(jeedom.log.currentAutoupdate[_params.display.uniqueId().attr('id')]) && jeedom.log.currentAutoupdate[_params.display.uniqueId().attr('id')].log != _params.log) {
      return
    }
    if (_params.callNumber == 0) {
      if (isset(_params.default_search)) {
        _params['search'].value(_params.default_search);
      } else {
        _params['search'].value('');
      }
      _params.display.scrollTop(_params.display.height() + 200000);
      if (_params['control'].attr('data-state') == 0 && _params['once'] == 0) {
        _params['control'].attr('data-state', 1);
      }
      _params['control'].off('click').on('click', function() {
        if ($(this).attr('data-state') == 1) {
          $(this).attr('data-state', 0);
          $(this).removeClass('btn-warning').addClass('btn-success');
          $(this).html('<i class="fa fa-play"></i><span class="hidden-768"> {{Reprendre}}</span>');
        } else {
          $(this).removeClass('btn-success').addClass('btn-warning');
          $(this).html('<i class="fa fa-pause"></i><span class="hidden-768"> {{Pause}}</span>');
          $(this).attr('data-state', 1);
          _params.display.scrollTop(_params.display.height() + 200000);
          _params['once'] = 0;
          jeedom.log.autoupdate(_params);
        }
      });

      _params['search'].off('keypress').on('keypress', function() {
        if (_params['control'].attr('data-state') == 0) {
          _params['control'].trigger('click');
        }
      });
    }
    _params.callNumber++;
    jeedom.log.currentAutoupdate[_params.display.uniqueId().attr('id')] = {
      log: _params.log
    };

    if (_params.callNumber > 0 && (_params.display.scrollTop() + _params.display.innerHeight() + 1) < _params.display[0].scrollHeight) {
      if (_params['control'].attr('data-state') == 1) {
        _params['control'].trigger('click');
      }
      return;
    }

    jeedom.log.get({
      log: _params.log,
      slaveId: _params.slaveId,
      global: (_params.callNumber == 1),
      success: function(result) {
        var log = ''
        var line
        var isSysLog = (_params.display[0].id == 'pre_globallog') ? true : false
        var isScenaroLog = (_params.display[0].id == 'pre_scenariolog') ? true : false

        if ($.isArray(result)) {
          //line by line, numbered for system log:
          for (var i in result.reverse()) {
        //    if (!isset(_params['search']) || _params['search'].value() == '' || result[i].toLowerCase().indexOf(_params['search'].value().toLowerCase()) != -1) {
              log += result[i] + "\n"
        //    }
          }
        }

        var colorMe = false
        var isAuto = ($rawLogCheck.attr('autoswitch') == 1) ? true : false
        var isLong = (log.length > jeedom.log.coloredThreshold) ? true : false

        if (!$rawLogCheck.is(':checked') && !isLong) {
          colorMe = true
        } else if (isLong && !isAuto && !$rawLogCheck.is(':checked')) {
          colorMe = true
        } else if (isLong && isAuto && _params.callNumber == 1) {
          colorMe = false
          $rawLogCheck.prop('checked', true)
        } else if (!isLong && isAuto && _params.callNumber == 1) {
          colorMe = true
          $rawLogCheck.prop('checked', false)
        }

        if (colorMe) {
          log = jeedom.log.stringColorReplace(log)
          log = jeedom.log.scenarioColorReplace(log)
          _params.display.html(log)
        } else {
          log = jeedom.log.scenarioRemouveHtmlTag(log)
          _params.display.text(log)
        }

        if (_params['once'] != 1) {
          _params.display.scrollTop(_params.display.height() + 200000);
          if (jeedom.log.timeout !== null) {
            clearTimeout(jeedom.log.timeout)
          }
          jeedom.log.timeout = setTimeout(function() {
            jeedom.log.autoupdate(_params)
          }, 1000)
        }
      },
      error: function() {
        if (jeedom.log.timeout !== null) {
          clearTimeout(jeedom.log.timeout);
        }
        jeedom.log.timeout = setTimeout(function() {
          jeedom.log.autoupdate(_params)
        }, 1000);
      },
    });
  }

  jeedom.log.htmlTag = {
    '<label class="warning" style="margin-bottom: 4px;" style="width: 48px;">': '',
    '<label class="info" style="margin-bottom: 4px;" style="width: 48px;">': '',
    '<label class="error" style="margin-bottom: 4px;" style="width: 48px;">': '',
    '<label class="success" style="margin-bottom: 4px;" style="width: 48px;">': '',
    '<label class="debug" style="margin-bottom: 4px;" style="width: 48px;">': '',
    '</label>': ''
  }

  jeedom.log.scenarioRemouveHtmlTag = function(_str) {
    for (var re in jeedom.log.htmlTag) {
      _str = _str.split(re).join(jeedom.log.htmlTag[re])
    }
    return _str
  }