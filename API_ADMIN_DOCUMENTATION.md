# 📚 API Admin Documentation - FueliX

## 🔐 Authentication

Toutes les routes admin nécessitent :
- Header `Authorization: Bearer {admin_token}`
- L'utilisateur doit avoir `role: "admin"` dans Firestore

---

## 📋 Endpoints

### **1. Liste des utilisateurs**

```http
GET /api/admin/users
Authorization: Bearer {admin_token}
```

**Réponse :**
```json
{
  "users": [
    {
      "id": "ObdoMo9R1f00T9qo2U18",
      "name": "aya ouni",
      "email": "aouney282@gmail.com",
      "phone": null,
      "city": null,
      "role": "user",
      "created_at": "2026-04-15T10:30:00Z",
      "cards": [
        {
          "id": "card_123",
          "card_number": "1234567890123456",
          "masked_number": "**** **** **** 1234",
          "balance": 150.50,
          "card_plan_id": "bronze",
          "card_plan_name": "Bronze Card",
          "color": "#CD7F32",
          "authorized_products": ["fuel"]
        }
      ]
    },
    {
      "id": "p8fabJuM3d1zmmDOQOZXh",
      "name": "amine boulabba",
      "email": "boulabbamine8@gmail.com",
      "role": "admin",
      "cards": []
    }
  ]
}
```

**Utilisation dans le dashboard :**
- Afficher la liste des users dans un tableau
- Colonne "Name", "Email", "Role", "Card Level", "Balance"
- Bouton "View/Edit" pour chaque user

---

### **2. Détails d'un utilisateur**

```http
GET /api/admin/users/{userId}
Authorization: Bearer {admin_token}
```

**Exemple :**
```http
GET /api/admin/users/ObdoMo9R1f00T9qo2U18
```

**Réponse :**
```json
{
  "user": {
    "id": "ObdoMo9R1f00T9qo2U18",
    "name": "aya ouni",
    "email": "aouney282@gmail.com",
    "phone": "+216 12 345 678",
    "city": "Tunis",
    "role": "user",
    "created_at": "2026-04-15T10:30:00Z"
  },
  "cards": [
    {
      "id": "card_123",
      "card_number": "1234567890123456",
      "masked_number": "**** **** **** 1234",
      "balance": 150.50,
      "card_plan_id": "bronze",
      "card_plan_name": "Bronze Card",
      "color": "#CD7F32",
      "authorized_products": ["fuel"],
      "created_at": "2026-01-01T00:00:00Z"
    }
  ],
  "available_plans": [
    {
      "id": "bronze",
      "name": "Bronze Card",
      "description": "Basic fuel access",
      "color": "#CD7F32",
      "tier_level": 1,
      "authorized_products": ["fuel"]
    },
    {
      "id": "silver",
      "name": "Silver Card",
      "description": "Fuel + Car wash",
      "color": "#C0C0C0",
      "tier_level": 2,
      "authorized_products": ["fuel", "carwash"]
    },
    {
      "id": "gold",
      "name": "Gold Card",
      "description": "All services included",
      "color": "#FFD700",
      "tier_level": 3,
      "authorized_products": ["fuel", "carwash", "lubricants"]
    }
  ]
}
```

**Utilisation dans le dashboard :**
- Afficher les infos du user
- Afficher sa carte actuelle
- Afficher un formulaire avec radio buttons pour les 3 niveaux
- Bouton "Update Card Level"

---

### **3. Modifier le niveau de carte**

```http
PUT /api/admin/users/{userId}/card-level
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "plan_id": "silver"
}
```

**Valeurs possibles pour `plan_id` :**
- `"bronze"` → Standard (Niveau 1)
- `"silver"` → Premium (Niveau 2)
- `"gold"` → VIP (Niveau 3)

**Exemple :**
```http
PUT /api/admin/users/ObdoMo9R1f00T9qo2U18/card-level
Content-Type: application/json

{
  "plan_id": "gold"
}
```

**Réponse :**
```json
{
  "message": "Card level updated to Gold Card successfully",
  "user": {
    "id": "ObdoMo9R1f00T9qo2U18",
    "name": "aya ouni",
    "email": "aouney282@gmail.com"
  },
  "card": {
    "id": "card_123",
    "card_plan_id": "gold",
    "card_plan_name": "Gold Card",
    "color": "#FFD700",
    "authorized_products": ["fuel", "carwash", "lubricants"],
    "balance": 150.50
  },
  "plan": {
    "id": "gold",
    "name": "Gold Card",
    "tier_level": 3,
    "color": "#FFD700"
  }
}
```

**Erreurs possibles :**
- `404` : User not found
- `404` : User has no fuel card
- `404` : Card plan not found
- `422` : Validation error (plan_id invalide)

---

## 🎯 Workflow Dashboard

### **Page : Liste des utilisateurs**

