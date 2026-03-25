// Fonction pour basculer entre les onglets
function switchTab(tabName) {
    // Masquer tous les onglets
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });

    // Désactiver tous les boutons d'onglets
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });

    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Activer le bouton correspondant
    event.target.classList.add('active');
}

// Confirmation avant de refuser une fiche
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const action = e.submitter.getAttribute('value');
            if (action === 'refuser') {
                if (!confirm('Êtes-vous sûr de vouloir refuser cette fiche de frais ?')) {
                    e.preventDefault();
                }
            }
        });
    });
});
