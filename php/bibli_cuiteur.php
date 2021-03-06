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
/*define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuiteur_bd');
define('BD_USER', 'cuiteur_userl');
define('BD_PASS', 'cuiteur_passl');
*/
define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuenin_cuiteur');
define('BD_USER', 'cuenin_u');
define('BD_PASS', 'cuenin_p');
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

define('LMAX_MESSAGE', 255);

define('CUIT_PER_REQUEST', 4);

define('NB_SUGGESTIONS', 5);
define('NB_MAX_ABO_REQUEST', 10);

define('NB_AFFICHAGE_TENDANCES', 4);
define('NB_AFFICHAGE_SUGGESTIONS', 2);

//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 */
function em_aff_entete(?string $titre = null, bool $link=true, string $mention=""):void{
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
                    '<textarea name="txtMessage">', $mention, '</textarea>',
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
    $abonne = $t['nbAbos'] <= 1 ? "abonné": "abonnés";
    $abonnement = $t['nbAbos2'] <= 1 ? "abonement": "abonements";

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
                '<li>', em_html_a('blablas.php', "{$t['nbBlabla']} ".$blabla, 'user', $t['usId'], "Afficher les {$blabla} de {$t['usPseudo']}"), '</li>',
                '<li>', em_html_a('abonnements.php', "{$t['nbAbos']} ".$abonnement, 'user', $t['usId'], "Afficher les {$abonnement} de {$t['usPseudo']}"),'</li>',  
                '<li>', em_html_a('abonnes.php', "{$t['nbAbos2']} ".$abonne, 'user', $t['usId'], "Afficher les {$abonne} de {$t['usPseudo']}"),'</li>',  
            '</ul>';
            // libération des ressources
            mysqli_free_result($res);
            $sql = "SELECT taID, COUNT(*) AS NB
            FROM tags JOIN blablas ON taIDBlabla = blID
            GROUP BY taID
            ORDER BY NB DESC
            LIMIT 0,".NB_AFFICHAGE_TENDANCES;
            $res = em_bd_send_request($bd, $sql);
            echo '<h3>Tendances</h3>',
            '<ul>';
            while ($t = mysqli_fetch_assoc($res)) {
                echo '<li>#<a href="./tendances.php?tag=', urlencode($t['taID']), '" title="Voir les blablas contenant ce tag">', $t['taID'], '</a></li>';
            }
            echo    '<li><a href="./tendances.php">Toutes les tendances</a><li>',
            '</ul>';
            mysqli_free_result($res);
            echo '<h3>Suggestions</h3>',             
            '<ul>';
            $final_sugestion = tcag_get_sugestions($bd, NB_AFFICHAGE_SUGGESTIONS, NB_MAX_ABO_REQUEST);
            $res = tcag_get_user_infos_prep_req($final_sugestion);
            if ($res){
                $all_match = tcag_get_user_infos_send_req($bd, $res);
                foreach($all_match as $suggestion){
                    echo '<li>',
                            '<img src="../', ($suggestion['usAvecPhoto'] == 1 ? "upload/{$suggestion['usId']}.jpg" : 'images/anonyme.jpg'), 
                            '" alt="photo de l\'auteur">',
                            em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($suggestion['usPseudo']).'</strong>','user', $suggestion['usId'], "Voir les infos de {$suggestion['usPseudo']}"), 
                            ' ', '<strong>', em_html_proteger_sortie($suggestion['usNom']), '</strong>',
                        '</li>';
                }
            } else {
                echo '<li>Nous n\'avons pas de suggestions pour le moment</li>';
            }
            echo    '<li><a href="./suggestions.php">Plus de suggestions</a></li>',
                '</ul>';
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
                    em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($pseudo_orig).'</strong>','user', $id_orig, 'Voir les infos de '.em_html_proteger_sortie($pseudo_orig)), 
                    ' ', em_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .em_html_a( 'utilisateur.php','<strong>'.em_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'user', $t['autID'], 'Voir les infos de '.em_html_proteger_sortie($t['autPseudo'])) : ''),
                    '<br>';

                    $output = htmlspecialchars($t['blTexte'], ENT_NOQUOTES, "UTF-8");
                    $output = tcag_active_mention_and_tags($output);
                    echo $output;

                    echo '<p class="finMessage">',
                    em_amj_clair($t['blDate']), ' à ', em_heure_clair($t['blHeure']);
                    if ($t['autID'] == $_SESSION['usID']) {
                        echo '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'-del\').submit();">Supprimer</a>';
                    } else  {
                        echo 
                    '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'-rep\').submit();">Répondre</a>',
                    '<a href="#" onclick="document.getElementById(\'cuit-'.$t['blID'].'-rec\').submit();">Recuiter</a>';
                    }
                    echo 
                    '<form action="cuiteur.php" method="POST" id="cuit-',  $t['blID'], '-del">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="delete">',
                    '</form>',
                    '<form action="cuiteur.php" method="POST" id="cuit-',  $t['blID'], '-rep">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="response">',
                        '<input type="hidden" id="authorName" name="authorName" value="', $pseudo_orig, '">',
                    '</form>',
                    '<form action="cuiteur.php" method="POST" id="cuit-',  $t['blID'], '-rec">',
                        '<input type="hidden" id="blablaId" name="blablaId" value="', $t['blID'], '">',
                        '<input type="hidden" id="blaction" name="blaction" value="recuit">',
                    '</form>',
                '</li>';
        $compteur++;
    }
    if ($compteur < mysqli_num_rows($r)){
        if (is_numeric($_GET['page'])){
        echo    '<li class="plusBlablas">',
                    '<a href="?page=', $_GET['page']+1;
        }
        else {
            echo    '<li class="plusBlablas">',
                    '<a href="?page=', 1;
        }
        foreach ($_GET as $key => $value){
            if ($key !== "page"){
                echo "&$key=$value";
            }
        }
        echo         '"><strong>Plus de blablas</strong></a>',
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


//_______________________________________________________________
/**
 * Récupère toutes les mentions d'utilisateus d'un blaba
 * 
 * @param string $text texte du blaba
 * @return array Tableau contenant les mentions d'utilisateurs
 */
function get_users_mentionned(string $text): array {
    preg_match_all('/@([a-zA-Z0-9]+)/',$text,$matches, PREG_SET_ORDER, 0);
    return array_column($matches, 1);
}

/**
 * Récupère toutes les tags d'un blaba
 * 
 * @param string $text texte du blaba
 * @return array Tableau contenant les tags
 */
function get_tags_mentionned(string $text): array {
    preg_match_all('/#([a-zA-Z0-9éâîôùèçàïû]+)/',$text,$matches, PREG_SET_ORDER, 0);
    return array_column($matches, 1);
}


/**
 * Ajoute les liens vers les mentions d'utilisateurs et les tags dans le texte
 * 
 * @param string $cuit texte du blaba
 * @return string Texte du blaba avec les liens
 */
function tcag_active_mention_and_tags(string $cuit): string {
    $t = preg_replace_callback('/(?<=([^&])|^)#([a-zA-Z0-9éâîôùèçàïû]+)/m', 
        function ($matches){
            return '<a class=\"tag\" href="tendances.php?tag='.urlencode($matches[2]).'">#'.$matches[2].'</a>';},
        $cuit);
    $t = preg_replace_callback('/(?<=([^&])|^)@([a-zA-Z0-9]+)/', 
        function ($matches){
            return '<a class="peopleCite" href="utilisateur.php?pseudo='.urlencode($matches[2]).'">@'.$matches[2].'</a>';},
        $t);
    return $t;
}

//_______________________________________________________________
/**
 * Prépare une requetes SQL pour récuperer les informations de tous les utilisateurs listées dans $usIdArray
 * Elle permet de récuperer d'uncoup les informations d'un tableau d'id d'utilisateurs
 * 
 * @param array $usIdArray tableau contenant les indentifiants des utilisateurs dont on veux recuperer les informations
 */
function tcag_get_user_infos_prep_req(array $usIdArray): string {
    $sql = "";
    $c = 0;
    foreach ($usIdArray as $usId){
        if ($c != 0){
            $sql .= " UNION ";
        }
        $sql .= "(SELECT 
            usId,
            usPseudo,
            usAvecPhoto,
            usNom,
            (SELECT COUNT(blid) FROM blablas WHERE blIDAuteur = {$usId}) AS nbBlabla,
            (SELECT COUNT(*) from mentions WHERE meIDUser = {$usId}) AS nbMention,
            (SELECT COUNT(eaIDUser) from estabonne WHERE eaIDAbonne = {$usId}) AS nbAbos,
            (SELECT COUNT(eaIDAbonne) from estabonne WHERE eaIDUser = {$usId}) AS nbAbos2,
            (SELECT COUNT(*) from estabonne WHERE eaIDUser = {$_SESSION['usID']} AND eaIDAbonne = {$usId}) AS isAbo
        FROM users
        WHERE usID = {$usId})";
        $c = $c +1;
    }
    return $sql;
}

