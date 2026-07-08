/* eslint-disable */

export default class StatusCodeRendererHtml {

    /**
     * @param {string} lineBreak
     * @param {string} listItem
     */
    constructor(lineBreak, listItem) {
        this.lineBreak = lineBreak || '';
        this.listItem = listItem || '';
    }

    /**
     * Render given status codes with message
     *
     * @param {array<string, string>} statusCodes
     *
     * @return {string}
     */
    render(statusCodes) {
        return Object.keys(statusCodes).map((statusCode, index) => {
            const message = statusCodes[statusCode];

            switch (statusCode) {
                case 'synced':
                    return `<b class="is-synced">${message}</b>`;
                case 'not-synced':
                    return `<b class="not-synced">${message}</b>`;
                case 'syncable':
                case 'not-syncable':
                    return `${this.lineBreak}<small><b>${message}</b></small>`;
                case 'product-not-found':
                    return `<span class="na">${message}</span>`;
                default:
                    return `${this.lineBreak}<small> ${this.listItem} ${message}</small>`;
            }
        }).join('');
    }
}