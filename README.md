# Digitizer API Extensions

WordPress plugin that extends the REST API with custom endpoints for JetEngine FAQ fields, Elementor data, and post metadata.

## Features

- **FAQ API**: Read/write JetEngine FAQ repeater fields via REST API
- **FAQ Bulk Update**: Update FAQ on multiple posts in one request
- **FAQ Title**: Read/write FAQ section title
- **Reading Time**: Read/write post reading time meta field
- **Elementor API**: Read/update Elementor widget content via REST API

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

## Authentication

All write operations require WordPress authentication (Application Password recommended).

## Changelog

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
