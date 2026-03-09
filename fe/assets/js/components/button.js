/* ==========================================
   BUTTON COMPONENT CLASS
   ========================================== */

import Component from './component-base.js';
import DOMUtils from '../utilities/dom.js';

class Button extends Component {
    constructor(element, options = {}) {
        super(options);
        this.element = element;
        this.config = {
            type: 'button', // button, submit, reset
            variant: 'primary', // primary, secondary, accent, success, danger, outline, ghost
            size: 'md', // sm, md, lg
            disabled: false,
            loading: false,
            ...options
        };
        this.state = {
            isLoading: false,
            isDisabled: false
        };
    }

    bindEvents() {
        this.element.addEventListener('click', (e) => {
            if (this.state.isDisabled || this.state.isLoading) {
                e.preventDefault();
            }
        });
    }

    render() {
        // Update button classes based on state
        this.updateClasses();
    }

    updateClasses() {
        const classList = ['btn', `btn--${this.config.variant}`, `btn--${this.config.size}`];

        if (this.state.isLoading) {
            classList.push('btn--loading');
        }

        if (this.state.isDisabled) {
            this.element.setAttribute('disabled', 'disabled');
        } else {
            this.element.removeAttribute('disabled');
        }

        this.element.className = classList.join(' ');
    }

    setLoading(loading) {
        this.setState({ isLoading: loading });
        this.element.disabled = loading;
    }

    setDisabled(disabled) {
        this.setState({ isDisabled: disabled });
    }

    enable() {
        this.setDisabled(false);
    }

    disable() {
        this.setDisabled(true);
    }

    static initAll(selector = '.btn') {
        DOMUtils.queryAll(selector).forEach(element => {
            if (!element.componentInstance) {
                const instance = new Button(element);
                instance.init();
                element.componentInstance = instance;
            }
        });
    }
}

export default Button;