//_______________________________________________________________
/**
 * Envoye une requete pour récupérer les infos d'une liste d'utilisateur précédament créé par la fonction tcag_get_user_infos_prep_req
 * 
 * @param mysqli $bd Objet de connexion à la base de données
 * @param string $sql Requete SQL
 * @return array Tableau associatif contenant les infos des utilisateurs ou false si une érreur est survenues
 */
function tcag_get_user_infos_send_req(mysqli $bd, string $sql): false | array {
    if (!empty($sql)){
        $info_user_search = em_bd_send_request($bd, $sql);
        if (mysqli_num_rows($info_user_search) > 0){
            $res = [];
            while (($t = mysqli_fetch_assoc($info_user_search))){
                $res[] = $t;
            }
            mysqli_free_result($info_user_search);
            return $res;
        }
    }
    return false;
}

//_______________________________________________________________
/**
 * Affiche toutes les informations d'un utilisateur
 * Le nombre de blablas, de mentions, d'abonnés, d'abonnements, pseudo et nom
 * 
 * @param array $infos Les informations de l'utilisateur a afficher
 */
function tcag_aff_user_infos(array $infos): void {
    $blabla = $infos['nbBlabla'] <= 1 ? "blabla": "blablas";
    $mention = $infos['nbMention'] <= 1 ? "mention": "mentions";
    $abonne = $infos['nbAbos'] <= 1 ? "abonné": "abonnés";
    $abonnement = $infos['nbAbos2'] <= 1 ? "abonement": "abonements";
    echo    '<img src="../', ($infos['usAvecPhoto'] == 1 ? "upload/{$infos['usId']}.jpg" : 'images/anonyme.jpg'), 
            '" class="imgAuteur" alt="photo de l\'auteur">',
            em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($infos['usPseudo']).'</strong>','user', $infos['usId'], "Voir les infos de {$infos['usPseudo']}"), 
            ' ', '<strong>', em_html_proteger_sortie($infos['usNom']), '</strong>',
            '<br>',
            em_html_a('blablas.php', "{$infos['nbBlabla']} ".$blabla, 'user', $infos['usId'], "Afficher les blablas de {$infos['usPseudo']}"), ' - ',
            em_html_a('mentions.php', "{$infos['nbMention']} ".$mention, 'user', $infos['usId'], "Afficher les mentions de {$infos['usPseudo']}"), ' - ',
            em_html_a('abonnes.php', "{$infos['nbAbos']} ".$abonne, 'user', $infos['usId'], "Afficher les abonnes de {$infos['usPseudo']}"), ' - ',
            em_html_a('abonnements.php', "{$infos['nbAbos2']} ".$abonnement, 'user', $infos['usId'], "Afficher les abonnements de {$infos['usPseudo']}");
}

