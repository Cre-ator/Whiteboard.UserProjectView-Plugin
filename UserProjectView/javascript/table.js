function row_view( user_id )
{
    for ( var i = 0; i < document.getElementsByName( user_id ).length; i++ )
    {
        if ( document.getElementsByName( user_id )[i] )
        {
            var actualVisibility = document.getElementsByName( user_id )[i].style.visibility;
            if ( actualVisibility == '' || actualVisibility == 'visible' )
            {
                document.getElementsByName( user_id )[i].style.visibility = "hidden";
                document.getElementsByName( user_id )[i].style.display = "none";
            }
            else
            {
                document.getElementsByName( user_id )[i].style.visibility = "visible";
                document.getElementsByName( user_id )[i].style.display = "";
            }
        }
    }
}