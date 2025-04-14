document.addEventListener('DOMContentLoaded', function() {
    // Handle status updates for quotes
    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.quoteId;
            const status = this.dataset.status;
            updateQuoteStatus(quoteId, status);
        });
    });

    // Handle service form submission
    const serviceForm = document.getElementById('serviceForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitServiceForm(this);
        });
    }

    // Handle project form submission
    const projectForm = document.getElementById('projectForm');
    if (projectForm) {
        projectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitProjectForm(this);
        });
    }

    // Handle image preview
    document.querySelectorAll('.image-upload').forEach(input => {
        input.addEventListener('change', function() {
            previewImage(this);
        });
    });

    // Initialize delete confirmations
    initDeleteConfirmations();
});

// Update quote status
function updateQuoteStatus(quoteId, status) {
    fetch('../includes/update-quote-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            quote_id: quoteId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la mise à jour du statut');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}

// Submit service form
function submitServiceForm(form) {
    const formData = new FormData(form);
    
    fetch('../includes/process-service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Service enregistré avec succès');
            location.href = 'services.php';
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}

// Submit project form
function submitProjectForm(form) {
    const formData = new FormData(form);
    
    fetch('../includes/process-project.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Projet enregistré avec succès');
            location.href = 'projects.php';
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}

// Preview uploaded image
function previewImage(input) {
    const preview = document.getElementById(input.dataset.preview);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize delete confirmations
function initDeleteConfirmations() {
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
}

// Handle material comparison form
document.getElementById('comparisonForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../includes/process-comparison.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Comparatif enregistré avec succès');
            location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
});
