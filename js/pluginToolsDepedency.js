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
        $('#md_modal').dialog({title: "{{Log d'exécution}}"}).load('index.php?v=d&plugin=genericTypeManager&modal=log.execution&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
        return
      }      
    }
  }
  
// EQUIPEMENT GENERIC FUNCTION
  // Affichage des log
  $('.eqLogicAction[data-action=showLog]').off('click').on('click', function() {
    $('#md_modal').dialog({
      title: "{{Log d'exécution}}"
    }).load('index.php?v=d&plugin=envisaLink&modal=log.execution&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open')
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
          result = callFunct(formatFnct, result, []);
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