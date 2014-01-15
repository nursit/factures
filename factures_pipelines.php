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

/**
 * Chargement du fichier dans le pipeline autoriser
 */
function factures_autoriser(){}

/**
 * Par defaut on peut toujours facturer
 * @return bool
 */
function autoriser_facturer_dist(){ return true;}


/**
 * Facturer un reglement
 * @param $flux
 * @return mixed
 */
function factures_bank_facturer_reglement($flux){

	include_spip("inc/transaction");

	if (autoriser("facturer","transaction",$flux['args']['id_transaction'])){

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

	}

	return $flux;
}

function factures_rechercher_liste_des_champs($flux){

	$flux['facture'] = array(
		'id_facture' => 1,
		'no_comptable' => 1,
		'parrain' => 1,
		'tracking_id' => 1,
	);
	return $flux;
}