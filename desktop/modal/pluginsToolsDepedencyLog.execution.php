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
sendVarToJs('logId',    init('id'));
sendVarToJs('eqType',   init('eqType'));
?>
<style>
  .label-info,
  .label-success,
  .label-warning,
  .label-danger,
  .label-info {
    width: 50px;
  }
</style>
<div style="display: none;width : 100%" id="div_alertLog"></div>
<?php echo '<span style="font-weight: bold;">' . $pluggin->getHumanName(true, false, true) . '</span>'; ?>
<div class="input-group pull-right">
  <span class="input-group-btn" style="display: inline;">
    <span class="label-sm">{{Log brut}}</span>
    <input type="checkbox" id="brutlogcheck" autoswitch="0"/>
    <i id="brutlogicon" class="fas fa-exclamation-circle icon_orange"></i>
    <input class="input-sm roundedLeft" id="inLogSearch" style="width : 200px;margin-left:5px;" placeholder="{{Rechercher}}" />
    <a id="bt_resetLogSearch" class="btn btn-sm"><i class="fas fa-times"></i>
    </a><a class="btn btn-warning btn-sm" data-state="1" id="btLogStopStart"><i class="fas fa-pause"></i> {{Pause}}
    </a><a class="btn btn-success btn-sm" id="btLogDownload"><i class="fas fa-cloud-download-alt"></i> {{Télécharger}}
    </a><a class="btn btn-warning btn-sm roundedRight" id="btLogEmpty"><i class="fas fa-trash"></i> {{Vider}}</a>
  </span>
</div>
<br/><br/>
<pre id='preLog' class='pluggin-LogContents'></pre>

<?php 
include_file('core', 'pluginToolsDepedency', 'js'); 
?>

<script>
var $rawLogCheck = $('#brutlogcheck')
$rawLogCheck.on('click').on('click', function () {
  $rawLogCheck.attr('autoswitch', 0)

  var scroll = $('#preLog').scrollTop()
  jeedom.log.autoupdate({
    log:      'pluginLog/plugin'+logId+'.log',
    display:  $('#preLog'),
    search:   $('#inLogSearch'),
    control:  $('#btLogStopStart'),
    once: 1
  })
  $('#preLog').scrollTop(scroll)
})


jeedom.log.autoupdate({
  log:      'pluginLog/plugin'+logId+'.log',
  display:  $('#preLog'),
  search:   $('#inLogSearch'),
  control:  $('#btLogStopStart')
})

$('#bt_resetLogSearch').on('click', function () {
  $('#inLogSearch').val('').keyup()
})

$('#btLogEmpty').on('click', function() {
  $.ajax({
    type: 'POST',
    url: 'core/ajax/pluginsToolsDepedency.ajax.php',
    data: {
      action: 'emptyLog',
      eqType: eqType,
      id:     logId
    },
    dataType: 'json',
    error: function (request, status, error) {
      $('#div_alertLog').showAlert({message: error.message, level: 'danger'})
    },
    success: function (data) {
      $('#div_alertLog').showAlert({message: '{{Log vidé avec succès}}', level: 'success'})
      $.clearDivContent('preLog')      
    }
  });  
})

$('#btLogDownload').click(function() {
  window.open('core/php/downloadFile.php?pathfile=log/pluginLog/plugin<?php echo init('id') ?>.log', "_blank", null)
})
</script>
