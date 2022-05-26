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

if (isset($_POST['btnRecherche']) && 
    isset($_POST['userName']) &&
    !empty($_POST['userName'])){
        $sql = 'SELECT `usId` FROM `users` WHERE `usNom` LIKE "%'.$_POST['userName'].'%" OR `usPseudo` LIKE "%'.$_POST['userName'].'%"';

        $result = em_bd_send_request($bd, $sql);
        $counter = 0;

        while (($t = mysqli_fetch_assoc($result))){
            $sql = "SELECT 
                        usId,
                        usPseudo,
                        usAvecPhoto,
                        usNom,
                        (SELECT COUNT(blid) FROM blablas WHERE blIDAuteur = {$t['usId']}) AS nbBlabla,
                        (SELECT COUNT(*) from mentions WHERE meIDUser = {$t['usId']}) AS nbMention,
                        (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$t['usId']}) AS nbAbos,
                        (SELECT COUNT(eaIDUser) from estabonne WHERE eaIDAbonne = {$t['usId']}) AS nbAbos2,
                        (SELECT COUNT(*) from estabonne WHERE eaIDUser = {$t['usId']} AND eaIDAbonne = {$_SESSION['usID']}) AS isAbo
                    FROM users
                    WHERE usID = {$t['usId']}";
            $info_user_search = em_bd_send_request($bd, $sql);
            if ($info_user_search){
                $all_match[$counter] = mysqli_fetch_assoc($info_user_search);
                mysqli_free_result($info_user_search);
                $counter++;
            }
        }
        mysqli_free_result($result);
    }

if (isset($_POST['btnAbonner'])){
    $array_to_abonner = NULL;
    $array_to_abonner_counter = 0;

    $array_to_desabonner = NULL;
    $array_to_desabonner_counter = 0;

    foreach($_POST as $key=>$value){
        if (preg_match("/^abonner-([0-9]+)$/", $key, $id_abonner)){
            $array_to_abonner[$array_to_abonner_counter] = $id_abonner[1];
            $array_to_abonner_counter++;
        }
        elseif (preg_match("/^desabonner-([0-9]+)$/", $key, $id_desabonner)){
            $array_to_desabonner[$array_to_desabonner_counter] = $id_desabonner[1];
            $array_to_desabonner_counter++;
        }
    }
    $sql = "";
    for ($i = 0; $i < $array_to_desabonner_counter; $i++){
        if ($i == 0){
            $sql = "DELETE FROM `estabonne` WHERE (eaIDUser = {$array_to_desabonner[$i]} AND eaIDAbonne = {$_SESSION['usID']})";
        } else {
            $sql .= " OR (eaIDUser = {$array_to_desabonner[$i]} AND eaIDAbonne = {$_SESSION['usID']})";
        }
    }
    if ($sql){
        em_bd_send_request($bd, $sql);
    }
    $sql = "";
    $date_abonnement = date('Ymd');
    for ($i = 0; $i < $array_to_abonner_counter; $i++){
        if ($i == 0){
            $sql = "INSERT INTO `estabonne`(`eaIDUser`, `eaIDAbonne`, `eaDate`) VALUES ('{$array_to_abonner[$i]}', '{$_SESSION['usID']}', '$date_abonnement')";
        } else {
            $sql .= ", ('{$array_to_abonner[$i]}', '{$_SESSION['usID']}', '$date_abonnement')";
        }
    }
    if ($sql){
        em_bd_send_request($bd, $sql);
    }
    header('Location: ./cuiteur.php');
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
            $number_result = count($all_match);
            echo '<form action="#" method="POST">',
                 '<ul>';
            for ($i = 0; $i < $number_result; $i++){
                echo '<li>';
                tcag_aff_user_infos($all_match[$i]);
                //Affichage mettre checkbox a droite
                echo '<br>',
                     '<p class="abonement-checkbox">';
                if ($_SESSION['usID'] != $all_match[$i]['usId']){
                    if ($all_match[$i]['isAbo'] == 0){
                        echo    '<input type="checkbox" title="S\'abonner a '.$all_match[$i]['usPseudo'].'" value="" id="abonner-'.$all_match[$i]['usId'].'" name="abonner-'.$all_match[$i]['usId'].'">',
                                '<label for="abonner-'.$all_match[$i]['usId'].'">S\'abonner</label>';
                    } else {
                        echo    '<input type="checkbox" title="Se désabonner de '.$all_match[$i]['usPseudo'].'" value="Se désabonner" id="desabonner-'.$all_match[$i]['usId'].'" name="desabonner-'.$all_match[$i]['usId'].'">',
                                '<label for="desabonner-'.$all_match[$i]['usId'].'">Se désabonner</label>';
                    }
                }
                echo '</p></li>';
            }
            echo '</ul>',
                 '<table>',
                    '<tr>',
                        '<td>',
                        '<input type="submit" name="btnAbonner" id="btnAbonner" value="Valider" title="S\'abonner ou se désabonner des personnes sélectionnées">',
                        '</td>',
                    '</tr>',
                '</table>',
                '</form>';
        }
    }
}

mysqli_close($bd);

em_aff_pied();
em_aff_fin();
?>