const Encore = require('@symfony/webpack-encore');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

Encore
    .setOutputPath('assets/')
    // when fonts, images or attachments are generated, they are stored in "assets/{img|font|..}/"
    // and linked via "./" in CSS.
    .setPublicPath('./')
    .setManifestKeyPrefix('./')
    .addStyleEntry('product-debug', './resources/scss/product-debug.scss')
    .addEntry('product-debug-modules', './resources/js/product-debug.modules.js')
    .enableSassLoader()
    .enablePostCssLoader()
    .enableForkedTypeScriptTypesChecking()
    .enableSourceMaps(!Encore.isProduction())

    .cleanupOutputBeforeBuild(['*.js', '*.css'])
    .disableSingleRuntimeChunk()
    .addPlugin(new DependencyExtractionWebpackPlugin())
;

module.exports = Encore.getWebpackConfig();
