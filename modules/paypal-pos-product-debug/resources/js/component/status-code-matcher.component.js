/* eslint-disable */

export default class StatusCodeMatcher {

    /**
     * @param {array<string, string>} statusMap
     */
    constructor(statusMap)
    {
        this.statusMap = statusMap || [];
    }

    /**
     * Match given status codes with the specific messages
     *
     * @param {string[]} statusCodes
     *
     * @return {array<string, string>}
     */
    match(statusCodes) {
        let map = [];

        statusCodes.forEach(statusCode => {
            map[statusCode] = this.get(statusCode);
        })

        return map;
    }

    /**
     * Get Message from Status Map
     *
     * @param {string} statusCode
     *
     * @return {string}
     */
    get(statusCode)
    {
        return this.exists(statusCode) ? this.statusMap[statusCode] : this.statusMap['undefined'];
    }

    /**
     *
     * Add new Status with Message
     *
     * @param {string} statusCode
     * @param {string} message
     */
    set(statusCode, message)
    {
        this.statusMap[statusCode] = message;
    }

    /**
     * Check if a status code exists in status map
     *
     * @param {string} statusCode
     *
     * @return {boolean}
     */
    exists(statusCode)
    {
        return statusCode in this.statusMap;
    }

    /**
     * Unset a status code from the status map
     *
     * @param {string} statusCode
     */
    unset(statusCode)
    {
        delete this.statusMap[statusCode];
    }
}