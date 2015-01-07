/**
 * Created by pierrickmartos on 05/11/14.
 */
var AdEntify = {
   setupTagsBehavior: function() {
      jQuery('.adentify-container[data-tags-visibility="visible-on-hover"]').hover(function() {
         jQuery(this).find('.tags').fadeIn();
      }, function() {
         jQuery(this).find('.tags').fadeOut();
      });
   },

   setupEventHandlers: function() {
      var that = this;
      var photoEnterTime = {};
      var tagEnterTime = {};
      jQuery('.adentify-container').hover(function() {
         photoEnterTime[jQuery(this).attr('data-photo-id')] = Date.now();
         that.postAnalytic('hover', 'photo', null, jQuery(this).attr('data-photo-id'));
      }, function() {
         if (photoEnterTime[jQuery(this).attr('data-photo-id')]) {
            var interactionTime = Date.now() - photoEnterTime[jQuery(this).attr('data-photo-id')];
            if (interactionTime > 200)
               that.postAnalytic('interaction', 'photo', null, jQuery(this).attr('data-photo-id'), null, interactionTime)
         }
      });
      jQuery('.adentify-container .tag').hover(function() {
         tagEnterTime[jQuery(this).attr('data-tag-id')] = Date.now();
         that.postAnalytic('hover', 'tag', jQuery(this).attr('data-tag-id'), null);
      }, function() {
         if (tagEnterTime[jQuery(this).attr('data-tag-id')]) {
            var interactionTime = Date.now() - tagEnterTime[jQuery(this).attr('data-tag-id')];
            if (interactionTime > 200)
               that.postAnalytic('interaction', 'tag', jQuery(this).attr('data-tag-id'), null, null, interactionTime)
         }
      });
      jQuery('.adentify-container .tag a').click(function() {
         var jQuerytag = jQuery(this).parents('.tag');
         if (jQuerytag.length) {
            that.postAnalytic('click', 'tag', jQuerytag.attr('data-tag-id'), null, jQuery(this).attr('href'));
         }
      });
   },

   postAnalytic: function(action, element, tag, photo, link, actionValue) {
      var analytic = {
         'platform': 'wordpress',
         'element': element,
         'action': action,
         'sourceUrl': window.location.href
      };
      if (tag)
         analytic.tag = tag;
      if (photo)
         analytic.photo = photo;
      if (link)
         analytic.link = link;
      if (actionValue)
         analytic.actionValue = actionValue;

      jQuery.ajax({
         type: 'POST',
         url: adentifyTagsData.admin_ajax_url,
         data: {
            'action': 'ad_analytics',
            'analytic': analytic
         }
      });
   },

   changePopoverPos: function(that, vw) {
      var deferreds = [];
      var i = 0;

      // Create a deferred for all images
      jQuery(that).find('img').each(function() {
         deferreds.push(new jQuery.Deferred());
      });

      // When image is loaded, resolve the next deferred
      jQuery(that).find('img').load(function() {
         if (deferreds.length != 0)
            deferreds[i].resolve();
         i++;
      }).each(function() {
         if(this.complete)
            jQuery(this).load();
      });

      // When all deferreds are done (all images loaded) do some stuff
      jQuery.when.apply(null, deferreds).done(function() {
         if (that.parentsUntil('.ad-post-container', '.adentify-container').attr('data-tags-visibility') == 'visible-on-hover')
            jQuery('.tags').css('display', 'block');
         that.css('display', 'block');
         if (vw > 1400)
            that.css({'margin-left': - that.find('.popover-inner').outerWidth() / 2}).css('display', 'none');
         else {
            var popoverInnerWidth = that.find('.popover-inner').outerWidth(true);
            var marginLeft = (jQuery('.tags').outerWidth(true) / 2) - (that.parent().position().left - 15 + that.find('.popover-inner').outerWidth(true) / 2);
            that.css({'margin-left': marginLeft + 'px', 'width': popoverInnerWidth + 'px'}).css('display', 'none');
         }
         if (that.parentsUntil('.ad-post-container', '.adentify-container').attr('data-tags-visibility') == 'visible-on-hover')
            jQuery('.tags').css('display', 'none');
      });
   },

   changeAllPopoverPos: function(vw) {
      var that = this;
      jQuery('.adentify-container .popover').each(function() {
         that.changePopoverPos(jQuery(this), vw);
      });
   },

   init: function() {
      var that = this;
      this.setupTagsBehavior();
      this.setupEventHandlers();
      jQuery('.adentify-container').each(function() {
         that.postAnalytic('view', 'photo', null, jQuery(this).attr('data-photo-id'));
      });
      that.changeAllPopoverPos(jQuery(window).width());
      if (!Date.now) {
         Date.now = function() { return new Date().getTime(); };
      }
   }
};

jQuery(document).ready(function() {
   AdEntify.init();
});
