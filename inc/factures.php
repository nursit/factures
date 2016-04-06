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
 * Generer une facture unique pour une transaction
 * en gerant le risque de double appel concurent pour la meme transaction
 * (en principe deja gere en amont de l'appel, mais pas de facon certaine a 100%)
 *
 * @param int $id_transaction
 * @param null|array $options_notif
 * @return array|bool
 */
function factures_creer_facture($id_transaction, $options_notif=null){
	$id_facture=0;
	$no_comptable='';
	$url = '';

	// transaction deja en cours de facturation ?
	// attendons au max 5s pour voir si la facturation se fait entre temps
	// permet de recuperer le numero de facture
	$max_wait = 5;
	while ($row = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))
	  AND $row['id_facture']==-1
	  AND $max_wait--){
		sleep(1);
	}

	// transaction introuvable ou toujours verrouillee ?
	if (!$row OR $row['id_facture']==-1)
		return false;

	// deja facture
	if ($row['id_facture']){
		$url= generer_url_public('facture',"id_facture=$id_facture&hash=".md5($row['details']),false,false);
		return array($row['id_facture'],$row['no_comptable'],$url);
	}

	// verouiller la facturation de cette transaction
	sql_updateq("spip_transactions",array('id_facture'=>-1),"id_transaction=".intval($id_transaction));


	// creer la facture
	$details = recuperer_fond('modeles/transaction_details',array('id_transaction'=>$id_transaction));
	$client = recuperer_fond('modeles/client_adresse_facture',array('id_auteur'=>$row['id_auteur'],'id_transaction'=>$id_transaction));

	$numeroter_facture = charger_fonction('numeroter_facture','inc');

	$set = array(
		'id_auteur' => $row['id_auteur'],
		'montant_ht' => $row['montant_ht'],
		'montant' => $row['montant'],
		'montant_regle' => $row['montant_regle'],
		'date' => date('Y-m-d H:i:s'),
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
		$no_comptable = $numeroter_facture($id_facture,$set['date']);

		$set['no_comptable'] = $no_comptable;
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

		$url= generer_url_public('facture',"id_facture=$id_facture&hash=".md5($set['details']),false,false);

		if ($options_notif
			AND is_array($options_notif)
		  AND $notifications = charger_fonction('notifications', 'inc')){
			$options_notif['url_facture'] = $url;
			$notifications("genererfacture", $id_facture, $options_notif);
		}
	}

	return array($id_facture,$no_comptable,$url);
}
