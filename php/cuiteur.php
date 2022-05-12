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

if (isset($_POST['btnPublier'])){
    if (!isset($_POST['txtMessage']) || empty($_POST['txtMessage'])){
        //gestion de empty
    }
    if (tcag_has_html_tag($_POST['txtMessage'])){
        //gestion erreur
    }
    $users_mentioned = get_users_mentionned($_POST['txtMessage']);
    $tags_mentioned = get_tags_mentionned($_POST['txtMessage']);
    $text = em_bd_proteger_entree($bd, $_POST['txtMessage']);
    
    $date_cuit = date('Ymd');
    $heure_cuit = date('h:m:s');

    $reqSql = "INSERT INTO `blablas`(`blIDAuteur`, `blDate`, `blHeure`, `blTexte`) 
        VALUES ('".$_SESSION['usID']."','".$date_cuit."','".$heure_cuit."','".$text."')";
    $reqSql .= "\nDECLARE @LASTID AS INTEGER(100) = SCOPE_IDENTITY()";
    if (count($tags_mentioned)>0){
        $reqSql .= "\nINSERT INTO `tags`(`taID`, `taIDBlabla`) VALUES "; 
    }
    foreach($tags_mentioned as $tag){
        $reqSql .= "\n ('".$tag."', '@LASTID')";
    }
    if (count($users_mentioned)>0){
        $reqSql .= "\nINSERT INTO `mentions`(`meIDUser`, `meIDBlabla`) VALUES "; 
    }
    foreach($users_mentioned as $user){
        $reqSql .= "\n ((SELECT usID FROM users WHERE usPseudo = '".$user."'), '@LASTID')";
    }

    var_dump($reqSql);
}

$sql = '
(   SELECT 
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
	WHERE users.usID = '.$_SESSION['usID'].'
UNION
	SELECT
        blTexte, 
        blDate, 
        blHeure,
        users.usID AS autID, 
        users.usPseudo AS autPseudo, 
        users.usNom AS autNom, 
        users.usAvecPhoto AS autPhoto,
        users2.usID AS usID2, 
        users2.usPseudo AS usPseudo2, 
        users2.usNom AS usNom2, 
        users2.usAvecPhoto AS usAvecPhoto2
	FROM 
        ((users INNER JOIN estabonne ON users.usID = eaIDAbonne) 
            INNER JOIN blablas ON users.usID = blIDAuteur)
            LEFT OUTER JOIN `users` AS users2 ON `blIDAutOrig` = users2.usID
	WHERE eaIDUser = '.$_SESSION['usID'].'
UNION
	SELECT 
        blTexte, 
        blDate, 
        blHeure,
        users.usID AS autID, 
        users.usPseudo AS autPseudo, 
        users.usNom AS autNom, 
        users.usAvecPhoto AS autPhoto,
        users2.usID AS usID2, 
        users2.usPseudo AS usPseudo2, 
        users2.usNom AS usNom2, 
        users2.usAvecPhoto AS usAvecPhoto2
	FROM ((users INNER JOIN blablas on users.usID = blIDAuteur) 
        INNER JOIN mentions ON blID = meIDBlabla)
        LEFT OUTER JOIN `users` AS users2 ON `blIDAutOrig` = users2.usID
	WHERE meIDUser = '.$_SESSION['usID'].')     
ORDER BY blDate DESC, blHeure DESC';



$res = em_bd_send_request($bd, $sql);

em_aff_debut('Cuiteur', '../styles/cuiteur.css');
em_aff_entete();
em_aff_infos();
echo '<ul>';

if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
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
