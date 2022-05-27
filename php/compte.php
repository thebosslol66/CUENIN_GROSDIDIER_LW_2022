<?php

function tcag_gere_form_1 (mysqli $bd):array {
    $erreurs = array();
    
    if (! empty($_POST['part1'])) {
        foreach($_POST as &$val){
            $val = trim($val);
        }
        
        // vérification des noms et prenoms
        if (empty($_POST['nomprenom'])) {
            $erreurs[] = 'Le nom et le prénom doivent être renseignés.'; 
        }
        else {
            if (mb_strlen($_POST['nomprenom'], 'UTF-8') > LMAX_NOMPRENOM){
                $erreurs[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
            }
            $noTags = strip_tags($_POST['nomprenom']);
            if ($noTags != $_POST['nomprenom']){
                $erreurs[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
            }
            else {
                if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST['nomprenom'])){
                    $erreurs[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
                }
            }
        }

        // vérification de la date de naissance
        if (empty($_POST['naissance'])){
            $erreurs[] = 'La date de naissance doit être renseignée.'; 
        }
        else{
            if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
                $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
            }
            else{
                list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
                if (!checkdate($mois, $jour, $annee)) {
                    $erreurs[] = 'La date de naissance n\'est pas valide.'; 
                }
                else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MIN) > time()) {
                    $erreurs[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
                }
                else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MAX + 1) < time()) {
                    $erreurs[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
                }
            }
        }
        
        $maxlenville = 50;
        // vérification du nom de ville
        if (! empty($_POST['ville'])) {
            if (mb_strlen($_POST['ville'], 'UTF-8') > $maxlenville){
                $erreurs[] = 'Le nom de la ville ne peut pas dépasser ' . $maxlenville . ' caractères.';
            }
            $noTags = strip_tags($_POST['ville']);
            if ($noTags != $_POST['ville']){
                $erreurs[] = 'Le nom de la ville ne peut pas contenir de code HTML.';
            }
            else {
                if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST['ville'])){
                    $erreurs[] = 'Le nom de ville contient des caractères non autorisés.';
                }
            }
        }

        $maxlenbio = 255;
        // vérification de la bio
        if (! empty($_POST['bio'])) {
            if (mb_strlen($_POST['bio'], 'UTF-8') > $maxlenbio){
                $erreurs[] = 'La bio ne peut pas dépasser ' . $maxlenbio . ' caractères.';
            }
            $noTags = strip_tags($_POST['bio']);
            if ($noTags != $_POST['bio']){
                $erreurs[] = 'La bio ne peut pas contenir de code HTML.';
            }
        }

        // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
        if (! count($erreurs) > 0) {
            // pas d'erreurs ==> enregistrement de l'utilisateur
            $nomprenom = em_bd_proteger_entree($bd, $_POST['nomprenom']);
            $aaaammjj = $annee*10000  + $mois*100 + $jour;
            $bio = em_bd_proteger_entree($bd, $_POST['bio']);
            $ville = em_bd_proteger_entree($bd, $_POST['ville']);
            
            $sql = "UPDATE users
                    SET usNom = '{$nomprenom}'
                    ,usBio = '{$bio}'
                    ,usVille = '{$ville}'
                    WHERE usID = {$_SESSION['usID']}";
                    
            em_bd_send_request($bd, $sql);

        }
    }
    return $erreurs;
    
}

function tcag_gere_form_2 (mysqli $bd):array {
    $erreurs = array();
    
    if (! empty($_POST['part2'])) {
        foreach($_POST as &$val){
            $val = trim($val);
        }
        
        // vérification du format de l'adresse email
        if (empty($_POST['email'])){
            $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
        }
        else {
            if (mb_strlen($_POST['email'], 'UTF-8') > LMAX_EMAIL){
                $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
            }
            // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
            // est moins forte que celle faite ci-dessous avec la fonction filter_var()
            // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
            // celle faite ci-dessous
            if(! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = 'L\'adresse mail n\'est pas valide.';
            }
        }
        
        // vérification du format du site web
        $maxlenweb = 120;
        if (! empty($_POST['web'])){
            if (mb_strlen($_POST['web'], 'UTF-8') > $maxlenweb){
                $erreurs[] = 'Le site web ne peut pas dépasser '.$maxlenweb.' caractères.';
            }
            if(! filter_var($_POST['web'], FILTER_VALIDATE_URL)) {
                $erreurs[] = 'Le site web n\'est pas valide.';
            }
        }

        // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
        if (! count($erreurs) > 0) {
            // pas d'erreurs ==> enregistrement de l'utilisateur
            $email = em_bd_proteger_entree($bd, $_POST['email']);
            $web = em_bd_proteger_entree($bd, $_POST['web']);

            $sql = "UPDATE users
                    SET usMail = '{$email}'
                    ,usWeb = '{$web}'
                    WHERE usID = {$_SESSION['usID']}";
                    
            em_bd_send_request($bd, $sql);

        }
    }
    return $erreurs;
    
}

