/**
 * ToyKids - JavaScript
 * Gestion des interactions dynamiques et calculs
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎮 ToyKids - JavaScript chargé !');
    
    // Initialiser les fonctionnalités selon la page
    initContactForm();
    initOrderForm();
    initAnimations();
});

// ==========================================
// Gestion du formulaire de contact
// ==========================================
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageField && charCount) {
        // Compteur de caractères
        messageField.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 500;
            charCount.textContent = `${length} / ${maxLength} caractères`;
            
            if (length >= maxLength) {
                charCount.style.color = '#f5576c';
                this.value = this.value.substring(0, maxLength);
            } else if (length >= maxLength * 0.9) {
                charCount.style.color = '#FFA07A';
            } else {
                charCount.style.color = '#666';
            }
        });
    }
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const nom = document.getElementById('nom').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (nom === '' || email === '' || message === '') {
                e.preventDefault();
                alert('⚠️ Veuillez remplir tous les champs obligatoires !');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                alert('⚠️ Veuillez entrer une adresse email valide !');
                return false;
            }
            
            if (message.length < 10) {
                e.preventDefault();
                alert('⚠️ Le message doit contenir au moins 10 caractères !');
                return false;
            }
            
            return true;
        });
    }
}

// ==========================================
// Gestion du formulaire de commande
// ==========================================
function initOrderForm() {
    const orderForm = document.getElementById('orderForm');
    
    if (orderForm) {
        // Calculer le total au chargement
        calculateTotal();
        
        // Ajouter des écouteurs sur tous les champs de quantité
        const quantityInputs = document.querySelectorAll('input[name^="quantite_"]');
        quantityInputs.forEach(input => {
            input.addEventListener('change', calculateTotal);
            input.addEventListener('input', calculateTotal);
        });
        
        // Validation du formulaire
        orderForm.addEventListener('submit', function(e) {
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const email = document.getElementById('email').value.trim();
            const telephone = document.getElementById('telephone').value.trim();
            const adresse = document.getElementById('adresse').value.trim();
            
            // Vérifier les champs obligatoires
            if (!nom || !prenom || !email || !telephone || !adresse) {
                e.preventDefault();
                alert('⚠️ Veuillez remplir tous les champs obligatoires !');
                return false;
            }
            
            // Valider l'email
            if (!validateEmail(email)) {
                e.preventDefault();
                alert('⚠️ Veuillez entrer une adresse email valide !');
                return false;
            }
            
            // Vérifier qu'au moins un jouet est sélectionné
            const totalItems = parseInt(document.getElementById('totalItems').textContent);
            if (totalItems === 0) {
                e.preventDefault();
                alert('⚠️ Veuillez sélectionner au moins un jouet !');
                return false;
            }
            
            // Confirmation
            const totalPrice = document.getElementById('totalPrice').textContent;
            const confirmation = confirm(`🎁 Confirmez-vous votre commande de ${totalItems} article(s) pour un montant de ${totalPrice} € ?`);
            
            if (!confirmation) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
}

// ==========================================
// Calculer le total de la commande
// ==========================================
function calculateTotal() {
    const quantityInputs = document.querySelectorAll('input[name^="quantite_"]');
    let totalItems = 0;
    let totalPrice = 0;
    
    quantityInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        
        totalItems += quantity;
        totalPrice += quantity * price;
    });
    
    // Mettre à jour l'affichage
    const totalItemsElement = document.getElementById('totalItems');
    const totalPriceElement = document.getElementById('totalPrice');
    
    if (totalItemsElement) {
        totalItemsElement.textContent = totalItems;
    }
    
    if (totalPriceElement) {
        totalPriceElement.textContent = totalPrice.toFixed(2).replace('.', ',');
    }
    
    // Animation de mise à jour
    if (totalItemsElement && totalPriceElement) {
        totalItemsElement.style.transform = 'scale(1.2)';
        totalPriceElement.style.transform = 'scale(1.2)';
        
        setTimeout(() => {
            totalItemsElement.style.transform = 'scale(1)';
            totalPriceElement.style.transform = 'scale(1)';
        }, 200);
    }
}

// ==========================================
// Incrémenter la quantité
// ==========================================
function incrementQuantity(jouetId, maxStock) {
    const input = document.getElementById('quantite_' + jouetId);
    if (input) {
        let value = parseInt(input.value) || 0;
        if (value < maxStock) {
            input.value = value + 1;
            calculateTotal();
            
            // Animation
            input.style.backgroundColor = '#FFD89B';
            setTimeout(() => {
                input.style.backgroundColor = '';
            }, 200);
        } else {
            // Notification de stock maximum
            showNotification('⚠️ Stock maximum atteint pour ce jouet !', 'warning');
        }
    }
}

// ==========================================
// Décrémenter la quantité
// ==========================================
function decrementQuantity(jouetId) {
    const input = document.getElementById('quantite_' + jouetId);
    if (input) {
        let value = parseInt(input.value) || 0;
        if (value > 0) {
            input.value = value - 1;
            calculateTotal();
            
            // Animation
            input.style.backgroundColor = '#FFB6C1';
            setTimeout(() => {
                input.style.backgroundColor = '';
            }, 200);
        }
    }
}

// ==========================================
// Validation d'email
// ==========================================
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// ==========================================
// Animations au scroll
// ==========================================
function initAnimations() {
    // Observer pour les animations au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observer les cartes de produits
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        observer.observe(card);
    });
    
    // Observer les cartes about
    const aboutCards = document.querySelectorAll('.about-card');
    aboutCards.forEach(card => {
        observer.observe(card);
    });
    
    // Observer les cartes de catégories
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        observer.observe(card);
    });
}

// ==========================================
// Afficher une notification
// ==========================================
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.textContent = message;
    
    // Styles
    notification.style.position = 'fixed';
    notification.style.top = '100px';
    notification.style.right = '20px';
    notification.style.padding = '20px 30px';
    notification.style.borderRadius = '15px';
    notification.style.fontSize = '1.1em';
    notification.style.fontWeight = '600';
    notification.style.zIndex = '10000';
    notification.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';
    notification.style.animation = 'slideInRight 0.3s ease';
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)';
        notification.style.color = 'white';
    } else if (type === 'warning') {
        notification.style.background = 'linear-gradient(135deg, #FFD89B 0%, #FFA07A 100%)';
        notification.style.color = '#333';
    } else if (type === 'error') {
        notification.style.background = 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)';
        notification.style.color = '#d63447';
    } else {
        notification.style.background = 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)';
        notification.style.color = '#764ba2';
    }
    
    // Ajouter au body
    document.body.appendChild(notification);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// ==========================================
// Animation CSS pour les notifications
// ==========================================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ==========================================
// Smooth scroll pour les ancres
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ==========================================
// Menu responsive (optionnel)
// ==========================================
function initResponsiveMenu() {
    const navbar = document.querySelector('.nav-menu');
    const menuToggle = document.createElement('button');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '☰';
    menuToggle.style.display = 'none';
    menuToggle.style.fontSize = '2em';
    menuToggle.style.background = 'transparent';
    menuToggle.style.border = 'none';
    menuToggle.style.color = 'white';
    menuToggle.style.cursor = 'pointer';
    
    // Ajouter le bouton avant le menu
    if (navbar) {
        navbar.parentElement.insertBefore(menuToggle, navbar);
        
        menuToggle.addEventListener('click', function() {
            navbar.classList.toggle('active');
        });
        
        // Responsive
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                menuToggle.style.display = 'block';
            } else {
                menuToggle.style.display = 'none';
                navbar.classList.remove('active');
            }
        });
        
        // Vérifier au chargement
        if (window.innerWidth <= 768) {
            menuToggle.style.display = 'block';
        }
    }
}

// Initialiser le menu responsive
initResponsiveMenu();

// ==========================================
// Console log stylisé
// ==========================================
console.log('%c🎨 ToyKids - Magasin de Jouets 🎈', 'font-size: 20px; color: #FF6B9D; font-weight: bold;');
console.log('%cDéveloppé avec ❤️ pour les enfants', 'font-size: 14px; color: #667eea;');
