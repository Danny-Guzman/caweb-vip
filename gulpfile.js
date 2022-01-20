/**
 * Gulpfile.
 *
 * Gulp for CAWeb WordPress.
 * 
 * This Gulpfile is a modified version of WPGulp.
 * @tutorial https://github.com/ahmadawais/WPGulp
 * @author Ahmad Awais <https://twitter.com/MrAhmadAwais/>
 */

/**
 * Load WPGulp Configuration.
 *
 * TODO: Customize your project in the wpgulp.js file.
 */
const config = require( './wpgulp.config.js' );

/**
 * Load Plugins.
 *
 * Load gulp plugins and passing them semantic names.
 */
const gulp = require( 'gulp' ); // Gulp of-course.

// Monitoring related plugins.
const watch = require('gulp-watch');

// CSS related plugins.
const sass = require('gulp-sass')(require('node-sass')); // Gulp plugin for Sass compilation.

// JS related plugins.
const uglify = require('gulp-uglify-es').default; // Minifies JS files.

// HTML related plugins
const htmlbeautify = require('gulp-html-beautify'); // Beautify HTML/PHP files

// Utility related plugins.
const concat = require( 'gulp-concat' ); // Concatenates files.
const lineec = require( 'gulp-line-ending-corrector' ); // Consistent Line Endings for non UNIX systems. Gulp Plugin for Line Ending Corrector (A utility that makes sure your files have consistent line endings).

const fs = require('fs'); // File System
const del = require('del'); // Delete plugin
var path = require('path');

var argv = require('yargs').argv;
var log = require('fancy-log');
var tap = require('gulp-tap');

gulp.task('monitor', function () {

	watch(['assets/**/*'], function (cb) {
		buildAllAssets();
	});
});


/*
	CAWeb VIP Admin Styles
*/
gulp.task('admin-css',  async function() {

	del(['css/admin*.css']);

	if (argv.prod) {
		buildAdminStyles(true);
	}

	if (argv.dev) {
		buildAdminStyles(false);
	}

	if (buildAll(argv)) {
		buildAdminStyles(true);
		buildAdminStyles(false);
	}

});

/*
	CAWeb VIP Admin JavaScript
*/
gulp.task('admin-js',  async function() {
	del(['js/admin*.js']);

	if (argv.prod) {
		buildAdminJS(true);
	}

	if (argv.dev) {
		buildAdminJS(false);
	}

	if (buildAll(argv)) {
		buildAdminJS(true);
		buildAdminJS(false);
	}
});


gulp.task('beautify', async function() {
	var options = {indentSize: 2};
	var noFlags = ! Object.getOwnPropertyNames(_.params).length || undefined === _.params.file;
	var src = ['*.php', '*.html'];

	if( ! noFlags ){
		src = _.params.file;
	}
	
	gulp.src(src, {base: './'})
	  .pipe(htmlbeautify(options))
	  .pipe(gulp.dest('./'));
	
});

/*
	CAWeb Build All CSS/JS and Beautify
*/
gulp.task('build', async function(){
	buildAllAssets();
});


// Gulp Task Functions
async function buildAllAssets() {
	del(['js/*.js', 'css/*.css']);

	if (argv.prod) {
		// Build Admin Styles
		buildAdminStyles(true);

		// Build Admin JS
		buildAdminJS(true);
	}

	if (argv.dev) {
		// Build Admin Styles
		buildAdminStyles(false);

		// Build Admin JS
		buildAdminJS(false);
	}

	if (buildAll(argv)) {
		// Build Admin Styles
		buildAdminStyles(true);
		buildAdminStyles(false);

		// Build Admin JS
		buildAdminJS(true);
		buildAdminJS(false);
	}

}

async function buildAdminStyles( min = false){
	var buildOutputStyle = min ? 'compressed' : 'expanded';
	var minified = min ? '.min' : '';
	var t = minified ? ' Minified ] ' : ' ] ';
	t = '[ ✅ CAWeb VIP Admin Styles' + t;

	if( ! config.adminCSS.length )
		return;

	return gulp.src(config.adminCSS, { allowEmpty: true } )
		.pipe(
			sass({
				outputStyle: buildOutputStyle,
			})
		)
		.pipe( lineec() ) // Consistent Line Endings for non UNIX systems.
		.pipe(concat('admin' + minified + '.css')) // compiled file
		.pipe(tap(function (file) {
			log(t + path.basename(file.path) + ' was created successfully.');
		}))
		.pipe(gulp.dest('./css/'));
}

async function buildAdminJS( min = false){
	var minified = min ? '.min' : '';
	var t = minified ? ' Minified ] ' : ' ] ';
	t = '[ ✅ CAWeb VIP Admin JavaScript' + t;

	if( ! config.adminJS.length )
		return;

	let js = gulp.src(config.adminJS, { allowEmpty: true } )
		.pipe( lineec() ) // Consistent Line Endings for non UNIX systems.
		.pipe(concat('admin' + minified + '.js')) // compiled file
		.pipe(tap(function (file) {
			log(t + path.basename(file.path) + ' was created successfully.');
		}))


	if( min ){
		js = js.pipe(uglify());
	}

	return js.pipe(gulp.dest('./js/'));
}

function buildAll(params = {}) {
	var b = params.hasOwnProperty('all') || !Object.keys(params).length;
	var p = params.hasOwnProperty('dev') || params.hasOwnProperty('prod');

	return b || !p;
}