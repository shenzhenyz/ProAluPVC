document.addEventListener('DOMContentLoaded', function() {
    // Load Services
    loadServices();
    
    // Load Gallery
    loadGallery();
    
    // Initialize Google Maps
    if (document.querySelector('.map')) {
        window.initMap = initMap;
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Navbar scroll behavior
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });
    
    // Animate elements on scroll
    animateOnScroll();
    
    // Form validation
    initFormValidation();
});

// Load Services from API
async function loadServices() {
    try {
        const response = await fetch('api/services.php');
        const services = await response.json();
        
        const servicesContainer = document.querySelector('#services .row');
        if (!servicesContainer) return;
        
        // If no services are returned or there's an error, use default services
        if (!services || services.length === 0) {
            const defaultServices = [
                {
                    id: 1,
                    title: 'Fenêtres en PVC',
                    description: 'Fenêtres en PVC de haute qualité, offrant une excellente isolation thermique et acoustique.',
                    icon: 'bi-window'
                },
                {
                    id: 2,
                    title: 'Portes en Aluminium',
                    description: 'Portes en aluminium élégantes et durables, parfaites pour les entrées principales et secondaires.',
                    icon: 'bi-door-open'
                },
                {
                    id: 3,
                    title: 'Vérandas',
                    description: 'Vérandas sur mesure pour agrandir votre espace de vie et profiter de la lumière naturelle.',
                    icon: 'bi-house-add'
                },
                {
                    id: 4,
                    title: 'Volets Roulants',
                    description: 'Volets roulants motorisés ou manuels pour une meilleure sécurité et isolation.',
                    icon: 'bi-layout-sidebar'
                },
                {
                    id: 5,
                    title: 'Garde-corps',
                    description: 'Garde-corps en aluminium ou en verre pour balcons, terrasses et escaliers.',
                    icon: 'bi-border-style'
                },
                {
                    id: 6,
                    title: 'Murs Rideaux',
                    description: 'Murs rideaux en aluminium et verre pour façades commerciales et résidentielles.',
                    icon: 'bi-building'
                }
            ];
            renderServices(defaultServices);
        } else {
            renderServices(services);
        }
        
        // Add animation to service cards
        const serviceCards = document.querySelectorAll('.service-card');
        serviceCards.forEach((card, index) => {
            card.classList.add('animate-on-scroll');
            card.dataset.animation = 'fadeIn';
            card.dataset.delay = (index * 0.1) + 's';
        });
        
    } catch (error) {
        console.error('Error loading services:', error);
        // Use default services if API fails
        const defaultServices = [
            {
                id: 1,
                title: 'Fenêtres en PVC',
                description: 'Fenêtres en PVC de haute qualité, offrant une excellente isolation thermique et acoustique.',
                icon: 'bi-window'
            },
            {
                id: 2,
                title: 'Portes en Aluminium',
                description: 'Portes en aluminium élégantes et durables, parfaites pour les entrées principales et secondaires.',
                icon: 'bi-door-open'
            },
            {
                id: 3,
                title: 'Vérandas',
                description: 'Vérandas sur mesure pour agrandir votre espace de vie et profiter de la lumière naturelle.',
                icon: 'bi-house-add'
            },
            {
                id: 4,
                title: 'Volets Roulants',
                description: 'Volets roulants motorisés ou manuels pour une meilleure sécurité et isolation.',
                icon: 'bi-layout-sidebar'
            },
            {
                id: 5,
                title: 'Garde-corps',
                description: 'Garde-corps en aluminium ou en verre pour balcons, terrasses et escaliers.',
                icon: 'bi-border-style'
            },
            {
                id: 6,
                title: 'Murs Rideaux',
                description: 'Murs rideaux en aluminium et verre pour façades commerciales et résidentielles.',
                icon: 'bi-building'
            }
        ];
        renderServices(defaultServices);
    }
}

// Render services to the DOM
function renderServices(services) {
    const servicesContainer = document.querySelector('#services .row');
    if (!servicesContainer) return;
    
    servicesContainer.innerHTML = '';
    
    services.forEach((service, index) => {
        const serviceCard = `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="service-card" data-animation="fadeIn" data-delay="${index * 0.1}s">
                    <div class="service-icon">
                        <i class="bi ${service.icon || 'bi-tools'}"></i>
                    </div>
                    <h3>${service.title}</h3>
                    <p>${service.description}</p>
                    <a href="devis.php?service=${service.id}" class="btn btn-primary">Demander un devis</a>
                </div>
            </div>
        `;
        servicesContainer.insertAdjacentHTML('beforeend', serviceCard);
    });
}

