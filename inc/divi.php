<?php
/**
 * CAWeb VIP Divi Overrides
 *
 * @package CAWeb VIP
 */

 
/**
 * Fixes unclosed HTML tags
 *
 * @since 3.18.4
 * @see Divi/core/components/data/init.php#L384
 * 
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/151700
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2283
 * 
 * @param string $content source HTML
 *
 * @return string
 */
if ( ! function_exists( 'et_core_fix_unclosed_html_tags' ) ):
	function et_core_fix_unclosed_html_tags( $content ) {
		// Exit if source has no HTML tags or we miss what we need to fix them anyway.
		if ( false === strpos( $content, '<' ) || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}
	
		$scripts = false;
	
		if ( false !== strpos( $content, '<script' ) ) {
			// Replace scripts with placeholders so we don't mess with HTML included in JS strings.
			$scripts = new ET_Core_Data_ScriptReplacer();
			$content = preg_replace_callback( '|<script.*?>[\s\S]+?</script>|', array( $scripts, 'replace' ), $content );
		}

		libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		@$doc->loadHTML( sprintf(
			'<html><head>%s</head><body>%s</body></html>',
			// Use WP charset
			sprintf( '<meta http-equiv="content-type" content="text/html; charset=%s" />', get_bloginfo( 'charset' ) ),
			$content
		) );
	
		libxml_use_internal_errors(false);

		if ( preg_match( '|<body>([\s\S]+)</body>|', $doc->saveHTML(), $matches ) ) {
			// Extract the fixed content.
			$content = $matches[1];
		}
	
		if ( $scripts ) {
			// Replace placeholders with scripts.
			$content = strtr( $content, $scripts->map() );
		}
	
		return $content;
	}
	endif;