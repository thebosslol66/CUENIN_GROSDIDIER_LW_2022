<?php

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

function tcl_traitement_inscription (array $tab): array {
  $sterr = [];
  $res=false;
  if (! tc_test_taille($tab['pseudo'],4,30))
  $sterr[]= 'Le pseudo doit être constitué de 4 à 30 caractères';
  if ( (! preg_match('/^[a-zA-Z0-9]*$/',$tab['pseudo'])))
  $sterr[]= 'Le pseudo ne doit contenir que des caractères alphanumériques.';
  if ((! tc_test_taille($tab['passe1'],4,20)) || (! tc_test_taille($tab['passe2'],4,20)))
    $sterr[]= 'Le mot de passe doit être constitué de 4 à 20 caractères.';
  if ($tab['passe1']!=$tab['passe2'])
    $sterr[]= 'Les mots de passe doivent être identiques.';
  if (! tc_test_taille($tab['nomprenom'],1,60))
    $sterr[]= 'Le champ nom-prenom doit être constitué de 1 à 60 caractères.';
  if ((! preg_match('/^[a-zA-Z\' -]*$/',$tab['nomprenom'])))
    $sterr[]= 'Le champ nom-prenom ne doit contenir que des caractères valides.';
  if (! filter_var($tab['email'], FILTER_VALIDATE_EMAIL))
    $sterr[]= 'L\'adresse email n\'est pas valide.';
  $a = substr($tab['naissance'],0, 4);
  $m = substr($tab['naissance'],5, 2);
  $j = substr($tab['naissance'],8, 2);
  if (! checkdate($m,$j,$a))
    $sterr[]= 'La date de naissance n\'est pas valide.';
  $now=date("Ymj");
  $age=$a.$m.$j;
  if (($now-($age)) < 180000)
    $sterr[]= 'Vous devez avoir au moins 18 ans pour vous inscrire.';
  if (($now-($age)) > 1200000)
    $sterr[]= 'Le site est interdit aux personnes fortement agées.';
  
  if (! $sterr){
    $bd = tc_bd_connect();
    $sql = 'SELECT * FROM users ORDER BY usID';
    $res = tc_bd_send_request($bd, $sql);
    while ($t = mysqli_fetch_assoc($res)) {
    if ($tab['pseudo']==$t['usPseudo'])
      $sterr[]= 'Le pseudo est déjà utilisé.';
    }
    mysqli_free_result($res);
    if ($sterr)
      mysqli_close($bd);
  }
  if ($sterr)
    echo '<ul>';
  else {
    $sql = 'INSERT INTO users (`usID`, `usNom`, `usVille`, `usWeb`, `usMail`, `usPseudo`, `usPasse`, `usBio`, `usDateNaissance`, `usDateInscription`, `usAvecPhoto`)
    VALUES (NULL, \''.$_POST['nomprenom'].'\', \'\', \'\', \''.$_POST['email'].'\', \''.$_POST['pseudo'].'\', \''.password_hash($_POST['passe1'],PASSWORD_DEFAULT).'\', \'\', \''.$age.'\', \''.$now.'\',0)';
    if (tc_bd_send_request($bd, $sql)) {
      header('Location: protegee.php');
    } else {
      $sterr[]= 'Erreur au niveau de la base de données';
    }
  }
  return $sterr;
}

function tcl_aff_formulaire (array $tab): void {
  if (isset($_POST['submit'])) {
    $err = tcl_traitement_inscription($tab);
  }
  tc_aff_debut('Cuiteur | Incription', '../styles/inscription.css');
tc_aff_entete("Inscription");

echo '<aside></aside>';
if (isset($err)) {
  echo '<ul>';
  foreach ($err as $value)
    echo '<li>',$value,'</li>';
  echo '</ul>';
}

echo '<form action="../php/inscription_4.php" method="post">',
'<table>',
'<h2>Pour vous inscrire, merci de fournir les informations suivantes.</h2>',
'<fieldset>',
'<table>',
'<td>',
'<label for="pseudo">Votre pseudo :</label><br>',
'<label for="passe1">Votre mot de passe :</label><br>',
'<label for="passe2">Répétez le mot de passe :</label><br>',
'<label for="nomprenom">Nom et prénom :</label><br>',
'<label for="email">Votre adresse email :</label><br>',
'<label for="naissance">Votre date de naissance :</label><br>',
'</td>',
'<td>',
'<input name="pseudo" type="text" placeholder="Minimum 4 caractères alphanumériques" autofocus="" minlength="4" maxlength="30"';
if (isset($_POST['pseudo']))
  echo ' value="',$_POST['pseudo'],'"';
echo 'required=""><br>',
'<input name="passe1" type="password" required=""><br>',
'<input name="passe2" type="password" required=""><br>',
'<input name="nomprenom" type="text"';
if (isset($_POST['nomprenom']))
  echo ' value="',$_POST['nomprenom'],'"';
 echo 'required=""><br>',
'<input name="email" type="email"';
if (isset($_POST['email']))
  echo ' value="',$_POST['email'],'"';
  echo 'required=""><br>',
'<input name="naissance" type="date"';
if (isset($_POST['naissance']))
  echo ' value="',$_POST['naissance'],'"';
  echo 'required=""><br>',
'</td>',
'</table>',
'</fieldset>',
'<p><input type="submit" name="submit" value="S\'inscrire"><input type="reset" value="Réinitialiser"></p>',
'</form>';

tc_aff_pied();
tc_aff_fin();
}

tcl_aff_formulaire($_POST);

?>