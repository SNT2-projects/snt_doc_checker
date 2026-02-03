# Historique des Versions - snt_doc_checker

## Version 1.4.0 (2026-02-03)

### Nouvelles fonctionnalites

#### Statuts de commande separes pour Categorie C et D
- **Avant:** Un seul statut "Demande de pieces" pour toutes les commandes avec produits restreints
- **Apres:** Deux statuts distincts configurables :
  - `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C` → "Demande de pieces : Categorie C"
  - `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D` → "Demande de pieces : Categorie D"
- **Regle de priorite:** Si une commande contient des produits C ET D, le statut D est applique (prioritaire)

#### Statut "Pieces validees" automatique
- **Nouveau champ:** `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES`
- **Logique:** Si le client a deja des documents avec `statut_document = 'valid'`, le statut "Pieces validees" est automatiquement applique au lieu de "Demande de pieces"
- **Nouvelle methode:** `customerHasValidDocuments($idCustomer)` - verifie en base si le client possede des documents valides

#### Correction du timing des statuts
- **Probleme:** Le statut "Demande de pieces" etait ajoute AVANT le statut de paiement (ex: "En attente de virement bancaire")
- **Solution:** Changement du hook de `actionValidateOrder` vers `actionOrderStatusPostUpdate`
- **Resultat:** Le statut du module est maintenant ajoute EN DERNIER dans l'historique de la commande

### Modifications techniques

#### Fichiers modifies

| Fichier | Modification |
|---------|--------------|
| `snt_doc_checker.php` | Hook change, nouvelles configs, nouvelle methode `customerHasValidDocuments()` |
| `upgrade/upgrade-1.4.0.php` | Migration du hook + migration de l'ancienne config vers les nouvelles |

#### Configuration (onglet Restrictions)

| Cle | Description |
|-----|-------------|
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C` | ID du statut "Demande de pieces : Categorie C" |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D` | ID du statut "Demande de pieces : Categorie D" |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES` | ID du statut "Pieces validees" |

#### Logique du hook `hookActionOrderStatusPostUpdate()`

```
1. Commande avec produit categorie C ou D ?
   |-- NON → Ne rien faire
   |-- OUI → Continuer

2. Client a des documents valides ?
   |-- OUI → Appliquer statut "Pieces validees"
   |-- NON → Continuer

3. Produit categorie D present ?
   |-- OUI → Appliquer statut "Demande de pieces : Categorie D"
   |-- NON → Appliquer statut "Demande de pieces : Categorie C"
```

#### Script d'upgrade 1.4.0

- Desenregistre l'ancien hook `actionValidateOrder`
- Enregistre le nouveau hook `actionOrderStatusPostUpdate`
- Migre l'ancienne config `SNT_DOC_CHECKER_ORDER_STATUS_PIECES` vers les deux nouvelles (C et D)

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

## Resume des configurations

### Onglet Restrictions (v1.4.0)

| Cle | Description |
|-----|-------------|
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C` | ID feature_value categorie C |
| `SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D` | ID feature_value categorie D |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C` | ID statut "Demande de pieces : Categorie C" |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D` | ID statut "Demande de pieces : Categorie D" |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES` | ID statut "Pieces validees" |

### Hooks enregistres (v1.4.0)

| Hook | Description |
|------|-------------|
| `actionOrderStatusPostUpdate` | Changement automatique statut commande (apres statut paiement) |
| `displayAdminOrder` | Affichage documents sur page commande BO |
| `displayTagCommentOrder` | Avertissement categorie C/D dans tunnel |
| `displayCustomerAccount` | Lien vers upload documents |
| `displayMyAccountBlock` | Lien sidebar compte client |
| `actionFrontControllerSetMedia` | CSS front-office |

---

## Notes de migration

### De 1.3.0 vers 1.4.0

1. L'ancienne config `SNT_DOC_CHECKER_ORDER_STATUS_PIECES` est automatiquement migree vers `_PIECES_C` et `_PIECES_D`
2. Configurer les nouveaux IDs de statut dans le BO :
   - ID du statut "Demande de pieces : Categorie C"
   - ID du statut "Demande de pieces : Categorie D"
   - ID du statut "Pieces validees"

### De < 1.3.0 vers 1.3.0

1. La table `ps_snt_doc_checker_customer` est creee automatiquement
2. Les clients existants auront `birth_date = NULL`
3. La date de naissance devra etre saisie lors de la premiere modification d'un document
