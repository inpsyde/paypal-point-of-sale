import { test as base, BaseExtend } from '@inpsyde/playwright-utils/build';
import { PosSettingsPage } from './admin/PosSettingsPage';
import { WcProductsPage } from './admin/WcProductsPage';
import { WcProductEditPage } from './admin/WcProductEditPage';

type ProjectExtend = BaseExtend & {
    posSettings: PosSettingsPage;
    wcProducts: WcProductsPage;
    wcProductEdit: WcProductEditPage;
};

const test = base.extend< ProjectExtend >( {
    posSettings: async ( { page, sitePrefixRef }, use ) => {
        await use( new PosSettingsPage( { page, sitePrefix: () => sitePrefixRef.current } ) );
    },
    wcProducts: async ( { page, sitePrefixRef }, use ) => {
        await use( new WcProductsPage( { page, sitePrefix: () => sitePrefixRef.current } ) );
    },
    wcProductEdit: async ( { page, sitePrefixRef }, use ) => {
        await use( new WcProductEditPage( { page, sitePrefix: () => sitePrefixRef.current } ) );
    },
} );

export { test };
export type { ProjectExtend as BaseExtend };
