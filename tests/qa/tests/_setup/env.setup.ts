import { test as setup, resetEnvironment } from '../../utils';

setup( 'Setup: Reset Environment', async ( { cli } ) => {
	setup.skip(
		process.env.E2E_CONFIRM_RESET !== 'true',
		'Set E2E_CONFIRM_RESET=true to run this destructive reset.'
	);

	await resetEnvironment( cli );
} );
