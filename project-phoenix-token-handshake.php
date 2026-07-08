<?php
/**
 * Plugin Name: Phoenix Hardened Hub & Token Handshake
 * Plugin URI:  https://natebal.com
 * Description: Enterprise-grade AI hub featuring Intelligent Entity Filtering, Content Negotiation, and a token-secured OpenAPI manifest.
 * Version:     1.8.1
 * Author:      Nate Balcom
 * Author URI:  https://natebal.com/about-me/
 * License:     GPL2
 * text-domain: project-phoenix
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
        $ai_crawlers = array('chatgpt-user', 'gptbot', 'claudebot', 'claude-searchbot', 'applebot-extended', 'perplexitybot', 'google-extended', 'cohere-ai', 'facebookexternalhit');

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
        if (!$post) {
            return;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Phoenix-Handshake: Secured-Semantic-Negotiation-v1.8.1');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

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

        // Vaporize active style elements and script modules completely
        $clean_html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $raw_content);
        $clean_html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $clean_html);
        $clean_html = preg_replace('//s', '', $clean_html);

        // Intelligent callback parsing to scrub out structural CSS blocks hidden inside pre/code nodes
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

        $clean_html = strip_tags($clean_html, '<h1><h2><h3><h4><h5><h6><p><ul><ol><li><strong><b><em><i><a><article><section><pre><code><table><thead><tbody><tr><th><td>');

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?></title>
    <meta name="robots" content="noindex, nofollow">
</head>
<body style="font-family:system-ui,-apple-system,sans-serif; line-height:1.6; max-width:680px; margin:40px auto; padding:0 20px; background:#030712; color:#cbd5e1;">
    <article>
        <header>
            <h1 style="color:#38bdf8; font-size:2.25rem; margin-bottom:5px; font-weight:bold;"><?php echo esc_html($title); ?></h1>
            <p style="color:#64748b; font-size:0.875rem; margin-top:0;">Node Reference: <a href="<?php echo esc_url($permalink); ?>" style="color:#38bdf8; text-decoration:none;"><?php echo esc_url($permalink); ?></a></p>
            <?php if (has_tag('project-phoenix', $post) || strpos(strtolower($raw_content), 'execution strategy') !== false): ?>
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
            "version" => "1.8.1"
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
