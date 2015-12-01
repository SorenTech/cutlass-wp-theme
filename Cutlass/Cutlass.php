<?php namespace Cutlass;

use Philo\Blade\Blade as PhiloBlade;

class Cutlass
{

    /**
     * The unique instance of the plugin.
     *
     * @var Cutlass
     */
    private static $instance;

    /**
     * The Blade class used to render our views
     *
     * @var Blade
     */
    public static $blade;


    /**
     * Gets an instance of our plugin.
     *
     * @return Cutlass
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Makes and renders the view into a cached PHP file
     * then echos and returns it.
     *
     * @param array $filenames - An array of views to render in order of precedence
     * @param array $context   - An array of items to add to the view
     *
     * @return mixed
     */
    public static function render($filenames, $context = [ ])
    {

        /**
         * The directory in which you want to have your Blade template files
         * Filter: 'cutlass_views_directory' - Change the location of the default Blade views directory
         * @var string
         */
        $views_directory = apply_filters('cutlass_views_directory', app_path() . '/resources/views');

        /**
         * The directory in which you want to have Blade store it's cached/compiled files
         * Filter: 'cutlass_cache_directory' - Change the location of the Blade cache
         * @var string
         */
        $cache_directory = apply_filters('cutlass_cache_directory', app_path() . '/storage/framework/views');

        /**
         * Whether the Blade cache is enabled or disabled
         * Filter: 'cutlass_disable_cache' - Enable or Disable the Blade cache
         * @var bool
         */
        $disable_cache = apply_filters('cutlass_disable_cache', false);

        if ($disable_cache === true) {
            self::clear_blade_cache();
        }

        /**
         * Blade Engine
         */
        self::$blade = new PhiloBlade($views_directory, $cache_directory);

        $cutlassrenderer = new Blade($filenames, $context, self::$blade);

        $output = $cutlassrenderer->render();

        echo $output;

        return $output;

    }


    /**
     * Clears the entire Blade cache directory
     *
     * @return array
     */
    protected static function clear_blade_cache()
    {
        return array_map('unlink', glob(app_path() . '/storage/framework/views/*'));
    }


    /**
     * Returns a nice formatted title according to which page
     * we're on.
     *
     * From Root's Sage
     * https://github.com/roots/sage
     *
     * @param null|int $post_id
     *
     * @return string
     */
    public static function get_page_title($post_id = 0)
    {

        if (is_home()) {
            if (get_option('page_for_posts', true)) {
                return get_the_title(get_option('page_for_posts', true));
            } else {
                return 'Latest Posts';
            }
        } elseif (is_archive()) {
            return get_the_archive_title();
        } elseif (is_search()) {
            return 'Search Results for ' . get_search_query();
        } elseif (is_404()) {
            return '404 - Not Found';
        } else {
            return get_the_title($post_id);
        }

    }


    /**
     * Checks global wp_query for posts and returns them,
     * otherwise runs get_posts on passed query
     *
     * @param array $query
     *
     * @return array
     */
    public static function get_posts($query = [ ])
    {
        global $wp_query;

        /**
         * Set return var
         */
        $posts = [ ];

        /**
         * If the query's empty and the global WP_Query has posts grab them
         * else just grab the posts the normal way
         */
        if (empty( $query ) && property_exists($wp_query, 'posts') && ! empty( $wp_query->posts )) {
            $posts = $wp_query->posts;
        } else {
            $posts = get_posts($query);
        }

        /**
         * Return empty if either of those fail
         */
        if (empty( $posts )) {
            return [ ];
        }

        /**
         * Convert WP_Posts to Posts
         */
        self::convert_posts($posts);

        /**
         * Return array of Posts
         */
        return $posts;

    }


    /**
     * Gets the post and converts it into a Post
     * which grants us some nifty methods and properties
     *
     * @param int $postid
     *
     * @return Post|bool
     */
    public static function get_post($postid = null)
    {

        /**
         * If postid is empty get the ID the normal way
         */
        if (empty( $postid )) {
            $postid = get_queried_object_id();
        }

        /**
         * Grab post using postid
         */
        $post = get_post($postid);

        /**
         * If it's a correct WP_Post convert it to a
         * Post
         */
        if (is_a($post, 'WP_Post')) {
            return new Post($post);
        }

        /**
         * Return null if all else fails
         */
        return false;

    }


    /**
     * Converts WP_Posts to Posts
     *
     * * Note: We use array_walk over foreach for memory conservation because
     * * the gained time is not worth the memory lost
     *
     * @param array|WP_Post $posts
     *
     * @return null|WP_Post
     */
    public static function convert_posts(&$posts)
    {

        /**
         * If it's already Post just return
         */
        if (is_a($posts, 'Post')) {
            return;
        }

        /**
         * If it's a single WP_Post object convert it
         * and return
         */
        if (is_a($posts, 'WP_Post')) {
            $posts = new Post($posts);

            return $posts;
        }

        /**
         * Convert all posts
         */
        array_walk($posts, function (&$value, $key) {
            $value = new Post($value);
        });

    }
}