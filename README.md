# ITR Knowledgebase

A powerful, fully customizable Knowledge Base plugin for WordPress with Elementor support. Built for large-scale knowledge bases with hierarchical categories, author/reviewer management, advanced import/export, and full Elementor integration.

---

## Requirements

| Requirement          | Minimum Version |
| -------------------- | --------------- |
| WordPress            | 6.0             |
| PHP                  | 7.4             |
| Elementor (optional) | 3.0             |

---

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory.
2. The plugin folder must be named `itr-knowledgebase`.
3. Go to **WordPress Admin → Plugins** and activate **ITR Knowledgebase**.
4. Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.
5. Navigate to **KB Articles → Settings** to configure the plugin.

---

## Features

### Content Structure

- **Custom Post Type** — `itr_kb_article` with full REST API support, revisions, and custom capabilities.
- **Hierarchical Categories** — Unlimited nesting depth. Each category supports a custom icon (Dashicons), category image, and custom sort order via drag-and-drop.
- **Tags** — Flat tagging taxonomy for cross-category article organisation.
- **Authors & Reviewers** — Independent `itr_kb_author` CPT. Each person can be assigned the role of Author, Reviewer, or Both.

### Author & Reviewer Inheritance System

- Assign default authors and reviewers directly to categories and sub-categories.
- Articles automatically **inherit** author/reviewer from their assigned category chain.
- Inheritance walks the full hierarchy: `Sub-Sub-Sub → Sub-Sub → Sub → Parent`.
- Each field (author, reviewer) is resolved **independently** — an article can inherit its author from one level and its reviewers from another.
- Manually overriding a field on an article **freezes** that field — category changes will never touch it again.
- The article edit screen shows a badge for each field: **Inherited from: [Category Name]** or **Manually set**, with a one-click **Clear Override** option.
- When a category's author or reviewer changes, all inheriting articles in that category are **backfilled automatically** in batches of 50.
- When an author is deleted or trashed, all article references are cleared automatically and inheritance is re-applied.

### Elementor Integration

- **Custom widget category** — ITR Knowledgebase.
- **10 widgets**: Search, Breadcrumb, Category Tree, Category Grid, Article List, Article Accordion, TOC, Author Box, Content Sections, Category Accordion.
- **4 dynamic tags**: KB Publish Date, KB Last Updated Date, KB View Count, KB Author Name.

### Frontend Templates

- **Single article** — 3-column layout: TOC sidebar (sticky) | Article content | Category sidebar (collapsible, all closed by default).
- **Archive / Category** — 2-column layout with accordion category navigation. Three modes: Main Archive (banner + sections), Category page (subcategory cards + article list), Tag page (flat list).
- **Table of Contents** — Auto-generated from H2/H3/H4 headings with scroll-spy and collapse toggle.
- **Breadcrumb** — Schema.org-compliant. Reflects the category path the user navigated through when Category-in-URL is enabled.
- **Prev/Next navigation** within the same category.
- **Print** — Clean print window with site logo, article content only. No theme header/footer included.

### Category in Article URL (Optional)

Enable in **Settings → Permalink** to include the full category hierarchy in article URLs when linking from category pages:

```
OFF (default):  /knowledgebase/article-slug/
ON:             /knowledgebase/parent/child/sub/article-slug/
```

- The canonical URL (used in admin, sitemaps) is **never changed**.
- Old URLs remain valid — no 404s for existing links or bookmarks.
- The breadcrumb automatically reflects the category path from the URL.

### Search

- AJAX live search with keyword highlighting.
- Context-aware excerpts showing matched content.
- Popular and failed search tracking.
- Load More support.

### Import / Export

- **Export** — Downloads a JSON file containing all articles, categories, tags, and authors.
- **Import** — Chunked file upload supporting files up to 200 MB. The file is uploaded in 5 MB pieces (no server PHP upload limits apply). Categories are imported in parent-before-child order at any nesting depth. Import processes in batches of 50 with a real-time progress bar and log.
- Compatible with the Echo KB Exporter plugin format.
- Supports Rank Math SEO meta on articles.

### Performance

- Batch processing for all bulk operations (inheritance backfill, author deletion cleanup).
- Direct `update_post_meta()` calls — never `wp_update_post()` — for bulk operations to avoid side effects.
- Term cache flushed after each category insert during import to prevent stale object-cache reads.
- Dashicons enqueued for all visitors (not just logged-in users).

