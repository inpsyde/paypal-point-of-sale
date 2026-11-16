const Encore = require('@symfony/webpack-encore');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

Encore
    .setOutputPath('assets/')
    // when fonts, images or attachments are generated, they are stored in "assets/{img|font|..}/"
    // and linked via "./" in CSS.
    .setPublicPath('./')
    .setManifestKeyPrefix('./')
    .addEntry('products-editor', './resources/js/products-editor.js')
    .addStyleEntry('products-style', './resources/scss/products-style.scss')
    .enableSassLoader()
    .enablePostCssLoader()
    .enableForkedTypeScriptTypesChecking()
    .enableSourceMaps(!Encore.isProduction())

    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()
    .addPlugin(new DependencyExtractionWebpackPlugin())
;

module.exports = Encore.getWebpackConfig();
