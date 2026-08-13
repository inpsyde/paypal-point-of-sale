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
	variants?: { sku?: string }[];
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

	async deleteProduct( uuid: string ): Promise< void > {
		await this.request.delete(
			`https://products.izettle.com/organizations/self/products/${ uuid }`,
			{ headers: this.authHeaders() }
		);
	}

	// ── Inventory ─────────────────────────────────────────────────────────────

	async getInventoryBalance( locationUuid: string ): Promise< unknown > {
		const response = await this.request.get(
			`https://inventory.izettle.com/organizations/self/inventory/locations/${ locationUuid }`,
			{ headers: this.authHeaders() }
		);
		return response.json();
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
