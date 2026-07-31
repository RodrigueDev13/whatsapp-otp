# WhatsApp OTP — auto-hébergé (Laravel + sidecar Node)

Service auto-hébergé pour envoyer des codes OTP par WhatsApp, sans passer par
l'API officielle Meta Business (pas de compte Business à valider, pas de
template à faire approuver).

**Important — lisez avant de mettre en production.** Ce projet parle à
WhatsApp via `whatsapp-web.js`, une librairie **non officielle** qui simule
un client WhatsApp Web. Ce n'est pas l'API officielle Meta Cloud API :
- Risque de ban du numéro non nul, quelle que soit la qualité du code.
- Pas de garantie de délivrance contractuelle.
- Nécessite de garder une session active en permanence (navigateur headless),
  et de rescanner un QR code si la session se déconnecte.
- N'utilisez jamais votre numéro WhatsApp personnel ou professionnel
  principal — dédiez un numéro à cet usage, avec une "montée en confiance"
  progressive (échangez quelques messages avant d'envoyer des OTP en volume).
- Pour un usage critique/réglementé, préférez un canal de secours
  (SMS/email) ou l'API officielle Meta Cloud API.

## Architecture

```
Internet ──HTTPS──▶  Laravel (app/)              seul composant exposé publiquement
                          │  appels HTTP internes, jamais exposés à l'extérieur
                          ▼
                      Sidecar Node.js (engine/)    pilote whatsapp-web.js (Puppeteer/Chromium)
                          │
                          ▼
                      WhatsApp Web (non officiel)
```

- `engine/` : micro-service Node.js/Express qui pilote une session WhatsApp
  Web via `whatsapp-web.js`. Expose une API interne (`/status`, `/qr`,
  `/send`, `/disconnect`) protégée par un secret partagé — **ne l'exposez
  jamais sur Internet**.
- `app/` : application Laravel qui porte tout le reste — dashboard
  (statut/QR code), gestion des clés API, endpoint transactionnel
  `POST /api/otp/send`.

Le dashboard permet de **déconnecter le numéro lié et en lier un autre** :
bouton "Déconnecter & changer de compte" (visible une fois la session
`READY`), qui délie le compte côté WhatsApp (comme depuis "Appareils liés"
sur le téléphone) puis relance automatiquement un nouveau QR code à
scanner.

## Démarrage rapide en dev (sans Docker, deux terminaux)

Prérequis : PHP ≥ 8.3, Composer, Node ≥ 20.

```bash
# 1. Sidecar WhatsApp
cd engine
npm install
cp .env.example .env      # ajustez ENGINE_INTERNAL_SECRET
npm start                 # laisse tourner ce terminal

# 2. Laravel (dans un second terminal)
cd app
composer install
cp .env.example .env
php artisan key:generate
# Vérifiez que WA_ENGINE_URL et ENGINE_INTERNAL_SECRET correspondent
# exactement à ceux utilisés par engine/.env, sinon Laravel ne pourra
# pas authentifier ses appels vers le sidecar.
php artisan migrate --seed   # crée le compte admin (ADMIN_EMAIL/ADMIN_PASSWORD dans .env)
php artisan serve
```

Ouvrez `http://localhost:8000`, connectez-vous avec le compte admin seedé.

## Usage permanent sur ce PC (Windows + WAMP, sans Docker)

Pour ne pas dépendre d'un terminal `php artisan serve` ouvert en permanence,
Laravel est servi par l'Apache de WAMP (qui tourne déjà comme service Windows,
donc toujours actif), et le sidecar Node tourne via un script à boucle de
redémarrage automatique.

**1. Laravel via WAMP** — un vhost dédié a été ajouté dans
`C:\wamp64\bin\apache\apache2.4.65\conf\extra\httpd-vhosts.conf` (port 8090,
accès restreint à la machine locale avec `Require local`) :

```apache
Listen 8090
<VirtualHost *:8090>
  ServerName whatsapp-otp.local
  DocumentRoot "C:/Users/DELL/StudioProjects/whatsapp-otp/app/public"
  <Directory "C:/Users/DELL/StudioProjects/whatsapp-otp/app/public/">
    Options +Indexes +FollowSymLinks
    AllowOverride All
    Require local
  </Directory>
</VirtualHost>
```

