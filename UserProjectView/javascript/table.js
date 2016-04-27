// data-level : level of hierarchy
// data-status: 1=show; 0=hide
$( document ).ready(
    function ()
    {
        $( ".clickable" ).click(
            function ()
            {
                var element = $( this );
                var levelMain = parseInt( element.attr( 'data-level' ), 10 );
                var statusChildren;

                if ( "+" == element.find( ".icon" ).html() )
                {
                    element.find( ".icon" ).html( "-" );
                    statusChildren = "1";
                }
                else
                {
                    element.find( ".icon" ).html( "+" );
                    statusChildren = "0";
                }

                for ( ; ; )
                {
                    element = element.next();
                    if ( null == element.attr( 'class' ) )
                    {
                        break;
                    } // next element doesn't exist

                    if ( "clickable" == element.attr( 'class' )
                        && levelMain >= element.attr( 'data-level' )
                    )
                    {
                        break; // stop if same level
                    }

                    if ( "0" == statusChildren )
                    {
                        element.hide(); // hide all
                        if ( (levelMain + 1) == element.attr( 'data-level' ) )
                        {
                            element.attr( 'data-status', 0 );
                        } // change data-status only for the next data-level
                    }
                    else if ( "1" == statusChildren )
                    {
                        if ( (levelMain + 1) == element.attr( 'data-level' ) )
                        {
                            element.attr( 'data-status', 1 ); // change data-status only for the next data-level
                            element.show();
                        }
                        else if ( "1" == element.attr( 'data-status' ) )
                        {
                            element.show();
                        }
                    }
                }
            } );
        $( ".clickable" ).trigger( "click" );
        $( ".clickable" ).hover(
            function ()
            {
                $( this ).css( 'cursor', 'pointer' );
            }, function ()
            {
                $( this ).css( 'cursor', 'auto' );
            } );
    } );