import { WpPage, expect } from '@inpsyde/playwright-utils/build';

export class PosSettingsPage extends WpPage {
    url = '/wp-admin/admin.php?page=wc-settings&tab=zettle';

    // --- WC Settings tab ---
    wcSettingsTab = () => this.page.locator( 'a.nav-tab[href*="tab=zettle"]' );

    // --- Welcome state ---
    welcomeHeading = () => this.page.getByRole( 'heading', {
        name: 'Grow your business with PayPal Point of Sale and WooCommerce',
    } );

    connectButton = () => this.page.getByRole( 'button', { name: 'Connect' } );

    // --- API credentials state ---
    authoriseHeading = () => this.page.getByRole( 'heading', { name: 'Authorise connection' } );
    apiKeyInput = () => this.page.locator( '.zettle-api-key input' );
    authenticateButton = () => this.page.getByRole( 'button', {
        name: 'Authenticate with PayPal Point of Sale',
    } );

    // --- Invalid credentials state ---
    authFailedHeading = () => this.page.getByRole( 'heading', { name: 'Authentication failed' } );
    startOverButton = () => this.page.getByRole( 'button', { name: 'Start over' } );

    // --- Sync params ---
    mergeRadio = () => this.page.locator( '#zettle-merge-products' );
    wipeRadio = () => this.page.locator( '#zettle-wipe-products' );
    includeTaxRadio = () => this.page.locator( '#zettle-include-tax-prices' );
    zeroPricesRadio = () => this.page.locator( '#zettle-zero-prices' );

    // --- Navigation buttons ---
    backButton = () => this.page.getByRole( 'button', { name: 'Back' } );
    nextButton = () => this.page.getByRole( 'button', { name: 'Next' } );
    startSyncButton = () => this.page.getByRole( 'button', { name: 'Start sync' } );

    // --- Sync progress ---
    cancelButton = () => this.page.getByRole( 'button', { name: 'Cancel' } );

    // --- Connected state ---
    productsCountText = () => this.page.getByText( 'Number of products syncing:' );
    // Opens the disconnect confirmation modal
    disconnectTrigger = () => this.page.locator( '.zettle-settings-header button[name="delete"]' );
    // "Disconnect" confirm button inside the modal
    disconnectConfirm = () => this.page.locator( '#zettleDisconnectModal button[name="delete"]' );
    // "Cancel" button inside the modal
    disconnectCancel = () => this.page.locator( '#zettleDisconnectModal button[name="back"]' );
    disconnectModalHeading = () => this.page.getByRole( 'heading', {
        name: 'Are you sure you want to disconnect?',
    } );

    // --- Actions ---

    clickConnect = async () => {
        await this.connectButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    enterApiKey = async ( apiKey: string ) => {
        await this.apiKeyInput().fill( apiKey );
    };

    clickAuthenticate = async () => {
        await this.authenticateButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    clickStartOver = async () => {
        await this.startOverButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    clickBack = async () => {
        await this.backButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    clickNext = async () => {
        await this.nextButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    clickStartSync = async () => {
        await this.startSyncButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    // --- Assertions ---

    assertWelcomeState = async () => {
        await expect( this.welcomeHeading() ).toBeVisible();
        await expect( this.connectButton() ).toBeVisible();
    };

    assertApiCredentialsState = async () => {
        await expect( this.authoriseHeading() ).toBeVisible();
        await expect( this.apiKeyInput() ).toBeVisible();
        await expect( this.authenticateButton() ).toBeVisible();
    };

    assertInvalidCredentialsState = async () => {
        await expect( this.authFailedHeading() ).toBeVisible();
        await expect( this.startOverButton() ).toBeVisible();
    };

    assertConnectedState = async () => {
        await expect( this.productsCountText() ).toBeVisible();
    };

    assertTabVisible = async () => {
        await expect( this.wcSettingsTab() ).toBeVisible();
    };

    assertTabNotVisible = async () => {
        await expect( this.wcSettingsTab() ).not.toBeVisible();
    };
}
