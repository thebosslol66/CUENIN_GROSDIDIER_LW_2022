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

$error = "";

$prefill_responce = "";

if (isset($_POST['btnPublier'])){
    if (!isset($_POST['txtMessage']) || empty($_POST['txtMessage'])){
        $error = "Votre cuit est vide ! Veuillez ecrire un message avant de publier.";
    }
    elseif (tcag_has_html_tag($_POST['txtMessage'])){
        $error =  "Votre cuit contient des balises HTML, veuillez les retirer.";
        $prefill_responce = $_POST['txtMessage'];
    }
    elseif (strlen($_POST['txtMessage']) > LMAX_MESSAGE){
        $error = "Votre cuit est trop long, veuillez le raccourcir.";
        $prefill_responce = $_POST['txtMessage'];
    }
    else{
        $users_mentioned = get_users_mentionned($_POST['txtMessage']);
        $tags_mentioned = get_tags_mentionned($_POST['txtMessage']);
        $text = em_bd_proteger_entree($bd, $_POST['txtMessage']);
        
        $date_cuit = date('Ymd');
        $heure_cuit = date('H:i:s');

        $reqSql = "INSERT INTO `blablas`(`blIDAuteur`, `blDate`, `blHeure`, `blTexte`) 
            VALUES ('".$_SESSION['usID']."','".$date_cuit."','".$heure_cuit."','".$text."')";
        $res = em_bd_send_request($bd, $reqSql);
        if ($res){
            $idmess = mysqli_insert_id($bd);
            $reqSql = "" ;
            if (count($tags_mentioned)>0){
                $reqSql .= "INSERT INTO `tags`(`taID`, `taIDBlabla`) VALUES "; 
                foreach($tags_mentioned as $tag){
                    $reqSql .= "\n ('".$tag."', '".$idmess."'),";
                }
                $reqSql = rtrim($reqSql, ",");
                $reqSql .= ";";
                $res = em_bd_send_request($bd, $reqSql);
            }
            
            if (count($users_mentioned)>0){
                $reqSql = "INSERT INTO `mentions`(`meIDUser`, `meIDBlabla`) VALUES "; 
                foreach($users_mentioned as $user){
                    $reqSql .= "\n ((SELECT `usID` FROM `users` WHERE `usPseudo` = '".$user."'), '".$idmess."'),";
                }
                $reqSql = rtrim($reqSql, ",");
                $reqSql .= ";";
                $res = em_bd_send_request($bd, $reqSql);
            }
        }
        else{
            $error = "Une erreur est survenue lors de l'enregistrement de votre cuit.";
            $prefill_responce = $_POST['txtMessage'];
        }
    }
    
}

if (isset($_POST['blaction']) && isset($_POST["blablaId"])){
    if ($_POST['blaction'] == 'delete'){
        $req = "SELECT *
                FROM `blablas`
                WHERE `blIDAuteur` = ".$_SESSION['usID']. "
                AND `blID` = ".$_POST["blablaId"];
        $res = em_bd_send_request($bd, $req);
        $req = "";
        if (mysqli_num_rows($res) > 0){
            mysqli_free_result($res);
            $req = "DELETE
                        FROM `mentions`
                        WHERE `meIDBlabla` = {$_POST["blablaId"]};";
                        $res = em_bd_send_request($bd, $req);
                   $req =  "DELETE
                        FROM `tags`
                        WHERE `taIDBlabla` = {$_POST["blablaId"]};";
                        $res = em_bd_send_request($bd, $req);
                    $req = "DELETE
                        FROM `blablas`
                        WHERE `blID` = {$_POST["blablaId"]};"; 
                        $res = em_bd_send_request($bd, $req);
        } else {
            mysqli_free_result($res);
        }
        $res = em_bd_send_request($bd, $req);
        
    }
    if ($_POST['blaction'] == 'response' && !empty($_POST['authorName'])){
        $prefill_responce = '@'.$_POST['authorName'];
    }
    if ($_POST['blaction'] == 'recuit'){
        $req = "SELECT *
                FROM `blablas`
                WHERE `blID` = ".$_POST["blablaId"];
        $res = em_bd_send_request($bd, $req);
        if (mysqli_num_rows($res) > 0){
            $t = mysqli_fetch_assoc($res);
            $users_mentioned = get_users_mentionned($t["blTexte"]);
            $tags_mentioned = get_tags_mentionned($t["blTexte"]);
            $origAuthor = $t["blIDAutOrig"] ? $t["blIDAutOrig"] : $t["blIDAuteur"];
            $date_cuit = date('Ymd');
            $heure_cuit = date('H:i:s');
            $reqSql = "INSERT INTO `blablas`(`blIDAuteur`, `blDate`, `blHeure`, `blTexte`, `blIDAutOrig`) 
                       VALUES ('".$_SESSION['usID']."','".$date_cuit."','".$heure_cuit."','".em_bd_proteger_entree($bd, $t["blTexte"])."', '".$origAuthor."')";
            mysqli_free_result($res);
            $res = em_bd_send_request($bd, $reqSql);
            if ($res){
                $idmess = mysqli_insert_id($bd);
                $reqSql = "" ;
                if (count($tags_mentioned)>0){
                    $reqSql .= "INSERT INTO `tags`(`taID`, `taIDBlabla`) VALUES "; 
                    foreach($tags_mentioned as $tag){
                        $reqSql .= "\n ('".$tag."', '".$idmess."'),";
                    }
                    $reqSql = rtrim($reqSql, ",");
                    $res = em_bd_send_request($bd, $reqSql);
                }
                
                if (count($users_mentioned)>0){
                    $reqSql = "INSERT INTO `mentions`(`meIDUser`, `meIDBlabla`) VALUES "; 
                    foreach($users_mentioned as $user){
                        $reqSql .= "\n ((SELECT usID FROM users WHERE usPseudo = '".$user."'), '".$idmess."'),";
                    }
                    $reqSql = rtrim($reqSql, ",");
                    $res = em_bd_send_request($bd, $reqSql);
                }
            }
        }
    }
}


$sql = '
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
	WHERE users.usID = '.$_SESSION['usID'].'
UNION
	SELECT
        blID,
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
        blID,
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
em_aff_entete(NULL, true, $prefill_responce);
em_aff_infos();
echo '<ul>';

if ($error){
    echo '<li class="error">', $error, "</li>";
}

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