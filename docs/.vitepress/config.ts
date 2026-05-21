import { defineConfig } from 'vitepress'

function toolItems() {
  return [
    { text: 'Association vs DB Audit', link: '/associations' },
    { text: 'SQL to Query Builder', link: '/sql-converter' },
    { text: 'Test Runner', link: '/test-runner' },
    { text: 'Fixture Check', link: '/fixture-check' },
    { text: 'URL Tools', link: '/url-tools' },
    { text: 'Plugin Info', link: '/plugins' },
    { text: 'Custom Linter Tasks', link: '/linter' },
  ]
}

function docsSidebar() {
  return [
    {
      text: 'Guide',
      items: [
        { text: 'Overview', link: '/guide' },
        { text: 'Configuration', link: '/configuration' },
        { text: 'Troubleshooting', link: '/troubleshooting' },
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
  cleanUrls: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-test-helper/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-test-helper/favicon.svg', type: 'image/svg+xml' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'cakephp-test-helper' }],
    ['meta', { property: 'og:description', content: 'Developer backend for CakePHP: association/DB audit, SQL to Query Builder, fixture and test helpers, and linters.' }],
    ['meta', { property: 'og:image', content: 'https://dereuromark.github.io/cakephp-test-helper/logo.svg' }],
    ['meta', { property: 'og:url', content: 'https://dereuromark.github.io/cakephp-test-helper/' }],
    ['meta', { name: 'twitter:card', content: 'summary' }],
    ['meta', { name: 'twitter:image', content: 'https://dereuromark.github.io/cakephp-test-helper/logo.svg' }],
  ],
  markdown: {
    lineNumbers: true,
  },
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide', activeMatch: '/(guide|configuration|troubleshooting)' },
      {
        text: 'Tools',
        items: toolItems(),
      },
      {
        text: 'Links',
        items: [
          { text: 'Changelog', link: 'https://github.com/dereuromark/cakephp-test-helper/releases' },
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
