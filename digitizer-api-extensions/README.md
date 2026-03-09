# Digitizer API Extensions Plugin

**Version:** 1.0.0  
**Author:** Digitizer  
**License:** GPL v2

---

## 📋 What It Does

Exposes JetEngine FAQ repeater fields (`qna`) to the WordPress REST API, enabling full automation of FAQ content via API.

---

## 🚀 Installation

### Method 1: Upload via WordPress Admin (Recommended)

1. **Zip the plugin folder:**
   ```bash
   cd ~/.openclaw/workspace/projects/content/plugin/
   zip -r digitizer-api-extensions.zip digitizer-api-extensions.php README.md
   ```

2. **Upload to WordPress:**
   - Go to: https://www.digitizer.studio/wp-admin/plugins.php
   - Click "Add New" → "Upload Plugin"
   - Choose `digitizer-api-extensions.zip`
   - Click "Install Now"
   - Click "Activate Plugin"

### Method 2: Upload via FTP/SFTP

1. **Upload files to:**
   ```
   /wp-content/plugins/digitizer-api-extensions/
   └── digitizer-api-extensions.php
   ```

2. **Activate:**
   - WordPress Admin → Plugins
   - Find "Digitizer API Extensions"
   - Click "Activate"

### Method 3: WP-CLI (if SSH available)

```bash
# Upload file to server, then:
wp plugin activate digitizer-api-extensions
```

---

## 🧪 Testing

### 1. Verify Plugin is Active

**Check info endpoint:**
```bash
curl https://www.digitizer.studio/wp-json/digitizer/v1/faq/info
```

**Expected response:**
```json
{
  "plugin": "Digitizer API Extensions",
  "version": "1.0.0",
  "features": [...]
}
```

### 2. Read FAQ from Post

```bash
curl -u benkalsky:0IUB Zosn beV9 r4JO 5Kc0 ZI4D \
  https://www.digitizer.studio/wp-json/wp/v2/posts/11805
```

**Look for `jet_qna` field in response:**
```json
{
  "id": 11805,
  "title": {...},
  "jet_qna": [
    {
      "question": "What is the cost?",
      "answer": "Typically ₪15K-₪50K"
    }
  ]
}
```

### 3. Write FAQ to Post

```bash
curl -X POST \
  -u benkalsky:0IUB Zosn beV9 r4JO 5Kc0 ZI4D \
  -H "Content-Type: application/json" \
  -d '{
    "jet_qna": [
      {
        "question": "Test question?",
        "answer": "Test answer."
      },
      {
        "question": "Another question?",
        "answer": "Another answer."
      }
    ]
  }' \
  https://www.digitizer.studio/wp-json/wp/v2/posts/11805
```

### 4. Bulk Update Multiple Posts

```bash
curl -X POST \
  -u benkalsky:0IUB Zosn beV9 r4JO 5Kc0 ZI4D \
  -H "Content-Type: application/json" \
  -d '{
    "updates": [
      {
        "post_id": 11805,
        "faq": [
          {"question": "Q1?", "answer": "A1"},
          {"question": "Q2?", "answer": "A2"}
        ]
      },
      {
        "post_id": 12345,
        "faq": [
          {"question": "Q1?", "answer": "A1"}
        ]
      }
    ]
  }' \
  https://www.digitizer.studio/wp-json/digitizer/v1/faq/bulk
```

---

## 🐍 Python Usage

### Simple Example

```python
import requests

# Setup
base_url = "https://www.digitizer.studio"
auth = ("benkalsky", "0IUB Zosn beV9 r4JO 5Kc0 ZI4D")

# Read FAQ
response = requests.get(
    f"{base_url}/wp-json/wp/v2/posts/11805",
    auth=auth
)
post = response.json()
print(post['jet_qna'])

# Write FAQ
data = {
    "jet_qna": [
        {
            "question": "What is the average cost?",
            "answer": "Business websites typically range from ₪15,000-₪50,000."
        },
        {
            "question": "How long does it take?",
            "answer": "Most projects take 4-8 weeks from kickoff to launch."
        }
    ]
}

response = requests.post(
    f"{base_url}/wp-json/wp/v2/posts/11805",
    json=data,
    auth=auth
)

if response.status_code == 200:
    print("✅ FAQ updated successfully")
else:
    print(f"❌ Error: {response.text}")
```

### Full Updater Class

See: `scripts/update-post-complete.py`

---

## 📚 API Reference

### Endpoints

#### 1. Read/Write FAQ (Standard Post Endpoint)

**GET** `/wp/v2/posts/{id}`
- Returns post with `jet_qna` field

**POST** `/wp/v2/posts/{id}`
- Accepts `{"jet_qna": [...]}`
- Updates FAQ field

#### 2. Bulk Update

**POST** `/digitizer/v1/faq/bulk`

**Request:**
```json
{
  "updates": [
    {
      "post_id": 123,
      "faq": [
        {"question": "Q1", "answer": "A1"}
      ]
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "results": [
    {
      "post_id": 123,
      "success": true,
      "faq_count": 1
    }
  ]
}
```

#### 3. Plugin Info

**GET** `/digitizer/v1/faq/info`

Returns plugin version and usage instructions.

---

## 🔧 Troubleshooting

### FAQ not showing up?

**Check:**
1. Plugin is activated: `wp plugin list`
2. Field name is correct: `qna` (check JetEngine settings)
3. Authentication working: Try reading a standard post field first

### Permission errors?

**Make sure:**
- Using admin credentials
- User has `edit_posts` capability

### FAQ format issues?

**Valid format:**
```json
[
  {
    "question": "String (required, non-empty)",
    "answer": "String (required, non-empty)"
  }
]
```

**Invalid:**
- String instead of array
- Missing `question` or `answer`
- Empty strings

---

## 📝 Changelog

### v1.0.0 - 2026-03-09
- Initial release
- Support for JetEngine `qna` repeater field
- Read/write via standard `/wp/v2/posts` endpoint
- Bulk update endpoint
- Validation and error handling

---

## 🤝 Support

For issues or questions:
- GitHub: https://github.com/Digitizers (if applicable)
- Email: support@digitizer.studio

---

**Built by Digitizer for Digitizer** 🚀
