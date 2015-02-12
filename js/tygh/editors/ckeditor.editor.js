/* editior-description:tmpl_text_ckeditor */

(function(_, $) {

    $.ceEditor('handlers', {

        editorName: 'ckeditor',
        params: null,
        
        run: function(elm, params) {
            CKEDITOR_BASEPATH = _.current_location + '/js/lib/ckeditor/';
            
            if (typeof(window.CKEDITOR) == 'undefined') {
                $.ceEditor('state', 'loading');
                return $.getScript('js/lib/ckeditor/ckeditor.js', function() {
                    $.ceEditor('state', 'loaded');
                    elm.ceEditor('run', params);
                });
            }
                
            if (!this.params) {
                this.params = {
                    toolbar: [['Format','Font','FontSize', 'Bold','Italic','Underline','TextColor','BGColor','-','Link','Image','Table','-','NumberedList','BulletedList','Indent','Outdent','JustifyLeft','JustifyCenter','JustifyRight','-','Source']],
                    bodyClass: 'wysiwyg-content',
                    contentsCss: $.ceEditor('content_css'),
                    filebrowserBrowseUrl : _.current_location + '/js/lib/elfinder/elfinder.ckeditor.html',
                    filebrowserWindowWidth : '600',
                    filebrowserWindowHeight : '500',
              
                    protectedSource: /<(span)[^>]*>.*<\/span>|<(i)[^>]*>.*<\/i>/ig ,
                    on: {
                        change: function(e) {
                            elm.ceEditor('changed', CKEDITOR.instances[elm.prop('id')].getData());
                        }
                    }

                };

                if (typeof params !== 'undefined' && params[this.editorName]) {
                    $.extend(this.params, params[this.editorName]);
                }
            }

            CKEDITOR.replace(elm.prop('id'), this.params);
        },

        destroy: function(elm) {
            if (typeof(CKEDITOR.instances[elm.prop('id')]) != 'undefined') {
                CKEDITOR.instances[elm.prop('id')].destroy();
            }
        },

        updateTextFields: function(elm) {
            if (typeof(window.CKEDITOR) != 'undefined') {
                if (typeof(CKEDITOR.instances[elm.prop('id')]) != 'undefined') {
                    CKEDITOR.instances[elm.prop('id')].updateElement();
                }
            }
        },

        recover: function(elm) {
            $.ceEditor('run', elm);
        },
        
        val: function(elm, value) {
            if (typeof(value) == 'undefined') {
                return CKEDITOR.instances[elm.prop('id')].getData();
            } else {
                CKEDITOR.instances[elm.prop('id')].setData(value);
            }
            return true;
        },

        disable: function(elm, value) {
            if (typeof(window.CKEDITOR) != 'undefined') {
                CKEDITOR.instances[elm.prop('id')].setReadOnly(value);
            }
            $(elm).prop('disabled', value);
        }
    });
    


    // FIXME: when jQuery UI will be updated from 1.11.1 version, remove the code below.
    $.widget( "ui.dialog", $.ui.dialog, {
     /*! jQuery UI - v1.10.2 - 2013-12-12
      *  http://bugs.jqueryui.com/ticket/9087#comment:27 - bugfix
      *  http://bugs.jqueryui.com/ticket/4727#comment:23 - bugfix
      *  allowInteraction fix to accommodate windowed editors
      */
      _allowInteraction: function( event ) {
        if ( this._super( event ) ) {
          return true;
        }

        // address interaction issues with general iframes with the dialog
        if ( event.target.ownerDocument != this.document[ 0 ] ) {
          return true;
        }

        // address interaction issues with dialog window
        if ( $( event.target ).closest( ".cke_dialog" ).length ) {
          return true;
        }

        // address interaction issues with iframe based drop downs in IE
        if ( $( event.target ).closest( ".cke" ).length ) {
          return true;
        }
      },
     /*! jQuery UI - v1.10.2 - 2013-10-28
      *  http://dev.ckeditor.com/ticket/10269 - bugfix
      *  moveToTop fix to accommodate windowed editors
      */
      _moveToTop: function ( event, silent ) {
        if ( !event || !this.options.modal ) {
          this._super( event, silent );
        }
      }
    });

}(Tygh, Tygh.$));
