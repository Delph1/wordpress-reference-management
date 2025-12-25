(function() {
    tinymce.create('tinymce.plugins.wprm', {
        init : function(ed, url) {
            ed.addButton('wprm', {
                title : 'Insert Reference',
                icon : 'icon dashicons-book-alt',
                onclick : function() {
                    // TODO: Open reference picker modal
                    ed.windowManager.open({
                        title: 'Insert or Add Reference',
                        body: [
                            {type: 'textbox', name: 'ref_id', label: 'Reference ID (existing)'},
                            {type: 'label', text: 'Or add a new reference below:'},
                            {type: 'textbox', name: 'authors', label: 'Authors'},
                            {type: 'textbox', name: 'title', label: 'Title'},
                            {type: 'textbox', name: 'publication', label: 'Publication'},
                            {type: 'textbox', name: 'year', label: 'Year'},
                            {type: 'textbox', name: 'url', label: 'URL'}
                        ],
                        onsubmit: function(e) {
                            if (e.data.ref_id) {
                                ed.insertContent('[wprm_cite id="' + e.data.ref_id + '"]');
                            } else if (e.data.title) {
                                // Add new reference via AJAX
                                var data = {
                                    action: 'wprm_add_reference',
                                    authors: e.data.authors || '',
                                    title: e.data.title || '',
                                    publication: e.data.publication || '',
                                    year: e.data.year || '',
                                    url: e.data.url || ''
                                };
                                var win = this;
                                jQuery.post(ajaxurl, data, function(response) {
                                    if (response.success && response.data && response.data.id) {
                                        ed.insertContent('[wprm_cite id="' + response.data.id + '"]');
                                    } else {
                                        alert('Failed to add reference: ' + (response.data || 'Unknown error'));
                                    }
                                });
                            } else {
                                alert('Please enter an existing Reference ID or fill in the Title to add a new reference.');
                            }
                        }
                    });
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        }
    });
    tinymce.PluginManager.add('wprm', tinymce.plugins.wprm);
})();
