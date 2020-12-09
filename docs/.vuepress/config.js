module.exports = {
    markdown: {
        anchor: { level: [2, 3] },
        extendMarkdown(md) {
            let markup = require('vuepress-theme-craftdocs/markup');
            md.use(markup);
        },
    },
    base: '/upvote/',
    title: 'Upvote plugin for Craft CMS',
    plugins: [
        [
            'vuepress-plugin-clean-urls',
            {
                normalSuffix: '/',
                indexSuffix: '/',
                notFoundPath: '/404.html',
            },
        ],
    ],
    theme: 'craftdocs',
    themeConfig: {
        codeLanguages: {
            php: 'PHP',
            twig: 'Twig',
            js: 'JavaScript',
        },
        logo: '/images/icon.svg',
        searchMaxSuggestions: 10,
        nav: [
            {text: 'Getting Started️', link: '/getting-started/'},
            {
                text: 'How It Works',
                items: [
                    {text: 'Display voting arrows', link: '/display-voting-arrows/'},
                    {text: 'Customize your icons', link: '/customize-your-icons/'},
                    {text: 'Customize your CSS', link: '/customize-your-css/'},
                    {text: 'Sort by highest voted', link: '/sort-by-highest-voted/'},
                    {text: 'Multiple voting for the same element', link: '/multiple-voting-for-the-same-element/'},
                    {text: 'User Vote History', link: '/user-vote-history/'},
                    {text: 'Element Vote History', link: '/element-vote-history/'},
                    {text: 'Disable JS or CSS', link: '/disable-js-or-css/'},
                    {text: 'Disable JS Preloading', link: '/disable-js-preloading/'},
                    {text: 'Getting Vote Totals', link: '/getting-vote-totals/'},
                    {text: 'Control the output format', link: '/control-the-output-format/'},
                    {text: 'Cast a vote on behalf of a specific user', link: '/cast-a-vote-on-behalf-of-a-specific-user/'},
                    {text: 'Events', link: '/events/'},
                    {text: 'Caching', link: '/caching/'},
                    {text: 'BREAKING CHANGE (v2.0.0)', link: '/breaking-change-v2-0-0/'},
                ]
            },
            {
                text: 'More',
                items: [
                    {text: 'Double Secret Agency', link: 'https://www.doublesecretagency.com/plugins'},
                    {text: 'Our other Craft plugins', link: 'https://plugins.doublesecretagency.com', target:'_self'},
                ]
            },
        ],
        sidebar: {
            '/': [
                'getting-started',
                'display-voting-arrows',
                'customize-your-icons',
                'customize-your-css',
                'sort-by-highest-voted',
                'multiple-voting-for-the-same-element',
                'user-vote-history',
                'element-vote-history',
                'disable-js-or-css',
                'disable-js-preloading',
                'getting-vote-totals',
                'control-the-output-format',
                'cast-a-vote-on-behalf-of-a-specific-user',
                'events',
                'caching',
                'breaking-change-v2-0-0',
            ],
        }
    }
};
