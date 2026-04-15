<?php
/**
 * Plugin Name: CIPIT Videos
 * Description: Video grid with YouTube/Vimeo support. Fixed modal layout and Taxonomy Groups.
 * Version: 3.6
 * Author: Kevin Muchwat
 */

if (!defined('ABSPATH'))
    exit;

// 1. Register Custom Post Type & Taxonomy (Styled like Publications)
add_action('init', function () {
    register_post_type('cipit_video', [
        'labels' => [
            'name' => 'CIPIT Videos',
            'singular_name' => 'Video',
            'add_new' => 'Add Video Link',
            'add_new_item' => 'Add New Video Link',
            'edit_item' => 'Edit Video'
        ],
        'public' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => ['title', 'thumbnail', 'editor'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('video_group', 'cipit_video', [
        'hierarchical' => true,
        'labels' => ['name' => 'Video Groups', 'singular_name' => 'Group'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
});

// 2. Admin Meta Box
add_action('add_meta_boxes', function () {
    add_meta_box('cipit_video_details', 'Video Source Settings', function ($post) {
        $url = get_post_meta($post->ID, '_video_youtube_url', true);
        $hash = get_post_meta($post->ID, '_video_vimeo_hash', true);
        ?>
        <div style="margin-bottom: 15px;">
            <p><strong>Video URL:</strong> (YouTube or Vimeo)</p>
            <input type="text" name="cipit_video_url_field" value="<?php echo esc_attr($url); ?>" style="width:100%;"
                placeholder="https://..." />
        </div>
        <div>
            <p><strong>Vimeo Hash ID:</strong> (Optional)</p>
            <input type="text" name="cipit_video_hash_field" value="<?php echo esc_attr($hash); ?>" style="width:100%;"
                placeholder="e.g. a1b2c3d4" />
        </div>
        <?php
    }, 'cipit_video', 'normal', 'high');
});

add_action('save_post', function ($post_id) {
    if (isset($_POST['cipit_video_url_field']))
        update_post_meta($post_id, '_video_youtube_url', esc_url_raw($_POST['cipit_video_url_field']));
    if (isset($_POST['cipit_video_hash_field']))
        update_post_meta($post_id, '_video_vimeo_hash', sanitize_text_field($_POST['cipit_video_hash_field']));
});

// 3. Shortcode Implementation
add_shortcode('cipit_videos', function ($atts) {
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $atts = shortcode_atts(['show' => 6, 'order' => 'DESC', 'pagination' => 'true', 'group' => ''], $atts);

    $args = [
        'post_type' => 'cipit_video',
        'posts_per_page' => intval($atts['show']),
        'paged' => $paged,
        'order' => $atts['order'],
    ];

    if (!empty($atts['group'])) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'video_group',
                'field' => 'slug',
                'terms' => $atts['group'],
            ]
        ];
    }

    $query = new WP_Query($args);

    if (!$query->have_posts())
        return '';
    ob_start();
    ?>
    <div id="video-grid-<?php echo esc_attr($atts['group'] ?: 'all'); ?>" class="cipit-plugin-wrapper">
        <div class="video-grid-layout">
            <?php while ($query->have_posts()):
                $query->the_post();
                $url = get_post_meta(get_the_ID(), '_video_youtube_url', true);
                $v_hash = get_post_meta(get_the_ID(), '_video_vimeo_hash', true);

                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
                    $e_id = $match[1];
                    $e_url = "https://www.youtube.com/embed/{$e_id}?controls=1&rel=0&modestbranding=1&vq=hd1080";
                    $t_url = "https://img.youtube.com/vi/{$e_id}/maxresdefault.jpg";
                } elseif (preg_match('%vimeo\.com/(?:video/|)(\d+)%i', $url, $match)) {
                    $e_url = "https://player.vimeo.com/video/{$match[1]}?badge=0" . (!empty($v_hash) ? "&h=$v_hash" : "");
                    $t_url = "https://via.placeholder.com/640x360.png?text=Vimeo+Video";
                }
                ?>
                <article class="video-card">
                    <div class="video-thumb-wrapper"
                        onclick="openCipitModal('<?php echo esc_url($e_url); ?>', '<?php echo esc_attr(get_the_title()); ?>', `<?php echo addslashes(get_the_content()); ?>`)">
                        <?php if (has_post_thumbnail()):
                            the_post_thumbnail('large', ['class' => 'grid-thumb']);
                        else: ?>
                            <img src="<?php echo esc_url($t_url); ?>" class="grid-thumb">
                        <?php endif; ?>
                        <div class="thumb-hover-overlay"><i class="fa-solid fa-play"></i></div>
                    </div>
                    <div class="video-card-details">
                        <h3 class="video-card-title">
                            <?php the_title(); ?>
                        </h3>
                        <button class="view-more-btn"
                            onclick="openCipitModal('<?php echo esc_url($e_url); ?>', '<?php echo esc_attr(get_the_title()); ?>', `<?php echo addslashes(get_the_content()); ?>`)">View
                            More <span>→</span></button>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php if ($atts['pagination'] === 'true'): ?>
            <div class="pagination">
                <?php echo paginate_links([
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'format' => '?paged=%#%',
                    'prev_text' => '<i class="fa-solid fa-angle-left"></i>',
                    'next_text' => '<i class="fa-solid fa-angle-right"></i>',
                    'add_fragment' => '#video-grid-' . esc_attr($atts['group'] ?: 'all')
                ]); ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="cipitVideoModal" class="cipit-modal">
        <div class="cipit-modal-content">
            <button class="modal-close" onclick="closeCipitModal()">&times;</button>
            <div class="cipit-modal-body">
                <div class="modal-video-side">
                    <div class="video-ratio-lock">
                        <iframe id="modalIframe" src="" frameborder="0" allow="autoplay; fullscreen"
                            allowfullscreen></iframe>
                    </div>
                </div>
                <div class="modal-info-side">
                    <div class="modal-info-scroll">
                        <h2 id="modalTitle"></h2>
                        <div id="modalContent" class="modal-text-body"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCipitModal(url, title, content) {
            const modal = document.getElementById('cipitVideoModal');
            const iframe = document.getElementById('modalIframe');
            iframe.src = url + (url.includes('?') ? '&' : '?') + "autoplay=1";
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalContent').innerHTML = content;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeCipitModal() {
            document.getElementById('cipitVideoModal').style.display = 'none';
            document.getElementById('modalIframe').src = "";
            document.body.style.overflow = 'auto';
        }
    </script>

    <style>
        .cipit-plugin-wrapper {
            margin: 2rem 0;
            scroll-margin-top: 120px;
        }

        .video-grid-layout {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .video-card {
            display: flex;
            flex-direction: column;
        }

        .video-thumb-wrapper {
            position: relative;
            cursor: pointer;
            border-radius: var(--border-radius);
            overflow: hidden;
            aspect-ratio: 16/9;
            background: #000;
            border: 1px solid #eee;
            box-shadow: var(--card-shadow);
        }

        .grid-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            transform: scale(1.05);
        }

        .video-thumb-wrapper:hover .grid-thumb {
            transform: scale(1.1);
        }

        .thumb-hover-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: #fff;
            font-size: 35px;
            background: rgba(192, 33, 38, 0.3);
        }

        .video-thumb-wrapper:hover .thumb-hover-overlay {
            opacity: 1;
        }

        .video-card-title {
            font-size: var(--h4-font-size);
            font-weight: 700;
            color: var(--primary-color);
            margin: 15px 0 8px 0;
        }

        .view-more-btn {
            align-self: flex-start;
            background: var(--dark-gray);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: var(--button-radius);
            font-size: 0.75rem;
            cursor: pointer;
        }

        .cipit-modal {
            display: none;
            position: fixed;
            z-index: 99999;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .cipit-modal-content {
            background: #fff;
            border-radius: var(--border-radius);
            width: 95%;
            max-width: 1250px;
            height: 85vh;
            position: relative;
            overflow: hidden;
            display: block;
        }

        .cipit-modal-body {
            display: flex;
            flex-direction: row;
            width: 100%;
            height: 100%;
            align-items: stretch;
        }

        .modal-video-side {
            flex: 1.6;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-ratio-lock {
            width: 100%;
            position: relative;
            padding-top: 56.25%;
        }

        .video-ratio-lock iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .modal-info-side {
            flex: 1;
            background: #fff;
            border-left: 1px solid #eee;
            position: relative;
        }

        .modal-info-scroll {
            position: absolute;
            inset: 0;
            padding: 50px 35px;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2.5rem;
            color: var(--dark-gray);
            background: none;
            border: none;
            cursor: pointer;
            z-index: 1001;
        }

        #modalTitle {
            color: var(--primary-color);
            font-size: var(--h3-font-size);
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .modal-text-body {
            font-size: 1.05rem;
            line-height: 1.618;
            color: #555;
            text-align: justify;
        }

        @media (max-width: 1024px) {
            .video-grid-layout {
                grid-template-columns: repeat(2, 1fr);
            }

            .cipit-modal-body {
                flex-direction: column;
                overflow-y: auto;
            }

            .modal-video-side {
                flex: none;
                width: 100%;
                aspect-ratio: 16/9;
            }

            .modal-info-side {
                flex: none;
                border-left: none;
            }

            .modal-info-scroll {
                position: relative;
                padding: 30px 20px;
            }
        }

        @media (max-width: 600px) {
            .video-grid-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});