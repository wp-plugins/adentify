/**
 * Created by pierrickmartos on 06/10/2014.
 */
var AdEntifyBO = {

   tag: null,
   tags: [],
   photoIdSelected: null,
   wpPhotoIdSelected: null,
   currentSelectedPhoto: null,
   selectedPhotoClassName: 'ad-selected-photo',
   currentTagIndex: null,

   /*
    * Events handlers`
    */
   clickOnAdEntifyButton: function() {
      if (jQuery('#adentify-upload-modal').html() === undefined) {
         // render modals
         this.renderModals();
         // Setup event handlers
         this.setupEventHandlers();
      }
      else
         jQuery('#adentify-upload-modal').show();

      jQuery('body').addClass('ad-modal-open');
      jQuery('#__wp-uploader-id-2').focus();
   },

   clickOnUploadTab: function(e) {
      jQuery('#upload-file, #file-library').removeClass('active');
      jQuery(e.target).addClass('active');
      jQuery('#ad-uploader, #ad-library, #ad-tag-from-library, #ad-insert-from-library, #ad-delete-photo').hide();
      switch(e.target.id) {
         case 'file-library':
            jQuery('#ad-library, #ad-tag-from-library, #ad-insert-from-library, #ad-delete-photo').show();
            break;
         case 'upload-file':
         default:
            jQuery('#ad-uploader').show();
            break;
      }
      return false;
   },

   clickOnTagTab: function(e) {
      jQuery('.tag-tab').removeClass('active');
      jQuery(e.target).addClass('active');
      jQuery('.tag-form').hide();
      this.hideOpenedSelect2();

      // Switch tab
      jQuery(jQuery(e.target).data('target')).show();
      // Focus
      jQuery(jQuery(e.target).data('target') + 'input').first().focus();

      return false;
   },

   hideOpenedSelect2: function() {
      jQuery('#select2-drop-mask').click();
   },

   clickOnUploaderButton: function() {
       var that = this;
       jQuery('#upload-img').click().fileupload({
         datatype: 'json',
         url: adentifyTagsData.admin_ajax_url,
         formData: {
            'action': 'ad_upload'
         },
         add: function (e, data) {
            jQuery('#ad-uploader-content').hide();
            that.startLoading('uploading-message');
            data.submit();
         },
         success: function (data) {
            that.openPhotoModal(data.data.photo);
            that.appendPhotoToLibrary(data.data.photo, data.data.wp_photo_id);
            that.photoIdSelected = data.data.photo.id;
         },
          complete: function() {
             that.stopLoading('uploading-message');
             jQuery('#ad-uploader-content').show();
          }
      });
      return false;
   },

   clickOnLibraryPhoto: function(e) {
      if (this.currentSelectedPhoto) {
         this.currentSelectedPhoto.removeClass(this.selectedPhotoClassName);
      }
      this.currentSelectedPhoto = jQuery(e.target);
      this.currentSelectedPhoto.addClass(this.selectedPhotoClassName);
      jQuery('#ad-insert-from-library, #ad-tag-from-library, #ad-delete-photo').removeAttr('disabled');
      this.photoIdSelected = this.currentSelectedPhoto.attr('data-adentify-photo-id');
      this.wpPhotoIdSelected = this.currentSelectedPhoto.attr('data-wp-photo-id');
      return false;
   },

   clickOnDeletePhoto: function(e) {
      var that = this;
      if (!jQuery(e.target).is('[disabled]') && typeof this.photoIdSelected !== 'undefined' && this.photoIdSelected)
      {
         that.startLoading('tag-from-library');
         jQuery.ajax({
            type: 'GET',
            url: adentifyTagsData.admin_ajax_url,
            data: {
               'action': 'ad_delete_photo',
               'wp_photo_id': that.wpPhotoIdSelected,
               'photo_id': that.photoIdSelected
            },
            complete: function() {
               console.log("photo: " + that.wpPhotoIdSelected + " deleted from wordpress");
               console.log("photo: " + that.photoIdSelected + " deleted from Adentify");
               jQuery('.ad-library-photo-thumbnail:has(.ad-library-photo-wrapper[data-wp-photo-id="' + that.wpPhotoIdSelected + '"])').remove();
               that.removePhotoSelection(false);
               that.stopLoading('tag-from-library');
            }
         });
      }
      return false;
   },

   /*
    * Setup event handlers
    */
   setupEventHandlers: function() {
      var that = this;

      // hide the modals
      jQuery('#adentify-modal-backdrop, #adentify-modal-backdrop2, #adentify-modal-close, #adentify-modal-close2').click(jQuery.proxy(this.closeModals, this));

      // Close modals on ECHAP
      jQuery('#__wp-uploader-id-2, #__wp-uploader-id-3').keydown(function(e) { if (e.which == 27) that.closeModals(); });

      // switch between the upload's tabs
      jQuery('#upload-file, #file-library').click(jQuery.proxy(this.clickOnUploadTab, this));

      // switch between the tag's tabs
      jQuery('.tag-tab').click(jQuery.proxy(this.clickOnTagTab, this));

      // upload the image
      jQuery('#adentify-uploader-button').click(jQuery.proxy(this.clickOnUploaderButton, this));

      // Add tag
      jQuery('.ad-media-frame-content .photo-overlay').click(jQuery.proxy(this.addTag, this));

      // post tag
      jQuery('.submit-tag-button').click(jQuery.proxy(this.retrieveTagData, this));

      // delete a tag
      jQuery('div').on('click', '.ad-delete-tag', jQuery.proxy(this.removeTag, this));

      // Store the id of the selected photo and enabled the buttons
      jQuery('.ad-library-photo-wrapper').on('click', jQuery.proxy(this.clickOnLibraryPhoto, this));

      // show the tag modal with the selected photo
      jQuery('#ad-tag-from-library').click(jQuery.proxy(this.getPhoto, this));

      // insert a photo in the post editor
      jQuery('#ad-insert-from-library, #ad-insert-after-tag').click(jQuery.proxy(this.insertPhotoInPostEditor, this));

      // "back" button on the tag modal
      jQuery('#ad-back-to-library').click(jQuery.proxy(this.backToMainModal, this));

      // delete a photo
      jQuery('#ad-delete-photo').click(jQuery.proxy(this.clickOnDeletePhoto, this));

      jQuery('#ad-formats').change(this.changeAdFormats);
   },

   /*
    * Other methods
    */
   initTinyEditor: function() {
      new TINY.editor.edit('editor',{
         id:'product-description', // (required) ID of the textarea
         width:340, // (optional) width of the editor
         height:200, // (optional) heightof the editor
         cssclass:'tinyeditor', // (optional) CSS class of the editor
         controlclass:'tinyeditor-control', // (optional) CSS class of the buttons
         rowclass:'tinyeditor-header', // (optional) CSS class of the button rows
         dividerclass:'tinyeditor-divider', // (optional) CSS class of the button diviers
         controls:['bold', 'italic', 'underline', 'strikethrough', '|', 'orderedlist', 'unorderedlist'],
         fonts:['Verdana','Arial','Georgia','Trebuchet MS'],  // (optional) array of fonts to display
         css:'body{background-color:white}', // (optional) attach CSS to the editor
         bodyid:'product-description-editor', // (optional) attach an ID to the editor body
      });
   },

   removePhotoSelection: function(needId) {
      jQuery('.ad-library-photo-wrapper[data-adentify-photo-id=' + this.photoIdSelected +']').removeClass(this.selectedPhotoClassName);
      if (needId === false) {
         this.photoIdSelected = undefined;
         this.wpPhotoIdSelected = undefined;
      }
      jQuery('#ad-insert-from-library, #ad-tag-from-library, #ad-delete-photo').attr('disabled', 'disabled');
   },

   renderModals: function() {
      jQuery('body').append('<div id="adentify-upload-modal"></div>').append('<div id="adentify-tag-modal"></div>');
      jQuery('#adentify-upload-modal').html(jQuery('#adentify-uploader').html());
      jQuery('#adentify-tag-modal').hide().html(jQuery('#adentify-tag-modal-template').html());
      jQuery('#ad-tag-from-library, #ad-insert-from-library, #ad-delete-photo').hide();
      this.stopLoading('uploading-message');
      this.initTinyEditor();
   },

   closeModals: function() {
      jQuery('body').removeClass("ad-modal-open");
      jQuery('#adentify-upload-modal').hide(0, function() {
         jQuery('#ad-uploader-content').show();
      });
      jQuery('#adentify-tag-modal').hide();
      this.removePhotoSelection(false);
      this.resetForms();
      this.removeTempTagsFromDOM(jQuery('.photo-overlay'));
      this.removeTagsFromDOM(jQuery('.photo-overlay'));
      return false;
   },

   backToMainModal: function() {
      this.hideOpenedSelect2();
      jQuery('#adentify-tag-modal').hide();
      jQuery('#ad-uploader-content, #adentify-upload-modal').show();
      jQuery('#__wp-uploader-id-2').focus();
      this.resetForms();
      this.removeTempTagsFromDOM(jQuery('.photo-overlay'));
      this.removeTagsFromDOM(jQuery('.photo-overlay'));
      return false;
   },

   startLoading: function(loader, tagId) {
      switch (loader) {
         case 'tag-from-library':
            jQuery('#ad-tag-from-library-loading').show();
            break;
         case 'uploading-message':
            jQuery('#ad-uploading-message').show();
            break;
         case 'remove-tag':
            jQuery('#ad-remove-tag-loader-' + tagId).show();
            break;
         case 'posting-tag':
         default:
            jQuery('.tag-posting').show();
            break;
      }
   },

   stopLoading: function(loader, tagId) {
      switch (loader) {
         case 'tag-from-library':
            jQuery('#ad-tag-from-library-loading').hide();
            break;
         case 'uploading-message':
            jQuery('#ad-uploading-message').hide();
            break;
         case 'remove-tag':
            jQuery('.ad-remove-tag-loader-' + tagId).hide();
            break;
         case 'posting-tag':
         default:
            jQuery('.tag-posting').hide();
            break;
      }
   },

   appendPhotoToLibrary: function(photo, wp_photo_id) {
      var that = this;
      try {
         var photo = photo;
         that.wpPhotoIdSelected = wp_photo_id;
         var thumbnail = '<div class="ad-library-photo-wrapper" data-wp-photo-id="' + that.wpPhotoIdSelected + '" data-adentify-photo-id="' + photo.id + '" style="background-image: url(' + photo.small_url + ')"></div>';
         var wrapper = '<li class="ad-library-photo-thumbnail">' + thumbnail + '</li>';
         jQuery('#ad-library-list').append(wrapper);
         jQuery('.ad-library-photo-wrapper[data-adentify-photo-id="' + photo.id + '"]').click(function() {
            if (that.currentSelectedPhoto) {
               that.currentSelectedPhoto.removeClass(that.selectedPhotoClassName);
            }
            that.currentSelectedPhoto = jQuery(this);
            that.currentSelectedPhoto.addClass(that.selectedPhotoClassName);
            jQuery('#ad-insert-from-library, #ad-tag-from-library, #ad-delete-photo').removeAttr('disabled');
            that.photoIdSelected = that.currentSelectedPhoto.attr('data-adentify-photo-id');
            that.wpPhotoIdSelected = that.currentSelectedPhoto.attr('data-wp-photo-id');
         });
      } catch(e) {
         console.log("Error appending photo to library: ");
      }
   },

   openPhotoModal: function(data) {
      jQuery('#photo-getting-tagged').remove();
      jQuery('#adentify-upload-modal').hide();
      jQuery('#adentify-tag-modal').show(0, function() {
         jQuery('#tag-product input').first().focus();
      });
      try {
         var that = this;
         var photo = data;
         photo.tags.forEach(function(tag) {
            that.renderTag(jQuery('.photo-overlay'), tag);
         });
         this.setupTagForms();
         var maxHeight = jQuery('#ad-display-photo').height();

         jQuery('#ad-wrapper-tag-photo').append('<img id="photo-getting-tagged" style="max-height:' + maxHeight
         + 'px" class="ad-photo-getting-tagged" data-wp-photo-id="' + this.wpPhotoIdSelected + '" data-adentify-photo-id="' + photo.id
         + '" src="' + photo.large_url + '"/>');
         (photo.large_height > maxHeight) ? jQuery('#ad-wrapper-tag-photo').height(maxHeight) : jQuery('#ad-wrapper-tag-photo').height(photo.large_height);
      } catch(e) {
         console.log("Error: " + data); // TODO gestion erreur
      }
   },

   getPhoto: function(e) {
      var that = this;
      if (!jQuery(e.target).is('[disabled]') && typeof this.photoIdSelected !== 'undefined' && this.photoIdSelected) {
         that.startLoading('tag-from-library');
         jQuery.ajax({
            type: 'GET',
            url: adentifyTagsData.admin_ajax_url,
            dataType: 'json',
            data: {
               'action': 'ad_get_photo',
               'photo_id': that.photoIdSelected
            },
            success: function(data) {
               if (typeof data !== 'undefined')
                  that.openPhotoModal(data);
               else
                  alert('Impossible de recuperer la photo'); // TODO: gestion erreur
               that.removePhotoSelection(true);
            },
            complete: function() {
               that.stopLoading('tag-from-library');
            }
         });
      }
   },

   setupAutocomplete: function(selector, placeholder, formatResult, formatSelection, searchUrl, getUrl, tagFormField,
                               enableCreateSearchChoice, extraQueryParams) {
      enableCreateSearchChoice = enableCreateSearchChoice || true;
      tagFormField = tagFormField || [];
      extraQueryParams = extraQueryParams || [];

      var select2Parameters = {
         placeholder: placeholder,
         minimumInputLength: 1,
         id: function(e) { return typeof e.id !== 'undefined' ? e.id : null; },
         ajax: {
            url: searchUrl,
            dataType: 'json',
            quietMillis: 250,
            crossDomain: true,
            data: function (term, page) {
               var queryParams = {
                  query: term
               };
               jQuery.extend(queryParams, extraQueryParams);

               return queryParams;
            },
            results: function (data, page) {
               return { results: (typeof data.data !== 'undefined' ? data.data : data) };
            },
            cache: true,
               transport: function(params) {
               params.beforeSend = function(request){
                  request.setRequestHeader("Authorization", 'Bearer ' + adentifyTagsData.adentify_api_access_token);
               };
               return jQuery.ajax(params);
            }
         },
         initSelection: function(element, callback) {
            var id = jQuery(element).val();
            if (id !== "") {
               jQuery.ajax(getUrl + id, {
                  dataType: "json"
               }).done(function(data) { callback(data); });
            }
         },
         formatResult: formatResult,
         formatSelection: formatSelection,
         dropdownCssClass: "bigdrop",
         escapeMarkup: function (m) { return m; }
      };
      if (enableCreateSearchChoice) {
         jQuery.extend(select2Parameters, {
            createSearchChoice: function(term) {
               return (selector === '#person-name') ? { id:0 ,'firstname': term } : {id: 0, 'name': term } ;
            },
            createSearchChoicePosition: 'bottom'
         });
      }
      jQuery(selector).select2(select2Parameters).on('select2-selecting', function(e) {
         tagFormField.forEach(function(entry) {
            if (typeof entry.isSelect2 !== 'undefined' && entry.isSelect2) {
               jQuery(entry.fieldSelector).select2('data', e.choice[entry.propertyName]);
            } else
               jQuery(entry.fieldSelector).val(e.choice[entry.propertyName]);
            if (jQuery(entry.fieldSelector).attr('id') == 'product-description')
               jQuery('.tinyeditor iframe').contents().find('#product-description-editor').html(e.choice[entry.propertyName]);
         });
      });
   },

   setupTagForms: function() {
      var that = this;
      // Setup autocomplete with Select2.js
      this.setupAutocomplete('#brand-name', 'Search for a brand', function(item) { return that.genericFormatResult(item); },
         function(item) { return that.genericFormatSelection(item); }, adentifyTagsData.adentify_api_brand_search_url,
         adentifyTagsData.adentify_api_brand_get_url);

      this.setupAutocomplete('#product-name', 'Search for a product', function(item) { return that.genericFormatResult(item, 'medium_url'); },
         function(item) { return that.genericFormatSelection(item); }, adentifyTagsData.adentify_api_product_search_url,
         adentifyTagsData.adentify_api_product_get_url, [
            {
               fieldSelector: '#product-description',
               propertyName: 'description'
            },
            {
               fieldSelector: '#product-url',
               propertyName: 'purchase_url'
            },
            {
               fieldSelector: '#brand-name',
               propertyName: 'brand',
               isSelect2: true
            }
         ], true, { 'p': adentifyTagsData.product_providers });

      this.setupAutocomplete('#venue-name', 'Search for a venue', function(item) { return that.genericFormatResult(item); },
         function(item) { return that.genericFormatSelection(item); }, adentifyTagsData.adentify_api_venue_search_url,
         adentifyTagsData.adentify_api_venue_get_url, [
            {
               fieldSelector: '#venue-description',
               propertyName: 'description'
            },
            {
               fieldSelector: '#venue-url',
               propertyName: 'link'
            }
         ]);

      this.setupAutocomplete('#person-name', 'Search for a person', function(item) { return that.genericFormatResult(item, null, [ 'firstname', 'lastname' ]); },
         function(item) { return that.genericFormatSelection(item, [ 'firstname', 'lastname' ]); }, adentifyTagsData.adentify_api_person_search_url,
         adentifyTagsData.adentify_api_person_get_url, [
            {
               fieldSelector: '#person-url',
               propertyName: 'link'
            }
         ]);
   },

   genericFormatResult: function(item, imageKey, nameKey) {
      imageKey = imageKey || 'medium_logo_url';
      nameKey = nameKey || 'name';
      var markup = '<div class="row-fluid">' +
         (typeof item[imageKey] !== 'undefined' ? '<div class="span2"><img class="small-logo" src="' + item[imageKey] + '" /></div>' : '');
      markup += '<div class="span10">' + (nameKey instanceof Array ? this.implodeObject(item, nameKey) : item[nameKey]);
      if (typeof item.product_provider !== 'undefined') {
         markup += '<span class="providerName">' + item.product_provider.name + '</span>';
      } else {
         markup += '<span class="providerName">AdEntify</span>';
      }
      markup += '</div><div class="clearfix"></div></div></div>';

      return markup;
   },

   genericFormatSelection: function(item, key) {
      key = key || 'name';
      if (key instanceof Array) {
         return this.implodeObject(item, key);
      } else
         return item[key];
   },

   implodeObject: function(object, keys) {
      var implodedString = [];
      keys.forEach(function(key) {
         implodedString.push(object[key]);
      });
      return implodedString.join(' ');
   },

   addTag: function(e) {
      if (jQuery(e.target).hasClass('photo-overlay')) {
         var xPosition = (e.offsetX === undefined ? e.originalEvent.layerX : e.offsetX) / e.currentTarget.clientWidth;
         var yPosition = (e.offsetY === undefined ? e.originalEvent.layerY : e.offsetY) / e.currentTarget.clientHeight;

         // Remove tags aren't persisted
         if (this.tags.length > 0) {
            for (i = 0; i < this.tags.length; i++) {
               if (typeof this.tags[i].temp !== 'undefined')
                  this.tags.splice(i--, 1);
            }
            this.removeTempTagsFromDOM(e.target);
         }

         var tag = {
            'x_position': xPosition,
            'y_position': yPosition,
            'temp': true
         };

         this.currentTagIndex = this.tags.push(tag) - 1;
         this.renderTag(e.target, tag);
      }
   },

   renderTag: function(photoOverlay, tag) {
      jQuery(photoOverlay).find('.tags-container').append('<div class="' + adentifyTagsData.tag_shape +
         ' tag" ' + (typeof tag.temp !== 'undefined' ? 'data-temp-tag="true"' : '') + ' style="left: ' + (tag.x_position * 100) + '%; ' +
         'top: ' + tag.y_position * 100 + '%; margin-left: -15px; margin-top: -15px;">' +
         ((typeof tag.id !== 'undefined') ? '<div class="popover"><div class="popover-inner">' +
         ((typeof tag.title !== 'undefined') ? '<p class="title">' + tag.title + '</p>' : '') +
         ((typeof tag.description !== 'undefined') ? '<p class="tag-description">' + tag.description + '</p>' : '') +
         '<div data-tag-id="' + tag.id + '" class="ad-delete-tag button button-primary button-large media-button-insert">Supprimer le tag</div>' +
         '<div id="ad-remove-tag-loader-' + tag.id + '" class="loading-gif-container ad-delete-loader" style="display: none">' +
         '<div class="loader rotate"><div class="loading-gif"></div></div></div>' +
         '</div>' : '') + '</div></div>');
   },

   removeTempTagsFromDOM: function(photoOverlay) {
      jQuery(photoOverlay).find('.tags-container .tag[data-temp-tag]').remove();
   },

   removeTagsFromDOM: function(photoOverlay) {
      jQuery(photoOverlay).find('div .tag').remove();
   },

   removeTag: function(e) {
      var that = this;
      that.startLoading('remove-tag', e.target.attributes['data-tag-id'].value);
      jQuery.ajax({
         type: 'GET',
         url: adentifyTagsData.admin_ajax_url,
         data: {
            'action': 'ad_remove_tag',
            'tag_id': e.target.attributes['data-tag-id'].value
         },
         complete: function() {
            console.log("tag removed");
            jQuery('div .tag:has([data-tag-id="' + e.target.attributes['data-tag-id'].value + '"])').remove();
            that.stopLoading('remove-tag', e.target.id);
         }
      });
      return(false);
   },

   /*
   * Get value from select2
   *
   * options.array: array of select2 to retrieve with selector name and target object property name
   * options.properties: target object properties
   * options.fail: callback when failed
   * options.success: callback when all good
   * */
   getValuesFromSelect2: function(options) {
      var that = this;
      options.array.forEach(function(item) {
         var value;
         if (typeof item.objectProperty !== 'undefined') {
            var data = jQuery(item.select2Selector).select2('data');
            value = that.implodeObject(data, item.objectProperty);
         } else
            value = jQuery(item.select2Selector).select2('val');

         if (typeof value !== 'undefined' && value && value != "0") {
            options.properties[item.propertyName] = value;
         }
         else if ((value == 0 || value == '0') && typeof item.createIfNotExists) {
            if (typeof options.properties.extraData === 'undefined')
               options.properties.extraData = {};

            var data = jQuery(item.select2Selector).select2('data');
            options.properties.extraData[item.propertyName] = data;
         }
         else {
            options.fail();
            return;
         }
      });

      options.success();
   },

   retrieveTagData: function(e) {
      e.preventDefault();
      var that = this;

      if (typeof this.currentTagIndex !== 'undefined' && typeof this.tags[this.currentTagIndex] !== 'undefined') {
         // Get data from form
         var tagForm = jQuery('#' + jQuery(e.target).context.form.id).serializeObject();
         var tag = this.tags[this.currentTagIndex];

         var properties = {
            'type': jQuery(e.target).context.form.attributes['data-tag-type'].value,
            'photo': jQuery('#photo-getting-tagged').attr('data-adentify-photo-id')
         };

         if (typeof tagForm.description !== 'undefined')
            properties.description = tagForm.description;
         if (typeof tagForm.url !== 'undefined')
            properties.link = tagForm.url;

         switch (jQuery(e.target).context.form.attributes['data-tag-type'].value) {
            case 'place':
               this.getValuesFromSelect2({
                  array: [
                     {
                        propertyName: 'title',
                        select2Selector: '#venue-name',
                        objectProperty: [ 'name' ]
                     },
                     {
                        propertyName: 'venue',
                        select2Selector: '#venue-name',
                        createIfNotExists: true
                     }
                  ],
                  properties: properties,
                  success: function() {
                     that.postTag(jQuery.extend(tag, properties));
                  },
                  fail: function () {
                     alert('Please select a venue before adding'); // TODO: gestion erreur
                  }
               });
               break;
            case 'product':
               this.getValuesFromSelect2({
                  array: [
                     {
                        propertyName: 'product',
                        select2Selector: '#product-name'
                     },
                     {
                        propertyName: 'title',
                        select2Selector: '#product-name',
                        objectProperty: [ 'name' ]
                     },
                     {
                        propertyName: 'brand',
                        select2Selector: '#brand-name'
                     }
                  ],
                  properties: properties,
                  success: function() {
                     that.postTag(jQuery.extend(tag, properties));
                  },
                  fail: function () {
                     alert('Please select a product and a brand before adding'); // TODO: gestion erreur
                  }
               });
               break;
            case 'person':
               this.getValuesFromSelect2({
                  array: [
                     {
                        propertyName: 'title',
                        select2Selector: '#person-name',
                        objectProperty: [ 'firstname', 'lastname' ]
                     },
                     {
                        propertyName: 'person',
                        select2Selector: '#person-name'
                     }
                  ],
                  properties: properties,
                  success: function() {
                     that.postTag(jQuery.extend(tag, properties));
                  },
                  fail: function () {
                     alert('Please select a person before adding'); // TODO: gestion erreur
                  }
               });
               break;
            case 'advertising':
               tag.tagInfo = {
                  dimensions: {
                     width: tagForm.width,
                     height: tagForm.height
                  },
                  code: tagForm.code
               };
               tag.type = 'advertising';
               tag.title = 'advertising';

               jQuery.extend(tag, properties);
               that.postTag(tag);
               break;
         }
      } else {
         alert('Vous devez tout d\'abord ajouter un tag sur l\'image');
         // TODO: gestion erreur
      }
   },

   resetForms: function() {
      jQuery('.ad-tag-frame-content textarea, .ad-tag-frame-content input').each(function(index, element) {
         jQuery(element).val('');
      });
      jQuery('#product-name, #venue-name, #brand-name, #person-name').select2('data', null);
      jQuery('.tinyeditor iframe').contents().find('#product-description-editor').html('');
   },

   postTag: function(tag) {
      var that = this;
      jQuery('.submit-tag').hide();
      that.startLoading('posting-tag');
      jQuery.ajax({
         type: 'POST',
         url: adentifyTagsData.admin_ajax_url,
         data: {
            'action': 'ad_tag',
            'tag': tag
         },
         success: function(data) {
            that.resetForms();
            that.renderTag(jQuery('.photo-overlay'), JSON.parse(data));
            that.removeTempTagsFromDOM(jQuery('.photo-overlay'));
            // TODO: append popover to tag
         },
         complete: function() {
            jQuery('.submit-tag').show();
            that.stopLoading('posting-tag');
            that.currentTagIndex = null;
         }
      });
   },

   insertPhotoInPostEditor: function(e) {
      if (!jQuery(e.target).is('[disabled]')) {
         if (typeof this.photoIdSelected !== "undefined" && this.photoIdSelected) {
            window.send_to_editor('[adentify=' + this.photoIdSelected + ']');
            this.closeModals();
         } else
            console.log("you have to select a photo"); // TODO: gestion erreur
      }
      return false;
   },

   changeAdFormats: function() {
      if (jQuery(this).val() !== 'custom') {
         var format = jQuery(this).val().split('-');
         jQuery('#ad-width').val(format[0]);
         jQuery('#ad-height').val(format[1]);
      } else {
         jQuery('#ad-width').val('');
         jQuery('#ad-height').val('');
      }
   },

   /*
    * Init
    * */
   init: function() {
      var that = this;
      // Listen click event on AdEntify button
      var adentifyButton = jQuery('#adentify-upload-img');
      if (adentifyButton.length) {
         adentifyButton.click(function() {
            that.clickOnAdEntifyButton();
            return false;
         });
      }
   }
};

jQuery(document).ready(function(jQuery) {
   // Helpers
   jQuery.fn.extend({
      serializeObject: function()
      {
         var o = {};
         var a = this.serializeArray();
         jQuery.each(a, function() {
            if (o[this.name] !== undefined) {
               if (!o[this.name].push) {
                  o[this.name] = [o[this.name]];
               }
               o[this.name].push(this.value || '');
            } else {
               o[this.name] = this.value || '';
            }
         });
         return o;
      }
   });

   AdEntifyBO.init();
});
