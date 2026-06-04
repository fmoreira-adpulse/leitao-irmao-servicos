(function($) {
    $.fn.annotateImage = function(options) {
        var config = $.extend({}, $.fn.annotateImage.defaults, options);
        
		var $element = this;
        $element.mode = 'view';

        $element.input_field_id = config.input_field_id;
        $element.interdict_areas_overlap = config.interdict_areas_overlap;
        $element.captions = config.captions;
        $element.getUrl = config.getUrl;
        $element.saveUrl = config.saveUrl;
        $element.deleteUrl = config.deleteUrl;
        $element.editable = config.editable;
        $element.useAjax = config.useAjax;
        $element.notes = config.notes;

        $element.background = $('<div class="image-annotate-background"><div class="image-annotate-view"></div><div class="image-annotate-edit"><div class="image-annotate-edit-area"></div></div></div>');
        $element.background.children('.image-annotate-edit').hide();
        $element.background.children('.image-annotate-view').hide();
        $element.after($element.background);

        $element.background.height($element.height());
        $element.background.width($element.width());
        $element.background.css('background-image', 'url("' + $element.attr('src') + '")');
        $element.background.css('background-size', 'cover');
        $element.background.children('.image-annotate-view, .image-annotate-edit').height($element.height());
        $element.background.children('.image-annotate-view, .image-annotate-edit').width($element.width());

        if ($element.editable) {
            this.button = $('<button class="button button-primary button-large" id="bwp-lookbook-add" onclick="javascript: return false;"><span>'+this.captions.button_add+'</span></button>');
            if($("input[name=lookbook_id]").val())
				this.button.show();
			else
				this.button.hide();
			this.button.click(function() {
                $.fn.annotateImage.add($element);
            });
            this.background.before(this.button);
        }

        $element.background.children('.image-annotate-view').hover(function() {
            $(this).show();
        }, function() {
            $(this).hide();
        });		
		
        $element.background.hover(function() {
            if ($(this).children('.image-annotate-edit').css('display') == 'none') {
                $(this).children('.image-annotate-view').show();
            }
        }, function() {
            $(this).children('.image-annotate-view').hide();
        });
		
        if ($element.useAjax) {
            $.fn.annotateImage.ajaxLoad(this);
        } else {
            $.fn.annotateImage.load(this);
        }
		
        $element.hide();

        return this;
    };

    $.fn.annotateImage.defaults = {
        getUrl: 'your-get.rails',
        saveUrl: 'your-save.rails',
        deleteUrl: 'your-delete.rails',
        editable: true,
        useAjax: true,
        notes: new Array()
    };

    $.fn.annotateImage.clear = function(image) {    
        for (var i = 0; i < image.notes.length; i++) {
            image.notes[image.notes[i]].destroy();
        }
        image.notes = new Array();
    };

    $.fn.annotateImage.ajaxLoad = function(image) {
        $.getJSON(image.getUrl + '?ticks=' + $.fn.annotateImage.getTicks(), function(data) {
            image.notes = data;
            $.fn.annotateImage.load(image);
        });
    };

    $.fn.annotateImage.load = function(image) {
        for (var i = 0; i < image.notes.length; i++) {
            image.notes[image.notes[i]] = new $.fn.annotateView(image, image.notes[i]);
        }
    };

    $.fn.annotateImage.getTicks = function() {   
        var now = new Date();
        return now.getTime();
    };

    $.fn.annotateImage.add = function(image) {
        if (image.mode == 'view') {
            image.mode = 'edit';
            var editable = new $.fn.annotateEdit(image);

            $.fn.annotateImage.createCancelButton(editable, image);
            $.fn.annotateImage.createSaveButton(editable, image);
            $("#image-annotate-edit-ok").css('float', 'right').css('margin-right', '25px');
            $(".image-annotate-edit-close").css('margin-left', '25px');
        }

    };

    $.fn.annotateImage.createSaveButton = function(editable, image, note) {
        var ok = $('<button class="image-annotate-edit-ok" id="image-annotate-edit-ok" onclick="javascript: return false;"><span>OK</span></button>');

        ok.click(function() {
            var form = $('#image-annotate-edit-form form');
            var slug = $('#bwp-lookbook-slug').val();
		
            $.fn.annotateImage.appendPosition(form, editable)
            image.mode = 'view';
            if (image.useAjax) {
                $.ajax({
                    url: image.saveUrl,
                    data: form.serialize(),
                    error: function(e) { alert(image.captions.alert_saving_error) },
                    success: function(data) {
				if (data.annotation_id != undefined) {
					editable.note.id = data.annotation_id;
				}
		    },
                    dataType: "json"
                });
            }

            if (image.interdict_areas_overlap) {
                var test_area = Object();
                test_area.id = editable.note.id;
                test_area.height = editable.area.height();
                test_area.width = editable.area.width();
                test_area.left = editable.area.position().left;
                test_area.top = editable.area.position().top;
		        var notes_obj = jQuery.parseJSON(JSON.stringify(image.notes));

                if (notes_obj && !checkPositionHostpost(test_area, notes_obj)) {
                    alert(image.captions.alert_overlap_error);
                    return false;
                }
            }  
            

            var hide_popup = false;
			if ($.trim(slug)=='') {
				alert(image.captions.alert_sku_error);
				return false;
			}else {
				jQuery.post( relative_url + "/wp-admin/admin.php?page=lookbook&bwp_action=check_isset_product", {post_id: jQuery("#bwp-lookbook-slug").val(), noredirect: 1}, function( data ) {
					if (data != 1) {
						alert(image.captions.alert_product_dont_exists + '"' + slug + '" ' + data);
						return false;
					}else {
						editable.destroy();
					}
				}).fail(function() {
					alert("Unable to check product Slug");
					return 0;
				});

			}

            if (note) {
                note.resetPosition(editable,slug);             
            } else {
                editable.note.editable = true;
                note = new $.fn.annotateView(image, editable.note);
                note.resetPosition(editable,slug);
                image.notes.push(editable.note);
            }  

            $('#'+image.input_field_id).val(JSON.stringify(image.notes));

            if (hide_popup){
                editable.destroy();
            }

        });
        editable.form.append(ok);
    };

    $.fn.annotateImage.createCancelButton = function(editable, image) {
        var cancel = $('<button class="image-annotate-edit-close" onclick="javascript: return false;"><span>'+image.captions.cancel_btn+'</span></button>');
        cancel.click(function() {
            editable.destroy();
            image.mode = 'view';
        });
        editable.form.append(cancel);
    };

    $.fn.annotateImage.saveAsHtml = function(image, target) {
        var element = $(target);
        var html = "";
        for (var i = 0; i < image.notes.length; i++) {
            html += $.fn.annotateImage.createHiddenField("sku_" + i, image.notes[i].slug);
            html += $.fn.annotateImage.createHiddenField("top_" + i, image.notes[i].top);
            html += $.fn.annotateImage.createHiddenField("left_" + i, image.notes[i].left);
            html += $.fn.annotateImage.createHiddenField("height_" + i, image.notes[i].height);
            html += $.fn.annotateImage.createHiddenField("width_" + i, image.notes[i].width);
        }
        element.html(html);
    };

    $.fn.annotateImage.createHiddenField = function(name, value) {
        return '&lt;input type="hidden" name="' + name + '" value="' + value + '" /&gt;<br />';
    };

    $.fn.annotateEdit = function(image, note) {
        this.image = image;
        if (note) {
            this.note = note;
        } else {
            var newNote = new Object();
            newNote.id = ""+new Date().getTime();
            newNote.top = 30;
            newNote.left = 30;
            newNote.width = 30;
            newNote.height = 30;
            newNote.slug = "";
            newNote.img_height = this.image.height();
            newNote.img_width = this.image.width();
            this.note = newNote;
        }
        var area = image.background.children('.image-annotate-edit').children('.image-annotate-edit-area');
        this.area = area;
        this.area.css('height', this.note.height + 'px');
        this.area.css('width', this.note.width + 'px');
        this.area.css('left', this.note.left + 'px');
        this.area.css('top', this.note.top + 'px');
        image.background.children('.image-annotate-view').hide();
        image.background.children('.image-annotate-edit').show();
        var p_link_selected = '';
        var p_show = '';
        if (this.note.slug!=''){ 
           p_link_selected = 'checked="checked"';
           p_show = 'style="display:block"'; 
        }
		
        var form_str = '<div id="image-annotate-edit-form"><form id="annotate-edit-form">'
                    +'<div id="product-data" '+p_show+'><p><label for="bwp-lookbook-slug">'+image.captions.prod_sku+' </label>'
                    +'<input id="bwp-lookbook-slug" value="'+this.note.slug+'" name="slug" type="text" autocomplete="off"/></p><div class="result_search_product"></div></div>'                    
                    +'</form></div>'
        var form = $(form_str);
        this.form = form;
		
        $('body').append(this.form);
        this.form.css('left', this.area.offset().left + 'px');
        this.form.css('top', (parseInt(this.area.offset().top) + parseInt(this.area.height()) + 7) + 'px');

		/*Search JS*/
		$("#bwp-lookbook-slug").on("keydown", function() {
		  setTimeout(function($e){
			var character = $e.val();
			if(character.length > 1){
				jQuery.post( 
					relative_url + "/wp-admin/admin.php?page=lookbook&bwp_action=search_product",
					{character: character, noredirect: 1},
					function( html ) {
						$( ".result_search_product" ).html(html);
						$(".item-list-product").click(function(){
							var slug = $(this).data("slug");
							$("#bwp-lookbook-slug").val(slug);
						});						
					}).fail(function() {
						alert("Unable to check product ID");
						return 0;
				});		
			}			
		  }, 200, $(this));
		});
		
        area.resizable({
            handles: 'all',

            stop: function(e, ui) {
                form.css('left', area.offset().left + 'px');
                form.css('top', (parseInt(area.offset().top) + parseInt(area.height()) + 2) + 'px');
            }
        })
        .draggable({
            containment: image.background,
            drag: function(e, ui) {
                form.css('left', area.offset().left + 'px');
                form.css('top', (parseInt(area.offset().top) + parseInt(area.height()) + 2) + 'px');
            },
            stop: function(e, ui) {
                form.css('left', area.offset().left + 'px');
                form.css('top', (parseInt(area.offset().top) + parseInt(area.height()) + 2) + 'px');
            }
        });

        return this;
    };

    $.fn.annotateEdit.prototype.destroy = function() {
		var $element = this;	
        $element.image.background.children('.image-annotate-edit').hide();
        $element.area.resizable('destroy');
        $element.area.draggable('destroy');
        $element.area.css('height', '');
        $element.area.css('width', '');
        $element.area.css('left', '');
        $element.area.css('top', '');
        $element.form.remove();
        displayPinsMessenger();  
    }

    $.fn.annotateView = function(image, note) {
		var $element = this;
        $element.image = image;
        $element.note = note;
        $element.editable = (note.editable && image.editable);
        $element.area = $('<div class="bwp-lookbook-area' + ($element.editable ? ' bwp-lookbook-area-editable' : '') + '"><div></div></div>');
        image.background.children('.image-annotate-view').prepend($element.area);
        var str_text = note.slug;
        $element.form = $('<div class="bwp-lookbook-note">' + str_text + '</div>');
        $element.form.hide();
        image.background.children('.image-annotate-view').append($element.form);
        $element.form.children('span.actions').hide();
        $element.setPosition();
        this.area.hover(function() {
            $element.show();
        }, function() {
            $element.hide();
        });
        if (this.editable) {
            var form = this;
            this.area.click(function() {
                form.edit();
            });
        }
    };

    $.fn.annotateView.prototype.setPosition = function() {
        this.area.children('div').height((parseInt(this.note.height) - 2) + 'px');
        this.area.children('div').width((parseInt(this.note.width) - 2) + 'px');
        this.area.css('left', (this.note.left) + 'px');
        this.area.css('top', (this.note.top) + 'px');
        this.form.css('left', (this.note.left) + 'px');
        this.form.css('top', (parseInt(this.note.top) + parseInt(this.note.height) + 7) + 'px');
    };

    $.fn.annotateView.prototype.show = function() {
        this.form.fadeIn(250);
        if (!this.editable) {
            this.area.addClass('bwp-lookbook-area-hover');
        } else {
            this.area.addClass('bwp-lookbook-area-editable-hover');
        }
    };

    $.fn.annotateView.prototype.hide = function() {    
        this.form.fadeOut(250);
        this.area.removeClass('bwp-lookbook-area-hover');
        this.area.removeClass('bwp-lookbook-area-editable-hover');
    };

    $.fn.annotateView.prototype.destroy = function() {
        this.area.remove();
        this.form.remove();
    }

    $.fn.annotateView.prototype.edit = function() {     
        if (this.image.mode == 'view') {
            this.image.mode = 'edit';
            var annotation = this;
            var editable = new $.fn.annotateEdit(this.image, this.note);
            var del = $('<button class="image-annotate-edit-delete" onclick="javascript: return false;"><span>'+this.image.captions.delete_btn+'</span></button>');
            del.click(function() {
                var form = $('#image-annotate-edit-form form');

                $.fn.annotateImage.appendPosition(form, editable)

                if (annotation.image.useAjax) {
                    $.ajax({
                        url: annotation.image.deleteUrl,
                        data: form.serialize(),
                        error: function(e) { alert(annotation.image.captions.alert_delete_pin) }
                    });
                }

                for (var i = 0; i < annotation.image.notes.length; i++) {
                    if (annotation.image.notes[i]==editable.note) 
                    {
                        annotation.image.notes.splice(i,1);
                    }
                } 
                $('#'+annotation.image.input_field_id).val(JSON.stringify(annotation.image.notes));

                annotation.image.mode = 'view';
                editable.destroy();
                annotation.destroy(); 
              
            });
            editable.form.append(del);
                       
            $.fn.annotateImage.createCancelButton(editable, this.image); 
            $.fn.annotateImage.createSaveButton(editable, this.image, annotation);
        }
    };

    $.fn.annotateImage.appendPosition = function(form, editable) {
        var areaFields = $('<input type="hidden" value="' + editable.area.height() + '" name="height"/>' +
                           '<input type="hidden" value="' + editable.area.width() + '" name="width"/>' +
                           '<input type="hidden" value="' + editable.area.position().top + '" name="top"/>' +
                           '<input type="hidden" value="' + editable.area.position().left + '" name="left"/>' +
                           '<input type="hidden" value="' + editable.note.id + '" name="id"/>');
        form.append(areaFields);
    }

    $.fn.annotateView.prototype.resetPosition = function(editable,slug) {
        if (slug!=''){
            this.form.html(slug);
        }
        
        this.form.hide();

        // Resize
        this.area.children('div').height(editable.area.height() + 'px');
        this.area.children('div').width((editable.area.width() - 2) + 'px');
        this.area.css('left', (editable.area.position().left) + 'px');
        this.area.css('top', (editable.area.position().top) + 'px');
        this.form.css('left', (editable.area.position().left) + 'px');
        this.form.css('top', (parseInt(editable.area.position().top) + parseInt(editable.area.height()) + 7) + 'px');
        // Save new position to note
        this.note.top = editable.area.position().top;
        this.note.left = editable.area.position().left;
        this.note.height = editable.area.height();
        this.note.width = editable.area.width();
        this.note.slug = slug;
        this.note.id = editable.note.id;
        this.editable = true;
    };

    intersects = function(X1, Y1, H1, L1, X2, Y2, H2, L2) {
        X1 = parseInt(X1);
        Y1 = parseInt(Y1);
        H1 = parseInt(H1);
        L1 = parseInt(L1);
        X2 = parseInt(X2);
        Y2 = parseInt(Y2);
        H2 = parseInt(H2);
        L2 = parseInt(L2);
        a = X1 + L1 < X2;
        b = X1 > X2 + L2;
        c = Y1 + H1 < Y2;
        d = Y1 > Y2 + H2;                            
        if ((a || b || c || d)) {
            return false;
        }
        else
        {
            return true;
        }
    };
    
    displayPinsMessenger = function() {
           view_is_visible = $(".image-annotate-background").find(".image-annotate-view").is(":visible");
           edit_is_visible = $(".image-annotate-background").find(".image-annotate-edit").is(":visible");
           if (view_is_visible || edit_is_visible) {
                 $(".pins-msg").hide();
           }
           else
           {
                $(".pins-msg").show();
           }
    }
    
    checkPositionHostpost = function(note, notes) {
        i=0;
        item = true;

        if (typeof notes !== 'undefined' && notes.length > 0){
            $(notes).each( function() {
                if (note.id!=notes[i].id) {
                    if (intersects(note.left, note.top, note.height, note.width, notes[i].left, notes[i].top, notes[i].height, notes[i].width)){
                     item = false;
                    }
            }
            i++;
        });
        }
        return item;
    };

})(jQuery);