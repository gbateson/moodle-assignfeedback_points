// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod/assign/feedback/points/awardpoints.js
 *
 * @package    mod-assign
 * @subpackage feedback
 * @copyright  2015 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// make sure we have a PTS object
if (typeof(window.PTS)=="undefined") {
    window.PTS = {};
}

/**
 * set_feedback_visibility
 *
 * @return void
 */
PTS.set_feedback_visibility = function() {
    var feedback = $("#feedback");
    var display = (feedback.html() ? "initial" : "none");
    feedback.parents("div.fitem").css("display", display);
}

/**
 * get_input_userid
 *
 * @param object input
 * @return integer the user id if one is found, otherwise 0;
 */
PTS.get_input_userid = function(input) {
    var type = input.prop("type");
    if (type=="checkbox") {
        var regexp = new RegExp("id_.*_([0-9]+)");
        return input.prop("id").replace(regexp, "$1");
    }
    if (type=="radio") {
        return input.val();
    }
    return 0;
}

/**
 * set_awardto_xy
 *
 * @return void
 */
PTS.set_awardto_xy = function(span, x, y) {
    if (typeof(x)=="undefined") {
        x = $(span).css("left");
    }
    if (typeof(y)=="undefined") {
        y = $(span).css("top")
    }
    $(span).find("input[type=checkbox],input[type=radio]").each(function(){
        var userid = PTS.get_input_userid($(this));
        if (userid) {
            $("#id_awardtox_" + userid).val(x);
            $("#id_awardtoy_" + userid).val(y);
        }
    });
}

/**
 * clear_map_action
 *
 * @return void
 */
PTS.clear_map_action = function() {
    var input = $("#id_mapaction_none");
    if (input.length && input.prop("checked")==false) {
        input.prop("checked", true);
        input.parent().addClass("checked");
        input.parent().siblings().removeClass("checked");
    }
}

/**
 * do_map_action
 *
 * @param object input
 * @return void
 */
PTS.do_map_action = function(input) {
    var fnc = "do_map_" + input.val();
    if (PTS[fnc]) {
        PTS[fnc]();
    }
}

/**
 * do_map_reset
 *
 * @return void
 */
PTS.do_map_reset = function() {

    // convert spans to relative position
    $(PTS.user_container + " span").each(function(){
        $(this).find("input[type=checkbox],input[type=radio]").each(function(){
            var userid = PTS.get_input_userid($(this));
            if (userid) {
                $("#id_awardtox_" + userid).val("");
                $("#id_awardtoy_" + userid).val("");
                $(this).parent().css({"position" : "relative", "left" : "0px", "top" : "0px"});
            }
        });
    });

    // convert spans to absolute position
    PTS.set_span_position($(PTS.user_container + " span"));

    // resize the user-map
    PTS.do_map_resize();
}

/**
 * do_map_cleanup
 *
 * @return void
 */
PTS.do_map_cleanup = function() {
    var separate = false;
    var grid = { x : $("#id_userwidth").val(),
                 y : $("#id_userheight").val()}
    $(PTS.user_container + " span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var css = {};
            var p = $(this).position();
            var x = (p.left % grid.x);
            var y = (p.top  % grid.y);
            if (x) {
                if (x < 0) {
                    css.left = "+=" + x;
                } else if (x <= (grid.x / 2)) {
                    css.left = "-=" + x; // i.e. shift this span left
                } else {
                    css.left = "+=" + (grid.x - x); // right
                }
            }
            if (y) {
                if (y < 0) {
                    css.top = "+=" + y;
                } else if (y <= (grid.y / 2)) {
                    css.top = "-=" + y; // i.e. shift this span up
                } else {
                    css.top = "+=" + (grid.y - y); // down
                }
            }
            if (x || y) {
                PTS.set_awardto_xy($(this, p.left + x, p.top + y));
                $(this).animate(css, PTS.cleanup.duration);
                PTS.update_map = true;
                separate = true;
            }
        }
    });

    if (separate) {
        // ensure users are separate (and force map resize)
        PTS.do_map_separate(true);
    } else {
        PTS.clear_map_action();
    }
}

