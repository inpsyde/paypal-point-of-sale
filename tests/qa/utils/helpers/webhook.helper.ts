import * as crypto from 'crypto';
import { expect } from '@inpsyde/playwright-utils/build';
import { runWpCli, type AnyCli } from './pos-cli.helper';
import type { ZettleApiClient, ZettleProduct } from '../api';

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

/**
 * Whether PayPal POS can reach this site, which webhook registration needs:
 * ngrok is on, or the base URL is not a local one.
 */
export function canReceiveWebhooks(): boolean {
	return (
		process.env.NGROK_ENABLED === 'true' ||
		! /localhost|127\.0\.0\.1/.test( process.env.WP_BASE_URL ?? '' )
	);
}

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
 * Read the PayPal POS product a synced WC product maps to. Looks it up by the UUID in the
 * ID map, not by name, so a leftover product from an earlier run can never match.
 * @param cli
 * @param zettleApi
 * @param wcProductId
 */
export async function getSyncedPosProduct(
	cli: AnyCli,
	zettleApi: ZettleApiClient,
	wcProductId: number
): Promise< ZettleProduct | undefined > {
	const uuid = await getPosProductUuid( cli, wcProductId );
	expect(
		uuid,
		`Assert WC product ${ wcProductId } has a PayPal POS UUID in the ID map`
	).not.toBeNull();
	return zettleApi.getProduct( uuid as string );
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
