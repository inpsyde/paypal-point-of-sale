/* eslint-disable */

/**
 * Object check helper function
 *
 * @param {object|{}} config    Config Object that needs to be checked
 *
 * @returns {Boolean} Returns true if the object is empty
 */
export function isEmptyConfig(config)
{
    if (config.constructor !== Object) {
        return false;
    }

    return Object.keys(config).length === 0;
}