/**
 * get_sorted_keys
 *
 * @param object obj
 * @return void
 */
PTS.get_sorted_keys = function (obj) {
    if (Object.keys) {
        var keys = Object.keys(obj);
    } else {
        var keys = [];
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                keys.push(key);
            }
        }
    }
    return keys.sort();
}

/**
 * do_map_separate
 *
 * @param boolean resize
 * @return void
 */
PTS.do_map_separate = function(resize) {

    if (typeof(resize)=="undefined") {
        resize = false;
    }

    // create array of SPAN elements indexed by:
    //   [o] : number of overlaps
    //   [z] : z-index (CSS layer)
    //   [y] : y-coordinate
    //   [x] : x-coordinate
    var spans = [];
    $(PTS.user_container + " span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            var x = parseInt(p.left);
            var y = parseInt(p.top);
            var z = $(this).zIndex();
            var o = 0;
            var x_max = (x + $(this).outerWidth(true));
            var y_max = (y + $(this).outerHeight(true));
            $(this).siblings().each(function(){
                if ($(this).hasClass("ui-draggable")) {
                    var p = $(this).position();
                    if ((p.left + $(this).outerWidth(true)) > x && p.left < x_max) {
                        if ((p.top + $(this).outerHeight(true)) > y && p.top < y_max) {
                            o++;
                        }
                    }
                }
            });
            if (! spans.hasOwnProperty(o)) {
                spans[o] = [];
            }
            if (! spans[o].hasOwnProperty(z)) {
                spans[o][z] = [];
            }
            if (! spans[o][z].hasOwnProperty(y)) {
                spans[o][z][y] = [];
            }
            if (! spans[o][z][y].hasOwnProperty(x)) {
                spans[o][z][y][x] = [];
            }
            // usually there will only be one span
            // at any given (x, y) coordinate, but
            // to be robust we allow for multiple spans
            spans[o][z][y][x].push(this);
        }
    });

    // setup the available positions
    PTS.positions.setup();

    var o_values = PTS.get_sorted_keys(spans);
    for (var o_index in o_values) {
        var o = o_values[o_index];

        var z_values = PTS.get_sorted_keys(spans[o]);
        for (var z_index in z_values) {
            var z = z_values[z_index];

            var y_values = PTS.get_sorted_keys(spans[o][z]);
            for (var y_index in y_values) {
                var y = y_values[y_index];

                var x_values = PTS.get_sorted_keys(spans[o][z][y]);
                for (var x_index in x_values) {
                    var x = x_values[x_index];

                    // a shortcut to the current span
                    var span = spans[o][z][y][x];

                    // fix/add this span to an available position
                    if (o==0) {
                        PTS.positions.fix(span);
                    } else {
                        PTS.positions.add(span);
                        resize = true;
                    }
                }
            }
        }
    }

    if (resize) {
        setTimeout(PTS.do_map_resize, parseInt(PTS.separate.duration));
    } else {
        // clear the mapaction
        PTS.clear_map_action();
    }
}

