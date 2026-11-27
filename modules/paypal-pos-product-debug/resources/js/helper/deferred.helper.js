/* eslint-disable */

/**
 * A wrapper for resolving promises later, outside of executor.
 */
export default class Deferred {
  constructor() {
    this.promise = new Promise((resolve, reject) => {
      this.reject = reject
      this.resolve = resolve
    })
  }
}
