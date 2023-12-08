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

//console.log("colorScReplacement: " + JSON.stringify(jeedom.log.colorScReplacement, null, 4));

var pluginsToolsDepedency =  function() {};

// KEY DOWN
  /*document.onkeydown = function(event) {
    if (jeedomUtils.getOpenedModal()) 
      return true;

    //if ((event.ctrlKey || event.metaKey)) {
    if (event.ctrlKey) {
      event.preventDefault();
      
      switch (event.which) {
        case 83:  pluginsToolsDepedency.saveEqLogic();        // s
                  break;
        case 76:  pluginsToolsDepedency.showLog();            // l
                  break;
        case 69:  pluginsToolsDepedency.jsonEditEqLogic();    // e
                  break;
      }

      return true;
    }
  }*/
  $(document).ready(function() {
    var ctrlDown = false,
        ctrlKey = 17,
        cmdKey = 91,
        vKey = 86,
        cKey = 67;
        sKey = 83;
        lKey = 76;
        eKey = 69;

    $(document).keydown(function(e) {
        if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = true;
    }).keyup(function(e) {
        if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = false;
    });

    $(".no-copy-paste").keydown(function(e) {
      if (ctrlDown && (e.keyCode == vKey || e.keyCode == cKey)) return false;
    });

    // Document Ctrl + C/V 
    $(document).keydown(function(e) {
      //if (ctrlDown && (e.keyCode == sKey)) console.log("Document catch Ctrl+C");
      //if (ctrlDown && (e.keyCode == vKey)) console.log("Document catch Ctrl+V");
      if (ctrlDown) {
        switch (event.which) {
          case sKey:  pluginsToolsDepedency.saveEqLogic();        // s
                      return false;
                      break;
          case lKey:  pluginsToolsDepedency.showLog();            // l
                      return false;
                      break;
          case eKey:  pluginsToolsDepedency.jsonEditEqLogic();    // e
                      return false;
                      break;
        }
      }
    });
  });  
  
  