PTS.positions = {
    p : [], // array of (x, y) coordinates
            // denoting which are available (=TRUE)
            // and which are NOT available (=FALSE)

    grid : {}, // the x, y spacing of the position grid

    setup : function(span) {
        // mark all positions as available (=TRUE)
        this.p = [];
        this.grid = {x : parseInt($("#id_userwidth").val()),
                     y : parseInt($("#id_userheight").val())};
        var x_max = $(PTS.user_container).width() - this.grid.x;
        var y_max = $(PTS.user_container).height() - this.grid.y;
        for (var x=0; x<x_max; x += this.grid.x) {
            this.p[x] = [];
            for (var y=0; y<y_max; y += this.grid.y) {
                this.p[x][y] = true;
            }
        }
    },

    fix : function(span, x, y) {
        // mark any positions that are covered
        // by this span as NOT available (=FALSE)
        if (typeof(x)=="undefined" || typeof(y)=="undefined") {
            var p = $(span).position();
        } else {
            var p = {"left" : x, "top" : y};
        }
        var x_min = (p.left);
        var x_max = (x_min + $(span).outerWidth(true));
        var y_min = (p.top);
        var y_max = (y_min + $(span).outerHeight(true));
        for (var x in this.p) {
            if (x < x_min || x >= x_max) {
                continue;
            }
            for (var y in this.p[x]) {
                if (y < y_min || y >= y_max) {
                    continue;
                }
                this.p[x][y] = false;
            }
        }
    },

    check : function(x, y, w, h) {
        // if a span of width "w" and height "h"
        // can be be positioned at coordinate (x, y)
        // then return TRUE; otherwise return FALSE
        var x_min = x;
        var x_max = (x + w);
        var y_min = y;
        var y_max = (y + h);
        for (var x in this.p) {
            if (x < x_min || x >= x_max) {
                continue;
            }
            for (var y in this.p[x]) {
                if (y < y_min || y >= y_max) {
                    continue;
                }
                if (this.p[x][y]==false) {
                    return false; // this position is NOT available
                }
            }
        }
        return true; // all required positions were available - YAY
    },

    add : function(span) {
        // add this span at the nearest available position
        var w = $(span).outerWidth(true);
        var h = $(span).outerHeight(true);
        var p = $(span).position();
        var best = null;

        for (var x in this.p) {
            for (var y in this.p[x]) {
                if (this.p[x][y]==false) {
                    continue; // this position is not available
                }
                x = parseInt(x);
                y = parseInt(y);
                if (this.check(x, y, w, h)) {
                    var z = 0;
                    z += (x==p.left ? 0 : Math.pow(x - p.left, 2));
                    z += (y==p.top  ? 0 : Math.pow(y - p.top,  2));
                    z = Math.sqrt(z);
                    if (best===null || best.z > z) {
                        best = {x : x, y : y, z : z};
                    }
                }
            }
       }
        if (best) {
            this.fix(span, best.x, best.y);
            var css = {"left" : best.x, "top" : best.y};
            var complete = function() {
                PTS.set_awardto_xy($(this));
            };
            $(span).animate(css, PTS.separate.duration, "swing", complete);
        } else {
            // no positions were available,
            // so add a new row for this span
            // and then try the add again
            var y_max = 0;
            for (var x in this.p) {
                if (y_max==0) { // 1st time only
                    for (var y in this.p[x]) {
                        y_max = Math.max(y, y_max);
                    }
                    y_max += this.grid.y;
                }
                this.p[x][y_max] = true;
            }
            this.add(span);
        }
    },

    // this method may be useful for debugging
    print : function(title) {
        if (title) {
            console.log(title);
        }
        var p = [];
        for (var x in this.p) {
            for (var y in this.p[x]) {
                if (! p.hasOwnProperty(y)) {
                    p[y] = [];
                }
                p[y][x] = this.p[x][y];
            }
        }
        for (var y in p) {
            var str = "";
            for (var x in p[y]) {
                str += " [" + x + "]=" + p[y][x];
            }
            if (str) {
                console.log("Row " + y + ":" + str);
            }
        }
    }
}

/**
 * do_map_shuffle
 *
 * @return void
 */
