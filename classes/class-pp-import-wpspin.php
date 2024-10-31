<?php
/**
 * Import functions
 *
 * @link       #
 * @since      2.0.0
 *
 * @package    wpx-pp-import-export
 * @subpackage wpx-pp-import-export/classes
 */

if ( ! class_exists( 'PP_IMPORT_WPSPIN' ) && defined( 'ABSPATH' ) ) {
	/**
	 * PP_IMPORT_WPSPIN
	 *
	 * @since      1.0.0
	 */
	class PP_IMPORT_WPSPIN {

		/**
		 * Constructor
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
			add_filter( 'upload_mimes', array( $this, 'cc_mime_types' ) );

			add_action( 'post_edit_form_tag', array( $this, 'pp_wpspin_update_edit_form' ) );
			add_action( 'save_post', array( $this, 'pp_wpspin_save_json_import' ) );
		}

		/**
		 * Add this for input type file
		 *
		 * @since    1.0.0
		 */
		public function pp_wpspin_update_edit_form() {
			echo ' enctype="multipart/form-data"';
		}

		/**
		 * Json file import
		 *
		 * @since    2.0.0
		 * @param  mixed $post_id post ID.
		 */
		public function pp_wpspin_save_json_import( $post_id ) {

			global $post;

			$is_autosave       = wp_is_post_autosave( $post_id );
			$is_revision       = wp_is_post_revision( $post_id );
			$is_valid_nonce    = ( isset( $_POST['ppwpspinjson_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['ppwpspinjson_nonce'] ), basename( __FILE__ ) ) ) ? 'true' : 'false';
			$is_user_logged_in = is_user_logged_in();// check login.

			// Exits script depending on save status.
			if ( $is_autosave || $is_revision || ! $is_valid_nonce || ! $is_user_logged_in ) {
				return;
			}

			// Verify if this is an auto save routine. If it is our form has not been submitted, so we don't want.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Check permissions to edit pages and/or posts.

			if ( ! empty( $_FILES['pp_wpspin_json_file']['tmp_name'] ) ) {

				$json   = file_get_contents( $_FILES['pp_wpspin_json_file']['tmp_name'] );
				$result = json_decode( $json, true );
				/**
				 * Update Post Data
				*/

				if ( ! empty( $result['post_data'] ) && is_array( $result['post_data'] ) ) {
					/**
					 * extract images and upload to the new path
					 */
					$extract_content = $this->upload_content_image_and_change_path( $post_id, $result['post_data']['post_content'], home_url() );
					$post_data       = array(
						'ID'           => $post_id,
						'post_content' => $extract_content,
						'post_excerpt' => $result['post_data']['post_excerpt'],
					);
					/**
					 * Get rid of infinite import/update
					*/
					remove_action( 'save_post', array( $this, 'pp_wpspin_save_json_import' ) );
					wp_update_post( $post_data );
					add_action( 'save_post', array( $this, 'pp_wpspin_save_json_import' ) );
				}

				/*
				Update post meta
				*/
				if ( ! empty( $result['post_meta'] ) && is_array( $result['post_meta'] ) ) {
					$prev_value = '';
					foreach ( $result['post_meta'] as $meta_key => $meta_value ) {
						if ( '_wp_page_template' === $meta_key ) {
							continue;
						}
						update_post_meta( $post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
					}
				}
				/**
				* Update acf fields
				*/
				if ( ! empty( $result['acf_fields'] ) && is_array( $result['acf_fields'] ) && class_exists( 'ACF' ) ) {
					$imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif", "webp");
					foreach ( $result['acf_fields'] as $meta_key => $meta_value ) { // Page header.
						if ( isset( $meta_value['type'] ) && 'image' == $meta_value['type'] ) {
							$image_url                         = $meta_value['url'];
							$attach_id                         = $this->upload_image( $image_url, $post_id );
							$result['acf_fields'][ $meta_key ] = $attach_id;
						} else {
							if(is_array($meta_value)) {
								foreach($meta_value as $mk=>$mv) {
									if(is_array($mv)) {
										foreach($mv as $mkk=>$mvv) {
											$urlExt = pathinfo($mvv, PATHINFO_EXTENSION);
											if (in_array($urlExt, $imgExts)) {
												if ( strpos( $mvv, $home_url ) !== false ) {
													$attach_id = $this->upload_image( $mvv, $post_id );
													$result['acf_fields'][ $meta_key ][$mk][$mkk] = $attach_id;
												}
											}
										}
									} else {
										$urlExt = pathinfo($mv, PATHINFO_EXTENSION);
										if (in_array($urlExt, $imgExts)) {
											if ( strpos( $mv, $home_url ) !== false ) {
												$attach_id = $this->upload_image( $mv, $post_id );
												$result['acf_fields'][ $meta_key ][$mk] = $attach_id;
											}
										}
									}
								}
							} else {
								$urlExt = pathinfo($meta_value, PATHINFO_EXTENSION);
								if (in_array($urlExt, $imgExts)) {
									if ( strpos( $meta_value, $home_url ) !== false ) {
										$attach_id = $this->upload_image( $meta_value, $post_id );
										$result['acf_fields'][ $meta_key ] = $attach_id;
									}
								}
							}
						}
						if ( is_array( $meta_value ) ) {
							foreach ( $meta_value as $field_key => $field_val ) {
								if ( isset( $field_val['type'] ) && 'image' == $field_val['type'] ) {
									$image_url                                       = $field_val['url'];
									$attach_id                                       = $this->upload_image( $image_url, $post_id );
									$result['acf_fields'][ $meta_key ][ $field_key ] = $attach_id;
								}
								if ( is_array( $field_val ) ) {
									foreach ( $field_val as $sub_field_key => $sub_field_val ) { // image.
										if ( isset( $sub_field_val['type'] ) && 'image' == $sub_field_val['type'] ) {
											$image_url = $sub_field_val['url'];
											$attach_id = $this->upload_image( $image_url, $post_id );
											$result['acf_fields'][ $meta_key ][ $field_key ][ $sub_field_key ] = $attach_id;
										}
										if ( is_array( $sub_field_val ) ) {
											foreach ( $sub_field_val as $sub_sub_field_key => $sub_sub_field_val ) {
												if ( isset( $sub_sub_field_val['type'] ) && 'image' == $sub_sub_field_val['type'] ) {
													$image_url = $sub_sub_field_val['url'];
													$attach_id = $this->upload_image( $image_url, $post_id );
													$result['acf_fields'][ $meta_key ][ $field_key ][ $sub_field_key ][ $sub_sub_field_key ] = $attach_id;
												}
												if ( is_array( $sub_sub_field_val ) ) {
													foreach ( $sub_sub_field_val as $sub_sub_sub_field_key => $sub_sub_sub_field_val ) {
														if ( isset( $sub_sub_sub_field_val['type'] ) && 'image' == $sub_sub_sub_field_val['type'] ) {
															$image_url = $sub_sub_sub_field_val['url'];
															$attach_id = $this->upload_image( $image_url, $post_id );
															$result['acf_fields'][ $meta_key ][ $field_key ][ $sub_field_key ][ $sub_sub_field_key ][ $sub_sub_sub_field_key ] = $attach_id;
														}
														if ( is_array( $sub_sub_sub_field_val ) ) {
															foreach ( $sub_sub_sub_field_val as $sub_sub_sub_sub_field_key => $sub_sub_sub_sub_field_val ) {
																if ( isset( $sub_sub_sub_sub_field_val['type'] ) && 'image' == $sub_sub_sub_sub_field_val['type'] ) {
																	$image_url = $sub_sub_sub_sub_field_val['url'];
																	$attach_id = $this->upload_image( $image_url, $post_id );
																	$result['acf_fields'][ $meta_key ][ $field_key ][ $sub_field_key ][ $sub_sub_field_key ][ $sub_sub_sub_field_key ][ $sub_sub_sub_sub_field_key ] = $attach_id;
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
				if ( ! empty( $result['acf_fields'] ) && is_array( $result['acf_fields'] ) && class_exists( 'ACF' ) ) {
					foreach ( $result['acf_fields'] as $meta_key => $meta_value ) { // Page header.
						$extract_content = $this->upload_content_image_and_change_path( $post_id, $meta_value, home_url() );
						update_field( $meta_key, $extract_content, $post_id );
					}
				}

				/**
				* Update taxonomies
				*/
				if ( ! empty( $result['taxonomies'] ) && is_array( $result['taxonomies'] ) ) {
					foreach ( $result['taxonomies'] as $taxonomy ) {
						$term = get_term_by( 'slug', $taxonomy['slug'], $taxonomy['taxonomy'] );

						if ( $term ) {

							$update = wp_update_term(
								$term->term_id,
								$taxonomy['taxonomy'],
								array(
									'name' => $taxonomy['name'],
									'slug' => $taxonomy['slug'],
								)
							);

							$cat_ids = array( $term->term_id );
							wp_set_object_terms( $post_id, $cat_ids, $taxonomy['taxonomy'], true );

						} else {
							$new_term = wp_insert_term(
								$taxonomy['name'],   // the term.
								$taxonomy['taxonomy'], // the taxonomy.
								array(
									'slug' => $taxonomy['slug'],
								)
							);

							$cat_ids = array( $new_term['term_id'] );
							wp_set_object_terms( $post_id, $cat_ids, $taxonomy['taxonomy'], true );
						}
					}
				}

				/**
				* Update feature_img
				*/
				if ( ! empty( $result['feature_img'] ) && is_array( $result['feature_img'] ) ) {

					$image_url = $result['feature_img'][0];
					$attach_id = $this->upload_image( $image_url, $post_id );
					// And finally assign featured image to post.
					set_post_thumbnail( $post_id, $attach_id );
				}
			}
		}

		/**
		 * This function checking post/page exist or not
		 *
		 * @since    1.0.0
		 * @param  mixed $post_id post id.
		 */
		public function pp_wpspin_post_id_exists( $post_id ) {

			if ( false == get_post_status( $post_id ) ) {
				return 0;
			} else {
				return 1;
			}
		}

		/**
		 * This function uploads image
		 *
		 * @since    2.0.0
		 * @param  mixed $image_url image url.
		 * @param  mixed $post_id post id.
		 */
		public function upload_image( $image_url, $post_id = null ) {
			$image_name       = basename( $image_url );
			$upload_dir       = wp_upload_dir(); // Set upload folder.
			$image_data       = file_get_contents( $image_url ); // Get image data.
			$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name.
			$filename         = basename( $unique_file_name ); // Create image file name.

			// Check folder permission and define file location.
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}
			// Create the image  file on the server.
			file_put_contents( $file, $image_data );
			// Check image file type.
			$wp_filetype = wp_check_filetype( $filename, null );
			// Set attachment data.
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			// Create the attachment.
			if ( null == $post_id ) {
				$attach_id = wp_insert_attachment( $attachment, $file );
			} else {
				$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
			}

			require_once ABSPATH . 'wp-admin/includes/image.php';
			// Define attachment metadata.
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			// Assign metadata to attachment.
			wp_update_attachment_metadata( $attach_id, $attach_data );
			return $attach_id;
		}

		/**
		 * Cc_mime_types
		 *
		 * @since    1.0.0
		 * @param  mixed $mimes mimes.
		 */
		public function cc_mime_types( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		}

		/**
		 * upload_content_image_and_change_path
		 *
		 * @since    2.0.0
		 * @param  mixed $post_content post content.
		 * @param  mixed $post_id post id.
		 * @param  mixed $home_url home url to replace the link.
		 */
		public function upload_content_image_and_change_path ( $post_id, $post_content, $home_url ) {
			if(empty($post_content)) {
				return $post_content;
			}
			if(is_array($post_content)) {
				return $post_content;
			}
			$imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif", "webp");
			$urlExt = pathinfo($post_content, PATHINFO_EXTENSION);
			if (in_array($urlExt, $imgExts)) {
				if ( strpos( $post_content, $home_url ) !== false ) {
					$attach_id = $this->upload_image( $post_content, $post_id );
					$newlink   = wp_get_attachment_url( $attach_id );
					return $newlink;
				}
			} else {
				$post_content = mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' );
				if($post_content == strip_tags($post_content)) {
					return $post_content;
				}
				$doc = new DOMDocument();
				$doc->loadHTML( $post_content );
				$tags = $doc->getElementsByTagName( 'img' ); //get all the image tags from content
				if ( $tags->length > 0 ) {
					foreach ( $tags as $tag ) {
						$oldsrc = $tag->getAttribute( 'src' );
						if ( strpos( $oldsrc, $home_url ) !== false ) {
							$attach_id = $this->upload_image( $oldsrc, $post_id );
							$newlink   = wp_get_attachment_url( $attach_id );
							$tag->setAttribute( 'src', $newlink );
						}
					}
					$extractcontent = utf8_encode( $doc->saveHTML() );//encode data to escape special char
					return $extractcontent;
				} else {
					return $post_content;
				}
			}
		}

	}//end class

	new PP_IMPORT_WPSPIN();

} // class_exists
