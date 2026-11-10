/* eslint-disable */

/**
 * Merge defaults with custom options
 *
 * @private
 *
 * @param {Object} defaults Default settings
 * @param {Object} options User options
 * @param {boolean} deep mode or not
 *
 * @returns {Object} Merged values of defaults and options
 */
export function extendDefaults()
{
    // Variables
    let extended = {};
    let deep = true;
    let i = 0;

    // Check if a deep merge
    if ( Object.prototype.toString.call(arguments[0]) === '[object Boolean]' ) {
        deep = arguments[0];
        i++;
    }

    // Merge the object into the extended object
    let merge = function (obj) {
        for (let prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                // If property is an object, merge properties
                if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
                    extended[prop] = extendDefaults(extended[prop], obj[prop]);
                } else {
                    extended[prop] = obj[prop];
                }
            }
        }
    };

    // Loop through each object and conduct a merge
    for (; i < arguments.length; i++) {
        merge(arguments[i]);
    }

    return extended;
}
