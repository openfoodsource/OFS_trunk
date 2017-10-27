/**
 * Extending jQuery with autocomplete
 * Version: 1.0
 */
(function($) {

 var RETURN = 13;
 var TAB = 9;
 var ESC = 27;
 var ARRUP = 38;
 var ARRDN = 40;

function getCaretPosition(obj){
  var start = -1;
  var end = -1;
  if(typeof obj.selectionStart != "undefined"){
    start = obj.selectionStart;
    end = obj.selectionEnd;
  }
  else if(document.selection&&document.selection.createRange){
    var M=document.selection.createRange();
    var Lp;
    try{
      Lp = M.duplicate();
      Lp.moveToElementText(obj);
    }catch(e){
      Lp=obj.createTextRange();
    }
    Lp.setEndPoint("EndToStart",M);
    start=Lp.text.length;
    if(start>obj.value.length)
      start = -1;
    
    Lp.setEndPoint("EndToStart",M);
    end=Lp.text.length;
    if(end>obj.value.length)
      end = -1;
  }
  return {'start':start,'end':end};
}
function setCaret(obj,l){
  obj.focus();
  if (obj.setSelectionRange){
    obj.setSelectionRange(l,l);
  }
  else if(obj.createTextRange){
    m = obj.createTextRange();      
    m.moveStart('character',l);
    m.collapse();
    m.select();
  }
}

$.fn.autocomplete =
  function(options){
    if(!options && (!$.isFunction(options.get) || !options.ajax_get)){
       return $(this);
    }
    // options
    options = $.extend({ 
                         delay     : 500 ,
                         timeout   : 5000 ,
                         minchars  : 3 ,
                         multi     : false ,
                         cache     : true , 
                         height    : 150 ,
                         noresults : 'No results'
                         },
                       options);
    // take me
    var me = $(this);
    var me_this = $(this).get(0);
    // bind key events
    me.keypress(function(ev){
      var key = ev.which;
      switch(key){
        case RETURN:
          if(!current_highlight) getSuggestions(getUserInput());
          else setHighlightedValue();
          return false;
        case ESC:
          clearSuggestions();
          return false;
      }
      return true;
    });
    
    me.keyup(function(ev){
      var key = ev.which;
      // set responses to keydown events in the field
      // this allows the user to use the arrow keys to scroll through the results
      switch(key){
        case ESC: case RETURN: return false;
        case ARRUP:
          changeHighlight(key);
          return false;
        case ARRDN:
          changeHighlight(key);
          return false;
        default:
          getSuggestions(getUserInput());
      }
      return true;
    });

    // no autocomplete!
    me.attr("autocomplete","off");

    // init variables
    var user_input = "";
    var input_chars_size  = 0;
    var suggestions = [];
    var current_highlight = 0;
    var suggestions_menu = null;
    var suggestions_list = null;
    var clearSuggestionsTimer = false;
    var ajaxTimer = false;

    // get user input
    function getUserInput(){
      var val = me.val();
      if(options.multi){
        var pos = getCaretPosition(me_this);
        var start = pos.start;
        for(;start>0 && val.charAt(start-1) != ',';start--){}
        var end = pos.start;
        for(;end<val.length && val.charAt(end) != ',';end++){}
        var val = val.substr(start,pos.start-start);
        $('#info').text("start "+start+" end "+end+" pos "+pos.start+" "+val);
      }
      return val;
    }
    // set suggestion
    function setSuggestion(val){
      user_input = val;
      if(options.multi){
        var orig = me.val();
        var pos = getCaretPosition(me_this);
        var start = pos.start;
        for(;start>0 && orig.charAt(start-1) != ',';start--){}
        var end = pos.start;
        for(;end<orig.length && orig.charAt(end) != ',';end++){}
        var new_val = orig.substr(0,start) + val + orig.substr(end);
        me.val(new_val);
        $('#info').text("set "+(start+val.length));
        setCaret(me_this,start+val.length);
      }
      else{
        me_this.focus();
        me.val(val);
      }
    }
    // get suggestions
    function getSuggestions(val){
        if (val == user_input) return false;
        // input length is less than the min required to trigger a request
        // reset input string
        // do nothing
        if (val.length < options.minchars){
            clearSuggestions();
            user_input = false;
            return false;
        }
        // if caching enabled, and user is typing (ie. length of input is increasing)
        // filter results out of suggestions from last request
        if (val.length > input_chars_size && suggestions.length && options.cache){
            var arr = [];
            for (var i=0;i<suggestions.length;i++){
                if(suggestions[i].value.substr(0,val.length).toLowerCase() == val.toLowerCase())
                    arr.push( suggestions[i] );
            }

            user_input = val;
            input_chars_size = val.length;
            suggestions = arr;
            createList(suggestions);
            return false;
        }
        else{
            // do new request
            user_input = val;
            input_chars_size = val.length;
            clearTimeout(ajaxTimer);
            ajaxTimer = setTimeout( 
                function(){ 
                  //try{
                  suggestions = [];
                  // call pre callback, if exists
                  if($.isFunction(options.pre_callback))
                      options.pre_callback();
                  // call get
                  if($.isFunction(options.get)){
                    var jsondata = options.get(val);
                    for(var i=0;i<jsondata.length;i++){
                       suggestions.push(jsondata[i]);
                    }
                    createList(suggestions);
                  }
                  if($.isFunction(options.ajax_get)){
                     options.ajax_get(val,function(jsondata){
                       for(var i=0;i<jsondata.length;i++){
                         suggestions.push(jsondata[i]);
                       }
                       createList(suggestions);
                     });
                  }
                  
                //}catch(e){
                //   alert('Error when AJAX call: '+e);
                //}
              },
              options.delay );
        }
        return false;
    };
    // create suggestions list
    function createList(arr){
        // get rid of old list
        // and clear the list removal timeout
        if(suggestions_menu) $(suggestions_menu).remove();
        killTimeout();
	
	// create holding div
	suggestions_menu = $('<div class="jqac-menu"></div>').get(0);
	
	// ovveride some necessary CSS properties 
	$(suggestions_menu).css('position','absolute');
	$(suggestions_menu).css('max-height',options.height+'px');
	$(suggestions_menu).css('overflow-y','auto');
	
	// create and populate ul
	suggestions_list = $('<ul></ul>').get(0);
	
	// loop throught arr of suggestions
	// creating an LI element for each suggestion
	for (var i=0;i<arr.length;i++){
            // format output with the input enclosed in a EM element
            // (as HTML, not DOM)
            var val = new String(arr[i].value);
            // using RE
            var re = new RegExp("("+user_input+")",'ig');
            var output = val.replace(re,'<em>$1</em>');
            // using substr
            //var st = val.toLowerCase().indexOf( user_input.toLowerCase() );
            //var len = user_input.length;
            //var output = val.substring(0,st)+"<em>"+val.substring(st,st+len)+"</em>"+val.substring(st+len);

            var span = $('<span class="jqac-link">'+output+'</span>').get(0);
            if (arr[i].info != ""){
                $(span).append($('<div class="jqac-info">'+arr[i].info+'</div>'));
            }

            $(span).attr('name',i+1);
            $(span).click(function () { setHighlightedValue(); });
            $(span).mouseover(function () { setHighlight($(this).attr('name')); });

            var li = $('<li></li>').get(0);
            $(li).append(span);

            $(suggestions_list).append(li);
        }


        // no results
        //
        if (arr.length == 0){
            $(suggestions_list).append('<li class="jqac-warning">'+options.noresults+'</li>');
        }

        $(suggestions_menu).append(suggestions_list);

	// get position of target textfield
	// position holding div below it
	// set width of holding div to width of field
	var pos = me.offset();
	
	$(suggestions_menu).css('left', pos.left + "px");
    $(suggestions_menu).css('top', ( pos.top + me.height() + 2 ) + "px");
    $(suggestions_menu).width(me.width());
	
	// set mouseover functions for div
	// when mouse pointer leaves div, set a timeout to remove the list after an interval
	// when mouse enters div, kill the timeout so the list won't be removed
	$(suggestions_menu).mouseover(function(){ killTimeout() });
	$(suggestions_menu).mouseout(function(){ resetTimeout() });
	$(suggestions_menu).ready(function(){ 
      
    });

	// add DIV to document
	$('body').append(suggestions_menu);
	
	// adjust height
	if($(suggestions_menu).height() > options.height){
       $(suggestions_menu).height(options.height);
    }
	
	// currently no item is highlighted
	current_highlight = 0;
	
	// remove list after an interval
	clearSuggestionsTimer = setTimeout(function () { clearSuggestions() }, options.timeout);
    };
    // set highlighted value
    function setHighlightedValue(){
        if(current_highlight && suggestions[current_highlight-1]){
            setSuggestion(suggestions[ current_highlight-1 ].value);
            // pass selected object to callback function, if exists
            if ($.isFunction(options.callback))
              options.callback( suggestions[current_highlight-1] );

            clearSuggestions();
        }
    };
    // change highlight according to key
    function changeHighlight(key){	
        if(!suggestions_list) return false;
        var n;
        if (key == ARRDN)
            n = current_highlight + 1;
        else if (key == ARRUP)
            n = current_highlight - 1;

        if (n > $(suggestions_list).children().size())
            n = 1;
        if (n < 1)
            n = $(suggestions_list).children().size();
        setHighlight(n);
    };
    // change highlight
    function setHighlight(n){
        if (!suggestions_list) return false;
        if (current_highlight > 0) clearHighlight();
        current_highlight = Number(n);
        var li = $(suggestions_list).children().get(current_highlight-1);
        li.className = 'jqac-highlight';
        adjustScroll(li);
        killTimeout();
    };
    // clear highlight
    function clearHighlight(){
        if (!suggestions_list)return false;
        if (current_highlight > 0){
            $(suggestions_list).children().get(current_highlight-1).className = '';
            current_highlight = 0;
        }
    };
    // clear suggestions list
    function clearSuggestions(){
        killTimeout();
        if (suggestions_menu){
          $(suggestions_menu).remove();
          suggestions_menu = null;
          current_highlight = 0;
          user_input = false;
        }
    };
    // set scroll
    function adjustScroll(el){
      if(!suggestions_menu) return false;
      var viewportHeight = suggestions_menu.clientHeight;        
      var wholeHeight = suggestions_menu.scrollHeight;
      var scrolled = suggestions_menu.scrollTop;
      var elTop = el.offsetTop;
      var elBottom = elTop + el.offsetHeight
      if(elBottom > scrolled + viewportHeight){
        suggestions_menu.scrollTop = elBottom - viewportHeight;
      }
      else if(elTop < scrolled){
        suggestions_menu.scrollTop = elTop;
      }
      return true; 
    }
    // timeout funcs
    function killTimeout(){
        clearTimeout(clearSuggestionsTimer);
    };
    function resetTimeout(){
        clearTimeout(clearSuggestionsTimer);
        clearSuggestionsTimer = setTimeout(function () { clearSuggestions() }, 1000);
    };
  
    return $(this);

  };

})($);