//_______________________________________________________________
/**
 * Affiche la checkbox pour s'abonner à un utilisateur
 * @param array $us_infos les information de l'utilisateur a afficher
 */
function tcag_aff_user_infos_with_abo_button(array $us_infos): void {
    if ($_SESSION['usID'] != $us_infos['usId']){
        echo '<p class="abonement-checkbox">';
        if ($us_infos['isAbo'] == 0){
            echo    '<input type="checkbox" title="S\'abonner a '.$us_infos['usPseudo'].'" value="" id="abonner-'.$us_infos['usId'].'" name="abonner-'.$us_infos['usId'].'">',
                    '<label for="abonner-'.$us_infos['usId'].'">S\'abonner</label>';
        } else {
            echo    '<input type="checkbox" title="Se désabonner de '.$us_infos['usPseudo'].'" value="Se désabonner" id="desabonner-'.$us_infos['usId'].'" name="desabonner-'.$us_infos['usId'].'">',
                    '<label for="desabonner-'.$us_infos['usId'].'">Se désabonner</label>';
        }
        echo '</p>';
    }
}

//_______________________________________________________________
/**
 * Gère l'affichage de la liste des utilisateurs avec leur infos
 * Ajoute le bouton s'abonner ou se desabonner sur les utilisateurs différents de l'utilisateur courrant
 * Ajoute le formulaire permettant l'abonnement ou le désabonnement des utilisateurs sélectionnées
 * 
 * @param array $all_match Tableau contenant les informations des utilisateurs à afficher
 * @param int $userId ID de l'utilisateur afficher par la page (pour ne pas afficher le formulaire si on se trouve sur la page abonnement de l'utilisateur et qu'il n'en as aucun)
 */
