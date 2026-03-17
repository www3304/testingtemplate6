// Dynamic Site Configuration and Enhanced Functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize dynamic theming
    initializeDynamicTheming();
    
    // Initialize enhanced floating buttons
    initializeFloatingButtons();
    
    // Initialize dynamic sections with enhanced interactions
    initializeDynamicSections();
    
    // Initialize smooth scrolling for anchor links
    initializeSmoothScrolling();
    
    // Initialize responsive behavior
    initializeResponsiveBehavior();
});

/**
 * Initialize dynamic theming based on database settings
 */
function initializeDynamicTheming() {
    if (typeof window.siteSettings !== 'undefined') {
        const settings = window.siteSettings;
        
        // Set CSS custom properties for dynamic theming
        const root = document.documentElement;
        
        // Background colors
        root.style.setProperty('--primary-bg-color', settings.primaryBgColor);
        root.style.setProperty('--secondary-bg-color', settings.secondaryBgColor);
        
        // Header colors
        root.style.setProperty('--header-bg-start', settings.headerBgStart);
        root.style.setProperty('--header-bg-end', settings.headerBgEnd);
        
        // Footer colors
        root.style.setProperty('--footer-bg-start', settings.footerBgStart);
        root.style.setProperty('--footer-bg-end', settings.footerBgEnd);
        
        // Component colors
        root.style.setProperty('--card-bg-color', settings.cardBgColor);
        root.style.setProperty('--text-color', settings.textColor);
        root.style.setProperty('--accent-color', settings.accentColor);
        root.style.setProperty('--border-color', settings.borderColor);
        
        console.log('Dynamic theming initialized with database settings');
    } else {
        console.log('Using default theme settings');
    }
}

/**
 * Initialize enhanced floating buttons with dynamic positioning and interactions
 */
function initializeFloatingButtons() {
    if (typeof window.siteSettings !== 'undefined' && window.siteSettings.floatingButtons) {
        const buttons = window.siteSettings.floatingButtons;
        
        // Remove existing floating buttons (if any)
        const existingButtons = document.querySelectorAll('[class^="floating-btn-"]');
        existingButtons.forEach(btn => btn.remove());
        
        // Create enhanced floating buttons
        buttons.forEach((button, index) => {
            if (button.link && button.image) {
                createFloatingButton(button, index, buttons.length);
            }
        });
        
        console.log(`Initialized ${buttons.length} floating buttons`);
    }
}

/**
 * Initialize dynamic sections with enhanced interactions
 */
function initializeDynamicSections() {
    // Add hover effects and animations to dynamic sections
    const dynamicSections = document.querySelectorAll('main > div[style*="flex: 1 1 300px"]');
    
    dynamicSections.forEach((section, index) => {
        // Add staggered animation on page load
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
        
        // Add enhanced hover effects
        section.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.4)';
        });
        
        section.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.3)';
        });
    });
    
    console.log(`Enhanced ${dynamicSections.length} dynamic sections`);
}

/**
 * Create a single floating button with enhanced functionality
 */
const topValues = [72, 80]; // Custom positions for first N buttons

function createFloatingButton(button, index, totalButtons) {
    const buttonElement = document.createElement('a');
    buttonElement.href = button.link;
    buttonElement.className = `floating-btn floating-btn-${index}`;

    // Smart positioning: use custom value if available, else continue with 8% spacing
    let top;
    if (topValues[index] !== undefined) {
        top = topValues[index];
    } else {
        // Continue the pattern: 72, 80, 88, 96, 104, etc. (8% spacing)
        top = 80 - (index * 8);
    }

    buttonElement.style.cssText = `
        position: fixed;
        right: 0;
        top: ${top}%;
        transform: translateY(-50%);
        width: 60px;
        height: auto;
        animation: moveUpDown ${3 + index}s ease-in-out infinite;
        filter: drop-shadow(0 0 5px var(--border-color, white));
        transition: all 0.3s ease;
        cursor: pointer;
        z-index: 1001;
    `;

    const img = document.createElement('img');
    img.src = button.image;
    img.alt = `Floating Button ${index + 1}`;
    img.style.cssText = 'width: 100%; height: auto; display: block;';

    img.onerror = function() {
        this.style.display = 'none';
    };

    buttonElement.appendChild(img);

    // Hover effects
    buttonElement.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-50%) scale(1.1)';
        this.style.filter = 'drop-shadow(0 0 8px var(--accent-color, #F6D02C))';
    });
    buttonElement.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(-50%) scale(1)';
        this.style.filter = 'drop-shadow(0 0 5px var(--border-color, white))';
    });

    document.body.appendChild(buttonElement);
}

/**
 * Calculate optimal position for floating buttons
 */
function calculateButtonPosition(index, totalButtons) {
    // Start at 20% from top, end at 80% from top
    const startPosition = 20;
    const endPosition = 80;
    const spacing = (endPosition - startPosition) / (totalButtons - 1 || 1);
    return startPosition + (index * spacing);
}

/**
 * Initialize smooth scrolling for anchor links
 */
function initializeSmoothScrolling() {
    // Add smooth scrolling to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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
}

/**
 * Initialize responsive behavior
 */
function initializeResponsiveBehavior() {
    // Handle mobile menu toggle (if needed in future)
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('active');
            }
        });
    }
    
    // Handle window resize for floating buttons
    window.addEventListener('resize', function() {
        // Recalculate floating button positions on resize
        if (typeof window.siteSettings !== 'undefined' && window.siteSettings.floatingButtons) {
            const buttons = window.siteSettings.floatingButtons;
            buttons.forEach((button, index) => {
                const buttonElement = document.querySelector(`.floating-btn-${index}`);
                if (buttonElement) {
                    buttonElement.style.top = `${calculateButtonPosition(index, buttons.length)}%`;
                }
            });
        }
    });
}

/**
 * Utility function to debounce function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Utility function to check if element is in viewport
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Add scroll-based animations (optional enhancement)
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all cards and sections for animation
    document.querySelectorAll('.card, main > div').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Initialize scroll animations when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add a small delay to ensure all elements are rendered
    setTimeout(initializeScrollAnimations, 100);
});

// Export functions for potential external use
window.SiteManager = {
    initializeDynamicTheming,
    initializeFloatingButtons,
    initializeDynamicSections,
    createFloatingButton,
    calculateButtonPosition,
    isInViewport,
    debounce
};
