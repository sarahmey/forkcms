CKEDITOR.dialog.add(
    'linkDialog',
    function (editor) {
        var urlRegex = '(https?:\\/\\/(www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{2,256}(\\.[a-z]{2,6})?)?\\/([-a-zA-Z0-9@:%_\\+.~#?&//=]*)';

        return {
            title: editor.lang.medialibrary.addLink,

            minWidth: 400,
            minHeight: 200,

            contents: [
                {
                    id: 'tab',
                    label: '',
                    elements: [
                        {
                            type: 'text',
                            id: 'displayText',
                            label: editor.lang.medialibrary.displayText,
                            validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.medialibrary.displayCannotBeEmpty),
                            setup: function (element) {
                                this.setValue(element.getText());
                            },
                            commit: function (element) {
                                element.setText(this.getValue());
                            }
                        },
                        {
                            type: 'hbox',
                            widths: ['65%', '5%', '30%'],
                            children: [
                                {
                                    type: 'text',
                                    id: 'url',
                                    label: 'URL',
                                    validate: CKEDITOR.dialog.validate.regex(new RegExp(urlRegex), editor.lang.medialibrary.urlNotValid),
                                    setup: function (element) {
                                        this.setValue(element.getAttribute('href'));
                                    },
                                    commit: function (element) {
                                        element.setAttribute('href', this.getValue());
                                        element.setAttribute('data-cke-saved-href', this.getValue());
                                    }
                                },
                                {
                                    type: 'html',
                                    html: '<span style="display: block;padding: 27px 5px 0 5px;">or</span>'
                                },
                                {
                                    type: 'button',
                                    id: 'browseServer',
                                    label: editor.lang.medialibrary.browseServer,
                                    onClick: function () {
                                        var editor = this.getDialog().getParentEditor();
                                        editor.popup(window.location.origin + jsData.MediaLibrary.browseAction, 800, 800);

                                        window.onmessage = function (event) {
                                            if (event.data) {
                                                this.setValueOf('tab', 'url', event.data);
                                            }
                                        }.bind(this.getDialog());
                                    },
                                    style: 'margin-top: 20px;'
                                }
                            ]
                        }
                    ]
                }
            ],

            onOk: function () {
                var dialog = this,
                    anchor = dialog.element;

                dialog.commitContent(anchor);

                if (dialog.insertMode) {
                    editor.insertElement(anchor);
                }
            },

            onShow: function () {
                var dialog = this;

                var selection = editor.getSelection();
                var initialText = selection.getSelectedText();
                var element = selection.getStartElement();

                if (element) {
                    element = element.getAscendant('a', true);
                }

                dialog.insertMode = !element || element.getName() !== 'a';
                if (dialog.insertMode) {
                    element = editor.document.createElement('a');

                    dialog.setValueOf('tab', initialText.match(urlRegex) ? 'url' : 'displayText', initialText);
                }

                this.element = element;

                if (!this.insertMode) {
                    this.setupContent(element);
                }
            }
        };
    }
);
