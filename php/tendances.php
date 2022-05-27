<?php

function tcag_aff_ligne_tendance (mysqli $bd, int $date, String $titre):void {
    $sql = "SELECT taID, COUNT(*) AS NB
    FROM tags JOIN blablas ON taIDBlabla = blID
    WHERE blDate >= {$date}
    GROUP BY taID
    ORDER BY NB DESC
    LIMIT 0,10";

    $res = em_bd_send_request($bd, $sql);
    echo "<h2>{$titre}</h2>";
    if (mysqli_num_rows($res) == 0){
        echo 'Aucune tendance ...';
    } else {
        echo '<ol>';
        while ($t = mysqli_fetch_assoc($res)) {
            echo "<li><a href=\"?tag={$t['taID']}\"><strong>{$t['taID']}({$t['NB']})</strong></a></li>";
        }
    }
    echo '</ol>';
    mysqli_free_result($res);
}

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! em_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

$all_match = NULL;

$bd = em_bd_connect();

tcag_catch_result_list_users_responce($bd);

em_aff_debut('Tendances', '../styles/cuiteur.css');
if (empty($_GET['tag'])) {
    em_aff_entete('');
} else {
    em_aff_entete($_GET['tag']);
}
em_aff_infos();
$bd = em_bd_connect();
if (empty($_GET['tag'])) {
    $date = date('Ymd');
    tcag_aff_ligne_tendance ($bd, $date, "Top 10 du jour");
    tcag_aff_ligne_tendance ($bd, $date - 7, "Top 10 de la semaine");
    tcag_aff_ligne_tendance ($bd, $date - 100, "Top 10 du mois");
    tcag_aff_ligne_tendance ($bd, $date - 10000, "Top 10 de l'année");
} else {
    $sql = "
(   SELECT 
        blID,
        blTexte, 
        blDate, 
        blHeure,
        users.usID AS autID, 
        users.usPseudo AS autPseudo, 
        users.usNom AS autNom, 
        users.usAvecPhoto AS autPhoto,
        users2.usID AS oriID, 
        users2.usPseudo AS oriPseudo, 
        users2.usNom AS oriNom, 
        users2.usAvecPhoto AS oriPhoto
	FROM 
        ((blablas JOIN tags ON blID = taIDBlabla)INNER JOIN users ON blIDAuteur = users.usID)
            LEFT OUTER JOIN `users` AS users2 ON `blIDAutOrig` = users2.usID
	WHERE taID = '{$_GET['tag']}')     
ORDER BY blDate DESC, blHeure DESC";
    echo '<ul>';
    $res = em_bd_send_request($bd, $sql);
    if (mysqli_num_rows($res) == 0){
        echo '<li>Le fil de blablas est vide</li>';
    } else {
        em_aff_blablas($res);
    }
    echo '</ul>';
    mysqli_free_result($res);
}

mysqli_close($bd);

em_aff_pied();
em_aff_fin();
?>