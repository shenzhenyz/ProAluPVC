/**
 * Animations et transitions pour Pro Alu et PVC
 * Ce fichier contient toutes les animations et transitions utilisées sur le site
 */

document.addEventListener('DOMContentLoaded', function() {
    // Animation des éléments au scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                // Déterminer le type d'animation à appliquer
                if (element.classList.contains('fade-in')) {
                    element.classList.add('animated-fade-in');
                } else if (element.classList.contains('slide-up')) {
                    element.classList.add('animated-slide-up');
                } else if (element.classList.contains('slide-right')) {
                    element.classList.add('animated-slide-right');
                } else if (element.classList.contains('slide-left')) {
                    element.classList.add('animated-slide-left');
                } else if (element.classList.contains('zoom-in')) {
                    element.classList.add('animated-zoom-in');
                } else {
                    element.classList.add('animated');
                }
            }
        });
    };
    
    // Exécuter l'animation au chargement et au scroll
    animateOnScroll();
    window.addEventListener('scroll', animateOnScroll);
    
    // Animation des cartes de service au survol
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('service-card-hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('service-card-hover');
        });
    });
    
    // Animation des boutons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.classList.add('btn-hover-effect');
        });
        
        button.addEventListener('mouseleave', function() {
            this.classList.remove('btn-hover-effect');
        });
    });
    
    // Animation de la navbar au scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }
    
    // Animation des icônes de service
    const serviceIcons = document.querySelectorAll('.service-icon');
    serviceIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.classList.add('service-icon-pulse');
        });
        
        icon.addEventListener('mouseleave', function() {
            this.classList.remove('service-icon-pulse');
        });
    });
    
    // Animation des options de service dans le formulaire de devis
    const serviceOptions = document.querySelectorAll('.service-option');
    serviceOptions.forEach(option => {
        option.addEventListener('mouseenter', function() {
            this.classList.add('service-option-hover');
        });
        
        option.addEventListener('mouseleave', function() {
            this.classList.remove('service-option-hover');
        });
    });
    
    // Animation du formulaire de connexion
    const authCard = document.querySelector('.auth-card');
    if (authCard) {
        setTimeout(() => {
            authCard.classList.add('auth-card-visible');
        }, 100);
    }
});
