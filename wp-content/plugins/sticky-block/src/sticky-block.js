/**
* @preserve Sticky Block 1.11 | @senff | GPL2 Licensed
*/

(function ($) {

    // === Initialization ===

        $('.wp-block-senff-sticky-block').each(function(sb) {
            var topSpace = $(this).attr('data-topspace');
            var cfa = $(this).attr('data-cfa');        
            var minWidth = $(this).attr('data-minwidth');
            var maxWidth = $(this).attr('data-maxwidth');
            var pushUp = $(this).attr('data-pushup');
            var zIndex = $(this).attr('data-zindex');

            $(this).addClass('senff-sticky-block-'+sb).addClass('sticky-block-original-'+sb).addClass('block-is-not-sticky');

            // === Raise flag is the number of pushup elements is NOT 1 ===
            if ((pushUp) && ($(pushUp).length < 1)) {
                // There are no elements on the page with the called selector for the Push-up Element.
                console.warn('STICKY BLOCK DEBUGGER: you selected a Push-up element "'+pushUp+'" but there is no element on the page with that class/ID.')
                pushUp = '';
            } else if ((pushUp) && ($(pushUp).length > 1)) {
              // You can't use more than one element to push up the sticky element.
              // Make sure that you use a selector that applies to only ONE SINGLE element on the page.
                console.warn('STICKY BLOCK DEBUGGER: there are '+$(pushUp).length+' elements on the page with the selector/class/ID you selected for the push-up element ("'+pushUp+'"). You can select only ONE element to push the sticky element up.');
                pushUp = '';
            }

            // === Create a placeholder with the same CSS styles ===
            var orgAssignedStyles = cssStyles($(this)); // All original element styles, assigned by CSS.
            var orgInlineStyles = $('.sticky-block-original-'+sb).attr('style'); // All original element styles, inline.
            if (orgInlineStyles == null) {
                orgInlineStyles = '';
            }
            createPlaceholder(sb);

            checkElement = setInterval(function(){
                stickIt(
                    sb,
                    topSpace,
                    cfa,
                    minWidth,
                    maxWidth,
                    pushUp,
                    zIndex,
                    orgAssignedStyles,
                    orgInlineStyles
                )
            },10);            

        });

    // Helper function: get the important CSS rules from an element
    function cssStyles(el) {
        o = {};
        o['display'] = el.css('display');
        o['float'] = el.css('float');
        o['flex'] = el.css('flex');
        o['box-sizing'] = el.css('box-sizing');
        o['clear'] = el.css('clear');
        o['overflow'] = el.css('overflow');
        o['transform'] = el.css('transform');
        return o;
    }

    // Helper function: creates the placeholder
    function createPlaceholder(sb) {
        $('.sticky-block-original-'+sb).wrap('<div class="sticky-block-wrapper sticky-block-wrapper-'+sb+'"></div>').addClass('sticky-block-active-'+sb).before('<div class="sticky-block-placeholder sticky-block-placeholder-'+sb+'" style="width:0; height:0; margin:0; padding:0; visibility:hidden;"></div>');
    }

    // Helper function: this is where the magic happens
    function stickIt(sb,topSpace,cfa,minWidth,maxWidth,pushUp,zIndex,orgAssignedStyles,orgInlineStyles) {

        // We need to check the position of the ACTIVE element.
        // This is the original one when it's not sticky, but when it's sticky, it's the placeholder.
        var $activeElement = $('.sticky-block-active-'+sb);
        var $originalElement = $('.sticky-block-original-'+sb);

        var orgElementPos = $activeElement.offset();
        orgElementTop = orgElementPos.top;  // Position of the element related to the top of the PAGE

        if(pushUp) {
          var pushElementPos = $(pushUp).offset();
          pushElementTop = pushElementPos.top;    // Position of the pushup element related to the top of the PAGE
        }         

        // Calculating actual viewport width
        var e = window, a = 'inner';
        if (!('innerWidth' in window )) {
            a = 'client';
            e = document.documentElement || document.body;
        }
        viewport = e[ a+'Width' ];

        // Is there an admin bar and do we need to consider it
        if ((cfa=="true") && $('body').hasClass('admin-bar') && (viewport > 600)) {
            // below 600, the adminbar is not fixed
            adminBarHeight = $('#wpadminbar').height();
        } else {
            adminBarHeight = 0;
        }

        if (($(window).scrollTop() >= (orgElementTop - topSpace - adminBarHeight)) && (viewport >= minWidth) && (viewport <= maxWidth)) {

            // We've scrolled PAST the original position; this is where we need to make the element sticky.

            // Placeholder element should always have same left position as original element.     
            // The sticky element will NOT have a TOP or the LEFT margin. This is because the 
            // left/top reference point of the original element does not consider the margin. 
            // So, we're checking the left/top point of the actual original element and then 
            // use that position for the sticky element.

            // LEFT POSITION
                coordsOrgElement = $activeElement.offset();
                leftOrgElement = coordsOrgElement.left;  // This is the position REGARDLESS of the margin. 

            // WIDTH/HEIGHT
            // The placeholder needs to have the width and height of the original element, 
            // WITHOUT the margins but WITH the padding and borders.
            // Whatever margins the original has, the placeholder needs to have that too.
            // TO DO: get actual CSS rule for the width, not the calculated width

            // BUT! Now these are BLOCKS, the placeholder width can just be 100% (see down below)

                widthPlaceholder = $activeElement[0].getBoundingClientRect().width;
                if (!widthPlaceholder) {
                    widthPlaceholder = $activeElement.css('width');  // FALLBACK for subpixels
                }

                heightPlaceholder = $activeElement[0].getBoundingClientRect().height;
                if (!heightPlaceholder) {
                    heightPlaceholder = $activeElement.css('height');  // FALLBACK for subpixels
                }

            // WIDTH/HEIGHT OF STICKY ELEMENT
            // The original element though, needs to have the inner width and height of the 
            // original (non-sticky) element.
            // No padding, no borders, because that will be applied later anyway, regardless 
            // of box-sizing
                // widthSticky = $originalElement.css('width');
                widthSticky = $activeElement.css('width');
                if(widthSticky == '0px') {
                    widthSticky = ($originalElement[0].getBoundingClientRect().width);
                }
                heightSticky = $originalElement.height();

            // PADDING
            // If padding is percentages, convert to pixels when it becomes sticky
            // Just a leftover from the old method. We will not use padding for the placeholder
                paddingOrgElement = [
                    $originalElement.css('padding-top'), 
                    $originalElement.css('padding-right'), 
                    $originalElement.css('padding-bottom'),
                    $originalElement.css('padding-left')
                ];
                paddingSticky = paddingOrgElement[0] + ' ' + paddingOrgElement[1] + ' ' + paddingOrgElement[2] + ' ' + paddingOrgElement[3];

            // MARGIN
                marginOrgElement = [
                    $activeElement.css('margin-top'), 
                    $activeElement.css('margin-right'), 
                    $activeElement.css('margin-bottom'),
                    $activeElement.css('margin-left')
                ];
                marginPlaceholder = marginOrgElement[0] + ' ' + marginOrgElement[1] + ' ' + marginOrgElement[2] + ' ' + marginOrgElement[3];

            // OTHER ELEMENTS
            // if original has float, display, etc., we need to assign that to the placeholder
            // Though not as important as the width/height/margin/padding

            var assignedStyles = '';
            for (var importantStyle in orgAssignedStyles) {
                if (orgAssignedStyles[importantStyle] == 'inline') {
                    assignedStyles += importantStyle+':inline-block; '; 
                } else {
                    assignedStyles += importantStyle+':'+orgAssignedStyles[importantStyle]+'; '; 
                }
            }

            // If scrolled position = pushup-element (top coordinate) - space between top and element - element height - admin bar
            // In other words, if the pushup element hits the bottom of the sticky element
            var elementHeight = $originalElement.outerHeight();
            if (pushUp && ($(window).scrollTop() > (pushElementTop-topSpace-elementHeight-adminBarHeight))) {
                stickyTopMargin = (pushElementTop-topSpace-elementHeight-$(window).scrollTop());
            } else {
                stickyTopMargin = adminBarHeight;
            }
            stickyTopMargin = stickyTopMargin - 1;

            assignedStyles += 'width:100%; height:'+heightPlaceholder+'px; margin:'+marginPlaceholder+';';

            $originalElement.removeClass('sticky-block-active-'+sb).removeClass('block-is-not-sticky').addClass('element-is-sticky').addClass('block-is-sticky').css('position','fixed').css('left',leftOrgElement+'px').css('top',topSpace+'px').css('width',widthSticky).css('margin-left',0).css('padding',paddingSticky).css('margin-top',stickyTopMargin).css('z-index',zIndex);
            if(!$('.sticky-block-placeholder-'+sb).hasClass('sticky-block-active-'+sb)) {
                $('.sticky-block-placeholder-'+sb).addClass('sticky-block-active-'+sb).attr('style',assignedStyles);
            }

        } else {
            
            // We've NOT scrolled past the original position; the element should be unsticky again
            $originalElement.addClass('sticky-block-active-'+sb).removeClass('element-is-sticky').removeClass('block-is-sticky').addClass('block-is-not-sticky').attr('style',orgInlineStyles);
            if($('.sticky-block-placeholder-'+sb).hasClass('sticky-block-active-'+sb)) {
                $('.sticky-block-placeholder-'+sb).removeClass('sticky-block-active-'+sb).removeAttr('style').css('width','0').css('height','0').css('margin','0').css('padding','0');
            }

        }

    }

}(jQuery));

