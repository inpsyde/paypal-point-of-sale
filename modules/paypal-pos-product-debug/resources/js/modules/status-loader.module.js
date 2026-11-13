/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';
import Debouncer from "../helper/debouncer.helper";

export default class StatusLoader {
    /**
     * constructor
     *
     * @param {StatusCodeFetcher} fetcher
     * @param {StatusCodeMatcher} matcher
     * @param {StatusCodeRendererHtml} renderer
     * @param {Element} element
     * @param {Object} options
     */
    constructor(fetcher, matcher, renderer, element, options)
    {
        this._defaults = {
            /**
             * debounce time for the window load event
             */
            loadContentDebounceTime: 100,

            isHidden: false,
        };

        this.options = extendDefaults(this._defaults, options, true);

        this.el = element || null;
        this.productId = null;

        this.fetcher = fetcher;
        this.matcher = matcher;
        this.renderer = renderer;

        this.init();
    }

    init()
    {
        if (this.el === null) {
            throw new Error('No valid Element was given.');
        }

        if (this.el.dataset.syncStatusId === null) {
            throw new Error('No ProductId for Element was setted.');
        }

        this.productId = parseInt(this.el.dataset.syncStatusId);

        this.assignDebouncedOnLoadContent();
        this.registerEvents();
    }

    registerEvents()
    {
        if (this.options.isHidden) {
            return;
        }

        window.addEventListener(
            'load',
            this.debounceOnLoadContent,
            false
        );
    }

    assignDebouncedOnLoadContent()
    {
        this.debounceOnLoadContent = Debouncer.debounce(
            this.loadContent.bind(this),
            this.options.loadContentDebounceTime
        );
    }

    /**
     * Load Content - validate attached Product ID and output validation
     */
    loadContent()
    {
        const fetch = this.fetcher.fetch(this.productId);

        fetch.then(response => {
           const statusMap = this.matcher.match(
               response.statuses
           );

            this.el.innerHTML = this.renderer.render(statusMap);
        });
    }
}
