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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect()) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	
	if (init('action') == 'eqLogicCopy') {
    if (($eqType = init('eqType')) == '')
      throw new Exception(__('Pluggin inconnu. Vérifiez l\'ID', __FILE__));
    
		if (!is_object($pluggin = $eqType::byId(init('id'))))
			throw new Exception(__($eqType.' inconnu. Vérifiez l\'ID', __FILE__));

		if (($eqName = init('name')) == '')
			throw new Exception(__('Le nom de la copie de l\'équipement ne peut être vide', __FILE__));

		if (($objectId  = init('objectId')) == '')
			throw new Exception(__('L\'object de la copie de l\'équipement ne peut être vide', __FILE__));
    
		ajax::success(utils::o2a($plugginName -> eqLogicCopy($eqName, $objectId)));
	}
  
  if (init('action') == 'emptyLog') {
    file_put_contents(dirname(__FILE__) . '/../../log/pluginLog/plugin'.init('id').'.log', "");
  }

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} 
catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
