# Note de mise à jour - Module Doc Checker v1.4.0

**Date :** 04/02/2026
**Destinataires :** Équipe e-commerce / Service client

---

## Résumé des changements

Le module de vérification des documents d'identité a été mis à jour avec plusieurs améliorations pour faciliter le travail des équipes et automatiser certaines tâches.

---

## 1. Gestion des documents clients (v1.3.0)

### Date de naissance obligatoire

Lors de la consultation ou modification d'un document client, **la date de naissance du client doit être renseignée**. Cette information est désormais obligatoire pour pouvoir sauvegarder un document.

- Si la date de naissance n'est pas renseignée, une fenêtre s'affiche pour la saisir
- Cette date est liée au client (pas au document), donc elle n'est à saisir qu'une seule fois par client
- **Exception (v1.7.1) :** un document peut être passé au statut **"Refusé"** sans date de naissance (par exemple quand le document est refusé justement parce qu'il ne permet pas de lire la date de naissance)

### Date d'expiration obligatoire pour valider

Pour passer un document au statut **"Validé"**, la date d'expiration doit obligatoirement être renseignée. Sans cette date, il est impossible de valider le document.

- Les autres statuts (En attente, Refusé) restent possibles sans date d'expiration
- Cela garantit un suivi des documents qui vont expirer

### Synchronisation des documents liés (recto/verso)

Lorsque deux documents sont liés (par exemple : recto et verso d'une carte d'identité), **le changement de statut de l'un entraîne automatiquement le même changement sur l'autre**.

- Si vous validez le recto, le verso est automatiquement validé
- Si vous refusez le verso, le recto est automatiquement refusé
- Cela évite les incohérences entre documents liés

---

## 2. Attribution automatique des statuts de commande (v1.4.0)

### Fonctionnement

Lorsqu'un client passe une commande contenant des **produits de catégorie C ou D** (armes réglementées), le module attribue automatiquement un statut à la commande selon des règles précises.

### Règles pour la Catégorie D

| Condition | Statut attribué |
|-----------|-----------------|
| Le client a **18 ans ou plus** | ✅ "Pièces validées" |
| Le client a **moins de 18 ans** ou âge non renseigné | ❌ "Demande de pièces : Catégorie D" |

### Règles pour la Catégorie C (plus restrictives)

| Condition | Statut attribué |
|-----------|-----------------|
| Le client a **18 ans ou plus** ET possède un **Permis de chasse** ou **Licence FFTir** valide et non expiré | ✅ "Pièces validées" |
| Sinon (âge < 18 ans OU pas de permis/licence valide) | ❌ "Demande de pièces : Catégorie C" |

**Documents acceptés pour la Catégorie C :**
- Permis de chasse (pc)
- Validation du permis de chasse (vpc)
- Licence Fédération Française de Tir (lfft)

### Commandes mixtes (Cat. C + Cat. D)

Si une commande contient à la fois des produits Cat. C et Cat. D, **les règles de la Catégorie C s'appliquent** (car plus restrictives).

### Quand le statut est-il attribué ?

Le statut est attribué automatiquement lorsque la commande passe en :
- **"Paiement accepté"** (paiement carte, PayPal confirmé, etc.)
- **"En attente de virement bancaire"**

### Emails automatiques

Lorsque le statut "Demande de pièces" est attribué, **un email est automatiquement envoyé au client** pour lui demander de fournir ses documents.

### Importance de la date de naissance

**IMPORTANT :** La date de naissance du client doit être renseignée dans le module Doc Checker pour que les règles d'âge fonctionnent. Si la date de naissance n'est pas renseignée, le client sera considéré comme n'ayant pas l'âge requis.

---

## 3. Ce qui change pour les équipes

### Au quotidien

1. **Plus besoin d'attribuer manuellement** le statut "Demande de pièces" sur les commandes avec produits réglementés
2. **Vérifier la date de naissance** lors de la première consultation d'un document client
3. **Renseigner la date d'expiration** avant de valider un document

## 4. Points d'attention

### Documents liés

Attention : si vous changez le statut d'un document lié (recto/verso), l'autre document sera automatiquement mis à jour. Vérifiez bien les deux documents avant de valider ou refuser.

### Commandes mixtes (Cat. C + Cat. D)

Si une commande contient à la fois des produits de catégorie C et de catégorie D, c'est le statut **"Catégorie C"** qui est appliqué (car plus restrictif).

### Vérification de l'âge

L'âge du client est calculé à partir de la **date de naissance** renseignée dans le module Doc Checker (table `ps_snt_doc_checker_customer`). Si cette date n'est pas renseignée, le client est considéré comme mineur.

### Documents valides pour Catégorie C

Pour la Catégorie C, seuls les documents suivants sont pris en compte :
- **Permis de chasse** (type: pc)
- **Validation du permis de chasse** (type: vpc)
- **Licence FFTir** (type: lfft)

Ces documents doivent être :
- Au statut **"Validé"**
- **Non expirés** (date d'expiration >= aujourd'hui ou non renseignée)

---

## 5. En cas de problème

Si une commande n'a pas reçu le bon statut automatiquement :
1. Vérifier que la commande contient bien des produits de catégorie C ou D
2. Vérifier la configuration du module (IDs des statuts, IDs des catégories)
3. Attribuer manuellement le statut si nécessaire

Pour toute question technique, contacter le **support@snt2.fr**.

## 6. Ce que vous devez continuer à faire manuellement

1. Une commande est en Demande de pièces : Catégorie C ou D -> La passée en Pièces validées une fois que le collaborateur a validé le document du client, cela permettra au connecteur de l'importer

2. Eventuellement débloquer la commande côté Oasis si elle est bloquée pour demande de pièces
