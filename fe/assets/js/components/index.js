/* ==========================================
   COMPONENT LIBRARY - INITIALIZATION
   ========================================== */

import Button from './button.js';
import Modal from './modal.js';

/**
 * Initialize all components on page load
 */
function initializeComponents() {
    // Initialize all buttons
    Button.initAll();

    // Initialize all modals
    Modal.initAll();

    // Add more component initializations here as needed
}

/**
 * Reinitialize components (useful for dynamically added content)
 */
function reinitializeComponents(container = document) {
    Button.initAll(container.querySelectorAll('.btn'));
    Modal.initAll(container.querySelectorAll('[data-modal]'));
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeComponents);
} else {
    initializeComponents();
}

// Export for manual use
export { initializeComponents, reinitializeComponents };
