M.block_course_overview_lite = {};

M.block_course_overview_lite.init = function(Y) {
    Y.use('io', function(Y) {
        M.block_course_overview_lite.Y = Y;
        Y.on('available', M.block_course_overview_lite.ajax.ajaxLoad, '#ajaxcourse');
        Y.one('#course_overview_lite_show_detailed').on('click', M.block_course_overview_lite.overview.show);
        Y.one('#course_overview_lite_hide_detailed').on('click', M.block_course_overview_lite.overview.hide);
    });
};

M.block_course_overview_lite.add_handles = function(Y) {
    M.block_course_overview_lite.Y = Y;

    Y.one('#course_list').all('.hide_course').each(function(node){
        node.on('click', M.block_course_overview_lite.hide);
    });

    YUI().use('dd-constrain', 'dd-proxy', 'dd-drop', 'dd-plugin', function(Y) {
        //Static Vars
        var goingUp = false, lastY = 0;

        var list = Y.Node.all('#course_list .coursebox');
        list.each(function(v, k) {
            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: '#course_list'
            });
            dd.addHandle('.course_title .move');
        });

        var drops = Y.Node.all('#coursebox');
        drops.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });

        Y.DD.DDM.on('drag:start', function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').addClass('block_course_overview_lite');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        });

        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            //Put our styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
            M.block_course_overview_lite.save(Y);
        });

        Y.DD.DDM.on('drag:drag', function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < lastY) {
                //We are going up
                goingUp = true;
            } else {
                //We are going down.
                goingUp = false;
            }
            //Cache for next check
            lastY = y;
        });

        Y.DD.DDM.on('drop:over', function(e) {
            //Get a reference to our drag and drop nodes
            var drag = e.drag.get('node'),
                drop = e.drop.get('node');

            //Are we dropping on a li node?
            if (drop.hasClass('coursebox')) {
                //Are we not going up?
                if (!goingUp) {
                    drop = drop.get('nextSibling');
                }
                //Add the node to this list
                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                //Resize this nodes shim, so we can drop on it later.
                e.drop.sizeShim();
            }
        });

        Y.DD.DDM.on('drag:drophit', function(e) {
            var drop = e.drop.get('node'),
                drag = e.drag.get('node');

            //if we are not on an li, we must have been dropped on a ul
            if (!drop.hasClass('coursebox')) {
                if (!drop.contains(drag)) {
                    drop.appendChild(drag);
                }
            }
        });
    });
};

M.block_course_overview_lite.save = function() {
    var Y = M.block_course_overview_lite.Y;
    var sortorder = Y.one('#course_list').get('children').getAttribute('id');
    for (var i = 0; i < sortorder.length; i++) {
        sortorder[i] = sortorder[i].substring(7);
    }
    var params = {
        sesskey : M.cfg.sesskey,
        sortorder : sortorder
    };
    Y.io(M.cfg.wwwroot + '/blocks/course_overview_lite/save.php', {
        method: 'POST',
        data: build_querystring(params),
        context: this
    });
};

M.block_course_overview_lite.hide = function(e) {
    var Y = M.block_course_overview_lite.Y,
        id = this.get("id"),
        params = {
            sesskey : M.cfg.sesskey,
            toggle_hidden : id
        };
    Y.io(M.cfg.wwwroot + '/blocks/course_overview_lite/save_hide.php', {
        method: 'POST',
        data: build_querystring(params),
        context: this,
        on: {
           complete: function() {

               var parent = Y.one('.block_course_overview_lite #course_list #course-' + id);
               var img = this.one('img');
               if (parent.hasClass('userhidden')) {
                   img.set('src',  M.util.image_url('i/hide', 'moodle'));
               } else {
                   img.set('src',  M.util.image_url('i/show', 'moodle'));
               }
               parent.toggleClass('userhidden');
           }
        }
    });
};

M.block_course_overview_lite.overview = {
    loaded: false,
    hide: function(e) {
        var Y = M.block_course_overview_lite.Y;
        Y.one('#course_overview_lite_show_detailed').removeClass('selected');
        Y.one('#course_overview_lite_hide_detailed').addClass('selected');
        Y.all('.block_course_overview_lite .activity_info').each(function(node) {
            node.addClass('hidden_overview');
        });
    },
    show: function(e) {
        var Y = M.block_course_overview_lite.Y;
        Y.one('#course_overview_lite_show_detailed').addClass('selected');
        Y.one('#course_overview_lite_hide_detailed').removeClass('selected');
        Y.all('.block_course_overview_lite .activity_info').each(function(node) {
            node.removeClass('hidden_overview');
        });
        if (M.block_course_overview_lite.overview.loaded === false) {
            M.block_course_overview_lite.overview.ajaxLoad(e);
        }
    },
    ajaxLoad: function(e) {
        var Y = M.block_course_overview_lite.Y,
            params = {
                sesskey : M.cfg.sesskey
            };
        Y.io(M.cfg.wwwroot + '/blocks/course_overview_lite/getoverviews.php', {
            method:'GET',
            data:  build_querystring(params),
            on: {
                complete:  M.block_course_overview_lite.overview.ajaxProcessResponse
            }
        });
        return true;
    },
    ajaxProcessResponse: function(e, outcome) {
        var Y = M.block_course_overview_lite.Y;
        if (outcome.status == 200) {
            try {
                YUI().use('json-parse', 'json-stringify', function (Y) {
                    var object = Y.JSON.parse(outcome.responseText);
                    if (object.hasOwnProperty('error')) {
                       object = {};
                    } else {
                        M.block_course_overview_lite.overview.loaded = true;
                    }
                    M.block_course_overview_lite.renderer.loadOverviews(object);
                });
            } catch (ex) {
                // If we got here then there was an error parsing the result
                Y.all('.block_course_overview_lite .activity_info').each(function(node) {
                    node.addClass('hidden_overview');
                });
                M.block_course_overview_lite.overview.loaded = false;
            }
        } else {
            M.block_course_overview_lite.overview.loaded = false;
        }
        return true;
    }
};

