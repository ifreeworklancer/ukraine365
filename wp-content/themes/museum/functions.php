<?php
//Clear header
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_resource_hints', 2);
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
show_admin_bar(false);
//Theme setup
function theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    register_nav_menus([
        'header_menu' => 'Header',
        'footer_menu' => 'Footer',
    ]);
}
add_action('after_setup_theme', 'theme_setup');
//Enqueue scripts
function theme_scripts()
{
    wp_deregister_script('wp-embed');
    wp_enqueue_script('app', get_theme_file_uri('dist/app.js'), ['jquery'], '1.0', true);
}
add_action('wp_enqueue_scripts', 'theme_scripts');
// Enqueue styles
function theme_styles()
{
    wp_enqueue_style('theme-app', get_theme_file_uri('dist/app.css'), null, null);
}
add_action('wp_enqueue_scripts', 'theme_styles');
// Contact list
function theme_customize_register($wp_customize)
{
    $wp_customize->add_setting('email');
    $wp_customize->add_control('email', [
        'section' => 'contacts',
        'label' => 'E-mail',
        'type' => 'text',
    ]);
    $wp_customize->add_setting('phone1');
    $wp_customize->add_control('phone1', [
        'section' => 'contacts',
        'label' => 'Телефон',
        'type' => 'text',
    ]);
    $wp_customize->add_setting('phone2');
    $wp_customize->add_control('phone2', [
        'section' => 'contacts',
        'label' => 'Телефон',
        'type' => 'text',
    ]);
}
add_action('customize_register', 'theme_customize_register');
// DD
function dd($args)
{
    echo '<pre>';
    var_dump($args);
    echo '</pre>';
    die();
}

//Vue
function get_ajax_posts()
{
    $args = [
        'posts_per_page' => 6,
        'paged' => $_POST['paged'],
    ];
    if (isset($_POST)) {
        if ($category = $_POST['category']) {
            $args['category_name'] = $category;
        }
        if ($filters = $_POST['filters']) {
            $args['tag_slug__in'] = explode(',', $filters);
        }
    }
    $ajaxposts = new WP_Query($args);
    echo json_encode([
        'posts' => format_posts($ajaxposts->posts),
        'last_page' => $ajaxposts->max_num_pages,
    ]);
    exit;
}
// Fire AJAX action for both logged in and non-logged in users
add_action('wp_ajax_get_ajax_posts', 'get_ajax_posts');
add_action('wp_ajax_nopriv_get_ajax_posts', 'get_ajax_posts');
function format_posts($posts)
{
    if (!is_array($posts)) {
        return null;
    }
    $computed = [];
    foreach ($posts as $post) {
        array_push($computed, [
            'title' => $post->post_title,
            'image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'posted_at' => get_the_date('j.m.Y', $post->ID),
            'description' => wp_trim_words($post->post_content, 30, '...'),
            'permalink' => get_the_permalink($post->ID),
            'lang' => pll_get_post_language($post->ID, 'slug'),
        ]);
    }
    return $computed;
}
function slugify($text)
{
    $text = strtolower(trim($text));
    $text =
        transliterator_transliterate('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();',
            $text);
    $text = str_replace([' ', ',', '.'], '-', $text);
    return $text;
}
/**
 * @param $query
 * @param $current
 * @return string
 */
function makeFilterLink($query, $current)
{
    global $wp;
    $output = '';
    if (isset($query['filter'])) {
        $output = '?filter=';
        $filters = explode(',', $query['filter']);
        if (!in_array($current, $filters)) {
            array_push($filters, $current);
        } elseif (($key = array_search($current, $filters)) !== false) {
            unset($filters[$key]);
        }
        if (count($filters)) {
            $output .= implode(',', $filters);
        } else {
            $output = '';
        }
    } else {
        $output = '?filter=' . $current;
    }
    return home_url($wp->request . $output);
}
/**
 * @param $query
 * @param $current
 * @return bool
 */
function checkIfFilterExists($query, $current)
{
    if (empty($query['filter'])) {
        return false;
    }
    return in_array($current, explode(',', $query['filter']));
}
/**
 * @param $image
 * @return array
 */
function getImageTags($image)
{
    $tags = explode(',', $image['caption']);
    $tags = array_map('trim', $tags);
    $tags = array_map('strtolower', $tags);
    return $tags;
}
if (!function_exists('get_video_embed')) {
    function get_video_embed($url)
    {
        if (!$url) {
            return null;
        }
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/",
            $url, $matches);
        return "https://www.youtube.com/embed/"
            . $matches[1]
            . "?rel=0&showinfo=0&mute=1&loop=1&modestbranding=1";
    }
}
