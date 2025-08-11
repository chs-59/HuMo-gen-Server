/* jQuery autocompleate search for Person Ids to link into admin/include/editor_user_settings
 * 
 */

  $( function() {
    function split( val ) {
      return val.split( /;\s*/ );
    }
    function extractLast( term ) {
      return split( term ).pop();
    }
    $( ".search_gnr" )
      // don't navigate away from the field on tab when selecting an item
      .on( "keydown", function( event ) {
        if ( event.keyCode === $.ui.keyCode.TAB &&
            $( this ).autocomplete( "instance" ).menu.active ) {
          event.preventDefault();
        }
      })
      .autocomplete({
        source: function( request, response ) {
          $.getJSON( "include/json_search_pers.php", {
            term: extractLast( request.term ) + ':::' + $(this.element.get(0)).attr('data-tree')
          }, response );
        },
        search: function() {
          // custom minLength
          var term = extractLast( this.value );
          if ( term.length < 2 ) {
            return false;
          }
        },
        focus: function() {
          // prevent value inserted on focus
          return false;
        },
        select: function( event, ui ) {
          var terms = split( this.value );
          // remove the current input
          terms.pop();
          // add the selected item
          terms.push( ui.item.value );
          // add placeholder to get the comma-and-space at the end
          terms.push( "" );
          this.value = terms.join( ";" );
          return false;
        }
      });
  } );
  
// jQuery autocompleate search for places admin/include/editor_user_settings
  $( function() {
    $( ".search-place" )
      .autocomplete({
        source: function( request, response ) {
          $.getJSON( "include/json_search_place.php", {
            term: request.term 
          }, response );
        },
      });
  } );
   