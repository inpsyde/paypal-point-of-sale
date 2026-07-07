import type { RequestUtils } from '@inpsyde/playwright-utils/build';
import { runWpCli, type AnyCli } from './pos-cli.helper';

// Loose on purpose: WooCommerce.CreateProduct (from @inpsyde/playwright-utils) requires
// regular_price and has no status/manage_stock/stock_quantity fields, which tests need.
type CreateProductData = { name: string } & Record< string, unknown >;

/** Create a WC product for a test; defaults to a published simple product. */
export async function createProduct(
    requestUtils: RequestUtils,
    data: CreateProductData
): Promise< { id: number } > {
    return requestUtils.rest< { id: number } >( {
        path: '/wc/v3/products',
        method: 'POST',
        data: { type: 'simple', status: 'publish', ...data },
    } );
}

/**
 * Delete a WC product via WP-CLI — always, even in tests that never called processQueue().
 * REST deletion can fail with a stale nonce once processQueue() has run, so using WP-CLI
 * everywhere keeps cleanup uniform instead of depending on what each test happens to do.
 */
export async function deleteProduct( cli: AnyCli, productId: number ): Promise< void > {
    await runWpCli( cli, `wc product delete ${ productId } --force=true --user=1` ).catch( () => {} );
}

/** Delete a WC order via WP-CLI — same reasoning as deleteProduct. */
export async function deleteOrder( cli: AnyCli, orderId: number ): Promise< void > {
    await runWpCli( cli, `wc shop_order delete ${ orderId } --force=true --user=1` ).catch( () => {} );
}