Après toute modification de ce fichier, redémarrez Apache via l'icône WAMP
(barre des tâches → clic gauche → "Redémarrer tous les services") — cette
étape nécessite les droits admin que WAMP demande lui-même au démarrage,
un terminal normal ne peut pas redémarrer le service. L'app est ensuite
accessible en permanence sur **http://localhost:8090**, sans rien lancer.

**2. Sidecar WhatsApp via `engine\start.bat`** — double-cliquez sur
`engine\start.bat` (ou lancez-le depuis un terminal). Il relance
automatiquement `node src\server.js` s'il plante, avec un court délai entre
tentatives. Fermez la fenêtre (ou Ctrl+C) pour l'arrêter.

Un raccourci vers ce script a été ajouté dans le dossier Démarrage de Windows
(`%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup`), donc le sidecar
démarre automatiquement à l'ouverture de session. Pour désactiver ce
démarrage auto, supprimez simplement le raccourci
`whatsapp-otp-engine.lnk` de ce dossier.

**⚠️ Piège WAMP : deux versions de PHP différentes.** Sur cette machine, WAMP a
plusieurs versions de PHP installées, et **la version utilisée par Apache
(`C:\wamp64\bin\php\php8.3.28`) n'est pas forcément celle utilisée par la
commande `php` en ligne de commande** (ici, `php` en CLI pointe vers 8.4.15 —
vérifiable avec `php -v`). Si vous relancez `composer install`/`composer
update` avec la commande `composer`/`php` par défaut, Composer peut résoudre
des dépendances qui exigent une version de PHP plus récente que celle
qu'Apache charge réellement — l'app plante alors avec une erreur `Composer
detected issues in your platform`. Pour éviter ça, **utilisez toujours le PHP
d'Apache explicitement** pour les commandes touchant à ce projet :

```bash
"C:\wamp64\bin\php\php8.3.28\php.exe" "C:\composer\composer.phar" install
"C:\wamp64\bin\php\php8.3.28\php.exe" artisan migrate
```

Si le vhost renvoie une erreur 500 après une modification, vérifiez d'abord
`C:\wamp64\logs\php_error.log` — c'est la source la plus fiable (l'affichage
d'erreur dans le navigateur peut être vide ou trompeur selon le contexte).

## Envoyer un OTP

```bash
curl -X POST http://localhost:8090/api/otp/send \
  -H "X-Api-Key: <votre clé>" \
  -H "Content-Type: application/json" \
  -d '{"phone": "33612345678", "message": "Votre code est 123456"}'
```

(Remplacez `8090` par `8000` si vous utilisez `php artisan serve` en dev au
lieu du vhost WAMP.)

Réponses possibles :
- `200 { "success": true, "id": "..." }`
- `401` clé API manquante ou invalide
- `422` numéro/message invalide
- `503` session WhatsApp non connectée (retournez sur le dashboard, un QR
  peut être en attente)

## Alternative : déploiement sur un VPS / conteneur (Proxmox, etc.) avec Docker

Optionnel — utile si vous déployez un jour sur un serveur distant exposé sur
Internet plutôt que sur ce PC. Aucune dépendance à Docker n'est nécessaire
pour l'usage local décrit ci-dessus.

Ce déploiement inclut un service `caddy` qui termine le TLS : il obtient et
renouvelle automatiquement un certificat Let's Encrypt pour le domaine que
vous indiquez, puis fait reverse-proxy vers `app`. **Prérequis avant de
lancer** : un nom de domaine dont l'enregistrement DNS A pointe vers l'IP
publique de l'hôte, et les ports **80 et 443 accessibles depuis Internet**
(port forwarding sur la box/le pare-feu vers ce conteneur/VM si nécessaire —
Caddy en a besoin pour la validation ACME du certificat).

```bash
cp .env.example .env
# renseignez DOMAIN, APP_KEY (générez-la avec la commande ci-dessous),
# ENGINE_INTERNAL_SECRET, ADMIN_EMAIL, ADMIN_PASSWORD
docker compose run --rm app php artisan key:generate --show   # copiez la valeur dans .env

docker compose up -d --build
```

Ouvrez `https://<DOMAIN>` — Caddy sert automatiquement en HTTPS dès que le
domaine résout vers l'hôte.

