# ITR Knowledgebase

A powerful, fully customizable Knowledge Base plugin for WordPress with Elementor support. Built for large-scale knowledge bases with hierarchical categories, author/reviewer management, ad banner system, advanced import/export, and full Elementor integration.

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
- **Hierarchical Categories** — Unlimited nesting depth. Each category supports a custom icon (Dashicons), category image, custom sort order via drag-and-drop, default author/reviewer, and four ad banner positions.
- **Tags** — Flat tagging taxonomy for cross-category article organisation.
- **Authors & Reviewers** — Independent `itr_kb_author` CPT. Each person can be assigned the role of Author, Reviewer, or Both.

---

### Author & Reviewer Inheritance System

- Assign default authors and reviewers directly to categories and sub-categories.
- Articles automatically **inherit** author/reviewer from their assigned category chain.
- Inheritance walks the full hierarchy: `Sub-Sub-Sub → Sub-Sub → Sub → Parent`.
- Each field (author, reviewer) is resolved **independently**.
- Manually overriding a field on an article **freezes** that field — category changes will never touch it again.
- The article edit screen shows a badge: **Inherited from: [Category Name]** or **Manually set**, with a one-click **Clear Override** option.
- When a category's author or reviewer changes, all inheriting articles are **backfilled automatically** in batches of 50.
- When an author is **deleted or trashed**, all article references are cleared and inheritance is re-applied.
- When a **category is deleted**, articles are automatically reassigned to the parent category (if one exists) and inheritance re-resolves. If the deleted category was top-level, affected article fields go empty.

---

### Ad Banner System

- Set **four banner positions** per category: Desktop TOC, Desktop Categories, Mobile Top, Mobile Bottom.
- Each position has an independent **image + URL pair** — image and URL always inherit together as a pair.
- **Inheritance** resolves at render time by walking the full category hierarchy. Child category always beats parent. Each position resolved independently.
- **Desktop TOC Banner** — rendered inside the sticky TOC container, scrolls with the TOC, never overlapped.
- **Desktop Categories Banner** — rendered inside the Categories sidebar column, always correctly positioned.
- **Mobile Top Banner** — above article content on mobile only.
- **Mobile Bottom Banner** — below article content on mobile only.
- TOC column renders even when TOC is hidden (no headings), as long as a TOC banner is set.
- If a URL is set the banner is clickable (same tab). If no URL, banner is non-clickable.
- If no banner resolves for a position, nothing is rendered — no gap or placeholder.
- **Elementor Widget** — KB Banner widget with Position dropdown for Elementor single-article templates.
- **Shortcode** — `[itr_kb_banner position="desktop_toc"]`. Valid positions: `desktop_toc`, `desktop_categories`, `mobile_top`, `mobile_bottom`.
- Accepted image formats: JPG, PNG, GIF, WebP. Maximum upload size: 5 MB. Videos and other non-image formats are blocked from selection in the media picker.

---

### Elementor Integration

- **Custom widget category** — ITR Knowledgebase.
- **11 widgets**: Search, Breadcrumb, Category Tree, Category Grid, Article List, Article Accordion, TOC, Author Box, Content Sections, Category Accordion, Banner.
- **4 dynamic tags**: KB Publish Date, KB Last Updated Date, KB View Count, KB Author Name.

---

### Category Grid & Category Accordion Widgets

- **Source control** — Auto (context-based query) or Manual (select specific categories).
- **Manual mode** — Elementor Repeater lets you add categories one by one and **drag-and-drop reorder** them freely. Row titles show the category name (not the ID).
- Only top-level parent categories appear in the selector. Sub-categories render automatically inside each card/panel.

---

### Frontend Templates

- **Single article** — 3-column layout: TOC/Banner sidebar (sticky) | Article content | Category sidebar (collapsible, all closed by default).
- **Archive / Category** — 2-column layout. Three modes: Main Archive (banner + category sections), Category page (subcategory cards + article list with Load More), Tag page (flat list).
- **Table of Contents** — Auto-generated from H2/H3/H4 headings with scroll-spy and collapse toggle.
- **Breadcrumb** — Schema.org-compliant. Reflects the category path the user navigated through when Category-in-URL is enabled.
- **Prev/Next navigation** within the same category.
- **Print** — Clean popup window with site logo and article content only. No theme header/footer.

---

### Category in Article URL (Optional)

Enable in **Settings → Permalink** to include the full category hierarchy in article URLs when linking from category pages:

```
OFF (default):  /knowledgebase/article-slug/
ON:             /knowledgebase/parent/child/sub/article-slug/
```

- Canonical URL (used in admin, sitemaps) is **never changed**.
- Old URLs remain valid — no 404s.
- Breadcrumb automatically reflects the URL category path.

---

### Search

- AJAX live search with keyword highlighting and context-aware excerpts.
- Popular and failed search tracking.
- Load More support.

---

### Import / Export

