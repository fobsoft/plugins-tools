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

if (!isConnect()) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
$eqType = init('eqType');
$pluggin = $eqType::byId(init('id'));
if (!is_object($pluggin)) {
  throw new Exception(__('Aucun équipement ne correspondant à :', __FILE__) . ' ' . init('id'));
}

sendVarToJs('jeephp2js.md_eqLogicJsonEdit_scEqType',  init('eqType'));
sendVarToJs('jeephp2js.md_eqLogicJsonEdit_scId',      init('id'));

include_file('3rdparty', 'codemirror/addon/selection/active-line', 'js');
include_file('3rdparty', 'codemirror/addon/search/search', 'js');
include_file('3rdparty', 'codemirror/addon/search/searchcursor', 'js');
include_file('3rdparty', 'codemirror/addon/dialog/dialog', 'js');
include_file('3rdparty', 'codemirror/addon/dialog/dialog', 'css');

include_file('3rdparty', 'codemirror/addon/fold/brace-fold', 'js');
include_file('3rdparty', 'codemirror/addon/fold/comment-fold', 'js');
include_file('3rdparty', 'codemirror/addon/fold/foldcode', 'js');
include_file('3rdparty', 'codemirror/addon/fold/indent-fold', 'js');
include_file('3rdparty', 'codemirror/addon/fold/foldgutter', 'js');
include_file('3rdparty', 'codemirror/addon/fold/foldgutter', 'css');
?>

<div id="div_alertJsonEdit" data-modalType="md_JsonEdit"></div>
<!--<a class="btn btn-success btn-sm pull-right" id="bt_saveConfig"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>-->
<br/><br/>
<textarea id="ta_JsonEdit">
  <?php
  echo json_encode($pluggin -> export(), JSON_PRETTY_PRINT);
  ?>
</textarea>

<script type="text/javascript">
  fileEditor = CodeMirror.fromTextArea(document.getElementById("ta_JsonEdit"), {
    lineNumbers: true,
    mode: 'application/json',
    styleActiveLine: true,
    lineNumbers: true,
    lineWrapping: true,
    matchBrackets: true,
    autoRefresh: true,
    foldGutter: true,
    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
  })
  fileEditor.setOption("extraKeys", {
    "Ctrl-Y": cm => CodeMirror.commands.foldAll(cm),
    "Ctrl-I": cm => CodeMirror.commands.unfoldAll(cm)
  })
  fileEditor.getWrapperElement().style.height = ($('#ta_JsonEdit').closest('.ui-dialog-content').height() - 90) + 'px'
  fileEditor.refresh()

  $('#bt_saveConfig').on('click', function() {
    $.hideAlert()
    if (fileEditor == undefined) {
      $('#div_alertJsonEdit').showAlert({message: '{{Erreur editeur non défini}}', level: 'danger'})
      return
    }
    try {
      JSON.parse(fileEditor.getValue())
    } 
    catch(e) {
      $('#div_alertJsonEdit').showAlert({message: '{{Champs json invalide}}', level: 'danger'})
      return
    }
    
    var _params = {
      type:       jeephp2js.md_eqLogicJsonEdit_scEqType,
      id :        jeephp2js.md_eqLogicJsonEdit_scId,
      eqLogics :  json_decode(fileEditor.getValue())
    };
    console.log("_params: " + JSON.stringify(_params, null, 4));
    jeedom.eqLogic.save({
      type:       jeephp2js.md_eqLogicJsonEdit_scEqType,
      id :        jeephp2js.md_eqLogicJsonEdit_scId,
      eqLogics :  fileEditor.getValue(),
      error: function(error) {
        $('#div_alertJsonEdit').showAlert({message: error.message + 'test', level: 'danger'})
      },
      success: function(data) {
        $('#div_alertJsonEdit').showAlert({message: '{{Sauvegarde réussie}}', level: 'success'})
      }
    })
  })

</script>