function tcag_aff_result_list_users(array $all_match, int $userId): void {
    $number_result = count($all_match);
    echo '<ul>';
    for ($i = 0; $i < $number_result; $i++){
        echo '<li>';
        if ($all_match[$i]['usId'] == $_SESSION['usID']){
            echo '<div class="user-infos">';
                    tcag_aff_user_infos($all_match[$i]);
            echo '</div>';
        }
        else {
            tcag_aff_user_infos($all_match[$i]);
            tcag_aff_user_infos_with_abo_button($all_match[$i]);
        }
        
        echo '</li>';
    }
    echo '</ul>';
    if ($number_result > 0 || $_SESSION['usID'] != $userId){
        echo '<table>',
                '<tr>',
                    '<td>',
                    '<input type="submit" name="btnAbonner" id="btnAbonner" value="Valider" title="S\'abonner ou se désabonner des personnes sélectionnées">',
                    '</td>',
                '</tr>',
            '</table>';
    }
    echo    '</form>';
}

//_______________________________________________________________
/**
 * Gére les abonement et les désabonements sur les affichages des listes d'utilisateurs
 * 
 * Ajoute ou supprime des abonnements lorsqu'on clique sur les checkbox des utilisateurs
 * Utilisées sur les pages Recherche d'utilisateurs, abonnements, abonnés et sugestions
 * 
 * @param mysqli $bd Objet de connexion à la base de données
 */
function tcag_catch_result_list_users_responce(mysqli $bd): void {
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
                $sql = "DELETE FROM `estabonne` WHERE (eaIDAbonne = {$array_to_desabonner[$i]} AND eaIDUser = {$_SESSION['usID']})";
            } else {
                $sql .= " OR ( eaIDAbonne = {$array_to_desabonner[$i]} AND eaIDUser = {$_SESSION['usID']})";
            }
        }
        if ($sql){
            em_bd_send_request($bd, $sql);
        }
        $sql = "";
        $date_abonnement = date('Ymd');
        for ($i = 0; $i < $array_to_abonner_counter; $i++){
            if ($i == 0){
                $sql = "INSERT INTO `estabonne`(`eaIDUser`, `eaIDAbonne`, `eaDate`) VALUES ('{$_SESSION['usID']}', '{$array_to_abonner[$i]}', '$date_abonnement')";
            } else {
                $sql .= ", ('{$_SESSION['usID']}', '{$array_to_abonner[$i]}', '$date_abonnement')";
            }
        }
        if ($sql){
            em_bd_send_request($bd, $sql);
        }
        header('Location: ./cuiteur.php');
    }
}

//_______________________________________________________________
/** 
 * Retourne les id des utilisateurs suggeré a l'utilisateur courrant
 * 
 * 1 étape:
 *  - Récupérer les id des utilisateurs suivies par les personnes suivies par l'utilisateur courrant et dont il ne suit pas
 *  - Si il y a plus de suggestions que $nb_max_suggestions alors on retourne 5 utilisateurs au hazard
 * 2 étape:
 *  - Si il y a moins de suggestions que $nb_max_suggestions alors on prend les $nb_max_max_abos utilisateurs qui possèdent le plus d'abonnées
 * 3 étape:
 *  - On ajoute au hazard les utilisarteur auquel il n'est pas abonnée
 * 
 * On retourne ensuite la liste des utilisateurs suggérés.
 * 
 * @param mysqli $bd Object de connexion à la base de données
 * @param int $nb_max_suggestions Nombre maximum de suggestions
 * @param int $nb_max_max_abos Nombre maximum d'utilisateurs à prendre avec le maximum d'abonnées pour ajouter au suggestions
 * @return array Liste des id des utilisateurs suggérés
 */
