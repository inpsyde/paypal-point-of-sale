const Encore = require('@symfony/webpack-encore');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

Encore
    .setOutputPath('assets/')
    // when fonts, images or attachments are generated, they are stored in "assets/{img|font|..}/"
    // and linked via "./" in CSS.
    .setPublicPath('./')
    .setManifestKeyPrefix('./')
    .addEntry('admin-scripts', './resources/js/admin.js')
    .addEntry('sync-scripts', './resources/js/sync.js')
    .addStyleEntry('admin', './resources/scss/admin.scss')
    .enableSassLoader()
    .enablePostCssLoader()
    .enableForkedTypeScriptTypesChecking()
    .enableSourceMaps(!Encore.isProduction())

    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .addPlugin(new DependencyExtractionWebpackPlugin())
;

module.exports = Encore.getWebpackConfig();
