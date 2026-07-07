import type { VipCli, SshCli, WpEnvCli, LocalhostCli, DdevCli } from '@inpsyde/playwright-utils/build';
import type { PosSettingsPage } from '../admin';

export type AnyCli = VipCli | SshCli | WpEnvCli | LocalhostCli | DdevCli;

// Routes through whichever cli implementation is active (wpenv, ssh, ddev, ...).
// WpCli.execute() is protected in TypeScript only — the cast is intentional.
export async function runWpCli( cli: AnyCli, command: string ): Promise< string > {
    return ( cli as any ).execute( command );
}

export async function resetOnboarding( cli: AnyCli ): Promise< void > {
    await runWpCli( cli, 'zettle reset onboarding complete' );
}

export async function processQueue( cli: AnyCli ): Promise< void > {
    await runWpCli( cli, 'zettle queue process' ).catch( () => {} );
}

export async function syncProduct( cli: AnyCli, productId: number ): Promise< void > {
    await runWpCli( cli, `zettle sync product ${ productId }` );
}

// Lets a spec file run standalone (its own PayPal POS connection) without paying the ~1
// minute connect cost when the full suite already connected via the setup:paypal-pos project.
// Checks the live settings page rather than a WP option, since that's the same source of
// truth the UI itself renders from.
export async function ensurePosConnected( posSettings: PosSettingsPage, cli: AnyCli ): Promise< void > {
    const apiKey = process.env.PAYPAL_POS_API_KEY;
    if ( ! apiKey ) {
        return;
    }

    await posSettings.visit();
    if ( await posSettings.productsCountText().isVisible() ) {
        return;
    }

    await posSettings.connect( apiKey, cli );
}
