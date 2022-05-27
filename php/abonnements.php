<?php

function em_genere_ligne_tab (string $titre, string $donnee):void {
    echo "<tr><td><strong>{$titre} :</strong></td><td>{$donnee}</td></tr>";
}
//TODO: gerer si l'id entrée n'existre pas

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

if (!empty($_GET["user"]))
    $idUser = $_GET["user"];
elseif(!empty($_GET["pseudo"])){
    $idUser = $_GET["pseudo"];
    $sql = "SELECT usId FROM `users` WHERE `usPseudo` = '{$_GET["pseudo"]}'";
    $res = em_bd_send_request($bd, $sql);
    if (mysqli_num_rows($res) == 1){
        $idUser = mysqli_fetch_assoc($res)['usId'];
    }
    else {
        $idUser ="";
    }
    mysqli_free_result($res);
}
else
    $idUser = "";

if (empty($idUser)){
    $str = "Le profil est introuvable";
    em_aff_debut($str, '../styles/cuiteur.css');
    em_aff_entete($str);
    em_aff_infos();
    mysqli_close($bd);

    em_aff_pied();
    em_aff_fin();
    ob_end_flush();
    exit;
}

tcag_catch_result_list_users_responce($bd);

$all_id_users_infos = [];
$all_id_users_infos[] = $idUser;

$sql = "SELECT `eaIDAbonne` 
        FROM `estabonne` 
        WHERE 
        eaIDUser = {$idUser}";

$result = em_bd_send_request($bd, $sql);

while (($t = mysqli_fetch_assoc($result))){
    $all_id_users_infos[] = $t['eaIDAbonne'];
}
mysqli_free_result($result);

$all_info_users = tcag_get_user_infos_send_req($bd, tcag_get_user_infos_prep_req($all_id_users_infos));
$page_user_info = $all_info_users[0];
array_splice($all_info_users, 0, 1);

mysqli_close($bd);


$str = "Les abonnements de {$page_user_info['usPseudo']}";
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