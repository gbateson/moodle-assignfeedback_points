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
PTS.offset = {"top" : 0, "left" : 0};

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
    if (msg=="" || msg.indexOf('id="feedback"') < 0) {
        $("#feedback").html(msg);
    } else {
        $("#feedback").parent().html(msg);
    }
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
 * @param elm a DOM element
 * @return void
 */
PTS.set_awardto_xy = function(elm, x, y) {
    if (typeof(x)=="undefined" || typeof(y)=="undefined") {
        var p = $(elm).position();
        x = p.left;
        y = p.top;
    }
    $(elm).find("input[type=checkbox],input[type=radio]").each(function(){
        var userid = PTS.get_input_userid($(this));
        if (userid) {
            if (x != $("#id_awardtox_" + userid).val()) {
                $("#id_awardtox_" + userid).val(x);
                PTS.update_usermap = true;
            }
            if (y != $("#id_awardtoy_" + userid).val()) {
                $("#id_awardtoy_" + userid).val(y);
                PTS.update_usermap = true;
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

    // convert elms to relative position
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
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

    // convert elms to absolute position
    PTS.set_elm_position($(PTS.user_container + " > " + PTS.group_element_tag));

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
    var grid = {"x" : $("#id_userwidth").val(),
                "y" : $("#id_userheight").val()}
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var css = {};
            var p = $(this).position();
            var x = (p.left % grid.x);
            var y = (p.top  % grid.y);
            if (x) {
                if (x < 0) {
                    css.left = "+=" + x;
                } else if (x <= (grid.x / 2)) {
                    css.left = "-=" + x; // i.e. shift this elm left
                } else {
                    css.left = "+=" + (grid.x - x); // right
                }
            }
            if (y) {
                if (y < 0) {
                    css.top = "+=" + y;
                } else if (y <= (grid.y / 2)) {
                    css.top = "-=" + y; // i.e. shift this elm up
                } else {
                    css.top = "+=" + (grid.y - y); // down
                }
            }
            if (x || y) {
                PTS.set_awardto_xy(this, p.left + x, p.top + y);
                $(this).animate(css, PTS.cleanup.duration);
                PTS.update_usermap = true;
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
    var elms = [];
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
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
            if (! elms.hasOwnProperty(o)) {
                elms[o] = [];
            }
            if (! elms[o].hasOwnProperty(z)) {
                elms[o][z] = [];
            }
            if (! elms[o][z].hasOwnProperty(y)) {
                elms[o][z][y] = [];
            }
            if (! elms[o][z][y].hasOwnProperty(x)) {
                elms[o][z][y][x] = [];
            }
            // usually there will only be one elm
            // at any given (x, y) coordinate, but
            // to be robust we allow for multiple elms
            elms[o][z][y][x].push(this);
        }
    });

    // setup the available positions
    PTS.positions.setup();

    var o_values = PTS.get_sorted_keys(elms);
    for (var o_index in o_values) {
        var o = o_values[o_index];

        var z_values = PTS.get_sorted_keys(elms[o]);
        for (var z_index in z_values) {
            var z = z_values[z_index];

            var y_values = PTS.get_sorted_keys(elms[o][z]);
            for (var y_index in y_values) {
                var y = y_values[y_index];

                var x_values = PTS.get_sorted_keys(elms[o][z][y]);
                for (var x_index in x_values) {
                    var x = x_values[x_index];

                    // a shortcut to the current elm
                    var elm = elms[o][z][y][x];

                    // fix/add this elm to an available position
                    if (o==0) {
                        PTS.positions.fix(elm);
                    } else {
                        PTS.positions.add(elm);
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
    "p" : [], // array of (x, y) coordinates
            // denoting which are available (=TRUE)
            // and which are NOT available (=FALSE)

    "grid" : {}, // the x, y spacing of the position grid

    "setup" : function(elm) {
        // mark all positions as available (=TRUE)
        this.p = [];
        this.grid = {"x" : parseInt($("#id_userwidth").val()),
                     "y" : parseInt($("#id_userheight").val())};
        var x_max = $(PTS.user_container).width() - this.grid.x;
        var y_max = $(PTS.user_container).height() - this.grid.y;
        for (var x=0; x<x_max; x += this.grid.x) {
            this.p[x] = [];
            for (var y=0; y<y_max; y += this.grid.y) {
                this.p[x][y] = true;
            }
        }
    },

    "fix" : function(elm, x, y) {
        // mark any positions that are covered
        // by this elm as NOT available (=FALSE)
        if (typeof(x)=="undefined" || typeof(y)=="undefined") {
            var p = $(elm).position();
        } else {
            var p = {"left" : x, "top" : y};
        }
        var x_min = (p.left);
        var x_max = (x_min + $(elm).outerWidth(true));
        var y_min = (p.top);
        var y_max = (y_min + $(elm).outerHeight(true));
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

    "check" : function(x, y, w, h) {
        // if a elm of width "w" and height "h"
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

    "add" : function(elm) {
        // add this elm at the nearest available position
        var w = $(elm).outerWidth(true);
        var h = $(elm).outerHeight(true);
        var p = $(elm).position();
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
            this.fix(elm, best.x, best.y);
            var css = {"left" : best.x, "top" : best.y};
            $(elm).animate(css, PTS.separate.duration);
            PTS.set_awardto_xy(elm, best.x, best.y);
        } else {
            // no positions were available,
            // so add a new row for this elm
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
            this.add(elm);
        }
    },

    // this method may be useful for debugging
    "print" : function(title) {
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

    // create array of elms and their indexes
    var i = 0;
    var elms = [];
    var indexes = [];
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
        if ($(this).hasClass("ui-draggable")) {
            elms[i] = this;
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
    PTS.update_usermap = false;

    // switch the positions all SPANs
    for (var i=0; i<indexes.length; i++) {
        if (indexes[i]===null) {
            continue;
        }
        var h = elms[i].innerHTML;
        var x = i;
        var xx = indexes[x];
        while (xx != i) {
            indexes[x] = null;
            PTS.shuffle_user(elms[x], elms[xx]);
            x = xx;
            xx = indexes[x];
        }
        indexes[x] = null;
        PTS.shuffle_user(elms[x], elms[i]);
    }

    // if any of the SPANs moved, send new map settings to server
    setTimeout(PTS.update_usermap_via_ajax, PTS.shuffle.duration);

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
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            x = (x===null ? p.left : Math.min(x, p.left));
            y = (y===null ? p.top  : Math.min(y, p.top));
            w = Math.max(w, p.left + $(this).outerWidth(true) + 8);
            h = Math.max(h, p.top + $(this).outerHeight(true) + 8);
        }
    });

    // adjust position of elms
    if (x || y) {
        css = {"left" : (x > 0  ? "-=" + x : "+=" + Math.abs(x)),
               "top"  : (y > 0  ? "-=" + y : "+=" + Math.abs(y))}
        $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
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
        PTS.update_usermap = true;
    }
    if (h != $("#id_mapheight").val()) {
        $("#id_mapheight").val(h);
        PTS.update_usermap = true;
    }

    // do (animated) map resize
    // and then update map via ajax
    var css = {"width" : w, "height" : h};
    $(PTS.user_container).animate(css, PTS.resize.duration, "swing", PTS.update_usermap_via_ajax);

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
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
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
 * do_map_sortby
 *
 * @return void
 */
PTS.do_map_sortby = function() {

    var field = $("#id_sortbymenu").val();
    if (field=="") {
        // should we show some kind of message (PTS.str.selectsortbyfield):
        // Select a sort field from the menu, then click the "Sort by" button
        return false;
    }
    if (PTS.sortby[field]==null) {
        return false; // shouldn't happen !!
    }

    // create array of elms ordered by position, elms[y][x]
    var elms = {};
    $(PTS.user_container + " > " + PTS.group_element_tag).each(function(){
        if ($(this).hasClass("ui-draggable")) {
            var p = $(this).position();
            if (elms[p.top]==null) {
                elms[p.top] = {};
            }
            elms[p.top][p.left] = this;
        }
    });

    // this flag will be set to true
    // if the user map changes size
    // or the users change position
    PTS.update_usermap = false;

    var i = 0;
    for (var y in elms) {
        for (var x in elms[y]) {
            var id = PTS.sortby[field][i++];
            var elm = $("#id_awardto_" + id);
            elm = elm.first().parent().get(0);
            PTS.shuffle_user(elm, elms[y][x]);
        }
    }

    // if any of the SPANs moved, send new map settings to server
    setTimeout(PTS.update_usermap_via_ajax, PTS.shuffle.duration);

    // clear the mapaction
    PTS.clear_map_action();
}

/**
 * update_usermap_via_ajax
 *
 * @param object input
 * @return void
 */
PTS.update_usermap_via_ajax = function() {

    if (PTS.update_usermap) {
        PTS.update_usermap = false;

        PTS.set_feedback(PTS.str.contactingserver);
        var data = {
            "ajax"        : 1,
            "group"       : PTS.groupid,
            "groupid"     : PTS.groupid,
            "sesskey"     : PTS.sesskey,
            "mapid"       : $("#id_mapid").val(),
            "mapwidth"    : $("#id_mapwidth").val(),
            "mapheight"   : $("#id_mapheight").val(),
            "userwidth"   : $("#id_userwidth").val(),
            "userheight"  : $("#id_userheight").val()
        };
        $("input[name^=awardtox]").each(function(){
            data[$(this).prop("name")] = $(this).val();
        });
        $("input[name^=awardtoy]").each(function(){
            data[$(this).prop("name")] = $(this).val();
        });
        $.ajax({
            "cache"    : false,
            "data"     : data,
            "datatype" : "html",
            "method"   : "post",
            "url"      : PTS.awardpoints_ajax_php
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

    if (input.length==0) {
        return false; // shouldn't happen !!
    }

    if (input.length==1) {
        var userid = PTS.get_input_userid(input)
    } else {
        var userid = [];
        input.each(function(){
            var uid = PTS.get_input_userid($(this));
            if (uid) {
                userid[uid] = 1;
            }
        });
    }

    var data = {
        "ajax"    : 1,
        "group"   : PTS.groupid,
        "groupid" : PTS.groupid,
        "sesskey" : PTS.sesskey,
        "awardto" : userid
    };

    if (PTS.gradingmethod=="") {
        // simple direct grading

        var name = "points";
        var selector = PTS.points_container + " > "
                     + PTS.group_element_tag + " > "
                     + "input[name=" + name + "]:checked";
        var points = $(selector).val();
        data[name] = points;

        var value = "";
        var name = "commenttext";
        var selector = "#id_" + name + "menu";
        if ($(selector).length) {
            value = $(selector).val();
        }
        if (value=="") {
            selector = "#id_" + name;
            value = $(selector).val();
        }
        data[name] = value;

        if (PTS.showpointstoday) {
            PTS.set_points(input, "pointstoday", points);
        }
        if (PTS.showpointstotal) {
            PTS.set_points(input, "pointstotal", points);
        }

    } else {
        // Advanced grading e.g. "rubric" or "guide"

        // select all radio, input  and textarea elements in the advanced grading table
        var name = "advancedgrading";
        var selector = PTS.gradingcontainer + " input[type=radio][name^=" + name + "],"
                     + PTS.gradingcontainer + " input[type=text][name^=" + name + "],"
                     + PTS.gradingcontainer + " textarea[name^=" + name + "]";

        $(selector).each(function(){
            var tag = this.tagName;
            var name = this.name;
            var type = this.type;
            if (tag=="TEXTAREA" || (tag=="INPUT" && type=="text")) {
                var ok = $(this).val().length;
            } else if (tag=="INPUT" && type=="radio") {
                var ok = $(this).prop("checked");
            } else {
                var ok = false; // shouldn't happen !!
            }
            if (ok) {
                data[name] = $(this).val();
            }
        });

        var name = "advancedgradinginstanceid";
        data[name] = $("input[type=hidden][name=" + name + "]").val();

        // RegExp to parse the html of a criterion score's DOM element
        // e.g. Report 1: 3/5
        // $1 : the criterion description
        // $2 : the current score for this criterion
        // $3 : the maximum score for this criterion (optional)
        var regexp = new RegExp("^(.*): (-?[0-9.]+)(/[0-9.]+)?$");

        if (PTS.gradingmethod=="rubric") {
            if (typeof(userid)=="string") {
                PTS.set_rubric_levels(userid, regexp);
            } else {
                for (var uid in userid) {
                    PTS.set_rubric_levels(uid, regexp);
                }
            }
        }

        if (PTS.gradingmethod=="guide") {
            var name = "showmarkerdesc";
            data[name] = $(PTS.gradingcontainer + " input[type=radio][name=" + name + "]:checked").val();

            var name = "showstudentdesc";
            data[name] = $(PTS.gradingcontainer + " input[type=radio][name=" + name + "]:checked").val();

            // we are not going to send anything extra to the server
            // however, we need to update the scores and total
            // in the browser
            if (typeof(userid)=="string") {
                PTS.set_guide_scores(userid, regexp);
            } else {
                for (var uid in userid) {
                    PTS.set_guide_scores(uid, regexp);
                }
            }
        }
    }

    PTS.set_feedback(PTS.contacting_server_msg);
    $.ajax({
        "cache"    : false,
        "data"     : data,
        "datatype" : "html",
        "method"   : "post",
        "url"      : PTS.awardpoints_ajax_php
    }).done(function(feedback){
        input.prop("checked", false);
        input.parent().removeClass("checked");
        if (feedback.match(new RegExp("^\{.*\}$"))) {
            feedback = $.parseJSON(feedback);
            var regexp = new RegExp("^(.*): (-?[0-9.]+)(/[0-9.]+)?$");
            for (var userid in feedback.values) {
                var user = $("#id_awardto_" + userid).first();
                for (var type in feedback.values[userid]) {
                    var value = feedback.values[userid][type];
                    var em = user.parent().find("em." + type);
                    if (em.length) {
                        em = em.first();
                        var oldhtml = em.html();
                        var newhtml = "$1: " + value + "$3";
                        em.html(oldhtml.replace(regexp, newhtml));
                    }
                }
            }
            feedback = feedback.text;
        }
        PTS.set_ajax_feedback(feedback);
    });
}

/**
 * set_points
 *
 * @param object  input
 * @param string  classname
 * @param integer points
 * @return void
 */
PTS.set_points = function(input, classname, points) {
    var regexp = new RegExp("^(.*): (-?[0-9]+)$");
    input.each(function(){
        var em = $(this).parent().find("em." + classname).first();
        var oldhtml = em.html();
        var newpoints = parseInt(points);
        if (PTS.pointstype==0) {
            newpoints += parseInt(oldhtml.replace(regexp, "$2"));
        }
        em.html(oldhtml.replace(regexp, "$1: " + newpoints));
    });
}

/**
 * set_rubric_levels
 *
 * @param integer uid
 * @param object  regexp to parse score in user tile
 * @return void, but may update rubric levels
 */
PTS.set_rubric_levels = function(uid, regexp) {

    var input = $("#id_awardto_" + uid);
    if (input.length==0) {
        return false; // shouldn't happen !!
    }

    var total = null;
    for (var cid in PTS.criteriascores) {

        // get score from rubric form
        var score = PTS.get_rubric_form_score(cid);

        // update/extract score in user tile
        score = PTS.get_criterion_score(input, uid, cid, score, regexp);

        // increment total
        if (total===null) {
            total = 0;
        }
        total += parseInt(score);
    }

    // update total score for this user
    if (PTS.showrubrictotal && total !== null) {
        PTS.set_criteria_total(input, regexp, total);
    }
}

/**
 * set_guide_scores
 *
 * @param integer uid
 * @param object  regexp to parse score in user tile
 * @return void, but may update guide scores
 */
PTS.set_guide_scores = function(uid, regexp) {

    var input = $("#id_awardto_" + uid);
    if (input.length==0) {
        return false; // shouldn't happen !!
    }

    var total = null;
    for (var cid in PTS.criteriascores) {

        // get score from advanced grading form
        var score = PTS.get_guide_form_score(cid);

        // update/extract score in user tile
        score = PTS.get_criterion_score(input, uid, cid, score, regexp);

        // increment total
        if (total===null) {
            total = 0;
        }
        total += parseInt(score);
    }

    // set total score for this user
    if (PTS.showguidetotal && total !== null) {
        PTS.set_criteria_total(input, regexp, total);
    }
}

/**
 * set_criteria_total
 *
 * @param object  input element for user tile
 * @param object  regexp to match score in user tile
 * @param integer total of criteria scores
 * @return void, but may update criteria total in user tile
 */
PTS.set_criteria_total = function(input, regexp, total) {
    var em = input.parent().find("em." + PTS.gradingmethod + "total");
    if (em.length) {
        em = em.first();
        var oldhtml = em.html();
        var newhtml = "$1: " + total + "$3";
        em.html(oldhtml.replace(regexp, newhtml));
    }
}

/**
 * get_criterion_score
 *
 * @param object  input element
 * @param integer uid user id
 * @param integer cid criterion id
 * @param integer score from advanced grading form
 * @param object  regexp to match score in user tile
 * @return void, but may update criteria total in user tile
 */
PTS.get_criterion_score = function(input, uid, cid, score, regexp) {

    // input    : the <input> element for the current user
    // em       : the <em> element used to display the criteria score
    // oldhtml  : the inner html used to display the criteria score
    // score    : the value of the rubric criteria score

    var em = input.parent().find("em.criterion-" + cid);
    var oldhtml = '';
    if (em.length) {
        em = em.first();
        oldhtml = em.html();
    }

    // set/get score in criteria score display element
    if (oldhtml) {
        if (score===null) {
            score = oldhtml.replace(regexp, "$2");
        } else {
            var newhtml = "$1: " + score + "$3";
            em.html(oldhtml.replace(regexp, newhtml));
        }
    } else if (score===null) {
        // score was not set in criteria form
        // nor was it available in the display <em>
        // so get it from PTS.usercriteriascores
        score = PTS.get_user_criteria_score(uid, cid);
    } else {
        PTS.set_user_criteria_score(uid, cid, score);
    }

    return score;
}

/**
 * get_rubric_form_score
 *
 * @param integer cid
 * @return integer (or null)
 */
PTS.get_rubric_form_score = function(cid) {
    var name = "advancedgrading[criteria][" + cid + "][levelid]";
    var input = $("input[type=radio][name='" + name + "']:checked");
    if (input.length==0) {
        return null; // no level is selected in form
    }
    var lid = input.first().val();
    return PTS.criteriascores[cid]["levels"][lid] - PTS.criteriascores[cid]["min"];
}

/**
 * get_guide_form_score
 *
 * @param integer cid
 * @return integer (or null)
 */
PTS.get_guide_form_score = function(cid) {
    var name = "advancedgrading[criteria][" + cid + "][score]";
    var input = $("input[type=text][name='" + name + "']");
    if (input.length==0) {
        return null; // shouldn't happen !!
    }
    var score = input.first().val();
    if (score===null || score===false || score==="" || isNaN(score)) {
        return null; // value is missing or invalid
    }
    return score;
}

/**
 * get_user_criteria_score
 *
 * @param integer cid
 * @return integer (or null)
 */
PTS.get_user_criteria_score = function(uid, cid) {
    var score = 0;
    if (uid in PTS.usercriteriascores) {
        if (cid in PTS.usercriteriascores[uid]) {
            score = PTS.usercriteriascores[uid][cid];
            if (PTS.criteriascores[cid]["min"]) {
                score -= PTS.criteriascores[cid]["min"];
            }
        }
    }
    return score;
}

/**
 * set_user_criteria_score
 *
 * @param integer cid
 * @return integer (or null)
 */
PTS.set_user_criteria_score = function(uid, cid, score) {
    if (uid in PTS.usercriteriascores) {
        PTS.usercriteriascores[uid][cid] = parseInt(score);
        return true;
    } else {
        return false;
    }
}

/**
 * set_elm_size_color
 *
 * @param object  elms
 * @param integer w
 * @param integer h
 * @param string display (optional)
 * @return void
 */
PTS.set_elm_size_color = function(elms, w, h, display, debug) {
    elms.each(function(){
        $(this).find("input").css("display", display);
    });
    elms.each(function(){
        w = Math.max($(this).width(), w);
    });
    elms.each(function(){
        $(this).css("min-width", w);
    });
    elms.each(function(){
        h = Math.max($(this).height(), h);
    });
    elms.each(function(){
        $(this).css("min-height", h);
    });
    elms.each(function(){
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
 * @param object usermap
 * @return void
 */
PTS.set_usermap_size = function(usermap) {
    usermap.css("position", "relative");
    var w = $("#id_mapwidth").val();
    var h = $("#id_mapheight").val();
    if (parseInt(w)==0) {
        w = usermap.width();
        if (w > 640) {
            w = 640;
            usermap.css("width", w);
        }
        $("#id_mapwidth").val(w);
        PTS.update_usermap = true;
    }
    if (parseInt(h)==0) {
        h = usermap.height();
        $("#id_mapheight").val(h);
        PTS.update_usermap = true;
    }
}

/**
 * format_mapaction_row
 *
 * param object mapaction_container
 * param object mapaction_buttons
 * param object mapaction_buttons
 * @return void
 */
PTS.format_mapaction_row = function(mapaction_container, mapaction_buttons, points_buttons) {
    if (PTS.mapactionsperrow) {
        PTS.format_buttons_row(PTS.mapactionsperrow, mapaction_container, mapaction_buttons, points_buttons);
    } else {
        var w = 0;
        var w_max = mapaction_container.width();

        var i = 1;
        var i_max = (Math.floor(mapaction_buttons.length / 2) - 1);

        var sortbymenu = $("#id_sortbymenu");
        w += sortbymenu.outerWidth(true);

        var elm = mapaction_buttons.last();
        while (elm && (i < i_max) && ((w + elm.outerWidth(true)) < w_max)) {
            w += elm.outerWidth(true);
            elm = elm.prev();
            i++;
        }

        if (w < mapaction_container.width()) {
            // there is enough room adjust the buttons
            var div = document.createElement("DIV");
            $(div).css("clear", "both");
            elm.before(div);
        }
    }
}

/**
 * format_points_row
 *
 * param object points_container
 * param object points_buttons
 * param object mapaction_buttons
 * @return void
 */
PTS.format_points_row = function(points_container, points_buttons, mapaction_buttons) {
    PTS.format_buttons_row(PTS.pointsperrow, points_container, points_buttons, mapaction_buttons);
}

/**
 * format_buttons_row
 *
 * param integer buttonsperrow
 * param object  container
 * param object  buttons1
 * param object  buttons2
 * @return void
 */
PTS.format_buttons_row = function(buttonsperrow, container, buttons1, buttons2) {
    var l = null;
    var r = null;
    var w = 0; // width
    var i = 0; // index on elms
    if (buttonsperrow) {
        var elms = buttons1;
    } else {
        var elms = buttons2;
    }
    elms.each(function(){
        var p = $(this).position();
        p.right = p.left + $(this).outerWidth(true);
        if (l===null || l > p.left) {
            l = p.left;
        }
        if (r===null || r < p.right) {
            r = p.right;
        }
        if (i < buttonsperrow) {
            i++;
            if (w < (r - l)) {
                w = (r - l);
            }
        }
    });
    if (w==0) {
        w = $("#id_mapwidth").val();
        if (w < (r - l)) {
            w = (r - l);
        }
    }
    container.css("max-width", w + "px");
}

/**
 * set_elm_position
 *
 * @param object elms
 * @return void
 */
PTS.set_elm_position = function(elms) {
    var update = false;
    $(elms.get().reverse()).each(function(){
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
                    PTS.update_usermap = true;
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
 * @param object elms
 * @return void
 */
PTS.set_user_size = function(elms) {
    var widths  = []; // width => frequency
    var heights = []; // height => frequency
    elms.each(function(){
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
        PTS.update_usermap = true;
    }
    if (userheight != $("#id_userheight").val()) {
        $("#id_userheight").val(userheight);
        PTS.update_usermap = true;
    }
}

/**
 * set_map_handle_size
 *
 * @return void
 */
 PTS.set_map_handle_size = function() {
    if ("ontouchstart" in document.documentElement) {
        $("div.ui-resizable-n").css({
            "margin-left-width"   : "-9px",
            "border-left-width"   :  "9px",
            "border-right-width"  :  "9px",
            "border-bottom-width" : "15px"
        });
        $("div.ui-resizable-e").css({
            "margin-top-width"    : "-9px",
            "border-top-width"    :  "9px",
            "border-bottom-width" :  "9px",
            "border-left-width"   : "15px"
        });
        $("div.ui-resizable-s").css({
            "margin-left-width"   : "-9px",
            "border-left-width"   :  "9px",
            "border-right-width"  :  "9px",
            "border-top-width"    : "15px"
        });
        $("div.ui-resizable-w").css({
            "margin-top-width"    : "-9px",
            "border-top-width"    :  "9px",
            "border-bottom-width" :  "9px",
            "border-right-width"  : "15px"
        });
        $("div.ui-resizable-nw, " +
          "div.ui-resizable-ne, " +
          "div.ui-resizable-sw, " +
          "div.ui-resizable-se").css({
            "height" : "15px",
            "width"  : "15px"
        });
    }
}

PTS.get_mapmode_buttons = function() {
    return PTS.mapmode_container + " > " + PTS.group_element_tag + " > input[name=mapmode]";
}

PTS.get_mapmode_value = function() {
    return $(PTS.get_mapmode_buttons() + ":checked").val();
}

PTS.set_mapmode_value = function(value) {
    if (PTS.get_mapmode_value() != value) {
        $(PTS.get_mapmode_buttons()).val([value]);
        $(PTS.get_mapmode_buttons() + "[value=" + value + "]").click();
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
 * set_elm_event_handlers
 *
 * @param object   elms
 * @param function onclick, additional onclick function, or FALSE
 * @return void
 */
PTS.set_elm_event_handlers = function(elms, onclick, ondblclick) {
    elms.each(function(){

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

            var toggle_checked = false;
            var toggle_selected = false;
            var trigger_onclick = false;

            if (PTS.theme_type==PTS.THEME_TYPE_SPAN) {

                // Moodle <= 3.1 theme
                // <span>
                //   <input ... >
                //   <label ... >
                //     <img ... >
                //     <em.name ... >
                //     <em.pointstotal ... >

                if (event.target.nodeName=="INPUT") {
                    var nodeName = "INPUT";
                } else if ($(event.target).closest("label").length) {
                    var nodeName = "LABEL"; // contains IMG and EM
                } else {
                    var nodeName = "SPAN"; // contains INPUT and LABEL
                }

                if ($(this).hasClass("ui-selectee")) {
                    toggle_checked = (nodeName=="SPAN" && PTS.is_mapmode_award(event));
                    toggle_selected = PTS.is_mapmode_select(event);
                    trigger_onclick = (! toggle_selected);
                } else {
                    toggle_checked = (nodeName=="SPAN");
                    trigger_onclick = true;
                    toggle_selected = false;
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

            } else {

                // template theme in Moodle >= 3.2 e.g. "Boost"
                // <label>
                //   <input ... >
                //   <img ... >
                //   <em.name ... >
                //   <em.pointstotal ... >

                if (event.target.nodeName=="INPUT") {
                    var nodeName = "INPUT";
                } else {
                    var nodeName = "LABEL"; // contains INPUT, IMG and EM
                }

                if (nodeName=="INPUT" ) {
                    if ($(this).closest("label").hasClass("ui-selectee")) {
                        toggle_checked = PTS.is_mapmode_award(event);
                        toggle_selected = PTS.is_mapmode_select(event);
                    } else {
                        toggle_checked = true;
                        toggle_selected = false;
                    }
                    trigger_onclick = true;
                }

                if (toggle_selected) {
                    $(this).closest("label").toggleClass("ui-selected");
                }
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

    var elm = input.parent();
    switch (true) {

        case PTS.is_mapmode_award(event):
            if (input.prop("checked")) {
                if (elm.hasClass("ui-selected")) {
                    // do onclick for ALL selected users
                    var selected = elm.siblings(".ui-selected");

                    var checked = (input.prop("type")=="checkbox");
                    selected.addClass("checked");
                    selected.find("input").prop("checked", checked);

                    var notselected = elm.siblings(":not(.ui-selected)");
                    notselected.removeClass("checked");
                    notselected.find("input").prop("checked", false);

                    if (selected.addBack) { // jQuery >= 1.8
                        input = selected.addBack().find("input");
                    } else {
                        input = selected.andSelf().find("input");
                    }
                } else {
                    // do onclick for ONE single item
                    // that is not currently selected
                    var selected = elm.siblings(".ui-selected");
                    selected.removeClass("ui-selected");
                }
                if (PTS.sendimmediately) {
                    PTS.send_points_via_ajax(input);
                }
            }
            break;

        case PTS.is_mapmode_absent(event):
            elm.toggleClass("absent");
            break;

        case PTS.is_mapmode_report(event):
            var userid = PTS.get_input_userid(input.first())
            if (userid) {
                PTS.set_feedback(PTS.contacting_server_msg);
                $.ajax({
                    "cache"   : false,
                    "data"    : {"ajax"    : 1,
                                 "userid"  : userid,
                                 "sesskey" : PTS.sesskey},
                    "datatype": "html",
                    "method"  : "post",
                    "url"     : PTS.reportpoints_ajax_php
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
                    report.dialog("option", "position", {"my" : "left top",
                                                         "at" : "left top",
                                                         "of" : elm});

                    // get clean user's name
                    var name = elm.find("em.name").html();
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
 * hide/show name fields
 *
 * @return void
 */
PTS.hideshow_name_fields = function() {

    $("#id_newlinetoken").each(function(){
        $(this).change(function(evt){
            $(this).nextAll().remove();
            var txt = '';
            switch ($(this).val()) {
                case '': txt = PTS.str.newlineempty; break;
                case ' ':
                case '\u3000': txt = PTS.str.newlinespace; break;
            }
            if (txt) {
                var elm = document.createElement('SMALL');
                txt = document.createTextNode(' ' + txt);
                elm.appendChild(txt);
                $(this).after(elm);
            }
        });
        $(this).trigger("change");
    });

    // set URL of the first available help icon
    // this will be used to generate URLs for other images
    var helpiconurl = $("img.iconhelp").first().prop("src");
    if (helpiconurl==null || helpiconurl=='') {
        // templatable themes (e.g. Boost) don't have help icons, so extract from URL
        helpiconurl = location.href.replace(new RegExp("^(.*?)/mod/assign.*$"), "$1");
        helpiconurl += "/pix/help.png";
    }

    // add hide/show toggle functionality to "name" settings
    // e.g. name="nametokens[0][field]" id="id_nametokens_0_field"
    $("select[id^=id_nametokens_][id$=_field]").each(function(){

        // create new IMG element
        var img = document.createElement("IMG");
        img.src = helpiconurl.replace('help', "t/switch_minus");
        img.title = PTS.str.showless;

        // add IMG click event handler
        $(img).click(function(evt){
            if (PTS.theme_type==PTS.THEME_TYPE_SPAN) {
                // Moodle <= 3.1
                var elm = $(this);
            } else {
                // Moodle >= 3.2
                // template theme e.g. "Boost"
                var elm = $(this).closest("div");
            }
            var src = $(this).prop("src");
            if (src.indexOf("minus") >= 0) {
                $(this).prop("src", src.replace("minus", "plus"));
                $(this).prop("title", PTS.str.showmore);
                elm.nextAll().hide("fast").removeClass("showme").addClass("hideme");
            } else {
                $(this).prop("src", src.replace("plus", "minus"));
                $(this).prop("title", PTS.str.showless);
                elm.nextAll().removeClass("hideme").addClass("showme").show("slow");
            }
        });

        // append IMG (and some white space)
        $(this).after(img);
        $(this).after(document.createTextNode(" "));

        // initially, we hide the name settings
        if (PTS.theme_type==PTS.THEME_TYPE_SPAN) {
            $(img).trigger('click');
        } else {
            // in Boost et al. we must wait
            // until after fields have been disabled
            setTimeout(function(){$(img).trigger('click');}, 2000);
        }
    });
}

/**
 * document ready
 *
 * @return void
 */
$(document).ready(function() {
    PTS.hideshow_name_fields();

    var mapaction_container = $(PTS.mapaction_container);
    var mapmode_container   = $(PTS.mapmode_container);
    var user_container      = $(PTS.user_container);
    var points_container    = $(PTS.points_container);

    var mapaction_buttons = mapaction_container.children(PTS.group_element_tag);
    var mapmode_buttons   = mapmode_container.children(PTS.group_element_tag);
    var user_tiles        = user_container.children(PTS.group_element_tag);
    var points_buttons    = points_container.children(PTS.group_element_tag);

    // this flag will be set to true
    // if the user map changes size
    // or the users change position
    PTS.update_usermap = false;

    PTS.set_feedback_visibility();
    PTS.set_usermap_size(user_container);

    PTS.set_elm_size_color(mapaction_buttons, PTS.mapaction_min_width, PTS.mapaction_min_height, "none");
    PTS.set_elm_size_color(mapmode_buttons,   PTS.mapmode_min_width,   PTS.mapmode_min_height,   "none");
    PTS.set_elm_size_color(user_tiles,        PTS.user_min_width,      PTS.user_min_height,      PTS.elementdisplay);
    PTS.set_elm_size_color(points_buttons,    PTS.points_min_width,    PTS.points_min_height,    "none", "debug");

    PTS.set_user_size(user_tiles);
    PTS.set_elm_position(user_tiles);

    PTS.format_mapaction_row(mapaction_container, mapaction_buttons, points_buttons);
    PTS.format_points_row(points_container, points_buttons, mapaction_buttons);

    PTS.set_elm_event_handlers(mapaction_buttons, PTS.do_map_action);
    PTS.set_elm_event_handlers(mapmode_buttons,   false);
    PTS.set_elm_event_handlers(user_tiles,      PTS.do_user_click);
    PTS.set_elm_event_handlers(points_buttons,    false);

    // send new map settings to server
    // if PTS.update_usermap was set to TRUE
    PTS.update_usermap_via_ajax();

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

    user_tiles.draggable({
        "containment" : PTS.user_container,
        "scroll" : true,
        "stack" : PTS.group_element_tag,
        "start" : function(event, ui) {
            $(this).addClass("ui-dragging");
            if ($(this).hasClass("ui-selected")){
                PTS.selected = $(PTS.group_element_tag + ".ui-selected").each(function(){
                    $(this).data("offset", $(this).offset());
                });
            } else {
                PTS.selected = $([]);
                $(this).siblings().removeClass("ui-selected");
            }
            PTS.offset = $(this).offset();
        },
        "drag" : function(event, ui) {
            var dt = (ui.position.top - PTS.offset.top);
            var dl = (ui.position.left - PTS.offset.left);
            // adjust the offset of all selected elements
            // except $(this) - the element being dragged
            PTS.selected.not(this).each(function(){
                var offset = $(this).data("offset");
                $(this).css({"top"  : offset.top + dt,
                             "left" : offset.left + dl});
            });
        },
        "stop" : function(event, ui) {
            PTS.selected.not(this).each(function(){
                PTS.set_awardto_xy(this);
            });
            PTS.set_awardto_xy(this);
            PTS.update_usermap_via_ajax();
        }
    });

    // make the user container selectable
    user_container.selectable({
        "cancel"   : "div.ui-resizable-handle,input",
        "disabled" : (PTS.allowselectable ? false : true),
        "filter"   : PTS.group_element_tag
    });

    var handles = {"e" : "egrip",
                   "se": "segrip",
                   "s" : "sgrip"};
    for (var h in handles) {
        var options = {
            "class": "ui-resizable-handle ui-resizable-" + h,
            "id": handles[h]
        };
        $("<div/>", options).appendTo(PTS.user_container);
        handles[h] = "#" + handles[h];
    }

    user_container.resizable({
        "handles" : handles,
        "create"  : function(event, ui) {
            var w = $("#id_mapwidth").val();
            var h = $("#id_mapheight").val();
            user_container.css("width", w);
            user_container.css("height", h);
        },
        // restrict the max/min size of the user-map (DISABLED)
        //start: function(event, ui) {
        //    var w = 0;
        //    var h = 0;
        //    $(this).find(PTS.group_element_tag).each(function(){
        //        var p = $(this).position();
        //        w = Math.max(w, p.left + $(this).width());
        //        h = Math.max(h, p.top + $(this).height());
        //    });
        //    $(this).resizable("option", "minWidth", w + 24);
        //    $(this).resizable("option", "minHeight", h + 14);
        //},
        "stop" : function(event, ui) {
            var w = parseInt(user_container.css("width"));
            if (w != $("#id_mapwidth").val()) {
                $("#id_mapwidth").val(w);
                PTS.update_usermap = true;
            }
            var h = parseInt(user_container.css("height"));
            if (h != $("#id_mapheight").val()) {
                $("#id_mapheight").val(h);
                PTS.update_usermap = true;
            }
            PTS.update_usermap_via_ajax();
        }
    });
    PTS.set_map_handle_size();

    // adjust CSS for input elements in layouts_container
    var input = $(PTS.layouts_container + " input[class=indent]");
    input.parent().css({"display" : "inline-block", "min-width" : "140px"});

    var input = $(PTS.layouts_container + " input[name=layouts]");
    input.parent().css({"display" : "inline-block", "min-width" : "76px"});

    // create the report.dialog()
    $(PTS.report_container).dialog({
        "autoOpen" : false,
        "width"    : "auto"
    });

    // remove the "report" section header
    $("#id_report_hdr").remove();

    // cache for standard width of TEXT input elements
    PTS.minwidths = new Array();

    // adjust width of newline token text input elements
    var input = $("input[name=nameformat], " +
                  "input[name=newlinetoken], " +
                  "input[name^=nametokens][name$='[token]']");
    input.each(function(){
        $(this).keyup(function(){
            var minwidth = 0;
            var size = $(this).attr("size");
            if (size) {
                if (size in PTS.minwidths) {
                    minwidth = PTS.minwidths[size];
                } else {
                    var elm = document.createElement("INPUT");
                    $(elm).attr("size", size);
                    $(elm).css("width", "auto");
                    $(elm).hide().appendTo("BODY");
                    minwidth = $(elm).outerWidth();
                    $(elm).remove();
                    PTS.minwidths[size] = minwidth;
                }
            }
            var value = $(this).val();
            var txt = document.createTextNode(value);
            var elm = document.createElement("SPAN");
            $(elm).append(txt).hide().appendTo("BODY");
            var w = $(elm).width();
            $(elm).remove();
            if (w < minwidth) {
                w = minwidth;
            }
            $(this).width(w);
        });
        $(this).triggerHandler("keyup");
    });

    var input = $("#id_names_hdr [name^=nametokens]");
    input = input.not("[name$='[token]']");
    input = input.not("[name$='[field]']");
    input = input.not("[name$=add]");
    input.each(function(){
        $(this).change(function(){
            var v = $(this).val();
            if (v===null || v===false || v===0 || v==="" || v==="0") {
                $(this).addClass("inactive").removeClass("active");
            } else {
                $(this).addClass("active").removeClass("inactive");
            }
        });
        $(this).triggerHandler("change");
    });

    if (PTS.moodletheme=="essential") {
        $("#assignfeedback_points_award_points_form #fgroup_id_buttonar").css("position", "static");
    }

    // add reset buttons to Advanced grading form
    if (PTS.gradingmethod && PTS.showresetbuttons) {
        var resetcriterion = function(event){
            var tr = $(this).closest("tr");
            tr.find("input[type=radio]:checked").prop("checked", false);
            tr.find("td.checked").removeClass("checked");
            tr.find("textarea, input[type=text]").val("");
            event.preventDefault();
            event.stopPropagation();
        };
        $("#advancedgrading-criteria tr.criterion").each(function(){
            var txt = document.createTextNode(PTS.str.reset);
            var btn = document.createElement("BUTTON");
            btn.appendChild(txt);
            $(btn).click(resetcriterion);
            $(btn).addClass("criteria-reset-button");
            var td = document.createElement("TD");
            $(td).addClass("criteria-reset-cell");
            td.appendChild(btn);
            this.appendChild(td);
        });
    }

    // shorten scores in Rubric form, e.g. "10 points", by removing text, e.g. "points"
    if (PTS.gradingmethod=="rubric" && PTS.showrubricformscores==PTS.GRADINGTEXT_SHORTEN) {
        $("#advancedgrading-criteria span.scorevalue").parent().contents().filter(function(){
            return this.nodeType==3
        }).remove();
    }
});
