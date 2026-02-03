# SNT Doc Checker - Documentation pour Claude

## Contexte Métier

Module PrestaShop permettant aux clients d'envoyer des documents d'identité (CNI, passeport, permis de chasse, licences de tir, etc.) pour autoriser l'achat d'armes de **catégorie C** et **catégorie D** sur le site SNT.

Les documents sont stockés sur **Scaleway S3** (compatible AWS S3).

---

## Structure du Module

```
snt_doc_checker/
├── snt_doc_checker.php              # Classe principale du module (1127 lignes)
├── config.xml / config_fr.xml       # Métadonnées du module
│
├── controllers/
│   ├── admin/
│   │   └── AdminMenuTabsDocChecker.php   # BO: Gestion des documents
│   └── front/
│       ├── user_uploader.php             # FO: Upload documents client
│       └── cron.php                      # CRON: Vérification expiration
│
├── models/
│   └── SntDocCheckerData.php        # ObjectModel pour les documents
│
├── s3/
│   └── S3Utils.php                  # Utilitaire AWS S3 (Scaleway)
│
├── sql/
│   ├── install.php                  # Création des tables
│   └── uninstall.php                # Désinstallation (ne supprime pas les données)
│
├── upgrade/
│   └── upgrade-1.2.0.php            # Dernière version
│
├── views/
│   ├── css/
│   │   ├── back.css                 # Styles BO
│   │   └── front.css                # Styles FO
│   ├── js/
│   │   ├── back.js                  # Import batch, progress bar (310 lignes)
│   │   └── front.js                 # Interactions FO
│   └── templates/
│       ├── admin/
│       │   ├── configure.tpl        # Configuration module
│       │   ├── order_documents.tpl  # Documents sur page commande
│       │   ├── preview_document.tpl # Prévisualisation avec zoom/rotation
│       │   ├── search_customer.tpl  # Recherche client
│       │   └── cron_info.tpl        # Infos CRON
│       ├── front/
│       │   ├── user_uploader.tpl    # Formulaire upload client
│       │   └── order_comment.tpl    # Avertissements tunnel commande
│       └── hook/
│           ├── block_user_uploader.tpl
│           └── card_user_uploader.tpl
│
├── mails/fr/                        # Templates emails (FR uniquement)
│   ├── doc_accepted.html/txt
│   ├── doc_refused.html/txt
│   ├── doc_expired.html/txt
│   ├── doc_submited.html/txt
│   ├── pieces_categorie_c.html/txt
│   └── pieces_categorie_d.html/txt
│
├── translations/
│   └── fr.php                       # Traductions françaises
│
├── uploads/                         # Stockage local (non utilisé, S3 préféré)
└── logs/
    └── import_debug.log             # Logs d'import
```

---

## Tables de Base de Données

### `ps_snt_doc_checker_data` (Documents)
| Colonne | Type | Description |
|---------|------|-------------|
| id_data | INT(11) PK | ID auto-incrémenté |
| dte_upload | TIMESTAMP | Date d'upload |
| link_document | TEXT(300) | Chemin fichier |
| type_document | VARCHAR(100) | Type (pi, pc, vpc, lfft, lffb, lffbt, cc, pp, pcd) |
| statut_document | VARCHAR(100) | Statut (pending, valid, refused, expired, deleted) |
| comment_document | VARCHAR(100) | Commentaire admin |
| dte_update | DATE | Dernière mise à jour |
| id_document | VARCHAR(100) | Référence document |
| dte_expiration | DATE | Date d'expiration |
| id_customer | INT(11) | ID client |

### `ps_snt_doc_checker_message` (Messages configurables)
| Colonne | Type | Description |
|---------|------|-------------|
| id_message | INT(11) PK | ID |
| type_message | VARCHAR(100) | Type de message |
| value_message | TEXT(300) | Contenu |

### `ps_snt_doc_checker_doc_type` (Types de documents)
| Colonne | Type | Description |
|---------|------|-------------|
| id_doc_type | INT(11) PK | ID |
| doc_type_identifier | VARCHAR(100) | Identifiant (pi, pc, etc.) |
| doc_type_name | VARCHAR(100) | Nom affiché |
| doc_type_description | TEXT(300) | Description |
| doc_type_status | TINYINT(1) | Actif/Inactif |

### `ps_snt_doc_checker_logs` (Historique actions)
| Colonne | Type | Description |
|---------|------|-------------|
| id_log | INT(11) PK | ID |
| id_employee | INT(11) | Employé |
| employee_name | VARCHAR(255) | Nom employé |
| id_document | INT(11) | Document concerné |
| id_customer | INT(11) | Client concerné |
| action | VARCHAR(50) | Type d'action |
| details | TEXT | Détails |
| date_add | DATETIME | Date |
| ip_address | VARCHAR(45) | IP |