PTS.do_map_shuffle = function() {

    // create array of spans and their indexes
    var i = 0;
    var spans = [];
    var indexes = [];
    $(PTS.user_container + " span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            spans[i] = this;
            indexes[i] = i++;
        }
    });

    // shuffle array of indexes, based on ...
    // http://onwebdev.blogspot.com/2011/05/jquery-randomize-and-shuffle-indexesay.html
    var i = indexes.length;
    while (i > 0) {
        var xx = parseInt(Math.random() * i);
        var x = indexes[--i];
        indexes[i] = indexes[xx];
        indexes[xx] = x;
    }

    // this flag will be set to true
    // if the user map changes size
    // or the users change position
    PTS.update_map = false;

    // switch the positions all SPANs
    for (var i=0; i<indexes.length; i++) {
        if (indexes[i]===null) {
            continue;
        }
        var h = spans[i].innerHTML;
        var x = i;
        var xx = indexes[x];
        while (xx != i) {
            indexes[x] = null;
            PTS.shuffle_user(spans[x], spans[xx]);
            x = xx;
            xx = indexes[x];
        }
        indexes[x] = null;
        PTS.shuffle_user(spans[x], spans[i]);
    }

    if (PTS.update_map) {
        PTS.update_map = false;
        PTS.update_map_via_ajax();
    }

    // clear the mapaction
    PTS.clear_map_action();
}

/*
 * shuffle_user
 * move SPAN "a" to current position of SPAN "b"
 *
 * @param object SPAN "a"
 * @param object SPAN "b"
 * @return void, but may reposition SPAN "a"
 *
 */
PTS.shuffle_user = function(a, b) {
    var p = $(b).position();
    var css = {"left" : p.left, "top" : p.top};
    $(a).animate(css, PTS.shuffle.duration);
    $(a).find("input[type=checkbox],input[type=radio]").each(function(){
        var userid = PTS.get_input_userid($(this));
        if (userid) {
            var x = $("#id_awardtox_" + userid).val();
            var y = $("#id_awardtoy_" + userid).val();
            if (x != p.left) {
                $("#id_awardtox_" + userid).val(p.left);
                PTS.update_map = true;
            }
            if (y != p.top) {
                $("#id_awardtoy_" + userid).val(p.top);
                PTS.update_map = true;
            }
        }
    });
}


/**
 * do_map_resize
 *
 * @return void
 */
PTS.do_map_resize = function() {
    var x = null; // minimum x-coordinate
    var y = null; // minimum y-coordinate
    var w = 0; // required new width for user container
    var h = 0; // required new height for user container

    $(PTS.user_container + " span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            x = (x===null ? p.left : Math.min(x, p.left));
            y = (y===null ? p.top  : Math.min(y, p.top));
            w = Math.max(w, p.left + $(this).outerWidth(true) + 8);
            h = Math.max(h, p.top + $(this).outerHeight(true) + 8);
        }
    });

    if (x || y) {
        css = {"left" : (x > 0  ? "-=" + x : "+=" + Math.abs(x)),
               "top"  : (y > 0  ? "-=" + y : "+=" + Math.abs(y))}
        $(PTS.user_container + " span").each(function(){
            if ($(this).hasClass("ui-draggable")) {
                var complete = function() {
                    PTS.set_awardto_xy($(this));
                };
                $(this).animate(css, PTS.resize.duration, "swing", complete);
            }
        });
        w -= x;
        h -= y;
    }

    // store new width/height
    if (w != $("#id_mapwidth").val()) {
        $("#id_mapwidth").val(w);
        PTS.update_map = true;
    }
    if (h != $("#id_mapheight").val()) {
        $("#id_mapheight").val(h);
        PTS.update_map = true;
    }

    if (PTS.update_map) {
        PTS.update_map = false;
        PTS.update_map_via_ajax();
    }

    // do (animated) map resize
    var css = {"width" : w, "height" : h};
    $(PTS.user_container).animate(css, PTS.resize.duration);

    // clear the mapaction
    PTS.clear_map_action();
}

/**
 * do_map_rotate
 *
 * @return void
 */
