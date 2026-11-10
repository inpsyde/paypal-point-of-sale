import type { StockManagedProductData } from '../../../resources/types';

export const posStockUpdateProduct: StockManagedProductData = {
	name: 'POS-587 Stock Update',
	regular_price: '12.00',
	manage_stock: true,
	stock_quantity: 10,
};

export const posStockMgmtDisableProduct: StockManagedProductData = {
	name: 'POS-588 Stock Mgmt Disable',
	regular_price: '8.00',
	manage_stock: true,
	stock_quantity: 15,
};

export const posOrderStockProduct: StockManagedProductData = {
	name: 'POS-586 Order Stock',
	regular_price: '20.00',
	manage_stock: true,
	stock_quantity: 20,
};

export const posWebhookStockProduct: StockManagedProductData = {
	name: 'POS-585 Webhook Stock',
	regular_price: '15.00',
	manage_stock: true,
	stock_quantity: 20,
};
