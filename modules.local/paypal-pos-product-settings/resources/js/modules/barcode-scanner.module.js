/* eslint-disable */
import {extendDefaults} from '../helper/extend-defaults.helper';
import adapter from 'webrtc-adapter'; // looks like it just needs to be imported https://github.com/webrtc/adapter#usage
import Quagga from '@ericblade/quagga2'

export default class BarcodeScanner {
    /**
     * @callback successCallback
     * @param {string} barcode
     */

    /**
     * @callback errorCallback
     * @param error
     */

    /**
     * @typedef settings
     * @property {?string} barcodeType
     * @property {?string} cameraDeviceId
     */

    /**
     *
     * @param {Element} root
     * @param {successCallback} successCallback
     * @param {{
     * settingsChangedCallback: ?function(settings),
     * errorCallback: ?errorCallback,
     * viewport: ?Element,
     * barcodeTypeSelect: ?Element,
     * cameraSelect: ?Element,
     * streamSettings: ?object
     * }} options
     */
    constructor(root, successCallback, options) {
        this.root = root;
        this.successCallback = successCallback;

        const defaults = {
            settingsChangedCallback: null,
            errorCallback: null,
            viewport: root.querySelector('.zettle-barcode-scanner-viewport'),
            barcodeTypeSelect: root.querySelector('select[name="barcode_type"]'),
            cameraSelect: root.querySelector('select[name="camera"]'),
            streamSettings: {
                constraints: {
                    width: 640,
                    height: 480,
                },
            },
        };
        this.options = extendDefaults(defaults, options, true);

        this.started = false;

        this.lastSettings = null;

        root.querySelectorAll('select').forEach(el => {
            el.addEventListener('change', () => {
                const settings = this.getSettings();

                if (this.options.settingsChangedCallback) {
                    this.options.settingsChangedCallback(settings, this);
                }

                this.lastSettings = settings;

                if (this.started) {
                    this.restart();
                }
            });
        })
    }

    start() {
        Quagga.init(this._getQuaggaConfig(this.getSettings()), async (err) => {
            if (err) {
                console.log(err);
                if (this.options.errorCallback) {
                    this.options.errorCallback(err, this);
                }
                this.stop();
                return;
            }

            // camera IDs are not available without camera permissions,
            // so we cannot load the camera IDs earlier than here
            await this._fillCameraSelect(this.options.cameraSelect);
            const currentDeviceId = Quagga.CameraAccess.getActiveTrack()?.getSettings()?.deviceId;
            if (currentDeviceId) {
                this.options.cameraSelect.value = currentDeviceId;
            }

            Quagga.start();

            Quagga.onDetected(result => {
                if (!this.started) { // just to be sure
                    return;
                }

                this.successCallback(result.codeResult.code, this);
            });

            this.started = true;
        });
    }

    stop() {
        if (!this.started) {
            return;
        }

        Quagga.offDetected();
        Quagga.stop();

        this.started = false;
    }

    restart() {
        this.stop();
        this.start();
    }

    /**
     * @return {settings}
     */
    getSettings() {
        return {
            barcodeType: this.options.barcodeTypeSelect.value,
            cameraDeviceId: this.options.cameraSelect.value,
        };
    }

    /**
     * @param {settings} settings
     */
    updateSettingsUi(settings) {
        if (settings.barcodeType) {
            this.options.barcodeTypeSelect.value = settings.barcodeType;
        }

        this.lastSettings = settings;
    }

    /**
     * @param {settings} settings
     * @return {object}
     * @private
     */
    _getQuaggaConfig(settings) {
        const barcodeTypes = settings.barcodeType.split(',');
        const readers = barcodeTypes.map(this._getQuaggaReader);

        return {
            inputStream: {
                name: 'Live',
                type: 'LiveStream',
                target: this.options.viewport,
                constraints: {
                    ...this.options.streamSettings.constraints,
                    ...(this.lastSettings?.cameraDeviceId ? {deviceId: this.lastSettings.cameraDeviceId} : {}),
                },
            },
            decoder: {
                readers: readers,
            }
        };
    }

    /**
     * @param {string} barcodeType
     * @return {{format: string, config: object}}
     * @private
     */
    _getQuaggaReader(barcodeType) {
        if (barcodeType === 'ean_extended') {
            return {
                format: 'ean_reader',
                config: {
                    supplements: [
                        'ean_5_reader', 'ean_2_reader'
                    ],
                },
            };
        }
        return {
            format: `${barcodeType}_reader`,
            config: {},
        };
    }

    async _fillCameraSelect(select) {
        select.length = 0;

        const devices = await Quagga.CameraAccess.enumerateVideoDevices();

        devices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.appendChild(document.createTextNode(device.label));
            select.appendChild(option);
        });
    }
}
