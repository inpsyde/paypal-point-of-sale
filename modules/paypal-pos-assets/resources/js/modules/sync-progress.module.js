/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';

export default class SyncProgress {

    /**
     * constructor
     *
     * @param {Element} element
     * @param {String} url
     * @param {Object} options
     */
    constructor(element, url, options)
    {
        this._defaults = {
            requestMethod: 'GET',
            requestHeaders: {
                "Accept": "application/json",
                "Content-Type": "application/json; charset=utf-8"
            },
            requestArguments: {
                types: {
                    type: 'array',
                    active: true,
                    value: []
                },
                executionTime: {
                    type: 'integer',
                    active: false,
                    value: 3
                },
                meta: {
                    type: 'object',
                    active: false,
                    value: {}
                }
            },
            baseUrl: window.location.origin,
            preventDefault: true,
            nonce: null,
            autoProceed: true,
            selectors: {
                icon: '.sync-progress-icon',
                cancelBtn: '.sync-progress-action-cancel',
                backBtn: 'button.btn-secondary',
                proceedBtn: 'button.btn-primary',
                progressMessage: '.sync-progress-message',
                progressStatus: '.sync-progress-status'
            },
            messages: {
                error: 'ERROR',
                confirmCancel: 'CANCEL',
                finished: 'FINISH',
                status: {
                    prepare: 'PREPARE',
                    sync: 'SYNC',
                    cleanup: 'CLEANUP',
                }
            },
            phases: ['prepare', 'sync', 'cleanup'],
            jobTypes: {
                prepare: [],
                sync: [],
                cleanup: []
            }
        };
        this.options = extendDefaults(this._defaults, options, true);
        this.jobsCompleted = 0;
        this.el = element || null;
        this.url = url;
        this.currentPhase = 0;

        this.init();
    }

    init()
    {
        if (this.el === null) {
            throw new Error('No valid Element was given.');
        }

        if (this.url !== null && this.options.nonce === null) {
            throw new Error('No Nonce was given.');
        }

        if (this.options.autoProceed) {
            // looks like the script executes too late and the button is visible for a second
            // so the correct initial state must be set on the server side
            this.setActionButtonVisibility(false, this.options.selectors.proceedBtn);
        }

        this.registerEvents();
    }

