# Historique des Versions - snt_doc_checker

## Version 1.7.1 (2026-10-26)

### Resume pour les equipes

Correctif : il est desormais possible de **refuser** un document meme si la date de naissance du client n'est pas renseignee. Jusqu'ici, l'enregistrement etait bloque par l'alerte "Impossible d'enregistrer : la date de naissance du client est obligatoire", ce qui empechait justement de refuser un document sans date de naissance lisible.

### Correctifs

#### Refus d'un document sans date de naissance client

Le controle de la date de naissance est ignore lorsque le statut enregistre est `refused`. Il reste actif pour tous les autres statuts (Valide, En attente, Expire). Le controle de la date d'expiration (obligatoire pour "Valide") est inchange.

Le controle existe a deux niveaux, les deux ont ete corriges :
- **Navigateur :** `views/templates/admin/preview_document.tpl`, ecouteur `submit` du formulaire (boutons "Enregistrer" et "Enregistrer et rester") : l'alerte et la modale de saisie ne s'affichent plus si le statut choisi est "Refuse".
- **Serveur :** `controllers/admin/AdminMenuTabsDocChecker.php`, traitement `submitUpdate` / `submitUpdateStay`.

#### Banniere d'alerte (mode edition)

- **Fichier:** `views/templates/admin/preview_document.tpl`
- Le texte precise que la date de naissance n'est pas requise pour passer le document au statut "Refuse".

### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `controllers/admin/AdminMenuTabsDocChecker.php` | Exception `refused` sur la validation serveur de la date de naissance |
| `views/templates/admin/preview_document.tpl` | Exception `refused` sur la validation JS + texte de la banniere |
| `config.xml`, `config_fr.xml`, `snt_doc_checker.php` | Version 1.7.1 |
| `NOTE_MISE_A_JOUR.md`, `CLAUDE.md` | Documentation de l'exception |

Aucun script d'upgrade : pas de changement de base de donnees ni de hook.

---

## Version 1.5.2 (2026-07-06)

### Resume pour les equipes

Correctif d'affichage : les champs de saisie de date du back-office affichaient parfois le format americain (mm/jj/aaaa) au lieu du format francais (jj/mm/aaaa). Verification complete de la fonctionnalite "Modifier la date de naissance".

### Correctifs

#### Format d'affichage des dates (jj/mm/aaaa)

- **Fichier:** `views/templates/admin/preview_document.tpl`
- Ajout de l'attribut `lang="fr-FR"` sur les champs `<input type="date">` :
  - Champ **date de naissance** du client (`#birthDate`)
  - Champ **date d'expiration** du document (`#expirationDate`)

**Limitation connue :** le format d'affichage d'un `<input type="date">` natif est impose par le navigateur, pas par le HTML.
- **Firefox :** suit la locale du systeme d'exploitation (jj/mm/aaaa si OS en francais).
- **Chrome / Edge :** suivent la langue de l'interface du navigateur (`chrome://settings/languages`) et **ignorent** l'attribut `lang`. Un Chrome en anglais affichera toujours mm/jj/aaaa.

La valeur transmise reste au format ISO `aaaa-mm-jj` : aucun impact sur l'enregistrement ni la base de donnees.

**Solution robuste envisagee (non implementee) :** remplacer le champ natif par un datepicker jQuery UI (deja charge dans le BO PrestaShop) force en jj/mm/aaaa, pour un affichage identique sur tous les navigateurs.

### Verification effectuee

Controle de bout en bout de la fonctionnalite **"Modifier la date de naissance"** :

| Couche | Element | Etat |
|--------|---------|------|
| UI affichage | Date affichee en `%d/%m/%Y`, badge + boutons Modifier/Definir | OK |
| UI alerte | Banniere bloquante si date manquante (mode edition) | OK |
| Modal | `#birthDateModal` + input `#birthDate` | OK |
| JS | `openBirthDateModal()` / `saveBirthDate()` (validation non-vide + date passee) | OK |
| Controller | `ajaxProcessSetBirthDate()` (validation format `Y-m-d` + date passee) | OK |
| Modele | `SntDocCheckerCustomer::updateBirthDate()` (getOrCreate + `pSQL`) | OK |

Chaine complete et coherente, aucune anomalie detectee.

### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `views/templates/admin/preview_document.tpl` | Attribut `lang="fr-FR"` sur les 2 champs date |

---

## Version 1.4.0 (2026-02-04)

### Resume pour les equipes

