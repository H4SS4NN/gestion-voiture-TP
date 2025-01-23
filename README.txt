# Gestion Voiture

## Installation

1. Clonez le dépôt :
    ```sh
    git clone https://github.com/votre-utilisateur/gestion-voiture.git
    ```

2. Accédez au répertoire du projet :
    ```sh
    cd gestion-voiture
    ```

3. Installez les dépendances :
    ```sh
    composer install
    ```

4. Configurez les variables d'environnement :
    - Créez un fichier `.env.local` à la racine du projet.
    - Ajoutez les variables nécessaires (exemple ci-dessous) :
      ```
      DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"
      ```

5. Démarrez le serveur de développement :
    ```sh
    symfony server:start
    ```

## Utilisation

- Accédez à l'application via `http://localhost:8000` dans votre navigateur.

## Tests

Pour exécuter les tests, utilisez la commande suivante :
    ```sh
    php bin/phpunit
    ```