<?php

/**
 * Author: Kurtis Kemple
 * Date: 07/07/2013 ( MM/DD/YYYY)
 * Website(s): http://www.kurtiskemple.com
 * Email: kurtiskemple@gmail.com
 */

/**
 * Meta Box Class
 * allows for the easy creation of meta boxes for post types
 * supports multiple image attachments with quick delete and drag and drop sortablity
 * supports image label for radio inputs and handles groups
 */

/**
 * Meta Box requires meta_box.css and meta_box.js to be enqueued and available
 * in admin for complete functionality to be available.
 * Including proper layout , sortable images, multiple image uploads, and deleting images.
 */

/*
 * Add the following code to your functions php file, and update the paths to the css and js files
 * and you can begin using the Meta Box class:
 *
 * include_once( 'Meta_Box.php' );
 * function enqueue_kmk_meta_box_dependencies() {
	wp_enqueue_script( 'kmk_meta_box_js', bloginfo( 'template_url' ) . '/js/meta_box.js', array( 'jquery' ), 1.0 );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style( 'kmk_meta_box_css', bloginfo( 'template_url' ) . '/css/meta_box.css', '', 1.0 );
  }

  add_action( 'admin_enqueue_scripts', 'enqueue_kmk_meta_box_dependencies' );
 */

/**
 * Supported Input / Field Types:
 * Text,
 * Textarea,
 * Select,
 * Checkbox,
 * Radio,
 * Image
 */

/*
Example Usage:
$meta_box = array(
	'id' => 'kmk_slider_options',
	'title' => 'kmk Slider Options',
	'page' => 'kmk_slider',
	'context' => 'advanced',
	'priority' => 'high',
	'fields' => array(
		array(
			'name' => 'transition',
			'display_name' => 'Transition',
			'desc' => 'Slide or Fade',
			'id' => $prefix . 'transition',
			'type' => 'select',
			'options' => array( 'Slide', 'Fade' )
		),
		array(
			'name' => 'duration',
			'display_name' => 'Duration',
			'desc' => 'Seconds to pause, in milliseconds ( 3000 = 3s )',
			'id' => $prefix . 'duration',
			'type' => 'text',
			'std' => '3000'
		),
		array(
			'name' => 'useArrows',
			'display_name' => 'Use Side Arrow Navigation',
			'desc' => 'Show arrows at left and right of slider for navigation',
			'id' => $prefix . 'useArrows',
			'type' => 'checkbox'
		),
		array(
			'name' => 'dotNavPosition',
			'display_name' => 'Dot Navigation Position',
			'desc' => 'Show dot navigation at bottom of slider',
			'id' => $prefix . 'dotNavPosition',
			'type' => 'radio',
			'options' => array(
				array(
					'name' => 'top_left_dot_nav',
					'value' => 'top-left',
					'has_image' => true,
					'image_url' => PPWS_DIR_URL . 'img/top-left-dot-nav.jpg'
				)
			)
		),
		array(
			'name' => 'kmk_slider_images',
			'display_name' => 'Current Images',
			'desc' => 'Images for the slider',
			'id' => $prefix . 'slider_images',
			'type' => 'image',
			'allow_multiple' => true
		)
	)
);

new kmk_Meta_Box( $meta_box );
 */
class Custom_Meta_Box {

	public function __construct( $meta_data ) {
		//@todo: throw exception/error if $meta_data is empty or not an array
		/*
		 * Localize the meta data for this meta box instance
		 * Localize the meta data for the fields for this meta box instance for ease of use
		 */
		$this->_meta_data = $meta_data;
		$this->_fields = $meta_data['fields'];

		/*
		 * add the necessary Wordpress hooks
		 */
		add_action( 'add_meta_boxes', array( $this, 'init_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_kmk_meta_data' ) );
		add_action( 'wp_ajax_kmk_meta_unlink_file', array( $this, 'kmk_unlink_meta_file' ) );
		add_action( 'wp_ajax_kmk_meta_box_image_order', array( $this, 'kmk_sort_meta_images' ) );
	}

