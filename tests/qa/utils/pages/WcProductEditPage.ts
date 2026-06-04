import { WpPage, expect } from '@inpsyde/playwright-utils/build';

export type WcProductType = 'simple' | 'variable' | 'grouped' | 'external';

/**
 * WooCommerce classic product editor with PayPal POS product-data tab.
 *
 * NOTE: WooCommerceProductEdit has been contributed to @inpsyde/playwright-utils
 * (branch dev/wc-product-admin-pages) but is not yet published. The generic edit
 * functionality is inlined here until that PR is merged and released.
 */
export class WcProductEditPage extends WpPage {
    url = '/wp-admin/post-new.php?post_type=product';

    // ── Generic product editor ────────────────────────────────────────────────

    productNameInput = () => this.page.locator( '#title' );
    productTypeSelect = () => this.page.locator( '#product-type' );

    /** Publish / Update button (same element for both new and existing products) */
    publishButton = () => this.page.locator( '#publish' );

    // General tab
    generalTabLink = () => this.page.locator( 'a[href="#product_data-general"]' );
    regularPriceInput = () => this.page.locator( '#_regular_price' );

    // Inventory tab
    inventoryTabLink = () => this.page.locator( 'a[href="#product_data-inventory"]' );
    manageStockCheckbox = () => this.page.locator( '#_manage_stock' );
    stockQuantityInput = () => this.page.locator( '#_stock' );

    visitNew = async () => {
        await this.page.goto( '/wp-admin/post-new.php?post_type=product' );
        await this.page.waitForLoadState( 'load' );
    };

    visitExisting = async ( productId: number ) => {
        await this.page.goto( `/wp-admin/post.php?post=${ productId }&action=edit` );
        await this.page.waitForLoadState( 'load' );
    };

    setName = async ( name: string ) => {
        await this.productNameInput().fill( name );
    };

    setType = async ( type: WcProductType ) => {
        await this.productTypeSelect().selectOption( type );
    };

    setRegularPrice = async ( price: string ) => {
        await this.generalTabLink().click();
        await this.regularPriceInput().fill( price );
    };

    enableManageStock = async ( quantity: number ) => {
        await this.inventoryTabLink().click();
        if ( ! await this.manageStockCheckbox().isChecked() ) {
            await this.manageStockCheckbox().check();
        }
        await this.stockQuantityInput().fill( String( quantity ) );
    };

    publish = async () => {
        await this.publishButton().click();
        await this.page.waitForLoadState( 'load' );
        await this.page.waitForURL( /post\.php\?post=\d+&action=edit/ );
    };

    update = async () => {
        await this.publishButton().click();
        await this.page.waitForLoadState( 'load' );
    };

    getProductIdFromUrl = (): number => {
        const match = this.page.url().match( /post=(\d+)/ );
        if ( ! match ) throw new Error( `Could not extract product ID from URL: ${ this.page.url() }` );
        return parseInt( match[ 1 ], 10 );
    };

    // ── PayPal POS product-data tab ───────────────────────────────────────────

    posTabLink = () => this.page.locator( '.zettle-integration_tab a' );
    posPanel = () => this.page.locator( '#zettle_integration_panel' );
    excludeFromSyncCheckbox = () => this.page.locator( '#_zettle_exclude_from_sync' );
    barcodeInput = () => this.page.locator( '#_zettle_barcode' );

    openPosTab = async () => {
        await this.posTabLink().click();
        await expect( this.posPanel() ).toBeVisible();
    };

    setExcludeFromSync = async ( exclude: boolean ) => {
        await this.openPosTab();
        const cb = this.excludeFromSyncCheckbox();
        const isChecked = await cb.isChecked();
        if ( exclude && ! isChecked ) await cb.check();
        if ( ! exclude && isChecked ) await cb.uncheck();
    };
}
