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

function action_renvoyer_facture_dist(){
	$securiser_action = charger_fonction("securiser_action","inc");
	$id_facture = $securiser_action();

	if ($row = sql_fetsel("details,id_auteur,no_comptable","spip_factures","id_facture=".intval($id_facture))
	  AND $notifications = charger_fonction('notifications', 'inc')){
		spip_log("Renvoi de la facture #$id_facture a auteur #".$row['id_auteur']." par #".$GLOBALS['visiteur_session']['id_auteur'],"facture"._LOG_INFO_IMPORTANTE);
		$options = array();
		$options['url_facture'] = generer_url_public('facture',"id_facture=$id_facture&hash=".md5($row['details']),false,false);
		$notifications("genererfacture", $id_facture, $options);
	}
}
