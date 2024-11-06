<?php
/**
 * Created by PhpStorm.
 * User: Richard
 * Date: 07/10/2018
 * Time: 13:46
 */


?>

<article id="term-<?php echo $term->term_id; ?>" class="taxonomy-term">
  <h2><?php echo esc_html($term->name); ?></h2>
  <p><?php echo esc_html($term->description); ?></p>
  <a href="<?php echo esc_url(get_term_link($term)); ?>">View More</a>
</article>
