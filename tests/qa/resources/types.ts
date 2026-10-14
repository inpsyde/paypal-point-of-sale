/** Shared type definitions for test resources (fixtures, files, static data). */

import type { CreateProductData } from '../utils/helpers/wc-product.helper';

export interface PluginZipEntry {
	name: string;
	slug: string;
	zipFilePath: string;
}

export type StockManagedProductData = CreateProductData & {
	manage_stock: true;
	stock_quantity: number;
};

export type VariableAttributeProductData = CreateProductData & {
	type: 'variable';
	attributes: Array< {
		name: string;
		variation: true;
		visible: true;
		options: string[];
	} >;
};
