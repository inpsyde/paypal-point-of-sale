import { WpPage } from '@inpsyde/playwright-utils/build';

export class WcStatusLogsPage extends WpPage {
	url = '/wp-admin/admin.php?page=wc-status&tab=logs';

	sourceLink = ( source: string ) =>
		this.page.getByRole( 'link', { name: source, exact: true } );
	logEntries = () => this.page.locator( '#logs-entries' );

	async viewLatestLogForSource( source: string ): Promise< string > {
		await this.visit();
		await this.sourceLink( source ).first().click();
		await this.page.waitForLoadState( 'load' );

		return ( await this.logEntries().textContent() ) ?? '';
	}
}
