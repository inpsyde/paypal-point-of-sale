/* eslint-disable */

import {extendDefaults} from "../helper/extend-defaults.helper";
import Deferred from "../helper/deferred.helper";
import Debouncer from "../helper/debouncer.helper";

export default class StatusCodeFetcher {
    /**
     * constructor
     *
     * @param {String} url
     * @param {Object} options
     */
    constructor(url, options)
    {
        this._defaults = {
            requestMethod: 'GET',
            requestHeaders: {
                "Accept": "application/json",
                "Content-Type": "application/json; charset=utf-8"
            },
            requestArguments: {
                ids: {
                    type: 'array',
                    active: true,
                    value: [],
                },
                strategy: {
                    type: 'string',
                    active: false,
                    value: ''
                },
            },
            baseUrl: window.location.origin,
            nonce: null,
            status: [],
            maxCountPerFetch: 50,
            debounceTime: 100,
        };

        this.options = extendDefaults(this._defaults, options, true);
        this.url = url;

        if (this.url !== null && this.options.nonce === null) {
            throw new Error('No Nonce was given.');
        }

        this.idsToProcessPromiseMap = new Map();

        this.debounce = Debouncer.debounce(
            this._fetchData.bind(this),
            this.options.debounceTime
        );
    }

    /**
     * @param {int} productId
     */
    fetch(productId) {
        const dfd = new Deferred();

        this.idsToProcessPromiseMap.set(productId, dfd);

        this.debounce();

        if (this.idsToProcessPromiseMap.size >= this.options.maxCountPerFetch) {
            this._fetchData();
        }

        return dfd.promise;
    }

    _fetchData() {
        if (this.idsToProcessPromiseMap.size === 0) {
            return;
        }

        const ids = Array.from(this.idsToProcessPromiseMap.keys());
        const url = this._buildRequestArguments(this.url, ids);

        const idsToProcessPromiseMap = new Map(this.idsToProcessPromiseMap.entries());
        this.idsToProcessPromiseMap.clear();

        fetch(
            url,
            this._buildRequest()
        )
            .then(response => response.json())
            .then(result => {
                for (let [key, value] of Object.entries(result)) {
                    idsToProcessPromiseMap.get(Number(key)).resolve(value);
                }
            });
    }

    /**
     * @param {string|null} method
     *
     * @return {Object}
     *
     * @private
     */
    _buildRequest(method = null)
    {
        let requestHeaders = this.options.requestHeaders;

        const nonceRequestHeader = {
            'X-WP-Nonce': this.options.nonce
        };

        requestHeaders = {...requestHeaders, ...nonceRequestHeader};

        return {
            headers: requestHeaders,
            method: method ?? this.options.requestMethod
        }
    }

    /**
     * Build Arguments and add it to the Url
     *
     * @param url
     * @param {Array} productIds
     *
     * @return {string} url
     *
     * @private
     */
    _buildRequestArguments(url, productIds)
    {
        let nativeUrl = new URL(url, this.options.baseUrl);

        let args = extendDefaults(
            this.options.requestArguments,
            {
                ids: {value: productIds}
            }
        );

        for (const [key, argument] of Object.entries(args)) {
            if (argument.active) {
              switch (argument.type) {
                case 'array':
                  argument.value.forEach(value => {
                    nativeUrl.searchParams.append(
                      key + '[]',
                      value
                    );
                  })
                  break;
                default:
                  nativeUrl.searchParams.append(key, argument.value);
                  break;
              }
            }
        }
        return nativeUrl.toString();
    }
}
