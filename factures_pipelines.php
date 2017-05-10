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

/**
 * creer la facture proforma pour le reglement en attente
 * @param $flux
 * @return mixed
 */
function factures_trig_bank_reglement_en_attente($flux) {
	if (isset($flux['args']['id_transaction'])
	  and $id_transaction = $flux['args']['id_transaction']) {

		// on cree la facture proforma et c'est tout (on ne s'occupe pas de sa mise a disposition)
		include_spip('inc/factures');
		$res = factures_creer_facture_proforma($id_transaction);

	}
	return $flux;
}

/**
 * Afficher un lien vers la facture PROFORMA pour les reglements en attente
 * @param array $flux
 * @return array
 */
function factures_bank_afficher_reglement_en_attente($flux) {

	if (isset($flux['args']['id_transaction'])
	  and $id_transaction = $flux['args']['id_transaction']) {

		// on retrouve la proforma (en en regenerant une nouvelle si besoin)
		include_spip('inc/factures');
		if ($res = factures_creer_facture_proforma($id_transaction)) {
			list($id_facture_proforma, $no_comptable, $url) = $res;
			$flux['data'] .= "<p><a href=\"$url\">"._T('factures:info_telecharger_facture_proforma',array('no_comptable' => $no_comptable))."</a></p>";
		}

	}
	return $flux;
}

/**
 * Liste des champs de recherche factures et proforma
 *
 * @param array $flux
 * @return array
 */
function factures_rechercher_liste_des_champs($flux){

	$flux['facture'] = array(
		'id_facture' => 1,
		'no_comptable' => 1,
		'parrain' => 1,
		'tracking_id' => 1,
	);
	$flux['facture_proforma'] = array(
		'id_facture_proforma' => 1,
		'no_comptable' => 1,
		'parrain' => 1,
		'tracking_id' => 1,
	);
	return $flux;
}