import { APIRequestContext } from '@playwright/test';

/**
 * API client for Zettle / PayPal POS endpoints.
 *
 * Covers:
 *  - OAuth token exchange (client_credentials grant)
 *  - Product Library v2  — CRUD on Zettle products
 *  - Inventory v3        — stock balance reads & updates
 */
export class ZettleApiClient {
    private token: string | null = null;

    constructor( private readonly request: APIRequestContext ) {}

    // ── OAuth ─────────────────────────────────────────────────────────────────

    async authenticate( clientId: string, clientSecret: string ): Promise< void > {
        const response = await this.request.post(
            'https://oauth.zettle.com/token',
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

    // ── Inventory ─────────────────────────────────────────────────────────────

    async getInventoryBalance( locationUuid: string ): Promise< unknown > {
        const response = await this.request.get(
            `https://inventory.izettle.com/organizations/self/inventory/locations/${ locationUuid }`,
            { headers: this.authHeaders() }
        );
        return response.json();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private authHeaders(): Record< string, string > {
        if ( ! this.token ) throw new Error( 'ZettleApiClient: call authenticate() first' );
        return { Authorization: `Bearer ${ this.token }` };
    }
}
