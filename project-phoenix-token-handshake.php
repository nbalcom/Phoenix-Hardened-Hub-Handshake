<?php
/**
 * Plugin Name: Phoenix Hardened Hub & Token Handshake
 * Plugin URI:  https://natebal.com/lab/code/phoenix-hardened-hub-handshake-plugin/
 * Description: Enterprise-grade AI hub featuring Intelligent Entity Filtering, Content Negotiation, and a token-secured OpenAPI manifest.
 * Version:     1.8.8
 * Author:      Nate Balcom
 * Author URI:  https://natebal.com/about-me/
 * License:     GPL2
 * Text Domain: project-phoenix
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PHOENIX_API_KEY', 'PHOENIX_RECON_ALPHA_77');

/* ==========================================================================
   VECTOR 1: SEMANTIC CONTENT NEGOTIATION (THE HARDENED HANDSHAKE)
   ========================================================================= */

add_action('template_redirect', 'phoenix_execute_token_handshake');

function phoenix_execute_token_handshake() {
    if (!is_single() && !is_page() && !is_front_page()) {
        return;
    }

    $secure_handshake_triggered = false;

    // RULE 0: Explicit URL Query Parameter Overrides
    if (isset($_GET['ai']) || isset($_GET['phoenix'])) {
        $secure_handshake_triggered = true;
    }

    // RULE 1: Check HTTP Accept Header for explicit Markdown requests
    if (!$secure_handshake_triggered && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/markdown') !== false) {
        $secure_handshake_triggered = true;
    }

    // RULE 2: Intercept verified AI User-Agent strings
    if (!$secure_handshake_triggered && isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $ai_crawlers = array(
            'chatgpt-user', 
            'gptbot', 
            'claudebot', 
            'claude-searchbot', 
            'applebot-extended', 
            'perplexitybot', 
            'google-extended', 
            'cohere-ai', 
            'facebookexternalhit',
            'meta-externalagent',
            'amazonbot',
            'diffbot',
            'bytespider'
        );

        foreach ($ai_crawlers as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                $secure_handshake_triggered = true;
                break;
            }
        }
    }

    if ($secure_handshake_triggered) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        global $post;

        // Fetch canonical queried object to prevent global $post pollution
        $queried = get_queried_object();
        if ($queried instanceof WP_Post) {
            $post = $queried;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Phoenix-Handshake: Secured-Semantic-Negotiation-v1.8.8');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // FRONT PAGE: Execute and capture the active theme template (front-page.php)
        if (is_front_page()) {
            $front_template = locate_template(array('front-page.php', 'home.php'));
            if ($front_template) {
                ob_start();
                load_template($front_template, false);
                $rendered_template = ob_get_clean();

                // Strip navigation chrome, headers, footer wrappers, SVG icons, and forms
                $rendered_template = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $rendered_template);
                $rendered_template = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $rendered_template);
                $rendered_template = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $rendered_template);
                $rendered_template = preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '', $rendered_template);
                $rendered_template = preg_replace('/<form\b[^>]*>.*?<\/form>/is', '', $rendered_template);

                if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $rendered_template, $main_match)) {
                    $raw_content = $main_match[1];
                } elseif (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $rendered_template, $body_match)) {
                    $raw_content = $body_match[1];
                } else {
                    $raw_content = $rendered_template;
                }
            } else {
                $raw_content = ($post instanceof WP_Post) ? $post->post_content : '';
                if (function_exists('parse_blocks')) {
                    $parsed_blocks = parse_blocks($raw_content);
                    $compiled_content = '';
                    foreach ($parsed_blocks as $block) {
                        $compiled_content .= render_block($block);
                    }
                    $raw_content = $compiled_content;
                }
            }

            $title = get_bloginfo('name');
            $site_desc = get_bloginfo('description');
            if (!empty($site_desc) && stripos($title, $site_desc) === false) {
                $title .= ' — ' . $site_desc;
            }
            $permalink = home_url('/');
        } else {
            if (!$post || !($post instanceof WP_Post)) {
                return;
            }

            $title = get_the_title($post->ID);
            $permalink = get_permalink($post->ID);
            $raw_content = $post->post_content;

            if (function_exists('parse_blocks')) {
                $parsed_blocks = parse_blocks($raw_content);
                $compiled_content = '';
                foreach ($parsed_blocks as $block) {
                    $compiled_content .= render_block($block);
                }
                $raw_content = $compiled_content;
            }
        }

        // 1. DYNAMICALLY CAPTURE SCHEMA (Rank Math + Custom Meta Boxes + Inline Blocks)
        $json_ld_blocks = array();
        
        // A. Run Rank Math head action
        if (function_exists('do_action')) {
            ob_start();
            do_action('rank_math/head');
            $rank_math_head_output = ob_get_clean();

            if (!empty($rank_math_head_output) && preg_match_all('/<script[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>.*?<\/script>/is', $rank_math_head_output, $rm_matches)) {
                $json_ld_blocks = array_merge($json_ld_blocks, $rm_matches[0]);
            }
        }

        // B. Robust regex sniffer for Custom Post Meta JSON-LD graphs (handles multi-line formatting & whitespace)
        if ($post instanceof WP_Post) {
            $all_meta = get_post_meta($post->ID);
            if (!empty($all_meta) && is_array($all_meta)) {
                foreach ($all_meta as $key => $values) {
                    if (!is_array($values)) {
                        $values = array($values);
                    }
                    foreach ($values as $val) {
                        if (!is_string($val)) {
                            continue;
                        }
                        $trimmed = trim($val);
                        if (empty($trimmed)) {
                            continue;
                        }
                        if (preg_match('/<script[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>.*?<\/script>/is', $trimmed, $m)) {
                            $json_ld_blocks[] = $m[0];
                        } elseif (preg_match('/^\s*\{\s*["\']@(context|graph)["\']/i', $trimmed) && strpos($trimmed, 'schema.org') !== false) {
                            $json_ld_blocks[] = '<script type="application/ld+json">' . $trimmed . '</script>';
                        }
                    }
                }
            }
        }

        // C. Catch any inline schema blocks authored directly in content
        if (preg_match_all('/<script[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>.*?<\/script>/is', $raw_content, $inline_matches)) {
            $json_ld_blocks = array_merge($json_ld_blocks, $inline_matches[0]);
        }

        $json_ld_blocks = array_unique($json_ld_blocks);

        // 2. CONVERT FAQ BUTTONS/SUMMARIES INTO SEMANTIC <h3> HEADINGS
        $clean_html = preg_replace('/<(?:button|summary)[^>]*>(.*?)<\/(?:button|summary)>/is', '<h3>$1</h3>', $raw_content);

        // 3. VAPORIZE PROMOTIONAL NOISE (PDF Cards, CTA Capsules, and Lead Magnets)
        $clean_html = preg_replace('/<a\b[^>]*class=["\'][^"\']*(?:nb-pdf-cover|nb-pdf-btn|nb-btn|nb-neon-btn)[^"\']*["\'][^>]*>.*?<\/a>/is', '', $clean_html);
        $clean_html = preg_replace('/<h[1-6]\b[^>]*class=["\'][^"\']*(?:nb-solid-cta-capsule|nb-pdf-|nb-neon-)[^"\']*["\'][^>]*>.*?<\/h[1-6]>/is', '', $clean_html);
        $clean_html = preg_replace('/<p\b[^>]*class=["\'][^"\']*(?:nb-solid-cta-capsule|nb-pdf-|nb-neon-)[^"\']*["\'][^>]*>.*?<\/p>/is', '', $clean_html);
        $clean_html = preg_replace('/<div\b[^>]*class=["\'][^"\']*(?:nb-solid-cta-capsule|nb-pdf-|nb-neon-)[^"\']*["\'][^>]*>.*?<\/div>/is', '', $clean_html);

        // 4. STRIP SCRIPTS, STYLES, COMMENTS, AND EMPTY PARAGRAPHS / HEADINGS
        $clean_html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $clean_html);
        $clean_html = preg_replace('/<script(?![^>]*type=[\'"]application\/ld\+json[\'"])[^>]*>.*?<\/script>/is', '', $clean_html);
        $clean_html = preg_replace('/<!--.*?-->/s', '', $clean_html);

        for ($i = 0; $i < 3; $i++) {
            $clean_html = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $clean_html);
            $clean_html = preg_replace('/<h[1-6][^>]*>\s*<\/h[1-6]>/is', '', $clean_html);
        }

        // Scrub technical CSS blocks inside pre/code nodes
        $clean_html = preg_replace_callback('/<(pre|code)[^>]*>.*?<\/\1>/is', function($matches) {
            $target_block = $matches[0];
            if (
                strpos($target_block, '{') !== false || 
                strpos($target_block, '&#123;') !== false || 
                strpos($target_block, '&lbrace;') !== false ||
                strpos($target_block, '.nb-') !== false
            ) {
                return '<p><em>*[Technical CSS Design Layout Block Omitted for Agent Token Optimization]*</em></p>';
            }
            return $target_block;
        }, $clean_html);

        $clean_html = strip_tags($clean_html, '<h1><h2><h3><h4><h5><h6><p><ul><ol><li><strong><b><em><i><a><article><section><pre><code><table><thead><tbody><tr><th><td><blockquote><hr><dl><dt><dd>');

        // Resolve Metadata Values
        $meta_description = '';
        if (class_exists('RankMath')) {
            $meta_description = \RankMath\Post::get_meta('description', $post->ID);
        }
        if (empty($meta_description) && ($post instanceof WP_Post)) {
            $custom_desc = get_post_meta($post->ID, 'rank_math_description', true);
            $meta_description = !empty($custom_desc) ? $custom_desc : wp_strip_all_tags(wp_trim_words($post->post_content, 30));
        }
        ?>
