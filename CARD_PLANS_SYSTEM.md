# 🎴 Système de Card Plans FueliX

## 📋 Vue d'ensemble

Le système de card plans permet à l'admin (via web dashboard) de gérer les niveaux de cartes des utilisateurs. Les clients voient leur carte dans l'app mobile avec les services autorisés selon leur plan.

---

## 🏗️ Architecture

### **3 Plans Fixes**
- **Bronze** : Fuel uniquement (#CD7F32)
- **Silver** : Fuel + Car wash (#C0C0C0)
- **Gold** : Fuel + Car wash + Lubricants (#FFD700)

### **Rôles**
- **Client (App Mobile)** : Voit sa carte, scanne pour payer
- **Admin (Web Dashboard)** : Change le niveau de carte des utilisateurs

---

## 🔧 Backend (Laravel)

### **Routes API**

#### **Public (Client)**
```
GET /api/card-plans
```
Retourne la liste des 3 plans disponibles.

#### **Admin (Protégé par middleware 'admin')**
```
POST /admin/card-plans/seed
```
Crée les 3 plans par défaut dans Firestore (à faire une seule fois).

```
POST /admin/card-plans/assign
Body: {
  "user_id": "firestore_user_id",
  "card_id": "firestore_card_id",
  "plan_id": "bronze|silver|gold"
}
```
Change le plan d'un utilisateur.

### **Middleware Admin**
`app/Http/Middleware/EnsureUserIsAdmin.php`
- Vérifie que `$user->role === 'admin'`
- Protège toutes les routes `/admin/*`

### **Controller**
`app/Http/Controllers/Api/CardPlanController.php`
- `index()` : Liste des plans
- `seed()` : Créer les 3 plans par défaut
- `assignToUser()` : Assigner un plan à un utilisateur

---

## 📱 Frontend (Flutter)

### **Écrans Client**

#### **Card Screen** (`/card`)
- Affiche la carte de l'utilisateur
- **Couleur dynamique** selon le plan (Bronze/Silver/Gold)
- **Nom du plan** affiché sous "Digital Card"
- **Services autorisés** :
  - ✅ Autorisé : Icône colorée, cliquable
  - 🔒 Non autorisé : Icône grisée, cadenas, non cliquable
- Bouton "Scan to Pay" génère un QR code

**Données de la carte :**
```dart
{
  "id": "card_123",
  "masked_number": "**** **** **** 1234",
  "balance": 150.50,
  "balance_raw": 150.50,
  "valid_thru": "12/27",
  "issuer": "Fuelix",
  "card_plan_id": "gold",
  "card_plan_name": "Gold Card",
  "color": "#FFD700",
  "authorized_products": ["fuel", "carwash", "lubricants"]
}
```

**Note :** L'app mobile ne contient AUCUN écran admin. L'admin gère tout depuis le web dashboard.

### **API Service**
`lib/services/api_service.dart`

```dart
// Assigner un plan à un user (utilisé par le web dashboard)
ApiService.assignCardPlan(token, userId, cardId, planId)
```

---

## 🌐 Web Dashboard Admin (À implémenter)

### **Pages nécessaires**

#### **1. Liste des utilisateurs** (`/admin/users`)
```
┌─────────────────────────────────────────────────┐
│  Users Management                               │
├─────────────────────────────────────────────────┤
│  ID    Name         Email              Plan     │
│  001   John Doe     john@example.com   Bronze   │
│  002   Jane Smith   jane@example.com   Silver   │
│  003   Bob Wilson   bob@example.com    Gold     │
└─────────────────────────────────────────────────┘
```

#### **2. Détails utilisateur** (`/admin/users/{id}/edit`)
```
┌─────────────────────────────────────┐
│  Edit User: John Doe                │
├─────────────────────────────────────┤
│  Name: John Doe                     │
│  Email: john@example.com            │
│  Phone: +216 12 345 678             │
│                                     │
│  Fuel Card                          │
│  ─────────────────────────────────  │
│  Card Number: **** **** **** 1234   │
│  Balance: 50 TND                    │
│                                     │
│  Change Card Plan:                  │
│  ○ Bronze                           │
│  ○ Silver                           │
│  ● Gold                             │
│                                     │
│  [Save Changes]                     │
└─────────────────────────────────────┘
```

**API Call :**
```javascript
POST /admin/card-plans/assign
{
  "user_id": "john_firestore_id",
  "card_id": "john_card_id",
  "plan_id": "gold"
}
```

---

## 🔄 Workflow

### **Setup Initial (1 fois)**
1. Admin se connecte au web dashboard
2. Va dans "Card Plans"
3. Clique sur "Seed Default Plans"
4. Les 3 plans sont créés dans Firestore

### **Gestion Quotidienne**
1. Nouvel utilisateur s'inscrit → Reçoit carte Bronze automatiquement
2. Admin va dans "Users"
3. Clique sur "Edit" pour un utilisateur
4. Change le plan de Bronze à Silver ou Gold
5. Clique sur "Save"
6. L'utilisateur recharge son app et voit sa nouvelle carte

---

## 📊 Données Firestore

### **Collection : `card_plans`**
```
card_plans/
  bronze/
    name: "Bronze Card"
    description: "Basic fuel access"
    color: "#CD7F32"
    tier_level: 1
    authorized_products: ["fuel"]
    is_active: true
    created_at: "2026-05-06T10:00:00Z"
    updated_at: "2026-05-06T10:00:00Z"
    
  silver/
    name: "Silver Card"
    description: "Fuel + Car wash"
    color: "#C0C0C0"
    tier_level: 2
    authorized_products: ["fuel", "carwash"]
    is_active: true
    
  gold/
    name: "Gold Card"
    description: "All services included"
    color: "#FFD700"
    tier_level: 3
    authorized_products: ["fuel", "carwash", "lubricants"]
    is_active: true
```

### **Subcollection : `users/{uid}/fuel_cards/{cardId}`**
```
users/
  john_id/
    name: "John Doe"
    email: "john@example.com"
    role: "user"
    
    fuel_cards/
      card_123/
        card_number: "1234567890123456"
        masked_number: "**** **** **** 1234"
        balance: 150.50
        valid_thru: "12/27"
        issuer: "Fuelix"
        card_plan_id: "gold"
        card_plan_name: "Gold Card"
        color: "#FFD700"
        authorized_products: ["fuel", "carwash", "lubricants"]
        created_at: "2026-01-01T00:00:00Z"
        updated_at: "2026-05-06T10:30:00Z"
```

---

## ✅ Checklist

### **Backend**
- ✅ CardPlanController avec seed() et assign()
- ✅ Routes admin protégées par middleware
- ✅ Middleware EnsureUserIsAdmin
- ✅ Modèle User avec champ 'role'
- ✅ Migration users avec champ 'role'

### **Frontend Mobile**
- ✅ Card Screen affiche la couleur du plan
- ✅ Card Screen affiche le nom du plan
- ✅ Services autorisés/non autorisés visuellement différenciés
- ✅ API Service avec méthode assignCardPlan()
- ✅ Aucun écran admin dans l'app mobile

### **Web Dashboard**
- ⏳ Login admin
- ⏳ Page seed card plans (une seule fois)
- ⏳ Page liste des utilisateurs
- ⏳ Page détails utilisateur avec changement de plan
- ⏳ Intégration API assign card plan

---

## 🎯 Prochaines Étapes

1. **Créer le web dashboard admin** (React/Vue/Laravel Blade)
2. **Tester le workflow complet** :
   - Seed les plans
   - Créer un utilisateur
   - Changer son plan via web dashboard
   - Vérifier dans l'app mobile que la carte a changé
3. **Ajouter des logs** pour tracer les changements de plans

---

## 🔐 Sécurité

- Routes `/admin/*` protégées par middleware `admin`
- Vérification du rôle dans Firestore et MySQL
- Seuls les admins peuvent changer les plans
- Les clients ne peuvent pas changer leur propre plan

---

## 📝 Notes

- Les 3 plans sont **fixes** et ne changent jamais
- Pas de création/modification/suppression de plans
- L'admin change uniquement le niveau de carte des utilisateurs
- Le solde de la carte reste inchangé lors du changement de plan
- Les transactions passées restent valides

---

**Système prêt à être utilisé ! 🚀**
