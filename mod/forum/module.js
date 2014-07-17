M.mod_forum = {};

// This function adds the 'show related discussions' link and list on every discussion.
M.mod_forum.show_related_discussions = function(Y, related_discussions) {
    var mainsection = Y.one('#page-mod-forum-discuss #region-main');

    mainsection.append('<h3>' + M.str.mod_forum.related_discussions + '</a></h3>');

    var related_list = '<ul id="relateddiscussions">';
    for (var i in related_discussions) {
        discussion = related_discussions[i];
        related_list += '<li><a href="' +M.cfg.wwwroot + discussion.link + '">'+discussion.name +'</a></li>';
    }
    related_list += '</ul>';
    mainsection.append(related_list);
}