function tcag_gere_form_3 (mysqli $bd):array {
    $erreurs = array();
    
    if (! empty($_POST['part3'])) {
        foreach($_POST as &$val){
            $val = trim($val);
        }
        if (!empty($_POST['passe1']) || !empty($_POST['passe2'])) {
            // vérification des mots de passe
            if ($_POST['passe1'] !== $_POST['passe2']) {
                $erreurs[] = 'Les mots de passe doivent être identiques.';
            }
            $nb = mb_strlen($_POST['passe1'], 'UTF-8');
            if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
                $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
            }
            // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
            if (! count($erreurs) > 0) {
                $passe1 = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
                $passe1 = em_bd_proteger_entree($bd, $passe1);



                $sql = "UPDATE users
                        SET usPasse = '{$_POST['passe1']}'
                        WHERE usID = {$_SESSION['usID']}";
                        
                em_bd_send_request($bd, $sql);

            }
            
        }
        if (!empty($_FILES['photo']['name'])) {
            $nom = $_FILES['photo']['name'];
            $ext = strtolower(substr($nom, strrpos($nom, '.')));
            if ($ext != ".jpg") {
                $erreurs[] = 'Extension du fichier non autorisée';
            }

            $type = mime_content_type($_FILES['photo']['tmp_name']);
            if ($type != 'image/jpeg') {
                $erreurs[] = 'Le contenu du fichier n\'est pas valide';
            }

            $Dest = "../upload/{$_SESSION['usID']}.jpg";
            
            if (! count($erreurs) > 0) {
                if ($_FILES['photo']['error'] === 0
                && @is_uploaded_file($_FILES['photo']['tmp_name'])
                && @move_uploaded_file($_FILES['photo']['tmp_name'], $Dest)) {
                    
                } else {
                    $erreurs[] = "Le fichier n'a pas pu être uploadé";
                }
            }
        }

        if ($_POST['usePhoto'] != 0 && $_POST['usePhoto'] != 1) {
            $erreurs[] = 'Il y a eu un problème avec la selection de "Utiliser votre photo"';
        }
        if (($_POST['usePhoto'] == 1)&&(! file_exists("../upload/{$_SESSION['usID']}.jpg")))
            $erreurs[] = "Impossible d'utiliser la photo actuelle, il n'y en a pas.";
        


        // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
        if (! count($erreurs) > 0) {



            $sql = "UPDATE users
                    SET usAvecPhoto = '{$_POST['usePhoto']}'
                    WHERE usID = {$_SESSION['usID']}";
                    
            em_bd_send_request($bd, $sql);

        }
    }
    return $erreurs;
    
}

