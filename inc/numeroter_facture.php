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



function inc_numeroter_facture_dist($id_facture,$date_paiement){
	$time = strtotime($date_paiement);
	return "F.".date("Ymd",$time)."-$id_facture";
}