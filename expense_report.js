let horsForfaitList = [];

// Simple tab switcher
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));

    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
}

// Initialiser la fiche du mois en cours et charger les données
document.addEventListener('DOMContentLoaded', () => {
    const month = new Date().toISOString().slice(0,7);
    document.getElementById('ficheMonth').value = month;
    loadFiche(month);
});

// Charger les données de la fiche du mois sélectionné
function loadFiche(month) {
    document.getElementById('forfait_etape').value = 0;
    document.getElementById('forfait_km').value = 0;
    document.getElementById('forfait_nuitees').value = 0;
    document.getElementById('forfait_repas').value = 0;

    renderHF([]);
}

// Fonction pour sauvegarder les données forfaitaires
function saveForfaits() {
    const month = document.getElementById('ficheMonth').value;

    const forfait_etape = +document.getElementById('forfait_etape').value;
    const forfait_km = +document.getElementById('forfait_km').value;
    const forfait_nuitees = +document.getElementById('forfait_nuitees').value;
    const forfait_repas = +document.getElementById('forfait_repas').value;

    document.getElementById('saveForfaitsMois').value = month;
    document.getElementById('hidden_forfait_etape').value = forfait_etape;
    document.getElementById('hidden_forfait_km').value = forfait_km;
    document.getElementById('hidden_forfait_nuitees').value = forfait_nuitees;
    document.getElementById('hidden_forfait_repas').value = forfait_repas;
    document.getElementById('hidden_nbJustificatif').value = 0;

    document.getElementById('saveForfaitsForm').submit();
}

// Fonctions pour gérer les frais hors forfait
function renderHF(list) {
    document.getElementById('hfBody').innerHTML = list.map((hf,i) => `
        <tr>
            <td>${hf.date}</td>
            <td>${hf.libelle}</td>
            <td>${hf.montant.toFixed(2)} €</td>
            <td><button onclick="delHF(${i})">X</button></td>
        </tr>
    `).join('');
}

function addHorsForfait(e) {
    e.preventDefault();

    const month = document.getElementById('ficheMonth').value;
    const date = document.getElementById('hfDate').value;
    const libelle = document.getElementById('hfLabel').value;
    const montant = +document.getElementById('hfAmount').value;

    if (!date || !libelle || montant <= 0) {
        alert('Données invalides');
        return false;
    }

    // Ajouter dans le tableau JS
    horsForfaitList.push({
        date: date,
        libelle: libelle,
        montant: montant
    });

    // Réafficher
    renderHF(horsForfaitList);

    // reset form
    document.getElementById('hfForm').reset();

    return false; // empêche envoi direct
}

function delHF(i) {
    const month = document.getElementById('ficheMonth').value;
    const fiche = getFiche(month);

    fiche.horsForfait.splice(i,1);
    setFiche(month, fiche);

    renderHF(fiche.horsForfait);
}