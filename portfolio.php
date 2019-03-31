<?php
/**
* Plugin Name:     Portfolio
* Description:     Plugin which adds and list Portfolio
* version:         1.0.0
* Author:          Udhayakumar Sadagopan
* Author URI:      http://www.udhayakumars.com
**/


if (! defined('S_VERSION')) {
    define('S_VERSION', 1.0);
} // end if

class UV_Portfolio
{

    /* --------------------------------------------
     * Attributes
     -------------------------------------------- */

    // Represents the nonce value used to save the post media
    private $nonce = 'uv_portfolio_nonce';
    private $singular_label = "Portfolio";
    private $plural_label = "Portfolios";
    private $theme = "uv_portfolio";
    private $supports = array('title', 'revisions', 'editor', 'thumbnail');
    private $tax_name = 'portfolio_categories';

    /* --------------------------------------------
     * Constructor
     -------------------------------------------- */

    /**
     * Initializes localiztion, sets up JavaScript, and displays the meta box for saving the file
     * information.
     */
    public function __construct()
    {

        // Localization, Styles, and JavaScript
        add_action('admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 10, 1);

        add_action('wp_enqueue_scripts', array( $this, 'register_scripts' ), 10, 1);
        // Setup the meta box hooks
        add_action('init', array($this, 'create_cpt'));
        //$this->add_meta_box();

        add_shortcode('services', array($this, 'display_content'));
    } // end construct

    /* --------------------------------------------
     * Localization, Styles, and JavaScript
     -------------------------------------------- */

    /**
     * Addings the admin JavaScript
     */
    public function register_admin_scripts()
    {
        wp_enqueue_style(strtolower($this->theme).'/admin-style', plugins_url('css/admin-style.css', __FILE__));
    } // end register_scripts

    public function register_scripts()
    {
        wp_enqueue_style(strtolower($this->theme).'/style.css', plugins_url('css/style.css', __FILE__));
    } // end register_scripts

    /**
     * Introduces the file meta box for uploading the file to this post.
     */
    public function create_cpt()
    {

        // Set UI labels for Custom Post Type
        $labels = array(
            'name'                => _x($this->plural_label, 'Post Type General Name', $this->theme),
            'singular_name'       => _x($this->singular_label, 'Post Type Singular Name', $this->theme),
            'menu_name'           => __($this->plural_label, $this->theme),
            'parent_item_colon'   => __('Parent '.$this->singular_label, $this->theme),
            'all_items'           => __('All '.$this->plural_label, $this->theme),
            'view_item'           => __('View '.$this->singular_label, $this->theme),
            'add_new_item'        => __('Add New '.$this->singular_label, $this->theme),
            'add_new'             => __('Add New', $this->theme),
            'edit_item'           => __('Edit '.$this->singular_label, $this->theme),
            'update_item'         => __('Update '.$this->singular_label, $this->theme),
            'search_items'        => __('Search '.$this->singular_label, $this->theme),
            'not_found'           => __('Not Found', $this->theme),
            'not_found_in_trash'  => __('Not found in Trash', $this->theme),
        );

        // Set other options for Custom Post Type

        $args = array(
            'label'               => __(strtolower($this->plural_label), $this->theme),
            'description'         => __('List of '.$this->plural_label, $this->theme),
            'labels'              => $labels,
            // Features this CPT supports in Post Editor
            'supports'            => $this->supports,
            /* A hierarchical CPT is like Pages and can have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 7,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'page',
        );

        // Registering your Custom Post Type
        register_post_type(strtolower($this->plural_label), $args);
        $this->create_taxonomy();
    } // add_file_meta_box


    // Register Custom Taxonomy
    private function create_taxonomy()
    {
      $labels = array(
          'name' => _x( $this->singular_label.' Categories', 'taxonomy general name' ),
          'singular_name' => _x( $this->singular_label.' Category', 'taxonomy singular name' ),
          'search_items' =>  __( 'Search '.$this->singular_label.' Categories' ),
          'all_items' => __( 'All '.$this->singular_label.' Categories' ),
          'parent_item' => __( 'Parent '.$this->singular_label.' Category' ),
          'parent_item_colon' => __( 'Parent '.$this->singular_label.' Category:' ),
          'edit_item' => __( 'Edit '.$this->singular_label.' Category' ),
          'update_item' => __( 'Update '.$this->singular_label.' Category' ),
          'add_new_item' => __( 'Add New '.$this->singular_label.' Category' ),
          'new_item_name' => __( 'New '.$this->singular_label.' Category Name' ),
          'menu_name' => __( $this->singular_label.' Categories' ),
        );

      // Now register the taxonomy

        register_taxonomy($this->tax_name, array(strtolower($this->plural_label)), array(
          'public' => true,
          'publicly_queryable' => true,
          'hierarchical' => true,
          'labels' => $labels,
          'show_ui' => true,
          'show_admin_column' => true,
          'query_var' => true,
          'rewrite' => array( 'slug' => strtolower($this->plural_label), 'with_front' => true, ),
        ));
    }

    public function add_meta_box()
    {
        add_action('cmb2_admin_init', array($this, 'register_metabox'));
    }

    /**
     * Hook in and add a metabox that only appears on the 'About' page
     */
    public function register_metabox()
    {
        $prefix = strtolower($this->singular_label).'_';

        $cmb_product_page = new_cmb2_box([
         'id'           => $prefix . 'metabox',
         'title'        => esc_html__($this->singular_label.' Info', 'cmb2'),
         'object_types' => array( strtolower($this->plural_label) ), // Post type
         'context'      => 'normal',
         'priority'     => 'default',
         'show_names'   => true, // Show field names on the left
        ]);

        $cmb_product_page->add_field(
           array(
          'name'    => 'Icon',
          'desc'    => 'SVG/PNG format preferred',
          'id'      => $prefix . 'icon',
          'type'    => 'file',
          'text'    => array(
            'add_upload_file_text' => 'Add Icon' // Change upload button text. Default: "Add or Upload File"
          ),
          // query_args are passed to wp.media's library query.
          'query_args' => array(
            'type' => ['image/svg+xml', 'image/png'], // Make library only display PDFs.
          )
        )
      );
      $cmb->add_field( array(
      	'name' => 'Featured',
      	'desc' => 'check to show on home page',
      	'id'   => $prefix.'featured',
      	'type' => 'checkbox',
      ) );
    }

    public function display_content($atts)
    {
        $terms = get_terms(array('taxonomy' => $this->tax_name));
        $cards = [];
        foreach ($terms as $term) {
            $items = $this->get_all_posts($term->term_id);
            $card = '<div class="card mb-5">';
            $card .= '<div class="card-header font-weight-bold">'.$term->name.'</div>';
            $card .= '<div class="card-body p-0">';
            $card .= '<ul class="list-group">';
            $li = '';
              foreach ($items as $item):
              $li .= '<li class="list-group-item"><a href="'. $item['file_url'].'">'. $item['title']. '</a></li>';
            endforeach;
            $card .= $li;
            $card .= '</ul>';
            $card .= '</div>';
            $card .= '</div>';
            array_push($cards, $card);
        }
        return join('', $cards);
    }

    public function get_all_posts($term_id=null)
    {
        global $post;

        $args = [
            'post_type' => $this->plural_label,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        if($term_id) {
          $args['tax_query'] = [
            [
              'taxonomy' => $this->tax_name,
              'field' => 'term_id',
              'terms' => $term_id
            ]
          ];
        }

        $items = get_posts($args);
        wp_reset_postdata();
        return array_map(function ($post) {
            $id = $post->ID;

            return [
                    'title' => get_the_title($id),
                    'excerpt' => get_the_excerpt($id),
                    'img_thumb_url' => get_the_post_thumbnail_url($id, "large"),
                    'img_url' => get_the_post_thumbnail_url($id, "full"),
                    'item_url' => get_permalink($id)
                  ];
        }, $items);
    }


    /**
 * Determines whether or not the current user has the ability to save meta data associated with this post.
 *
 * @param		int		$post_id	The ID of the post being save
 * @param		bool				Whether or not the user has the ability to save this post.
 */
    public function user_can_save($post_id, $nonce)
    {
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST[ $nonce ]) && wp_verify_nonce($_POST[ $nonce ], plugin_basename(__FILE__)));

        // Return true if the user is able to save; otherwise, false.
        return ! ($is_autosave || $is_revision) && $is_valid_nonce;
    } // end user_can_save
} // end class

$GLOBALS['uv_portfolio'] = new UV_Portfolio();
