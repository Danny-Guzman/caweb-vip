/**
 * WPGulp Configuration File
 *
 * 1. Edit the variables as per your project requirements.
 * 2. In paths you can add <<glob or array of globs>>.
 *
 * @package WPGulp
 */


module.exports = {
	adminCSS:[ // WP Backend Admin CSS
		'assets/scss/admin.scss',
	],
	adminJS: [ // WP Backend Admin JS
		'assets/js/bootstrap/bootstrap.bundle.js',
		'assets/js/caweb/options/*.js',
		'assets/js/caweb/admin.js',
	], 
	frontendCSS: [], // Frontend CSS
	frontendJS: [], // Frontend JS 
};
