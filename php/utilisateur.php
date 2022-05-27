<?php

function em_genere_ligne_tab (string $titre, string $donnee):void {
    echo    '<tr>',
                '<td>',
                    "<strong>{$titre} :</strong>",
                '</td>',
                '<td>';
    if ($donnee){
        echo        em_html_proteger_sortie($donnee);
    } else{
        echo        "Non renseigné(e)";
    }
    echo        '</td>',
            '</tr>';
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
$bd = em_bd_connect();

if (!empty($_POST['btnDesabonner'])){
    if (!empty($_POST['usId']) && $_POST['usId'] != $_SESSION['usID']){
        $eaIDUser = em_bd_proteger_entree($bd, $_POST['usId']);
        $sql = "DELETE FROM `estabonne` WHERE (eaIDUser = {$_SESSION['usID']} AND eaIDAbonne = {$eaIDUser})";
        em_bd_send_request($bd, $sql);
    }
    mysqli_close($bd);
    header('Location: ./cuiteur.php');
}
if (!empty($_POST["btnAbonner"])){
    if (!empty($_POST['usId']) && $_POST['usId'] != $_SESSION['usID']){
        $date_abonnement = date('Ymd');
        $eaIDUser = em_bd_proteger_entree($bd, $_POST['usId']);
        $sql = "INSERT INTO `estabonne`(`eaIDUser`, `eaIDAbonne`, `eaDate`) VALUES ('{$_SESSION['usID']}', '{$eaIDUser}', '$date_abonnement')";
        em_bd_send_request($bd, $sql);
    }
    mysqli_close($bd);
    header('Location: ./cuiteur.php');
}

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

$sql = "SELECT 
                usPseudo,
                usId,
                usAvecPhoto,
                usNom,
                usWeb,
                usDateNaissance,
                usDateInscription,
                usVille,
                usBio,
                usWeb,
                (SELECT COUNT(blid) FROM blablas WHERE blIDAuteur = {$idUser}) AS nbBlabla,
                (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$idUser}) AS nbAbos2,
                (SELECT COUNT(eaIDUser) from estabonne WHERE eaIDAbonne = {$idUser}) AS nbAbos,
                (SELECT COUNT(*) from mentions WHERE meIDUser = {$idUser}) AS nbMention,
                (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$_SESSION["usID"]} AND eaIDAbonne = {$idUser}) AS estAbonne
            FROM users
            WHERE usID = {$idUser} OR usPseudo = {$idUser}";


$res = em_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);
$str = "Le profil de {$t['usPseudo']}";
em_aff_debut($str, '../styles/cuiteur.css');
em_aff_entete($str);
em_aff_infos();

    echo '<div class="user-infos">';
    tcag_aff_user_infos($t);
    echo '</div>',
        '<form action="#" method="POST"><table class="user-infos">';
    em_genere_ligne_tab ("Date de naissance", em_amj_clair($t['usDateNaissance']));
    em_genere_ligne_tab ("Date d'inscription", em_amj_clair($t['usDateInscription']));
    em_genere_ligne_tab ("Ville de résidence", $t['usVille']);
    em_genere_ligne_tab ("Mini-bio", $t['usBio']);
    em_genere_ligne_tab ("Site Web", $t['usWeb']);
    echo    '<tr>',
                '<td colspan="2">',
                '<input type="hidden" id="usId" name="usId" value="',$idUser,'">';
                if ($idUser != $_SESSION["usID"]) {
                    if ($t['estAbonne']==0)
                        echo '<input type="submit" name="btnAbonner" value="S\'abonner">';
                    else
                        echo '<input type="submit" name="btnDesabonner" value="Se desabonner">';
                }
                
    echo            '</td>',
            '</tr>',
        '</table></form>';
// libération des ressources
mysqli_free_result($res);




mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
?>