<!DOCTYPE html>
<html lang="en" style="background-color:#030712; color-scheme:dark;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?php echo esc_html($title); ?></title>
    
    <style>
        html, body {
            background-color: #030712 !important;
            color: #cbd5e1 !important;
            margin: 0;
            padding: 0;
        }
    </style>

    <!-- Primary SEO & Knowledge Engine Metadata -->
    <link rel="canonical" href="<?php echo esc_url($permalink); ?>">
    <?php if (!empty($meta_description)): ?>
    <meta name="description" content="<?php echo esc_attr($meta_description); ?>">
    <?php endif; ?>
    <?php if ($post instanceof WP_Post): ?>
    <meta name="author" content="<?php echo esc_attr(get_the_author_meta('display_name', $post->post_author)); ?>">
    <meta property="article:published_time" content="<?php echo esc_attr(get_the_date('c', $post->ID)); ?>">
    <meta property="article:modified_time" content="<?php echo esc_attr(get_the_modified_date('c', $post->ID)); ?>">
    
    <!-- Open Graph Verification Graph -->
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($meta_description); ?>">
    <meta property="og:url" content="<?php echo esc_url($permalink); ?>">
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
    <meta property="og:type" content="<?php echo is_single() ? 'article' : 'website'; ?>">
    <?php if (has_post_thumbnail($post->ID)): ?>
    <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'large')); ?>">
    <?php endif; ?>
    <?php endif; ?>

    <!-- Permissive Indexing Directives for Generative Crawlers -->
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

    <!-- Injected JSON-LD Knowledge Graph (Schema.org) -->
    <?php 
    if (!empty($json_ld_blocks)) {
        foreach ($json_ld_blocks as $schema_tag) {
            echo "\n    " . $schema_tag;
        }
    }
    ?>
