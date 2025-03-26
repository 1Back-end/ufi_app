# UFI PAYMENT

## About

## Prérequis

-   PHP >= 8.2
-   MySql >= 8.0
-   Composer

## Install & configuration

-   Clone project: `git clone https://github.com/1Back-end/ufi_app.git`
-   Installation des dépendances: `composer install`
-   Créer le fichier **.env** et copiez y le contenu du fichier **.env.exemple**
-   generate key: `php artisan key:generate`
-   Configurer les informations de la BD
-   Lancer les migrations `php artisan migrate`
-   Aller dans le fichier **_app/Models/Trait/UpdatingUser.php_** et commenter la fonction **_bootUpdatingUser()_**
-   Exécuter les commandes:
    -   `php artisan db:seed --class=InitBDForAllDataSeeder`
    -   `php artisan db:seed --class=CountriesTableSeeder`

## Lancer

Lancer le server

`php artisan serve`

ouvrez: [http://127.0.0.1:8000](http://127.0.0.1:8000)
