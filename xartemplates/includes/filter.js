/**
 *
 * Some javascript functions for use with the xar:categories-filter tag
 *
 */

/**
 * Set the cookie configuration for the category filter
 */
var xar_categories_filtername = 'catfilter';
var xar_categories_filterpath = '';

function xar_categories_setfilterconfig(name,path) {
    if (name) {
        xar_categories_filtername = name;
    }
    if (path) {
        xar_categories_filterpath = '; path=' + path;
    }
    return true;
}

/**
 * Get the current category filter from the cookie
 */
function xar_categories_getfilter() {
    var cookie = document.cookie.split('; ');

    var cidlist = new Array();
    for (var i=0; i < cookie.length; i++) {
        var crumb = cookie[i].split('=');
        if (xar_categories_filtername == crumb[0] && crumb[1]) {
            cidlist = crumb[1].split(':');
        }
    }

    return cidlist;
}

/**
 * Add a category id to the category filter
 */
function xar_categories_addfilter(cid) {
    var cidlist = xar_categories_getfilter();

    for (var j=0; j<cidlist.length; j++) {
        if (cidlist[j] == cid) {
            return true;
        }
    }
    cidlist[cidlist.length] = cid;
    document.cookie = xar_categories_filtername + '=' + cidlist.join(':') + xar_categories_filterpath;
    return true;
}

/**
 * Delete a category id from the category filter
 */
function xar_categories_delfilter(cid) {
    var cidlist = xar_categories_getfilter();
    var newcids = new Array();

    for (var j=0; j<cidlist.length; j++) {
        if (cidlist[j] != cid) {
            newcids[newcids.length] = cidlist[j];
        }
    }
    document.cookie = xar_categories_filtername + '=' + newcids.join(':') + xar_categories_filterpath;
    return true;
}

/**
 * Set the current category filter based on the cidlist
 */
function xar_categories_setfilter(cidlist) {
    if (cidlist) {
        document.cookie = xar_categories_filtername + '=' + cidlist.join(':') + xar_categories_filterpath;
    } else {
        document.cookie = xar_categories_filtername + '=' + xar_categories_filterpath;
    }
    return true;
}

/**
 *
 * The 'tree' category filter will update the category filter on the fly
 *
 */

/**
 * Toggle the category filter ON/OFF for a category id
 */
function xar_categories_togglefilter(cid, elem, on_str, off_str, doreload) {
    var cidlist = xar_categories_getfilter();
    var newcids = new Array();
    var found = 0;

    for (var j=0; j<cidlist.length; j++) {
        if (cidlist[j] == cid) {
            found = 1;
        } else {
            newcids[newcids.length] = cidlist[j];
        }
    }
    if (found) {
        document.cookie = xar_categories_filtername + '=' + newcids.join(':') + xar_categories_filterpath;
        if (document.getElementById(elem) != undefined)
        {
            document.getElementById(elem).innerHTML = off_str;
        }
    } else {
        cidlist[cidlist.length] = cid;
        document.cookie = xar_categories_filtername + '=' + cidlist.join(':') + xar_categories_filterpath;
        if (document.getElementById(elem) != undefined)
        {
            document.getElementById(elem).innerHTML = on_str;
        }
    }
    // reload the page
    if (doreload) {
        window.location.reload();
    }
    return true;
}

/**
 * Clear the category filter
 */
function xar_categories_clearfilter(tagtype, elemstart, on_str, off_str, doreload) {
    document.cookie = xar_categories_filtername + '=' + xar_categories_filterpath;
    // reload the page
    if (doreload) {
        window.location.reload();
    } else {
        // find all tagtype elements starting with elemstart
        var elem = document.getElementsByTagName(tagtype);
        var len = elem.length;
        for (var i = 0; i < len; i++) {
            var elemid = elem[i].getAttribute('id');
            if (elemid == undefined) {
                continue;
            }
            // and set them OFF
            if (elemid.substring(0,elemstart.length) == elemstart) {
                if (elem[i].innerHTML == on_str) {
                    elem[i].innerHTML = off_str;
                }
            }
        }
    }
    return true;
}

