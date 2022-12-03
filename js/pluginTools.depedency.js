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

var advancedScenario =  function() {};

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
        $('#md_modal').dialog({title: "{{Log d'exécution}}"}).load('index.php?v=d&plugin=genericTypeManager&modal=log.execution&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
        return
      }      
    }
  }
  
// EQUIPEMENT GENERIC FUNCTION
  // AFFICHAGE DES LOG
  $('.eqLogicAction[data-action=showLog]').off('click').on('click', function() {
    $('#md_modal').dialog({
      title: "{{Log d'exécution}}"
    }).load('index.php?v=d&plugin=envisaLink&modal=log.execution&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
  })

// AJOUTER UN BLOCK DE CONFIGURATION D'UN EQUIPEMENT
function addBlock(_fctName, _arrayValue, _arguments) {
  var fn = window[_fctName];
  if (typeof fn !== 'function')
    return;

  for (var i in _arrayValue)
    fn.apply(window, _arguments.concat([_arrayValue[i]]));
}

  // Effacer
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
  
  $('body').off('click','.bt_rename').on('click','.bt_rename',  function () {
    var dataClosest =   $(this).attr('data-closest');
    var el =            $(this);
    bootbox.prompt("{{Nouveau nom ?}}", function (result) {
      if (result !== null && result != '') {
        var previousName = el.text();
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
    });
  });  
  
advancedScenario.getSelectGenericType = function(_options, _callback) {
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
    var url = 'index.php?v=d&plugin=advancedScenario&modal=' + _options.source + '.insert&type=' + _options.type + "&funct=" + _options.funct
    if (_options.type == 'info') url += '&object=' + 1
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

advancedScenario.getTagElement = function(_options, _callback) {
  /*for (var i in editor.export().drawflow.Home.data) {
    var elem =      flowData[i].data;

    if (elem.type == 'tag')
      tagElement.push();
  }*/
}

advancedScenario.getSelect = function(_options, _callback) {
  var modalNameDialogBox = "#mod_insert" + _options.source;
  
  if ($(modalNameDialogBox).length != 0) {
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
    $(modalNameDialogBox).load('index.php?v=d&plugin=advancedScenario&modal=' + _options.source + '.insert&type=' + _options.type);
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
      retour.action = {};
      retour.human =  mod_function.getValue();
      retour.id =     mod_function.getId();
      if ($.trim(retour) != '' && 'function' == typeof(_callback)) {
        _callback(retour);
      }
      $(this).dialog('close');
    }
  });
  $(modalNameDialogBox).dialog('open');
}

advancedScenario.getEditExpression = function(_options, _callback) {
  if (!isset(_options)) {
    _options = {};
  }
  
  if ($("#mod_editExpression").length == 0) {
    $('body').append('<div id="mod_editExpression" title="{{Ajout d\'une expression}}"></div>');
    
    $("#mod_editExpression").dialog({closeText: '', autoOpen: false, modal: true, height: 600, width: 1000});
    jQuery.ajaxSetup({
      async: false
    });
    $('#mod_editExpression').load('index.php?v=d&plugin=advancedScenario&modal=editExpression&nodeType=' + _options.nodeType + '&type=' + _options.type + '&subtype=' + _options.subtype + '&expression=' + _options.nodeExpression + '&multipleExp=' + _options.multipleExp);
    jQuery.ajaxSetup({
      async: true
    });
  }

  $("#mod_editExpression").dialog('option', 'buttons', {
    "{{Annuler}}": function() {
      $(this).dialog("close");
    },
    "{{Valider}}": function() {
      var retour = {};
      retour.action = {};
      retour.human = mod_editExpression.getValue();
      if ($.trim(retour) != '' && 'function' == typeof(_callback)) {
        _callback(retour);
      }
      $(this).dialog('close');
    }
  });
  $('#mod_editExpression').dialog('open');
}

advancedScenario.displayCmdActionOption = function(_expression, _options, _callback) {
  var html = '';
  $.ajax({
    type: "POST",
    url: "plugins/advancedScenario/core/ajax/advancedScenario.ajax.php",
    data: {
      action: 'actionToHtml',
      version: 'scenario',
      expression: _expression,
      option: json_encode(_options)
    },
    dataType: 'json',
    async: ('function' == typeof(_callback)),
    global: false,
    error: function(request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function(data) {
      if (data.state != 'ok') {
        $.fn.showAlert({
          message: data.result,
          level: 'danger'
        });
        return;
      }
      if (data.result.html != '') {
        html += data.result.html;
      }
      if ('function' == typeof(_callback)) {
        _callback(html);
        return;
      }
    }
  });
  return html;
};
  