PTS.do_map_rotate = function(timeout) {
    var resize = false;
    var x_max = $(PTS.user_container).width();
    $(PTS.user_container + " span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            var w = $(this).outerWidth();
            var h = $(this).outerHeight();
            var x = ((w / h) * p.top);
            var y = ((h / w) * (x_max - p.left - w));
            var css = {"left" : x, "top" : y};
            var complete = function() {
                PTS.set_awardto_xy($(this));
            };
            $(this).animate(css, PTS.rotate.duration, "swing", complete);
            resize = true;
        }
    });
    if (resize) {
        setTimeout(PTS.do_map_resize, parseInt(PTS.rotate.timeout));
    } else {
        PTS.clear_map_action();
    }
}

/**
 * update_points_html
 *
 * @param object  input
 * @param string  type
 * @param integer points
 * @return void
 */
PTS.update_points_html = function(input, type, points) {
    var regexp = new RegExp("-?[0-9]+$");
    var html = input.parent().find("em.points" + type).html();
    var match = html.match(regexp);
    if (match) {
        points = parseInt(points);
        if (PTS.pointstype==0) { // incremental points
            points += parseInt(html.substring(match.index));
        }
        html = html.substring(0, match.index) + points;
        input.parent().find("em.points" + type).html(html);
    }
}

/**
 * update_map_via_ajax
 *
 * @param object input
 * @return void
 */
PTS.update_map_via_ajax = function() {
    $("#feedback").html(PTS.contacting_server_msg);
    PTS.set_feedback_visibility();
    var data = {ajax        : 1,
                mapid       : $("#id_mapid").val(),
                mapwidth    : $("#id_mapwidth").val(),
                mapheight   : $("#id_mapheight").val(),
                userwidth   : $("#id_userwidth").val(),
                userheight  : $("#id_userheight").val(),
                group       : PTS.groupid,
                groupid     : PTS.groupid,
                sesskey     : PTS.sesskey}
    $("input[name^=awardtox]").each(function(){
        data[$(this).prop("name")] = $(this).val();
    });
    $("input[name^=awardtoy]").each(function(){
        data[$(this).prop("name")] = $(this).val();
    });
    $.ajax({
        cache   : false,
        data    : data,
        datatype: "html",
        method  : "post",
        url     : PTS.awardpoints_ajax_php
    }).done(function(feedback){
        $("#feedback").parent().html(feedback);
        PTS.set_feedback_visibility();
    });
}

/**
 * send_points_via_ajax
 *
 * @param object input
 * @return void
 */
PTS.send_points_via_ajax = function(input) {
    var points = $(PTS.points_container + " span input[name=points]:checked").val();
    if ($("#id_commenttextmenu").length==0) {
        var commenttext = "";
    } else {
        var commenttext = $("#id_commenttextmenu").val();
    }
    if (commenttext=="") {
        commenttext = $("#id_commenttext").val();
    }
    var userid = PTS.get_input_userid(input);
    if (userid) {
        var data = {ajax        : 1,
                    points      : points,
                    awardto     : userid,
                    commenttext : commenttext,
                    group       : PTS.groupid,
                    groupid     : PTS.groupid,
                    sesskey     : PTS.sesskey}
        $("#feedback").html(PTS.contacting_server_msg);
        PTS.set_feedback_visibility();
        $.ajax({
            cache   : false,
            data    : data,
            datatype: "html",
            method  : "post",
            url     : PTS.awardpoints_ajax_php
        }).done(function(feedback){
            $("#feedback").parent().html(feedback);
            PTS.set_feedback_visibility();
            if (PTS.showpointstoday) {
                PTS.update_points_html(input, "today", points);
            }
            if (PTS.showpointstotal) {
                PTS.update_points_html(input, "total", points);
            }
            input.parent().removeClass("checked");
            input.prop("checked", false);
        });
    }
}

/**
 * set_span_size_color
 *
 * @param object  span
 * @param integer w
 * @param integer h
 * @param string display (optional)
 * @return void
 */
