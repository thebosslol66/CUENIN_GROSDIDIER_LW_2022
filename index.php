<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'php/bibli_generale.php';
require_once 'php/bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (em_est_authentifie()){
    header('Location: php/cuiteur.php');
    exit();
}

// traitement si soumission du formulaire d'inscription
$er = isset($_POST['btnSInscrire']) ? eml_traitement_inscription() : array(); 

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Connectez-vous', 'styles/cuiteur.css');

em_aff_entete('Connectez-vous', false);
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
            '<p>Pour vous connecter à, il faut vous authentifier :</p>',
            '<form method="post" action="index.php">',
                '<table>';

    em_aff_ligne_input( 'Pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 'required' => null));
    em_aff_ligne_input('Mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => null));

    echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnSInscrire" value="Connexion">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';

echo 'Pas encore de compte ? <a href="./php/inscription.php">Inscrivez-vous</a> sans tarder !',
  '<br>Vous hésitez à vous inscrire ? Laissez-vous séduire par une <a href="html/presentation.html">présentation</a> des possibilitées de Cuiteur.';
}

function eml_traitement_inscription(): array {
    
    if( !em_parametres_controle('post',array('pseudo', 'passe','btnSInscrire'))) {
        em_session_exit();   
    }
    
    foreach($_POST as $val){
        $val = trim($val);
    }

	return em_verification_connection($_POST["pseudo"], $_POST["passe"], 'php/cuiteur.php');
}