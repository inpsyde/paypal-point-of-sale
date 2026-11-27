import { APIRequestContext } from '@playwright/test';

export type ZettleWebhookSubscription = {
	uuid: string;
	eventNames: string[];
	destination: string;
	status?: string;
};

export type ZettleProduct = {
	name?: string;
	uuid?: string;
	variants?: {
		sku?: string;
		// Absent (rather than zeroed) when PayPal POS excludes price from the sync
		// payload — e.g. on a WC/POS currency mismatch. Shape per Product Library v2.
		price?: { amount?: number; currencyId?: string };
	}[];
};

export type ZettleVariantStock = {
	productUuid: string;
	variantUuid: string;
	balance: number;
};

/**
 * API client for Zettle / PayPal POS endpoints.
 *
 * Covers:
 *  - OAuth token exchange (client_credentials grant)
 *  - Product Library v2  — CRUD on Zettle products
 *  - Inventory v3        — stock balance reads & updates
 *  - Webhook subscriptions — list what's actually registered remotely
 */
export class ZettleApiClient {
	private token: string | null = null;

	// eslint-disable-next-line no-useless-constructor -- TS parameter property; base rule doesn't recognize it assigns this.request
	constructor( private readonly request: APIRequestContext ) {}

	// ── OAuth ─────────────────────────────────────────────────────────────────

	async authenticate(
		clientId: string,
		clientSecret: string
	): Promise< void > {
		const response = await this.request.post(
			'https://oauth.izettle.com/token',
			{
				form: {
					grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					client_id: clientId,
					assertion: clientSecret,
				},
			}
		);
		const body = await response.json();
		this.token = body.access_token;
	}

	// ── Product Library ───────────────────────────────────────────────────────

	async getProducts(): Promise< unknown[] > {
		const response = await this.request.get(
			'https://products.izettle.com/organizations/self/products/v2',
			{ headers: this.authHeaders() }
		);
		return response.json();
	}

	/**
	 * Read one product by UUID; undefined when PayPal POS no longer has it.
	 * @param uuid
	 */
	async getProduct( uuid: string ): Promise< ZettleProduct | undefined > {
		const response = await this.request.get(
			`https://products.izettle.com/organizations/self/products/${ uuid }`,
			{ headers: this.authHeaders() }
		);
		if ( response.status() === 404 ) {
			return undefined;
		}
		if ( ! response.ok() ) {
			throw new Error(
				`ZettleApiClient: GET product ${ uuid } failed with ${ response.status() }: ${ await response.text() }`
			);
		}
		return response.json();
	}

	async deleteProduct( uuid: string ): Promise< void > {
		await this.request.delete(
			`https://products.izettle.com/organizations/self/products/${ uuid }`,
			{ headers: this.authHeaders() }
		);
	}

	// Only for products that never synced, so they have no UUID; use getProduct() otherwise.
	async findProductByName(
		name: string
	): Promise< ZettleProduct | undefined > {
		const products = ( await this.getProducts() ) as ZettleProduct[];
		return products.find( ( product ) => product.name === name );
	}

	/**
	 * Delete a remote product by name, if one exists — used to clean up debris left behind
	 * when a rejection/exclusion test's product never made it into the WC↔POS id map (so
	 * deleteProduct(cli, ...) alone wouldn't know to remove it remotely).
	 * @param name
	 */
	async deleteProductByName( name: string ): Promise< void > {
		const found = await this.findProductByName( name );
		if ( found?.uuid ) {
			await this.deleteProduct( found.uuid );
		}
	}

	// ── Inventory ─────────────────────────────────────────────────────────────

	/**
	 * Stock balance of each variant of a product in the STORE inventory, the same
	 * endpoint the plugin's SyncStockJob reads. PayPal POS keeps returning the balance
	 * after tracking stops, so this does not tell whether the stock is tracked.
	 * @param productUuid
	 */
	async getProductStock(
		productUuid: string
	): Promise< ZettleVariantStock[] > {
		const storeUuid = await this.getStoreInventoryUuid();
		const response = await this.request.get(
			`https://inventory.izettle.com/v3/stock/${ storeUuid }/products/${ productUuid }`,
			{ headers: this.authHeaders() }
		);
		if ( response.status() === 404 ) {
			return [];
		}
		if ( ! response.ok() ) {
			throw new Error(
				`ZettleApiClient: GET stock of product ${ productUuid } failed with ${ response.status() }: ${ await response.text() }`
			);
		}
		const stock = ( await response.json() ) as ZettleVariantStock[];
		return stock.map( ( variant ) => ( {
			...variant,
			balance: Number( variant.balance ),
		} ) );
	}

	private async getStoreInventoryUuid(): Promise< string > {
		const response = await this.request.get(
			'https://inventory.izettle.com/v3/inventories',
			{ headers: this.authHeaders() }
		);
		if ( ! response.ok() ) {
			throw new Error(
				`ZettleApiClient: GET inventories failed with ${ response.status() }: ${ await response.text() }`
			);
		}
		const inventories = ( await response.json() ) as {
			inventoryUuid: string;
			inventoryType: string;
		}[];
		const store = inventories.find(
			( inventory ) => inventory.inventoryType === 'STORE'
		);
		if ( ! store ) {
			throw new Error( 'ZettleApiClient: no STORE inventory found' );
		}
		return store.inventoryUuid;
	}

	// ── Webhooks ──────────────────────────────────────────────────────────────

	async getWebhookSubscriptions(): Promise< ZettleWebhookSubscription[] > {
		const response = await this.request.get(
			'https://pusher.izettle.com/organizations/self/subscriptions',
			{ headers: this.authHeaders() }
		);
		return response.json();
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private authHeaders(): Record< string, string > {
		if ( ! this.token ) {
			throw new Error( 'ZettleApiClient: call authenticate() first' );
		}
		return { Authorization: `Bearer ${ this.token }` };
	}
}
