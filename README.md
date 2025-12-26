# WP Reference Manager

WP Reference Manager is a lightweight WordPress plugin to manage global references inside your WordPress installation and insert citations in posts/pages. It provides:

- Global reference database (stored in a custom DB table).
- Admin UI to add / edit / delete references.
- TinyMCE button to insert citations or create a new reference from the editor.
- Shortcodes:
  - `[wprm_cite id="123"]` — insert a citation in-text (renders a sequential number based on appearance order).
  - `[wprm_references]` — render the ordered reference list used in the current post/page in IEEE-like format.

### Installation

1. zip the folder and upload as a plugin

### Usage

- Open **References** in the admin menu to manage global references (authors, title, publication, year, URL).
- In the classic editor (TinyMCE), use the plugin button to insert an existing reference by ID or add a new reference directly and insert its citation.
- Place `[wprm_references]` in your post/page where you want the reference list to appear.
- Citations in the content are parsed to determine ordering, so the numbers in-text correspond to the numbered list.

Developer notes

- Database table: `{$wpdb->prefix}wprm_references` (created on activation).
- AJAX endpoint `wp_ajax_wprm_add_reference` allows adding references from the editor.
- The plugin attempts to avoid emitting output during plugin load (no stray `?>` or whitespace at file start/end).

### Contributing

Contributions welcome. Please open issues or PRs on the repository.

### License

GPL 3.0
