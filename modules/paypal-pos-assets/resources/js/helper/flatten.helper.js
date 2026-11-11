/* eslint-disable */

/**
 * Flattens the array.
 * e.g. flatten([[a], [], [b, c]]) == [a, b, c]
 *
 * @param {Array} arr
 *
 * @return {Array}
 */
export function flatten(arr)
{
    return [].concat(...arr);
}
