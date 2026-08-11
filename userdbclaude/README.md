# ECOWASmail Admin

Refonte propre et sécurisée du legacy `tableup.php` : panneau d'administration
des comptes ECOWASmail (création, mise à jour, suppression, envoi d'identifiants
et notifications par e-mail).

PHP procédural/objet léger, architecture en couches, interface HTML/CSS/JS
moderne et responsive — **sans framework**.

---

## 1. Architecture

```
public/                  ← SEUL dossier exposé au web (DocumentRoot)
  index.php              ← front controller (routage sur ?action=)
  assets/css/style.css   ← design responsive (teal ECOWAS, Lucide icons)
  assets/js/app.js       ← recherche live, tri, modale de confirmation
config/config.php        ← lit le .env et renvoie la configuration
.env                     ← secrets DB + SMTP (NON versionné, hors web)
src/
  bootstrap.php          ← session, autoload, helpers de rendu
  db.php                 ← connexion PDO (requêtes préparées)
  auth.php               ← Auth : login, rôles, gardes d'accès
  AuthController.php
  helpers.php            ← e(), parse_uname(), CSRF, flash…
  Users/UserRepository.php   ← toutes les requêtes SQL préparées
  Users/UserController.php
  Mail/Mailer.php        ← PHPMailer, 2 transports (O365 / relais interne)
  Mail/templates.php     ← gabarits HTML des e-mails
  Mail/MailController.php
templates/               ← vues (layout, login, liste, formulaire, actions)
sql/                     ← migration de la base
scripts/                 ← outils CLI (backfill hash, création admin)
legacy/                  ← ancien code, conservé pour référence
```

**Principes appliqués** (vs legacy) :

| Problème legacy | Correction |
|---|---|
| Injections SQL partout | PDO + requêtes **préparées** ; tri/recherche en liste blanche |
| Mots de passe en clair | `password_hash` (Argon2/bcrypt) ; le clair n'est jamais stocké |
| Actions destructrices en lien GET | POST + **jeton CSRF** + confirmation |
| Contrôle d'accès cassé (referer) | Vraie session authentifiée + rôles `admin`/`reader` |
| Secrets codés en dur | Tout dans `.env` |
| `parse_uname` dupliqué 6× | Une seule fonction `parse_uname()` |
| `ALTER TABLE` exposé au web | Supprimé |

---

## 2. Installation

### Pré-requis
- PHP **8.0+** (extensions `pdo_mysql`, `mbstring`)
- MySQL / MariaDB
- Composer

### Étapes

```bash
# 1. Dépendances (PHPMailer)
composer install

# 2. Configuration
cp .env.example .env
#   puis renseigner DB_*, SMTP_*, RELAY_*, ECOLINK_OFFICER

# 3. Migration de la base (existante avec données)
mysql -u userdb_user -p userdb_db < sql/01_init.sql   # ajoute pwd_hash + table admins
php scripts/backfill_hash.php                          # hashe les mots de passe existants
mysql -u userdb_user -p userdb_db < sql/02_cleanup.sql # retire pwd (clair) et status

# 4. Créer le premier administrateur du panneau
php scripts/create_admin.php paulk "MotDePasseFort" admin
```

### Test rapide en local avec SQLite (sans installer MySQL)

Pour essayer l'application immédiatement, sans serveur de base de données :

```bash
composer install
php scripts/setup_sqlite.php paulk admin123
php -S localhost:8000 -t public
```

Le script `setup_sqlite.php` :
- crée un `.env` configuré pour SQLite (s'il n'existe pas) ;
- crée la base `database/local.sqlite` avec le schéma à jour ;
- crée l'administrateur indiqué (`paulk` / `admin123` par défaut) ;
- insère quelques utilisateurs de démonstration.

Ouvre ensuite <http://localhost:8000/index.php?action=login>.

