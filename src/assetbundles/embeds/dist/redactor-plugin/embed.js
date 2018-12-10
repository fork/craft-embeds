(function ($R) {

    $R.add('plugin', 'embed', {
        // set translations
        translations: {
            en: {
                "insert-embed": "Insert Embed"
            },
            de: {
                "insert-embed": "Embed einfügen"
            }
        },
        init: function (app) {
            this.app = app;
            this.toolbar = app.toolbar;
            this.insertion = app.insertion;

            // define lang service
            this.lang = app.lang;
        },
        start: function () {
            // set up the button with lang variable
            var buttonData = {
                title: this.lang.get('insert-embed'),
                api: 'plugin.embed.toggle'
            };

            // add the button to the toolbar
            var $button = this.toolbar.addButton('insert-embed', buttonData);
            $button.setIcon('<i class="re-icon-embed"></i>');
        },
        toggle: function () {
            this.insertion.insertHtml('<hr class="redactor_pagebreak" embed="test" style="display:none" unselectable="on" contenteditable="false" />');
        },
        onchanged: function (html) {
            var event = new Event('custom-redactor-events');
            window.dispatchEvent(event);
        }
    });
})(Redactor);