### `ps_snt_doc_checker_doc_association` (Liaison recto/verso)
| Colonne | Type | Description |
|---------|------|-------------|
| id_association | INT(11) PK | ID |
| id_document_principal | INT(11) | Document recto (gère expiration) |
| id_document_associe | INT(11) | Document verso |
| date_add | DATETIME | Date création |

### `ps_snt_doc_checker_customer` (Infos client - v1.3.0)
| Colonne | Type | Description |
|---------|------|-------------|
| id_customer_info | INT(11) PK | ID auto-incrémenté |
| id_customer | INT(11) UNIQUE | ID client PrestaShop |
| birth_date | DATE NULL | Date de naissance (obligatoire en BO) |
| date_add | DATETIME | Date création |
| date_upd | DATETIME | Date mise à jour |

**Note:** Une seule ligne par client. La date de naissance est partagée par tous ses documents.

---

## Types de Documents

| Code | Nom complet |
|------|-------------|
| `pi` | Carte Nationale d'Identité |
| `pc` | Permis de chasse |
| `vpc` | Validation du permis de chasse |
| `lfft` | Licence Fédération Française de Tir |
| `lffb` | Licence Fédération Française de Biathlon |
| `lffbt` | Licence Fédération Française de Ball-trap |
| `cc` | Carte de collectionneur |
| `pp` | Passeport |
| `pcd` | Permis de conduire |

---

## Statuts des Documents

| Statut | Signification |
|--------|---------------|
| `pending` | En attente de validation |
| `valid` | Validé par admin |
| `refused` | Refusé par admin |
| `expired` | Expiré (automatique via CRON) |
| `deleted` | Supprimé |

---

## Hooks Utilisés

| Hook | Utilisation |
|------|-------------|
| `header` | Chargement CSS/JS FO (commenté) |
| `displayBackOfficeHeader` | Debug BO (commenté) |
| `displayCustomerAccount` | Lien vers upload documents |
| `displayMyAccountBlock` | Lien sidebar compte |
| `actionFrontControllerSetMedia` | CSS front-office |
| `displayAdminOrder` | Affiche documents sur commande |
| `displayTagCommentOrder` | Avertissement catégorie C/D dans tunnel |
| `actionValidateOrder` | Changement statut auto si produit cat. C/D (v1.3.0) |

---

## Configuration (ps_configuration)

| Clé | Description |
|-----|-------------|
| `SNT_DOC_CHECKER_MESSAGES_FRONT` | Activer messages (bool) |
| `SNT_DOC_CHECKER_MESSAGE_TUNNEL_C` | Message catégorie C (HTML) |
| `SNT_DOC_CHECKER_MESSAGE_TUNNEL_D` | Message catégorie D (HTML) |
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C` | ID valeur caractéristique cat. C |
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D` | ID valeur caractéristique cat. D |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES` | ID statut "Demande de pièces" (v1.3.0) |
| `SNT_DOC_CHECKER_S3_KEY` | Clé AWS |
| `SNT_DOC_CHECKER_S3_SECRET` | Secret AWS |
| `SNT_DOC_CHECKER_S3_BUCKET` | Nom du bucket |
| `SNT_DOC_CHECKER_AWS_REGION` | Région (fr-par pour Scaleway) |
| `SNT_DOC_CHECKER_S3_STORAGE_TYPE` | Type stockage (STANDARD) |
| `SNT_DOC_CHECKER_DAILY_CHECK` | Activer vérification quotidienne (bool) |
| `SNT_DOC_CHECKER_CRON_SECURE_KEY` | Clé sécurité CRON (auto-générée) |
| `SNT_DOC_CHECKER_LOGS` | Activer logs debug (bool) |

---

## Intégration S3 (Scaleway)

**Fichier:** `s3/S3Utils.php`

**Méthodes principales:**
- `putS3($filePath)` - Upload fichier
- `getS3($fileName)` - Téléchargement
- `deleteS3($fileName)` - Suppression
- `listS3($maxKeys, $marker)` - Liste paginée
- `getS3PresignedUrl($fileName, $expirationSeconds, $extension)` - URL temporaire signée
- `getTemporaryConsultationUrl($fileName, $extension)` - URL consultation 60 min

**Configuration:**
- Signature AWS v2 (HMAC-SHA1)
- ACL: private
- Endpoint: `https://{bucket}.s3.{region}.scw.cloud`

---

## Fonctionnalités Back-Office

### Controller: `AdminMenuTabsDocChecker.php`

**Liste des documents:**
- Filtres: type, statut, date
- Actions: Éditer, Voir, Supprimer
- Jointure client pour afficher nom/email

**Prévisualisation:**
- Zoom (0.1x à 5x)
- Rotation (90°)
- Drag/pan pour images
- Viewer PDF intégré
- URL temporaire 60 min

**Actions AJAX:**
- `searchCustomer` - Recherche client en temps réel
- `getCustomerDocuments` - Documents d'un client
- `linkDocuments` - Associer recto/verso
- `unlinkDocument` - Dissocier
- `setExpirationDate` - Définir expiration

---

## Fonctionnalités Front-Office

### Controller: `user_uploader.php`

