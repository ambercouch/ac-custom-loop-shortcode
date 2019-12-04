<?php
/**
 * Created by PhpStorm.
 * User: Richard
 * Date: 07/10/2018
 * Time: 13:46
 */

$handle = 'ac_wp_custom_loop_styles';
$list = 'enqueued';

if (! wp_script_is( $handle, $list )) {
    wp_register_style( 'ac_wp_custom_loop_styles', plugin_dir_url( __FILE__ ) . 'assets/css/ac_wp_custom_loop_styles.css', array(), '20181007' );
    wp_enqueue_style( 'ac_wp_custom_loop_styles' );
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('c-accl-post-list__post-thumb'); ?>>
  <div class="c-accl-post-thumb">
    <?php if ( '' !== get_the_post_thumbnail() ) : ?>
      <div class="post-thumbnail c-accl-post-thumb__feature-image">
        <a href="<?php the_permalink(); ?>" class="c-accl-post-thumb__feature-image-link" >
            <?php the_post_thumbnail('post-thumbnail'); ?>
        </a>
      </div><!-- .post-thumbnail -->
    <?php endif; ?>
         <header  class="entry-header c-accl-post-thumb__header">
           <h2 class="entry-title c-accl-post-thumb__heading">
             <a href="<?php esc_url( get_permalink() ) ?>" class="c-accl-post-thumb__link" rel="bookmark">
               <span class="c-accl-post-thumb__link-title"><?php the_title() ?></span>
             </a>
           </h2>
         </header>
    <?php if (has_excerpt()) : ?>
      <div class="c-accl-post-thumb__excerpt"  >
        <?php the_excerpt() ?>
      </div>
    <?php endif; ?>
  </div>
</article>
