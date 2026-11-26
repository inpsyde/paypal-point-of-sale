import { uniqueValue, type CreateProductData } from '../../../../utils';
import type {
	StockManagedProductData,
	VariableAttributeProductData,
} from '../../../../resources/types';

export const posColumnTestProduct: CreateProductData = {
	name: uniqueValue( 'POS-579 Column Test' ),
	regular_price: '5.00',
};

export const posDraftProduct: CreateProductData = {
	name: uniqueValue( 'POS-573 Draft Product' ),
	status: 'draft',
	regular_price: '9.99',
};

export const posSimpleProductLifecycle: StockManagedProductData = {
	name: uniqueValue( 'POS-581 Simple Product' ),
	regular_price: '19.99',
	manage_stock: true,
	stock_quantity: 10,
};

export const posDeleteMeProduct: CreateProductData = {
	name: uniqueValue( 'POS-582 Delete Me' ),
	regular_price: '5.00',
};

export const posOriginalNameProduct: CreateProductData = {
	name: uniqueValue( 'POS-583 Original Name' ),
	regular_price: '10.00',
};

export const posUpdatedProductName = uniqueValue( 'POS-583 Updated Name' );

export const posExcludeMeProduct: CreateProductData = {
	name: uniqueValue( 'POS-578 Exclude Me' ),
	regular_price: '15.00',
};

export const posTypeChangeProduct: CreateProductData = {
	name: uniqueValue( 'POS-580 Type Change' ),
	regular_price: '20.00',
};

export const posShirtVariableProduct: VariableAttributeProductData = {
	name: uniqueValue( 'POS-584 T-Shirt' ),
	type: 'variable',
	attributes: [
		{
			name: 'Size',
			variation: true,
			visible: true,
			options: [ 'S', 'L', 'XL' ],
		},
	],
};

export const posNoTaxRateProduct: CreateProductData = {
	name: uniqueValue( 'POS-650 No Tax Rate' ),
	regular_price: '15.00',
};

export const posNoRatesTaxClassName = uniqueValue( 'POS-650 No Rates' );

export const posLastVariationProduct: VariableAttributeProductData = {
	name: uniqueValue( 'POS-662 Last Variation' ),
	type: 'variable',
	attributes: [
		{
			name: 'Size',
			variation: true,
			visible: true,
			options: [ 'S' ],
		},
	],
};

export const posHiddenCatalogProduct: CreateProductData = {
	name: uniqueValue( 'POS-651 Hidden Catalog Visibility' ),
	regular_price: '10.00',
	catalog_visibility: 'hidden',
};

export const posSkuOnCreate = uniqueValue( 'WC-SKU-0001', '-' );
export const posSkuOnUpdate = uniqueValue( 'WC-SKU-0002', '-' );

export const posSkuSyncProduct: CreateProductData = {
	name: uniqueValue( 'POS-664 SKU Sync Product' ),
	regular_price: '12.00',
	sku: posSkuOnCreate,
};

export const posPrivateProduct: CreateProductData = {
	name: uniqueValue( 'POS-665 Private Product' ),
	regular_price: '10.00',
	status: 'private',
};

export const posCurrencyMismatchProduct: CreateProductData = {
	name: uniqueValue( 'POS-590 Currency Mismatch' ),
	regular_price: '25.00',
};
