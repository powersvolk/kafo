!function(i){i(document).ready(function(){i(document).on("reset",function(e){var t=i(e.target);t.is("form")&&t.find("select.initialized").each(function(){var i=t.find("option[selected]").text();t.siblings("input.select-dropdown").val(i)})})}),i.fn.material_select=function(e){function t(i,e,t){var n=i.indexOf(e),s=-1===n;return s?i.push(e):i.splice(n,1),t.siblings("ul.dropdown__content").find("li").eq(e).toggleClass("active"),t.find("option").eq(e).prop("selected",s),o(i,t),s}function o(i,e){for(var t="",o=0,n=i.length;o<n;o++){var s=e.find("option").eq(i[o]).text();t+=0===o?s:", "+s}""===t&&(t=e.find("option:disabled").eq(0).text()),e.siblings("input.select-dropdown").val(t)}i(this).each(function(){var o=i(this);if(!o.hasClass("browser-default")){var n=!!o.attr("multiple"),s=o.data("select-id");if(s&&(o.parent().find("span.caret").remove(),o.parent().find("input").remove(),o.unwrap(),i("ul#select-options-"+s).remove()),"destroy"===e)return void o.data("select-id",null).removeClass("initialized");var l=Materialize.guid();o.data("select-id",l);var a=i('<div class="select-wrapper"></div>');a.addClass(o.attr("class"));var r=i('<ul id="select-options-'+l+'" class="dropdown__content select-dropdown '+(n?"multiple-select-dropdown":"")+'"></ul>'),d=o.children("option, optgroup"),c=[],p=!1,f=o.find("option:selected").html()||o.find("option:first").html()||"",h=function(e,t,o){var n=t.is(":disabled")?"disabled ":"",s="optgroup-option"===o?"optgroup-option ":"",l=t.data("icon"),a=t.attr("class");if(l){var d="";return a&&(d=' class="'+a+'"'),"multiple"===o?r.append(i('<li class="dropdown__option '+n+'"><img alt="" src="'+l+'"'+d+'><span class="form-check form-check--dropdown"><input type="checkbox" class="form-check__input"'+n+'/><label class="form-check__label">'+t.html()+"</label></span></li>")):r.append(i('<li class="dropdown__option '+n+s+'"><img alt="" src="'+l+'"'+d+'><span class="dropdown__option-link">'+t.html()+"</span></li>")),!0}"multiple"===o?r.append(i('<li class="dropdown__option '+n+'"><span class="form-check form-check--dropdown"><input type="checkbox" class="form-check__input"'+n+'/><label class="form-check__label">'+t.html()+"</label></span></li>")):r.append(i('<li class="dropdown__option '+n+s+'"><span class="dropdown__option-link">'+t.html()+"</span></li>"))};d.length&&d.each(function(){if(i(this).is("option"))n?h(0,i(this),"multiple"):h(0,i(this));else if(i(this).is("optgroup")){var e=i(this).children("option");r.append(i('<li class="dropdown__option optgroup"><span class="dropdown__option-link">'+i(this).attr("label")+"</span></li>")),e.each(function(){h(0,i(this),"optgroup-option")})}}),r.find("li:not(.optgroup)").each(function(s){i(this).click(function(l){if(!i(this).hasClass("disabled")&&!i(this).hasClass("optgroup")){var a=!0;n?(i('input[type="checkbox"]',this).prop("checked",function(i,e){return!e}),a=t(c,i(this).index(),o),g.trigger("focus")):(r.find("li").removeClass("active"),i(this).toggleClass("active"),g.val(i(this).text())),w(r,i(this)),o.find("option").eq(s).prop("selected",a),o.trigger("change"),void 0!==e&&e()}l.stopPropagation()})}),o.wrap(a);var u=i('<span class="caret">&#9660;</span>');o.is(":disabled")&&u.addClass("disabled");var v=f.replace(/"/g,"&quot;"),g=i('<input type="text" class="select-dropdown" readonly="true" '+(o.is(":disabled")?"disabled":"")+' data-activates="select-options-'+l+'" value="'+v+'"/>');o.before(g),g.before(u),g.after(r),o.is(":disabled")||g.dropdown({hover:!1,closeOnClick:!1}),o.attr("tabindex")&&i(g[0]).attr("tabindex",o.attr("tabindex")),o.addClass("initialized"),g.on({focus:function(){if(i("ul.select-dropdown").not(r[0]).is(":visible")&&i("input.select-dropdown").trigger("close"),!r.is(":visible")){i(this).trigger("open",["focus"]);var e=i(this).val();n&&e.indexOf(",")>=0&&(e=e.split(",")[0]);var t=r.find("li").filter(function(){return i(this).text().toLowerCase()===e.toLowerCase()})[0];w(r,t,!0)}},click:function(i){i.stopPropagation()}}),g.on("blur",function(){n||i(this).trigger("close"),r.find("li.selected").removeClass("selected")}),r.hover(function(){p=!0},function(){p=!1}),i(window).on({click:function(){n&&(p||g.trigger("close"))}}),n&&o.find("option:selected:not(:disabled)").each(function(){var e=i(this).index();t(c,e,o),r.find("li").eq(e).find(":checkbox").prop("checked",!0)});var w=function(e,t,o){if(t){e.find("li.selected").removeClass("selected");var s=i(t);s.addClass("selected"),n&&!o||r.scrollTo(s)}},b=[],m=function(e){if(9==e.which)return void g.trigger("close");if(40==e.which&&!r.is(":visible"))return void g.trigger("open");if(13!=e.which||r.is(":visible")){e.preventDefault();var t=String.fromCharCode(e.which).toLowerCase(),o=[9,13,27,38,40];if(t&&-1===o.indexOf(e.which)){b.push(t);var s=b.join(""),l=r.find("li").filter(function(){return 0===i(this).text().toLowerCase().indexOf(s)})[0];l&&w(r,l)}if(13==e.which){var a=r.find("li.selected:not(.disabled)")[0];a&&(i(a).trigger("click"),n||g.trigger("close"))}40==e.which&&(l=r.find("li.selected").length?r.find("li.selected").next("li:not(.disabled)")[0]:r.find("li:not(.disabled)")[0],w(r,l)),27==e.which&&g.trigger("close"),38==e.which&&(l=r.find("li.selected").prev("li:not(.disabled)")[0])&&w(r,l),setTimeout(function(){b=[]},1e3)}};g.on("keydown",m)}})}}(jQuery);