	/**
	 * register the meta box with Wordpress
	 * @return null
	 */
	public function init_meta_box() {
		add_meta_box( $this->_meta_data[ 'id' ], $this->_meta_data[ 'title' ], array( $this, 'kmk_meta_box_html' ), $this->_meta_data[ 'page' ], $this->_meta_data[ 'context' ], $this->_meta_data[ 'priority' ] );
	}

	/**
	 * function that handles the html output for this meta box
	 * @param  WP object        	$post the current post we are outputting admin html for
	 * @return string html       the html to output for this meta box instance on the admin page
	 */
	public function kmk_meta_box_html( $post ) {

		// Use nonce for verification
		echo '<input type="hidden" name="kmk_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';
		echo '<table class="form-table">';
		$arrayOptions = array(
			'name',
			'display_name',
			'type',
			'desc',
			'id',
			'std'
		);

		foreach ( $this->_fields as $field ) {

			foreach ( $arrayOptions as $ar ) {
				$field[ $ar ] = ( isset( $field[ $ar ] ) ) ? $field[ $ar ] : '';
			}

			// get current post meta data
			$meta = get_post_meta( $post->ID, $field[ 'name' ], true );
			$loading_url = get_bloginfo( 'url' ) . '/wp-admin/images/loading.gif';
			echo '<tr>',
				'<th style="width:20%"><label for="', $field[ 'name' ], '">', $field['display_name'], ( $field['type'] == 'image' ) ? '<img src="' . $loading_url . '" class="kmk-loading-animation" />' : '', '</label></th>',
				'<td>';
				switch ( $field[ 'type' ] ) {
					case 'text':
						echo '<input type="text" name="', $field[ 'name' ], '" id="', $field[ 'id' ], '" value="', $meta ? $meta : $field[ 'std' ], '" size="30" style="width:97%" />', '<br />', $field[ 'desc' ];
						break;
					case 'textarea':
						echo '<textarea name="', $field[ 'name' ], '" id="', $field['id'], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>', '<br />', $field[ 'desc' ];
						break;
					case 'select':
						echo '<select name="', $field[ 'name' ], '" id="', $field[ 'id' ], '">';
						foreach ( $field[ 'options' ] as $option ) {
							echo '<option ', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
						}
						echo '</select>', '<span style="padding-left: 20px;">', $field[ 'desc' ], '</span>';
						break;
					case 'radio':
						foreach ( $field[ 'options' ] as $option ) {
							echo '<div class="kmk-meta-box-radio-wrapper">',
								'<input type="radio" name="', $field[ 'name' ], '" value="', $option[ 'value' ], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' />', ( $option[ 'has_image' ] == true ) ? '<img src="' . $option[ 'image_url' ] . '"/>' : $option[ 'display_name' ],
								'</div>';
						}
						break;
					case 'checkbox':
						echo '<input type="checkbox" name="', $field[ 'name'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />', '<span style="padding-left: 20px;">' , $field[ 'desc' ] , '</span>';
						break;
					case 'image': ?>
						<div id="<?php echo $field[ 'id' ]; ?>-uploaded" class="<?php echo ( $field[ 'allow_multiple' ] ) ? 'kmk-sortable' : ''; ?>">
							<?php
							$args = array(
								'post_type' => 'attachment',
								'post_parent' => $post->ID,
								'numberposts' => -1,
								'post_status' => NULL,
								'orderby' => 'menu_order',
								'order' => 'asc'
							);
							$attachs = get_posts( $args );

							if ( !empty( $attachs ) ) {
								foreach ( $attachs as $att ) {
								?>
									<div class="single-att" id="<?php echo $att->ID; ?>">
										<?php echo wp_get_attachment_image( $att->ID, 'thumbnail' ); ?>
										<a class="deletefile" href="#" rel="<?php echo $att->ID; ?>-<?php echo $post->ID; ?> "title="Delete this file">Delete Image</a>
									</div>
								<?php
								}
							} else {
								echo 'No Images uploaded yet';
							}
							?>
						</div>

						<?php if ( $field[ 'allow_multiple' ] || empty( $attachs ) ): ?>

							<h4 class="kmk-meta-upload-images-title">Upload New Image<?php echo ( $field[ 'allow_multiple' ] ) ? 's' : ''; ?></h4>
							<div id="newimages">
								<p><input type="file" name="<?php echo $field[ 'id' ] ?>[]" id="" /></p>
								<?php if( $field[ 'allow_multiple' ] ): ?>
									<a class="add" href="#">Add More Images</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php break;
				}
			echo	'</td><td>',
				'</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * the function that handles saving form data for this meta box instance
	 * @param  int $post_id       the id of the current post being updated
	 * @return null
	 */
	public function save_kmk_meta_data( $post_id ) {
		// verify nonce
		if ( !wp_verify_nonce( $_POST['kmk_meta_box_nonce'], basename(__FILE__) ) ) {
			return $post_id;
		}

		// check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( !current_user_can('edit_post', $post_id ) ) {
			return $post_id;
		}

		foreach ( $this->_fields as $field ) {
			$name = $field[ 'name' ];

			$old = get_post_meta( $post_id, $name, true );
			$new = $_POST[$field['name']];

			if ( $field[ 'type' ] == 'file' || $field[ 'type' ] == 'image' ) {
				if ( !empty( $_FILES[ $name] ) ) {

					$this->fix_file_array( $_FILES[ $name ] );

					foreach ( $_FILES[ $name ] as $position => $fileitem ) {

						$file = wp_handle_upload( $fileitem, array( 'test_form' => false ) );
						$filename = $file[ 'url' ];

						if ( !empty( $filename ) ) {
							$wp_filetype = wp_check_filetype( basename( $filename ), null );
							$attachment = array(
								'post_mime_type' => $wp_filetype[ 'type' ],
								'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
								'post_status' => 'inherit'
							);

							$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );

							// you must first include the image.php file
							// for the function wp_generate_attachment_metadata() to work
							require_once( ABSPATH . 'wp-admin/includes/image.php' );

							$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
							wp_update_attachment_metadata( $attach_id, $attach_data );
						}
					}
				}
			}

			if ($field[ 'type' ] == 'wysiwyg' ) {
				$new = wpautop( $new );
			}

			if ( $field[ 'type' ] == 'textarea' ) {
				$new = htmlspecialchars( $new );
			}

			if ( $new && $new != $old ) {
				update_post_meta( $post_id, $name, $new );
			} elseif ( '' == $new && $old && $field[ 'type' ] != 'file' && $field['type'] != 'image' ) {
				delete_post_meta( $post_id, $name, $old );
			}
		}
	}

	/**
	 * function called on ajax update of sortable images
	 * saves new menu order for that group of images
	 * @return null [description]
	 */
	public function kmk_sort_meta_images() {
		global $wpdb; // WordPress database class

		$order = explode(',', $_POST[ 'order' ]);
		$counter = 0;

		foreach ($order as $image_id) {
			$wpdb->update($wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $image_id ) );
			$counter++;
		}
		die(1);
	}

	/**
	 * ajax callback function that handles removal of wp attachment when image is deleted
	 * @return null
	 */
	public function kmk_unlink_meta_file() {
		global $wpdb;
		if ( $_POST[ 'data' ] ) {
			$data = explode( '-', $_POST[ 'data' ] );
			$att_id = $data[0];
			$post_id = $data[1];
			wp_delete_attachment( $att_id );
		}
		die(1);
	}

	/**
	 * function to fix the file array for multiple attachments
	 * @param  array $files the files to order / remove
	 * @return null
	 */
	private function fix_file_array( &$files ) {

		$names = array(
			'name' => 1,
			'type' => 1,
			'tmp_name' => 1,
			'error' => 1,
			'size' => 1
		);

		foreach ( $files as $key => $part ) {
			// only deal with valid keys and multiple files
			$key = ( string ) $key;
			if ( isset( $names[ $key ] ) && is_array( $part ) ) {
				foreach ( $part as $position => $value ) {
					$files[ $position ][ $key ] = $value;
				}
				// remove old key reference
				unset( $files[ $key ] );
			}
		}
	}
}