M.block_course_overview_lite.ajax = {
    ajaxLoad: function(e) {
        var Y = M.block_course_overview_lite.Y,
            editMode = Y.one('#course_list').hasClass('ajax-edit'),
            params = {
                sesskey : M.cfg.sesskey,
                edit: editMode
            };
        Y.io(M.cfg.wwwroot + '/blocks/course_overview_lite/getcourses.php', {
            method:'GET',
            data:  build_querystring(params),
            on: {
                complete:  M.block_course_overview_lite.ajax.ajaxProcessResponse
            }
        });
        return true;
    },
    ajaxProcessResponse: function(e, outcome) {
        var Y = M.block_course_overview_lite.Y;
        if (outcome.status == 200) {
            try {
                YUI().use('json-parse', 'json-stringify', function (Y) {
                    var object = Y.JSON.parse(outcome.responseText);
                    M.block_course_overview_lite.renderer.loadCourses(object);
                });
            } catch (ex) {
                // If we got here then there was an error parsing the result
                Y.one('#course_list').setHTML("Error parsing courses.");
            }
        } else {
            Y.one('#course_list').setHTML("Error loading courses.");
        }
        return true;
    }
};

M.block_course_overview_lite.renderer = {

    drawCourse: function(course, edit) {
        var move = Y.Node.create('<div class="move">' + '<img src="' + M.util.image_url('i/move_2d', 'moodle') + '" ' +
                'class="cursor" alt="' + M.str.moodle.move + '" title="' + M.str.moodle.move + '"/></div>');
        var img = course.userhidden ? 'i/show' : 'i/hide';
        var hide = Y.Node.create('<div class="hide_course" id="' + course.id + '">' + '<img src="' + M.util.image_url(img, 'moodle') + '" ' +
            'class="hide_icon" alt="' + M.str.block_course_overview_lite.hide_icon_alt + '" align="right"' +
            ' title="' + M.str.block_course_overview_lite.hide_icon_alt + '"/></div>');
        var box = Y.Node.create('<div id="course-' + course.id + '" class="box coursebox ' + (course.userhidden ? 'userhidden' : '') + '"></div>');
        var title = Y.Node.create('<div class="course_title"></div>');
        var link = Y.Node.create('<h3 class="title"><a href="' + course.url + '" title="' + course.fullname + '">' + course.fullname + '</a></h3>');
        if (edit) {
            title.appendChild(move);
            title.appendChild(hide);
        }
        title.appendChild(link);
        title.appendChild(Y.Node.create('<div class="box flush"></div>'));
        box.appendChild(title);
        box.appendChild(Y.Node.create('<div class="activity_info hidden_overview" id="activity-overview-' + course.id + '">' +
            '<img src="' + M.util.image_url('i/loading_small', 'moodle') + '"/></div>'));
        box.appendChild(Y.Node.create('<div class="box flush"></div>'));
        if (course.current) { box.addClass('currentcourse'); }
        if (course.hidden) { link.addClass('dimmed_text'); }
        Y.one('#course_list').appendChild(box);
        return true;
    },

    loadCourses: function(object) {
        var Y = M.block_course_overview_lite.Y;
        var edit = Y.one('#ajaxcourse').hasClass('ajax-edit');
        YUI().use('node', function(Y) {

            Y.one('#course_list').setHTML('');
            for (var key in object) {
                if (object.hasOwnProperty(key)) {
                    var course = object[key];
                    if (course.userhidden && !edit) {
                        continue;
                    }
                    M.block_course_overview_lite.renderer.drawCourse(course, edit);
                }
            }
        });
        if (edit) {
            M.block_course_overview_lite.add_handles(Y);
        }
        return true;
    },

    loadOverviews: function(object) {
        var Y = M.block_course_overview_lite.Y;
        Y.all('.activity_info').each(function(node){
            node.setHTML('');
        });
        for (var key in object) {
            if (object.hasOwnProperty(key)) {
                var overviews = object[key];
                Y.one('#activity-overview-' + key).setHTML(overviews)
            }
        }
    }
};

M.block_course_overview_lite.userpref = false;