PTS.set_span_size_color = function(span, w, h, display) {
    span.each(function(){
        if (display==null) {
            display = PTS.elementdisplay;
        }
        $(this).find("input").css("display", display);
    });
    span.each(function(){
        w = Math.max($(this).width(), w);
    });
    span.each(function(){
        $(this).css("min-width", w);
    });
    span.each(function(){
        h = Math.max($(this).height(), h);
    });
    span.each(function(){
        $(this).css("min-height", h);
    });
    span.each(function(){
        $(this).find("input").each(function(){
            if ($(this).prop("checked")) {
                $(this).parent().addClass("checked");
            }
        });
    });
}

/**
 * set_usermap_size
 *
 * @param object map
 * @return void
 */
PTS.set_usermap_size = function(map) {
    map.css("position", "relative");
    var w = $("#id_mapwidth").val();
    var h = $("#id_mapheight").val();
    if (parseInt(w)==0) {
        w = map.width();
        if (w > 640) {
            w = 640;
            map.css("width", w);
        }
        $("#id_mapwidth").val(w);
        PTS.update_map = true;
    }
    if (parseInt(h)==0) {
        h = map.height();
        $("#id_mapheight").val(h);
        PTS.update_map = true;
    }
}

/**
 * set_span_position
 *
 * @param object span
 * @return void
 */
PTS.set_span_position = function(span) {
    var update = false;
    $(span.get().reverse()).each(function(){
        var p = $(this).position();
        var x = p.left;
        var y = p.top;
        $(this).find("input[type=checkbox],input[type=radio]").each(function(){
            var userid = PTS.get_input_userid($(this));
            if (userid) {
                var awardtox = $("#id_awardtox_" + userid).val();
                var awardtoy = $("#id_awardtoy_" + userid).val();
                if (awardtox==="" || awardtoy==="") {
                    $("#id_awardtox_" + userid).val(x);
                    $("#id_awardtoy_" + userid).val(y);
                    $(this).parent().css({"position" : "absolute", "left" : x+"px", "top" : y+"px"});
                    PTS.update_map = true;
                } else {
                    $(this).parent().css({"position" : "absolute", "left" : awardtox+"px", "top" : awardtoy+"px"});
                }
            }
        });
    });
}

/**
 * set_user_size
 *
 * @param object span
 * @return void
 */
PTS.set_user_size = function(span) {
    var widths  = []; // width => frequency
    var heights = []; // height => frequency
    span.each(function(){
        // width           : content width
        // outerWidth      : width + padding + border
        // outerWidth(true): outerWidth + margin
        var w = $(this).outerWidth(true);
        var h = $(this).outerHeight(true);
        if (widths.hasOwnProperty(w)) {
            widths[w] ++;
        } else {
            widths[w] = 1;
        }
        if (heights.hasOwnProperty(h)) {
            heights[h] ++;
        } else {
            heights[h] = 1;
        }
    });
    var userwidth = 0;
    for (var w in widths) {
        if (widths[w] > userwidth) {
            userwidth = w;
        }
    }
    var userheight = 0;
    for (var h in heights) {
        if (heights[h] > userheight) {
            userheight = h;
        }
    }
    if (userwidth != $("#id_userwidth").val()) {
        $("#id_userwidth").val(userwidth);
        PTS.update_map = true;
    }
    if (userheight != $("#id_userheight").val()) {
        $("#id_userheight").val(userheight);
        PTS.update_map = true;
    }
}

/**
 * set_span_event_handlers
 *
 * @param object   span
 * @param function onclick, additional onclick function, or FALSE
 * @return void
 */
