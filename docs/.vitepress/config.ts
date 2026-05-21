import { defineConfig } from 'vitepress'

function toolItems() {
  return [
    { text: 'Association vs DB Audit', link: '/Associations' },
    { text: 'SQL to Query Builder', link: '/SqlConverter' },
    { text: 'Test Runner', link: '/TestRunner' },
    { text: 'Fixture Check', link: '/FixtureCheck' },
    { text: 'URL Tools', link: '/UrlTools' },
    { text: 'Plugin Info', link: '/Plugins' },
    { text: 'Custom Linter Tasks', link: '/Linter' },
  ]
}

function docsSidebar() {
  return [
    {
      text: 'Guide',
      items: [
        { text: 'Overview', link: '/guide' },
        { text: 'Configuration', link: '/Configuration' },
        { text: 'Troubleshooting', link: '/Troubleshooting' },
      ],
    },
    {
      text: 'Tools',
      items: toolItems(),
    },
  ]
}

export default defineConfig({
  title: 'cakephp-test-helper',
  description: 'Developer backend for CakePHP: association vs DB foreign-key audit, SQL to Query Builder converter, fixture and test helpers, and custom linter tasks.',
  base: '/cakephp-test-helper/',
  lastUpdated: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-test-helper/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-test-helper/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide', activeMatch: '/(guide|Configuration|Troubleshooting)' },
      {
        text: 'Tools',
        items: toolItems(),
      },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-test-helper' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-test-helper' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-test-helper/issues' },
        ],
      },
    ],
    sidebar: {
      '/': docsSidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-test-helper' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-test-helper/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