</head>
<body style="font-family:system-ui,-apple-system,sans-serif; line-height:1.6; max-width:680px; margin:40px auto; padding:0 20px; background-color:#030712; color:#cbd5e1;">
    <article>
        <header>
            <h1 style="color:#38bdf8; font-size:2.25rem; margin-bottom:5px; font-weight:bold;"><?php echo esc_html($title); ?></h1>
            <p style="color:#64748b; font-size:0.875rem; margin-top:0;">Node Reference: <a href="<?php echo esc_url($permalink); ?>" style="color:#38bdf8; text-decoration:none;"><?php echo esc_url($permalink); ?></a></p>
            <?php if (($post instanceof WP_Post && has_tag('project-phoenix', $post)) || strpos(strtolower($raw_content), 'execution strategy') !== false): ?>
                <p style="color:#f87171; font-size:0.75rem; font-family:monospace; margin-top:0;">Execution Strategy: Private Cloud Architecture Semantic Content Negotiation</p>
            <?php endif; ?>
        </header>
        <hr style="border:0; border-top:1px solid #1e293b; margin:30px 0;">
        <main style="font-size:1.05rem;">
            <?php echo wp_kses_post($clean_html); ?>
        </main>
    </article>
</body>
</html>
        <?php
        exit;
    }
}


