import { WpPage, expect } from '@inpsyde/playwright-utils/build';
import { Locator } from '@playwright/test';

export type ProductSyncStatus =
    | 'synced'
    | 'not-synced'
    | 'not-published'
    | 'excluded'
    | 'unsupported-product-type';


/**
 * WooCommerce product admin list page with PayPal POS sync-status column assertions.
 *
 * NOTE: WooCommerceProductList has been contributed to @inpsyde/playwright-utils
 * (branch dev/wc-product-admin-pages) but is not yet published. The generic list
 * functionality is inlined here until that PR is merged and released.
 */
export class WcProductsPage extends WpPage {
    url = '/wp-admin/edit.php?post_type=product';

    // ── Generic product list ──────────────────────────────────────────────────

    /** A product row matched by the product name text */
    productRow = ( productName: string ): Locator =>
        this.page
            .locator( '#the-list tr.type-product' )
            .filter( { hasText: productName } );

    /**
     * Navigate to the product list.
     * First string arg is treated as an optional post status filter (e.g. 'draft', 'trash').
     */
    async visit( ...args: ( string | number | { assertNoErrors?: boolean } )[] ): Promise< void > {
        const postStatus = typeof args[ 0 ] === 'string' ? args[ 0 ] : undefined;
        const params = new URLSearchParams( { post_type: 'product' } );
        if ( postStatus ) params.set( 'post_status', postStatus );
        await this.page.goto( `/wp-admin/edit.php?${ params }` );
        await this.page.waitForLoadState( 'load' );
    }

    // ── PayPal POS sync-status column ─────────────────────────────────────────

    // WP uses the column key as the <th> id; the key registered is `zettle_synced`
    syncStatusColumnHeader = () => this.page.locator( 'th#zettle_synced' );

    // By product name — may match multiple rows if previous runs left debris
    syncStatusCell = ( productName: string ): Locator =>
        this.productRow( productName ).locator( 'td.column-zettle_synced' );

    // By WP post ID — always unique, immune to duplicate-name strict-mode errors
    syncStatusCellById = ( productId: number ): Locator =>
        this.page.locator( `#post-${ productId } td.column-zettle_synced` );

    assertSyncStatusColumnVisible = async () => {
        await this.visit();
        await expect( this.syncStatusColumnHeader() ).toBeVisible();
    };

    assertProductSyncStatus = async (
        productName: string,
        status: ProductSyncStatus,
        productId?: number
    ) => {
        const cell = productId !== undefined
            ? this.syncStatusCellById( productId )
            : this.syncStatusCell( productName );

        // Wait for the JS loader spinner to be replaced by actual status content
        await cell.locator( '.loader' ).first().waitFor( { state: 'hidden', timeout: 15_000 } );

        switch ( status ) {
            case 'synced':
                await expect( cell.locator( 'b.is-synced' ) ).toHaveText( 'Synced' );
                break;
            case 'not-synced':
                await expect( cell.locator( 'b.not-synced' ) ).toBeVisible();
                break;
            case 'not-published':
                await expect( cell ).toContainText( 'Not published' );
                break;
            case 'excluded':
                await expect( cell ).toContainText( 'Excluded' );
                break;
            case 'unsupported-product-type':
                await expect( cell ).toContainText( 'Unsupported product type' );
                break;
        }
    };
}
