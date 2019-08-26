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

	// il y a deja une facture ?
	// on met a jour les infos de paiement si besoin, et on retourne
	if ($id_facture = $row['id_facture']
		and $facture = sql_fetsel('*','spip_factures', 'id_facture='.intval($id_facture))){

		// verifier que le facture est bien reglee comme la transaction
		// cas possible d'une facture emise avant paiement de la transaction, et indiquee comme non reglee
		$set = array();
		if (round($facture['montant_regle'],4) <= round($row['montant_regle'], 4)) {
			$set['montant_regle'] = $row['montant_regle'];
		}
		if ($facture['date_paiement'] !== $row['date_paiement']) {
			$set['date_paiement'] = $row['date_paiement'];
		}
		if ($set) {
			sql_updateq('spip_factures', $set, 'id_facture='.intval($id_facture));
		}

		$url= generer_url_public('facture',"id_facture=$id_facture&hash=".md5($facture['details']),false,false);
		return array($id_facture,$facture['no_comptable'],$url);
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
		// loger sur disque pour avoir une trace en cas de fail SQL sur le updateq suivant (permet une reparation manuelle)
		spip_log("Transaction $id_transaction => Facture $id_facture",'factures'._LOG_INFO_IMPORTANTE);
		// poser le pointeur immediatement, pour ne pas risquer d'avoir une facture orpheline
		sql_updateq("spip_transactions",array("id_facture"=>$id_facture),"id_transaction=".intval($id_transaction));

		// puis generer le numero comptable et mettre a jour la facture
		$no_comptable = $numeroter_facture($id_facture,$set['date']);
		$set['no_comptable'] = $no_comptable;
		sql_updateq("spip_factures",array("no_comptable"=>$no_comptable),"id_facture=".intval($id_facture));

		pipeline('post_insertion',
			array(
				'args' => array(
					'table' => 'spip_factures',
					'id_objet' => $id_facture
				),
				'data' => $set
			)
		);

		// on relit le no_comptable en base au cas ou le pipeline aurait surcharge
		$no_comptable = sql_getfetsel('no_comptable', 'spip_factures', 'id_facture='.intval($id_facture));

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


/**
 * Generer une facture proforma pour une transaction avec commande, en attente de paiement
 *
 * @param int $id_transaction
 * @param null|array $options_notif
 * @return array|bool
 */
function factures_creer_facture_proforma($id_transaction, $options_notif=null){
	$id_facture_proforma=0;
	$no_comptable='';
	$url = '';

	$row = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction));

	// transaction introuvable ou toujours verrouillee ?
	if (!$row) {
		return false;
	}

	// creer les elements de la facture proforma, comme pour une vrai facture
	$details = recuperer_fond('modeles/transaction_details',array('id_transaction'=>$id_transaction));
	$client = recuperer_fond('modeles/client_adresse_facture',array('id_auteur'=>$row['id_auteur'],'id_transaction'=>$id_transaction));


	// deja une facture proforma ?
	if ($proforma = sql_fetsel('*','spip_factures_proforma','id_transaction='.intval($id_transaction),'','id_facture_proforma DESC','0,1')
	  and md5($details) == md5($proforma['details'])) {
		$id_facture_proforma = $proforma['id_facture_proforma'];
		$url= generer_url_public('facture_proforma',"id_facture_proforma=$id_facture_proforma&hash=".md5($proforma['details']), false, false);
		return array($id_facture_proforma, $proforma['no_comptable'], $url);
	}

	$numeroter_facture_proforma = charger_fonction('numeroter_facture_proforma','inc');

	$set = array(
		'id_transaction' => $row['id_transaction'],
		'montant_ht' => $row['montant_ht'],
		'montant' => $row['montant'],
		'date' => date('Y-m-d H:i:s'),
		'client' => $client,
		'details' => $details,
		'parrain' => $row['parrain'],
		'tracking_id' => $row['tracking_id'],
	);

	// Envoyer aux plugins
	$set = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_factures_proforma',
			),
			'data' => $set
		)
	);

	$id_facture_proforma = sql_insertq('spip_factures_proforma',$set);

	if ($id_facture_proforma){
		$no_comptable = $numeroter_facture_proforma($id_facture_proforma,$set['date']);

		$set['no_comptable'] = $no_comptable;
		sql_updateq("spip_factures_proforma", array("no_comptable"=>$no_comptable), "id_facture_proforma=".intval($id_facture_proforma));

		pipeline('post_insertion',
			array(
				'args' => array(
					'table' => 'spip_factures_proforma',
					'id_objet' => $id_facture_proforma
				),
				'data' => $set
			)
		);

		// on relit le no_comptable en base au cas ou le pipeline aurait surcharge
		$no_comptable = sql_getfetsel('no_comptable', 'spip_factures_proforma', 'id_facture_proforma='.intval($id_facture_proforma));

		$url= generer_url_public('facture_proforma',"id_facture_proforma=$id_facture_proforma&hash=".md5($set['details']), false, false);

		if ($options_notif
			AND is_array($options_notif)
		  AND $notifications = charger_fonction('notifications', 'inc')){
			$options_notif['url_facture_proforma'] = $url;
			$notifications("genererfactureproforma", $id_facture_proforma, $options_notif);

		}

	}

	return array($id_facture_proforma, $no_comptable, $url);
}