Cette version ajoute l'attribution automatique de statuts de commande selon la categorie de produits (C ou D) et les documents du client.

### Nouvelles fonctionnalites

- **Regle de priorite :** Si une commande contient des produits C ET D, le statut C est applique (plus restrictif)

#### 2. Statut "Pieces validees" automatique

- **Configuration :** `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES` → ID du statut "Pieces validees"
- **Logique :** Si le client a deja des documents valides en base (`statut_document = 'valid'`), le statut "Pieces validees" est applique automatiquement au lieu de "Demande de pieces"

#### 3. Statuts declencheurs configurables

- **Configuration :** `SNT_DOC_CHECKER_TRIGGER_STATUSES` → Liste d'IDs separes par virgules
- **Valeur recommandee :** `2,10` (Paiement accepte + En attente de virement bancaire)
- **Fonctionnement :** Le module n'ajoute son statut QUE lorsqu'un de ces statuts est applique a la commande

**IMPORTANT :** Ne PAS inclure les statuts intermediaires comme "En attente de paiement PayPal" pour eviter les problemes de timing.

### Configuration requise (Back-Office)

Aller dans **Modules > Doc Checker > Configuration > Onglet Restrictions** :

| Champ | Description | Exemple |
|-------|-------------|---------|
| ID categorie C | ID de la feature_value pour la categorie C | 45 |
| ID categorie D | ID de la feature_value pour la categorie D | 46 |
| ID statut "Demande pieces : Cat C" | ID du statut PrestaShop | 15 |
| ID statut "Demande pieces : Cat D" | ID du statut PrestaShop | 16 |
| ID statut "Pieces validees" | ID du statut PrestaShop | 17 |
| Statuts declencheurs | IDs separes par virgules | 2,10 |

### Logique de fonctionnement

```
Quand un statut est ajoute a une commande :

1. Le statut est-il un declencheur configure (ex: 2 ou 10) ?
   NON → Rien faire
   OUI → Continuer

2. Notre statut existe deja dans l'historique ?
   OUI → Rien faire (eviter doublon)
   NON → Continuer

3. La commande contient des produits de categorie C ou D ?
   NON → Rien faire
   OUI → Continuer

4. Determiner le statut selon les regles :

   CATEGORIE C (prioritaire si mixte C+D) :
   |-- Client >= 18 ans ET (permis chasse OU licence FFTir valide) ?
       OUI → "Pieces validees"
       NON → "Demande de pieces : Categorie C"

   CATEGORIE D :
   |-- Client >= 18 ans ?
       OUI → "Pieces validees"
       NON → "Demande de pieces : Categorie D"

5. Forcer le current_state a la fin du script (compatibilite PayPal)
```

### Nouvelles methodes ajoutees

| Methode | Description |
|---------|-------------|
| `getCustomerAge($idCustomer)` | Calcule l'age du client depuis `ps_snt_doc_checker_customer.birth_date` |
| `customerHasValidHuntingOrShootingLicense($idCustomer)` | Verifie si le client a un document pc/vpc/lfft valide et non expire |

### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `snt_doc_checker.php` | Hook `actionObjectOrderHistoryAddAfter`, logique statuts, `register_shutdown_function()` |
| `upgrade/upgrade-1.4.0.php` | Migration hooks + migration ancienne config |

### Migration depuis v1.3.0

1. **Automatique :** L'ancienne config `SNT_DOC_CHECKER_ORDER_STATUS_PIECES` est migree vers `_PIECES_C` et `_PIECES_D`
2. **Manuel :** Configurer les nouveaux champs dans le BO (voir tableau ci-dessus)
3. **Manuel :** Creer les statuts PrestaShop si necessaire :
   - "Demande de pieces : Categorie C"
   - "Demande de pieces : Categorie D"
   - "Pieces validees"

---

## Version 1.3.0 (2026-02-03)

### Nouvelles fonctionnalites

#### Date de naissance client obligatoire
- **Nouvelle table:** `ps_snt_doc_checker_customer`
  - `id_customer_info` (PK)
  - `id_customer` (UNIQUE)
  - `birth_date` (DATE NULL)
  - `date_add`, `date_upd`
- **Nouveau modele:** `models/SntDocCheckerCustomer.php`
- **Comportement:** La sauvegarde d'un document est BLOQUEE si la date de naissance du client n'est pas renseignee
- **Interface:** Modal de saisie dans la previsualisation du document

