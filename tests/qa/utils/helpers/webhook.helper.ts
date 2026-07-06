import * as crypto from 'crypto';
import { runWpCli, type AnyCli } from './pos-cli.helper';

/** Read the PayPal POS webhook listener's signing key, or '' if not registered. */
export async function getWebhookSigningKey( cli: AnyCli ): Promise< string > {
    try {
        const raw = await runWpCli( cli, "wp option get 'paypal-pos.webhook.listener' --format=json" );
        const config = JSON.parse( raw );
        return config.signingKey ?? '';
    } catch {
        return '';
    }
}

/** Look up the POS-side variant UUID for a synced WC product, or null if not in the ID map. */
export async function getPosVariantUuid( cli: AnyCli, wcProductId: number ): Promise< string | null > {
    try {
        const raw = await runWpCli(
            cli,
            `wp db query "SELECT remote_id FROM $(wp db prefix)zettle_woocommerce_id_map WHERE local_id = ${ wcProductId } AND type = 'variant' LIMIT 1" --skip-column-names`
        );
        const uuid = raw.trim();
        return uuid || null;
    } catch {
        return null;
    }
}

/** Sign a webhook payload the same way PayPal POS/Zettle does, for simulating incoming webhooks. */
export function signWebhookPayload( timestamp: string, payloadString: string, signingKey: string ): string {
    return crypto
        .createHmac( 'sha256', signingKey )
        .update( `${ timestamp }.${ payloadString }` )
        .digest( 'hex' );
}
