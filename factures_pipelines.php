<?php
/*
 * Factures
 * module de facturation
 *
 * Auteurs :
 * Cedric Morin, Nursit.com
 * (c) 2012 - Distribue sous licence GNU/GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


function factures_bank_facturer_reglement($flux){
	include_spip('inc/factures');

	// emettre la facture
	list($id_facture,$no_comptable) = factures_creer_facture($flux['args']['id_transaction']);

	// generer le message de retour
	$href= "";
	if ($row = sql_fetsel("details,id_auteur,no_comptable","spip_factures","id_facture=".intval($id_facture))){
		$href= generer_url_public('facture',"id_facture=$id_facture&hash=".md5($row['details']),false,false);
	}

	if ($href){
		$flux['data'] .= "<br />"._T('factures:mail_imprimer_facture',array('url'=>$href,'numero'=>$row['no_comptable']));

		if ($notifications = charger_fonction('notifications', 'inc')) {
			$options = $flux['args'];
			$options['url_facture'] = $href;
			$notifications("genererfacture", $id_facture, $options);
		}
	}


	return $flux;
}