#### Date d'expiration obligatoire pour validation
- **Comportement:** Le passage au statut "valid" est BLOQUE si la date d'expiration n'est pas renseignee
- **Note:** Les autres statuts (pending, refused) restent possibles sans date d'expiration

#### Synchronisation des statuts recto/verso
- Quand on change le statut d'un document lie (recto ou verso), l'autre document est automatiquement mis a jour avec le meme statut

#### Changement automatique du statut de commande
- **Hook:** `actionValidateOrder` (remplace par `actionOrderStatusPostUpdate` en v1.4.0)
- **Comportement:** Si une commande contient des produits de categorie C ou D, le statut est automatiquement change
- **Email:** L'email associe au statut est envoye via `addWithemail()`

### Modifications techniques

#### Fichiers crees

| Fichier | Description |
|---------|-------------|
| `models/SntDocCheckerCustomer.php` | Modele pour gerer la date de naissance client |
| `upgrade/upgrade-1.3.0.php` | Script de creation de la table `ps_snt_doc_checker_customer` |

#### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `snt_doc_checker.php` | Version bump, nouveau hook, nouvelle config |
| `sql/install.php` | Ajout creation table `ps_snt_doc_checker_customer` |
| `controllers/admin/AdminMenuTabsDocChecker.php` | Validation date naissance + expiration, AJAX setBirthDate, sync statuts |
| `views/templates/admin/preview_document.tpl` | Bannieres alertes, modal date naissance, affichage infos |

---

## Version 1.2.0 (2026-02-03)

### Renommage tag_checker → snt_doc_checker

#### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `mails/fr/pieces_categorie_c.html` | URL `tag_checker` → `snt_doc_checker` |
| `mails/fr/pieces_categorie_d.html` | URL `tag_checker` → `snt_doc_checker` |
| `views/templates/front/order_comment.tpl` | Chemin image `tag_checker` → `snt_doc_checker` |
| `views/templates/admin/order_documents.tpl` | `mod='tag_checker'` → `mod='snt_doc_checker'` |

**Note:** Les requetes SQL vers `ps_tag_checker_document` sont conservees car c'est la table de l'ancien module (necessaire pour l'import legacy).

---

## Resume des configurations (v1.4.0)

### Onglet Restrictions

| Cle | Description | Exemple |
|-----|-------------|---------|
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C` | ID feature_value categorie C | 45 |
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D` | ID feature_value categorie D | 46 |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C` | ID statut "Demande de pieces : Categorie C" | 15 |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D` | ID statut "Demande de pieces : Categorie D" | 16 |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES` | ID statut "Pieces validees" | 17 |
| `SNT_DOC_CHECKER_TRIGGER_STATUSES` | IDs des statuts declencheurs | 2,10 |

### Hooks enregistres

| Hook | Description |
|------|-------------|
| `actionObjectOrderHistoryAddAfter` | Attribution automatique du statut selon categorie C/D |
| `displayAdminOrder` | Affichage documents sur page commande BO |
| `displayTagCommentOrder` | Avertissement categorie C/D dans tunnel |
| `displayCustomerAccount` | Lien vers upload documents |
| `displayMyAccountBlock` | Lien sidebar compte client |
| `actionFrontControllerSetMedia` | CSS front-office |

---

## Notes de migration

### De 1.3.0 vers 1.4.0

1. **Automatique :** L'ancienne config `SNT_DOC_CHECKER_ORDER_STATUS_PIECES` est migree vers `_PIECES_C` et `_PIECES_D`
2. **Manuel :** Configurer les nouveaux IDs de statut dans le BO
3. **Manuel :** Configurer `SNT_DOC_CHECKER_TRIGGER_STATUSES` avec `2,10`
4. **Manuel :** Desactiver/reactiver le module pour enregistrer le nouveau hook

### De < 1.3.0 vers 1.3.0

1. La table `ps_snt_doc_checker_customer` est creee automatiquement
2. Les clients existants auront `birth_date = NULL`
3. La date de naissance devra etre saisie lors de la premiere modification d'un document

---

## Statuts PrestaShop a creer

Pour que le module fonctionne, creer ces statuts dans **Commandes > Statuts** :

| Nom du statut | Couleur suggeree | Envoyer email |
|---------------|------------------|---------------|
| Demande de pieces : Categorie C | Orange | Oui (template: pieces_categorie_c) |
| Demande de pieces : Categorie D | Orange | Oui (template: pieces_categorie_d) |
| Pieces validees | Vert | Non |

Ensuite, reporter les IDs de ces statuts dans la configuration du module.
