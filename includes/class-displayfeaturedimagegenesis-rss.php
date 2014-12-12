<?php
/**
 * @package   DisplayFeaturedImageGenesis
 * @author    Robin Cornett <hello@robincornett.com>
 * @license   GPL-2.0+
 * @link      http://robincornett.com
 * @copyright 2014 Robin Cornett Creative, LLC
 */

class Display_Featured_Image_Genesis_RSS {

	/**
	 * Decide whether or not to add the featured image to the feed or the feed excerpt
	 *
	 * @return filter the_excerpt_rss (if summaries) or the_content_feed (full text)
	 * @since  x.y.z
	 */
	public function maybe_do_feed() {

		$displaysetting = get_option( 'displayfeaturedimagegenesis' );
		$feed_image     = $displaysetting['feed_image'];
		$rss_option     = get_option( 'rss_use_excerpt' );

		//* if the user isn't sending images to the feed, we're done
		if ( ! $feed_image ) {
			return;
		}

		//* if the feed is full text, filter the content
		if ( '0' === $rss_option ) {
			add_filter( 'the_content_feed', array( $this, 'add_image_to_feed' ), 15 );
		}
		//* if the feed is summaries, filter the excerpt, not the content
		else {
			add_filter( 'the_excerpt_rss', array( $this, 'add_image_to_feed' ), 1000, 1 );
		}

	}

	/**
	 * add the featured image to the feed, unless it already exists
	 * includes allowances for Send Images to RSS plugin, which processes before this
	 *
	 * @param return $content
	 * @since  x.y.z
	 */
	public function add_image_to_feed( $content ) {

		//* if the post doesn't have a thumbnail, we're done here
		if ( ! has_post_thumbnail() ) {
			return $content;
		}

		//* first check: see if the featured image already exists in full in the content
		$size = 'original';
		if ( class_exists( 'SendImagesRSS' ) ) {
			$simplify = get_option( 'sendimagesrss_simplify_feed' );
			$alt_feed = get_option( 'sendimagesrss_alternate_feed' );

			if ( ! $simplify && ( ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ) ) {
				$size  = 'mailchimp';
			}
		}

		$post_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), $size );
		$image_content  = strpos( $content, 'src="' . $post_thumbnail[0] );
		$rss_option     = get_option( 'rss_use_excerpt' );

		//* if the featured image already exists in all its glory in the content, we're done here
		if ( false !== $image_content && '0' === $rss_option ) {
			return $content;
		}

		//* reset size to large so we don't send huge files to the feed
		$size = 'large';
		if ( class_exists( 'SendImagesRSS' ) ) {
			$simplify = get_option( 'sendimagesrss_simplify_feed' );
			$alt_feed = get_option( 'sendimagesrss_alternate_feed' );
			//* if the user is using Send Images to RSS, send the right images to the right feeds
			if ( ! $simplify && ( ( $alt_feed && is_feed( 'email' ) ) || ! $alt_feed ) ) {
				$size  = 'mailchimp';
				$class = 'rss-mailchimp';
			}
		}

		$align      = '';
		$style      = 'display:block;margin:10px auto;';
		$class      = 'rss-featured-image';

		//* if the feed output is descriptions only, change image size to thumbnail with small alignment
		if ( '1' === $rss_option ) {
			$size  = 'thumbnail';
			$align = 'left';
			$style = 'margin:0px 0px 20px 20px;';
			$class = 'rss-small';
		}

		//* whew. build the image!
		$image = get_the_post_thumbnail( get_the_ID(), $size, array( 'align' => $align, 'style' => $style, 'class' => $class ) );

		$content = $image . $content;

		return $content;

	}

}