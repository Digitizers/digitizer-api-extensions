# Digitizer API Extensions

WordPress plugin that extends the REST API with custom endpoints for JetEngine FAQ fields, Elementor data, Rank Math SEO, author taxonomy, and post metadata.

## Features

- **FAQ API**: Read/write JetEngine FAQ repeater fields via REST API
- **FAQ Bulk Update**: Update FAQ on multiple posts in one request
- **FAQ Title**: Read/write FAQ section title
- **Reading Time**: Custom REST field for post reading time
- **Elementor API**: Read/update Elementor widget content via REST API
- **Author Taxonomy**: Custom fields for author bio, image, and LinkedIn URL
- **Rank Math SEO**: Full access to all 12 Rank Math meta fields via REST API

## Installation

Upload to `/wp-content/plugins/digitizer-api-extensions/` and activate.

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp/v2/posts/{id}` | GET/POST | Includes `jet_qna`, `jet_faq_title`, `reading_time` fields |
| `/digitizer/v1/faq/bulk` | POST | Bulk update FAQ on multiple posts |
| `/digitizer/v1/faq/info` | GET | Plugin version and feature info |
| `/digitizer/v1/elementor/{id}` | GET | Get Elementor widget tree |
| `/digitizer/v1/elementor/{id}` | POST | Update Elementor widgets by ID |
| `/wp/v2/authors/{id}` | GET/POST | Author taxonomy with `author_description`, `author_image`, `linkedin` fields |

## Rank Math SEO Fields

All 12 Rank Math fields accessible via the post `meta` object:

| Field | Description |
|-------|-------------|
| `rank_math_title` | SEO title |
| `rank_math_description` | Meta description |
| `rank_math_focus_keyword` | Focus keyword |
| `rank_math_robots` | Robots meta |
| `rank_math_canonical_url` | Canonical URL |
| `rank_math_primary_category` | Primary category ID |
| `rank_math_og_title` | Open Graph title |
| `rank_math_og_description` | Open Graph description |
| `rank_math_og_image` | Open Graph image URL |
| `rank_math_twitter_title` | Twitter card title |
| `rank_math_twitter_description` | Twitter card description |
| `rank_math_schema_article_type` | Schema article type |

## API Contracts

**Important implementation details:**

- `reading_time` is a **top-level REST field** (`register_rest_field`), not inside `meta`
- `jet_qna` and `jet_faq_title` must use `/digitizer/v1/faq/bulk` with `"updates"` key for writes
- `rank_math_*` fields work via the `meta` object (Rank Math Pro registers them)
- Author fields are on the `authors` taxonomy endpoint (`/wp/v2/authors/{id}`)

## Authentication

All write operations require WordPress authentication (Application Password recommended).

## Changelog

### v1.5.1
- All 12 Rank Math SEO fields exposed via REST API
- Plugin info endpoint reads version from plugin header (no more hardcoded values)

### v1.5.0
- Added initial Rank Math SEO field support (3 fields)

### v1.4.0
- Added author taxonomy fields (`author_description`, `author_image`, `linkedin`)

### v1.3.0
- Added `reading_time` REST API field

### v1.2.0
- Added Elementor API endpoints (GET/POST widget data)

### v1.1.0
- Added `jet_faq_title` field

### v1.0.0
- Initial release: FAQ read/write + bulk endpoint

## License

GPL v2 or later
