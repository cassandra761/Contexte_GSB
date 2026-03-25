// Gestion des onglets
function switchTab(tabName) {
    // Masquer tous les onglets
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Désactiver tous les boutons d'onglet
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));

    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Activer le bouton d'onglet correspondant
    const selectedButton = Array.from(tabButtons).find(button =>
        button.textContent.toLowerCase().includes(tabName.toLowerCase().replace('-', ' '))
    );
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

// Gestion du formulaire d'ajout d'utilisateur
function showAddUserForm() {
    document.getElementById('addUserForm').style.display = 'block';
}

function hideAddUserForm() {
    document.getElementById('addUserForm').style.display = 'none';
}

// Gestion des utilisateurs
function editUser(type, id) {
    alert('Fonctionnalité de modification à implémenter. Type: ' + type + ', ID: ' + id);
}

function deleteUser(type, id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        // Créer un formulaire temporaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_user';

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'user_type';
        typeInput.value = type;

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'user_id';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(typeInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Gestion de la base de données
function viewTable(tableName) {
    // Ouvrir une nouvelle fenêtre ou onglet pour voir le contenu de la table
    window.open('admin.php?view_table=' + encodeURIComponent(tableName), '_blank');
}

function showTableStructure(tableName) {
    // Ouvrir une nouvelle fenêtre ou onglet pour voir la structure de la table
    window.open('admin.php?show_structure=' + encodeURIComponent(tableName), '_blank');
}