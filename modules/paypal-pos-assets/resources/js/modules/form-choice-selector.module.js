/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';

// Global String format helper
String.prototype.format = function () {
    return [...arguments].reduce((p,c) => p.replace(/%s/,c), this);
};

export default class FormChoiceSelector {
    constructor(element, options)
    {
        this._defaults = {
            activeCls: 'active',
            disabledCls: 'disabled',
            triggerElSelector: '.form-choice-selector',
            radioButtonSelector: 'input[type="radio"]'
        };

        // Extend the default El Selector and ignore all with the disabled class
        this._defaults.triggerElSelector = "%s:not(.%s)".format(
            this._defaults.triggerElSelector,
            this._defaults.disabledCls
        );

        this.options = extendDefaults(this._defaults, options);

        this.el = element || null;

        if (this.el === null) {
            return;
        }

        this.init();
    }

    init()
    {
        this.registerEvents();
    }

    registerEvents()
    {
        let triggerableEls = this.el.querySelectorAll(this.options.triggerElSelector);

        window.addEventListener('load', () => this.onLoad(triggerableEls));

        triggerableEls.forEach(triggerableEl => {
            triggerableEl.addEventListener('click', (e) => {
                this.onClick(e, triggerableEl);
            });
        });
    }

    /**
     * @param {NodeList} triggerableEls
     */
    onLoad(triggerableEls)
    {
        let hasActiveSelector = [...triggerableEls].some(item => item.classList.contains(this.options.activeCls));

        if (triggerableEls.length > 1 && !hasActiveSelector) {
            this.setActiveFromSelect(triggerableEls);
        }

        triggerableEls.forEach(triggerEl => {
            const checkedRadioInput = triggerEl.querySelector('input[type="radio"]:checked') || null;

            if (checkedRadioInput !== null) {
                triggerEl.classList.add(this.options.activeCls);
            }
        });
    }

    /**
     * @param {Event} e
     * @param {Element} element
     */
    onClick(e, element)
    {
        if (element.classList.contains(this.options.activeCls)) {
            return;
        }

        if (element.classList.contains(this.options.disabledCls)) {
            return;
        }

        this.triggerEl(element);
    }

    /**
     * @param {Element} element
     */
    triggerEl(element)
    {
        let triggerEls = this.el.querySelectorAll(this.options.triggerElSelector);

        triggerEls.forEach(triggerEl => {
            if (triggerEl.classList.contains(this.options.activeCls)) {
                triggerEl.classList.remove(this.options.activeCls);
            }
        });

        element.classList.add(this.options.activeCls);

        this.toggleRadioInput(element);
    }

    /**
     * @param {Element} element
     */
    toggleRadioInput(element)
    {
        let triggerEls = this.el.querySelectorAll(this.options.triggerElSelector);

        triggerEls.forEach(triggerEl => {
            const checkedRadioInputs = triggerEl.querySelectorAll(this.options.radioButtonSelector);

            checkedRadioInputs.forEach(checkedRadioInput => {
                checkedRadioInput.removeAttribute('checked');
                checkedRadioInput.checked = false;
            });
        });

        const radioInput = element.querySelector(this.options.radioButtonSelector) || null;

        if (radioInput !== null) {
            radioInput.checked = true;
            radioInput.setAttribute('checked', '');
        }
    }

    /**
     * @param {NodeList} elements
     */
    setActiveFromSelect(elements)
    {
        const element = elements.item(0);
        const inputElement = element.querySelector('input');
        const selectElement = document.querySelector('select[name="' + inputElement.name + '"]') || null;

        if (selectElement === null) {
            return;
        }

        const selectedOption = selectElement.options[selectElement.options.selectedIndex];

        elements.forEach(element => {
            const inputEl = element.querySelector(this.options.radioButtonSelector) || null;

            if (inputEl !== null) {
                if (inputEl.value === selectedOption.value) {
                    inputEl.checked = true;
                    inputEl.setAttribute('checked', '');
                }
            }
        });
    }

    /**
     * @param {Element} element
     */
    setElementActive(element)
    {
        const inputEl = element.querySelector(this.options.radioButtonSelector) || null;

        if (inputEl !== null) {
            inputEl.checked = true;
            inputEl.setAttribute('checked', '');
        }
    }
}