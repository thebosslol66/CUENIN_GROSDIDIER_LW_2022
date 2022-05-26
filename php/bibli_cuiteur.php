<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application Cuiteur                 *
 *********************************************************/

 // Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

//définition de l'encodage des caractères pour les expressions rationnelles multi-octets
mb_regex_encoding ('UTF-8');

define('IS_DEV', true);//true en phase de développement, false en phase de production

 // Paramètres pour accéder à la base de données
define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuiteur_bd');
define('BD_USER', 'cuiteur_userl');
define('BD_PASS', 'cuiteur_passl'); 
/*define('BD_NAME', 'merlet_cuiteur');
define('BD_USER', 'merlet_u');
define('BD_PASS', 'merlet_p');*/


// paramètres de l'application
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 30); //longueur du champ dans la base de données
define('LMAX_EMAIL', 80); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 60); //longueur du champ dans la base de données


define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('AGE_MIN', 18);
define('AGE_MAX', 120);

define('CUIT_PER_REQUEST', 4);


//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 */
function em_aff_entete(?string $titre = null, bool $link=true):void{
    echo '<div id="bcContenu">',
          $link ? '<header>' : '<header class="deconnecte">';
	if ($link){
    echo        '<a href="./deconnexion.php" title="Se déconnecter de cuiteur"></a>',
                '<a href="../index.php" title="Ma page d\'accueil"></a>',
                '<a href="./recherche.php" title="Rechercher des personnes à suivre"></a>',
                '<a href="./compte.php" title="Modifier mes informations personnelles"></a>';
    }
    if ($titre === null){
        echo    '<form action="" method="POST">',
                    '<textarea name="txtMessage"></textarea>',
                    '<input type="submit" name="btnPublier" value="" title="Publier mon message">',
                '</form>';
    }
    else{
        echo    '<h1>', $titre, '</h1>';
    }
    echo    '</header>';    
}

//_______________________________________________________________
/**
 * Génération et affichage du bloc d'informations utilisateur
 *
 * @param bool    $connecte  true si l'utilisateur courant s'est authentifié, false sinon
 */
function em_aff_infos(bool $connecte = true):void{
    echo '<aside>';
    if ($connecte){
        $bd = em_bd_connect();

    $sql = "SELECT 
                usId,
                usPseudo,
                usAvecPhoto,
                usNom,
                (SELECT COUNT(blid) FROM blablas WHERE blIDAuteur = {$_SESSION['usID']}) AS nbBlabla,
                (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$_SESSION['usID']}) AS nbAbos,
                (SELECT COUNT(eaIDUser) from estabonne WHERE eaIDAbonne = {$_SESSION['usID']}) AS nbAbos2
            FROM users
            WHERE usID = {$_SESSION['usID']}";

    $res = em_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);

    $blabla = $t['nbBlabla'] <= 1 ? "blabla": "blablas";
    $abonnement = $t['nbAbos'] <= 1 ? "abonné": "abonnés";
    $abonne = $t['nbAbos2'] <= 1 ? "abonement": "abonements";

        echo
            '<h3>Utilisateur</h3>',
            '<ul>',
                '<li>';
                if ($t['usAvecPhoto'] == 1) {
                    echo '<img src="../images/',$t['usPseudo'],'.jpg" alt="photo de l\'utilisateur">';
                } else {
                    echo '<img src="../images/anonyme.jpg" alt="photo de l\'utilisateur">';
                }
        echo    em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($t['usPseudo']).'</strong>','user', $t['usId'], 'Voir mes infos'),
                ' ', '<strong>', em_html_proteger_sortie($t['usNom']), '</strong>',
                '</li>',
                '<li>', em_html_a('blablas.php', "{$t['nbBlabla']} ".$blabla, 'user', $t['usId'], "Afficher les blablas de {$t['usPseudo']}"), '</li>',
                '<li>', em_html_a('abonnes.php', "{$t['nbAbos2']} ".$abonne, 'user', $t['usId'], "Afficher les abonés de {$t['usPseudo']}"),'</li>',
                '<li>', em_html_a('abonnements.php', "{$t['nbAbos']} ".$abonnement, 'user', $t['usId'], "Afficher les abonements de {$t['usPseudo']}"),'</li>',    
            '</ul>',
            '<h3>Tendances</h3>',
            '<ul>',
                '<li>#<a href="../index.html" title="Voir les blablas contenant ce tag">info</a></li>',
                '<li>#<a href="../index.html" title="Voir les blablas contenant ce tag">lol</a></li>',
                '<li>#<a href="../index.html" title="Voir les blablas contenant ce tag">imbécile</a></li>',
                '<li>#<a href="../index.html" title="Voir les blablas contenant ce tag">fairelafete</a></li>',
                '<li><a href="../index.html">Toutes les tendances</a><li>',
            '</ul>',
            '<h3>Suggestions</h3>',             
            '<ul>',
                '<li>',
                    '<img src="../images/yoda.jpg" alt="photo de l\'utilisateur">',
                    '<a href="../index.html" title="Voir mes infos">yoda</a> Yoda',
                '</li>',       
                '<li>',
                    '<img src="../images/paulo.jpg" alt="photo de l\'utilisateur">',
                    '<a href="../index.html" title="Voir mes infos">paulo</a> Jean-Paul Sartre',
                '</li>',
                '<li><a href="../index.html">Plus de suggestions</a></li>',
            '</ul>';
            // libération des ressources
            mysqli_free_result($res);
            mysqli_close($bd);
        }
    echo '</aside>',
         '<main>';   

}

