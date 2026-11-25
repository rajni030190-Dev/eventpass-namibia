<?php
/**
 * Main Template File (Fallback)
 *
 * @package EventPass_Namibia
 */

if (is_front_page() || is_home()) {
    require __DIR__ . '/front-page.php';
    return;
}

get_header();
?>
<main class="ep-main-content" style="padding: 4rem 1.5rem; min-height: 60vh;">
    <div class="container">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                the_title('<h1 class="ep-page-title">', '</h1>');
                the_content();
            endwhile;
        endif;
        ?>
    </div>
</main>
<?php
get_footer();
