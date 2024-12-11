<?php
/**
 * Created by PhpStorm.
 * User: Richard
 * Date: 07/10/2018
 * Time: 13:46
 */


?>
<div id="term-<?php echo $term->term_id; ?>" class="c-accl-post-list__term-thumb">
    <div class="c-accl-term-thumb">
  <h2 class="c-accl-term-thumb__heading"><?php echo esc_html($term->name); ?></h2>
  <?php if ($term->description != '') : ?>
            <?php echo wpautop(esc_html($term->description)); ?>
        <?php endif ?>
  <a href="<?php echo esc_url(get_term_link($term)); ?>">View More</a>
  </div>
</div>
