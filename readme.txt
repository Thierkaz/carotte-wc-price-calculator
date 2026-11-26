=== Carotte WC Price Calculator ===
Contributors: carotte
Requires at least: 6.5
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPL-2.0-or-later

Calcul du prix normal des produits simples depuis les champs Carotte existants.

== Utilisation ==
Dans l'éditeur classique WooCommerce, renseigner :
- Prix rapporté à l'unité (_carotte_price_per_unit) : ex. 12,50 €/kg.
- Conditionnement (_carotte_unit) : ex. 3,6 Kg ou 250gr.
Le prix normal (_regular_price) est renseigné dès la saisie :
12,50 €/kg x 3,6 kg = 45,00 ; 12,50 €/kg x 250 g = 3,13.
Le calcul est vérifié à nouveau par le serveur lors de l'enregistrement.

Les champs sont fournis par le thème epicerie-carotte-eshop : ce plugin ne
les duplique pas et ne crée aucun champ supplémentaire.

Formats : virgule ou point décimal ; g, gr, gramme(s), gram(s), kg, kilo(s),
kilogramme(s), kilogram(s), sans distinction de casse. Espaces insécables
acceptés. Prix : nombre seul, euros, EUR, €, /kg ou par kg.
Un poids sans unité est interprété en kg. Le poids doit être strictement
positif ; un prix nul est accepté. Les valeurs négatives et les saisies
ambiguës (2 x 250 g, 3-4 kg, pièce, panier, €/g ou €/litre) sont refusées.
Aucun calcul n'est fait à la seule ouverture de la fiche. À l'enregistrement,
des valeurs valides font toujours autorité sur le prix normal manuel.
En cas de données invalides, le prix normal saisi est conservé et un message
explique l'absence de calcul. Les textes saisis dans les champs restent inchangés.

Seuls les produits simples sont traités : pas les variables, variations,
groupés ou externes. Les prix promotionnels et leurs dates restent gérés
par WooCommerce. Le plugin suit la précision monétaire et le séparateur
décimal de la boutique, sans modifier la configuration des taxes.
Pas de recalcul en masse, ni par import, REST API ou modification rapide.

== Environnement de développement et de test ==
Même organisation que carotte-wc-pickup-scheduler :
- Sources : /home/thierry/wordpress-dev/wp-content/plugins/carotte-wc-price-calculator
- Montage dédié dans /home/thierry/epicerie-carotte-wp/docker-compose.yml.
- Instance Carotte : http://localhost:8090

Depuis /home/thierry/epicerie-carotte-wp :
docker compose up -d --no-deps --force-recreate wordpress
docker compose exec -u www-data wordpress wp plugin activate carotte-wc-price-calculator

La recréation applique le montage sans supprimer les volumes de données.
Elle nécessite Docker disponible et la base de données déjà démarrée.
Sinon, démarrer d'abord la stack avec docker compose up -d --build.
Installation classique possible en copiant le dossier du plugin dans
wp-content/plugins puis en l'activant dans Extensions.

== Tests ==
Depuis le dossier du plugin :
npm ci
npm test
npm run check
npm run test:php

Sans PHP sur l'hôte, depuis /home/thierry/epicerie-carotte-wp :
docker compose exec -u www-data wordpress php /var/www/html/wp-content/plugins/carotte-wc-price-calculator/tests/run.php
docker compose exec -u www-data wordpress php -l /var/www/html/wp-content/plugins/carotte-wc-price-calculator/carotte-wc-price-calculator.php
docker compose exec -u www-data wordpress php -l /var/www/html/wp-content/plugins/carotte-wc-price-calculator/includes/class-calculator.php
docker compose exec -u www-data wordpress php -l /var/www/html/wp-content/plugins/carotte-wc-price-calculator/includes/class-plugin.php

Les tests PHP autonomes utilisent des doublures WooCommerce ; ils ne remplacent
pas la vérification dans une vraie boutique :
1. Simple : 12,50 €/kg et 3,6 Kg => prix normal 45,00.
2. Remplacer le poids par 250gr => 3,13 ; enregistrer et rouvrir.
3. Tester panier ou 2 x 250 g : prix conservé et avertissement.
4. Produit variable et variations : aucun prix modifié par cet addon.
5. Tester promotion active, future et expirée : fonctionnement natif conservé.
6. Désactiver JavaScript, enregistrer un simple : même calcul côté serveur.

== Build et distribution ==
Le dépôt Git du plugin est indépendant de wordpress-dev :
https://github.com/Thierkaz/carotte-wc-price-calculator

Après avoir committé les modifications, exécuter npm run build.
L'archive dist/carotte-wc-price-calculator-0.1.0.zip contient uniquement les
fichiers du plugin sous un dossier carotte-wc-price-calculator/.
Les tests, node_modules, scripts et fichiers GitHub sont exclus.

GitHub Actions valide PHP 8.1 à 8.4 et JavaScript, puis produit un ZIP sur main.
Un tag vX.Y.Z correspondant à la version de l'en-tête PHP publie une release
avec ce ZIP installable. Télécharger l'asset ZIP nommé comme le plugin, et
non l'archive automatique « Source code ».
Dans WordPress : Extensions > Ajouter une extension > Téléverser une extension,
sélectionner le ZIP, installer puis activer. Le thème doit fournir les champs
Carotte et WooCommerce doit être actif.

== Changelog ==
= 0.1.0 =
Première version : calcul kg/g pour produits simples.