// EQUIPEMENT GENERIC FUNCTION
  // Affichage des log
  $('.eqLogicAction[data-action=showLog]').off('click').on('click', function() {
    pluginsToolsDepedency.showLog();
  })

  
  $('.eqLogicAction[data-action=editJsonEqLogic]').off('click').on('click', function() {
    pluginsToolsDepedency.jsonEditEqLogic();
  })  
  
  // Choisir un icon
  $("body").off('click','.bt_chooseIcon').on('click','.bt_chooseIcon',function () {
    var dataClosest = $(this).attr('data-closest');
    var dataL1key =   $(this).attr('data-filedL1key');
    var dataAtt =     $(this).attr('data-attr');
    var el = $(this).closest('.' + dataClosest).find('.' + dataAtt + '[data-l1key=' + dataL1key + ']');
    chooseIcon(function (_icon) {
      el.empty().append(_icon);
    });
  });  
  
  // Effacer un block
  $("body").off('click','.bt_remove').on('click','.bt_remove',function () {
    const dataMsg =       $(this).attr('data-msg');
    const dataLiClosest = $(this).attr('data-li-closest');
    const dataClosest =   $(this).attr('data-closest');
    const dataConfirm =   $(this).attr('data-confirm');
    const el =            $(this);
    const confirmRemove = true;
    
    if (isset(dataConfirm) && isset(dataMsg) && dataConfirm == 1) {
      bootbox.confirm(dataMsg, function (result) {
        if (result !== null) {
          if (result) {
            if (isset(dataLiClosest))
              el.closest(dataLiClosest).remove();
            el.closest('.' + dataClosest).remove();
          }
        }
      });
    }
    else {
      if (isset(dataLiClosest))
        $(dataLiClosest).remove();
      el.closest('.' + dataClosest).remove();
    }
  });
  
  // Ajouter un block
  $('body').off('click','.bt_add').on('click','.bt_add',  function () {
    const callFnct =            isset($(this).attr('data-callFnct'))?       $(this).attr('data-callFnct'):'';
    const dataClosest =         isset($(this).attr('data-closest'))?        $(this).attr('data-closest'):'';
    const dataType =            isset($(this).attr('data-type'))?           $(this).attr('data-type'):'';
    const dataName =            isset($(this).attr('data-name'))?           $(this).attr('data-name'):'';
    const dataExpAttName =      isset($(this).attr('data-expAttName'))?     $(this).attr('data-expAttName'):'expressionAttr';
    const dataPromptMessage =   $(this).attr('data-promptMessage');
    const formatFnct =          $(this).attr('data-formatFnct');
    const el =                  (isset(dataClosest) && dataClosest != '')?  $(this).closest('.' + dataClosest):'';
        
    if (isset(dataPromptMessage)) {
/* multiple input
bootbox.confirm("<form id='infos' action=''>\
    First name:<input type='text' name='first_name' /><br/>\
    Last name:<input type='text' name='last_name' />\
    </form>", function(result) {
        if(result)
            $('#infos').submit();
});
*/
      
      bootbox.prompt(dataPromptMessage, function (result) {
        if (result !== null && result != '') {
          //console.log("bt_add " + result);
          if (isset(formatFnct)) {
            result = pluginsToolsDepedency.callFunct(formatFnct, [result]);
          }
          //console.log("bt_add " + result);
          if (result != '') {
            elem = { 
                    name: result                                           
                   };
            pluginsToolsDepedency.callFunct(callFnct, [dataType, dataName, el, dataExpAttName], [elem]);
          }
        }
      });
    }
    else
      pluginsToolsDepedency.callFunct(callFnct, [dataType, dataName, el, dataExpAttName]);
  });

  $('body').off('click','.bt_addGenericTypeTrigger').on('click','.bt_addGenericTypeTrigger',  function () {
    const callFnct =            isset($(this).attr('data-callFnct'))?       $(this).attr('data-callFnct'):'';
    const dataClosest =         isset($(this).attr('data-closest'))?        $(this).attr('data-closest'):'';
    const dataType =            isset($(this).attr('data-type'))?           $(this).attr('data-type'):'';
    const dataSubType =         isset($(this).attr('data-subType'))?        $(this).attr('data-subType'):'';
    const dataName =            isset($(this).attr('data-name'))?           $(this).attr('data-name'):'';
    const dataExpAttName =      isset($(this).attr('data-expAttName'))?     $(this).attr('data-expAttName'):'expressionAttr';
    const el =                  (isset(dataClosest) && dataClosest != '')?  $(this).closest('.' + dataClosest):'';
    
    pluginsToolsDepedency.getSelectGenericType(
      {
        source: 'GenericType', 
        title:  '{{Sélectionner un type générique}}', 
        type:   dataSubType
      }, 
      function(result) {
        pluginsToolsDepedency.callFunct(callFnct, [dataType, dataName, el, dataExpAttName], [result]);
      }
    )  
  });  
  
  // Renommer un block
  $('body').off('click','.bt_rename').on('click','.bt_rename',  function () {
    var dataClosest =         $(this).attr('data-closest');
    var dataPromptMessage =   isset($(this).attr('data-promptMessage'))?  $(this).attr('data-promptMessage'):'{{Nouveau nom ?}}';
    var dataExpAttName =      isset($(this).attr('data-expAttName'))?     $(this).attr('data-expAttName'):'expressionAttr';
    var dataL1key =           isset($(this).attr('data-filedL1key'))?     $(this).attr('data-filedL1key'):'';
    var dataL2key =           isset($(this).attr('data-filedL2key'))?     $(this).attr('data-filedL2key'):'';
    var formatFnct =          $(this).attr('data-formatFnct');
    var el =                  $(this);
    var previousValue =       $(this).text();
    
    bootbox.confirm("<form class='bootbox-form'>\<br/>\
                        " + dataPromptMessage + "<br/>\
                        <input class='bootbox-input bootbox-input-text form-control' autocomplete='off' type='text' data-l1key='fieldValue' value='" + previousValue + "' />\
                     </form>", function(result) {
      if (result) {
        var fieldValue = $('.bootbox-confirm').find('[data-l1key=fieldValue]').val();

        //console.log("bt_rename " + fieldValue);
        if (isset(formatFnct)) {
          fieldValue = pluginsToolsDepedency.callFunct(formatFnct, [fieldValue]);
        }
        //console.log("bt_rename " + fieldValue);
        if (fieldValue != '') {
          el.text(fieldValue);
          
          /* A changer pour le prochain */
          el.closest('.panel.panel-default').find('span.name').text(fieldValue);
          if (el.hasClass(dataExpAttName)) {
            $('.' + dataExpAttName + '[data-l1key=' + dataL1key + ']').each(function () {
              if ($(this).text() == previousValue) {
                $(this).text(fieldValue);
              }
            });
          }
          
          if (dataL1key != '') {
            console.log("dataL1key set to " + dataL1key);
            if (dataL2key != '')
              el.closest('.' + dataClosest).find('.' + dataExpAttName + '[data-l1key=' + dataL1key + '][data-l2key=' + dataL2key + ']').each(function () {
                $(this).val(fieldValue);
              });
            else {
              el.closest('.' + dataClosest).find('.' + dataExpAttName + '[data-l1key=' + dataL1key + ']').each(function () {
                $(this).val(fieldValue);
              });
            }
          }  
        }
      }
    });
  });  

  // Ajouter un equipement
  $('body').off('click','.listEqlogic').on('click','.listEqlogic', function () {
    var dataClosest =   $(this).attr('data-closest');
    var dataAttr =      $(this).attr('data-attr');
    var dataL1key =     $(this).attr('data-filedL1key');
    var dataL2key =     isset($(this).attr('data-filedL2key'))? $(this).attr('data-filedL2key'):'';
    
    if (dataL2key != '')
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + '][data-l2key=' + dataL2key + ']');
    else
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + ']');
    
    jeedom.eqLogic.getSelectModal({}, function (result) {
      if (el.attr('data-concat') == 1) {
        el.atCaret('insert', result.human);
      } 
      else {
        el.value(result.human);
      }
    });
  });
  
  // Ajouter une commande info
  $('body').off('click','.listCmdInfo').on('click','.listCmdInfo', function () {
    var dataClosest =   $(this).attr('data-closest');
    var dataAttr =      $(this).attr('data-attr');
    var dataL1key =     $(this).attr('data-filedL1key');
    var dataL2key =     isset($(this).attr('data-filedL2key'))? $(this).attr('data-filedL2key'):'';
    
    if (dataL2key != '')
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + '][data-l2key=' + dataL2key + ']');
    else
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + ']');
    
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
      if (el.attr('data-concat') == 1) {
        el.atCaret('insert', result.human);
      } 
      else {
        el.value(result.human);
      }
    });
  });
  
  // Ajouter une commande action
  $('body').off('click','.listCmdAction').on('click','.listCmdAction', function () {
    var dataClosest =   $(this).attr('data-closest');
    var dataAttr =      $(this).attr('data-attr');
    var dataL1key =     $(this).attr('data-filedL1key');
    var dataL2key =     isset($(this).attr('data-filedL2key'))? $(this).attr('data-filedL2key'):'';
    
    if (dataL2key != '')
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + '][data-l2key=' + dataL2key + ']');
    else
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + ']');
    
    jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
      el.value(result.human);
      jeedom.cmd.displayActionOption(el.value(), '', function (html) {
        el.closest('.' + dataClosest).find('.actionOptions').html(html);
        taAutosize();
      });      
    });
  });

  // Ajouter un mot clee
  $("body").off('click','.listAction').on('click','.listAction',  function () {
    var dataClosest =   $(this).attr('data-closest');
    var dataAttr =      $(this).attr('data-attr');
    var dataL1key =     $(this).attr('data-filedL1key');
    var dataL2key =     isset($(this).attr('data-filedL2key'))? $(this).attr('data-filedL2key'):'';
    
    if (dataL2key != '')
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + '][data-l2key=' + dataL2key + ']');
    else
      var el = $(this).closest('.' + dataClosest).find('.' + dataAttr + '[data-l1key=' + dataL1key + ']');
    
    jeedom.getSelectActionModal({}, function (result) {
      el.value(result.human);
      jeedom.cmd.displayActionOption(el.value(), '', function (html) {
        el.closest('.' + dataClosest).find('.actionOptions').html(html);
        taAutosize();
      });
    });
  });  

