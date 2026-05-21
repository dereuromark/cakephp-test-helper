import { defineConfig } from 'vitepress'

function docsSidebar() {
  return [
    {
      text: 'Documentation',
      items: [
        { text: 'Overview', link: '/guide' },
        { text: 'Association vs DB Audit', link: '/Associations' },
        { text: 'SQL to Query Builder', link: '/SqlConverter' },
        { text: 'Custom Linter Tasks', link: '/Linter' },
      ],
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
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide', activeMatch: '/guide' },
      {
        text: 'Tools',
        items: [
          { text: 'Association vs DB Audit', link: '/Associations' },
          { text: 'SQL to Query Builder', link: '/SqlConverter' },
          { text: 'Custom Linter Tasks', link: '/Linter' },
        ],
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
