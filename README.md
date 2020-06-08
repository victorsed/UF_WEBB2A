# VS-GAMING
Site e-commerce de vente de jeux en ligne
SEDAROS Victor-Emmanuel - LACOMBE Samy

## Installation
```
composer install
```
#### BDD
Parametrer la connexion à la BDD dans le fichier .env 
```
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```
#### Créer un compte ADMIN
```
php bin/console app:create-admin-user 'email' 'password'
```
## Built with
- Symfony 4
- Doctrine
- Twig
- Bootstrap