// Load Gallery from API
async function loadGallery() {
    try {
        const response = await fetch('api/gallery.php');
        const projects = await response.json();
        
        const galleryContainer = document.querySelector('.gallery');
        if (!galleryContainer) return;
        
        // Display only the latest 6 projects on the homepage
        const recentProjects = projects.slice(0, 6);
        
        recentProjects.forEach((project, index) => {
            const galleryItem = `
                <div class="col-md-6 col-lg-4">
                    <div class="gallery-item animate-on-scroll" data-animation="${index % 2 === 0 ? 'slideInLeft' : 'slideInRight'}" data-delay="${index * 0.1}s">
                        <img src="${project.image_path}" alt="${project.title}" class="img-fluid">
                        <div class="gallery-overlay">
                            <h3>${project.title}</h3>
                            <p>${project.category}</p>
                        </div>
                    </div>
                </div>
            `;
            galleryContainer.insertAdjacentHTML('beforeend', galleryItem);
        });
        
        // If no projects are loaded, show placeholder
        if (recentProjects.length === 0) {
            const placeholders = [
                { title: 'Fenêtres PVC', category: 'Résidentiel', image: 'assets/images/projects/windows.jpg' },
                { title: 'Porte d\'entrée', category: 'Résidentiel', image: 'assets/images/projects/door.jpg' },
                { title: 'Véranda moderne', category: 'Extension', image: 'assets/images/projects/veranda.jpg' }
            ];
            
            placeholders.forEach((project, index) => {
                const galleryItem = `
                    <div class="col-md-6 col-lg-4">
                        <div class="gallery-item animate-on-scroll" data-animation="${index % 2 === 0 ? 'slideInLeft' : 'slideInRight'}" data-delay="${index * 0.1}s">
                            <img src="${project.image}" alt="${project.title}" class="img-fluid">
                            <div class="gallery-overlay">
                                <h3>${project.title}</h3>
                                <p>${project.category}</p>
                            </div>
                        </div>
                    </div>
                `;
                galleryContainer.insertAdjacentHTML('beforeend', galleryItem);
            });
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
    }
}

// Initialize Google Maps
function initMap() {
    try {
        const location = { lat: 36.7213, lng: 3.0501 }; // Coordonnées pour Birkhadem, Alger
        const mapElement = document.querySelector('.map');
        
        if (!mapElement) {
            console.error('Map container not found');
            return;
        }
        
        const map = new google.maps.Map(mapElement, {
            zoom: 15,
            center: location,
            styles: [
                {
                    "featureType": "all",
                    "elementType": "geometry",
                    "stylers": [{"saturation": -80}]
                },
                {
                    "featureType": "road",
                    "elementType": "labels",
                    "stylers": [{"visibility": "off"}]
                }
            ]
        });
        
        const marker = new google.maps.Marker({
            position: location,
            map: map,
            title: 'Pro Alu et PVC',
            animation: google.maps.Animation.DROP
        });
        
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h5 style="margin: 0 0 5px;">Pro Alu et PVC</h5>
                    <p style="margin: 0;">Lotissement El Salem 3, Villa N°1, Tahar Bouchet BirKhadem, ALGER 16000</p>
                </div>
            `
        });
        
        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });
        
        // Open info window by default
        infoWindow.open(map, marker);
    } catch (error) {
        console.error('Error initializing Google Maps:', error);
        const mapElement = document.querySelector('.map');
        if (mapElement) {
            mapElement.innerHTML = `
                <div class="map-error">
                    <p><i class="bi bi-exclamation-triangle"></i> Impossible de charger la carte. Veuillez réessayer plus tard.</p>
                </div>
            `;
        }
    }
}

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Animate elements on scroll
function animateOnScroll() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const animation = el.dataset.animation || 'fadeIn';
                const delay = el.dataset.delay || '0s';
                
                el.style.animationDelay = delay;
                el.classList.add(animation);
                el.classList.add('animated');
                
                observer.unobserve(el);
            }
        });
    }, {
        threshold: 0.1
    });
    
    elements.forEach(element => {
        observer.observe(element);
    });
}

// Handle quote request form submission
document.querySelector('#quoteForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Add service options to the service dropdown if it's empty
    const serviceSelect = document.querySelector('#service');
    if (serviceSelect && serviceSelect.options.length <= 1) {
        const defaultServices = [
            { id: 1, name: 'Fenêtres en PVC' },
            { id: 2, name: 'Portes en Aluminium' },
            { id: 3, name: 'Vérandas' },
            { id: 4, name: 'Volets Roulants' },
            { id: 5, name: 'Garde-corps' },
            { id: 6, name: 'Murs Rideaux' }
        ];
        
        defaultServices.forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.name;
            serviceSelect.appendChild(option);
        });
    }
    
    fetch('includes/process-quote.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Votre demande de devis a été envoyée avec succès!');
            this.reset();
        } else {
            alert('Une erreur est survenue. Veuillez réessayer.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue. Veuillez réessayer.');
    });
});