//_______________________________________________________________
/**
 * Génération et affichage du pied de page
 *
 */
function em_aff_pied(): void{
    echo    '</main>',
            '<footer>',
                '<a href="../index.html">A propos</a>',
                '<a href="../index.html">Publicité</a>',
                '<a href="../index.html">Patati</a>',
                '<a href="../index.html">Aide</a>',
                '<a href="../index.html">Patata</a>',
                '<a href="../index.html">Stages</a>',
                '<a href="../index.html">Emplois</a>',
                '<a href="../index.html">Confidentialité</a>',
            '</footer>',
    '</div>';
}

//_______________________________________________________________
/**
* Affichages des résultats des SELECT des blablas.
*
* La fonction gére la boucle de lecture des résultats et les
* encapsule dans du code HTML envoyé au navigateur 
*
* @param mysqli_result  $r       Objet permettant l'accès aux résultats de la requête SELECT
*/
function em_aff_blablas(mysqli_result $r): void {
    if (!isset($_GET['page'])){
        $_GET['page'] = 1;
    }
    else if($_GET['page'] < 1){
        $_GET['page'] = 1;
    }
    
    $compteur = 0;
    $maxCuit = $_GET['page'] * CUIT_PER_REQUEST;
    while (($t = mysqli_fetch_assoc($r)) && $compteur < $maxCuit) {
        if ($t['oriID'] === null){
            $id_orig = $t['autID'];
            $pseudo_orig = $t['autPseudo'];
            $photo = $t['autPhoto'];
            $nom_orig = $t['autNom'];
        }
        else{
            $id_orig = $t['oriID'];
            $pseudo_orig = $t['oriPseudo'];
            $photo = $t['oriPhoto'];
            $nom_orig = $t['oriNom'];
        }
        echo    '<li>', 
                    '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                    '" class="imgAuteur" alt="photo de l\'auteur">',
                    em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($pseudo_orig).'</strong>','user', $id_orig, 'Voir mes infos'), 
                    ' ', em_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .em_html_a( 'utilisateur.php','<strong>'.em_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'id', $t['autID'], 'Voir mes infos') : ''),
                    '<br>';

                    $output = em_html_proteger_sortie($t['blTexte']);
                    $output = ag_active_mention_and_tags($output);
                    echo $output;

                    echo '<p class="finMessage">',
                    em_amj_clair($t['blDate']), ' à ', em_heure_clair($t['blHeure']);
                    if ($t['autID'] == $_SESSION['usID']) {
                        echo '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'-del\').submit();">Supprimer</a>';
                    } else  {
                        echo 
                    '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'-rep\').submit();">Répondre</a>',
                    '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'rec\').submit();">Recuiter</a>';
                    }
                    echo 
                    '<form method="POST" id="cuit-',  $t['blID'], '-del">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="delete">',
                    '</form>',
                    '<form method="POST" id="cuit-',  $t['blID'], '-rep">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="response">',
                    '</form>',
                    '<form method="POST" id="cuit-',  $t['blID'], '-rec">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="recuit">',
                    '</form>',
                '</li>';
        $compteur++;
    }
    if ($compteur < mysqli_num_rows($r)){
        echo    '<li class="plusBlablas">',
                    '<a href="?page=', $_GET['page']+1, '"><strong>Plus de blablas</strong></a>',
                    '<img src="../images/speaker.png" width="75" height="82" alt="Image du speaker \'Plus de blablas\'">',
                '</li>';
    }
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function em_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function em_session_exit(string $page = '../index.php'):void {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
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
function em_verification_connection(string $pseudo, string $password, string $target='php/protegee.php'): array {
    
    $erreur=false;
    
    // vérification du pseudo
    $l = mb_strlen($password, 'UTF-8');
    if ($l == 0){
        $erreur=true;
    }
    else if ($l < LMIN_PSEUDO || $l > LMAX_PSEUDO){
        $erreur=true;
    }
    else if( !mb_ereg_match('^[[:alnum:]]{'.LMIN_PSEUDO.','.LMAX_PSEUDO.'}$', $pseudo)){
        $erreur=true;
    }
    
    $nb = mb_strlen($password, 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $erreur=true;
    }
  
    if ($erreur == false) {
        // vérification de l'unicité du pseudo 
        // (uniquement si pas d'autres erreurs, parce que la connection à la base de données est consommatrice de ressources)
        $bd = em_bd_connect();

        // pas utile, car le pseudo a déjà été vérifié, mais tellement plus sécurisant...
        $pseudo = em_bd_proteger_entree($bd, $pseudo);
        $passe = password_hash($password, PASSWORD_DEFAULT);
        $sql = "SELECT usID,usPasse FROM users WHERE usPseudo = '$pseudo'"; 
    
        $res = em_bd_send_request($bd, $sql);
        
        if (mysqli_num_rows($res) == 0) {
            $erreur=true;
        }
      
      while ($t = mysqli_fetch_assoc($res)) {
        if (! password_verify($password, $t['usPasse'])) {
          $erreur=true;
        } else {
          $_SESSION['usID'] = $t['usID'];
        }
      }
      
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if ($erreur == true) {
        $erreurs = array();
        $erreurs[] = 'Les identifiants sont invalides';
        return $erreurs;
    }
    // redirection vers la page protegee.php
    header('Location: '.$target);
    exit();
}


function get_users_mentionned(string $text): array {
    preg_match_all('/@([a-zA-Z0-9]+)/',$text,$matches, PREG_SET_ORDER, 0);
    return array_column($matches, 1);
}

function get_tags_mentionned(string $text): array {
    preg_match_all('/#([a-zA-Z0-9éâîôùèçàïû]+)/',$text,$matches, PREG_SET_ORDER, 0);
    return array_column($matches, 1);
}

function ag_active_mention_and_tags(string $cuit): string {
    $cuit = html_entity_decode($cuit, ENT_NOQUOTES, "UTF-8");
    $t = preg_replace("/(?<=([^&]))#([a-zA-Z0-9éâîôùèçàïû]+)/m", "<a class=\"tag\" href=\"tendances.php?tag=$2\">#$2</a>", $cuit);
    $t = preg_replace("/(?<=([^&]))@([a-zA-Z0-9]+)[$]?/", "<a class=\"peopleCite\" href=\"utilisateur.php?pseudo=$2\">@$2</a>", $t);
    return $t;
}


function tcag_aff_user_infos(array $infos){
    $blabla = $infos['nbBlabla'] <= 1 ? "blabla": "blablas";
    $mention = $infos['nbMention'] <= 1 ? "mention": "mentions";
    $abonnement = $infos['nbAbos'] <= 1 ? "abonné": "abonnés";
    $abonne = $infos['nbAbos2'] <= 1 ? "abonement": "abonements";
    echo    '<img src="../', ($infos['usAvecPhoto'] == 1 ? "upload/{$infos['usId']}.jpg" : 'images/anonyme.jpg'), 
            '" class="imgAuteur" alt="photo de l\'auteur">',
            em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($infos['usPseudo']).'</strong>','user', $infos['usId'], 'Voir mes infos'), 
            ' ', '<strong>', em_html_proteger_sortie($infos['usNom']), '</strong>',
            '<br>',
            em_html_a('blablas.php', "{$infos['nbBlabla']} ".$blabla, 'user', $infos['usId'], "Afficher les blablas de {$infos['usPseudo']}"), ' - ',
            em_html_a('mentions.php', "{$infos['nbMention']} ".$mention, 'user', $infos['usId'], "Afficher les mentions de {$infos['usPseudo']}"), ' - ',
            em_html_a('abonnes.php', "{$infos['nbAbos2']} ".$abonne, 'user', $infos['usId'], "Afficher les abonés de {$infos['usPseudo']}"), ' - ',
            em_html_a('abonnements.php', "{$infos['nbAbos']} ".$abonnement, 'user', $infos['usId'], "Afficher les abonements de {$infos['usPseudo']}");
}
?>
