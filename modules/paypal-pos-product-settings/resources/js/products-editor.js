/* eslint-disable */
import VariationsObserver from "../../../../modules.local/paypal-pos-product-settings/resources/js/modules/variations-observer.module";
import BarcodeScanner from './modules/barcode-scanner.module';
import store from 'store';

const scanners = [];

const initBarcodeScanner = (scannerRoot) => {
    if (scannerRoot.getAttribute('data-initialized')) {
        return;
    }

    const settingsStorageKey = 'zettleBarcodeScanningSettings';

    const inputFieldRoot = scannerRoot.parentElement.querySelector('.zettle-barcode-input-field');
    const input = inputFieldRoot.querySelector('input');

    const barcodeScanner = new BarcodeScanner(scannerRoot, (barcode, scanner) => {
        input.value = barcode;

        scanner.stop();
        scannerRoot.style.display = 'none';
    }, {
        errorCallback: err => {
            alert(zettleBarcodeScanning.initErrorMessage);
            scannerRoot.style.display = 'none';
        },
        settingsChangedCallback: settings => {
            store.set(settingsStorageKey, settings);
        },
    });

    scanners.push(barcodeScanner);

    inputFieldRoot.querySelector('button').addEventListener('click', () => {
        const wasShown = scannerRoot.style.display === 'block';

        document.querySelectorAll('.zettle-barcode-scan').forEach(el => {
            el.style.display = 'none';
        });

        scanners.forEach(s => s.stop());

        if (!wasShown) {
            const settings = store.get(settingsStorageKey);
            if (settings) {
                barcodeScanner.updateSettingsUi(settings);
            }

            scannerRoot.style.display = 'block';

            barcodeScanner.start();
        }
    });

    scannerRoot.setAttribute('data-initialized', 'true');
};

const initAllBarcodeScanners = () => {
    document.querySelectorAll('.zettle-barcode-scan').forEach(initBarcodeScanner);
};

initAllBarcodeScanners();

const variationsObserver = new VariationsObserver(initAllBarcodeScanners);
variationsObserver.startWhenPossible();