pluginsToolsDepedency.showLog = function() {
  if ($('.eqLogicAction[data-action=showLog]').is(':visible'))
    $('#md_modal').dialog({title: "{{Log d'exécution}}"}).load('index.php?v=d&p=dashboard&modal=pluginsToolsDepedency.showLog&eqType=' + eqType + '&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
}

pluginsToolsDepedency.jsonEditEqLogic = function() {
  if ($('.eqLogicAction[data-action=editJsonEqLogic]').is(':visible'))
    $('#md_modal').dialog({title: "{{Edition texte}}"}).load('index.php?v=d&p=dashboard&modal=pluginsToolsDepedency.jsonEdit.EqLogic&eqType=' + eqType + '&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
}

pluginsToolsDepedency.saveEqLogic = function() {
  if ($('.eqLogicAction[data-action=save]').is(':visible'))
    $(".eqLogicAction[data-action=save]").click()
}

pluginsToolsDepedency.callFunct = function(_fctName, _defaultArguments, _arrayCallArguments, _checkArrayValueExist = null) {
  var returnValue = '';
  var fn = window[_fctName];
  if (typeof fn !== 'function')
    return;
  
  console.log("_arrayCallArguments " + _fctName);
  if (isset(_arrayCallArguments) || isset(_checkArrayValueExist)) {
    console.log("_arrayCallArguments t2");
    if (isset(_arrayCallArguments)) {
      console.log("_arrayCallArguments t3");
      for (var i in _arrayCallArguments) {
        console.log("_arrayCallArguments t4");
        if (!isset(_checkArrayValueExist)
            || (isset(_arrayCallArguments[i][_checkArrayValueExist]) && _arrayCallArguments[i][_checkArrayValueExist] != '')
           ) {
          console.log("_arrayCallArguments t5");
          returnValue = fn.apply(window, _defaultArguments.concat([_arrayCallArguments[i]]));
        }
        console.log("_arrayCallArguments t6");
      }
    }
  }
  else {
    console.log("_arrayCallArguments apply defaultArguments");
    returnValue = fn.apply(window, _defaultArguments);
  }
  
  return returnValue;
}

pluginsToolsDepedency.setDivBlock = function(_type, _el, _expressionAttr, _elem, div) {
  if (typeof _el === 'object' && _el !== null) {
    console.log("setDivBlock: first " + _type + "," + _expressionAttr);
    _el.find('.div_' + _type).append(div);
    _el.find('.' + _type).last().setValues(_elem, '.' + _expressionAttr);    
  } 
  else {
    console.log("setDivBlock: second " + _type + "," + _expressionAttr);
    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type).last().setValues(_elem, '.' + _expressionAttr);
  } 
}  

// SELECTIONNER UN TYPE GENERIC
pluginsToolsDepedency.getSelectGenericType = function(_options, _callback) {
  var modalNameDialogBox = "#mod_insert" + _options.source;
  
  if ($("#mod_insert" + _options.source).length != 0) {
      $(modalNameDialogBox).remove();
  }
  
  if ($(modalNameDialogBox).length == 0) {
    $('body').append('<div id="mod_insert' + _options.source + '" title="' + _options.title + '" ></div>');
    $(modalNameDialogBox).dialog({
      closeText: '',
      autoOpen: false,
      modal: true,
      height: 310,
      width: 800
    });
    jQuery.ajaxSetup({
      async: false
    });
    var url = 'index.php?v=d&plugin=advancedScenario&modal=' + _options.source + '.insert&type=' + _options.type + "&funct=" + _options.funct + '&object=' + 1
    $(modalNameDialogBox).load(url);
    jQuery.ajaxSetup({
        async: true
    });
    mod_function.setOptions(_options);
  }
  
  $(modalNameDialogBox).dialog('option', 'buttons', {
    "{{Annuler}}": function() { 
      $(this).dialog("close"); 
    },
    "{{Valider}}": function() {
        var retour = {};
        retour.action =           {};
        retour.human =            mod_function.getValue();
        retour.genericType =      mod_function.getGenericTypeId();
        retour.genericHumanName = mod_function.getGenericHumanName();
        retour.objectId =         mod_function.getObjectId();
        retour.objectHumanName =  mod_function.getObjectHumanName();
        if ($.trim(retour) != '' && 'function' == typeof(_callback)) {
          _callback(retour);
        }
        $(this).dialog('close');
    }
  });
  $(modalNameDialogBox).dialog('open');
}

// FUNCTION POUR L'AFFICHAGE DES LOGS
  if (isset(jeedom.log.colorScReplacement)) {
    //console.log("colorScReplacement: " + JSON.stringify(jeedom.log.colorScReplacement, null, 4));
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
      //console.log('[jeedom.log.autoupdate] No logfile')
      return
    }
    if (!isset(_params.display)) {
      //console.log('[jeedom.log.autoupdate] No display')
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
          log = jeedom.log.remouveHtmlTag(log)
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
    '<label class="warning" style="margin-bottom: 0px;">': '',
    '<label class="info"    style="margin-bottom: 0px;">': '',
    '<label class="error"   style="margin-bottom: 0px;">': '',
    '<label class="success" style="margin-bottom: 0px;">': '',
    '<label class="debug"   style="margin-bottom: 0px;">': '',
    '</label>': ''
  }

  jeedom.log.remouveHtmlTag = function(_str) {
    for (var re in jeedom.log.htmlTag) {
      _str = _str.split(re).join(jeedom.log.htmlTag[re])
    }
    return _str
  }