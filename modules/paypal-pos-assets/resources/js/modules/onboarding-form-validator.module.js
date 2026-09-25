/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';
import {flatten} from "../helper/flatten.helper";

export default class OnboardingFormValidator {
    /**
     * Error label position: insert after the input field.
     * @return {string}
     * @const
     */
    static get POSITION_AFTER_INPUT_FIELD()
    {
        return 'after_input_field';
    }

    /**
     * Error label position: insert to the parent container specified in selector property.
     * @return {string}
     * @const
     */
    static get POSITION_IN_CLOSEST_SELECTOR()
    {
        return 'closest';
    }

    /**
     * @param {Element} element The element (such as form) containing input fields
     * and submit button.
     * @param {object} rules The rules for validation in
     * {inputName: {ruleName: {message: '...', param1: foo}} format.
     * @param {object} options
     */
    constructor(element, rules, options)
    {
        this._defaults = {
            proceedActionButtonSelector: `button.btn-primary[name='save']`,
            errorLabel: {
                class: 'validation-error',
                position: {
                    type: OnboardingFormValidator.POSITION_AFTER_INPUT_FIELD,
                }
            },
            baseUrl: window.location.origin,
        };

        this.options = extendDefaults(this._defaults, options);

        this.el = element;
        if (!this.el) {
            return;
        }

        this.rules = rules;

        this.isValid = null;

        const button = this.getActionButton(this.options.proceedActionButtonSelector);

        if (!button) {
            console.log(this.options.proceedActionButtonSelector + ' not found');
            return;
        }
        button.addEventListener('click', e => this.onClick(e, this.el));
    }

    /**
     * @param {Event} e
     * @param {Element} element
     */
    onClick(e, element)
    {
        if (this.isValid) {
            return;
        }

        e.preventDefault();

        this.validate()
            .then(errors => {
                if (!errors.length) {
                    this.isValid = true;
                    this.submitActionButton(this.options.proceedActionButtonSelector);
                    return;
                }

                this.isValid = false;

                this.removeErrorLabels(this.el)

                for (const error of errors) {
                    this.addErrorLabel(error.element, error.rule.parameters.message);
                }

                errors[0].element.focus();
            })
            .catch(error => {
                console.error(error);

                // something failed, just let the user through, client-side validation is not critical
                this.isValid = true;
                this.submitActionButton(this.options.proceedActionButtonSelector)
            });
    }

    async validate()
    {
        const itemsToValidate = flatten(
            Object.entries(this.rules)
                .map(([name, rules]) => this.getValidatableElements(name)
                    .map(el => ({
                        element: el,
                        rules,
                    })))
        );

        const errors = [];
        const createError = (element, rule) => ({element, rule});
        const createRuleObject = arr => ({id: arr[0], parameters: arr[1]});

        for (const it of itemsToValidate) {
            const value = this.getElementValue(it.element);

            for (const rule of Object.entries(it.rules).map(createRuleObject)) {
                if (rule.id === 'required') {
                    if (!this.validateRequired(value)) {
                        errors.push(createError(it.element, rule));
                        break; // don't send remote request if already not valid
                    }
                }

                if (rule.id === 'remote') {
                    if (!(await this.validateRemote(value, rule.parameters))) {
                        errors.push(createError(it.element, rule));
                    }
                }
            }
        }

        return errors;
    }

    getValidatableElements(name)
    {
        return Array.from(this.el.querySelectorAll(`*[name="${name}"]`))
            .filter(this.isElementVisible);
    }

    /**
     * @param {Element} element
     * @return boolean
     */
    isElementVisible(element)
    {
        // from jQuery is(':visible')
        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }

    /**
     *
     * @param {Element} element
     * @return {*}
     */
    getElementValue(element)
    {
        return element.value;
    }

    /**
     * @param value
     * @return {boolean}
     */
    validateRequired(value)
    {
        return Boolean(value);
    }

    /**
     * @param value
     * @param {object} parameters
     * @return {boolean}
     */
    async validateRemote(value, parameters)
    {
        parameters = extendDefaults({
            url: null,
            valueParamName: 'value',
            requestMethod: 'GET',
            requestHeaders: {
                "Accept": "application/json",
                "Content-Type": "application/json; charset=utf-8"
            },
            resultPropertyName: 'result',
            errorPropertyName: 'error',
            skippedErrors: [],
            nonce: null,
        }, parameters)

        const requestHeaders = parameters.requestHeaders;

        if (parameters.nonce) {
            requestHeaders['X-WP-Nonce'] = parameters.nonce;
        }

        const url = new URL(parameters.url, this.options.baseUrl);

        url.searchParams.append(parameters.valueParamName, value);

        const response = await fetch(
            url.toString(),
            {
                headers: requestHeaders,
                method: parameters.requestMethod
            }
        );

        if (!response.ok) {
            throw new Error(`Status Code: ${response.status} Message: ${response.statusText}`);
        }

        const json = await response.json();

        const isValid = Boolean(json[parameters.resultPropertyName]);

        return isValid || parameters.skippedErrors.includes(json[parameters.errorPropertyName]);
    }

    /**
     *
     * @param {Element} element
     * @param {string} text
     */
    addErrorLabel(element, text)
    {
        const labelHtml = `<p class="${this.options.errorLabel.class}">${text}</p>`;

        switch (this.options.errorLabel.position.type) {
            case OnboardingFormValidator.POSITION_IN_CLOSEST_SELECTOR:
                const container = element.closest(this.options.errorLabel.position.selector);

                if (!container) {
                    console.error(`${this.options.errorLabel.position.selector} not found`);
                    return;
                }

                container.insertAdjacentHTML('beforeend', labelHtml);
                break;
            default:
                console.warn(`Unknown position type: ${this.options.errorLabel.position.type}`);
            // noinspection FallThroughInSwitchStatementJS
            case OnboardingFormValidator.POSITION_AFTER_INPUT_FIELD:
                element.insertAdjacentHTML('afterend', labelHtml);
                break;
        }
    }

    /**
     * Remove all error labels inside the container.
     *
     * @param {Element} container
     */
    removeErrorLabels(container)
    {
        const labels = container.querySelectorAll(`.${this.options.errorLabel.class}`);

        for (const label of labels) {
            label.remove();
        }
    }

    getActionButton(actionButtonSelector)
    {
        return this.el.querySelector(actionButtonSelector);
    }

    submitActionButton(actionButtonSelector)
    {
        const btn = this.getActionButton(actionButtonSelector);

        if (!btn) {
            console.log(actionButtonSelector + ' not found');
            return;
        }

        btn.click();
    }
}
