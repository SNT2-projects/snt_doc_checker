# Scénarios de Test - snt_doc_checker v1.4.0

## Configuration Préalable

Avant de lancer les tests, vérifier que les configurations suivantes sont renseignées :

| Configuration | Description | Exemple |
|---------------|-------------|---------|
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C` | ID du statut "Demande de pièces : Catégorie C" | 15 |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D` | ID du statut "Demande de pièces : Catégorie D" | 16 |
| `SNT_DOC_CHECKER_ORDER_STATUS_PIECES_VALIDES` | ID du statut "Pièces validées" | 17 |
| `SNT_DOC_CHECKER_TRIGGER_STATUSES` | IDs des statuts déclencheurs (séparés par virgules) | 2,10,11 |
| `SNT_DOC_CHECKER_FEATURE_ID` | ID de la caractéristique "Catégorie" | 8 |
| `SNT_DOC_CHECKER_FEATURE_VALUE_C` | ID de la valeur "Catégorie C" | 45 |
| `SNT_DOC_CHECKER_FEATURE_VALUE_D` | ID de la valeur "Catégorie D" | 46 |

---

## Matrice de Tests

### Légende des statuts attendus

- **Statut C** = "Demande de pièces : Catégorie C"
- **Statut D** = "Demande de pièces : Catégorie D"
- **Statut Validé** = "Pièces validées"
- **Aucun** = Pas de statut ajouté par le module

---

## Tests par Scénario

### 1. Client SANS documents

| # | Produits dans la commande | Statut attendu | Priorité |
|---|---------------------------|----------------|----------|
| 1.1 | Produit Catégorie C uniquement | **Statut C** | - |
| 1.2 | Produit Catégorie D uniquement | **Statut D** | - |
| 1.3 | Produits Catégorie C + Catégorie D | **Statut D** | D > C |
| 1.4 | Produits sans restriction (ni C ni D) | **Aucun** | - |
| 1.5 | Produit C + Produit sans restriction | **Statut C** | - |
| 1.6 | Produit D + Produit sans restriction | **Statut D** | - |

---

### 2. Client AVEC documents VALIDES

| # | Produits dans la commande | Statut attendu | Priorité |
|---|---------------------------|----------------|----------|
| 2.1 | Produit Catégorie C uniquement | **Statut Validé** | - |
| 2.2 | Produit Catégorie D uniquement | **Statut Validé** | - |
| 2.3 | Produits Catégorie C + Catégorie D | **Statut Validé** | - |
| 2.4 | Produits sans restriction (ni C ni D) | **Aucun** | - |
| 2.5 | Produit C + Produit sans restriction | **Statut Validé** | - |
| 2.6 | Produit D + Produit sans restriction | **Statut Validé** | - |

---

### 3. Client AVEC documents EN ATTENTE (pending)

| # | Produits dans la commande | Statut attendu | Priorité |
|---|---------------------------|----------------|----------|
| 3.1 | Produit Catégorie C uniquement | **Statut C** | - |
| 3.2 | Produit Catégorie D uniquement | **Statut D** | - |
| 3.3 | Produits Catégorie C + Catégorie D | **Statut D** | D > C |
| 3.4 | Produits sans restriction (ni C ni D) | **Aucun** | - |

> Note: Un document "pending" n'est pas considéré comme valide

---

### 4. Client AVEC documents EXPIRES

| # | Produits dans la commande | Statut attendu | Priorité |
|---|---------------------------|----------------|----------|
| 4.1 | Produit Catégorie C uniquement | **Statut C** | - |
| 4.2 | Produit Catégorie D uniquement | **Statut D** | - |
| 4.3 | Produits Catégorie C + Catégorie D | **Statut D** | D > C |
| 4.4 | Produits sans restriction (ni C ni D) | **Aucun** | - |

> Note: Un document expiré n'est pas considéré comme valide

---

### 5. Client AVEC documents REFUSES (rejected)

