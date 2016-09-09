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

// array of selected elements
PTS.selected = $([]);

// current offset of element being dragged
// this will be added to all selected elements
PTS.offset = {top: 0, left: 0};

// a unique number for debugging purposes
// console.log((PTS.i++) + ": some message");
PTS.i = 1;

// the previous mapmode
// this is used to remember and revert to a previous mapmode
// after switching mode automatically during a keypress event
PTS.mapmode = "";

/**
 * set_feedback
 *
 * @param string msg
 * @return void
 */
PTS.set_feedback = function(msg) {
    $("#feedback").html(msg);
    PTS.set_feedback_visibility();
}

/**
 * set_ajax_feedback
 *
 * @param string msg
 * @return void
 */
PTS.set_ajax_feedback = function(msg) {
    $("#feedback").parent().html(msg);
    PTS.set_feedback_visibility();
}

/**
 * set_feedback_visibility
 *
 * @return void
 */
PTS.set_feedback_visibility = function() {
    var feedback = $("#feedback");
    if (PTS.showfeedback==2) {
        var css = {"visibility" : (feedback.html() ? "initial" : "hidden")}
    } else {
        var css = {"display" : (PTS.showfeedback==1 ? "initial" : "none")};
    }
    feedback.parents("div.fitem").css(css);
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
 * @param span a DOM element
 * @return void
 */
PTS.set_awardto_xy = function(span, x, y) {
    if (typeof(x)=="undefined" || typeof(y)=="undefined") {
        var p = $(span).position();
        x = p.left;
        y = p.top;
    }
    $(span).find("input[type=checkbox],input[type=radio]").each(function(){
        var userid = PTS.get_input_userid($(this));
        if (userid) {
            if (x != $("#id_awardtox_" + userid).val()) {
                $("#id_awardtox_" + userid).val(x);
                PTS.update_map = true;
            }
            if (y != $("#id_awardtoy_" + userid).val()) {
                $("#id_awardtoy_" + userid).val(y);
                PTS.update_map = true;
            }
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
 * @param object event
 * @param object input
 * @return void
 */
PTS.do_map_action = function(event, input) {
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
    $(PTS.user_container + " > span").each(function(){
        $(this).find("input[type=checkbox],input[type=radio]").each(function(){
            var userid = PTS.get_input_userid($(this));
            if (userid) {
                $("#id_awardtox_" + userid).val("");
                $("#id_awardtoy_" + userid).val("");
                $(this).parent().css({"position" : "relative",
                                      "left" : "0px",
                                      "top" : "0px"});
            }
        });
    });

    // convert spans to absolute position
    PTS.set_span_position($(PTS.user_container + " > span"));

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
    var grid = {x : $("#id_userwidth").val(),
                y : $("#id_userheight").val()}
    $(PTS.user_container + " > span").each(function(){
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
                PTS.set_awardto_xy(this, p.left + x, p.top + y);
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
    $(PTS.user_container + " > span").each(function(){
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
        setTimeout(PTS.do_map_resize, PTS.separate.duration);
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
            $(span).animate(css, PTS.separate.duration);
            PTS.set_awardto_xy(span, best.x, best.y);
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
    $(PTS.user_container + " > span").each(function(){
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

    // send new map settings to server
    // if any of the SPANs were moved
    setTimeout(PTS.update_map_via_ajax, PTS.shuffle.duration);

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
    PTS.set_awardto_xy(a, p.left, p.top);
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

    // get minimum (x,y) coordinates
    // and maximum w(idth) and h(eight)
    $(PTS.user_container + " > span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            x = (x===null ? p.left : Math.min(x, p.left));
            y = (y===null ? p.top  : Math.min(y, p.top));
            w = Math.max(w, p.left + $(this).outerWidth(true) + 8);
            h = Math.max(h, p.top + $(this).outerHeight(true) + 8);
        }
    });

    // adjust position of spans
    if (x || y) {
        css = {"left" : (x > 0  ? "-=" + x : "+=" + Math.abs(x)),
               "top"  : (y > 0  ? "-=" + y : "+=" + Math.abs(y))}
        $(PTS.user_container + " > span").each(function(){
            if ($(this).hasClass("ui-draggable")) {
                var complete = function() {
                    PTS.set_awardto_xy(this);
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

    // do (animated) map resize
    // and then update map via ajax
    var css = {"width" : w, "height" : h};
    $(PTS.user_container).animate(css, PTS.resize.duration, "swing", PTS.update_map_via_ajax);

    // clear the mapaction
    PTS.clear_map_action();
}

/**
 * do_map_rotate
 *
 * @return void
 */
PTS.do_map_rotate = function() {
    var resize = false;
    var x_max = $(PTS.user_container).width();
    $(PTS.user_container + " > span").each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            var w = $(this).outerWidth();
            var h = $(this).outerHeight();
            var x = Math.round((w / h) * p.top);
            var y = Math.round((h / w) * (x_max - p.left - w));
            PTS.set_awardto_xy(this, x, y);
            var css = {"left" : x, "top" : y};
            $(this).animate(css, PTS.rotate.duration);
            resize = true;
        }
    });
    if (resize) {
        setTimeout(PTS.do_map_resize, PTS.rotate.duration);
    } else {
        PTS.clear_map_action();
    }
}

/**
 * update_points_html
 *
 * @param object  input
 * @param string  type ("today" or "total")
 * @param integer points
 * @return void
 */
PTS.update_points_html = function(input, type, points) {
    var regexp = new RegExp("-?[0-9]+$");
    input.each(function(){
        var selector = "em." + type + "points";
        var html = $(this).parent().find(selector).html();
        var match = html.match(regexp);
        if (match) {
            var newpoints = parseInt(points);
            if (type=="today") { // PTS.pointstype==0, incremental
                newpoints += parseInt(html.substring(match.index));
            }
            html = html.substring(0, match.index) + newpoints;
            $(this).parent().find(selector).html(html);
        }
    });
}

/**
 * update_map_via_ajax
 *
 * @param object input
 * @return void
 */
PTS.update_map_via_ajax = function() {

    if (PTS.update_map) {
        PTS.update_map = false;

        PTS.set_feedback(PTS.contacting_server_msg);
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
            PTS.set_ajax_feedback(feedback);
        });
    }
}

/**
 * send_points_via_ajax
 *
 * @param object input
 * @return void
 */
PTS.send_points_via_ajax = function(input) {

    if (PTS.gradingmethod=="") {
        // simple direct grading

        var points = $(PTS.points_container + " > span > input[name=points]:checked").val();
        var advancedgrading = false;

    } else {
        // advanced grading e.g. "rubric" or "guide"

        var points = 0;
        var advancedgrading = [];

        // a RegExp to parse the name of an advanced grading element (radio or textarea)
        // e.g. advancedgrading[criteria][1][levelid]
        var regexp = new RegExp("^[a-z]+\\[([a-z]+)\\]\\[([0-9]+)\\]\\[([a-z]+)\\]$");
        // [0] : the full element name, starting with "advancedgrading"
        // [1] : "criteria"
        // [2] : integer (a criteria id)
        // [3] : RUBRIC radio: "levelid", textarea: "remark"
        //       GUIDE  text: "score", textarea: "remark"

        // select all radio and textarea elements within the rubric table
        var selector = PTS.gradingcontainer + " input[type=radio][name^=advancedgrading]:checked,"
                     + PTS.gradingcontainer + " input[type=text][name^=advancedgrading],"
                     + PTS.gradingcontainer + " textarea[name^=advancedgrading]";

        $(selector).each(function(){
            var name = $(this).prop("name");
            var type = $(this).prop("type");
            var value = $(this).val();
            advancedgrading[name] = value;
            var m = name.match(regexp);
            if (m) {
                if (PTS.gradingmethod=="rubric" && type=="radio" && m[3]=="levelid") {
                    // the selector for the SPAN element containing the score for this criteria level
                    // e.g. <span id="advancedgrading-criteria-2-levels-7-score" ...>
                    var selector = "#advancedgrading-" + m[1] + "-" + m[2] + "-levels-" + value + "-score";
                    points += parseInt($(selector).html());
                }
                if (PTS.gradingmethod=="guide" && type=="text" && m[3]=="score") {
                    points += parseInt(value);
                }
            }
        });
    }

    if ($("#id_commenttextmenu").length==0) {
        var commenttext = "";
    } else {
        var commenttext = $("#id_commenttextmenu").val();
    }
    if (commenttext=="") {
        commenttext = $("#id_commenttext").val();
    }

    if (input.length==0) {
        var userid = 0; // shouldn't happen !!
    } else if (input.length==1) {
        var userid = PTS.get_input_userid(input)
    } else if (input.length) {
        var userid = [];
        input.each(function(){
            var uid = PTS.get_input_userid($(this));
            if (uid) {
                userid[uid] = 1;
            }
        })
    }

    if (userid) {
        var data = {ajax        : 1,
                    points      : points,
                    awardto     : userid,
                    commenttext : commenttext,
                    group       : PTS.groupid,
                    groupid     : PTS.groupid,
                    sesskey     : PTS.sesskey}
        if (advancedgrading) {
            for (var name in advancedgrading) {
                data[name] = advancedgrading[name];
            }
            data.advancedgradinginstanceid = $("input[type=hidden][name=advancedgradinginstanceid]").val();
        }
        if (PTS.gradingmethod=="guide") {
            data.showmarkerdesc = $(PTS.gradingcontainer + " input[type=radio][name=showmarkerdesc]:checked").val();
            data.showstudentdesc = $(PTS.gradingcontainer + " input[type=radio][name=showstudentdesc]:checked").val();
        }
        PTS.set_feedback(PTS.contacting_server_msg);
        $.ajax({
            cache   : false,
            data    : data,
            datatype: "html",
            method  : "post",
            url     : PTS.awardpoints_ajax_php
        }).done(function(feedback){
            PTS.set_ajax_feedback(feedback);
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
 * @param object  spans
 * @param integer w
 * @param integer h
 * @param string display (optional)
 * @return void
 */
PTS.set_span_size_color = function(spans, w, h, display) {
    spans.each(function(){
        $(this).find("input").css("display", display);
    });
    spans.each(function(){
        w = Math.max($(this).width(), w);
    });
    spans.each(function(){
        $(this).css("min-width", w);
    });
    spans.each(function(){
        h = Math.max($(this).height(), h);
    });
    spans.each(function(){
        $(this).css("min-height", h);
    });
    spans.each(function(){
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
 * @param object spans
 * @return void
 */
PTS.set_span_position = function(spans) {
    var update = false;
    $(spans.get().reverse()).each(function(){
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
                    $(this).parent().css({"position" : "absolute",
                                          "left" : x + "px",
                                          "top" : y + "px"});
                    PTS.update_map = true;
                } else {
                    $(this).parent().css({"position" : "absolute",
                                          "left" : awardtox + "px",
                                          "top" : awardtoy + "px"});
                }
            }
        });
    });
}

/**
 * set_user_size
 *
 * @param object spans
 * @return void
 */
PTS.set_user_size = function(spans) {
    var widths  = []; // width => frequency
    var heights = []; // height => frequency
    spans.each(function(){
        // width           : content width
        // outerWidth      : width + padding + border
        // outerWidth(true): outerWidth + margin
        var w = $(this).outerWidth(true);
        var h = $(this).outerHeight(true);
        if (widths.hasOwnProperty(w)) {
            widths[w]++;
        } else {
            widths[w] = 1;
        }
        if (heights.hasOwnProperty(h)) {
            heights[h]++;
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

PTS.get_mapmode_elements = function() {
    return PTS.mapmode_container + " > span > input[name=mapmode]";
}

PTS.get_mapmode_value = function() {
    return $(PTS.get_mapmode_elements() + ":checked").val();
}

PTS.set_mapmode_value = function(value) {
    if (PTS.get_mapmode_value() != value) {
        $(PTS.get_mapmode_elements()).val([value]);
        $(PTS.get_mapmode_elements() + "[value=" + value + "]").click();
    }
}

PTS.is_mapmode_value = function(value, mapmode_cache) {
    if (mapmode_cache) {
        return (value==mapmode_cache);
    } else {
        return (value==PTS.get_mapmode_value());
    }
}

PTS.is_mapmode_award = function(event, mapmode_cache) {
    if (event.metaKey) {
        return false; // select mode
    }
    return PTS.is_mapmode_value("award", mapmode_cache);
}

PTS.is_mapmode_select = function(event, mapmode_cache) {
    if (event.metaKey) {
        return true; // select mode
    }
    return PTS.is_mapmode_value("select", mapmode_cache);
}

PTS.is_mapmode_absent = function(event, mapmode_cache) {
    if (event.metaKey) {
        return false; // select mode
    }
    return PTS.is_mapmode_value("absent", mapmode_cache);
}

PTS.is_mapmode_report = function(event, mapmode_cache) {
    if (event.metaKey) {
        return false; // select mode
    }
    return PTS.is_mapmode_value("report", mapmode_cache);
}

PTS.is_not_mapmode_award = function(event) {
    var mapmode_cache = PTS.get_mapmode_value();
    if (PTS.is_mapmode_select(event, mapmode_cache)) {
        return true;
    }
    if (PTS.is_mapmode_absent(event, mapmode_cache)) {
        return true;
    }
    if (PTS.is_mapmode_report(event, mapmode_cache)) {
        return true;
    }
    return false;
}

/**
 * set_span_event_handlers
 *
 * @param object   spans
 * @param function onclick, additional onclick function, or FALSE
 * @return void
 */
PTS.set_span_event_handlers = function(spans, onclick, ondblclick) {
    spans.each(function(){

        $(this).find("input").click(function(event, ui){
            if ($(this).parent().hasClass("ui-selectee") && PTS.is_not_mapmode_award(event)) {
                // ignore Ctrl-click on selectable items
                event.preventDefault();
            } else {
                if ($(this).prop("checked")) {
                    $(this).parent().addClass("checked");
                    if ($(this).prop("type")=="radio") {
                        $(this).parent().siblings().removeClass("checked");
                    }
                } else {
                    $(this).parent().removeClass("checked");
                }
            }
        });

        $(this).click(function(event, ui){

            if ($(event.target).closest("label").length) {
                nodeName = "LABEL";
            } else if (event.target.nodeName=="INPUT") {
                nodeName = "INPUT";
            } else {
                nodeName = "SPAN";
            }

            if ($(this).hasClass("ui-selectee")) {
                var toggle_checked = (nodeName=="SPAN" && PTS.is_mapmode_award(event));
                var toggle_selected = PTS.is_mapmode_select(event);
                var trigger_onclick = (! toggle_selected);
            } else {
                var toggle_checked = (nodeName=="SPAN");
                var trigger_onclick = true;
                var toggle_selected = false;
            }

            if (nodeName=="LABEL") {
                trigger_onclick = false;
                toggle_selected = false;
            } else {
                if ($(this).hasClass("ui-dragging")) {
                    $(this).removeClass("ui-dragging");
                    toggle_checked = false;
                    toggle_selected = false;
                    trigger_onclick = false;
                }
            }

            if (toggle_selected) {
                $(this).toggleClass("ui-selected");
            }

            var input = $(this).find("input").first();
            if (toggle_checked) {
                if (input.prop("checked")==false) {
                    input.prop("checked", true);
                    $(this).addClass("checked");
                    if (input.prop("type")=="radio") {
                        $(this).siblings().removeClass("checked");
                    }
                } else if (input.prop("type")=="checkbox") {
                    input.prop("checked", false);
                    $(this).removeClass("checked");
                }
            }

            if (trigger_onclick && onclick) {
                onclick(event, input);
            }
        });
    });
}

/**
 * do_user_click
 *
 * @param object event
 * @param object input
 * @return void
 */
PTS.do_user_click = function(event, input) {

    var span = input.parent();
    switch (true) {

        case PTS.is_mapmode_award(event):
            if (input.prop("checked")) {
                if (span.hasClass("ui-selected")) {
                    // do onclick for ALL selected users
                    var selected = span.siblings(".ui-selected");
                    var checked = (input.prop("type")=="checkbox");
                    selected.addClass("checked");
                    selected.find("input").prop("checked", checked);

                    var notselected = span.siblings(":not(.ui-selected)");
                    notselected.removeClass("checked");
                    notselected.find("input").prop("checked", false);

                    input = selected.andSelf().find("input");
                } else {
                    // do onclick for ONE single item
                    // that is not currently selected
                    var selected = span.siblings(".ui-selected");
                    selected.removeClass("ui-selected");
                }
                if (PTS.sendimmediately) {
                    PTS.send_points_via_ajax(input);
                }
            }
            break;

        case PTS.is_mapmode_absent(event):
            span.toggleClass("absent");
            break;

        case PTS.is_mapmode_report(event):
            var userid = PTS.get_input_userid(input.first())
            if (userid) {
                PTS.set_feedback(PTS.contacting_server_msg);
                $.ajax({
                    cache   : false,
                    data    : {ajax    : 1,
                               userid  : userid,
                               sesskey : PTS.sesskey},
                    datatype: "html",
                    method  : "post",
                    url     : PTS.reportpoints_ajax_php
                }).done(function(report_content){
                    PTS.set_feedback("");

                    // create reference to report element
                    var report = $(PTS.report_container);

                    // close the report.dialog()
                    if (report.dialog("isOpen")) {
                        report.dialog("close");
                    }

                    // insert the report content
                    report.html(report_content);

                    // position the report at top left of user tile
                    report.dialog("option", "position", {my: "left top",
                                                         at: "left top",
                                                         of: span});

                    // get clean user's name
                    var name = span.find("em.name").html();
                    name = name.replace(new RegExp("<[^>]*>", "g"), " ");

                    // get clean report title
                    var title = report.dialog("option", "title");
                    title = title.replace(new RegExp(": .*$"), "");

                    // append user's name to report title
                    report.dialog("option", "title", title + ": " + name);

                    // reveal the report
                    report.dialog("open");
                });
            }
            break;
    }
}

/**
 * document ready
 *
 * @return void
 */
$(document).ready(function() {

    var user_container = $(PTS.user_container);
    var action_spans   = $(PTS.mapaction_container + " > span");
    var mode_spans     = $(PTS.mapmode_container   + " > span");
    var user_spans     = $(PTS.user_container      + " > span");
    var points_spans   = $(PTS.points_container    + " > span");

    // this flag will be set to true
    // if the user map changes size
    // or the users change position
    PTS.update_map = false;

    PTS.set_feedback_visibility();
    PTS.set_usermap_size(user_container);

    PTS.set_span_size_color(action_spans, PTS.mapaction_min_width, PTS.mapaction_min_height, "none");
    PTS.set_span_size_color(mode_spans,   PTS.mapmode_min_width,   PTS.mapmode_min_height,   "none");
    PTS.set_span_size_color(user_spans,   PTS.user_min_width,      PTS.user_min_height,      PTS.elementdisplay);
    PTS.set_span_size_color(points_spans, PTS.points_min_width,    PTS.points_min_height,    "none");

    PTS.set_user_size(user_spans);
    PTS.set_span_position(user_spans);

    PTS.set_span_event_handlers(action_spans, PTS.do_map_action);
    PTS.set_span_event_handlers(mode_spans,   false);
    PTS.set_span_event_handlers(user_spans,   PTS.do_user_click);
    PTS.set_span_event_handlers(points_spans, false);

    // send new map settings to server
    // if PTS.update_map was set to TRUE
    PTS.update_map_via_ajax();

    // the keydown event will detect Apple/Win key
    // and set the mapmode to "select"
    $(document).keydown(function(event){
        if (event.metaKey) {
            PTS.mapmode = PTS.get_mapmode_value();
            PTS.set_mapmode_value("select");
        } else {
            PTS.mapmode = "";
        }
    });

    // the keyup event revert the mapmode
    $(document).keyup(function(event){
        if (PTS.mapmode) {
            PTS.set_mapmode_value(PTS.mapmode);
            PTS.mapmode = "";
        }
    });

    user_spans.draggable({
        containment: PTS.user_container,
        scroll: true,
        stack: "span",
        start: function(event, ui) {
            $(this).addClass("ui-dragging");
            if ($(this).hasClass("ui-selected")){
                PTS.selected = $("span.ui-selected").each(function(){
                    $(this).data("offset", $(this).offset());
                });
            } else {
                PTS.selected = $([]);
                $(this).siblings().removeClass("ui-selected");
            }
            PTS.offset = $(this).offset();
        },
        drag: function(event, ui) {
            var dt = (ui.position.top - PTS.offset.top);
            var dl = (ui.position.left - PTS.offset.left);
            // adjust the offset of all selected elements
            // except $(this) - the element being dragged
            PTS.selected.not(this).each(function(){
                var offset = $(this).data("offset");
                $(this).css({top : offset.top + dt,
                             left: offset.left + dl});
            });
        },
        stop: function(event, ui) {
            PTS.selected.not(this).each(function(){
                PTS.set_awardto_xy(this);
            });
            PTS.set_awardto_xy(this);
            PTS.update_map_via_ajax();
        }
    });

    // make the user container selectable
    user_container.selectable({
        cancel: "div.ui-resizable-handle,input",
        disabled: (PTS.allowselectable ? false : true),
        filter: "span"
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
            user_container.css("width", w);
            user_container.css("height", h);
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
            var w = parseInt(user_container.css("width"));
            if (w != $("#id_mapwidth").val()) {
                $("#id_mapwidth").val(w);
                PTS.update_map = true;
            }
            var h = parseInt(user_container.css("height"));
            if (h != $("#id_mapheight").val()) {
                $("#id_mapheight").val(h);
                PTS.update_map = true;
            }
            PTS.update_map_via_ajax();
        }
    });


    // adjust CSS for input elements in layouts_container
    var input = $(PTS.layouts_container + " input[class=indent]");
    input.parent().css({"display" : "inline-block", "min-width" : "140px"});

    var input = $(PTS.layouts_container + " input[name=layouts]");
    input.parent().css({"display" : "inline-block", "min-width" : "76px"});

    // create the report.dialog()
    $(PTS.report_container).dialog({
        autoOpen : false,
        width    : 'auto'
    });

    // and remove the "report" section header
    $("#id_report_hdr").remove();
});

