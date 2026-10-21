export default {
	slug: 'flavor-like',
	mainFile: 'flavor-like.php',
	textDomain: 'flavor-like',

	assets: {
		js: {
			bundles: [
				{
					sources: [
						'assets/js/src/tooltip.js',
						'assets/js/src/notifications.js',
						'assets/js/src/flavor-like.js',
						'assets/js/src/scripts.js',
					],
					output: 'assets/js/flavor-like.js',
					minOutput: 'assets/js/flavor-like.min.js',
				},
			],
			minify: [],
		},
		css: {
			sassEntries: {
				'assets/css/flavor-like.css': 'assets/sass/flavor-like.scss',
				'admin/assets/css/admin.css': 'admin/assets/sass/admin.scss',
				'admin/assets/css/plugins.css': 'admin/assets/sass/plugins.scss',
			},
			minifySeparate: [
				'assets/css/flavor-like.css',
				'admin/assets/css/admin.css',
				'admin/assets/css/plugins.css',
			],
		},
		watch: {
			scss: [],
			js: [],
		},
	},

	build: {
		excludes: [
			'assets/sass',
			'admin/assets/sass',
			'assets/js/src',
			'admin/assets/js/src',
			'wp-toolkit.config.mjs',
			'includes/blocks/button/src',
			'includes/blocks/top-content/src',
			'includes/blocks/button/package.json',
			'includes/blocks/button/package-lock.json',
			'includes/blocks/top-content/package.json',
			'includes/blocks/top-content/package-lock.json',
			'includes/blocks/button/webpack.config.js',
			'includes/blocks/top-content/webpack.config.js',
		],
		devOnlyFiles: ['assets/js/flavor-like.js', 'assets/css/flavor-like.css'],
		preprocess: {
			DEV: false,
			TODO: false,
			LITE: true,
			PRO: false,
		},
		hooks: {
			preBuild: ['npm run build:blocks'],
			postBuild: [],
		},
		zipName: '{slug}.zip',
		trimTrailingWhitespace: true
	},

	deploy: {
		prod: { envPrefix: 'DEPLOY_PROD' },
		kinsta: { envPrefix: 'DEPLOY_KINSTA' },
	},

	release: {
		enabled: true,
		wpAssets: 'wp-assets',
		svnUrl: 'https://plugins.svn.wordpress.org/flavor-like',
	},

	i18n: {
		domain: 'flavor-like',
		potFile: 'languages/flavor-like.pot',
		exclude: 'build,node_modules,admin/includes/statistics,admin/includes/optiwich,includes/blocks',
		headers: {
			'Report-Msgid-Bugs-To': 'https://github.com/Jacky088/flavor-like',
			'Language-Team': 'Flavor Like Team',
		},
	},
};
