// Base de données utilisateurs (simulation)
const users = {
    'lvillachane': {
        password: 'jux7g',
        profile: 'Visiteur médical',
        firstName: 'Laurent',
        lastName: 'Villachane'
    },
    'comptable1': {
        password: 'comp123',
        profile: 'Comptable',
        firstName: 'Comptable',
        lastName: 'GSB'
    }
};

// Gestion de la connexion
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('errorMessage');
    
    // Vérification des identifiants
    if (users[username] && users[username].password === password) {
        // Connexion réussie
        const user = users[username];
        const sessionData = {
            username: username,
            profile: user.profile,
            firstName: user.firstName,
            lastName: user.lastName,
            connectedAt: new Date().toLocaleString('fr-FR')
        };
        
        // Sauvegarde en localStorage
        localStorage.setItem('userSession', JSON.stringify(sessionData));
        
        // Redirection vers l'espace connecté
        window.location.href = 'expense_report.html';
    } else {
        // Affichage du message d'erreur
        errorMessage.style.display = 'block';
        errorMessage.textContent = '❌ Identifiants invalides. Veuillez vérifier votre login et votre mot de passe.';
        document.getElementById('password').value = '';
    }
});

// Retour à la page de connexion au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si une session existe
    const userSession = localStorage.getItem('userSession');
    if (userSession) {
        // L'utilisateur est déjà connecté, le rediriger vers l'espace connecté
        window.location.href = 'expense_report.html';
    }
});
