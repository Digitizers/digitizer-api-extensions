# Digitizer API Extensions

WordPress plugin that exposes JetEngine FAQ repeater fields to the REST API for content automation.

## Features

- ✅ Expose JetEngine `qna` field to WordPress REST API
- ✅ Read/write FAQ via standard `/wp/v2/posts/{id}` endpoint
- ✅ Bulk update endpoint for batch operations
- ✅ Full validation (question + answer required)
- ✅ Compatible with JetEngine serialization formats

## Installation

### Via WordPress Admin (Recommended)

1. Download the latest release
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file
4. Click **Install Now** → **Activate**

### Manual Installation

1. Upload `digitizer-api-extensions` folder to `/wp-content/plugins/`
2. Activate via WordPress admin

## Usage

### Read FAQ

```bash
curl https://your-site.com/wp-json/wp/v2/posts/123
```

Response includes `jet_qna` field:

```json
{
  "id": 123,
  "jet_qna": [
    {
      "question": "What is the cost?",
      "answer": "Typically ₪15K-₪50K"
    }
  ]
}
```

### Write FAQ

```bash
curl -X POST https://your-site.com/wp-json/wp/v2/posts/123 \
  -u user:pass \
  -H "Content-Type: application/json" \
  -d '{
    "jet_qna": [
      {"question": "Q1?", "answer": "A1"},
      {"question": "Q2?", "answer": "A2"}
    ]
  }'
```

### Bulk Update

```bash
curl -X POST https://your-site.com/wp-json/digitizer/v1/faq/bulk \
  -u user:pass \
  -H "Content-Type: application/json" \
  -d '{
    "updates": [
      {"post_id": 123, "faq": [...]},
      {"post_id": 456, "faq": [...]}
    ]
  }'
```

## Endpoints

- `GET/POST /wp/v2/posts/{id}` - Standard endpoint with `jet_qna` field
- `POST /digitizer/v1/faq/bulk` - Bulk FAQ updates
- `GET /digitizer/v1/faq/info` - Plugin info

## Requirements

- WordPress 5.0+
- PHP 7.4+
- JetEngine plugin (for FAQ functionality)

## Documentation

See [full documentation](digitizer-api-extensions/README.md) for:
- Complete API reference
- Python usage examples
- Troubleshooting guide

## License

GPL v2 or later

## Author

[Digitizer](https://digitizer.studio)

---

**Built for content automation workflows**
