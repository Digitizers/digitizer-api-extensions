<?php
/**
 * Plugin Name: Digitizer API Extensions
 * Plugin URI: https://digitizer.studio
 * Description: Expose JetEngine FAQ fields to WordPress REST API for content automation
 * Version: 1.2.0-beta.2
 * Author: Digitizer
 * Author URI: https://digitizer.studio
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: digitizer-api-extensions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register JetEngine FAQ field in WordPress REST API
 */
add_action('rest_api_init', function() {
    
    // Register FAQ title field for posts
    register_rest_field('post', 'jet_faq_title', [
        'get_callback' => function($post) {
            return get_post_meta($post['id'], 'title', true) ?: '';
        },
        'update_callback' => function($value, $post) {
            if (!is_string($value)) {
                return new WP_Error('invalid_faq_title', 'FAQ title must be a string', ['status' => 400]);
            }
            return update_post_meta($post->ID, 'title', sanitize_text_field($value)) !== false;
        },
        'schema' => [
            'description' => 'JetEngine FAQ section title',
            'type' => 'string',
            'context' => ['view', 'edit']
        ]
    ]);

    // Register FAQ field for posts
    register_rest_field('post', 'jet_qna', [
        
        /**
         * GET: Read FAQ from post
         */
        'get_callback' => function($post) {
            $qna = get_post_meta($post['id'], 'qna', true);
            
            // Return empty array if no FAQ
            if (empty($qna)) {
                return [];
            }
            
            // Ensure it's an array (JetEngine stores as serialized)
            if (is_string($qna)) {
                $decoded = json_decode($qna, true);
                if ($decoded !== null) {
                    $qna = $decoded;
                } else {
                    // Try unserialize (JetEngine format)
                    $unserialized = @unserialize($qna);
                    if ($unserialized !== false) {
                        $qna = $unserialized;
                    }
                }
            }
            
            // Ensure array of objects
            if (!is_array($qna)) {
                return [];
            }
            
            return $qna;
        },
        
        /**
         * POST/PUT: Write FAQ to post
         */
        'update_callback' => function($value, $post) {
            
            // Allow empty array (clear FAQ)
            if (empty($value)) {
                delete_post_meta($post->ID, 'qna');
                return true;
            }
            
            // Validate structure
            if (!is_array($value)) {
                return new WP_Error(
                    'invalid_faq',
                    'FAQ must be an array of question/answer objects',
                    ['status' => 400]
                );
            }
            
            // Validate each item
            foreach ($value as $index => $item) {
                if (!is_array($item)) {
                    return new WP_Error(
                        'invalid_faq_item',
                        "FAQ item $index must be an object/array",
                        ['status' => 400]
                    );
                }
                
                if (!isset($item['question']) || !isset($item['answer'])) {
                    return new WP_Error(
                        'invalid_faq_item',
                        "FAQ item $index must have 'question' and 'answer' fields",
                        ['status' => 400]
                    );
                }
                
                // Validate not empty
                if (empty(trim($item['question'])) || empty(trim($item['answer']))) {
                    return new WP_Error(
                        'empty_faq_item',
                        "FAQ item $index has empty question or answer",
                        ['status' => 400]
                    );
                }
            }
            
            // Update meta (JetEngine expects serialized array)
            $updated = update_post_meta($post->ID, 'qna', $value);
            
            return $updated !== false;
        },
        
        /**
         * Schema definition
         */
        'schema' => [
            'description' => 'JetEngine FAQ repeater field (question/answer pairs)',
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'question' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'FAQ question'
                    ],
                    'answer' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'FAQ answer'
                    ]
                ]
            ],
            'context' => ['view', 'edit']
        ]
    ]);
});

/**
 * Add custom bulk FAQ update endpoint
 * POST /wp-json/digitizer/v1/faq/bulk
 */
add_action('rest_api_init', function() {
    register_rest_route('digitizer/v1', '/faq/bulk', [
        'methods' => 'POST',
        'callback' => function($request) {
            $updates = $request->get_param('updates');
            
            if (!is_array($updates)) {
                return new WP_Error(
                    'invalid_updates',
                    'Updates must be an array',
                    ['status' => 400]
                );
            }
            
            $results = [];
            
            foreach ($updates as $update) {
                if (!isset($update['post_id']) || !isset($update['faq'])) {
                    $results[] = [
                        'post_id' => $update['post_id'] ?? 'unknown',
                        'success' => false,
                        'error' => 'Missing post_id or faq field'
                    ];
                    continue;
                }
                
                $post_id = intval($update['post_id']);
                $faq = $update['faq'];
                
                // Validate post exists
                if (!get_post($post_id)) {
                    $results[] = [
                        'post_id' => $post_id,
                        'success' => false,
                        'error' => 'Post not found'
                    ];
                    continue;
                }
                
                // Update FAQ
                $updated = update_post_meta($post_id, 'qna', $faq);
                
                $results[] = [
                    'post_id' => $post_id,
                    'success' => $updated !== false,
                    'faq_count' => is_array($faq) ? count($faq) : 0
                ];
            }
            
            return rest_ensure_response([
                'success' => true,
                'results' => $results
            ]);
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => [
            'updates' => [
                'required' => true,
                'type' => 'array',
                'description' => 'Array of {post_id, faq} objects'
            ]
        ]
    ]);
});

/**
 * Add FAQ info endpoint
 * GET /wp-json/digitizer/v1/faq/info
 */
add_action('rest_api_init', function() {
    register_rest_route('digitizer/v1', '/faq/info', [
        'methods' => 'GET',
        'callback' => function($request) {
            return rest_ensure_response([
                'plugin' => 'Digitizer API Extensions',
                'version' => '1.2.0-beta.2',
                'features' => [
                    'jet_faq_title field in /wp/v2/posts/{id}',
                    'jet_qna field in /wp/v2/posts/{id}',
                    'Bulk update endpoint: /digitizer/v1/faq/bulk',
                    'Support for JetEngine FAQ repeater fields'
                ],
                'usage' => [
                    'read' => 'GET /wp/v2/posts/{id} → check jet_qna field',
                    'write' => 'POST /wp/v2/posts/{id} with {"jet_qna": [...]}',
                    'bulk' => 'POST /digitizer/v1/faq/bulk with {"updates": [...]}'
                ]
            ]);
        },
        'permission_callback' => '__return_true'
    ]);
});
