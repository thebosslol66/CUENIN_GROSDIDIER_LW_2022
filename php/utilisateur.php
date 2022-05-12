<?php
echo var_dump($_GET);
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

$sql = "SELECT 
                usPseudo,
                usID,
                usAvecPhoto,
                usNom,
                usWeb,
                usDateNaissance,
                usDateInscription,
                usVille,
                usBio,
                usWeb,
                (SELECT COUNT(blid) FROM blablas WHERE blIDAuteur = {$_GET["id"]}) AS blabla,
                (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$_GET["id"]}) AS abos,
                (SELECT COUNT(eaIDUser) from estabonne WHERE eaIDAbonne = {$_GET["id"]}) AS abos2,
                (SELECT COUNT(meIDUser) from mentions WHERE meIDBlabla = {$_GET["id"]}) AS mention
            FROM users
            WHERE usID = {$_SESSION['usID']}";

$res = em_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);
$str = "Le profil de {$t['usPseudo']}";
echo var_dump($str);
em_aff_debut($str, '../styles/cuiteur.css');
em_aff_entete($str);
em_aff_infos();

    echo '<img src="../';
    if ($t['usAvecPhoto'] == 1) {
        echo "upload/{$t['usPseudo']}.jpg";
    } else {
        echo 'images/anonyme.jpg';
    } 
    echo    '" class="imgAuteur" alt="photo de l\'utilisateur">',
            em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($t['usPseudo']),'id', $t['usID'], 'Voir mes infos'), 
            ' ', em_html_proteger_sortie($t['usNom']), '<br>',
            "<a href=blablas.php>{$t['blabla']} blablas</a> - <a href=mentions.php>{$t['mention']} mentions</a> ",
            "- <a href=abonnes.php>{$t['abos']} abonnés</a> - <a href=abonnements.php>{$t['abos2']} abonnements</a>",'</strong>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
?>