- **Export** — JSON file containing all articles, categories, tags, and authors.
- **Import** — Chunked file upload supporting files up to 200 MB (uploaded in 5 MB pieces, bypassing PHP server limits). Categories imported parent-before-child at any nesting depth. Real-time progress bar and log.
- Compatible with Echo KB Exporter format.
- Supports Rank Math SEO meta on articles.

---

### Performance

- Batch processing for all bulk operations (inheritance backfill, author/category deletion cleanup).
- Direct `update_post_meta()` — never `wp_update_post()` — for bulk operations.
- Term cache flushed after each category insert during import.
- Dashicons enqueued for all visitors (not just logged-in users).

---

## Settings

Navigate to **KB Articles → Settings**. Each tab has its own independent save handler — saving one tab **never affects** settings on other tabs.

| Tab           | Options                                                                     |
| ------------- | --------------------------------------------------------------------------- |
| **General**   | Articles per page (default: 10), TOC, breadcrumb, print button, back-to-top |
| **Permalink** | KB base slug, category slug, category-in-URL toggle                         |
| **Search**    | Enable live search, results count (default: 5), keyword highlighting        |
| **Styling**   | Typography (Google Fonts), full colour palette, spacing, border radius      |

---

## Admin Screens

- **KB Articles** — Article list with category/tag filters, Featured column, bulk Mark/Remove Featured.
- **Categories** — Hierarchical management with icon picker, image upload, default author/reviewer, four ad banner pairs, drag-and-drop reorder.
- **Authors & Reviewers** — CPT with role assignment (Author / Reviewer / Both) and photo support.
- **Import / Export** — Chunked file upload with real-time progress.
- **Settings** — Four-tab panel with independent save handlers.

---

## Custom Capabilities

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
├── itr-knowledgebase.php
├── uninstall.php
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
│   └── widgets/
│       ├── class-itr-kb-widget-banner.php
│       └── ...10 other widgets
├── includes/
│   ├── class-itr-kb-plugin.php
│   ├── class-itr-kb-loader.php
│   ├── class-itr-kb-activator.php
│   ├── class-itr-kb-deactivator.php
│   ├── class-itr-kb-inheritance.php
│   ├── class-itr-kb-banner.php
│   ├── admin/
│   │   ├── class-itr-kb-term-author.php
│   │   ├── class-itr-kb-term-banner.php
│   │   └── ...other admin classes
│   ├── api/
│   ├── frontend/
│   ├── helpers/
│   ├── post-types/
│   └── taxonomies/
└── templates/
    ├── single-itr-kb.php
    ├── archive-itr-kb.php
    └── partials/
```

---

## Theme Template Overrides

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

### 1.0.2

- **Ad Banner System** — Four banner positions per category (Desktop TOC, Desktop Categories, Mobile Top, Mobile Bottom) with full hierarchy inheritance, `[itr_kb_banner]` shortcode, and KB Banner Elementor widget.
- **Banner positioning fixed** — TOC banner inside sticky container; Categories banner inside sidebar column. Both always correctly positioned.
- **Category deletion inheritance** — When a category is deleted, articles are auto-reassigned to the parent category and inheritance re-resolves. Plugin handles reassignment directly rather than relying on WordPress core behaviour.
- **Category Grid & Accordion** — Added Source (Auto/Manual) with Elementor Repeater for drag-and-drop manual category ordering. Row titles show category names.
- **Settings tabs** — Each tab now has its own independent save handler. Saving Styling tab can no longer reset TOC, Search, or other settings.
- **Fixed** Articles Per Page default set to 10; Search Results Count default set to 5.
- **Fixed** Author/reviewer badge showing blank source name after category deletion.
- **Fixed** `wp_delete_term` not reliably reassigning posts to parent for custom taxonomies — now handled explicitly in plugin.

### 1.0.1

- **Author/Reviewer Inheritance System** — Categories and sub-categories now have Author and Reviewer fields. Articles inherit values from their category chain at any depth. Each field resolved independently. Manual overrides tracked per field with Clear Override option in article editor.
- **Category in Article URL** — Optional setting to include full category hierarchy in article URLs when linking from category pages.
- **Chunked Import** — File upload now processes in 5 MB chunks, supporting files up to 200 MB.
- **Import sub-categories fixed** — Categories now created parent-before-child at any nesting depth.
- **Fixed** 404 on parent-only category URLs.
- **Fixed** Dashicons not loading for logged-out visitors.
- **Fixed** Author/reviewer still displaying after being deleted or trashed.
- **Fixed** Elementor button/link styles overridden by plugin CSS inside article body.
- **Fixed** Print popup no longer includes theme header/footer.
- **Fixed** `fixTOCAnchorLinks is not defined` JS error.
- **Fixed** Category sidebar — all nodes start collapsed by default.

### 1.0.0

- Initial release.
- Hierarchical category management with unlimited nesting.
- 10 Elementor widgets and 4 dynamic tags.
- Full REST API.
- Rank Math SEO meta support on import.

---

## License

GPL-2.0+. See [http://www.gnu.org/licenses/gpl-2.0.txt](http://www.gnu.org/licenses/gpl-2.0.txt).

---

## Author

Developed by [ITRoadway](https://itroadway.com).
