/** Shared type definitions for test resources (fixtures, files, static data). */

import type { CreateProductData } from '../utils/helpers/wc-product.helper';

export interface PluginZipEntry {
	/** Full visible name of the plugin as shown in the WordPress admin */
	name: string;
	/** WordPress plugin slug (directory name inside the zip) */
	slug: string;
	/** Absolute path to the zip file inside resources/files/ */
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
