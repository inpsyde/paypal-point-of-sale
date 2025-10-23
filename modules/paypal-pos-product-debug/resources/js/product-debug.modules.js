/* eslint-disable */

import StatusCodeFetcher from "./component/status-code-fetcher.component";
import StatusCodeMatcher from "./component/status-code-matcher.component";
import StatusCodeRendererHtml from "./component/status-code-renderer-html.component";
import ElementClassObserver from "./helper/element-class-observer.helper";
import StatusLoader from "./modules/status-loader.module";

const zettleSyncStatusColumn = document.getElementById('zettle_synced') || null;

if (!zettleSyncStatusColumn) {
    throw new Error('PayPal Point of Sale Column not found.');
}

const productElements = document.querySelectorAll('*[data-sync-status="true"]') || null;

// Check if product listing has elements
if (!productElements || productElements.length < 1) {
    throw new Error('Product Elements not found.');
}

// Check if config is available
if (typeof zettleProductValidation === 'undefined') {
    throw Error("Url and Configuration Variable are not defined.");
}

const statusCodeFetcher = new StatusCodeFetcher(
  zettleProductValidation.url,
  {
    nonce: zettleProductValidation.nonce,
    requestArguments: zettleProductValidation.requestArguments,
  }
);

productElements.forEach(productElement => {
    // Don't instantiate a new StatusLoader instance without a valid productId
    if (productElement.dataset.id === null) {
        productElement.status = null;

        return;
    }

    productElement.status = new StatusLoader(
        statusCodeFetcher,
        new StatusCodeMatcher(
            zettleProductValidation.status
        ),
        new StatusCodeRendererHtml(
            '<br>',
            ' - '
        ),
        productElement,
        {
            isHidden: zettleSyncStatusColumn.classList.contains('hidden')
        }
    );
});

const elementClassObserver = new ElementClassObserver(
    zettleSyncStatusColumn,
    'hidden',
    () => {

    },
    () => {
        productElements.forEach(productElement => {
             if (!productElement.status instanceof StatusLoader) {
                return;
            }

            const loader = productElement.querySelector('.loader');

            if (!productElement.contains(loader)) {
                return;
            }

            productElement.status.loadContent();
        });
    }
);

elementClassObserver.observe();