- Maximum 15 documents par client
- Upload double fichier (recto/verso)
- Validation fichier (type, taille)
- Email de confirmation à la soumission
- Tableau récapitulatif des documents avec statut

---

## CRON d'Expiration

### Controller: `cron.php`

**URL:** `index.php?fc=module&module=snt_doc_checker&controller=cron&secure_key=[KEY]`

**Fonctionnement:**
1. Vérifie la clé de sécurité
2. Parcourt les documents avec date d'expiration dépassée
3. Passe le statut en `expired`
4. Met à jour les documents associés (verso)
5. Log toutes les opérations
6. Retourne JSON avec statistiques

---

## Import depuis l'Ancien Module (Legacy)

**Note:** L'ancien module s'appelait `tag_checker`. La fonctionnalité d'import permet de migrer les données si l'ancien module est encore installé.

**Processus:**
1. Bouton "Importer depuis l'ancien module" dans config (visible uniquement si tag_checker installé)
2. `ajaxCountDocuments()` compte les documents dans `ps_tag_checker_document`
3. `ajaxImportBatch()` importe par lots de 10
4. Upload S3 avec délai 10ms entre fichiers
5. Progress bar temps réel
6. `cleanLegacyTableDocuments()` nettoie les orphelins

---

## Points d'Attention pour les Développements

### Sécurité
- Utiliser `pSQL()` pour toutes les requêtes SQL
- Valider les fichiers uploadés (type, taille)
- Vérifier l'authentification client en FO
- Clé sécurisée pour CRON

### Performance
- Import par batch (10 documents)
- Délai entre uploads S3 (10ms)
- Auto-nettoyage logs > 5MB

### Conventions
- Version actuelle: **1.3.0**
- Préfixe config: `SNT_DOC_CHECKER_`
- Préfixe tables: `ps_snt_doc_checker_`
- Langue: Français uniquement

---

## Informations Projet

- **Langue:** Français uniquement (pas de multi-langue prévu)
- **Validation:** Manuelle par un humain (pas d'automatisation prévue)
- **Environnement de test:** PrestaShop Platform DEV (géré par le client)
- **Ancien nom du module:** `tag_checker` (nettoyé le 2026-02-03)

---

## Dette Technique / Nettoyage à Faire

| Élément | Description | Priorité | Statut |
|---------|-------------|----------|--------|
| ~~Références "tag_checker"~~ | ~~URLs, variables, commentaires renommés~~ | ~~Moyenne~~ | ✅ Fait |

**Note:** Les requêtes SQL vers `ps_tag_checker_document` sont conservées car c'est la table de l'ancien module (nécessaire pour l'import legacy).

---

## Erreurs Connues et Solutions

*Section à enrichir au fur et à mesure des développements*

| Erreur | Cause | Solution |
|--------|-------|----------|
| IDE: "Undefined type 'Module'" | L'IDE ne connaît pas PrestaShop | Faux positif, ignorer |

---

## Évolutions Réalisées

| Date | Version | Demande | Statut |
|------|---------|---------|--------|
| 2026-02-03 | 1.2.0 | Renommer références tag_checker → snt_doc_checker | ✅ Fait |
| 2026-02-03 | 1.3.0 | Date naissance client + Date expiration obligatoire | ✅ Fait |
| 2026-02-03 | 1.3.0 | Synchronisation statut entre documents liés (recto/verso) | ✅ Fait |
| 2026-02-03 | 1.3.0 | Changement statut automatique commande si produit cat. C/D | ✅ Fait |

---

## Notes Importantes

1. **Association recto/verso:** Le document principal (recto) gère la date d'expiration. Quand il expire, le verso expire aussi automatiquement. **De plus, quand on change le statut d'un document lié, l'autre document est automatiquement mis à jour avec le même statut.**

2. **Catégories C et D:** Les produits sont identifiés via des valeurs de caractéristiques PrestaShop. Les IDs sont configurés dans le BO.

3. **Emails:** Templates uniquement en français dans `/mails/fr/`. Copiés dans le dossier global PrestaShop à l'installation.

4. **S3:** Compatible Scaleway (pas AWS natif). Utilise signature v2, pas v4.

5. **Pas de suppression à la désinstallation:** Le fichier `uninstall.php` ne supprime PAS les données (tables conservées).

6. **Validation obligatoire (v1.3.0):**
   - **Date de naissance:** BLOQUE la sauvegarde d'un document si non renseignée (liée au client, pas au document)
   - **Date d'expiration:** BLOQUE la validation (statut "valid") si non renseignée

7. **Changement statut automatique commande (v1.3.0):**
   - Hook `actionValidateOrder` intercepte les commandes validées
   - Vérifie si les produits ont des feature_values = catégorie C ou D
   - Si oui, change le statut vers "Demande de pièces" (ID configuré dans `SNT_DOC_CHECKER_ORDER_STATUS_PIECES`)
   - L'email associé au statut est envoyé automatiquement via `addWithemail()`
