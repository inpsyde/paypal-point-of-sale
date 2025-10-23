/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';

/**
 * Calls callback when variations were added/removed/loaded.
 */
export default class VariationsObserver {
    /**
     * @param {Function} callback
     * @param {{
     * variationsListSelector: ?string,
     * variationsMetaBoxSelector: ?string,
     * mutationObserverConfig: ?object,
     * metaBoxMutationObserverConfig: ?object
     * }} options
     */
    constructor(callback, options = {}) {
        this._defaults = {
            variationsListSelector: '.woocommerce_variations',
            variationsMetaBoxSelector: '#variable_product_options',
            mutationObserverConfig: {
                childList: true,
            },
            metaBoxMutationObserverConfig: {
                childList: true,
            },
        };

        this.options = extendDefaults(this._defaults, options, true);

        this.callback = callback || null;
        if (this.callback === null) {
            throw new Error('No valid callback was passed.');
        }
    }

    canStart() {
        return document.querySelector(this.options.variationsListSelector) !== null;
    }

    start() {
        if (!this.canStart()) {
            throw new Error('Cannot start variations observer.');
        }

        this.stop();

        const el = document.querySelector(this.options.variationsListSelector);

        this.observer = new MutationObserver(this.callback);

        this.observer.observe(el, this.options.mutationObserverConfig);
    }

    stop()
    {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }

        if (this.metaBoxObserver) {
            this.metaBoxObserver.disconnect();
            this.metaBoxObserver = null;
        }
    }

    startWhenPossible()
    {
        try {
            this.start();
        } catch (e) {
            if (this.metaBoxObserver) {
                return;
            }

            const el = document.querySelector(this.options.variationsMetaBoxSelector);

            this.metaBoxObserver = new MutationObserver(m => {
                try {
                    this.start();
                } catch(e) {
                    return;
                }

                // probably not needed.
                // .woocommerce_variations seems to not exist only when product has no attributes
                this.callback(m);
            });

            this.metaBoxObserver.observe(el, this.options.metaBoxMutationObserverConfig);
        }
    }
}
