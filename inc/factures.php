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


function factures_creer_facture($id_transaction){
	$id_facture=0;
	$no_comptable='';

	// regarder si la facture a ete emise, et sinon la creer
	if ($row = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))
	  AND !($id_facture = $row['id_facture'])){

		$details = recuperer_fond('modeles/transaction_details',array('id_transaction'=>$id_transaction));
		$client = recuperer_fond('modeles/client_adresse_facture',array('id_auteur'=>$row['id_auteur'],'id_transaction'=>$id_transaction));

		$numeroter_facture = charger_fonction('numeroter_facture','inc');

		$set =
			array(
				'id_auteur' => $row['id_auteur'],
				'montant_ht' => $row['montant_ht'],
				'montant' => $row['montant'],
				'montant_regle' => $row['montant_regle'],
				'date_paiement' => $row['date_paiement'],
				'client' => $client,
				'details' => $details,
				'parrain' => $row['parrain'],
				'tracking_id' => $row['tracking_id'],
			);

		// Envoyer aux plugins
		$set = pipeline('pre_insertion',
			array(
				'args' => array(
					'table' => 'spip_factures',
				),
				'data' => $set
			)
		);

		$id_facture = sql_insertq('spip_factures',$set);

		if ($id_facture){
			$no_comptable = $numeroter_facture($id_facture,$row['date_paiement']);

			sql_updateq("spip_factures",array("no_comptable"=>$no_comptable),"id_facture=".intval($id_facture));
			sql_updateq("spip_transactions",array("id_facture"=>$id_facture),"id_transaction=".intval($id_transaction));

			pipeline('post_insertion',
				array(
					'args' => array(
						'table' => 'spip_factures',
						'id_objet' => $id_facture
					),
					'data' => $set
				)
			);

		}
	}

	return array($id_facture,$no_comptable);
}
