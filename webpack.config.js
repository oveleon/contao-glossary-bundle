const path = require('path');

module.exports = {
    mode: 'production',
    entry: './src/Resources/public/scripts/index.js',
    output: {
        library: "Glossary",
        libraryTarget: "var",
        filename: 'main.js',
        path: path.resolve(__dirname, 'src/Resources/public/scripts/dist'),
    },
    optimization: {
        minimize: true
    }
};
