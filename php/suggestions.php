<?php

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! em_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

$bd = em_bd_connect();

tcag_catch_result_list_users_responce($bd);

$final_sugestion = tcag_get_sugestions($bd, NB_SUGGESTIONS, NB_MAX_ABO_REQUEST);

$all_match = [];
$res = tcag_get_user_infos_prep_req($final_sugestion);

em_aff_debut('Suggestions', '../styles/cuiteur.css');
em_aff_entete('Suggestions');
em_aff_infos();

if ($res){
    echo '<form action="#" method="POST">';
    $all_match = tcag_get_user_infos_send_req($bd, $res);
    tcag_aff_result_list_users($all_match, $_SESSION['usID']);
} else {
    echo '<ul><li>Vous n\'avez aucune suggestions</li></ul>';
}

mysqli_close($bd);

em_aff_pied();
em_aff_fin();
?>