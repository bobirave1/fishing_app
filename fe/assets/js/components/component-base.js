/* ==========================================
   COMPONENT BASE CLASS
   ========================================== */

/**
 * Base class for all UI components
 * Provides common component lifecycle and utilities
 */
class Component {
    constructor(options = {}) {
        this.element = options.element || null;
        this.config = options.config || {};
        this.state = options.state || {};
        this.initialized = false;
    }

    /**
     * Initialize component
     */
    init() {
        if (this.initialized) return;
        this.bindEvents();
        this.render();
        this.initialized = true;
        this.onInit();
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Override in subclasses
    }

    /**
     * Render component
     */
    render() {
        // Override in subclasses
    }

    /**
     * Called after initialization
     */
    onInit() {
        // Override in subclasses
    }

    /**
     * Update component state
     */
    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.render();
    }

    /**
     * Get current state
     */
    getState() {
        return { ...this.state };
    }

    /**
     * Update component configuration
     */
    setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }

    /**
     * Destroy component
     */
    destroy() {
        this.unbindEvents();
        this.element = null;
        this.initialized = false;
    }

    /**
     * Unbind event listeners
     */
    unbindEvents() {
        // Override in subclasses
    }

    /**
     * Emit custom event
     */
    emit(eventName, detail = {}) {
        const event = new CustomEvent(eventName, { 
            detail,
            bubbles: true,
            cancelable: true 
        });
        this.element.dispatchEvent(event);
    }

    /**
     * Log with component name prefix
     */
    log(message, data = null) {
        console.log(`[${this.constructor.name}] ${message}`, data || '');
    }

    /**
     * Error logging
     */
    error(message, data = null) {
        console.error(`[${this.constructor.name}] ${message}`, data || '');
    }
}

export default Component;
