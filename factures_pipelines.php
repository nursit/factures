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
 * @param array $flux
 * @return array
 */
function factures_bank_facturer_reglement($flux){

	include_spip("inc/transaction");
	include_spip("inc/autoriser");

	if (autoriser("facturer","transaction",$flux['args']['id_transaction'])){

		include_spip('inc/factures');

		// emettre la facture
		$res = factures_creer_facture($flux['args']['id_transaction'], $flux['args']);
		if ($res
		  AND list($id_facture,$no_comptable,$url) = $res){

			// generer le message de retour
			if ($url){
				$flux['data'] .= "<br />"._T('factures:mail_imprimer_facture',array('url'=>$url,'numero'=>$no_comptable));
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