# Guide des commandes

Ce guide à pour but de renseigner les futurs développeurs sur les standards de développement que nous avons utiliser pour développer ce site. Nous allons détailler par étapes comment détecter les écarts à ces standards et les corriger automatiquement. Ici nous utilisons les standards PSR-1 et PSR-12.

## 1. Installer PHP_CodeSniffer

Pour vérifier si votre code PHP respecte les règles PSR-1 et PSR-12, vous pouvez utiliser l'outil de vérification de code PHP "PHP_CodeSniffer". Vous pouvez l'installer en utilisant Composer avec la commande suivante:

```bash
# Dans le projet symfony
composer require --dev "squizlabs/php_codesniffer=*"
```

## 2. Repérer les erreurs

Une fois installé, vous pouvez utiliser la commande suivante pour si vérifier votre code respecte les règles PSR-1 et PSR-12:

```bash 
#affiche la liste des erreurs de conformité aux règles PSR-1 et PSR-12.
vendor/bin/phpcs --standard=PSR1,PSR12 -p src/ 
```

## 3. Correction automatique

Pour corriger automatiquement les erreurs de conformité à PSR-1 et PSR-12 détectées par PHP_CodeSniffer, vous pouvez utiliser "PHP_CodeSniffer Fixer". Vous pouvez lancer la commande suivante pour corriger automatiquement ces erreurs :

```bash
vendor/bin/phpcbf --standard=PSR1,PSR12 src/ 
```

> Remarque: Cette commande ne peut pas corriger toutes les erreurs: il faudra les corriger à la main. Pas d'inquiétude, la plupart du temps vous devrez réduire le nombre de caractères par lignes à 120 maximum.