    registerEvents()
    {
        window.addEventListener('load', () => {
            this.updateProgress(0, 0);
            this.setActionButtonsState(false);
            this.toggleSyncIconAnimation();
            this.loop();
        });

        this.addNavigationConfirmation();

        const cancelActionEl = this.el.querySelector(this.options.selectors.cancelBtn);
        if (!cancelActionEl) {
            return;
        }
        cancelActionEl.addEventListener('click', (e) => {
            if (!confirm(this.options.messages.confirmCancel)) {
                e.preventDefault()
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
            this.removeNavigationConfirmation()
        });
    }

    async loop()
    {
        let url = this.url;
        const selectors = this.options.selectors;
        if (Object.keys(this.options.requestArguments).length >= 1) {
            url = this._buildRequestArguments(url);
        }
        try {
            let jobs = await this.getJobs(url);
            console.log('Queue response:', jobs)
            const {completed, remaining, meta} = jobs
            const {isFinished} = meta;

            this.updateProgress(completed, remaining);
            if (!isFinished || (
                isFinished && this._advanceToNextPhase()
            )) {
                await this.loop();
            }
        } catch (error) {
            let json = await error.data.json();
            console.error(json);
            alert(this.options.messages.error);
            this.toggleSyncIconAnimation();
            this.setActionButtonState(true, selectors.backBtn);
        }
    }

    /**
     *
     * @param {string} url
     *
     * @return {Array}
     */
    async getJobs(url)
    {
        let response = await fetch(
            url,
            this._buildRequest()
        );

        if (response.ok) {
            return await response.json();
        }
        /**
         * Something's wrong with the response, so we'll just throw the response itself
         */
        throw response;
    }

    _currentPhase()
    {
        return this.options.phases[this.currentPhase];
    }

    /**
     * Sets the current phase to the next entry in the defined phases.
     * Also carries out related actions like updating the status message and
     * dealing with cleanup once we've reached the end of the last phase
     * @returns {boolean} Whether or not we should continue looping
     * @private
     */
    _advanceToNextPhase()
    {
        const phase = this._currentPhase()
        const selectors = this.options.selectors;
        if (phase === this.options.phases.slice(-1).pop()) {
            this.setActionButtonState(false, selectors.backBtn);
            this.setActionButtonState(true, selectors.proceedBtn);
            this.toggleSyncIconAnimation();
            this.setProgressMessage(this.options.messages.finished, '')
            this.removeNavigationConfirmation()
            if (this.options.autoProceed) {
                this.submitActionButton(selectors.proceedBtn);
            }

            return false;
        }
        this.currentPhase++;
        console.log(`Switched from phase ${phase} to ${this._currentPhase()}`);
        this.jobsCompleted = 0;
        this.updateProgress(0, 0);
        return true;
    }

    /**
     * @param {Number} completed
     * @param {Number} remaining
     */
    updateProgress(completed, remaining)
    {
        this.jobsCompleted += completed;
        let remainingJobs = this.jobsCompleted + remaining;
        this.setProgressMessage(
            this.options.messages.status[this._currentPhase()] || '...',
            remainingJobs ? `(${this.jobsCompleted} / ${remainingJobs})` : '...'
        )

    }

    /**
     *
     * @param {string} message
     * @param {string} status
     */
    setProgressMessage(message, status)
    {
        const selectors = this.options.selectors;
        let msgEl = this.el.querySelector(selectors.progressMessage);
        let countEl = this.el.querySelector(selectors.progressStatus);
        msgEl.innerHTML = message
        countEl.innerHTML = status;
    }

    getActionButton(actionButtonSelector)
    {
        const actionButtonContainer = document.querySelector('.zettle-settings-onboarding-actions');

        return actionButtonContainer.querySelector(actionButtonSelector);
    }

    /**
     * @param {Boolean} enabled
     * @param actionButtonSelector
     */
    setActionButtonState(enabled, actionButtonSelector)
    {
        const btn = this.getActionButton(actionButtonSelector);
        if (btn === null) {
            console.log(actionButtonSelector + ' not found');
            return;
        }

        btn.disabled = !enabled;
    }

    /**
     * @param {Boolean} enabled
     */
    setActionButtonsState(enabled)
    {
        const actionButtonContainer = document.querySelector('.zettle-settings-onboarding-actions');
        const actionButtons = actionButtonContainer.querySelectorAll('button');

        actionButtons.forEach(btn => btn.disabled = !enabled);
    }

    /**
     * @param {Boolean} visible
     * @param actionButtonSelector
     */
    setActionButtonVisibility(visible, actionButtonSelector)
    {
        const btn = this.getActionButton(actionButtonSelector);
        if (btn === null) {
            console.log(actionButtonSelector + ' not found');
            return;
        }

        btn.style.display = visible ? 'block' : 'none';
    }

    submitActionButton(actionButtonSelector)
    {
        const btn = this.getActionButton(actionButtonSelector);
        if (btn === null) {
            console.log(actionButtonSelector + ' not found');
            return;
        }

        btn.click();
    }

    /**
     * Toggle the animation class to the icon
     */
    toggleSyncIconAnimation()
    {
        const iconElement = this.el.querySelector(this.options.selectors.icon) || null;

        if (iconElement !== null) {
            iconElement.classList.toggle('animate');
        }
    }

    /**l
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
     *
     * @return {string} url
     *
     * @private
     */
    _buildRequestArguments(url)
    {
        let nativeUrl = new URL(url, this.options.baseUrl);
        const phase = this._currentPhase();
        let jobTypes = this.options.jobTypes[phase] || [];
        let args = extendDefaults(this.options.requestArguments, {
            types: {value: jobTypes},
            meta: {value: {phase}}
        });
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
                    case 'object':
                        for (const [prop, value] of Object.entries(argument.value)) {
                            nativeUrl.searchParams.append(
                                `${key}[${prop}]`,
                                value
                            );
                        }
                        break;
                    default:
                        nativeUrl.searchParams.append(key, argument.value);
                        break;
                }
            }
        }
        return nativeUrl.toString();
    }

    /**
     * Adds the unload listener
     */
    addNavigationConfirmation()
    {
        window.addEventListener("beforeunload", this.onBeforeUnload);
    }

    /**
     * Removes the unload listener
     */
    removeNavigationConfirmation()
    {
        window.removeEventListener("beforeunload", this.onBeforeUnload)
    }

    /**
     * Block navigation unless the user confirms it
     * @param e
     */
    onBeforeUnload(e)
    {
        // Cancel the event
        e.preventDefault(); // If you prevent default behavior in Mozilla Firefox prompt will always be shown
        // Chrome requires returnValue to be set
        e.returnValue = '';                           //Webkit, Safari, Chrome etc.
    }
}