---

## Settings

Navigate to **KB Articles → Settings** to configure:

| Tab           | Options                                                                                               |
| ------------- | ----------------------------------------------------------------------------------------------------- |
| **General**   | Articles per page, featured articles, TOC, breadcrumb, print button, back-to-top, view count tracking |
| **Permalink** | KB base slug, category slug, category-in-URL toggle                                                   |
| **Search**    | Enable live search, results count, keyword highlighting                                               |
| **Styling**   | Typography (heading/body fonts via Google Fonts), full colour palette, spacing, border radius         |

---

## Admin Screens

- **KB Articles** — Article list with category/tag filter dropdowns, Featured column, bulk Mark/Remove Featured actions.
- **Categories** — Hierarchical category management with icon picker, image upload, default author/reviewer fields, drag-and-drop reorder.
- **Authors & Reviewers** — CPT with role assignment (Author / Reviewer / Both) and photo support.
- **Import / Export** — Tab-based screen with chunked file upload and real-time import progress.
- **Settings** — Four-tab settings panel.

---

## Custom Capabilities

The plugin registers 15 custom capabilities distributed across administrator, editor, author, and contributor roles:

```
manage_itr_kb_categories    edit_itr_kb_articles
edit_itr_kb_article         delete_itr_kb_articles
delete_itr_kb_article       publish_itr_kb_articles
read_private_itr_kb_articles
```

---

## REST API

Namespace: `itr-kb/v1`

| Endpoint        | Method | Description                                                                  |
| --------------- | ------ | ---------------------------------------------------------------------------- |
| `/search`       | GET    | Live search with `query` parameter                                           |
| `/articles`     | GET    | Articles by type: `latest`, `popular`, `trending`, `featured`, `recommended` |
| `/categories`   | GET    | All categories with hierarchy                                                |
| `/article/{id}` | GET    | Single article with meta                                                     |

---

## File Structure

```
itr-knowledgebase/
├── itr-knowledgebase.php           # Plugin entry point
├── uninstall.php                   # Cleanup on uninstall
├── assets/
│   ├── css/
│   │   ├── itr-kb-frontend.css
│   │   └── itr-kb-admin.css
│   └── js/
│       ├── itr-kb-frontend.js
│       └── itr-kb-admin.js
├── elementor/
│   ├── class-itr-kb-elementor.php
│   ├── class-itr-kb-elementor-tags.php
│   └── widgets/                    # 10 Elementor widgets
├── includes/
│   ├── class-itr-kb-plugin.php     # Central bootstrapper
│   ├── class-itr-kb-loader.php     # Hook loader
│   ├── class-itr-kb-activator.php
│   ├── class-itr-kb-deactivator.php
│   ├── class-itr-kb-inheritance.php  # Author/reviewer inheritance engine
│   ├── admin/                      # All admin classes
│   ├── api/                        # REST API
│   ├── frontend/                   # Frontend classes (TOC, breadcrumb, search, etc.)
│   ├── helpers/                    # Security, utils, query helpers
│   ├── post-types/                 # Article and Author CPTs
│   └── taxonomies/                 # Category and Tag taxonomies
└── templates/
    ├── single-itr-kb.php
    ├── archive-itr-kb.php
    └── partials/
        ├── itr-kb-author-box.php
        ├── itr-kb-category-tree.php
        └── itr-kb-search-bar.php
```

---

## Theme Template Overrides

Copy any template file into your theme under `itr-knowledgebase/` to override it:

```
your-theme/
└── itr-knowledgebase/
    ├── single-itr-kb.php
    ├── archive-itr-kb.php
    └── partials/
        └── itr-kb-author-box.php
```

---

## Changelog

### 1.0.0

- Initial release.
- Hierarchical category management with unlimited nesting.
- Author & Reviewer inheritance system with manual override tracking.
- Chunked file upload for large imports (up to 200 MB).
- Category-in-URL permalink option.
- 10 Elementor widgets and 4 dynamic tags.
- Full REST API.
- Rank Math SEO meta support on import.

---

## License

GPL-2.0+. See [http://www.gnu.org/licenses/gpl-2.0.txt](http://www.gnu.org/licenses/gpl-2.0.txt).

---

## Author

Developed by [NaeemAfridi].
