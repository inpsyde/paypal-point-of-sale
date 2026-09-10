import type { CreateProductData } from '../../../utils';
import type {
	StockManagedProductData,
	VariableAttributeProductData,
} from '../../../resources/types';

export const posColumnTestProduct: CreateProductData = {
	name: 'POS-579 Column Test',
	regular_price: '5.00',
};

export const posDraftProduct: CreateProductData = {
	name: 'POS-573 Draft Product',
	status: 'draft',
	regular_price: '9.99',
};

export const posSimpleProductLifecycle: StockManagedProductData = {
	name: 'POS-581 Simple Product',
	regular_price: '19.99',
	manage_stock: true,
	stock_quantity: 10,
};

export const posDeleteMeProduct: CreateProductData = {
	name: 'POS-582 Delete Me',
	regular_price: '5.00',
};

export const posOriginalNameProduct: CreateProductData = {
	name: 'POS-583 Original Name',
	regular_price: '10.00',
};

export const posExcludeMeProduct: CreateProductData = {
	name: 'POS-578 Exclude Me',
	regular_price: '15.00',
};

export const posTypeChangeProduct: CreateProductData = {
	name: 'POS-580 Type Change',
	regular_price: '20.00',
};

export const posShirtVariableProduct: VariableAttributeProductData = {
	name: 'POS-584 T-Shirt',
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

// Static portion only — `tax_class` is a runtime value (a tax class created earlier in the
// test) and gets merged in at the createProduct() call site in product-sync.spec.ts.
export const posNoTaxRateProduct: CreateProductData = {
	name: 'POS-650 No Tax Rate',
	regular_price: '15.00',
};

export const posLastVariationProduct: VariableAttributeProductData = {
	name: 'POS-662 Last Variation',
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
	name: 'POS-651 Hidden Catalog Visibility',
	regular_price: '10.00',
	catalog_visibility: 'hidden',
};

export const posSkuSyncProduct: CreateProductData = {
	name: 'POS-664 SKU Sync Product',
	regular_price: '12.00',
	sku: 'WC-SKU-0001',
};
