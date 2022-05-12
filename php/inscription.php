<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (em_est_authentifie()){
    header('Location: cuiteur_1.php');
    exit();
}

// traitement si soumission du formulaire d'inscription
$er = isset($_POST['btnSInscrire']) ? eml_traitement_inscription() : array(); 

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Inscription', '../styles/cuiteur.css');

em_aff_entete('Inscription',false);
em_aff_infos(false);

eml_aff_formulaire($er);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Affichage du contenu de la page (formulaire d'inscription)
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function eml_aff_formulaire(array $err): void {

    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe 
    if (isset($_POST['btnSInscrire'])){
        $values = em_html_proteger_sortie($_POST);
    }
    else{
        $values['pseudo'] = $values['nomprenom'] = $values['email'] = $values['naissance'] = '';
    }
        
    if (count($err) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }


    echo    
            '<p>Pour vous inscrire, merci de fournir les informations suivantes. </p>',
            '<form method="post" action="inscription.php">',
                '<table>';

    em_aff_ligne_input( 'Votre pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 
                        'placeholder' => 'Minimum 4 caractères alphanumériques', 'required' => null));
    em_aff_ligne_input('Votre mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '', 'required' => null));
    em_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));
    em_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'nomprenom', 'value' => $values['nomprenom'], 'required' => null));
    em_aff_ligne_input('Votre adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $values['email'], 'required' => null));
    em_aff_ligne_input('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $values['naissance'], 'required' => null));

    echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnSInscrire" value="S\'inscrire">',
                            '<input type="reset" value="Réinitialiser">', 
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';
}

/**
 *  Traitement de l'inscription 
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. enregistrement du nouvel inscrit dans la base
 *      Etape 3. ouverture de la session et redirection vers la page protegee.php 
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function eml_traitement_inscription(): array {
    
    if( !em_parametres_controle('post', array('pseudo', 'email', 'nomprenom', 'naissance', 
                                              'passe1', 'passe2', 'btnSInscrire'))) {
        em_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $erreurs = array();
    
    // vérification du pseudo
    $l = mb_strlen($_POST['pseudo'], 'UTF-8');
    if ($l == 0){
        $erreurs[] = 'Le pseudo doit être renseigné.';
    }
    else if ($l < LMIN_PSEUDO || $l > LMAX_PSEUDO){
        $erreurs[] = 'Le pseudo doit être constitué de '. LMIN_PSEUDO . ' à ' . LMAX_PSEUDO . ' caractères.';
    }
    else if( !mb_ereg_match('^[[:alnum:]]{'.LMIN_PSEUDO.','.LMAX_PSEUDO.'}$', $_POST['pseudo'])){
        $erreurs[] = 'Le pseudo ne doit contenir que des caractères alphanumériques.' ;
    }
    
    // vérification des mots de passe
    if ($_POST['passe1'] !== $_POST['passe2']) {
        $erreurs[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($_POST['passe1'], 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
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
    
   
    if (count($erreurs) == 0) {
        // vérification de l'unicité du pseudo 
        // (uniquement si pas d'autres erreurs, parce que la connection à la base de données est consommatrice de ressources)
        $bd = em_bd_connect();

        // pas utile, car le pseudo a déjà été vérifié, mais tellement plus sécurisant...
        $pseudo = em_bd_proteger_entree($bd, $_POST['pseudo']);
        $sql = "SELECT usID,usPasse FROM users WHERE usPseudo = '$pseudo'"; 
    
        $res = em_bd_send_request($bd, $sql);
        
        if (mysqli_num_rows($res) != 0) {
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
			header('Location: ../index.php');
        }
        else{
            // libération des ressources 
            mysqli_free_result($res);
        }
        
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }
    
    // pas d'erreurs ==> enregistrement de l'utilisateur
    $nomprenom = em_bd_proteger_entree($bd, $_POST['nomprenom']);
    $email = em_bd_proteger_entree($bd, $_POST['email']);
    
    $passe1 = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
    $passe1 = em_bd_proteger_entree($bd, $passe1);
    
    $aaaammjj = $annee*10000  + $mois*100 + $jour;
    
    $date_inscription = date('Ymd');
    
    $sql = "INSERT INTO users(usNom, usVille, usWeb, usMail, usPseudo, usPasse, usBio, usDateNaissance, usDateInscription) 
            VALUES ('$nomprenom', '', '', '$email', '$pseudo', '$passe1',  '', $aaaammjj, $date_inscription)";
            
    em_bd_send_request($bd, $sql);
    
    // mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
    $_SESSION['usID'] = mysqli_insert_id($bd);
    
    // libération des ressources
    mysqli_close($bd);
    
    // redirection vers la page protegee.php
    header('Location: compte.php'); //TODO : à modifier dans le projet
    exit();
}


?>