/* ==========================================================================
   VECTOR 2: OPENAPI FUNCTIONAL ACTION MANIFEST
   ========================================================================= */

add_action('rest_api_init', 'phoenix_register_agent_endpoints');

function phoenix_register_agent_endpoints() {
    register_rest_route('project-phoenix/v1', '/openapi.json', array(
        'methods'             => 'GET',
        'callback'            => 'phoenix_serve_openapi_manifest',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('project-phoenix/v1', '/recon-status', array(
        'methods'             => 'GET',
        'callback'            => 'phoenix_handle_recon_status_query',
        'permission_callback' => '__return_true', 
    ));
}

function phoenix_serve_openapi_manifest() {
    $manifest = array(
        "openapi" => "3.0.0",
        "info" => array(
            "title" => "Project Phoenix Architectural Interface",
            "description" => "Functional API schema for verified autonomous AI agents executing actions on NateBal.com assets.",
            "version" => "1.8.8"
        ),
        "servers" => array(
            array("url" => esc_url_raw(site_url('/wp-json/project-phoenix/v1')))
        ),
        "paths" => array(
            "/recon-status" => array(
                "get" => array(
                    "summary" => "Retrieve technical audit processing data metrics for a business domain.",
                    "operationId" => "getReconStatus",
                    "security" => array(
                        array("BearerAuth" => array())
                    ),
                    "parameters" => array(
                        array(
                            "name" => "domain",
                            "in" => "query",
                            "description" => "The target domain string to verify (e.g., targetsite.com)",
                            "required" => true,
                            "schema" => array("type" => "string")
                        )
                    ),
                    "responses" => array(
                        "200" => array("description" => "Pristine status metrics returned successfully."),
                        "401" => array("description" => "Unauthorized access attempt. Agent validation token signature rejected.")
                    )
                )
            )
        ),
        "components" => array(
            "securitySchemes" => array(
                "BearerAuth" => array(
                    "type" => "http",
                    "scheme" => "bearer",
                    "description" => "Provide your authorized internal Project Phoenix master key token string to execute."
                )
            )
        )
    );

    return new WP_REST_Response($manifest, 200);
}

function phoenix_handle_recon_status_query($request) {
    $auth_header = $request->get_header('Authorization');
    $token = '';

    if (!empty($auth_header) && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = trim($matches[1]);
    }

    if (empty($token) || $token !== PHOENIX_API_KEY) {
        return new WP_Error(
            'rest_unauthorized',
            'Unauthorized operational request. Project Phoenix security signature rejected.',
            array('status' => 401)
        );
    }

    $target_domain = $request->get_param('domain');
    if (empty($target_domain)) {
        return new WP_Error('missing_parameter', 'Target domain parameter required.', array('status' => 400));
    }

    $cleaned_domain = esc_url_raw(sanitize_text_field($target_domain));

    return new WP_REST_Response(array(
        "status"           => "compiled_and_dispatched",
        "target_url"       => $cleaned_domain,
        "origin_handshake" => "Secured-Functional-Negotiation-Verified",
        "dispatched_from"  => "phoenixreconunit@natebal.com",
        "timestamp"        => current_time('mysql')
    ), 200);
}