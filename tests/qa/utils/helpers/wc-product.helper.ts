import type { RequestUtils } from '@inpsyde/playwright-utils/build';
import {
	runWpCli,
	processQueue,
	syncProduct,
	type AnyCli,
} from './pos-cli.helper';
import type { WcProductsPage, ProductSyncStatus } from '../admin';

export type CreateProductData = { name: string } & Record< string, unknown >;

/**
 * Create a WC product for a test; defaults to a published simple product.
 * @param requestUtils
 * @param data
 */
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
 * @param cli
 * @param productId
 */
export async function deleteProduct(
	cli: AnyCli,
	productId: number
): Promise< void > {
	await runWpCli(
		cli,
		`wc product delete ${ productId } --force=true --user=1`
	).catch( () => {} );
	await processQueue( cli );
}

/**
 * Trigger a sync for a product and assert the resulting status shown in the admin list —
 * the sequence nearly every product-sync/stock-sync test ends up repeating.
 * @param cli
 * @param wcProducts
 * @param productName
 * @param status
 * @param productId
 */
export async function syncAndAssertStatus(
	cli: AnyCli,
	wcProducts: WcProductsPage,
	productName: string,
	status: ProductSyncStatus,
	productId: number
): Promise< void > {
	await syncProduct( cli, productId );
	await wcProducts.visit();
	await wcProducts.assertProductSyncStatus( productName, status, productId );
}

/**
 * Delete a product and assert the admin list shows it as not-synced afterward.
 * @param requestUtils
 * @param cli
 * @param wcProducts
 * @param productId
 * @param productName
 */
export async function assertDeletionUnsyncsFromPos(
	requestUtils: RequestUtils,
	cli: AnyCli,
	wcProducts: WcProductsPage,
	productId: number,
	productName: string
): Promise< void > {
	await requestUtils.rest( {
		path: `/wc/v3/products/${ productId }`,
		method: 'DELETE',
		params: { force: false },
	} );
	await processQueue( cli );
	await wcProducts.visit( 'trash' );
	await wcProducts.assertProductSyncStatus(
		productName,
		'not-synced',
		productId
	);
}

/**
 * Delete a WC order via WP-CLI — same reasoning as deleteProduct.
 * @param cli
 * @param orderId
 */
export async function deleteOrder(
	cli: AnyCli,
	orderId: number
): Promise< void > {
	await runWpCli(
		cli,
		`wc shop_order delete ${ orderId } --force=true --user=1`
	).catch( () => {} );
}
