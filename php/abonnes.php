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

[$idUser, $page_user_info] = tcag_get_user_info_or_not_found_user_page($bd);

tcag_catch_result_list_users_responce($bd);

$all_id_users_infos = [];

$sql = "SELECT `eaIDUser` 
        FROM `estabonne` 
        WHERE 
        eaIDAbonne = {$idUser}";

$result = em_bd_send_request($bd, $sql);

while (($t = mysqli_fetch_assoc($result))){
    $all_id_users_infos[] = $t['eaIDUser'];
}

mysqli_free_result($result);

if ($all_id_users_infos){
    $all_info_users = tcag_get_user_infos_send_req($bd, tcag_get_user_infos_prep_req($all_id_users_infos));
}
else {
    $all_info_users = [];
}

mysqli_close($bd);


$str = "Les abonnées de {$page_user_info['usPseudo']}";
em_aff_debut($str, '../styles/cuiteur.css');
em_aff_entete($str);
em_aff_infos();

echo '<form action="#" method="POST">';
if ($idUser == $_SESSION['usID']){
    echo '<div class="user-infos">';
            tcag_aff_user_infos($page_user_info);
    echo '</div>';
}
else {
    echo '<div class="first-user">';
        tcag_aff_user_infos($page_user_info);
        tcag_aff_user_infos_with_abo_button($page_user_info);
    echo '</div>';
}

echo '<form action="#" method="POST">';
tcag_aff_result_list_users($all_info_users, $idUser);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
?>