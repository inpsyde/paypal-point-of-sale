import { expect, type WooCommerceApi } from '@inpsyde/playwright-utils/build';
import {
	runWpCli,
	processQueue,
	syncProduct,
	type AnyCli,
} from './pos-cli.helper';
import { getPosProductUuid } from './webhook.helper';
import type { WcProductsPage, ProductSyncStatus } from '../admin';
import type { ZettleApiClient } from '../api';

export type CreateProductData = { name: string } & Record< string, unknown >;

// Generated once per worker, so leftovers of an aborted run never collide with this run's data.
const runSuffix = Date.now().toString( 36 );

/**
 * Make test data (product names, SKUs, tax class names) unique per run.
 * @param base
 * @param separator Use '-' for values that must not contain spaces, like SKUs.
 */
export function uniqueValue( base: string, separator = ' ' ): string {
	return `${ base }${ separator }${ runSuffix }`;
}

/**
 * Create a WC product for a test; defaults to a published simple product.
 * @param wooCommerceApi
 * @param data
 */
export async function createProduct(
	wooCommerceApi: WooCommerceApi,
	data: CreateProductData
): Promise< { id: number } > {
	return wooCommerceApi.createProduct( {
		type: 'simple',
		// @ts-expect-error -- pw-utils CreateProduct has no status and requires regular_price; remove after the pw-utils type change (R2).
		status: 'publish',
		...data,
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
 * Delete a product and assert the admin list shows it as not-synced afterward, and
 * that PayPal POS no longer has it.
 * @param wooCommerceApi
 * @param cli
 * @param wcProducts
 * @param zettleApi
 * @param productId
 * @param productName
 */
export async function assertDeletionUnsyncsFromPos(
	wooCommerceApi: WooCommerceApi,
	cli: AnyCli,
	wcProducts: WcProductsPage,
	zettleApi: ZettleApiClient,
	productId: number,
	productName: string
): Promise< void > {
	// Read the UUID first: deleting the product removes it from the ID map.
	const posUuid = await getPosProductUuid( cli, productId );
	expect(
		posUuid,
		`Assert WC product ${ productId } has a PayPal POS UUID before deletion`
	).not.toBeNull();

	await wooCommerceApi.deleteProduct( productId );
	await processQueue( cli );
	await wcProducts.visit( 'trash' );
	await wcProducts.assertProductSyncStatus(
		productName,
		'not-synced',
		productId
	);
	await expect
		.poll( () => zettleApi.getProduct( posUuid as string ), {
			message:
				'Assert deleted product is removed from the PayPal POS product library',
		} )
		.toBeUndefined();
}

/**
 * Delete a WC order permanently, for test cleanup.
 * @param wooCommerceApi
 * @param orderId
 */
export async function deleteOrder(
	wooCommerceApi: WooCommerceApi,
	orderId: number
): Promise< void > {
	await wooCommerceApi
		.deleteOrder( orderId, { force: true } )
		.catch( () => {} );
}
