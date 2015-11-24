<?php namespace Cutlass;

use Philo\Blade\Blade;
use Exception;

/**
 * The CutlassRenderer class
 *
 * Used to initialize the Blade templating engine
 */
class CutlassRenderer
{

    /**
     * The Blade helper object which gives us access to the Blade
     * configuration and all the cool methods Blade has.
     *
     * @var Blade
     */
    protected $blade;

    protected $filesnames;

    protected $context;


    /**
     * Initialize the class
     *
     * @param $filenames array - An array of filenames to render in order of precedence
     * @param $context   array - An array of data to add to the view
     * @param $blade     Blade - The Blade renderer class
     */
    public function __construct($filenames, $context = [ ], $blade)
    {

        $this->filesnames = $filenames;
        $this->context    = $context;
        $this->blade      = $blade;

        /**
         * Our default Custom Directives
         */
        $custom_directives = [
            'wploop'      => '<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $post = Cutlass\Cutlass::get_post(); ?>',
            'wploopempty' => '<?php endwhile; else : ?>',
            'wploopend'   => '<?php endif; wp_reset_postdata(); ?>',
            'wploopquery' => '<?php $query = new WP_Query({expression}); if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); $post = Cutlass\Cutlass::get_post(); ?>',
        ];

        /**
         * Our default Global View Data
         */
        $global_view_data = [
            'wp_head' => $this->render_wp_head(),
        ];

        /**
         * Filter: 'cutlass_custom_directives' - Add your own custom Blade directives
         * @var string
         */
        $this->custom_directives = apply_filters('cutlass_custom_directives', $custom_directives);

        /**
         * Filter: 'cutlass_global_view_data' - Add global data to all Blade views
         * @var string
         */
        $this->global_view_data = apply_filters('cutlass_global_view_data', $global_view_data);

    }


    /**
     * render
     *
     * Makes and renders the view into a cached PHP file
     * then echos and returns it.
     *
     * @return mixed
     * @throws Exception
     */
    public function render()
    {

        /**
         * Add custom directives to Blade
         */
        if ( ! empty( $this->custom_directives )) {
            foreach ($this->custom_directives as $key => $directive) {
                $this->directive($key, $directive);
            }
        }

        /**
         * Add global view data
         */
        if ( ! empty( $this->global_view_data )) {
            $this->blade->view()->share($this->global_view_data);
        }

        /**
         * Add view-specific context
         */
        if ( ! empty( $this->context )) {
            $this->blade->view()->share($this->context);
        }

        /**
         * Render the view (if it exists)
         * Check to see if it's a single filename, else check to see if
         * there's an array of filenames
         */
        $output = false;
        if (is_string($this->filesnames)) {
            if ( ! $this->blade->view()->exists($this->filesnames)) {
                throw new Exception('View ( ' . $this->filesnames . ' ) does not exist');
            }

            $output = $this->blade->view()->make($this->filesnames)->render();
        } elseif (is_array($this->filesnames)) {
            /**
             * For whatever reason it's necessary to flip the array to sort correctly
             */
            $this->filesnames = array_reverse($this->filesnames);

            foreach ($this->filesnames as $filename) {
                if ($this->blade->view()->exists($filename)) {
                    $output = $this->blade->view()->make($filename)->render();
                    break;
                }
            }
        }
        if ($output === false) {
            throw new Exception('No valid View found');
        }

        return $output;

    }


    /**
     * render_wp_head
     *
     * Renders the wp_head function so we can input into our view
     *
     * @return string
     */
    protected function render_wp_head()
    {
        ob_start();
        wp_head();

        return ob_get_clean();
    }


    /**
     * directive
     *
     * Adds the directive to our compiler
     *
     * @param string $key
     * @param string $directive
     */
    protected function directive($key, $directive)
    {

        if (is_callable($directive)) {
            $this->blade->getCompiler()->directive($key, $directive);

            return;
        }

        $this->blade->getCompiler()->directive($key, function ($expression) use ($directive) {
            /**
             * Replace expression string with directive variable
             */
            return str_replace('{expression}', $expression, $directive);

        });

    }
}