> ⚠️ SQLite est **réservé au test local**. L'envoi d'e-mails ne fonctionnera
> pas sans SMTP configuré : la création d'un utilisateur réussit, mais
> l'e-mail affichera une erreur — c'est normal en local.
> Pour repasser en production : `DB_DRIVER=mysql` dans `.env`.

### Déploiement sur serveur Plesk / Amen (SSH + Composer)

```bash
# 1. Se placer DANS le dossier du projet (et non /var/www/vhosts)
cd /var/www/vhosts/ecowas.int/userdb.ecowas.int   # adapter au chemin réel

# 2. Installer les dépendances (génère vendor/autoload.php + PHPMailer)
composer install --no-dev --optimize-autoloader

# 3. Configuration
cp .env.example .env
nano .env        # DB_DRIVER=mysql + identifiants Amen + SMTP

# 4. Migration de la base existante (table `user` avec données)
mysql -u <user> -p <base> < sql/01_init.sql
php scripts/backfill_hash.php
mysql -u <user> -p <base> < sql/02_cleanup.sql

# 5. Premier administrateur
php scripts/create_admin.php paulk "MotDePasseFort" admin
```

Dans **Plesk** : *Sites & Domaines → userdb.ecowas.int → Paramètres d'hébergement*
→ régler **« Racine du document »** sur `.../userdb.ecowas.int/public`, et la
**version PHP sur 8.0+**. Comme la racine pointe sur `public/`, les dossiers
`src/`, `config/`, `.env`, `sql/`, `scripts/` sont hors du web : non accessibles.

### Serveur web (générique)
Pointer le **DocumentRoot sur `public/`** (jamais sur la racine du projet).
En local pour tester :

```bash
php -S localhost:8000 -t public
```

---

## 3. Rôles

- **admin** : accès complet (création, édition, suppression, toutes les actions e-mail, colonnes techniques).
- **reader** : lecture seule de la liste réduite (Nom, Poste, Username).

---

## 4. Règle métier `parse_uname`

L'identifiant (`uname`) détermine l'adresse et le domaine mail :

| `uname` | Adresse (`cemail`) | Domaine |
|---|---|---|
| `prenom.nom` (sans `@`) | `prenom.nom@ecowas.int` | `ecowas.int` (Commission) |
| `prenom.nom@sigle.ecowas.int` | identique | `sigle.ecowas.int` (autre institution) |

L'URL de connexion est toujours `https://mail.<domaine>`.

### Code utilisateur (`codeUser`)
Chaque compte reçoit, **à la création**, un code unique court et non
modifiable (ex. `ECW-7F3A9C`, alphabet sans caractères ambigus). Il est
généré côté serveur (`generate_user_code()` + contrôle d'unicité en base),
affiché dans la liste et utilisable comme critère de recherche. Les comptes
existants sont dotés d'un code par `scripts/backfill_hash.php`.

---

## 5. Actions e-mail

Déclenchées depuis la liste, **avec écran de confirmation** :

| Action | Description | Mot de passe |
|---|---|---|
| Aperçu | Affiche le message sans l'envoyer | masqué |
| Renvoyer | Renvoie les identifiants | saisi par l'admin |
| Générique | Identifiants d'un compte générique | saisi par l'admin |
| Reset | Réinitialise le mot de passe | saisi + **hash mis à jour** |
| Phishing | Notification phishing + reset | saisi + **hash mis à jour** |
| Réactivation | Notifie la réactivation | aucun |
| ECOLink | Notifie l'officier SAP (si compte `sap=1`) | saisi par l'admin |

> Le mot de passe est saisi à chaque envoi car seul le **hash** est stocké
> (l'admin pose lui-même le mot de passe sur le serveur mail distant).

---

## 6. Sécurité — à faire en production
- Servir uniquement en **HTTPS** (cookies `secure`).
- Restreindre l'accès au dossier `/legacy` (ou le retirer du serveur).
- Vérifier que `.env`, `/src`, `/config`, `/sql`, `/scripts` ne sont **pas** servis par le web (ils sont hors de `public/`).
