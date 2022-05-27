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

$all_match = NULL;

$bd = em_bd_connect();

tcag_catch_result_list_users_responce($bd);

if (isset($_POST['btnRecherche']) && 
    isset($_POST['userName']) &&
    !empty($_POST['userName'])){
        $sql = 'SELECT `usId` FROM `users` WHERE `usNom` LIKE "%'.$_POST['userName'].'%" OR `usPseudo` LIKE "%'.$_POST['userName'].'%"';

        $result = em_bd_send_request($bd, $sql);

        $users_id_infos = [];

        while (($t = mysqli_fetch_assoc($result))){
            $users_id_infos[] = $t['usId'];
        }
        mysqli_free_result($result);
        $all_match = tcag_get_user_infos_send_req($bd, tcag_get_user_infos_prep_req($users_id_infos));
    }

em_aff_debut('Rechercher des utilisateurs', '../styles/cuiteur.css');
em_aff_entete('Rechercher des utilisateurs');
em_aff_infos();

echo '<form action="#" method="POST">',
     '<input type="text" name="userName" id="userName"></input>',
     '<input type="submit" id="btnRecherche" name="btnRecherche" value="Rechercher" title="Rechercher un utilisateur">',
     '</form>';

if (isset($_POST['btnRecherche'])){
    if (!isset($_POST['userName']) ||
    empty($_POST['userName'])){
        echo '<p class="error">Vous de vez préciser le nom de l\'utilisateur</p>';
    }
    else {
        echo '<h2>Résultats de la recherche</h2><hr>';
        if ($all_match == NULL){
            echo '<p class="error">Aucun utilisateur ne correspond a votre recherche</p>';
        }
        else {
            echo '<form action="#" method="POST">';
            tcag_aff_result_list_users($all_match, -1);
        }
    }
}

mysqli_close($bd);

em_aff_pied();
em_aff_fin();
?>