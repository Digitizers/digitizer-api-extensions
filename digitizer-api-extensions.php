<?php
/**
 * Plugin Name: Digitizer API Extensions
 * Plugin URI: https://digitizer.studio
 * Description: Expose JetEngine FAQ fields to WordPress REST API for content automation
 * Version: 1.5.1
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
    
    // Register author taxonomy custom fields
    register_rest_field('authors', 'author_description', [
        'get_callback' => function($term) {
            return get_term_meta($term['id'], 'author_description', true) ?: '';
        },
        'update_callback' => function($value, $term) {
            return update_term_meta($term->term_id, 'author_description', wp_kses_post($value)) !== false;
        },
        'schema' => [
            'description' => 'Author bio description',
            'type' => 'string',
            'context' => ['view', 'edit']
        ]
    ]);

    register_rest_field('authors', 'author_image', [
        'get_callback' => function($term) {
            return get_term_meta($term['id'], 'author_image', true) ?: '';
        },
        'update_callback' => function($value, $term) {
            return update_term_meta($term->term_id, 'author_image', esc_url_raw($value)) !== false;
        },
        'schema' => [
            'description' => 'Author avatar image URL',
            'type' => 'string',
            'context' => ['view', 'edit']
        ]
    ]);

    register_rest_field('authors', 'linkedin', [
        'get_callback' => function($term) {
            return get_term_meta($term['id'], 'linkedin', true) ?: '';
        },
        'update_callback' => function($value, $term) {
            return update_term_meta($term->term_id, 'linkedin', esc_url_raw($value)) !== false;
        },
        'schema' => [
            'description' => 'Author LinkedIn URL',
            'type' => 'string',
            'context' => ['view', 'edit']
        ]
    ]);

    // Register reading_time field for posts
    register_rest_field('post', 'reading_time', [
        'get_callback' => function($post) {
            return get_post_meta($post['id'], 'reading_time', true) ?: '';
        },
        'update_callback' => function($value, $post) {
            if (!is_string($value)) {
                return new WP_Error('invalid_reading_time', 'Reading time must be a string', ['status' => 400]);
            }
            return update_post_meta($post->ID, 'reading_time', sanitize_text_field($value)) !== false;
        },
        'schema' => [
            'description' => 'Estimated reading time',
            'type' => 'string',
            'context' => ['view', 'edit']
        ]
    ]);

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
                'version' => '1.2.0',
                'features' => [
                    'jet_faq_title field in /wp/v2/posts/{id}',
                    'jet_qna field in /wp/v2/posts/{id}',
                    'Bulk FAQ update: /digitizer/v1/faq/bulk',
                    'Elementor read: /digitizer/v1/elementor/{id}',
                    'Elementor update: POST /digitizer/v1/elementor/{id}',
                    'Support for JetEngine FAQ repeater fields',
                    'Support for Elementor widget content editing'
                ],
                'usage' => [
                    'faq_read' => 'GET /wp/v2/posts/{id} → check jet_qna field',
                    'faq_write' => 'POST /wp/v2/posts/{id} with {"jet_qna": [...]}',
                    'faq_bulk' => 'POST /digitizer/v1/faq/bulk with {"updates": [...]}',
                    'elementor_read' => 'GET /digitizer/v1/elementor/{id} → widget tree',
                    'elementor_update' => 'POST /digitizer/v1/elementor/{id} with {"updates": [{"widget_id": "...", "settings": {...}}]}'
                ]
            ]);
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * ====================================
 * Elementor Data API Endpoints
 * ====================================
 * Read and update Elementor widget content programmatically
 * without breaking page layout/design.
 */