1. **Appeler** `GET /api/admin/users`
2. **Afficher** un tableau avec :
   - ID
   - Name
   - Email
   - Role
   - Card Level (Bronze/Silver/Gold)
   - Balance
   - Actions (View/Edit button)

**Exemple de tableau :**
```
┌────────────────────────────────────────────────────────────────────┐
│ ID          │ Name        │ Email              │ Card Level │ Balance │
├────────────────────────────────────────────────────────────────────┤
│ Obdo...U18  │ aya ouni    │ aouney282@...      │ Bronze     │ 150 TND │
│ p8fa...OZXh │ amine bou.. │ boulabba...        │ Admin      │ -       │
└────────────────────────────────────────────────────────────────────┘
```

---

### **Page : Détails utilisateur (View/Edit)**

1. **Appeler** `GET /api/admin/users/{userId}`
2. **Afficher** :
   - Infos utilisateur (name, email, phone, city)
   - Carte actuelle (card number, balance, current level)
   - Formulaire de changement de niveau

**Exemple de formulaire :**
```
┌─────────────────────────────────────────────────┐
│  User: aya ouni                                 │
│  Email: aouney282@gmail.com                     │
│  Phone: +216 12 345 678                         │
│                                                 │
│  Fuel Card                                      │
│  ─────────────────────────────────────────────  │
│  Card: **** **** **** 1234                      │
│  Balance: 150.50 TND                            │
│  Current Level: Bronze Card                     │
│                                                 │
│  Change Card Level:                             │
│  ○ Standard (Bronze Card)                       │
│  ○ Premium (Silver Card)                        │
│  ○ VIP (Gold Card)                              │
│                                                 │
│  [Update Card Level]                            │
└─────────────────────────────────────────────────┘
```

3. **Quand l'admin clique sur "Update"** :
   - Appeler `PUT /api/admin/users/{userId}/card-level`
   - Body : `{ "plan_id": "silver" }`
   - Afficher un message de succès
   - Recharger les données

---

## 🔄 Exemple Complet (JavaScript/Fetch)

### **1. Lister les utilisateurs**

```javascript
async function loadUsers() {
  const token = localStorage.getItem('admin_token');
  
  const response = await fetch('http://localhost:8000/api/admin/users', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  
  const data = await response.json();
  
  // Afficher les users dans un tableau
  data.users.forEach(user => {
    const cardLevel = user.cards[0]?.card_plan_name || 'No card';
    const balance = user.cards[0]?.balance || 0;
    
    console.log(`${user.name} - ${cardLevel} - ${balance} TND`);
  });
}
```

### **2. Voir les détails d'un user**

```javascript
async function viewUser(userId) {
  const token = localStorage.getItem('admin_token');
  
  const response = await fetch(`http://localhost:8000/api/admin/users/${userId}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  
  const data = await response.json();
  
  console.log('User:', data.user);
  console.log('Card:', data.cards[0]);
  console.log('Available plans:', data.available_plans);
}
```

### **3. Changer le niveau de carte**

```javascript
async function updateCardLevel(userId, planId) {
  const token = localStorage.getItem('admin_token');
  
  const response = await fetch(`http://localhost:8000/api/admin/users/${userId}/card-level`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      plan_id: planId // "bronze", "silver", ou "gold"
    })
  });
  
  const data = await response.json();
  
  if (response.ok) {
    alert(`Card level updated to ${data.plan.name}!`);
  } else {
    alert(`Error: ${data.message}`);
  }
}

// Exemple d'utilisation
updateCardLevel('ObdoMo9R1f00T9qo2U18', 'gold');
```

---

## ✅ Checklist Intégration

- [ ] Implémenter la page "Liste des utilisateurs"
- [ ] Implémenter la page "Détails utilisateur"
- [ ] Ajouter le formulaire de changement de niveau
- [ ] Gérer les erreurs (user not found, no card, etc.)
- [ ] Afficher des messages de succès/erreur
- [ ] Tester avec un utilisateur réel

---

## 🚀 Test Rapide

### **1. Créer un admin**
1. Va dans Firestore Console
2. Collection `users` → Sélectionne un user
3. Modifie `role: "user"` → `role: "admin"`

### **2. Tester l'API**

```bash
# 1. Login admin
POST http://localhost:8000/api/login
{
  "email": "admin@example.com",
  "password": "password"
}
# Récupère le token

# 2. Lister les users
GET http://localhost:8000/api/admin/users
Authorization: Bearer {token}

# 3. Voir un user
GET http://localhost:8000/api/admin/users/ObdoMo9R1f00T9qo2U18
Authorization: Bearer {token}

# 4. Changer le niveau
PUT http://localhost:8000/api/admin/users/ObdoMo9R1f00T9qo2U18/card-level
Authorization: Bearer {token}
{
  "plan_id": "gold"
}
```

---

**Les API sont prêtes ! Ton dashboard peut maintenant gérer les niveaux de cartes.** 🎉