PTS.set_span_event_handlers = function(span, onclick) {
    span.each(function(){
        $(this).find("input").click(function(event){
            if ($(this).prop("checked")) {
                $(this).parent().addClass("checked");
                if ($(this).prop("type")=="radio") {
                    $(this).parent().siblings().removeClass("checked");
                }
                if (onclick) {
                    onclick($(this));
                }
            } else {
                $(this).parent().removeClass("checked");
            }
        });
        $(this).click(function(event){
            if (event.target.nodeName.toUpperCase()=="SPAN") {
                if ($(this).hasClass("disableclick")) {
                    $(this).removeClass("disableclick");
                } else {
                    var input = $(this).find("input");
                    if (input.prop("checked")==false) {
                        input.prop("checked", true);
                        $(this).addClass("checked");
                        if (input.prop("type")=="radio") {
                            $(this).siblings().removeClass("checked");
                        }
                        if (onclick) {
                            onclick(input);
                        }
                    } else if (input.prop("type")=="checkbox") {
                        input.prop("checked", false);
                        $(this).removeClass("checked");
                    }
                }
            }
        });
    });
}

/**
 * document ready
 *
 * @return void
 */
$(document).ready(function() {

    var user_container = $(PTS.user_container);
    var action_spans   = $(PTS.mapaction_container + " span");
    var user_spans     = $(PTS.user_container      + " span");
    var points_spans   = $(PTS.points_container    + " span");
    var do_user_action = (PTS.sendimmediately ? PTS.send_points_via_ajax : false);

    // this flag will be set to true
    // if the user map changes size
    // or the users change position
    PTS.update_map = false;

    PTS.set_feedback_visibility();
    PTS.set_usermap_size(user_container);

    PTS.set_span_size_color(action_spans, PTS.mapaction_min_width, PTS.mapaction_min_height, "none");
    PTS.set_span_size_color(user_spans,   PTS.user_min_width,      PTS.user_min_height);
    PTS.set_span_size_color(points_spans, PTS.points_min_width,    PTS.points_min_height);

    PTS.set_user_size(user_spans);
    PTS.set_span_position(user_spans);

    PTS.set_span_event_handlers(action_spans, PTS.do_map_action);
    PTS.set_span_event_handlers(user_spans,   do_user_action);
    PTS.set_span_event_handlers(points_spans, false);

    if (PTS.update_map) {
        PTS.update_map = false;
        PTS.update_map_via_ajax();
    }

    user_spans.draggable({
        containment: PTS.user_container,
        scroll: true,
        stack: "span",
        start: function(event, ui) {
            $(this).addClass("disableclick");
        },
        stop: function(event, ui) {
            PTS.set_awardto_xy($(this));
            PTS.update_map_via_ajax();
        }
    });

    var handles = {"e":"egrip", "se":"segrip", "s":"sgrip"};
    var h = null;
    for (h in handles) {
        var options = {
            class: "ui-resizable-handle ui-resizable-" + h,
            id: handles[h]
        };
        $("<div/>", options).appendTo(PTS.user_container);
        handles[h] = "#" + handles[h];
    }

    user_container.resizable({
        handles: handles,
        create: function(event, ui) {
            var w = $("#id_mapwidth").val();
            var h = $("#id_mapheight").val();
            $(this).css("width", w);
            $(this).css("height", h);
        },
        // restrict the max/min size of the user-map (DISABLED)
        //start: function(event, ui) {
        //    var w = 0;
        //    var h = 0;
        //    $(this).find("span").each(function(){
        //        var p = $(this).position();
        //        w = Math.max(w, p.left + $(this).width());
        //        h = Math.max(h, p.top + $(this).height());
        //    });
        //    $(this).resizable("option", "minWidth", w + 24);
        //    $(this).resizable("option", "minHeight", h + 14);
        //},
        stop: function(event, ui) {
            PTS.update_map_via_ajax();
            var w = $(this).css("width");
            var h = $(this).css("height");
            $("#id_mapwidth").val(parseInt(w));
            $("#id_mapheight").val(parseInt(h));
        }
    });

    var input = $(PTS.layouts_container + " input[class=indent]");
    input.parent().css({"display" : "inline-block", "min-width" : "140px"});

    var input = $(PTS.layouts_container + " input[name=layouts]");
    input.parent().css({"display" : "inline-block", "min-width" : "76px"});
});
