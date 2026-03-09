/* ==========================================
   DOM UTILITIES MODULE
   ========================================== */

const DOMUtils = {
    /**
     * Query single element
     */
    query(selector) {
        return document.querySelector(selector);
    },

    /**
     * Query all elements
     */
    queryAll(selector) {
        return document.querySelectorAll(selector);
    },

    /**
     * Get element by ID
     */
    byId(id) {
        return document.getElementById(id);
    },

    /**
     * Create element with optional classes and attributes
     */
    create(tag, options = {}) {
        const element = document.createElement(tag);
        
        if (options.className) {
            element.className = options.className;
        }
        
        if (options.id) {
            element.id = options.id;
        }
        
        if (options.text) {
            element.textContent = options.text;
        }
        
        if (options.html) {
            element.innerHTML = options.html;
        }
        
        if (options.attributes) {
            Object.entries(options.attributes).forEach(([key, value]) => {
                element.setAttribute(key, value);
            });
        }
        
        if (options.events) {
            Object.entries(options.events).forEach(([event, handler]) => {
                element.addEventListener(event, handler);
            });
        }
        
        return element;
    },

    /**
     * Add class to element(s)
     */
    addClass(element, className) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.classList.add(className));
        } else {
            element.classList.add(className);
        }
    },

    /**
     * Remove class from element(s)
     */
    removeClass(element, className) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.classList.remove(className));
        } else {
            element.classList.remove(className);
        }
    },

    /**
     * Toggle class on element(s)
     */
    toggleClass(element, className) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.classList.toggle(className));
        } else {
            element.classList.toggle(className);
        }
    },

    /**
     * Check if element has class
     */
    hasClass(element, className) {
        return element.classList.contains(className);
    },

    /**
     * Show element(s)
     */
    show(element) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.style.display = '');
        } else {
            element.style.display = '';
        }
    },

    /**
     * Hide element(s)
     */
    hide(element) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.style.display = 'none');
        } else {
            element.style.display = 'none';
        }
    },

    /**
     * Set attributes on element
     */
    setAttr(element, attributes) {
        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });
    },

    /**
     * Get attribute from element
     */
    getAttr(element, attribute) {
        return element.getAttribute(attribute);
    },

    /**
     * Remove element from DOM
     */
    remove(element) {
        if (NodeList.prototype.isPrototypeOf(element) || Array.isArray(element)) {
            element.forEach(el => el.remove());
        } else {
            element.remove();
        }
    },

    /**
     * Add event listener(s) with delegation support
     */
    on(element, event, selector, handler) {
        if (typeof selector === 'function') {
            handler = selector;
            selector = null;
        }

        if (selector) {
            element.addEventListener(event, function(e) {
                if (e.target.matches(selector)) {
                    handler.call(e.target, e);
                }
            });
        } else {
            element.addEventListener(event, handler);
        }
    },

    /**
     * Trigger custom event
     */
    trigger(element, eventName, detail = {}) {
        const event = new CustomEvent(eventName, { detail });
        element.dispatchEvent(event);
    }
};

export default DOMUtils;
