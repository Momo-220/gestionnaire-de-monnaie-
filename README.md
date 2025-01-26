# Application de Gestion des Dépenses

Une application web PHP moderne pour gérer vos dépenses personnelles, utilisant Firebase pour l'authentification et le stockage des données.

## Fonctionnalités

- Authentification des utilisateurs (inscription/connexion)
- Ajout, suppression et visualisation des transactions
- Catégorisation des dépenses et revenus
- Graphiques de visualisation des dépenses
- Export des données en CSV et PDF
- Interface responsive et moderne

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Compte Firebase
- Serveur web (Apache/Nginx)
- Extensions PHP requises :
  - PDO
  - JSON
  - OpenSSL
  - Mbstring

## Installation

1. Clonez le dépôt :
```bash
git clone [URL_DU_REPO]
cd gestion-depenses
```

2. Installez les dépendances :
```bash
composer install
```

3. Copiez le fichier .env.example en .env :
```bash
cp .env.example .env
```

4. Configurez Firebase :
   - Créez un projet sur [Firebase Console](https://console.firebase.google.com)
   - Générez une nouvelle clé privée pour le service account
   - Remplissez les variables d'environnement dans le fichier .env avec vos informations Firebase

5. Configurez les permissions :
```bash
chmod -R 755 .
chmod -R 777 storage
```

## Configuration Firebase

1. Dans la console Firebase :
   - Activez Authentication avec email/password
   - Créez une base de données Firestore
   - Configurez les règles de sécurité Firestore

2. Règles de sécurité Firestore recommandées :
```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /users/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    match /transactions/{transactionId} {
      allow read, write: if request.auth != null && resource.data.user_id == request.auth.uid;
    }
  }
}
```

## Configuration du Serveur Web

### Apache

Le fichier .htaccess est déjà configuré. Assurez-vous que mod_rewrite est activé :
```bash
a2enmod rewrite
service apache2 restart
```

### Nginx

Exemple de configuration :
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /chemin/vers/votre/app;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Utilisation

1. Accédez à l'application via votre navigateur
2. Créez un compte ou connectez-vous
3. Commencez à enregistrer vos transactions

## Sécurité

- Les mots de passe sont hashés avec password_hash()
- Protection contre les injections SQL via Firebase
- Protection XSS via htmlspecialchars()
- Variables d'environnement pour les informations sensibles
- Validation des entrées utilisateur

## Maintenance

Pour mettre à jour les dépendances :
```bash
composer update
```

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub.

## Licence

MIT License 