add_action('rest_api_init', function() {

    /**
     * GET /wp-json/digitizer/v1/elementor/{post_id}
     * Returns simplified tree of Elementor widgets with their content
     */
    register_rest_route('digitizer/v1', '/elementor/(?P<post_id>\d+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            $post_id = intval($request['post_id']);
            
            if (!get_post($post_id)) {
                return new WP_Error('not_found', 'Post not found', ['status' => 404]);
            }
            
            $elementor_data = get_post_meta($post_id, '_elementor_data', true);
            
            if (empty($elementor_data)) {
                return new WP_Error('no_elementor', 'Post has no Elementor data', ['status' => 404]);
            }
            
            // Decode if string
            if (is_string($elementor_data)) {
                $elementor_data = json_decode($elementor_data, true);
            }
            
            if (!is_array($elementor_data)) {
                return new WP_Error('invalid_data', 'Elementor data is not valid JSON', ['status' => 500]);
            }
            
            // Build simplified tree
            $tree = digitizer_elementor_build_tree($elementor_data);
            
            return rest_ensure_response([
                'post_id' => $post_id,
                'widget_count' => digitizer_elementor_count_widgets($elementor_data),
                'tree' => $tree
            ]);
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);

    /**
     * POST /wp-json/digitizer/v1/elementor/{post_id}
     * Update specific widgets by their ID
     * 
     * Body: {"updates": [{"widget_id": "abc123", "settings": {"title": "New Title"}}]}
     * 
     * Only updates the specified settings keys, preserving all other widget settings.
     */
    register_rest_route('digitizer/v1', '/elementor/(?P<post_id>\d+)', [
        'methods' => 'POST',
        'callback' => function($request) {
            $post_id = intval($request['post_id']);
            $updates = $request->get_param('updates');
            
            if (!get_post($post_id)) {
                return new WP_Error('not_found', 'Post not found', ['status' => 404]);
            }
            
            if (!is_array($updates) || empty($updates)) {
                return new WP_Error('invalid_updates', 'Updates must be a non-empty array', ['status' => 400]);
            }
            
            $elementor_data = get_post_meta($post_id, '_elementor_data', true);
            
            if (empty($elementor_data)) {
                return new WP_Error('no_elementor', 'Post has no Elementor data', ['status' => 404]);
            }
            
            // Decode if string
            if (is_string($elementor_data)) {
                $elementor_data = json_decode($elementor_data, true);
            }
            
            if (!is_array($elementor_data)) {
                return new WP_Error('invalid_data', 'Elementor data is not valid JSON', ['status' => 500]);
            }
            
            // Build update map: widget_id => settings to merge
            $update_map = [];
            foreach ($updates as $update) {
                if (!isset($update['widget_id']) || !isset($update['settings'])) {
                    return new WP_Error(
                        'invalid_update',
                        'Each update must have widget_id and settings',
                        ['status' => 400]
                    );
                }
                $update_map[$update['widget_id']] = $update['settings'];
            }
            
            // Apply updates recursively
            $applied = 0;
            $elementor_data = digitizer_elementor_apply_updates($elementor_data, $update_map, $applied);
            
            // Save back
            $json = wp_json_encode($elementor_data);
            $saved = update_post_meta($post_id, '_elementor_data', wp_slash($json));
            
            // Also clear Elementor CSS cache for this post
            delete_post_meta($post_id, '_elementor_css');
            
            return rest_ensure_response([
                'success' => true,
                'post_id' => $post_id,
                'updates_requested' => count($updates),
                'updates_applied' => $applied,
                'not_found' => array_keys(array_diff_key($update_map, array_flip(
                    digitizer_elementor_collect_ids($elementor_data)
                )))
            ]);
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => [
            'updates' => [
                'required' => true,
                'type' => 'array',
                'description' => 'Array of {widget_id, settings} objects'
            ]
        ]
    ]);
});

/**
 * Helper: Build simplified tree from Elementor data
 */
function digitizer_elementor_build_tree($elements, $depth = 0) {
    $tree = [];
    
    foreach ($elements as $el) {
        $node = [
            'id' => $el['id'] ?? '',
            'type' => $el['elType'] ?? 'unknown',
        ];
        
        if (!empty($el['widgetType'])) {
            $node['widget'] = $el['widgetType'];
        }
        
        // Extract text content from settings
        $settings = $el['settings'] ?? [];
        $content_keys = ['title', 'editor', 'text', 'title_text', 'heading_title', 'tab_title', 'description_text'];
        
        foreach ($content_keys as $key) {
            if (isset($settings[$key]) && !empty($settings[$key])) {
                $val = $settings[$key];
                if (strlen($val) > 200) {
                    $val = substr($val, 0, 200) . '...';
                }
                $node[$key] = $val;
            }
        }
        
        // Recurse into children
        if (!empty($el['elements'])) {
            $node['children'] = digitizer_elementor_build_tree($el['elements'], $depth + 1);
        }
        
        $tree[] = $node;
    }
    
    return $tree;
}

/**
 * Helper: Count total widgets
 */
function digitizer_elementor_count_widgets($elements) {
    $count = 0;
    foreach ($elements as $el) {
        if (($el['elType'] ?? '') === 'widget') {
            $count++;
        }
        if (!empty($el['elements'])) {
            $count += digitizer_elementor_count_widgets($el['elements']);
        }
    }
    return $count;
}

/**
 * Helper: Apply updates to widgets by ID (recursive)
 */
function digitizer_elementor_apply_updates($elements, $update_map, &$applied) {
    foreach ($elements as &$el) {
        $id = $el['id'] ?? '';
        
        if (isset($update_map[$id])) {
            if (!isset($el['settings']) || !is_array($el['settings'])) {
                $el['settings'] = [];
            }
            foreach ($update_map[$id] as $key => $value) {
                $el['settings'][$key] = $value;
            }
            $applied++;
        }
        
        if (!empty($el['elements'])) {
            $el['elements'] = digitizer_elementor_apply_updates($el['elements'], $update_map, $applied);
        }
    }
    
    return $elements;
}

/**
 * Helper: Collect all element IDs
 */
function digitizer_elementor_collect_ids($elements) {
    $ids = [];
    foreach ($elements as $el) {
        if (!empty($el['id'])) {
            $ids[] = $el['id'];
        }
        if (!empty($el['elements'])) {
            $ids = array_merge($ids, digitizer_elementor_collect_ids($el['elements']));
        }
    }
    return $ids;
}

/**
 * Register Rank Math SEO meta fields for REST API
 */
add_action('rest_api_init', function() {
    $seo_fields = [
        'rank_math_title'               => 'SEO title override',
        'rank_math_description'         => 'SEO meta description',
        'rank_math_focus_keyword'       => 'Focus keyword(s)',
        'rank_math_robots'              => 'Robot meta directives',
        'rank_math_canonical_url'       => 'Canonical URL override',
        'rank_math_primary_category'    => 'Primary category ID',
        'rank_math_seo_score'           => 'SEO score (0-100)',
        'rank_math_og_title'            => 'Open Graph title',
        'rank_math_og_description'      => 'Open Graph description',
        'rank_math_og_image'            => 'Open Graph image URL',
        'rank_math_twitter_title'       => 'Twitter card title',
        'rank_math_twitter_description' => 'Twitter card description',
    ];
    
    foreach ($seo_fields as $key => $desc) {
        register_post_meta('post', $key, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'description'   => $desc,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }
});
