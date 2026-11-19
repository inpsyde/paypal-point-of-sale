/* eslint-disable */
import SyncProgress from './modules/sync-progress.module';

const syncProgressElements = document.querySelectorAll('*[data-sync-progress="true"]');

syncProgressElements.forEach(syncProgressElement => {
    new SyncProgress(
        syncProgressElement,
        zettleQueueProcessEndpoint.url,
        zettleQueueProcessEndpoint
    );
});
