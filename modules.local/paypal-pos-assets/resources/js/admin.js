/* eslint-disable */
import FormChoiceSelector from './modules/form-choice-selector.module';
import Popup from "./modules/popup.module";
import OnboardingFormValidator from './modules/onboarding-form-validator.module'
import MicroModal from 'micromodal';

const formChoiceSelectorElements = document.querySelectorAll('.form-choice-selection');

formChoiceSelectorElements.forEach(formChoiceSelectorElement => {
    new FormChoiceSelector(formChoiceSelectorElement);
});

const onboardingContainer = document.querySelector('.zettle-settings-onboarding')
if (onboardingContainer) {
    new OnboardingFormValidator(
        onboardingContainer,
        zettleOnboardingValidationRules,
        {
            errorLabel: {
                position: {
                    type: OnboardingFormValidator.POSITION_IN_CLOSEST_SELECTOR,
                    selector: '.zettle-settings-onboarding-fields',
                }
            }
        }
    );
}

const connect = document.querySelectorAll('*[data-popup="true"]');

if (connect.length >= 1) {
    connect.forEach(connect => {
        new Popup(connect, {url: zettleAPIKeyCreation.url});
    });
}

const onboardingFormButtons = document.querySelectorAll(
    '.zettle-settings-onboarding-actions [type="submit"]'
);

// disable WC "Settings Lost" warning, IZET-184
onboardingFormButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        window.onbeforeunload = null;
    });
});

MicroModal.init();

const disconnectionConfirmButton = document.querySelector(`#${zettleDisconnection.dialogId} button[name="delete"]`);
if (disconnectionConfirmButton) {
    disconnectionConfirmButton.addEventListener('click', async () => {
        const response = await fetch(zettleDisconnection.url, {
            method: 'post',
            headers: {
                'X-WP-Nonce': zettleDisconnection.nonce,
            },
        });

        const reportError = error => {
            const msg = `Disconnect request error: ${error}. Check WC logs for more details.`;
            console.error(msg);
            alert(msg);
        }

        if (!response.ok) {
            reportError(response.status);
        } else {
            const result = await response.json();
            if (!result.result.success) {
                reportError(result.result.error);
            }
        }

        window.location.reload();
    });
}
