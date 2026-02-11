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
document.getElementById('loginForm').addEventListener('submit', function(e) { // Empêcher le rechargement de la page
    e.preventDefault();
    
    const username = document.getElementById('username').value; // Récupération du nom d'utilisateur
    const password = document.getElementById('password').value; // Récupération du mot de passe
    const errorMessage = document.getElementById('errorMessage'); // Élément pour afficher les messages d'erreur
    
    // Vérification des identifiants
    if (users[username] && users[username].password === password) { // Si les identifiants sont corrects
        // Connexion réussie
        const user = users[username]; // Récupération des données de l'utilisateur
        const sessionData = { // Création de la session utilisateur
            username: username, // Stockage du nom d'utilisateur
            profile: user.profile, // Stockage du profil de l'utilisateur
            firstName: user.firstName, // Stockage du prénom de l'utilisateur
            lastName: user.lastName, // Stockage du nom de famille de l'utilisateur
            connectedAt: new Date().toLocaleString('fr-FR') // Stockage de la date et heure de connexion
        };
        
        // Sauvegarde en localStorage
        localStorage.setItem('userSession', JSON.stringify(sessionData)); // Stockage de la session dans le localStorage
        
        // Redirection vers l'espace connecté
        window.location.href = 'expense_report.html'; // Redirection vers la page de l'espace connecté
    } else {
        // Affichage du message d'erreur
        errorMessage.style.display = 'block'; // Affichage du message d'erreur
        errorMessage.textContent = '❌ Identifiants invalides. Veuillez vérifier votre login et votre mot de passe.'; // Message d'erreur pour identifiants invalides
        document.getElementById('password').value = ''; // Réinitialisation du champ mot de passe
    }
});

// Retour à la page de connexion au chargement
document.addEventListener('DOMContentLoaded', function() { // Lorsque la page est chargée
    // Vérifier si une session existe
    const userSession = localStorage.getItem('userSession'); // Récupération de la session utilisateur depuis le localStorage
    if (userSession) { // Si une session existe
        // L'utilisateur est déjà connecté, le rediriger vers l'espace connecté
        window.location.href = 'expense_report.html'; // Redirection vers la page de l'espace connecté
    }
});
