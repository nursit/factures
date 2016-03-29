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


function factures_declarer_tables_principales($tables_principales){


	$spip_factures = array(
		"id_facture" 	=> "bigint(21) NOT NULL",
		"id_auteur" 	=> "bigint(21) NOT NULL",
		"no_comptable" 	=> "varchar(50) NOT NULL DEFAULT ''",
		"montant_ht" 	=> "varchar(25) NOT NULL DEFAULT ''",
		"montant" 	=> "varchar(25) NOT NULL DEFAULT ''",
		"montant_regle" 	=> "varchar(25) NOT NULL DEFAULT ''",
		"date" => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_paiement" => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"client" => "TEXT NOT NULL DEFAULT ''",
		"details" => "TEXT NOT NULL DEFAULT ''",
		"commentaire" => "TEXT NOT NULL DEFAULT ''",
		"parrain" => "varchar(35) NOT NULL DEFAULT ''",
		"tracking_id" => "bigint(21) NOT NULL",
		"maj" 		=> "TIMESTAMP");

	$spip_factures_key = array(
		"PRIMARY KEY" 	=> "id_facture",
		"KEY id_auteur" => "id_auteur");

	$tables_principales['spip_factures'] = array(
		'field' => &$spip_factures,
		'key' => &$spip_factures_key);


	return $tables_principales;
}


/**
 * Installation/maj de la table factures
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function factures_upgrade($nom_meta_base_version,$version_cible){
	$maj = array();
	// creation initiale
	$maj['create'] = array(
		array('maj_tables',array('spip_factures')),
	);

	$maj['0.2.0'] = array(
		array('maj_tables',array('spip_factures')),
		array('sql_update','spip_factures',array('date'=>'date_paiement')),
	);

	// lancer la maj
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Desinstallation/suppression des tables clusters
 *
 * @param string $nom_meta_base_version
 */
function factures_vider_tables($nom_meta_base_version) {
	#sql_drop_table("spip_factures"); // pas de suppression car facture reglementaire
	effacer_meta($nom_meta_base_version);
}