| # | Produits dans la commande | Statut attendu | Priorité |
|---|---------------------------|----------------|----------|
| 5.1 | Produit Catégorie C uniquement | **Statut C** | - |
| 5.2 | Produit Catégorie D uniquement | **Statut D** | - |
| 5.3 | Produits Catégorie C + Catégorie D | **Statut D** | D > C |
| 5.4 | Produits sans restriction (ni C ni D) | **Aucun** | - |

> Note: Un document "rejected" n'est pas considéré comme valide

---

## Tests de Timing du Statut

### 6. Vérification de l'ordre des statuts

| # | Mode de paiement | Vérification |
|---|------------------|--------------|
| 6.1 | Carte bancaire | Le statut module doit apparaître APRÈS le statut "Paiement accepté" |
| 6.2 | Virement bancaire | Le statut module doit apparaître APRÈS le statut "En attente de virement bancaire" |
| 6.3 | Chèque | Le statut module doit apparaître APRÈS le statut "En attente de paiement par chèque" |
| 6.4 | PayPal | Le statut module doit apparaître APRÈS le statut PayPal |

**Comment vérifier :**
1. Passer la commande
2. Aller dans Back-Office > Commandes > Détail de la commande
3. Vérifier l'historique des statuts (section "Historique")
4. Le statut du module de paiement doit avoir un `id_order_history` plus petit que celui du module doc_checker

---

## Tests des Statuts Déclencheurs

### 7. Vérification des triggers

| # | Statut déclencheur | Statut NON déclencheur | Résultat attendu |
|---|--------------------|-----------------------|------------------|
| 7.1 | ID 2 (Paiement accepté) configuré | - | Statut module ajouté |
| 7.2 | - | ID 3 (En préparation) non configuré | Aucun statut ajouté |
| 7.3 | IDs 2,10 configurés | Changement manuel vers ID 5 | Aucun statut ajouté |

---

## Checklist de Test Rapide

### Préparation

- [ ] Créer un compte client TEST sans documents
- [ ] Créer un compte client TEST avec 1 document valide
- [ ] Identifier 1 produit Catégorie C
- [ ] Identifier 1 produit Catégorie D
- [ ] Identifier 1 produit sans restriction

### Tests Essentiels

- [ ] **Test 1.1** : Client sans doc + Produit C → Statut C
- [ ] **Test 1.3** : Client sans doc + Produit C + D → Statut D (priorité)
- [ ] **Test 2.1** : Client avec doc valide + Produit C → Statut Validé
- [ ] **Test 1.4** : Client sans doc + Produit normal → Aucun statut
- [ ] **Test 6.1** : Vérifier timing (statut après paiement)

---

## Données de Test Suggérées

### Produits de Test

| Référence | Nom | Catégorie |
|-----------|-----|-----------|
| TEST-C-001 | Carabine Test Cat C | Catégorie C |
| TEST-D-001 | Pistolet Test Cat D | Catégorie D |
| TEST-N-001 | Accessoire Test | Aucune (pas de restriction) |

### Comptes Client de Test

| Email | Documents | Statut documents |
|-------|-----------|------------------|
| test-no-doc@example.com | Aucun | - |
| test-valid-doc@example.com | CNI | Valide |
| test-pending-doc@example.com | CNI | En attente |
| test-expired-doc@example.com | CNI | Expiré |
| test-rejected-doc@example.com | CNI | Refusé |

---

## Résultats des Tests

| Date | Version | Test # | Résultat | Notes |
|------|---------|--------|----------|-------|
| | 1.4.0 | | | |
| | | | | |
| | | | | |

---

## Notes Importantes

1. **Priorité D > C** : Si une commande contient des produits des deux catégories, seul le statut Catégorie D est appliqué (le plus restrictif)

2. **Document valide** : Seul un document avec `statut_document = 'valid'` ET non expiré est considéré comme valide

3. **Statuts déclencheurs** : Le module ne réagit qu'aux statuts configurés dans `SNT_DOC_CHECKER_TRIGGER_STATUSES`

4. **Pas de doublon** : Si le statut module est déjà présent dans l'historique, il ne sera pas ajouté une seconde fois

5. **Hook utilisé** : `actionObjectOrderHistoryAddAfter` - se déclenche APRÈS l'enregistrement d'un statut dans l'historique
