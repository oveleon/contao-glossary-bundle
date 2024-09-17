const path = require('path');

module.exports = {
    mode: 'production',
    entry: './public/scripts/index.js',
    output: {
        library: "Glossary",
        libraryTarget: "var",
        filename: 'main.js',
        path: path.resolve(__dirname, 'public/scripts/dist'),
    },
    optimization: {
        minimize: true
    }
};
