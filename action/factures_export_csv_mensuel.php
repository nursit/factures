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

function action_factures_export_csv_mensuel_dist(){

	$securiser_action = charger_fonction("securiser_action","inc");
	$date = $securiser_action();

	$t = strtotime($date);
	$date_debut = date('Y-m-01 00:00:00',$t);
	$t = strtotime('+1 month',strtotime($date_debut));
	$t = strtotime('+5 day',$t);
	$date_fin= date('Y-m-01 00:00:00',$t);

	$entetes = array(
		'ID','Date','No','Client','HT','TTC','Paye','Date paiement','Commande'
	);
	$factures = sql_allfetsel("id_facture,date,no_comptable,id_auteur,montant_ht,montant,montant_regle,date_paiement","spip_factures","date>=".sql_quote($date_debut)." AND date<".sql_quote($date_fin),'','id_facture');
	foreach($factures as $k=>$facture){
		$factures[$k]['date'] = date('d/m/Y',strtotime($facture['date']));
		$factures[$k]['date_paiement'] = date('d/m/Y',strtotime($facture['date_paiement']));
		$nom = sql_getfetsel('nom','spip_auteurs','id_auteur='.intval($facture['id_auteur']));
		$factures[$k]['id_auteur'] = trim($nom . " #" . $facture['id_auteur']);
		$factures[$k]['commande'] = '';
		if ($trans = sql_fetsel("*","spip_transactions","id_facture=".intval($facture['id_facture']))
		  AND $id_commande = intval($trans['id_commande'])){
			$reference = sql_getfetsel('reference','spip_commandes','id_commande='.intval($id_commande));
			$factures[$k]['commande'] = trim($reference . " #" . $trans['id_commande']);
		}
	}
	$factures = array_map('array_values',$factures);

	$nom = $GLOBALS['meta']['nom_site'] . ' Factures '.date('Y-m');
	$exporter_csv = charger_fonction('exporter_csv','inc');
	$exporter_csv($nom,$factures,',',$entetes,true);

}
