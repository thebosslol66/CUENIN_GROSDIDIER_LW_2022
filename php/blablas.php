<?php
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

[$idUser, $page_user_info] = tcag_get_user_info_or_not_found_user_page($bd);

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
        (blablas INNER JOIN users ON blIDAuteur = users.usID)
            LEFT OUTER JOIN `users` AS users2 ON `blIDAutOrig` = users2.usID
	WHERE users.usID = {$idUser})     
ORDER BY blDate DESC, blHeure DESC";

$res = em_bd_send_request($bd, $sql);

$str = "Les blablas de {$page_user_info['usPseudo']}";
em_aff_debut($str, '../styles/cuiteur.css');
em_aff_entete($str);
em_aff_infos();

echo '<div class="user-infos">';
    tcag_aff_user_infos($page_user_info);
echo '</div>',
     '<ul>';
    if (mysqli_num_rows($res) == 0){
        echo '<li>Le fil de blablas est vide</li>';
    }
    else{
        em_aff_blablas($res);
    }
echo '</ul>';
// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
?>