function tcag_get_sugestions(mysqli $bd, int $nb_max_suggestions, int $nb_max_max_abos): false | array {
    $sql = "SELECT DISTINCT usID, usPseudo
            FROM users INNER JOIN estabonne ON usID=eaIDAbonne
            WHERE eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser={$_SESSION['usID']})
            AND usID!={$_SESSION['usID']}
            AND usID NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser={$_SESSION['usID']})
            LIMIT {$nb_max_suggestions}";


    $result = em_bd_send_request($bd, $sql);

    $suggested_result = [];
    $final_sugestion = [];

    while (($t = mysqli_fetch_assoc($result))){
        $suggested_result[] = $t['usID'];
    }
    mysqli_free_result($result);

    if (count($suggested_result) < $nb_max_suggestions){
        $final_sugestion = $suggested_result;
        $probable_sugested = [];

        $sql = "SELECT usID, COUNT(usID), dejaAbonne.eaIDUser 
                FROM (users INNER JOIN estabonne ON usID=eaIDUser) 
                    LEFT OUTER JOIN estabonne AS dejaAbonne ON usID=dejaAbonne.eaIDAbonne AND dejaAbonne.eaIDUser={$_SESSION['usID']}
                GROUP BY usID ORDER BY COUNT(usID) DESC LIMIT {$nb_max_max_abos}";

        $result = em_bd_send_request($bd, $sql);

        while (($t = mysqli_fetch_assoc($result))){
            if ($t['eaIDUser'] == false && !in_array($t['usID'], $final_sugestion )){
                $probable_sugested[] = $t['usID'];
            }
        }

        mysqli_free_result($result);

        while (count($final_sugestion) < $nb_max_suggestions && count($probable_sugested) > 0){
            $t = rand(0, count($probable_sugested)-1);
            $final_sugestion[] = $probable_sugested[$t];
            array_splice($probable_sugested, $t, 1); 
        }
    }
    else{
        while (count($final_sugestion) < $nb_max_suggestions && count($suggested_result) > 0){
            $t = rand(0, count($suggested_result)-1);
            $final_sugestion[] = $suggested_result[$t];
            array_splice($suggested_result, $t, 1); 
        }
    }
    return $final_sugestion;
}

//_______________________________________________________________
/**
 * Retourne l'id de l'utilisateur saisie dans l'url sous forme de pseudo ou d'id
 * 
 * @param mysqli $bd Object de connexion à la base de données
 * @return int|false Id de l'utilisateur ou false si l'utilisateur n'existe pas
 */
function tcag_get_user_id_from_url(mysqli $bd): false | int {
    if (!empty($_GET["user"]) && is_numeric($_GET["user"]))
        $idUser = $_GET["user"];
    elseif(!empty($_GET["pseudo"])){
        $idUser = $_GET["pseudo"];
        $sql = 'SELECT usId FROM `users` WHERE `usPseudo` = "'. em_bd_proteger_entree($bd, $_GET["pseudo"]) .'"';
        $res = em_bd_send_request($bd, $sql);
        if (mysqli_num_rows($res) == 1){
            $idUser = mysqli_fetch_assoc($res)['usId'];
        }
        else {
            $idUser = false;
        }
        mysqli_free_result($res);
    }
    else
        $idUser = false;
    return $idUser;
}

//_______________________________________________________________
/**
 * Affiche unez page d'érreur disant que l'utilisateur n'a pas été trouvé
 * 
 * @param mysqli $bd Object de connexion à la base de données
 */
function tcag_page_user_not_found(mysqli $bd): void {
    ob_end_clean();
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

//_______________________________________________________________
/**
 * Permet de récuperer l'id de l'utilisateur saisi deans l'url sous forme de pseudo ou d'id
 * Et retourne l'id de l'utilisateur ainsi que ses informations dans un tableau
 * 
 * @param mysqli $bd Object de connexion à la base de données
 * @return array|false Tableau contenant l'id de l'utilisateur dans la case 0 et ses informations dans la case 1 sinon affiche une page disant que l'utilisateur n'éxiste pas
 */
function tcag_get_user_info_or_not_found_user_page(mysqli $bd): ?array {
    $idUser = tcag_get_user_id_from_url($bd);

    if ($idUser === false){
        tcag_page_user_not_found($bd);
    }

    $page_user_info = tcag_get_user_infos_send_req($bd, tcag_get_user_infos_prep_req([$idUser]))[0];

    if (empty($page_user_info)){
        tcag_page_user_not_found($bd);
    }
    return [$idUser, $page_user_info];
}
?>
