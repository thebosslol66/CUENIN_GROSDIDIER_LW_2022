<?php

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

tc_aff_debut('Cuiteur | Incription', '../styles/inscription.css');
tc_aff_entete("Inscription");

echo '<aside></aside>',
  '<form action="../php/inscription_3.php" method="post">',
  '<h2>Pour vous inscrire, merci de fournir les informations suivantes.</h2>',
  '<fieldset>',
      '<table>';
  tc_aff_ligne_input('Votre pseudo', '<input name="pseudo" placeholder="Minimum 4 caractères alphanumériques" autofocus="" minlength="4" class="form_txt" required="">');
        echo '<tr>',
          '<td><label for="passe1">Votre mot de passe :</label></td>',
          '<td><input class="form_txt" name="passe1" type="password" required=""></td>',
        '</tr>',
        '<tr>',
          '<td><label for="passe2">Répétez le mot de passe :</label></td>',
          '<td><input class="form_txt" name="passe2" type="password" required=""></td>',
        '</tr>',
          '<td><label for="nomprenom">Nom et prénom :</label></td>',
          '<td><input class="form_txt" name="nomprenom" required=""></td>',
        '<tr>',
          '<td><label for="email">Votre adresse email :</label></td>',
          '<td><input class="form_txt" name="email" type="email" required=""></td>',
        '</tr>',
        '<tr>',
          '<td><label for="naissance">Votre date de naissance :</label></td>',
          '<td><input name="naissance" type="date" required=""></td>',
        '</tr>',
      '</table>',
  '</fieldset>',
'<p><input type="submit" value="Soumettre"><input type="reset" value="Réinitialiser"></p>',
'</form>';

tc_aff_pied();
tc_aff_fin();

?>