function em_genere_ligne_tab (string $titre, string $donnee):void {
    echo "<tr><td>{$titre}</td><td>{$donnee}</td></tr>";
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

$err1 = tcag_gere_form_1($bd);
$err2 = tcag_gere_form_2($bd);
$err3 = tcag_gere_form_3($bd);

mysqli_close($bd);
$bd = em_bd_connect();

$sql = "SELECT *
        FROM users
        WHERE usID = {$_SESSION['usID']}";

$res = em_bd_send_request($bd, $sql);
$t = mysqli_fetch_assoc($res);

$str = "Paramètres de mon compte";
em_aff_debut($str, '../styles/cuiteur.css');
em_aff_entete($str);
em_aff_infos();

echo "<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>";

echo   
        '<form method="post" action="#">',
            '<h2>Informations personnelles</h2><table class=tabCentre>';
if (count($err1) > 0) {
    echo '<p class="error">Les erreurs suivantes ont été détectées :';
    foreach ($err1 as $v) {
        echo '<br> - ', $v;
    }
    echo '</p>';    
} else if (! empty($_POST['part1'])) {
    echo '<p class="noterror">La mise à jour des informations sur votre compte a bien été effectuée</p>';
}
        em_aff_ligne_input( 'Nom', array('type' => 'text', 'name' => 'nomprenom', 'value' => (empty($_POST['nomprenom']) ? em_html_proteger_sortie($t['usNom']) : $_POST['nomprenom']), 
        'placeholder' => 'Minimum 4 caractères alphanumériques', 'required' => null));

        $date = $t['usDateNaissance'];
        $jour = $date % 100;
        $date /= 100;
        $mois = (int)$date % 100;
        $date /= 100;
        $an = (int)$date;
        $strdate = $an.'-';
        if ($mois < 10)
            $strdate .= 0;
        $strdate .= $mois.'-';
        if ($jour < 10)
            $strdate .= 0;
        $strdate .= $jour;
        
        em_aff_ligne_input('Date de naissance', array('type' => 'date', 'name' => 'naissance', 'value' => (empty($_POST['naissance']) ? $strdate : $_POST['naissance']), 'required' => null));
        em_aff_ligne_input( 'Ville', array('type' => 'text', 'name' => 'ville', 'value' => (empty($_POST['ville']) ? em_html_proteger_sortie($t['usVille']) : $_POST['ville']), 
        ''));
        em_aff_ligne_input( 'Mini-bio', array('type' => 'text', 'name' => 'bio', 'value' => (empty($_POST['bio']) ? em_html_proteger_sortie($t['usBio']) : $_POST['bio']), 
        ''));
echo 
    '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="part1" value="Valider">',
        '</td>',
    '</tr>',
    '</table></form>';

echo '<form method="post" action="#">',
        '<h2>Informations sur votre compte Cuiteur</h2><table class=tabCentre>';
if (count($err2) > 0) {
    echo '<p class="error">Les erreurs suivantes ont été détectées :';
    foreach ($err2 as $v) {
        echo '<br> - ', $v;
    }
    echo '</p>';    
} else if (! empty($_POST['part2'])) {
    echo '<p class="noterror">La mise à jour des informations sur votre compte a bien été effectuée</p>';
}
        em_aff_ligne_input( 'Adresse mail', array('type' => 'email', 'name' => 'email', 'value' =>  (empty($_POST['email']) ? em_html_proteger_sortie($t['usMail']) : $_POST['email']), 
        'placeholder' => '', 'required' => null));
        em_aff_ligne_input( 'Site web', array('type' => 'text', 'name' => 'web', 'value' =>  (empty($_POST['web']) ? em_html_proteger_sortie($t['usWeb']) : $_POST['web']), 
        'placeholder' => ''));
        echo '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="part2" value="Valider">',
        '</td>',
    '</tr>',
    '</table></form>';

echo '<form enctype="multipart/form-data" method="post" action="#">',
    '<h2 class=titreCompte>Paramètres de votre compte Cuiteur</h2><table class=tabCentre>';
    if (count($err3) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err3 as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    } else if (! empty($_POST['part3'])) {
        echo '<p class="noterror">La mise à jour des informations sur votre compte a bien été effectuée</p>';
    }
    em_aff_ligne_input('Changer le mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => ''));
    em_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => ''));
    $srcPhoto = '<img src="../';
    $srcPhoto .= "upload/{$t['usID']}.jpg\" alt=\"Image non trouvée\"";
    $srcPhoto .= ' class="imgAuteur" alt="Votre photo">';
    if (empty($_POST['usePhoto'])) {
        if ($t['usAvecPhoto'] == 1) {
            $inlast = '<input type="radio" name="usePhoto" value="0">non
            <input type="radio" name="usePhoto" value="1" checked>oui';
        } else {
            $inlast = '<input type="radio" name="usePhoto" checked value="0">non
            <input type="radio" name="usePhoto" value="1" >oui';
        }
    } else if ($_POST['usePhoto']==1) {
        $inlast = '<input type="radio" name="usePhoto" value="0">non
        <input type="radio" name="usePhoto" value="1" checked>oui';
    } else {
        $inlast = '<input type="radio" name="usePhoto" checked value="0">non
        <input type="radio" name="usePhoto" value="1" >oui';
    }
    
    em_genere_ligne_tab ('Votre photo actuelle', $srcPhoto.'<br>Taille 20ko maximum<br>Image JPG carrée (mini 50x50px)<input type="file" name="photo">');
    em_genere_ligne_tab ('Utiliser votre photo', $inlast);
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="0.05">';
    echo 
    '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="part3" value="Valider">',
        '</td>',
    '</tr>',
    '</table></form></table>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

?>