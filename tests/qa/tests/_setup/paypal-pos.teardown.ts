import { test as teardown } from '../../utils';
import { resetOnboarding } from '../../utils';

teardown( 'Reset PayPal POS state', async ( { cli } ) => {
    await resetOnboarding( cli );
} );
