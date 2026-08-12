import * as crypto from 'crypto';
import { runWpCli, type AnyCli } from './pos-cli.helper';

// 'paypal-pos.webhook.listener' isn't its own WP option — it's a nested key inside
// woocommerce_zettle_settings, same as api_token/sdk.integration-id.
export async function getWebhookConfig( cli: AnyCli ): Promise< {
	signingKey?: string;
	destination?: string;
	eventNames?: string[];
} | null > {
	try {
		const raw = await runWpCli(
			cli,
			'option get woocommerce_zettle_settings --format=json'
		);
		return JSON.parse( raw )[ 'paypal-pos.webhook.listener' ] ?? null;
	} catch {
		return null;
	}
}

/**
 * Read the PayPal POS webhook listener's signing key, or '' if not registered.
 * @param cli
 */
export async function getWebhookSigningKey( cli: AnyCli ): Promise< string > {
	const config = await getWebhookConfig( cli );
	return config?.signingKey ?? '';
}

// POS-585 needs a key regardless of how registration happened; POS-591 tests that connect()
// itself triggers registration, so it must keep calling getWebhookSigningKey directly.
export async function ensureWebhookRegistered(
	cli: AnyCli
): Promise< string > {
	const existing = await getWebhookSigningKey( cli );
	if ( existing ) {
		return existing;
	}
	await runWpCli( cli, 'zettle webhook register' ).catch( () => {} );
	return getWebhookSigningKey( cli );
}

async function getPosIdMapUuid(
	cli: AnyCli,
	localId: number,
	type: 'product' | 'variant'
): Promise< string | null > {
	try {
		const prefix = ( await runWpCli( cli, 'db prefix' ) ).trim();
		const raw = await runWpCli(
			cli,
			`db query 'SELECT type, remote_id FROM ${ prefix }zettle_woocommerce_id_map WHERE local_id = ${ localId }' --skip-column-names`
		);
		const row = raw
			.split( '\n' )
			.map( ( line ) => line.trim() )
			.find( ( line ) => line.startsWith( `${ type }\t` ) );
		if ( ! row ) {
			return null;
		}
		const uuid = row.split( '\t' )[ 1 ]?.trim();
		return uuid || null;
	} catch {
		return null;
	}
}

/**
 * Look up the POS-side variant UUID for a synced WC product, or null if not in the ID map.
 * @param cli
 * @param wcProductId
 */
export async function getPosVariantUuid(
	cli: AnyCli,
	wcProductId: number
): Promise< string | null > {
	return getPosIdMapUuid( cli, wcProductId, 'variant' );
}

/**
 * Look up the POS-side product UUID for a synced WC product, or null if not in the ID map.
 * @param cli
 * @param wcProductId
 */
export async function getPosProductUuid(
	cli: AnyCli,
	wcProductId: number
): Promise< string | null > {
	return getPosIdMapUuid( cli, wcProductId, 'product' );
}

/**
 * Sign a webhook payload the same way PayPal POS/Zettle does, for simulating incoming webhooks.
 * @param timestamp
 * @param payloadString
 * @param signingKey
 */
export function signWebhookPayload(
	timestamp: string,
	payloadString: string,
	signingKey: string
): string {
	return crypto
		.createHmac( 'sha256', signingKey )
		.update( `${ timestamp }.${ payloadString }` )
		.digest( 'hex' );
}
