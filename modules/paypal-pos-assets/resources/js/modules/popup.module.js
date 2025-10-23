/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';

export default class Popup {
    /**
     * ApiAuth constructor
     *
     * @param {Element} element
     * @param {object} options
     */
    constructor(element, options)
    {
        this._defaults = {
            url: null,
            target: '_blank',
            features: {
                height: 900,
                width: 900,
                toolbar: 0,
                location: 0,
                menubar: 0
            },
            preventDefault: false
        };

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
        this.el.addEventListener('click', e => this.onClick(e, this.el));
    }

    /**
     * @param {Event} e
     * @param {Element} element
     */
    onClick(e, element)
    {
        if (this.el instanceof HTMLAnchorElement) {
            e.preventDefault();
        }

        if (this.options.preventDefault) {
            e.preventDefault();
        }

        window.open(this.options.url, this.options.target, this._buildWindowFeatures());
    }

    /**
     * @return string
     *
     * @private
     */
    _buildWindowFeatures()
    {
        const features = Object.keys(this.options.features);

        let featureList = '',
            separator = ',';

        features.forEach((item, index) => {
            let value = this.options.features[item];

            if ((index + 1) === features.length) {
                separator = '';
            }

            featureList = featureList.concat(item + '=' + value) + separator;
        });

        return featureList;
    }
}