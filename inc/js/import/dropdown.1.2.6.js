!function(t){t.fn.scrollTo=function(i){return t(this).scrollTop(t(this).scrollTop()-t(this).offset().top+t(i).offset().top),this},t.fn.dropdown=function(i){var o={inDuration:300,outDuration:225,constrainWidth:!1,hover:!1,gutter:0,belowOrigin:!1,alignment:"left",stopPropagation:!1};return"open"===i?(this.each(function(){t(this).trigger("open")}),!1):"close"===i?(this.each(function(){t(this).trigger("close")}),!1):void this.each(function(){function n(){void 0!==r.data("induration")&&(s.inDuration=r.data("induration")),void 0!==r.data("outduration")&&(s.outDuration=r.data("outduration")),void 0!==r.data("constrainwidth")&&(s.constrainWidth=r.data("constrainwidth")),void 0!==r.data("hover")&&(s.hover=r.data("hover")),void 0!==r.data("gutter")&&(s.gutter=r.data("gutter")),void 0!==r.data("beloworigin")&&(s.belowOrigin=r.data("beloworigin")),void 0!==r.data("alignment")&&(s.alignment=r.data("alignment")),void 0!==r.data("stoppropagation")&&(s.stopPropagation=r.data("stoppropagation"))}function e(i){"focus"===i&&(d=!0),n(),c.addClass("is-active"),r.addClass("is-active"),!0===s.constrainWidth?c.css("width",r.outerWidth()):c.css("white-space","nowrap");var o=window.innerHeight,e=r.innerHeight(),u=r.offset().left,l=r.offset().top-t(window).scrollTop(),h=s.alignment,g=0,f=0,p=0;!0===s.belowOrigin&&(p=e);var v=0,w=0,m=r.parent();if(m.is("body")||(m[0].scrollHeight>m[0].clientHeight&&(v=m[0].scrollTop),m[0].scrollWidth>m[0].clientWidth&&(w=m[0].scrollLeft)),u+c.innerWidth()>t(window).width()?h="right":u-c.innerWidth()+r.innerWidth()<0&&(h="left"),l+c.innerHeight()>o)if(l+e-c.innerHeight()<0){var b=o-l-p;c.css("max-height",b)}else p||(p+=e),p-=c.innerHeight();if("left"===h)g=s.gutter,f=r.position().left+g;else if("right"===h){var W=r.position().left+r.outerWidth()-c.outerWidth();g=-s.gutter,f=W+g}c.css({position:"absolute",top:r.position().top+p+v,left:f+w}),c.stop(!0,!0).css("opacity",0).slideDown({queue:!1,duration:s.inDuration,easing:"easeOutCubic",complete:function(){t(this).css("height","")}}).animate({opacity:1},{queue:!1,duration:s.inDuration,easing:"easeOutSine"}),t(document).bind("click."+c.attr("id")+" touchstart."+c.attr("id"),function(i){c.is(i.target)||r.is(i.target)||r.find(i.target).length||(a(),t(document).unbind("click."+c.attr("id")+" touchstart."+c.attr("id")))})}function a(){d=!1,c.fadeOut(s.outDuration),c.removeClass("is-active"),r.removeClass("is-active"),t(document).unbind("click."+c.attr("id")+" touchstart."+c.attr("id")),setTimeout(function(){c.css("max-height","")},s.outDuration)}var r=t(this),s=t.extend({},o,i),d=!1,c=t("#"+r.attr("data-activates"));if(n(),r.after(c),s.hover){var u=!1;r.unbind("click."+r.attr("id")),r.on("mouseenter",function(t){!1===u&&(e(),u=!0)}),r.on("mouseleave",function(i){var o=i.toElement||i.relatedTarget;t(o).closest(".dropdown__content").is(c)||(c.stop(!0,!0),a(),u=!1)}),c.on("mouseleave",function(i){var o=i.toElement||i.relatedTarget;t(o).closest(".dropdown__title").is(r)||(c.stop(!0,!0),a(),u=!1)})}else r.unbind("click."+r.attr("id")),r.bind("click."+r.attr("id"),function(i){d||(r[0]!=i.currentTarget||r.hasClass("is-active")||0!==t(i.target).closest(".dropdown__content").length?r.hasClass("is-active")&&(a(),t(document).unbind("click."+c.attr("id")+" touchstart."+c.attr("id"))):(i.preventDefault(),s.stopPropagation&&i.stopPropagation(),e("click")))});r.on("open",function(t,i){e(i)}),r.on("close",a)})},t(document).ready(function(){t(".js-dropdown-toggle").dropdown()})}(jQuery);