import type { VipCli, SshCli, WpEnvCli, LocalhostCli, DdevCli } from '@inpsyde/playwright-utils/build';

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
