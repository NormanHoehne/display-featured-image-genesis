<?php
/**
 * Genesis Framework.
 *
 * WARNING: This file is part of the core Genesis Framework. DO NOT edit this file under any circumstances.
 * Please do all modifications in the form of a child theme.
 *
 * @package Genesis\Widgets
 * @author  StudioPress
 * @license GPL-2.0+
 * @link    http://my.studiopress.com/themes/genesis/
 */

/**
 * Genesis Featured Post widget class.
 *
 * @since 0.1.8
 *
 * @package Genesis\Widgets
 */
class Display_Featured_Image_Genesis_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor. Set the default widget options and create widget.
	 *
	 * @since 0.1.8
	 */
	function __construct() {

		$this->defaults = array(
			'title'                   => '',
			'term'                    => '',
			'taxonomy'                => 'category',
			'exclude_displayed'       => 0,
			'show_image'              => 0,
			'image_alignment'         => '',
			'image_size'              => '',
			'show_title'              => 0,
			'show_content'            => 0
		);

		$widget_ops = array(
			'classname'   => 'featured-taxonomy',
			'description' => __( 'Displays a taxonomy with a thumbnail', 'display-featured-image-genesis' ),
		);

		$control_ops = array(
			'id_base' => 'featured-taxonomy',
			'width'   => 505,
			'height'  => 350,
		);

		parent::__construct( 'featured-taxonomy', __( 'Genesis - Featured Taxonomy', 'display-featured-image-genesis' ), $widget_ops, $control_ops );

		add_action( 'wp_ajax_tax_term_action', array( $this, 'tax_term_action_callback' ) );

	}

	/**
	 * Echo the widget content.
	 *
	 * @since 0.1.8
	 *
	 * @global WP_Query $wp_query               Query object.
	 * @global array    $_genesis_displayed_ids Array of displayed post IDs.
	 * @global $integer $more
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget( $args, $instance ) {

		global $wp_query, $_genesis_displayed_ids;

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $args['before_widget'];

		genesis_markup( array(
			'html5'   => '<article %s>',
			'xhtml'   => sprintf( '<div class="%s">', implode( ' ', get_post_class() ) ),
			'context' => 'entry',
		) );

		$term_id   = $instance['term'];
		$term_meta = get_option( "taxonomy_$term_id" );
		$term      = get_term_by( 'id', $term_id, $instance['taxonomy'] );
		$title     = $term->name;
		$slug      = $term->slug;
		$permalink = get_term_link( $term );

		if ( $term_meta ) {
			$image_id  = Display_Featured_Image_Genesis_Common::get_image_id( $term_meta['dfig_image'] );
			$image_src = wp_get_attachment_image_src( $image_id, $instance['image_size'] );
			$image     = '<img src="' . $image_src[0] . '" />';
		}

		if ( $instance['show_image'] && $image ) {
			printf( '<a href="%s" title="%s" class="%s">%s</a>', $permalink, the_title_attribute( 'echo=0' ), esc_attr( $instance['image_alignment'] ), $image );
		}

		if ( $instance['show_title'] )
			echo genesis_html5() ? '<header class="entry-header">' : '';

			if ( ! empty( $instance['show_title'] ) ) {

				if ( genesis_html5() )
					printf( '<h2 class="entry-title"><a href="%s">%s</a></h2>', $permalink, $title );
				else
					printf( '<h2><a href="%s">%s</a></h2>', $permalink, $title );

			}

		if ( $instance['show_title'] )
			echo genesis_html5() ? '</header>' : '';

		if ( $instance['show_content'] && $term->meta['intro_text'] ) {

			echo genesis_html5() ? '<div class="entry-content">' : '';

			$intro_text = apply_filters( 'genesis_term_intro_text_output', $term->meta['intro_text'] );

			echo $intro_text;

			echo genesis_html5() ? '</div>' : '';

		}

		genesis_markup( array(
			'html5' => '</article>',
			'xhtml' => '</div>',
		) );

		echo $args['after_widget'];

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 0.1.8
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title'] = strip_tags( $new_instance['title'] );
		return $new_instance;

	}

	/**
	 * Echo the settings update form.
	 *
	 * @since 0.1.8
	 *
	 * @param array $instance Current settings
	 */
	function form( $instance ) {

		//* Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'display-featured-image-genesis' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<div class="genesis-widget-column">

			<div class="genesis-widget-column-box genesis-widget-column-box-top">

				<p>
					<label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy', 'display-featured-image-genesis' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" onchange="tax_term_postback('<?php echo $this->get_field_id( 'term' ); ?>', this.value);" >
					<?php
					$tax_args = array(
						'public'   => true,
						'show_ui'  => true
					);
					$taxonomies = get_taxonomies( $tax_args );

					foreach ( $taxonomies as $taxonomy ) {
						echo '<option value="'. esc_attr( $taxonomy ) .'" '. selected( esc_attr( $taxonomy ), $instance['taxonomy'], false ) .'>'. esc_attr( $taxonomy ) .'</option>';
					} ?>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'term' ); ?>"><?php _e( 'Term', 'display-featured-image-genesis' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'term' ); ?>" name="<?php echo $this->get_field_name( 'term' ); ?>" >
						<?php
						$args   = array(
							'orderby'    => 'name',
							'order'      => 'ASC',
							'hide_empty' => false
						);
						$output = 'objects';
						$terms  = get_terms( $instance['taxonomy'], $args );
						foreach ( $terms as $term ) {
							echo '<option value="'. esc_attr( $term->term_id ) .'" '. selected( esc_attr( $term->term_id ), $instance['term'], false ) .'>'. esc_attr( $term->name ) .'</option>';
						} ?>
					</select>
				</p>

			</div>

			<div class="genesis-widget-column-box">

				<p>
					<input id="<?php echo $this->get_field_id( 'show_title' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_title' ); ?>" value="1" <?php checked( $instance['show_title'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show Taxonomy Title', 'display-featured-image-genesis' ); ?></label>
				</p>

				<p>
					<input id="<?php echo $this->get_field_id( 'show_content' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_content' ); ?>" value="1" <?php checked( $instance['show_content'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_content' ); ?>"><?php _e( 'Show Taxonomy Intro Text', 'display-featured-image-genesis' ); ?></label>
				</p>

			</div>

		</div>

		<div class="genesis-widget-column genesis-widget-column-right">

			<div class="genesis-widget-column-box genesis-widget-column-box-top">

				<p>
					<input id="<?php echo $this->get_field_id( 'show_image' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_image' ); ?>" value="1" <?php checked( $instance['show_image'] ); ?>/>
					<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Show Featured Image', 'display-featured-image-genesis' ); ?></label>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Image Size', 'display-featured-image-genesis' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'image_size' ); ?>" class="genesis-image-size-selector" name="<?php echo $this->get_field_name( 'image_size' ); ?>">
						<?php
						$sizes = genesis_get_image_sizes();
						foreach( (array) $sizes as $name => $size )
							echo '<option value="'.esc_attr( $name ).'" '.selected( $name, $instance['image_size'], FALSE ).'>'.esc_html( $name ).' ( '.$size['width'].'x'.$size['height'].' )</option>';
						?>
					</select>
				</p>

				<p>
					<label for="<?php echo $this->get_field_id( 'image_alignment' ); ?>"><?php _e( 'Image Alignment', 'display-featured-image-genesis' ); ?>:</label>
					<select id="<?php echo $this->get_field_id( 'image_alignment' ); ?>" name="<?php echo $this->get_field_name( 'image_alignment' ); ?>">
						<option value="alignnone">- <?php _e( 'None', 'display-featured-image-genesis' ); ?> -</option>
						<option value="alignleft" <?php selected( 'alignleft', $instance['image_alignment'] ); ?>><?php _e( 'Left', 'display-featured-image-genesis' ); ?></option>
						<option value="alignright" <?php selected( 'alignright', $instance['image_alignment'] ); ?>><?php _e( 'Right', 'display-featured-image-genesis' ); ?></option>
						<option value="aligncenter" <?php selected( 'aligncenter', $instance['image_alignment'] ); ?>><?php _e( 'Center', 'display-featured-image-genesis' ); ?></option>
					</select>
				</p>

			</div>

		</div>
		<?php

	}

	/**
	 * Handles the callback to populate the custom term dropdown. The
	 * selected post type is provided in $_POST['post_type'], and the
	 * calling script expects a JSON array of term objects.
	 */
	function tax_term_action_callback() {

		// And from there, a list of available terms in that tax
		$args  = array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true
		);
		$terms = get_terms( $_POST['taxonomy'], $args );

		// Build an appropriate JSON response containing this info
		foreach ( $terms as $term ) {
			$list[$term->slug] = $term->name;
		}

		// And emit it
		echo json_encode( $list );
		die();
	}

}
