/* eslint-disable */

/**
 * This observes a passed element, if the passed class was added or removed
 * the passed callbacks (classAddedCallback or classRemovedCallback)
 * got executed via a MutationObserver.
 */
export default class ElementClassObserver {

    /**
     * @param {Element} element
     * @param {string} classToWatch
     * @param {function} classAddedCallback
     * @param {function} classRemovedCallback
     */
    constructor(element, classToWatch, classAddedCallback, classRemovedCallback) {
        this.el = element || null;
        this.classToWatch = classToWatch;
        this.classAddedCallback = classAddedCallback;
        this.classRemovedCallback = classRemovedCallback;
        this.observer = null;
        this.lastClassState = this.el.classList.contains(this.classToWatch);

        this.init();
    }

    init() {
        if (this.el === null) {
            throw new Error("No valid Element was given.");
        }

        this.observer = new MutationObserver(
            mutationsList => {
                for (let mutation of mutationsList) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        let currentClassState = mutation.target.classList.contains(this.classToWatch);

                        if (this.lastClassState !== currentClassState) {
                            this.lastClassState = currentClassState;

                            if (currentClassState) {
                                this.classAddedCallback();
                            } else {
                                this.classRemovedCallback();
                            }
                        }
                    }
                }
            }
        );
    }

    observe() {
        this.observer.observe(this.el, {attributes: true});
    }

    disconnect() {
        this.observer.disconnect();
    }
}