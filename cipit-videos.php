<?php
/**
 * Plugin Name: CIPIT Videos - Perfect Ratio
 * Description: Video grid with pixel-perfect 16:9 iframe scaling. Optimized for Golden Ratio aesthetics and Theme CSS variables. Includes anchored pagination.
 * Version: 2.8
 * Author: Kevin Muchwat
 */

if (!defined('ABSPATH'))
    exit;

// 1. Register Post Type
add_action('init', function () {
    register_post_type('cipit_video', [
        'labels' => ['name' => 'CIPIT Videos', 'singular_name' => 'Video'],
        'public' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => ['title', 'thumbnail', 'editor'],
        'show_in_rest' => true,
    ]);
});

// 2. Shortcode: [cipit_videos]
add_shortcode('cipit_videos', function ($atts) {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $atts = shortcode_atts([
        'show' => 6,
        'order' => 'DESC',
        'pagination' => 'true',
        'group' => 'video-grid-section'
    ], $atts);

    $query = new WP_Query([
        'post_type' => 'cipit_video',
        'posts_per_page' => intval($atts['show']),
        'paged' => $paged,
        'order' => $atts['order'],
    ]);

    if (!$query->have_posts())
        return '';

    ob_start();
    ?>
    <div id="<?php echo esc_attr($atts['group']); ?>" class="cipit-plugin-wrapper">
        <div class="video-grid-layout">
            <?php while ($query->have_posts()):
                $query->the_post();
                $url = get_post_meta(get_the_ID(), '_video_youtube_url', true);
                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
                $embed_id = isset($match[1]) ? $match[1] : '';
                $embed_url = "https://www.youtube.com/embed/{$embed_id}?controls=1&rel=0&modestbranding=1&showinfo=0&iv_load_policy=3&vq=hd1080";
                ?>

                <article class="video-card">
                    <div class="video-thumb-wrapper"
                        onclick="openCipitModal('<?php echo esc_url($embed_url); ?>', '<?php echo esc_attr(get_the_title()); ?>', `<?php echo addslashes(get_the_content()); ?>`)">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large', ['class' => 'grid-thumb']); ?>
                        <?php else: ?>
                            <img src="https://img.youtube.com/vi/<?php echo $embed_id; ?>/maxresdefault.jpg" class="grid-thumb">
                        <?php endif; ?>
                        <div class="thumb-hover-overlay"><i class="fa-solid fa-play"></i></div>
                    </div>
                    <div class="video-card-details">
                        <h3 class="video-card-title">
                            <?php the_title(); ?>
                        </h3>
                        <button class="view-more-btn"
                            onclick="openCipitModal('<?php echo esc_url($embed_url); ?>', '<?php echo esc_attr(get_the_title()); ?>', `<?php echo addslashes(get_the_content()); ?>`)">
                            View More <span>→</span>
                        </button>
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
                    'type' => 'plain',
                    'add_fragment' => '#' . esc_attr($atts['group'])
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
                        <iframe id="modalIframe" src="" frameborder="0" allowfullscreen></iframe>
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
            iframe.src = url + "&autoplay=1";
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalContent').innerHTML = content;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCipitModal() {
            const modal = document.getElementById('cipitVideoModal');
            document.getElementById('modalIframe').src = "";
            modal.style.display = 'none';
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
            transition: var(--card-transition);
        }

        .video-thumb-wrapper {
            position: relative;
            cursor: pointer;
            border-radius: var(--border-radius);
            overflow: hidden;
            aspect-ratio: 16 / 9;
            background: #000;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: var(--card-shadow);
        }

        .grid-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .video-thumb-wrapper:hover .grid-thumb {
            transform: scale(1.05);
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
            line-height: 1.3;
        }

        .view-more-btn {
            align-self: flex-start;
            background: var(--dark-gray);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: var(--button-radius);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--card-transition);
        }

        .view-more-btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        /* MODAL STYLING */
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
            display: flex;
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
            box-shadow: var(--card-hover-shadow);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2.5rem;
            color: var(--dark-gray);
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
            background: none;
            border: none;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1001;
        }

        .modal-close:hover {
            color: var(--primary-color);
        }

        .cipit-modal-body {
            display: flex;
            width: 100%;
            height: 100%;
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
            border-left: 1px solid #eee;
            position: relative;
            background: #fff;
        }

        .modal-info-scroll {
            position: absolute;
            inset: 0;
            padding: 50px 35px;
            overflow-y: auto;
        }

        #modalTitle {
            color: var(--primary-color);
            font-size: var(--h3-font-size);
            margin-bottom: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .modal-text-body {
            font-size: 1.05rem;
            line-height: var(--gr, 1.618);
            color: #555;
            text-align: justify;
        }

        /* RESPONSIVE STACKING */
        @media (max-width: 1024px) {
            .video-grid-layout {
                grid-template-columns: repeat(2, 1fr);
            }

            .cipit-modal-content {
                flex-direction: column;
                height: 90vh;
                overflow-y: auto;
            }

            .modal-video-side {
                flex: none;
                width: 100%;
                aspect-ratio: 16 / 9;
            }

            .modal-info-side {
                border-left: none;
                border-top: 1px solid #eee;
                flex: none;
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