import { test as base, BaseExtend } from '@inpsyde/playwright-utils/build';
import { PosSettingsPage } from './admin/pos-settings.pom';
import { WcProductsPage } from './admin/wc-product-list.pom';
import { WcProductEditPage } from './admin/wc-product-edit.pom';
import { WcStatusLogsPage } from './admin/wc-status-logs.pom';

type ProjectExtend = BaseExtend & {
	posSettings: PosSettingsPage;
	wcProducts: WcProductsPage;
	wcProductEdit: WcProductEditPage;
	wcStatusLogs: WcStatusLogsPage;
};

const test = base.extend< ProjectExtend >( {
	posSettings: async ( { page, sitePrefixRef }, use ) => {
		await use(
			new PosSettingsPage( {
				page,
				sitePrefix: () => sitePrefixRef.current,
			} )
		);
	},
	wcProducts: async ( { page, sitePrefixRef }, use ) => {
		await use(
			new WcProductsPage( {
				page,
				sitePrefix: () => sitePrefixRef.current,
			} )
		);
	},
	wcProductEdit: async ( { page, sitePrefixRef }, use ) => {
		await use(
			new WcProductEditPage( {
				page,
				sitePrefix: () => sitePrefixRef.current,
			} )
		);
	},
	wcStatusLogs: async ( { page, sitePrefixRef }, use ) => {
		await use(
			new WcStatusLogsPage( {
				page,
				sitePrefix: () => sitePrefixRef.current,
			} )
		);
	},
} );

export { test };
export type { ProjectExtend as BaseExtend };