/**
 *
 * The 'form' category filter relies on the following ids for the input fields :
 *
 *   <selid>       : select containing the base categories
 *   <selid>_<cid> : select containing the child categories for base category <cid>
 *   <selid>_list  : select containing the list of selected categories
 *   <selid>_save  : submit button
 *
 */

/**
 * Show the right select box for child categories depending on the selected base category
 */
function xar_categories_showselectfilter(selid) {
    var selobj = document.getElementById(selid);
    if (selobj == undefined) {
        return true;
    }
    var idx = selobj.selectedIndex;
    for (var i = 0; i < selobj.options.length; i++) {
        var val = selobj.options[i].value;
        if (val < 1) {
            continue;
        }
        var subselobj = document.getElementById(selid+'_'+val);
        if (subselobj == undefined) {
            continue;
        }
        if (i == idx) {
            subselobj.style.display = 'inline';
        } else {
            subselobj.style.display = 'none';
        }
    }
    return true;
}


/**
 * Add a selected child category to the category filter
 */
function xar_categories_addselectfilter(selid, doreload) {
    var selobj = document.getElementById(selid);
    if (selobj == undefined) {
        return true;
    }
    var idx = selobj.selectedIndex;
    if (idx < 0) {
        return true;
    }
    var val = selobj.options[idx].value;
    if (val < 1) {
        return true;
    }
    var subselobj = document.getElementById(selid+'_'+val);
    if (subselobj == undefined) {
        return true;
    }
    var subidx = subselobj.selectedIndex;
    if (subidx < 0) {
        // if no child category is selected, we will add the base category here
        var subval = val;
        var subtext = selobj.options[idx].innerHTML;
    } else {
        var subval = subselobj.options[subidx].value;
        if (subval < 1) {
            // if no child category is selected, we will add the base category here
            var subval = val;
            var subtext = selobj.options[idx].innerHTML;
        } else {
            // we will add the child category here
            var subtext = subselobj.options[subidx].innerHTML;
        }
    }
    // add the selected category to the filter and reload
    if (doreload) {
        xar_categories_addfilter(subval);
        window.location.reload();
    }
    // add the selected category to the list
    var listselobj = document.getElementById(selid+'_list');
    if (listselobj == undefined) {
        return true;
    }
    var found = 0;
    for (var i = 0; i < listselobj.options.length; i++) {
        var listval = listselobj.options[i].value;
        if (listval == subval) {
            return true;
        }
    }
    listselobj.options[listselobj.options.length] = new Option(subtext,subval);
    // enable the Save button
    var saveselobj = document.getElementById(selid+'_save');
    if (saveselobj == undefined) {
        return true;
    }
    if (saveselobj.disabled) {
        saveselobj.disabled = false;
    }
    return true;
}

/**
 * Delete a selected category from the category filter
 */
function xar_categories_delselectfilter(selid, doreload) {
    var listselobj = document.getElementById(selid+'_list');
    if (listselobj == undefined) {
        return true;
    }
    var listidx = listselobj.selectedIndex;
    if (listidx < 0) {
        return true;
    }
    var listval = listselobj.options[listidx].value;
    if (listval < 1) {
        return true;
    }
    // delete the selected category from the filter and reload
    if (doreload) {
        xar_categories_delfilter(listval);
        window.location.reload();
    }
    // remove the selected category from the list
    listselobj.options[listidx] = null;
    // enable the Save button
    var saveselobj = document.getElementById(selid+'_save');
    if (saveselobj == undefined) {
        return true;
    }
    if (saveselobj.disabled) {
        saveselobj.disabled = false;
    }
    return true;
}

/**
 * Save the categories list to the category filter
 */
function xar_categories_saveselectfilter(selid, doreload) {
    var listselobj = document.getElementById(selid+'_list');
    if (listselobj == undefined) {
        return true;
    }
    var cidlist = new Array();
    for (var i = 0; i < listselobj.options.length; i++) {
        var listval = listselobj.options[i].value;
        if (listval > 0) {
            cidlist[cidlist.length] = listval;
        }
    }
    // always set the category filter here
    xar_categories_setfilter(cidlist);
    // reload the page
    if (doreload) {
        window.location.reload();
    }
    return true;
}

