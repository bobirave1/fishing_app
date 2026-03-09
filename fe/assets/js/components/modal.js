/* ==========================================
   MODAL/DIALOG COMPONENT CLASS
   ========================================== */

import Component from './component-base.js';
import DOMUtils from '../utilities/dom.js';

class Modal extends Component {
    constructor(element, options = {}) {
        super(options);
        this.element = element;
        this.config = {
            closeButton: true,
            clickOutsideCloses: true,
            animDuration: 300,
            ...options
        };
        this.state = {
            isOpen: false
        };
    }

    bindEvents() {
        // Close button
        if (this.config.closeButton) {
            const closeBtn = this.element.querySelector('[data-modal-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }
        }

        // Click outside closes
        if (this.config.clickOutsideCloses) {
            this.element.addEventListener('click', (e) => {
                if (e.target === this.element) {
                    this.close();
                }
            });
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.isOpen) {
                this.close();
            }
        });
    }

    open() {
        if (this.state.isOpen) return;
        
        this.state.isOpen = true;
        DOMUtils.addClass(this.element, 'modal--active');
        document.body.style.overflow = 'hidden';
        
        this.emit('modal:open');
    }

    close() {
        if (!this.state.isOpen) return;
        
        this.state.isOpen = false;
        DOMUtils.removeClass(this.element, 'modal--active');
        document.body.style.overflow = '';
        
        this.emit('modal:close');
    }

    toggle() {
        this.state.isOpen ? this.close() : this.open();
    }

    static initAll(selector = '[data-modal]') {
        DOMUtils.queryAll(selector).forEach(element => {
            if (!element.componentInstance) {
                const instance = new Modal(element);
                instance.init();
                element.componentInstance = instance;

                // Setup triggers
                const triggers = document.querySelectorAll(`[data-modal-target="${element.id}"]`);
                triggers.forEach(trigger => {
                    trigger.addEventListener('click', () => instance.open());
                });
            }
        });
    }
}

export default Modal;