- Seul le service `caddy` publie des ports sur l'hôte (80/443). `app` n'est
  joignable que par `caddy` sur le réseau Docker privé `web`, et `engine`
  reste strictement interne au réseau `internal` (`http://engine:3001`) —
  ni l'un ni l'autre n'est jamais exposé directement à l'extérieur.
- Les données de session WhatsApp (`.wwebjs_auth`), la base SQLite et les
  certificats TLS de Caddy sont persistés dans des volumes Docker nommés —
  ils survivent aux redéploiements (`docker compose up --build` ne force ni
  un nouveau scan de QR code, ni un nouveau certificat).
- Vous avez déjà un reverse proxy sur cet hôte pour d'autres services (Nginx
  Proxy Manager, Traefik...) ? Retirez le service `caddy` de
  `docker-compose.yml`, republiez le port de `app` (`ports: ["8000:8000"]`)
  et laissez votre proxy existant gérer le TLS à sa place — `app` reste
  configuré pour faire confiance aux headers `X-Forwarded-*` d'un proxy
  amont (voir `trustProxies` dans `app/bootstrap/app.php`).
- `php artisan serve` (utilisé dans le conteneur `app`) convient pour le
  faible trafic attendu d'un endpoint OTP transactionnel ; passez à
  PHP-FPM + Nginx si vous avez besoin de plus de débit.

## Sécurité des images Docker

Un scan de vulnérabilités (Docker Scout / Trivy) a été passé sur les trois
images (`app`, `engine`, `caddy`). Résumé et actions prises :

- **Dépendances applicatives (notre code) : clean.** `composer audit` (app)
  et `npm audit` (engine) ne remontent aucune vulnérabilité. La seule trouvée
  à l'époque du scan (`brace-expansion`, DoS, dépendance transitive de
  `whatsapp-web.js` → `archiver`, chemin de code non utilisé par ce projet)
  a été corrigée via un `overrides` dans `engine/package.json` plutôt que de
  downgrader `whatsapp-web.js` (ce que proposait `npm audit fix --force`,
  au prix d'un retour à une version ancienne et risquée) — vérifié dans
  l'image construite : une seule résolution, patchée.
- **Le gros du volume de CVE restant vient des images de base**
  (paquets Debian/Alpine de `php:8.3-cli-bookworm`, `node:20-bookworm-slim`,
  `caddy:2-alpine` : `chromium`, `perl`, `libxml2`, `zlib1g`, `curl`,
  `linux-libc-dev`...), pas de notre code. La quasi-totalité n'a pas encore
  de correctif publié par Debian/Alpine (`Fixed Version: none`) — rien à
  faire de plus tant que ça n'est pas backporté en amont.
- **Faux positifs "Node.js" à ignorer** : Trivy remonte aussi
  `brace-expansion`/`tar`/`glob`/`minimatch`/`sigstore`/`cross-spawn` sous un
  chemin `usr/local/lib/node_modules/npm/...` — ce sont les dépendances
  internes du **CLI `npm` lui-même**, embarquées dans l'image officielle
  `node`, jamais exécutées au runtime du conteneur (npm ne sert qu'au
  moment du build). Risque réel nul ici, mais elles resteront visibles dans
  un scan tant que l'image `node` upstream ne les met pas à jour.
- **Ce qui a été fait concrètement** : `docker pull` des images de base
  (`php`, `node`, `composer`, `caddy`) pour récupérer les derniers correctifs
  disponibles, puis rebuild complet (`docker compose build --no-cache`).

**À refaire périodiquement** : ces CVE d'image de base ne se corrigent
jamais en une fois — `docker pull` des images de base + rebuild
(`docker compose build --pull`) de temps en temps est le geste qui compte,
plutôt que de viser un état "zéro CVE" stable (illusoire avec Chromium
embarqué).

## Tests

```bash
cd app
php artisan test
```

## Limites connues du périmètre actuel

- Une seule session/numéro WhatsApp à la fois (pas de multi-numéro).
- Pas de file d'attente : `POST /send` du sidecar est synchrone — suffisant
  pour un OTP mais pas pour de gros volumes.
- Pas de webhooks entrants, pas de gestion de contacts/conversations : ce
  projet est volontairement limité à l'envoi transactionnel.
