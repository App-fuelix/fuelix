<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - Fuelix</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Réinitialiser votre mot de passe</h1>
        
        <div id="message" class="hidden mb-4 p-4 rounded"></div>
        
        <form id="resetForm">
            <input type="hidden" id="token" name="token" value="{{ request('token') }}">
            <input type="hidden" id="email" name="email" value="{{ request('email') }}">
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Email</label>
                <input type="email" value="{{ request('email') }}" disabled 
                       class="w-full px-4 py-2 border rounded bg-gray-100">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       minlength="8">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 mb-2">Confirmer le mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       minlength="8">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600 transition">
                Réinitialiser le mot de passe
            </button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const messageDiv = document.getElementById('message');
            const button = e.target.querySelector('button');
            button.disabled = true;
            button.textContent = 'Traitement...';
            
            const data = {
                token: document.getElementById('token').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value
            };
            
            try {
                const response = await fetch('/api/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                messageDiv.classList.remove('hidden');
                if (response.ok) {
                    messageDiv.className = 'mb-4 p-4 rounded bg-green-100 text-green-700';
                    messageDiv.textContent = result.message;
                    document.getElementById('resetForm').reset();
                    
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                } else {
                    messageDiv.className = 'mb-4 p-4 rounded bg-red-100 text-red-700';
                    messageDiv.textContent = result.message || 'Une erreur est survenue';
                    button.disabled = false;
                    button.textContent = 'Réinitialiser le mot de passe';
                }
            } catch (error) {
                messageDiv.classList.remove('hidden');
                messageDiv.className = 'mb-4 p-4 rounded bg-red-100 text-red-700';
                messageDiv.textContent = 'Erreur de connexion au serveur';
                button.disabled = false;
                button.textContent = 'Réinitialiser le mot de passe';
            }
        });
    </script>
</body>
</html>
