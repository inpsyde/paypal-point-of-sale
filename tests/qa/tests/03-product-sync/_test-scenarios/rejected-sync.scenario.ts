import type { RequestUtils } from '@inpsyde/playwright-utils/build';
import {
	test,
	createProduct,
	deleteProduct,
	syncAndAssertStatus,
	type CreateProductData,
	type ProductSyncStatus,
} from '../../../utils';

export type RejectedSyncCase = {
	title: string;
	productData: CreateProductData;
	expectedStatus: ProductSyncStatus;
	timeout?: number;
	// Runs after product creation but before syncProduct — for cases that need extra REST
	// setup (e.g. variations) beyond what createProduct's single POST covers.
	beforeSync?: (
		requestUtils: RequestUtils,
		productId: number
	) => Promise< void >;
};

/**
 * A product created with some invalid/unsupported shape is rejected during sync and shows
 * the matching not-synced reason in the admin product list — shared by every "single bad
 * product property" case (unsupported type, too many variant attributes, too many variants,
 * title too long, ...).
 * @param data
 */
export const testRejectedProductSync = ( data: RejectedSyncCase ) => {
	test( data.title, async ( { wcProducts, requestUtils, cli } ) => {
		test.setTimeout( data.timeout ?? 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct( requestUtils, data.productData );

		try {
			if ( data.beforeSync ) {
				await data.beforeSync( requestUtils, product.id );
			}

			await syncAndAssertStatus(
				cli,
				wcProducts,
				data.productData.name,
				data.expectedStatus,
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );
};
