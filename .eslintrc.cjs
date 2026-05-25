module.exports = {
    globals: {
        appVersion: true
    },
    parserOptions: {
        requireConfigFile: false,
    },
    extends: [
        '@nextcloud',
    ],
    rules: {
        'import/extensions': 'off',
        'jsdoc/require-jsdoc': 'off',
        'jsdoc/tag-lines': 'off',
        'vue/first-attribute-linebreak': 'off',
        'vue/html-indent': ['error', 4],
        indent: ['error', 4],
    },
}
